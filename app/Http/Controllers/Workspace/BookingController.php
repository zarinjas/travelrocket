<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Mail\TourismLetterMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Tenant;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class BookingController extends Controller
{
    public function index(Request $request, Tenant $tenant): Response
    {
        $search = trim((string) $request->query('search', ''));
        $bookingStatus = (string) $request->query('booking_status', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');
        $seat = (string) $request->query('seat', 'all'); // all | available | sold_out
        $sort = (string) $request->query('sort', 'newest'); // newest | oldest | balance_desc | price_desc
        $perPage = (int) $request->query('per_page', 20);
        $perPage = max(10, min(100, $perPage));

        $query = Booking::query()
            ->with([
                'package:id,name,destination,booking_capacity,current_bookings',
                'leadCustomer:id,name,full_name,phone,email',
            ])
            ->withCount('passengers')
            ->withSum('payments', 'amount');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('flight_number', 'like', "%{$search}%")
                    ->orWhere('flight_name', 'like', "%{$search}%")
                    ->orWhereHas('leadCustomer', function ($leadQuery) use ($search): void {
                        $leadQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%");
                    })
                    ->orWhereHas('package', function ($packageQuery) use ($search): void {
                        $packageQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%");
                    });
            });
        }

        if ($bookingStatus !== 'all') {
            $query->where('booking_status', $bookingStatus);
        }

        if ($paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }

        if ($seat === 'available') {
            $query->whereHas('package', fn ($packageQuery) => $packageQuery->whereColumn('booking_capacity', '>', 'current_bookings'));
        }

        if ($seat === 'sold_out') {
            $query->whereHas('package', fn ($packageQuery) => $packageQuery->whereColumn('booking_capacity', '<=', 'current_bookings'));
        }

        switch ($sort) {
            case 'oldest':
                $query->orderBy('id');
                break;
            case 'balance_desc':
                $query->orderByDesc('balance_due')->orderByDesc('id');
                break;
            case 'price_desc':
                $query->orderByDesc('total_price')->orderByDesc('id');
                break;
            case 'newest':
            default:
                $query->orderByDesc('id');
                break;
        }

        return Inertia::render('Workspace/Bookings/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'filters' => [
                'search' => $search,
                'booking_status' => $bookingStatus,
                'payment_status' => $paymentStatus,
                'seat' => $seat,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'bookingStatusOptions' => Booking::query()
                ->select('booking_status')
                ->distinct()
                ->pluck('booking_status')
                ->filter()
                ->values()
                ->all(),
            'paymentStatusOptions' => Booking::query()
                ->select('payment_status')
                ->distinct()
                ->pluck('payment_status')
                ->filter()
                ->values()
                ->all(),
            'bookings' => $query
                ->paginate($perPage)
                ->withQueryString()
                ->through(fn (Booking $booking) => [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'booking_status' => $booking->booking_status,
                    'payment_status' => $booking->payment_status,
                    'departure_date' => $booking->departure_date?->format('Y-m-d') ?: $booking->package?->start_date?->format('Y-m-d'),
                    'return_date' => $booking->return_date?->format('Y-m-d') ?: $booking->package?->end_date?->format('Y-m-d'),
                    'flight_name' => $booking->flight_name,
                    'flight_number' => $booking->flight_number,
                    'total_pax' => (int) $booking->total_pax,
                    'total_price' => (float) $booking->total_price,
                    'balance_due' => (float) $booking->balance_due,
                    'passengers_count' => (int) ($booking->passengers_count ?? 0),
                    'payments_total' => (float) ($booking->payments_sum_amount ?? 0),
                    'package' => [
                        'id' => $booking->package?->id,
                        'name' => $booking->package?->name,
                        'destination' => $booking->package?->destination,
                        'booking_capacity' => (int) ($booking->package?->booking_capacity ?? 0),
                        'current_bookings' => (int) ($booking->package?->current_bookings ?? 0),
                        'available_seats' => (int) ($booking->package?->available_seats ?? 0),
                        'is_sold_out' => (bool) ($booking->package?->is_sold_out ?? true),
                    ],
                    'lead_customer' => [
                        'id' => $booking->leadCustomer?->id,
                        'full_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name,
                    ],
                ]),
        ]);
    }

    public function tourismLettersIndex(Request $request, Tenant $tenant): Response
    {
        $search = trim((string) $request->query('search', ''));
        $status = (string) $request->query('status', 'all');
        $destination = trim((string) $request->query('destination', ''));
        $departureFrom = trim((string) $request->query('departure_from', ''));
        $departureTo = trim((string) $request->query('departure_to', ''));
        $actionNeeded = $request->boolean('action_needed', false);

        $query = Booking::query()
            ->with([
                'package:id,name,destination,start_date,end_date',
                'leadCustomer:id,name,full_name,passport_number,email,phone',
                'passengers:id,name,full_name,passport_number',
            ])
            ->latest();

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('booking_number', 'like', "%{$search}%")
                    ->orWhere('flight_number', 'like', "%{$search}%")
                    ->orWhere('flight_name', 'like', "%{$search}%")
                    ->orWhereHas('leadCustomer', function ($leadQuery) use ($search): void {
                        $leadQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('full_name', 'like', "%{$search}%")
                            ->orWhere('passport_number', 'like', "%{$search}%");
                    })
                    ->orWhereHas('package', function ($packageQuery) use ($search): void {
                        $packageQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('destination', 'like', "%{$search}%");
                    });
            });
        }

        if ($status === 'ready') {
            $query
                ->whereNotNull('flight_name')
                ->whereNotNull('flight_number')
                ->where(function ($builder): void {
                    $builder
                        ->whereNotNull('departure_date')
                        ->orWhereHas('package', fn ($packageQuery) => $packageQuery->whereNotNull('start_date'));
                })
                ->where(function ($builder): void {
                    $builder
                        ->whereNotNull('return_date')
                        ->orWhereHas('package', fn ($packageQuery) => $packageQuery->whereNotNull('end_date'));
                });
        }

        if ($status === 'incomplete') {
            $query->where(function ($builder): void {
                $builder
                    ->whereNull('flight_name')
                    ->orWhereNull('flight_number')
                    ->orWhere(function ($dateBuilder): void {
                        $dateBuilder
                            ->whereNull('departure_date')
                            ->whereDoesntHave('package', fn ($packageQuery) => $packageQuery->whereNotNull('start_date'));
                    })
                    ->orWhere(function ($dateBuilder): void {
                        $dateBuilder
                            ->whereNull('return_date')
                            ->whereDoesntHave('package', fn ($packageQuery) => $packageQuery->whereNotNull('end_date'));
                    });
            });
        }

        if ($destination !== '') {
            $query->whereHas('package', function ($packageQuery) use ($destination): void {
                $packageQuery->where('destination', $destination);
            });
        }

        if ($departureFrom !== '') {
            $query->where(function ($builder) use ($departureFrom): void {
                $builder
                    ->whereDate('departure_date', '>=', $departureFrom)
                    ->orWhere(function ($fallbackBuilder) use ($departureFrom): void {
                        $fallbackBuilder
                            ->whereNull('departure_date')
                            ->whereHas('package', fn ($packageQuery) => $packageQuery->whereDate('start_date', '>=', $departureFrom));
                    });
            });
        }

        if ($departureTo !== '') {
            $query->where(function ($builder) use ($departureTo): void {
                $builder
                    ->whereDate('departure_date', '<=', $departureTo)
                    ->orWhere(function ($fallbackBuilder) use ($departureTo): void {
                        $fallbackBuilder
                            ->whereNull('departure_date')
                            ->whereHas('package', fn ($packageQuery) => $packageQuery->whereDate('start_date', '<=', $departureTo));
                    });
            });
        }

        if ($actionNeeded) {
            $today = now()->toDateString();
            $cutoff = now()->addDays(7)->toDateString();

            $query
                ->where(function ($builder): void {
                    $builder
                        ->whereNull('flight_name')
                        ->orWhereNull('flight_number')
                        ->orWhere(function ($dateBuilder): void {
                            $dateBuilder
                                ->whereNull('departure_date')
                                ->whereDoesntHave('package', fn ($packageQuery) => $packageQuery->whereNotNull('start_date'));
                        })
                        ->orWhere(function ($dateBuilder): void {
                            $dateBuilder
                                ->whereNull('return_date')
                                ->whereDoesntHave('package', fn ($packageQuery) => $packageQuery->whereNotNull('end_date'));
                        });
                })
                ->where(function ($builder) use ($today, $cutoff): void {
                    $builder
                        ->whereBetween('departure_date', [$today, $cutoff])
                        ->orWhere(function ($fallbackBuilder) use ($today, $cutoff): void {
                            $fallbackBuilder
                                ->whereNull('departure_date')
                                ->whereHas('package', fn ($packageQuery) => $packageQuery->whereBetween('start_date', [$today, $cutoff]));
                        });
                });
        }

        $letters = $query
            ->paginate(12)
            ->withQueryString()
            ->through(function (Booking $booking) use ($tenant): array {
                $lead = $booking->leadCustomer;
                $passenger = $booking->passengers->first() ?: $lead;
                $departureDate = $booking->departure_date?->format('Y-m-d') ?: $booking->package?->start_date?->format('Y-m-d');
                $returnDate = $booking->return_date?->format('Y-m-d') ?: $booking->package?->end_date?->format('Y-m-d');

                return [
                    'id' => $booking->id,
                    'booking_number' => $booking->booking_number,
                    'lead_name' => $lead?->name ?? $lead?->full_name,
                    'lead_email' => $lead?->email,
                    'passenger_name' => $passenger?->name ?? $passenger?->full_name,
                    'passport_number' => $passenger?->passport_number,
                    'destination' => $booking->package?->destination,
                    'package_name' => $booking->package?->name,
                    'departure_date' => $departureDate,
                    'return_date' => $returnDate,
                    'flight_name' => $booking->flight_name,
                    'flight_number' => $booking->flight_number,
                    'letter_ready' => $this->hasTourismLetterMinimumData($booking),
                    'download_url' => route('bookings.tourism-letter.download', ['tenant' => $tenant->slug, 'booking' => $booking->id], false),
                    'show_url' => route('bookings.show', ['tenant' => $tenant->slug, 'booking' => $booking->id], false),
                    'edit_url' => route('bookings.edit', ['tenant' => $tenant->slug, 'booking' => $booking->id], false),
                ];
            });

        $destinationOptions = Package::query()
            ->whereNotNull('destination')
            ->where('destination', '!=', '')
            ->distinct()
            ->orderBy('destination')
            ->pluck('destination')
            ->values()
            ->all();

        return Inertia::render('Workspace/TourismLetters/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'letters' => $letters,
            'destinationOptions' => $destinationOptions,
            'filters' => [
                'search' => $search,
                'status' => $status,
                'destination' => $destination,
                'departure_from' => $departureFrom,
                'departure_to' => $departureTo,
                'action_needed' => $actionNeeded,
            ],
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Workspace/Bookings/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'booking' => null,
            'bookingStatuses' => [
                Booking::BOOKING_STATUS_PENDING,
                Booking::BOOKING_STATUS_CONFIRMED,
                Booking::BOOKING_STATUS_CANCELLED,
            ],
            'packages' => Package::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'category', 'name', 'destination', 'start_date', 'end_date', 'price', 'booking_capacity', 'current_bookings'])
                ->map(fn (Package $package) => $this->serializePackageOption($package))
                ->values()
                ->all(),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'full_name'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'full_name' => $customer->name ?: $customer->full_name,
                ])
                ->values()
                ->all(),
            'bookingDetail' => null,
            'formAction' => route('bookings.store', ['tenant' => $tenant]),
            'formMethod' => 'post',
        ]);
    }

    public function show(Tenant $tenant, Booking $booking): Response
    {
        $booking->load(['package', 'leadCustomer', 'passengers', 'payments']);

        return Inertia::render('Workspace/Bookings/ShowPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'booking' => $this->serializeBookingDetail($booking),
            'invoiceSummary' => [
                'issued_at' => now()->format('Y-m-d'),
                'currency' => 'RM',
                'subtotal' => (float) $booking->total_price,
                'total_paid' => (float) $booking->payments->sum('amount'),
                'balance_due' => (float) $booking->balance_due,
            ],
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $this->validateBooking($request);

        DB::transaction(function () use ($validated, $tenant): void {
            $passengerIds = $this->normalizePassengerIds(
                $validated['lead_customer_id'],
                $validated['passenger_ids'] ?? []
            );

            $seatCount = count($passengerIds);
            $reservesSeats = $validated['booking_status'] !== Booking::BOOKING_STATUS_CANCELLED;
            $package = Package::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($validated['package_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($reservesSeats && $package->available_seats < $seatCount) {
                throw ValidationException::withMessages([
                    'package_id' => 'Selected package does not have enough seats left.',
                ]);
            }

            if ($reservesSeats) {
                $package->forceFill([
                    'current_bookings' => (int) $package->current_bookings + $seatCount,
                ])->save();
            }

            $totalPax = $seatCount;
            $totalPrice = (float) $validated['total_price'];

            $booking = Booking::query()->create([
                'package_id' => $validated['package_id'],
                'lead_customer_id' => $validated['lead_customer_id'],
                'booking_number' => $this->generateBookingNumber($tenant),
                'total_pax' => $totalPax,
                'total_price' => $totalPrice,
                'balance_due' => $totalPrice,
                'booking_status' => $validated['booking_status'],
                'payment_status' => Booking::PAYMENT_STATUS_UNPAID,
                'departure_date' => $validated['departure_date'] ?? null,
                'return_date' => $validated['return_date'] ?? null,
                'flight_name' => $validated['flight_name'] ?? null,
                'flight_number' => $validated['flight_number'] ?? null,
                'customer_id' => $validated['lead_customer_id'],
                'status' => $validated['booking_status'],
            ]);

            $booking->passengers()->sync($passengerIds);
        });

        return redirect()
            ->route('bookings.index', ['tenant' => $tenant])
            ->with('success', 'Booking created successfully.');
    }

    public function edit(Tenant $tenant, Booking $booking): Response
    {
        return Inertia::render('Workspace/Bookings/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'booking' => [
                'id' => $booking->id,
                'package_id' => $booking->package_id,
                'lead_customer_id' => $booking->lead_customer_id ?: $booking->customer_id,
                'passenger_ids' => $booking->passengers()->pluck('customers.id')->all(),
                'booking_status' => $booking->booking_status,
                'total_price' => (float) $booking->total_price,
                'departure_date' => $booking->departure_date?->format('Y-m-d') ?: $booking->package?->start_date?->format('Y-m-d'),
                'return_date' => $booking->return_date?->format('Y-m-d') ?: $booking->package?->end_date?->format('Y-m-d'),
                'flight_name' => $booking->flight_name,
                'flight_number' => $booking->flight_number,
            ],
            'bookingStatuses' => [
                Booking::BOOKING_STATUS_PENDING,
                Booking::BOOKING_STATUS_CONFIRMED,
                Booking::BOOKING_STATUS_CANCELLED,
            ],
            'packages' => Package::query()
                ->orderBy('category')
                ->orderBy('name')
                ->get(['id', 'category', 'name', 'destination', 'start_date', 'end_date', 'price']),
            'customers' => Customer::query()
                ->orderBy('name')
                ->get(['id', 'name', 'full_name'])
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'full_name' => $customer->name ?: $customer->full_name,
                ])
                ->values()
                ->all(),
            'bookingDetail' => $this->serializeBookingDetail($booking->load(['package', 'leadCustomer', 'passengers', 'payments'])),
            'formAction' => route('bookings.update', ['tenant' => $tenant, 'booking' => $booking]),
            'formMethod' => 'put',
        ]);
    }

    public function update(Request $request, Tenant $tenant, Booking $booking): RedirectResponse
    {
        $validated = $this->validateBooking($request);

        DB::transaction(function () use ($validated, $booking, $tenant): void {
            $passengerIds = $this->normalizePassengerIds(
                $validated['lead_customer_id'],
                $validated['passenger_ids'] ?? []
            );

            $newSeatCount = count($passengerIds);
            $oldSeatCount = $booking->booking_status !== Booking::BOOKING_STATUS_CANCELLED ? (int) $booking->total_pax : 0;
            $reservesSeats = $validated['booking_status'] !== Booking::BOOKING_STATUS_CANCELLED;
            $newPackageId = (int) $validated['package_id'];
            $oldPackageId = (int) $booking->package_id;

            $packageIds = array_values(array_unique(array_filter([$oldPackageId, $newPackageId])));
            $packages = Package::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('id', $packageIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            $oldPackage = $packages->get($oldPackageId);
            $newPackage = $packages->get($newPackageId);

            if (! $oldPackage || ! $newPackage) {
                throw ValidationException::withMessages([
                    'package_id' => 'Selected package could not be loaded for seat validation.',
                ]);
            }

            if ($oldPackageId === $newPackageId) {
                $updatedCurrentBookings = (int) $oldPackage->current_bookings - $oldSeatCount + ($reservesSeats ? $newSeatCount : 0);

                if ($updatedCurrentBookings < 0 || $updatedCurrentBookings > (int) $oldPackage->booking_capacity) {
                    throw ValidationException::withMessages([
                        'package_id' => 'Selected package does not have enough seats left.',
                    ]);
                }

                $oldPackage->forceFill([
                    'current_bookings' => $updatedCurrentBookings,
                ])->save();
            } else {
                $updatedOldBookings = max(0, (int) $oldPackage->current_bookings - $oldSeatCount);

                if ($reservesSeats && $newPackage->available_seats < $newSeatCount) {
                    throw ValidationException::withMessages([
                        'package_id' => 'Selected package does not have enough seats left.',
                    ]);
                }

                $oldPackage->forceFill([
                    'current_bookings' => $updatedOldBookings,
                ])->save();

                if ($reservesSeats) {
                    $newPackage->forceFill([
                        'current_bookings' => (int) $newPackage->current_bookings + $newSeatCount,
                    ])->save();
                }
            }

            $totalPax = $newSeatCount;
            $totalPrice = (float) $validated['total_price'];
            $totalPaid = (float) $booking->payments()->sum('amount');
            $balanceDue = max(0, $totalPrice - $totalPaid);

            $paymentStatus = Booking::PAYMENT_STATUS_UNPAID;
            if ($balanceDue <= 0 && $totalPrice > 0) {
                $paymentStatus = Booking::PAYMENT_STATUS_PAID;
            } elseif ($totalPaid > 0) {
                $paymentStatus = Booking::PAYMENT_STATUS_PARTIAL;
            }

            $booking->update([
                'package_id' => $validated['package_id'],
                'lead_customer_id' => $validated['lead_customer_id'],
                'total_pax' => $totalPax,
                'total_price' => $totalPrice,
                'balance_due' => $balanceDue,
                'booking_status' => $validated['booking_status'],
                'payment_status' => $paymentStatus,
                'departure_date' => $validated['departure_date'] ?? null,
                'return_date' => $validated['return_date'] ?? null,
                'flight_name' => $validated['flight_name'] ?? null,
                'flight_number' => $validated['flight_number'] ?? null,
                'customer_id' => $validated['lead_customer_id'],
                'status' => $validated['booking_status'],
            ]);

            $booking->passengers()->sync($passengerIds);
        });

        return redirect()
            ->route('bookings.index', ['tenant' => $tenant])
            ->with('success', 'Booking updated successfully.');
    }

    public function tourismLetterEdit(Tenant $tenant, Booking $booking): Response
    {
        $booking->load(['package', 'leadCustomer', 'passengers']);

        return Inertia::render('Workspace/TourismLetters/EditPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'letter' => [
                'id' => $booking->id,
                'booking_number' => $booking->booking_number,
                'lead_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name,
                'lead_email' => $booking->leadCustomer?->email,
                'passenger_name' => $booking->passengers->first()?->name ?? $booking->passengers->first()?->full_name ?? $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name,
                'passport_number' => $booking->passengers->first()?->passport_number ?? $booking->leadCustomer?->passport_number,
                'destination' => $booking->package?->destination,
                'package_name' => $booking->package?->name,
                'departure_date' => $booking->departure_date?->format('Y-m-d') ?: $booking->package?->start_date?->format('Y-m-d'),
                'return_date' => $booking->return_date?->format('Y-m-d') ?: $booking->package?->end_date?->format('Y-m-d'),
                'flight_name' => $booking->flight_name,
                'flight_number' => $booking->flight_number,
            ],
            'formAction' => route('bookings.tourism-letter.update', ['tenant' => $tenant, 'booking' => $booking]),
        ]);
    }

    public function updateTourismLetter(Request $request, Tenant $tenant, Booking $booking): RedirectResponse
    {
        $validated = $request->validate([
            'departure_date' => ['required', 'date'],
            'return_date' => ['required', 'date', 'after_or_equal:departure_date'],
            'flight_name' => ['required', 'string', 'max:120'],
            'flight_number' => ['required', 'string', 'max:80'],
        ]);

        $booking->update([
            'departure_date' => $validated['departure_date'],
            'return_date' => $validated['return_date'],
            'flight_name' => $validated['flight_name'],
            'flight_number' => $validated['flight_number'],
        ]);

        return redirect()
            ->route('tourism-letters.index', ['tenant' => $tenant])
            ->with('success', 'Maklumat surat melancong telah dikemaskini.');
    }

    public function previewTourismLetter(Tenant $tenant, Booking $booking): HttpResponse
    {
        $booking->load(['tenant', 'package', 'leadCustomer', 'passengers']);
        $pdf = $this->buildTourismLetterPdf($booking);

        return $pdf->stream('surat-melancong-'.$booking->booking_number.'.pdf');
    }

    public function downloadTourismLetter(Tenant $tenant, Booking $booking): HttpResponse
    {
        $booking->load(['tenant', 'package', 'leadCustomer', 'passengers']);
        $pdf = $this->buildTourismLetterPdf($booking);

        return $pdf->download('surat-melancong-'.$booking->booking_number.'.pdf');
    }

    public function sendTourismLetterEmail(Tenant $tenant, Booking $booking): RedirectResponse
    {
        $booking->load(['tenant', 'package', 'leadCustomer', 'passengers']);

        $customerEmail = $booking->leadCustomer?->email;
        if (! $customerEmail) {
            throw ValidationException::withMessages([
                'tourism_letter' => 'Lead customer email is required to send tourism letter.',
            ]);
        }

        Mail::to($customerEmail)->send(new TourismLetterMail($booking));

        return back()->with('success', 'Surat melancong telah dihantar ke email lead customer.');
    }

    public function destroy(Tenant $tenant, Booking $booking): RedirectResponse
    {
        DB::transaction(function () use ($booking, $tenant): void {
            $package = Package::query()
                ->where('tenant_id', $tenant->id)
                ->whereKey($booking->package_id)
                ->lockForUpdate()
                ->first();

            if ($package && $booking->booking_status !== Booking::BOOKING_STATUS_CANCELLED) {
                $package->forceFill([
                    'current_bookings' => max(0, (int) $package->current_bookings - (int) $booking->total_pax),
                ])->save();
            }

            foreach ($booking->payments as $payment) {
                if ($payment->receipt_path) {
                    Storage::disk('public')->delete($payment->receipt_path);
                }
            }

            $booking->delete();
        });

        return redirect()
            ->route('bookings.index', ['tenant' => $tenant])
            ->with('success', 'Booking deleted successfully.');
    }

    public function bulkDelete(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['integer'],
        ]);

        $bookings = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->whereIn('id', $validated['ids'])
            ->with('payments')
            ->get();

        DB::transaction(function () use ($bookings, $tenant): void {
            foreach ($bookings as $booking) {
                $package = Package::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereKey($booking->package_id)
                    ->lockForUpdate()
                    ->first();

                if ($package && $booking->booking_status !== Booking::BOOKING_STATUS_CANCELLED) {
                    $package->forceFill([
                        'current_bookings' => max(0, (int) $package->current_bookings - (int) $booking->total_pax),
                    ])->save();
                }

                foreach ($booking->payments as $payment) {
                    if ($payment->receipt_path) {
                        Storage::disk('public')->delete($payment->receipt_path);
                    }
                }

                $booking->delete();
            }
        });

        return back()->with('success', count($bookings) . ' booking(s) deleted.');
    }

    public function exportCsv(Request $request, Tenant $tenant): StreamedResponse
    {
        $search = trim((string) $request->query('search', ''));
        $bookingStatus = (string) $request->query('booking_status', 'all');
        $paymentStatus = (string) $request->query('payment_status', 'all');

        $query = Booking::query()
            ->where('tenant_id', $tenant->id)
            ->with(['package:id,name,destination', 'leadCustomer:id,name,full_name,phone,email'])
            ->withSum('payments', 'amount');

        if ($search !== '') {
            $query->where(function ($builder) use ($search): void {
                $builder
                    ->where('booking_number', 'like', "%{$search}%")
                    ->orWhereHas('leadCustomer', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('full_name', 'like', "%{$search}%"))
                    ->orWhereHas('package', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        }

        if ($bookingStatus !== 'all') {
            $query->where('booking_status', $bookingStatus);
        }

        if ($paymentStatus !== 'all') {
            $query->where('payment_status', $paymentStatus);
        }

        $bookings = $query->orderByDesc('id')->get();

        return new StreamedResponse(function () use ($bookings): void {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Booking #', 'Customer', 'Phone', 'Email', 'Package', 'Destination', 'Pax', 'Total (RM)', 'Paid (RM)', 'Balance (RM)', 'Booking Status', 'Payment Status', 'Departure', 'Return']);

            foreach ($bookings as $booking) {
                fputcsv($handle, [
                    $booking->booking_number,
                    $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name ?? '',
                    $booking->leadCustomer?->phone ?? '',
                    $booking->leadCustomer?->email ?? '',
                    $booking->package?->name ?? '',
                    $booking->package?->destination ?? '',
                    $booking->total_pax,
                    number_format((float) $booking->total_price, 2, '.', ''),
                    number_format((float) ($booking->payments_sum_amount ?? 0), 2, '.', ''),
                    number_format((float) $booking->balance_due, 2, '.', ''),
                    $booking->booking_status,
                    $booking->payment_status,
                    $booking->departure_date?->format('Y-m-d') ?? '',
                    $booking->return_date?->format('Y-m-d') ?? '',
                ]);
            }

            fclose($handle);
        }, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="bookings-' . now()->format('Y-m-d') . '.csv"',
        ]);
    }

    private function serializePackageOption(Package $package): array
    {
        return [
            'id' => $package->id,
            'category' => $package->category,
            'name' => $package->name,
            'destination' => $package->destination,
            'start_date' => $package->start_date?->format('Y-m-d'),
            'end_date' => $package->end_date?->format('Y-m-d'),
            'price' => (float) $package->price,
            'booking_capacity' => (int) $package->booking_capacity,
            'current_bookings' => (int) $package->current_bookings,
            'available_seats' => $package->available_seats,
            'is_sold_out' => $package->is_sold_out,
        ];
    }

    protected function validateBooking(Request $request): array
    {
        $tenantId = $request->user()?->tenant_id;

        return $request->validate([
            'package_id' => [
                'required',
                'integer',
                Rule::exists('packages', 'id')->where('tenant_id', $tenantId),
            ],
            'lead_customer_id' => [
                'required',
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'passenger_ids' => ['nullable', 'array'],
            'passenger_ids.*' => [
                'integer',
                Rule::exists('customers', 'id')->where('tenant_id', $tenantId),
            ],
            'total_price' => ['required', 'numeric', 'min:0'],
            'booking_status' => ['required', 'in:pending,confirmed,cancelled'],
            'departure_date' => ['nullable', 'date'],
            'return_date' => ['nullable', 'date', 'after_or_equal:departure_date'],
            'flight_name' => ['nullable', 'string', 'max:120'],
            'flight_number' => ['nullable', 'string', 'max:80'],
        ]);
    }

    private function normalizePassengerIds(int $leadCustomerId, array $passengerIds): array
    {
        return collect([$leadCustomerId, ...$passengerIds])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();
    }

    private function generateBookingNumber(Tenant $tenant): string
    {
        $date = now()->format('Ymd');
        $prefix = "BK-{$date}-";

        $todayCount = Booking::query()
            ->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->where('booking_number', 'like', $prefix.'%')
            ->count();

        return sprintf('%s%03d', $prefix, $todayCount + 1);
    }

    private function serializeBookingDetail(Booking $booking): array
    {
        return [
            'id' => $booking->id,
            'booking_number' => $booking->booking_number,
            'total_pax' => (int) $booking->total_pax,
            'total_price' => (float) $booking->total_price,
            'balance_due' => (float) $booking->balance_due,
            'booking_status' => $booking->booking_status,
            'payment_status' => $booking->payment_status,
            'departure_date' => $booking->departure_date?->format('Y-m-d') ?: $booking->package?->start_date?->format('Y-m-d'),
            'return_date' => $booking->return_date?->format('Y-m-d') ?: $booking->package?->end_date?->format('Y-m-d'),
            'flight_name' => $booking->flight_name,
            'flight_number' => $booking->flight_number,
            'package' => [
                'id' => $booking->package?->id,
                'name' => $booking->package?->name,
                'destination' => $booking->package?->destination,
                'price' => (float) ($booking->package?->price ?? 0),
            ],
            'lead_customer' => [
                'id' => $booking->leadCustomer?->id,
                'full_name' => $booking->leadCustomer?->name ?? $booking->leadCustomer?->full_name,
                'phone' => $booking->leadCustomer?->phone,
                'email' => $booking->leadCustomer?->email,
            ],
            'passengers' => $booking->passengers
                ->map(fn (Customer $customer) => [
                    'id' => $customer->id,
                    'full_name' => $customer->name ?: $customer->full_name,
                    'passport_number' => $customer->passport_number,
                ])
                ->values()
                ->all(),
            'payments' => $booking->payments
                ->sortByDesc('payment_date')
                ->values()
                ->map(fn ($payment) => [
                    'id' => $payment->id,
                    'amount' => (float) $payment->amount,
                    'payment_method' => $payment->payment_method,
                    'payment_date' => $payment->payment_date?->format('Y-m-d'),
                    'receipt_path' => $payment->receipt_path,
                    'receipt_url' => $payment->receipt_path ? '/storage/' . $payment->receipt_path : null,
                ])
                ->all(),
        ];
    }

    private function ensureTenantProfileReadyForDocuments(Tenant $tenant): void
    {
        $required = [
            'company_name' => 'Company name',
            'company_address' => 'Company address',
            'company_phone' => 'Company phone',
            'company_email' => 'Company email',
            'logo_path' => 'Company logo',
        ];

        $missing = collect($required)
            ->filter(fn (string $label, string $field) => blank($tenant->{$field}))
            ->values()
            ->all();

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'company_profile' => 'Complete company profile before generating documents: '.implode(', ', $missing).'.',
            ]);
        }
    }

    private function buildTourismLetterPdf(Booking $booking): \Barryvdh\DomPDF\PDF
    {
        $logoDataUri = null;
        $logoPath = $booking->tenant?->logo_path;
        if ($logoPath && Storage::disk('public')->exists($logoPath)) {
            $absolutePath = Storage::disk('public')->path($logoPath);
            $mimeType = mime_content_type($absolutePath) ?: 'image/png';
            $logoDataUri = 'data:'.$mimeType.';base64,'.base64_encode((string) file_get_contents($absolutePath));
        }

        return Pdf::loadView('pdfs.tourism-letter', [
            'booking' => $booking,
            'logoDataUri' => $logoDataUri,
        ]);
    }

    private function ensureBookingTravelReady(Booking $booking): void
    {
        $departureDate = $booking->departure_date ?? $booking->package?->start_date;
        $returnDate = $booking->return_date ?? $booking->package?->end_date;
        $flightName = trim((string) $booking->flight_name);
        $flightNumber = trim((string) $booking->flight_number);

        $missing = [];
        if (! $departureDate) {
            $missing[] = 'Departure date';
        }

        if (! $returnDate) {
            $missing[] = 'Return date';
        }

        if ($flightName === '') {
            $missing[] = 'Flight / airline';
        }

        if ($flightNumber === '') {
            $missing[] = 'Flight number';
        }

        if ($booking->passengers->isEmpty() && ! $booking->leadCustomer) {
            $missing[] = 'Passenger details';
        }

        if ($missing !== []) {
            throw ValidationException::withMessages([
                'tourism_letter' => 'Complete travel information before generating tourism letter: '.implode(', ', $missing).'.',
            ]);
        }
    }

    private function hasTourismLetterMinimumData(Booking $booking): bool
    {
        $departureDate = $booking->departure_date ?? $booking->package?->start_date;
        $returnDate = $booking->return_date ?? $booking->package?->end_date;
        $flightName = trim((string) $booking->flight_name);
        $flightNumber = trim((string) $booking->flight_number);

        return $departureDate
            && $returnDate
            && $flightName !== ''
            && $flightNumber !== ''
            && ($booking->passengers->isNotEmpty() || $booking->leadCustomer);
    }
}
