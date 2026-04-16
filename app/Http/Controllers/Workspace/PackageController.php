<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PackageController extends Controller
{
    public function index(Request $request, Tenant $tenant): Response
    {
        $activeCategory = $this->resolveCategory(
            $request->string('category')->toString() ?: $request->string('type')->toString()
        );

        $packages = Package::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('bookings')
            ->withSum('bookings as revenue', 'total_price')
            ->when($activeCategory, fn ($query) => $query->where('category', $activeCategory))
            ->latest()
            ->get()
            ->map(fn (Package $package) => [
                ...$this->mapPackage($package),
                'bookings_count' => (int) ($package->bookings_count ?? 0),
                'revenue' => (float) ($package->revenue ?? 0),
            ])
            ->all();

        return Inertia::render('Workspace/Packages/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'packages' => $packages,
            'packageTypes' => $this->packageTypes(),
            'activeCategory' => $activeCategory,
        ]);
    }

    public function show(Tenant $tenant, Package $package): Response
    {
        $package->load(['bookings.leadCustomer', 'bookings.payments']);

        return Inertia::render('Workspace/Packages/ShowPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'package' => [
                ...$this->mapPackage($package),
                'bookings_count' => $package->bookings()->count(),
                'recent_bookings' => $package->bookings
                    ->sortByDesc('created_at')
                    ->take(5)
                    ->values()
                    ->map(fn ($booking) => [
                        'id' => $booking->id,
                        'booking_number' => $booking->booking_number,
                        'booking_status' => $booking->booking_status,
                        'payment_status' => $booking->payment_status,
                        'total_pax' => (int) $booking->total_pax,
                        'total_price' => (float) $booking->total_price,
                        'lead_customer' => [
                            'id' => $booking->leadCustomer?->id,
                            'full_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name,
                        ],
                    ])
                    ->all(),
            ],
        ]);
    }

    public function create(Request $request, Tenant $tenant): Response
    {
        $defaultCategory = $this->resolveCategory(
            $request->string('category')->toString() ?: $request->string('type')->toString()
        ) ?? $this->packageTypes()[0];

        return Inertia::render('Workspace/Packages/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'package' => null,
            'packageTypes' => $this->packageTypes(),
            'defaultCategory' => $defaultCategory,
            'statuses' => [Package::STATUS_DRAFT, Package::STATUS_PUBLISHED],
            'formAction' => route('packages.store', ['tenant' => $tenant]),
            'formMethod' => 'post',
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $this->validatePackage($request);
        $brochure = $validated['brochure'] ?? null;
        $coverImage = $validated['cover_image'] ?? null;
        unset($validated['brochure'], $validated['cover_image']);

        $validated['type'] = $validated['category'];
        $validated['current_bookings'] = 0;

        if ($brochure) {
            $validated['brochure_path'] = $this->storeBrochure($brochure, $tenant);
        }

        if ($coverImage) {
            $validated['cover_image_path'] = $this->storeCoverImage($coverImage, $tenant);
        }

        Package::query()->create($validated);

        return redirect()
            ->route('packages.index', ['tenant' => $tenant])
            ->with('success', 'Package created successfully.');
    }

    public function edit(Tenant $tenant, Package $package): Response
    {
        return Inertia::render('Workspace/Packages/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'package' => $this->mapPackage($package),
            'packageTypes' => $this->packageTypes(),
            'defaultCategory' => $package->category,
            'statuses' => [Package::STATUS_DRAFT, Package::STATUS_PUBLISHED],
            'formAction' => route('packages.update', ['tenant' => $tenant, 'package' => $package]),
            'formMethod' => 'put',
        ]);
    }

    public function update(Request $request, Tenant $tenant, Package $package): RedirectResponse
    {
        $validated = $this->validatePackage($request);
        $brochure = $validated['brochure'] ?? null;
        $coverImage = $validated['cover_image'] ?? null;
        unset($validated['brochure'], $validated['cover_image']);

        $validated['type'] = $validated['category'];
        $validated['current_bookings'] = $package->current_bookings;

        if ($brochure) {
            $validated['brochure_path'] = $this->storeBrochure($brochure, $tenant, $package->brochure_path);
        }

        if ($coverImage) {
            $validated['cover_image_path'] = $this->storeCoverImage($coverImage, $tenant, $package->cover_image_path);
        }

        $package->update($validated);

        return redirect()
            ->route('packages.index', ['tenant' => $tenant])
            ->with('success', 'Package updated successfully.');
    }

    public function destroy(Tenant $tenant, Package $package): RedirectResponse
    {
        if ($package->brochure_path) {
            Storage::disk('public')->delete($package->brochure_path);
        }

        $package->delete();

        return redirect()
            ->route('packages.index', ['tenant' => $tenant])
            ->with('success', 'Package deleted successfully.');
    }

    public function toggleStatus(Tenant $tenant, Package $package): RedirectResponse
    {
        $package->update([
            'status' => $package->status === Package::STATUS_PUBLISHED
                ? Package::STATUS_DRAFT
                : Package::STATUS_PUBLISHED,
        ]);

        return back()->with('success', "Package {$package->status}.");
    }

    public function duplicate(Tenant $tenant, Package $package): RedirectResponse
    {
        $clone = $package->replicate(['current_bookings']);
        $clone->name = $package->name . ' (Copy)';
        $clone->current_bookings = 0;
        $clone->status = Package::STATUS_DRAFT;
        $clone->save();

        return redirect()
            ->route('packages.edit', ['tenant' => $tenant, 'package' => $clone])
            ->with('success', 'Package duplicated. Edit the details below.');
    }

    public function bulkDelete(Request $request, Tenant $tenant): RedirectResponse
    {
        $ids = $request->validate(['ids' => ['required', 'array', 'min:1']])['ids'];

        $packages = Package::where('tenant_id', $tenant->id)->whereIn('id', $ids)->get();

        foreach ($packages as $package) {
            if ($package->brochure_path) {
                Storage::disk('public')->delete($package->brochure_path);
            }
            $package->delete();
        }

        return back()->with('success', count($ids) . ' package(s) deleted.');
    }

    public function exportCsv(Tenant $tenant): StreamedResponse
    {
        $packages = Package::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('bookings')
            ->withSum('bookings as revenue', 'total_price')
            ->latest()
            ->get();

        $headers = ['Name', 'Category', 'Destination', 'Start Date', 'End Date', 'Price (RM)', 'Capacity', 'Booked', 'Available', 'Revenue (RM)', 'Status'];

        $callback = function () use ($packages, $headers) {
            $file = fopen('php://output', 'w');
            fputcsv($file, $headers);
            foreach ($packages as $p) {
                fputcsv($file, [
                    $p->name,
                    $p->category,
                    $p->destination,
                    $p->start_date?->format('Y-m-d'),
                    $p->end_date?->format('Y-m-d'),
                    number_format($p->price, 2),
                    $p->booking_capacity,
                    $p->current_bookings,
                    $p->available_seats,
                    number_format($p->revenue ?? 0, 2),
                    $p->status,
                ]);
            }
            fclose($file);
        };

        return response()->stream($callback, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="packages-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    protected function validatePackage(Request $request): array
    {
        return $request->validate([
            'category' => ['required', Rule::in($this->packageTypes())],
            'name' => ['required', 'string', 'max:255'],
            'destination' => ['required', 'string', 'max:255'],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'itinerary' => ['nullable', 'string'],
            'booking_capacity' => ['required', 'integer', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],
            'brochure' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'],
            'cover_image' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'status' => ['required', 'in:draft,published'],
            'description' => ['nullable', 'string'],
            'itinerary_days' => ['nullable', 'array'],
            'itinerary_days.*.day' => ['required_with:itinerary_days', 'integer', 'min:1'],
            'itinerary_days.*.title' => ['required_with:itinerary_days', 'string', 'max:255'],
            'itinerary_days.*.description' => ['nullable', 'string'],
            'itinerary_days.*.activities' => ['nullable', 'array'],
            'itinerary_days.*.activities.*' => ['string', 'max:255'],
            'inclusions' => ['nullable', 'array'],
            'inclusions.*' => ['string', 'max:255'],
            'exclusions' => ['nullable', 'array'],
            'exclusions.*' => ['string', 'max:255'],
            'pricing_tiers' => ['nullable', 'array'],
            'pricing_tiers.adult' => ['nullable', 'numeric', 'min:0'],
            'pricing_tiers.child' => ['nullable', 'numeric', 'min:0'],
            'pricing_tiers.infant' => ['nullable', 'numeric', 'min:0'],
            'terms_conditions' => ['nullable', 'string'],
        ]);
    }

    protected function packageTypes(): array
    {
        return [
            'Umrah',
            'Outbound Tours',
            'Inbound Tours',
            'Domestic Tours',
        ];
    }

    protected function resolveCategory(?string $category): ?string
    {
        $category = trim((string) $category);

        return in_array($category, $this->packageTypes(), true) ? $category : null;
    }

    protected function mapPackage(Package $package): array
    {
        return [
            'id' => $package->id,
            'name' => $package->name,
            'category' => $package->category,
            'destination' => $package->destination,
            'start_date' => $package->start_date?->format('Y-m-d'),
            'end_date' => $package->end_date?->format('Y-m-d'),
            'date_range' => collect([$package->start_date?->format('d M Y'), $package->end_date?->format('d M Y')])->filter()->join(' - '),
            'itinerary' => $package->itinerary,
            'itinerary_days' => $package->itinerary_days ?? [],
            'inclusions' => $package->inclusions ?? [],
            'exclusions' => $package->exclusions ?? [],
            'pricing_tiers' => $package->pricing_tiers ?? ['adult' => null, 'child' => null, 'infant' => null],
            'terms_conditions' => $package->terms_conditions,
            'booking_capacity' => $package->booking_capacity,
            'current_bookings' => $package->current_bookings,
            'available_seats' => $package->available_seats,
            'remaining_capacity' => $package->available_seats,
            'is_sold_out' => $package->is_sold_out,
            'price' => $package->price,
            'brochure_path' => $package->brochure_path,
            'brochure_url' => $package->brochure_path ? '/storage/' . $package->brochure_path : null,
            'cover_image_path' => $package->cover_image_path,
            'cover_image_url' => $package->cover_image_path ? '/storage/' . $package->cover_image_path : null,
            'status' => $package->status,
            'description' => $package->description,
        ];
    }

    protected function storeBrochure(mixed $brochure, Tenant $tenant, ?string $oldPath = null): string
    {
        $directory = "brochures/{$tenant->id}";
        $extension = strtolower((string) $brochure->getClientOriginalExtension());
        $hash = hash('sha256', $tenant->id.'|'.Str::uuid()->toString().'|'.microtime(true));
        $fileName = $hash.($extension ? ".{$extension}" : '');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $brochure->storeAs($directory, $fileName, 'public');
    }

    protected function storeCoverImage(mixed $image, Tenant $tenant, ?string $oldPath = null): string
    {
        $directory = "covers/{$tenant->id}";
        $extension = strtolower((string) $image->getClientOriginalExtension());
        $hash = hash('sha256', $tenant->id.'|'.Str::uuid()->toString().'|'.microtime(true));
        $fileName = $hash.($extension ? ".{$extension}" : '');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $image->storeAs($directory, $fileName, 'public');
    }
}
