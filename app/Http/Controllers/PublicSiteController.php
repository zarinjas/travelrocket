<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\ContentBlock;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PublicSiteController extends Controller
{
    public function home(): Response
    {
        $homeContent = $this->resolvePageContent('home', $this->defaultHomeContent());

        $packages = Package::query()
            ->with('tenant:id,name,company_name')
            ->where('status', 'published')
            ->whereNotNull('start_date')
            ->where('start_date', '>=', now())
            ->orderBy('start_date')
            ->get()
            ->map(fn (Package $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'destination' => $p->destination,
                'description' => $p->description,
                'price' => (float) $p->price,
                'start_date' => $p->start_date?->format('d M Y'),
                'end_date' => $p->end_date?->format('d M Y'),
                'duration' => $p->start_date && $p->end_date ? $p->start_date->diffInDays($p->end_date) : null,
                'available_seats' => $p->available_seats,
                'is_sold_out' => $p->is_sold_out,
                'cover_image_url' => $p->cover_image_path ? '/storage/' . $p->cover_image_path : null,
                'tenant_name' => $p->tenant?->company_name ?? $p->tenant?->name,
            ])
            ->all();

        $categories = collect($packages)->pluck('category')->unique()->values()->all();

        $primaryTenant = Tenant::whereNotNull('logo_path')->first() ?? Tenant::first();

        return Inertia::render('Public/HomePage', [
            'content' => $homeContent,
            'highlights' => $homeContent['highlights'],
            'modules' => $this->modules(),
            'packages' => $packages,
            'categories' => $categories,
            'destinations' => $homeContent['destinations'] ?? [],
            'branding' => $primaryTenant ? [
                'name' => $primaryTenant->company_name ?? $primaryTenant->name,
                'logo_url' => $primaryTenant->logo_path ? '/storage/' . $primaryTenant->logo_path : null,
            ] : [],
        ]);
    }

    public function packageShow(Package $package): Response
    {
        abort_unless($package->status === 'published', 404);

        $package->load('tenant:id,name,company_name,company_phone,company_email,logo_path');

        $depositPercentage = 30;

        return Inertia::render('Public/PackageDetailPage', [
            'package' => [
                'id' => $package->id,
                'name' => $package->name,
                'category' => $package->category,
                'destination' => $package->destination,
                'description' => $package->description,
                'price' => (float) $package->price,
                'start_date' => $package->start_date?->format('d M Y'),
                'end_date' => $package->end_date?->format('d M Y'),
                'duration' => $package->start_date && $package->end_date ? $package->start_date->diffInDays($package->end_date) : null,
                'available_seats' => $package->available_seats,
                'is_sold_out' => $package->is_sold_out,
                'booking_capacity' => $package->booking_capacity,
                'cover_image_url' => $package->cover_image_path ? '/storage/' . $package->cover_image_path : null,
                'itinerary_days' => $package->itinerary_days ?? [],
                'inclusions' => $package->inclusions ?? [],
                'exclusions' => $package->exclusions ?? [],
                'pricing_tiers' => $package->pricing_tiers ?? [],
                'terms_conditions' => $package->terms_conditions,
                'tenant' => [
                    'name' => $package->tenant?->company_name ?? $package->tenant?->name,
                    'phone' => $package->tenant?->company_phone,
                    'email' => $package->tenant?->company_email,
                    'logo_url' => $package->tenant?->logo_path ? '/storage/' . $package->tenant->logo_path : null,
                ],
            ],
            'depositPercentage' => $depositPercentage,
        ]);
    }

    public function packageBook(Request $request, Package $package): RedirectResponse
    {
        abort_unless($package->status === 'published', 404);
        abort_if($package->is_sold_out, 422, 'This package is sold out.');

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:30'],
            'pax' => ['required', 'integer', 'min:1', 'max:' . $package->available_seats],
            'payment_type' => ['required', 'in:deposit,full'],
        ]);

        $totalPrice = $package->price * $validated['pax'];
        $depositPercentage = 30;
        $paymentAmount = $validated['payment_type'] === 'deposit'
            ? round($totalPrice * $depositPercentage / 100, 2)
            : $totalPrice;
        $balanceDue = $totalPrice - $paymentAmount;

        $booking = DB::transaction(function () use ($package, $validated, $totalPrice, $paymentAmount, $balanceDue) {
            $customer = Customer::query()->create([
                'tenant_id' => $package->tenant_id,
                'name' => $validated['name'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
            ]);

            $booking = Booking::query()->create([
                'tenant_id' => $package->tenant_id,
                'package_id' => $package->id,
                'customer_id' => $customer->id,
                'lead_customer_id' => $customer->id,
                'booking_number' => 'B2C-' . strtoupper(Str::random(8)),
                'total_pax' => $validated['pax'],
                'total_price' => $totalPrice,
                'balance_due' => $balanceDue,
                'booking_status' => 'confirmed',
                'payment_status' => $balanceDue <= 0 ? 'paid' : 'partial',
                'status' => 'confirmed',
                'travel_date' => $package->start_date,
                'departure_date' => $package->start_date,
                'return_date' => $package->end_date,
                'source' => 'b2c',
                'buyer_name' => $validated['name'],
                'buyer_email' => $validated['email'],
                'buyer_phone' => $validated['phone'],
            ]);

            Payment::query()->create([
                'tenant_id' => $package->tenant_id,
                'booking_id' => $booking->id,
                'amount' => $paymentAmount,
                'payment_method' => 'online_gateway',
                'payment_type' => $validated['payment_type'],
                'payment_date' => now()->toDateString(),
                'gateway_reference' => 'MOCK-' . strtoupper(Str::random(12)),
                'status' => 'success',
            ]);

            $package->increment('current_bookings', $validated['pax']);

            return $booking;
        });

        return redirect()->route('booking.confirmation', $booking);
    }

    public function bookingConfirmation(Booking $booking): Response
    {
        $booking->load(['package', 'payments', 'customer']);

        return Inertia::render('Public/BookingConfirmationPage', [
            'booking' => [
                'booking_number' => $booking->booking_number,
                'buyer_name' => $booking->buyer_name,
                'buyer_email' => $booking->buyer_email,
                'buyer_phone' => $booking->buyer_phone,
                'total_pax' => $booking->total_pax,
                'total_price' => (float) $booking->total_price,
                'balance_due' => (float) $booking->balance_due,
                'payment_status' => $booking->payment_status,
                'booking_status' => $booking->booking_status,
                'package' => [
                    'name' => $booking->package?->name,
                    'destination' => $booking->package?->destination,
                    'start_date' => $booking->package?->start_date?->format('d M Y'),
                    'end_date' => $booking->package?->end_date?->format('d M Y'),
                    'category' => $booking->package?->category,
                ],
                'payment' => $booking->payments->first() ? [
                    'amount' => (float) $booking->payments->first()->amount,
                    'payment_type' => $booking->payments->first()->payment_type,
                    'gateway_reference' => $booking->payments->first()->gateway_reference,
                    'payment_date' => $booking->payments->first()->payment_date?->format('d M Y'),
                ] : null,
            ],
        ]);
    }

    protected function modules(): array
    {
        return [
            [
                'title' => 'Catalog & Packages',
                'description' => 'Build and maintain Umrah, tour, and custom travel packages with clean pricing states.',
                'accent' => '01',
            ],
            [
                'title' => 'Passenger CRM',
                'description' => 'Keep customer profiles, contact details, passport data, and trip notes in one place.',
                'accent' => '02',
            ],
            [
                'title' => 'Bookings',
                'description' => 'Track the booking lifecycle from inquiry to paid, issued, and completed work.',
                'accent' => '03',
            ],
            [
                'title' => 'Itinerary & Tasks',
                'description' => 'Capture departures, timeline checkpoints, and operational tasks around each trip.',
                'accent' => '04',
            ],
            [
                'title' => 'Suppliers & Ops',
                'description' => 'Coordinate hotel, transport, and partner suppliers from one operational surface.',
                'accent' => '05',
            ],
            [
                'title' => 'Revenue & Reports',
                'description' => 'Monitor revenue, status mix, and weekly load while tenant data stays isolated.',
                'accent' => '06',
            ],
        ];
    }

    protected function resolvePageContent(string $page, array $defaults): array
    {
        if (! Schema::hasTable('content_blocks')) {
            return $defaults;
        }

        $records = ContentBlock::query()
            ->where('page', $page)
            ->where('is_active', true)
            ->get(['block_key', 'payload']);

        if ($records->isEmpty()) {
            return $defaults;
        }

        $resolved = $defaults;

        foreach ($records as $record) {
            $resolved[$record->block_key] = data_get($record->payload, 'value', $defaults[$record->block_key] ?? null);
        }

        return $resolved;
    }

    protected function defaultHomeContent(): array
    {
        return [
            'hero_badge' => 'Travel Agency Operating System',
            'hero_title' => 'Landing page yang lebih tenang, jelas, dan high-conviction.',
            'hero_subtitle' => 'Urus package, pelanggan, tempahan, dan prestasi agensi dalam satu workspace yang dibina untuk tindakan pantas, bukan dashboard yang serabut.',
            'primary_cta_label' => 'Get Started',
            'primary_cta_url' => '/login',
            'secondary_cta_label' => 'Browse Packages',
            'secondary_cta_url' => '#packages',
            'highlights' => [
                'Travel agency operations in one workspace',
                'Built for packages, itineraries, customers, and bookings',
                'Clean operational system for travel agencies',
            ],
            'destinations' => [],
        ];
    }
}
