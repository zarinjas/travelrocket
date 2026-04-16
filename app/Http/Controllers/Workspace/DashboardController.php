<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __invoke(Tenant $tenant): Response
    {
        $bookings = Booking::query()
            ->with(['package:id,name,destination,price,booking_capacity,current_bookings', 'leadCustomer:id,name,full_name,phone,email'])
            ->latest()
            ->get();

        $paidBookings = $bookings->filter(fn (Booking $booking) =>
            $booking->payment_status === Booking::PAYMENT_STATUS_PAID
        );
        $pendingBookings = $bookings->filter(fn (Booking $booking) =>
            $booking->booking_status === Booking::BOOKING_STATUS_PENDING
        );
        $cancelledBookings = $bookings->filter(fn (Booking $booking) =>
            $booking->booking_status === Booking::BOOKING_STATUS_CANCELLED
        );
        $confirmedBookings = $bookings->filter(fn (Booking $booking) =>
            $booking->booking_status === Booking::BOOKING_STATUS_CONFIRMED
        );

        $bookingCount = $bookings->count();
        $customerCount = Customer::query()->count();
        $newLeadsToday = Customer::query()->whereDate('created_at', now()->toDateString())->count();

        $bookingsThisMonth = Booking::query()
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->count();
        $bookingsLastMonth = Booking::query()
            ->whereBetween('created_at', [now()->copy()->subMonthNoOverflow()->startOfMonth(), now()->copy()->subMonthNoOverflow()->endOfMonth()])
            ->count();
        $newBookingsToday = Booking::query()->whereDate('created_at', now()->toDateString())->count();

        // Revenue from total_price of paid bookings
        $revenueValue = (float) $paidBookings->sum(fn (Booking $booking) => (float) ($booking->total_price ?? 0));

        // Outstanding = sum of balance_due where balance > 0
        $outstandingBalance = (float) $bookings
            ->filter(fn (Booking $booking) => (float) $booking->balance_due > 0)
            ->sum(fn (Booking $booking) => (float) $booking->balance_due);

        // Total collected across all bookings
        $totalCollected = (float) $bookings->sum(fn (Booking $booking) => (float) $booking->total_price - (float) $booking->balance_due);

        $averageBookingValue = $paidBookings->count() > 0
            ? round($revenueValue / $paidBookings->count(), 2)
            : 0;
        $conversionRate = $bookingCount > 0
            ? round(($paidBookings->count() / $bookingCount) * 100, 1)
            : 0;

        $totalPackages = Package::query()->count();
        $totalParticipantsBooked = (int) Booking::query()->sum('total_pax');
        $packages = Package::query()->get();
        $availableSeats = $packages->sum(fn (Package $package) => $package->available_seats);
        $soldOutPackages = $packages->filter(fn (Package $package) => $package->is_sold_out)->count();
        $lowInventoryPackages = $packages->filter(fn (Package $package) => $package->available_seats > 0 && $package->available_seats <= 5)->count();

        // Quotation metrics
        $allQuotations = Quotation::query()->get();
        $expiringQuotations = $allQuotations
            ->where('status', 'Sent')
            ->filter(fn (Quotation $q) => $q->valid_until && \Carbon\Carbon::parse($q->valid_until)->isBetween(now()->toDateString(), now()->addDays(7)->toDateString()))
            ->count();
        $expiredQuotations = $allQuotations->where('status', 'Expired')->count();

        // Sales chart — pre-compute last 7 days on the server to avoid timezone issues
        $salesChart = collect(range(6, 0))->map(function (int $daysAgo) use ($bookings) {
            $date = now()->subDays($daysAgo);
            $dateString = $date->toDateString();
            $dayBookings = $bookings->filter(fn (Booking $b) => $b->created_at?->toDateString() === $dateString);

            return [
                'day'   => $date->format('D'),        // Mon, Tue …
                'date'  => $date->format('j M'),      // 9 Apr, 10 Apr …
                'count' => $dayBookings->count(),
                'value' => round((float) $dayBookings->sum(fn (Booking $b) => (float) $b->total_price), 2),
            ];
        })->values()->all();

        // Weekly total for summary
        $weeklyTotal = array_sum(array_column($salesChart, 'value'));
        $weeklyBookings = array_sum(array_column($salesChart, 'count'));

        // Upcoming departures (next 14 days)
        $upcomingDepartures = $bookings
            ->filter(function (Booking $booking) {
                $dep = $booking->departure_date ?? $booking->package?->start_date;
                if (!$dep) return false;
                $depDate = \Carbon\Carbon::parse($dep);
                return $depDate->isBetween(now(), now()->addDays(14)) && $booking->booking_status !== Booking::BOOKING_STATUS_CANCELLED;
            })
            ->take(5)
            ->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'customer_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name ?? 'N/A',
                'package_name' => $booking->package?->name ?? 'N/A',
                'departure_date' => ($booking->departure_date ?? $booking->package?->start_date)?->format('Y-m-d'),
                'total_pax' => (int) $booking->total_pax,
            ])
            ->values()
            ->all();

        return Inertia::render('Workspace/DashboardPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'stats' => [
                'bookings' => $bookingCount,
                'customers' => $customerCount,
                'revenue' => $revenueValue,
                'outstanding' => $outstandingBalance,
                'collected' => $totalCollected,
                'average_booking_value' => $averageBookingValue,
                'conversion_rate' => $conversionRate,
                'status_breakdown' => [
                    'pending' => $pendingBookings->count(),
                    'confirmed' => $confirmedBookings->count(),
                    'paid' => $paidBookings->count(),
                    'cancelled' => $cancelledBookings->count(),
                ],
            ],
            'dashboardMetrics' => [
                'leads_total' => $customerCount,
                'new_leads_today' => $newLeadsToday,
                'bookings_this_month' => $bookingsThisMonth,
                'bookings_last_month' => $bookingsLastMonth,
                'new_bookings_today' => $newBookingsToday,
                'total_packages' => $totalPackages,
                'total_participants' => $totalParticipantsBooked,
            ],
            'quotationMetrics' => [
                'expiring' => $expiringQuotations,
                'expired' => $expiredQuotations,
            ],
            'inventoryMetrics' => [
                'available_seats' => $availableSeats,
                'sold_out_packages' => $soldOutPackages,
                'low_inventory_packages' => $lowInventoryPackages,
            ],
            'upcomingDepartures' => $upcomingDepartures,
            'recentBookings' => $bookings->take(10)->map(fn (Booking $booking) => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'booking_status' => $booking->booking_status,
                'payment_status' => $booking->payment_status,
                'total_price' => (float) $booking->total_price,
                'balance_due' => (float) $booking->balance_due,
                'total_pax' => (int) $booking->total_pax,
                'created_at' => $booking->created_at?->toISOString(),
                'package_name' => $booking->package?->name,
                'customer_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name ?? 'N/A',
            ])->values()->all(),
            'salesChart' => [
                'days' => $salesChart,
                'weeklyTotal' => $weeklyTotal,
                'weeklyBookings' => $weeklyBookings,
            ],
        ]);
    }
}
