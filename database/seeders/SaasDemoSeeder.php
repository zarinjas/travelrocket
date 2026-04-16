<?php

namespace Database\Seeders;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\CustomerBlastLog;
use App\Models\CustomerBlastSetting;
use App\Models\CustomerBlastTemplate;
use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\Package;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\TenantProfileChangeLog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SaasDemoSeeder extends Seeder
{
    public function run(): void
    {
        DB::transaction(function (): void {
            $tenant = Tenant::query()->updateOrCreate(
                ['slug' => 'demo-travel'],
                [
                    'name' => 'Demo Travel Sdn Bhd',
                    'company_name' => 'Demo Travel Sdn Bhd',
                    'company_address' => 'D-25-05, Menara Demo, Jalan Lestari, 59200 Kuala Lumpur',
                    'company_phone' => '+60 17-322 8913',
                    'company_email' => 'hello@demotravel.com',
                    'company_website' => 'https://demotravel.com',
                    'social_links' => [
                        'facebook' => 'https://facebook.com/demotravel',
                        'instagram' => 'https://instagram.com/demotravel',
                        'tiktok' => 'https://tiktok.com/@demotravel',
                    ],
                ]
            );

            $owner = User::query()->updateOrCreate(
                ['email' => 'owner@demotravel.com'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Owner Demo',
                    'role' => 'owner',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            User::query()->updateOrCreate(
                ['email' => 'staff@demotravel.com'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Staff Demo',
                    'role' => 'staff',
                    'email_verified_at' => now(),
                    'password' => Hash::make('password'),
                ]
            );

            $packages = collect([
                ['category' => 'Umrah', 'type' => 'Umrah', 'name' => 'Umrah Plus Turkiye', 'destination' => 'Makkah, Madinah, Istanbul', 'price' => 12990],
                ['category' => 'Outbound Tours', 'type' => 'Outbound Tours', 'name' => 'Korea Cherry Blossom', 'destination' => 'Seoul', 'price' => 6390],
                ['category' => 'Outbound Tours', 'type' => 'Outbound Tours', 'name' => 'Japan Golden Route', 'destination' => 'Tokyo, Osaka', 'price' => 8990],
                ['category' => 'Inbound Tours', 'type' => 'Inbound Tours', 'name' => 'Borneo Nature Escape', 'destination' => 'Kota Kinabalu', 'price' => 2890],
                ['category' => 'Domestic Tours', 'type' => 'Domestic Tours', 'name' => 'Langkawi Family Break', 'destination' => 'Langkawi', 'price' => 1590],
                ['category' => 'Domestic Tours', 'type' => 'Domestic Tours', 'name' => 'Cameron Highlands Trip', 'destination' => 'Cameron Highlands', 'price' => 1190],
            ])->map(function (array $item) use ($tenant): Package {
                $startDate = now()->addDays(random_int(7, 90));
                $endDate = (clone $startDate)->addDays(random_int(4, 10));
                $capacity = random_int(20, 45);

                return Package::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'name' => $item['name'],
                    ],
                    [
                        'category' => $item['category'],
                        'type' => $item['type'],
                        'destination' => $item['destination'],
                        'start_date' => $startDate->toDateString(),
                        'end_date' => $endDate->toDateString(),
                        'booking_capacity' => $capacity,
                        'current_bookings' => 0,
                        'price' => $item['price'],
                        'status' => Package::STATUS_PUBLISHED,
                        'description' => 'Demo package for SaaS preview.',
                        'itinerary' => 'Day 1 arrival, Day 2 city tour, Day 3 free and easy, last day return.',
                    ]
                );
            });

            $firstNames = ['Ahmad', 'Siti', 'Nurul', 'Hafiz', 'Aina', 'Farhan', 'Aisyah', 'Hakim', 'Syafiq', 'Dina', 'Izzat', 'Sofea'];
            $lastNames = ['Rahman', 'Yusof', 'Hassan', 'Karim', 'Azman', 'Latif', 'Sulaiman', 'Ismail', 'Hamzah', 'Musa'];

            $customers = collect(range(1, 42))->map(function (int $index) use ($tenant, $firstNames, $lastNames): Customer {
                $fullName = $firstNames[array_rand($firstNames)].' '.$lastNames[array_rand($lastNames)].' '.$index;
                $email = 'customer'.$index.'@demo-mail.com';

                return Customer::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'email' => $email,
                    ],
                    [
                        'name' => $fullName,
                        'full_name' => $fullName,
                        'passport_number' => strtoupper(Str::random(1)).str_pad((string) random_int(1000000, 9999999), 7, '0', STR_PAD_LEFT),
                        'phone' => '01'.random_int(10000000, 99999999),
                        'address' => 'Kuala Lumpur',
                        'allow_marketing' => (bool) random_int(0, 1),
                        'nationality' => 'Malaysian',
                        'notes' => 'Auto-generated demo customer.',
                    ]
                );
            });

            $statuses = [
                Booking::BOOKING_STATUS_PENDING,
                Booking::BOOKING_STATUS_CONFIRMED,
                Booking::BOOKING_STATUS_CANCELLED,
            ];

            $bookings = collect(range(1, 28))->map(function (int $i) use ($tenant, $packages, $customers, $statuses): Booking {
                $package = $packages->random();
                $lead = $customers->random();
                $pax = random_int(1, 5);
                $price = (float) $package->price * $pax;
                $paidRatio = [0, 0.3, 0.5, 0.8, 1][array_rand([0, 0.3, 0.5, 0.8, 1])];
                $amountPaid = round($price * $paidRatio, 2);
                $balance = max(0, $price - $amountPaid);
                $bookingStatus = $statuses[array_rand($statuses)];

                $paymentStatus = Booking::PAYMENT_STATUS_UNPAID;
                if ($amountPaid > 0 && $balance > 0) {
                    $paymentStatus = Booking::PAYMENT_STATUS_PARTIAL;
                }
                if ($balance <= 0 && $amountPaid > 0) {
                    $paymentStatus = Booking::PAYMENT_STATUS_PAID;
                }

                $departureDate = (clone $package->start_date)?->copy()?->addDays(random_int(-2, 2));
                $returnDate = (clone $package->end_date)?->copy()?->addDays(random_int(-2, 2));

                $booking = Booking::query()->updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'booking_number' => sprintf('BK-DEMO-%04d', $i),
                    ],
                    [
                        'package_id' => $package->id,
                        'lead_customer_id' => $lead->id,
                        'customer_id' => $lead->id,
                        'travel_date' => $package->start_date,
                        'total_pax' => $pax,
                        'total_price' => $price,
                        'balance_due' => $balance,
                        'booking_status' => $bookingStatus,
                        'payment_status' => $paymentStatus,
                        'status' => $bookingStatus,
                        'departure_date' => $departureDate?->toDateString(),
                        'return_date' => $returnDate?->toDateString(),
                        'flight_name' => ['Malaysia Airlines', 'AirAsia', 'Batik Air', 'Qatar Airways'][array_rand(['a', 'b', 'c', 'd'])],
                        'flight_number' => strtoupper(Str::random(2)).random_int(100, 999),
                        'notes' => 'Demo booking record for UI preview.',
                    ]
                );

                $passengerIds = $customers->shuffle()->take($pax - 1)->pluck('id')->push($lead->id)->unique()->values();
                $booking->passengers()->sync($passengerIds->all());

                Payment::query()->where('booking_id', $booking->id)->delete();
                if ($amountPaid > 0) {
                    $firstPayment = round($amountPaid * 0.5, 2);
                    $secondPayment = $amountPaid - $firstPayment;

                    Payment::query()->create([
                        'tenant_id' => $tenant->id,
                        'booking_id' => $booking->id,
                        'amount' => $firstPayment,
                        'payment_method' => 'transfer',
                        'payment_date' => now()->subDays(random_int(7, 60))->toDateString(),
                    ]);

                    if ($secondPayment > 0) {
                        Payment::query()->create([
                            'tenant_id' => $tenant->id,
                            'booking_id' => $booking->id,
                            'amount' => $secondPayment,
                            'payment_method' => 'gateway',
                            'payment_date' => now()->subDays(random_int(1, 20))->toDateString(),
                        ]);
                    }
                }

                return $booking;
            });

            $bookings->where('booking_status', '!=', Booking::BOOKING_STATUS_CANCELLED)
                ->groupBy('package_id')
                ->each(function ($group, $packageId): void {
                    Package::query()->whereKey($packageId)->update([
                        'current_bookings' => (int) collect($group)->sum('total_pax'),
                    ]);
                });

            $allInvoices = collect();

            $bookings->take(18)->each(function (Booking $booking, int $index) use ($tenant, $owner, &$allInvoices): void {
                $validUntil = now()->addDays(random_int(3, 20));
                if ($index % 5 === 0) {
                    $validUntil = now()->subDays(random_int(1, 5));
                }

                $quotation = Quotation::query()->updateOrCreate(
                    ['quotation_number' => sprintf('QT-DEMO-%04d', $index + 1)],
                    [
                        'tenant_id' => $tenant->id,
                        'package_id' => $booking->package_id,
                        'lead_customer_id' => $booking->lead_customer_id,
                        'subtotal' => $booking->total_price,
                        'discount' => round((float) $booking->total_price * 0.05, 2),
                        'total_amount' => round((float) $booking->total_price * 0.95, 2),
                        'remarks' => 'Demo quotation for dashboard and archive view.',
                        'status' => $validUntil->isPast() ? Quotation::STATUS_EXPIRED : Quotation::STATUS_PENDING,
                        'valid_until' => $validUntil->toDateString(),
                    ]
                );

                $invoiceTotal = (float) $quotation->total_amount;
                $invoicePaid = round($invoiceTotal * [0, 0.4, 0.7, 1][array_rand([0, 0.4, 0.7, 1])], 2);
                $invoiceStatus = Invoice::STATUS_UNPAID;
                if ($invoicePaid > 0 && $invoicePaid < $invoiceTotal) {
                    $invoiceStatus = Invoice::STATUS_PARTIAL;
                }
                if ($invoicePaid >= $invoiceTotal && $invoiceTotal > 0) {
                    $invoiceStatus = Invoice::STATUS_PAID;
                }

                $invoice = Invoice::query()->updateOrCreate(
                    ['invoice_number' => sprintf('INV-DEMO-%04d', $index + 1)],
                    [
                        'tenant_id' => $tenant->id,
                        'quotation_id' => $quotation->id,
                        'booking_id' => $booking->id,
                        'lead_customer_id' => $booking->lead_customer_id,
                        'subtotal' => $quotation->subtotal,
                        'discount' => $quotation->discount,
                        'total_amount' => $invoiceTotal,
                        'amount_paid' => $invoicePaid,
                        'status' => $invoiceStatus,
                        'remarks' => 'Demo invoice for finance module.',
                        'issued_date' => now()->subDays(random_int(1, 45))->toDateString(),
                        'due_date' => now()->addDays(random_int(-15, 20))->toDateString(),
                    ]
                );

                InvoiceReminderLog::query()->where('invoice_id', $invoice->id)->delete();

                if ($invoice->status !== Invoice::STATUS_PAID) {
                    InvoiceReminderLog::query()->create([
                        'tenant_id' => $tenant->id,
                        'invoice_id' => $invoice->id,
                        'channel' => 'email',
                        'stage' => 'due_soon',
                        'recipient' => $booking->leadCustomer?->email,
                        'status' => 'sent',
                        'message_preview' => 'Demo reminder: invoice due soon.',
                        'sent_by_user_id' => $owner->id,
                        'sent_at' => now()->subDays(random_int(1, 6)),
                    ]);
                }

                $allInvoices->push($invoice);
            });

            CustomerBlastTemplate::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Promo Umrah'],
                [
                    'body' => 'Assalamualaikum {{name}}, promosi Umrah terkini kini dibuka. Balas mesej ini untuk lock seat.',
                ]
            );

            CustomerBlastTemplate::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'name' => 'Follow Up Leads'],
                [
                    'body' => 'Hi {{name}}, kami nampak anda berminat dengan pakej {{package}}. Nak kami share detail penuh?',
                ]
            );

            CustomerBlastSetting::query()->updateOrCreate(
                ['tenant_id' => $tenant->id],
                ['draft_message' => 'Salam {{name}}, kami ada update pakej terkini untuk anda.'],
            );

            CustomerBlastLog::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'channel' => 'email',
                    'selection_mode' => 'filtered',
                ],
                [
                    'recipient_count' => 28,
                    'whatsapp_ready_count' => 21,
                    'email_ready_count' => 26,
                    'message' => 'Demo blast for campaign analytics.',
                    'meta' => [
                        'tag' => 'umrah',
                        'exported_at' => now()->toDateTimeString(),
                    ],
                ]
            );

            TenantProfileChangeLog::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'summary' => 'Initial demo branding setup',
                ],
                [
                    'user_id' => $owner->id,
                    'changes' => [
                        'company_name' => ['old' => null, 'new' => $tenant->company_name],
                        'company_email' => ['old' => null, 'new' => $tenant->company_email],
                    ],
                ]
            );
        });
    }
}
