<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Tenant;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class TourSetupController extends Controller
{
    public function __invoke(Tenant $tenant): Response
    {
        $packages = Package::query()
            ->where('tenant_id', $tenant->id)
            ->withCount('bookings')
            ->withSum('bookings as revenue', 'total_price')
            ->get();

        $hasPublished = $packages->where('status', 'published')->isNotEmpty();
        $hasBookings = $packages->sum('bookings_count') > 0;
        $hasBrochure = $packages->whereNotNull('brochure_path')->isNotEmpty();
        $hasItinerary = $packages->filter(fn ($p) => ! empty($p->itinerary) || ! empty($p->itinerary_days))->isNotEmpty();

        $setupChecklist = [
            ['key' => 'create_package',  'label' => 'Create your first package',        'done' => $packages->isNotEmpty()],
            ['key' => 'add_itinerary',   'label' => 'Add itinerary to a package',        'done' => $hasItinerary],
            ['key' => 'upload_brochure', 'label' => 'Upload a brochure',                 'done' => $hasBrochure],
            ['key' => 'publish_package', 'label' => 'Publish a package',                 'done' => $hasPublished],
            ['key' => 'first_booking',   'label' => 'Get your first booking',            'done' => $hasBookings],
            ['key' => 'add_inclusions',  'label' => 'Configure inclusions & exclusions', 'done' => $packages->filter(fn ($p) => ! empty($p->inclusions))->isNotEmpty()],
            ['key' => 'pricing_tiers',   'label' => 'Set up pricing tiers',              'done' => $packages->filter(fn ($p) => ! empty($p->pricing_tiers))->isNotEmpty()],
        ];

        // Category breakdown with deeper metrics
        $categoryBreakdown = $packages->groupBy('category')->map(fn ($group, $category) => [
            'category' => $category,
            'count' => $group->count(),
            'published' => $group->where('status', 'published')->count(),
            'revenue' => round((float) $group->sum('revenue'), 2),
            'total_capacity' => $group->sum('booking_capacity'),
            'total_booked' => $group->sum('current_bookings'),
            'fill_rate' => $group->sum('booking_capacity') > 0
                ? round(($group->sum('current_bookings') / $group->sum('booking_capacity')) * 100, 1)
                : 0,
            'avg_price' => round($group->avg('price'), 2),
        ])->values()->all();

        // Incomplete packages — missing critical fields
        $incompletePackages = $packages->filter(function (Package $p) {
            return empty($p->itinerary) && empty($p->itinerary_days)
                || empty($p->inclusions)
                || empty($p->pricing_tiers)
                || $p->price <= 0
                || empty($p->brochure_path)
                || $p->status === 'draft';
        })->map(fn (Package $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'status' => $p->status,
            'issues' => array_values(array_filter([
                (empty($p->itinerary) && empty($p->itinerary_days)) ? 'No itinerary' : null,
                empty($p->inclusions) ? 'No inclusions' : null,
                empty($p->pricing_tiers) ? 'No pricing tiers' : null,
                $p->price <= 0 ? 'Price is zero' : null,
                empty($p->brochure_path) ? 'No brochure' : null,
                $p->status === 'draft' ? 'Still in draft' : null,
            ])),
        ])->values()->take(10)->all();

        // Top performers by revenue
        $topPerformers = $packages->filter(fn ($p) => (float) $p->revenue > 0)
            ->sortByDesc('revenue')
            ->take(5)
            ->values()
            ->map(fn (Package $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'category' => $p->category,
                'revenue' => round((float) ($p->revenue ?? 0), 2),
                'bookings_count' => (int) $p->bookings_count,
                'fill_rate' => $p->booking_capacity > 0
                    ? round(($p->current_bookings / $p->booking_capacity) * 100, 1)
                    : 0,
            ])
            ->all();

        // Upcoming departures — packages starting within 30 days
        $upcomingPackages = $packages->filter(
            fn (Package $p) => $p->start_date
                && $p->start_date->gte(Carbon::today())
                && $p->start_date->lte(Carbon::today()->addDays(30))
        )->sortBy('start_date')
        ->values()
        ->map(fn (Package $p) => [
            'id' => $p->id,
            'name' => $p->name,
            'category' => $p->category,
            'destination' => $p->destination,
            'start_date' => $p->start_date->format('d M Y'),
            'days_until' => (int) Carbon::today()->diffInDays($p->start_date, false),
            'capacity' => $p->booking_capacity,
            'booked' => $p->current_bookings,
            'fill_rate' => $p->booking_capacity > 0
                ? round(($p->current_bookings / $p->booking_capacity) * 100, 1)
                : 0,
        ])
        ->all();

        // Inventory health
        $totalCapacity = $packages->sum('booking_capacity');
        $totalBooked = $packages->sum('current_bookings');
        $inventoryHealth = [
            'total_packages' => $packages->count(),
            'published' => $packages->where('status', 'published')->count(),
            'draft' => $packages->where('status', 'draft')->count(),
            'total_capacity' => $totalCapacity,
            'total_booked' => $totalBooked,
            'overall_fill_rate' => $totalCapacity > 0
                ? round(($totalBooked / $totalCapacity) * 100, 1)
                : 0,
            'total_revenue' => round((float) $packages->sum('revenue'), 2),
            'sold_out' => $packages->filter(fn ($p) => $p->is_sold_out && $p->booking_capacity > 0)->count(),
            'low_inventory' => $packages->filter(fn ($p) =>
                $p->booking_capacity > 0
                && ! $p->is_sold_out
                && $p->available_seats <= ceil($p->booking_capacity * 0.2)
            )->count(),
        ];

        return Inertia::render('Workspace/TourSetup/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'setupChecklist' => $setupChecklist,
            'categoryBreakdown' => $categoryBreakdown,
            'incompletePackages' => $incompletePackages,
            'topPerformers' => $topPerformers,
            'upcomingPackages' => $upcomingPackages,
            'inventoryHealth' => $inventoryHealth,
            'packageTypes' => ['Umrah', 'Outbound Tours', 'Inbound Tours', 'Domestic Tours'],
        ]);
    }
}
