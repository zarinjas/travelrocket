<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\RoomingList;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class RoomingListController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $packages = Package::query()
            ->where('tenant_id', $tenant->id)
            ->select(['id', 'name', 'destination', 'start_date', 'end_date'])
            ->get();

        return Inertia::render('Workspace/Packages/RoomingListIndex', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'packages' => $packages,
        ]);
    }

    public function show(Tenant $tenant, Package $package): Response
    {
        $paidBookings = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('package_id', $package->id)
            ->whereIn('payment_status', [Booking::PAYMENT_STATUS_PAID, Booking::PAYMENT_STATUS_PARTIAL])
            ->with([
                'leadCustomer:id,name,full_name,passport_number,date_of_birth',
                'passengers:id,name,full_name,passport_number,date_of_birth',
            ])
            ->get();

        $passengers = collect();

        foreach ($paidBookings as $booking) {
            if ($booking->leadCustomer) {
                $passengers->push($booking->leadCustomer);
            }
            foreach ($booking->passengers as $passenger) {
                $passengers->push($passenger);
            }
        }

        $passengers = $passengers
            ->unique('id')
            ->values()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'name'            => $c->name ?: $c->full_name,
                'passport_number' => $c->passport_number,
                'date_of_birth'   => $c->date_of_birth,
            ]);

        $roomingList = RoomingList::query()
            ->where('tenant_id', $tenant->id)
            ->where('package_id', $package->id)
            ->first();

        return Inertia::render('Workspace/Packages/RoomingListPage', [
            'workspace'  => $tenant->only(['id', 'name', 'slug']),
            'package'    => $package->only(['id', 'name', 'destination', 'start_date', 'end_date', 'inclusions']),
            'passengers' => $passengers,
            'savedRooms' => $roomingList?->rooms ?? [],
        ]);
    }

    public function save(Request $request, Tenant $tenant, Package $package): RedirectResponse
    {
        $validated = $request->validate([
            'rooms'                  => 'required|array',
            'rooms.*.id'             => 'required|string',
            'rooms.*.type'           => 'required|in:single,twin,double,triple,quad',
            'rooms.*.label'          => 'nullable|string|max:60',
            'rooms.*.passengers'     => 'array',
            'rooms.*.passengers.*'   => 'integer|exists:customers,id',
        ]);

        RoomingList::updateOrCreate(
            ['tenant_id' => $tenant->id, 'package_id' => $package->id],
            ['rooms' => $validated['rooms']]
        );

        return back()->with('success', 'Rooming list berjaya disimpan.');
    }

    public function generatePdf(Tenant $tenant, Package $package): HttpResponse
    {
        $paidBookings = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('package_id', $package->id)
            ->whereIn('payment_status', [Booking::PAYMENT_STATUS_PAID, Booking::PAYMENT_STATUS_PARTIAL])
            ->with([
                'leadCustomer:id,name,full_name,passport_number,date_of_birth',
                'passengers:id,name,full_name,passport_number,date_of_birth',
            ])
            ->get();

        $passengers = collect();

        foreach ($paidBookings as $booking) {
            if ($booking->leadCustomer) {
                $passengers->push($booking->leadCustomer);
            }
            foreach ($booking->passengers as $passenger) {
                $passengers->push($passenger);
            }
        }

        $passengers = $passengers
            ->unique('id')
            ->values()
            ->map(fn ($c) => [
                'id'              => $c->id,
                'name'            => $c->name ?: $c->full_name,
                'passport_number' => $c->passport_number,
                'date_of_birth'   => $c->date_of_birth,
            ]);

        $roomingList = RoomingList::query()
            ->where('tenant_id', $tenant->id)
            ->where('package_id', $package->id)
            ->first();

        $html = view('pdf.tour-confirmation', [
            'tenant'      => $tenant->only(['id', 'name', 'company_name', 'company_address', 'company_phone', 'company_email', 'company_website', 'logo_path']),
            'package'     => $package->only(['id', 'name', 'destination', 'start_date', 'end_date', 'itinerary_days', 'inclusions', 'exclusions', 'flight_info', 'visa_info', 'meal_plan']),
            'passengers'  => $passengers,
            'savedRooms'  => $roomingList?->rooms ?? [],
        ])->render();

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4')
            ->setOption('isPhpEnabled', true);

        return $pdf->download("tour-confirmation-{$package->id}-" . now()->format('Ymd') . '.pdf');
    }
}
