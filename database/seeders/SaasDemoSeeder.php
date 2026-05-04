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
use App\Models\PackageImage;
use App\Models\Payment;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
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
                [
                    'category' => 'Umrah',
                    'type' => 'Umrah',
                    'name' => 'Umrah Plus Turkiye',
                    'destination' => 'Makkah, Madinah, Istanbul',
                    'price' => 12990,
                    'cover_theme' => '#0f4c81',
                    'gallery_theme' => ['#0f4c81', '#1e6fb8', '#d97706'],
                    'hotel' => ['Makkah' => 'Swissotel Makkah', 'Madinah' => 'Anwar Al Madinah', 'Istanbul' => 'Grand Cevahir'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'Malaysia Airlines', 'flight_class' => 'economy', 'is_direct' => false],
                    'visa' => ['included' => 'yes', 'type' => 'Umrah visa', 'processing_days' => 14],
                ],
                [
                    'category' => 'Outbound Tours',
                    'type' => 'Outbound Tours',
                    'name' => 'Korea Cherry Blossom',
                    'destination' => 'Seoul',
                    'price' => 6390,
                    'cover_theme' => '#7c3aed',
                    'gallery_theme' => ['#7c3aed', '#a855f7', '#db2777'],
                    'hotel' => ['Seoul' => 'Lotte City Hotel Myeongdong'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'AirAsia X', 'flight_class' => 'economy', 'is_direct' => true],
                    'visa' => ['included' => 'no', 'type' => 'K-ETA', 'processing_days' => 3],
                ],
                [
                    'category' => 'Outbound Tours',
                    'type' => 'Outbound Tours',
                    'name' => 'Japan Golden Route',
                    'destination' => 'Tokyo, Osaka',
                    'price' => 8990,
                    'cover_theme' => '#b45309',
                    'gallery_theme' => ['#b45309', '#ea580c', '#0891b2'],
                    'hotel' => ['Tokyo' => 'Shinjuku Prince Hotel', 'Osaka' => 'Hotel Monterey Grasmere'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'Batik Air', 'flight_class' => 'economy', 'is_direct' => true],
                    'visa' => ['included' => 'no', 'type' => 'Japan eVisa', 'processing_days' => 7],
                ],
                [
                    'category' => 'Inbound Tours',
                    'type' => 'Inbound Tours',
                    'name' => 'Borneo Nature Escape',
                    'destination' => 'Kota Kinabalu',
                    'price' => 2890,
                    'cover_theme' => '#166534',
                    'gallery_theme' => ['#166534', '#0f766e', '#0284c7'],
                    'hotel' => ['Kota Kinabalu' => 'Horizon Hotel'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'Malaysia Airlines', 'flight_class' => 'economy', 'is_direct' => true],
                    'visa' => ['included' => 'n/a', 'type' => '', 'processing_days' => null],
                ],
                [
                    'category' => 'Domestic Tours',
                    'type' => 'Domestic Tours',
                    'name' => 'Langkawi Family Break',
                    'destination' => 'Langkawi',
                    'price' => 1590,
                    'cover_theme' => '#0ea5e9',
                    'gallery_theme' => ['#0ea5e9', '#14b8a6', '#f59e0b'],
                    'hotel' => ['Langkawi' => 'Aloft Langkawi Pantai Tengah'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'AirAsia', 'flight_class' => 'economy', 'is_direct' => true],
                    'visa' => ['included' => 'n/a', 'type' => '', 'processing_days' => null],
                ],
                [
                    'category' => 'Domestic Tours',
                    'type' => 'Domestic Tours',
                    'name' => 'Cameron Highlands Trip',
                    'destination' => 'Cameron Highlands',
                    'price' => 1190,
                    'cover_theme' => '#4b5563',
                    'gallery_theme' => ['#4b5563', '#6b7280', '#059669'],
                    'hotel' => ['Cameron Highlands' => 'Strawberry Park Resort'],
                    'flight' => ['departure_city' => 'Kuala Lumpur', 'airline' => 'Express Bus', 'flight_class' => 'standard', 'is_direct' => true],
                    'visa' => ['included' => 'n/a', 'type' => '', 'processing_days' => null],
                ],
            ])->map(function (array $item) use ($tenant): Package {
                $startDate = now()->addDays(random_int(7, 90));
                $endDate = (clone $startDate)->addDays(random_int(4, 10));
                $capacity = random_int(20, 45);

                $package = Package::query()->updateOrCreate(
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
                        'itinerary' => $this->buildPackageItineraryText($item['name'], $item['destination']),
                        'itinerary_days' => $this->buildPackageItineraryDays($item),
                        'inclusions' => $this->buildPackageInclusions($item),
                        'exclusions' => $this->buildPackageExclusions($item),
                        'pricing_tiers' => [
                            'adult' => (float) $item['price'],
                            'child' => round((float) $item['price'] * 0.82, 2),
                            'infant' => round((float) $item['price'] * 0.12, 2),
                        ],
                        'room_types' => [
                            ['name' => 'Twin Sharing', 'supplement' => 0],
                            ['name' => 'Single Room', 'supplement' => 450],
                            ['name' => 'Triple Sharing', 'supplement' => -180],
                        ],
                        'highlights' => [
                            'Airport transfers',
                            'Handpicked accommodation',
                            'Local guide support',
                        ],
                        'meal_plan' => 'Breakfast, lunch, and dinner as stated in itinerary.',
                        'hotel_details' => $item['hotel'],
                        'flight_info' => $item['flight'],
                        'visa_info' => $item['visa'],
                        'min_pax' => 1,
                        'max_pax' => 40,
                        'terms_conditions' => 'Deposit required within 7 days. Prices subject to change until confirmed.',
                    ]
                );

                $this->seedPackageAssets($tenant, $package, $item);

                return $package;
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

            $bookings->take(18)->each(function (Booking $booking, int $index) use ($tenant, $owner): void {
                $expiryDate = now()->addDays(random_int(3, 20));
                $status = Quotation::STATUS_DRAFT;
                if ($index % 5 === 0) {
                    $expiryDate = now()->subDays(random_int(1, 5));
                    $status = Quotation::STATUS_EXPIRED;
                } elseif ($index % 4 === 0) {
                    $status = Quotation::STATUS_SENT;
                } elseif ($index % 6 === 0) {
                    $status = Quotation::STATUS_CONVERTED;
                }

                $quotationItems = [
                    [
                        'description' => $booking->package?->name . "\n" . ($booking->package?->destination ?: 'Demo itinerary'),
                        'qty' => (int) max(1, $booking->total_pax),
                        'rate' => (float) $booking->package?->price,
                        'amount' => (float) $booking->total_price,
                    ],
                ];
                $quotationTotal = round((float) $booking->total_price * 0.95, 2);
                $quotation = Quotation::query()->updateOrCreate(
                    ['public_id' => sprintf('EST-%06d', $index + 1)],
                    [
                        'tenant_id' => $tenant->id,
                        'customer_id' => $booking->lead_customer_id,
                        'subject' => $booking->package?->name . ' quotation',
                        'items' => $quotationItems,
                        'sub_total' => (float) $booking->total_price,
                        'total' => $quotationTotal,
                        'status' => $status,
                        'expiry_date' => $expiryDate->toDateString(),
                        'notes' => 'Demo quotation for dashboard and archive view.',
                        'terms' => 'Valid for 7 days from issue date.',
                    ]
                );

                $invoiceTotal = (float) $quotation->total;
                $invoicePaid = round($invoiceTotal * [0, 0.4, 0.7, 1][array_rand([0, 0.4, 0.7, 1])], 2);
                $invoiceStatus = 'Unpaid';
                if ($invoicePaid > 0 && $invoicePaid < $invoiceTotal) {
                    $invoiceStatus = 'Partially Paid';
                }
                if ($invoicePaid >= $invoiceTotal && $invoiceTotal > 0) {
                    $invoiceStatus = 'Fully Paid';
                }
                $issuedDate = now()->subDays(random_int(1, 45));
                $dueDate = (clone $issuedDate)->addDays(random_int(7, 21));
                if ($invoiceStatus !== 'Fully Paid' && $dueDate->isPast()) {
                    $invoiceStatus = 'Overdue';
                }

                $invoice = Invoice::query()->updateOrCreate(
                    ['public_id' => sprintf('INV-%06d', $index + 1)],
                    [
                        'tenant_id' => $tenant->id,
                        'quote_id' => $quotation->id,
                        'booking_id' => $booking->id,
                        'lead_customer_id' => $booking->lead_customer_id,
                        'customer_id' => $booking->lead_customer_id,
                        'subject' => $booking->package?->name . ' invoice',
                        'items' => $quotationItems,
                        'sub_total' => (float) $quotation->sub_total,
                        'total' => $invoiceTotal,
                        'paid_amount' => $invoicePaid,
                        'status' => $invoiceStatus,
                        'notes' => 'Demo invoice for finance module.',
                        'terms' => 'Payment due within 14 days of issue date.',
                        'issued_date' => $issuedDate->toDateString(),
                        'due_date' => $dueDate->toDateString(),
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

        });
    }

    protected function seedPackageAssets(Tenant $tenant, Package $package, array $item): void
    {
        $slug = Str::slug($package->name);
        $basePath = "demo-assets/{$tenant->id}/packages/{$slug}";
        $coverPath = "{$basePath}/cover.svg";
        $brochurePath = "{$basePath}/brochure.pdf";

        if ($package->cover_image_path && $package->cover_image_path !== $coverPath) {
            Storage::disk('public')->delete($package->cover_image_path);
        }

        if ($package->brochure_path && $package->brochure_path !== $brochurePath) {
            Storage::disk('public')->delete($package->brochure_path);
        }

        Storage::disk('public')->put($coverPath, $this->buildDemoSvg(
            $package->name,
            $package->destination ?? $item['destination'],
            (string) ($item['cover_theme'] ?? '#1e3a8a')
        ));

        Storage::disk('public')->put($brochurePath, $this->buildDemoBrochurePdf(
            $package->name,
            $package->destination ?? $item['destination'],
            $item
        ));

        $package->forceFill([
            'cover_image_path' => $coverPath,
            'brochure_path' => $brochurePath,
        ])->save();

        $package->loadMissing('images');
        foreach ($package->images as $image) {
            Storage::disk('public')->delete($image->path);
            $image->delete();
        }

        $galleryThemes = $item['gallery_theme'] ?? ['#1e3a8a', '#2563eb', '#0f766e'];
        foreach (array_values($galleryThemes) as $index => $theme) {
            $galleryPath = "{$basePath}/gallery-".($index + 1).".svg";
            Storage::disk('public')->put($galleryPath, $this->buildDemoSvg(
                $package->name.' Gallery '.($index + 1),
                $package->destination ?? $item['destination'],
                (string) $theme
            ));

            $package->images()->create([
                'tenant_id' => $tenant->id,
                'path' => $galleryPath,
                'sort_order' => $index,
            ]);
        }
    }

    protected function buildPackageItineraryText(string $name, string $destination): string
    {
        return "{$name}\n".implode("\n", [
            "Day 1: Arrival and hotel check-in in {$destination}.",
            'Day 2: Guided sightseeing and shopping stop.',
            'Day 3: Free and easy with optional activities.',
            'Day 4: Departure and airport transfer.',
        ]);
    }

    protected function buildPackageItineraryDays(array $item): array
    {
        $destination = $item['destination'];

        return [
            [
                'day' => 1,
                'title' => 'Arrival',
                'description' => "Arrive at {$destination}, transfer to hotel, and evening briefing.",
                'activities' => ['Airport pickup', 'Hotel check-in', 'Welcome dinner'],
            ],
            [
                'day' => 2,
                'title' => 'City Highlights',
                'description' => 'Full day sightseeing with a local guide and photo stops.',
                'activities' => ['City tour', 'Local lunch', 'Shopping'],
            ],
            [
                'day' => 3,
                'title' => 'Free Time',
                'description' => 'Flexible day for personal exploration or optional add-ons.',
                'activities' => ['Optional excursion', 'Leisure time', 'Night market'],
            ],
            [
                'day' => 4,
                'title' => 'Return',
                'description' => 'Breakfast, check-out, and transfer back to the airport.',
                'activities' => ['Breakfast', 'Check-out', 'Airport transfer'],
            ],
        ];
    }

    protected function buildPackageInclusions(array $item): array
    {
        return [
            'Return air ticket',
            'Hotel accommodation',
            'Daily breakfast',
            'Airport transfers',
            'Tour guide',
            'Travel insurance',
        ];
    }

    protected function buildPackageExclusions(array $item): array
    {
        return [
            'Personal expenses',
            'Optional tours',
            'Tips for guide and driver',
        ];
    }

    protected function buildDemoSvg(string $title, string $subtitle, string $accent): string
    {
        $title = htmlspecialchars($title, ENT_QUOTES | ENT_XML1);
        $subtitle = htmlspecialchars($subtitle, ENT_QUOTES | ENT_XML1);
        $accent = htmlspecialchars($accent, ENT_QUOTES | ENT_XML1);

        return <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 800">
  <rect width="1200" height="800" fill="#0f172a"/>
  <rect x="0" y="0" width="1200" height="800" fill="{$accent}" opacity="0.88"/>
  <rect x="70" y="90" width="1060" height="620" rx="36" fill="#ffffff" opacity="0.12"/>
  <rect x="110" y="130" width="340" height="28" rx="14" fill="#ffffff" opacity="0.3"/>
  <text x="110" y="250" font-family="Arial, sans-serif" font-size="72" font-weight="700" fill="#ffffff">{$title}</text>
  <text x="110" y="320" font-family="Arial, sans-serif" font-size="30" fill="#ffffff" opacity="0.92">{$subtitle}</text>
  <text x="110" y="390" font-family="Arial, sans-serif" font-size="22" fill="#ffffff" opacity="0.85">Demo package cover image</text>
  <circle cx="935" cy="395" r="150" fill="#ffffff" opacity="0.14"/>
  <circle cx="935" cy="395" r="95" fill="#ffffff" opacity="0.26"/>
  <text x="860" y="410" font-family="Arial, sans-serif" font-size="42" font-weight="700" fill="#ffffff">TR</text>
</svg>
SVG;
    }

    protected function buildDemoBrochurePdf(string $title, string $destination, array $item): string
    {
        $lines = [
            "Destination: {$destination}",
            'Package overview:',
            'This is a demo brochure generated for UI preview.',
            'Inclusions:',
        ];

        foreach ($this->buildPackageInclusions($item) as $inclusion) {
            $lines[] = '- ' . $inclusion;
        }

        $lines[] = 'Itinerary:';
        foreach ($this->buildPackageItineraryDays($item) as $day) {
            $lines[] = sprintf('Day %d - %s: %s', $day['day'], $day['title'], $day['description']);
        }

        return $this->buildSimplePdf($title, $lines);
    }

    protected function buildSimplePdf(string $title, array $lines): string
    {
        $escape = fn (string $value): string => str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);

        $content = "BT\n/F1 22 Tf\n72 770 Td\n(".$escape($title).") Tj\n";
        $content .= "/F1 11 Tf\n0 -24 Td\n";
        foreach ($lines as $line) {
            $content .= '(' . $escape((string) $line) . ") Tj\n0 -16 Td\n";
        }
        $content .= "ET";

        $objects = [];
        $objects[] = "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";
        $objects[] = "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";
        $objects[] = "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";
        $objects[] = "4 0 obj\n<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>\nendobj\n";
        $objects[] = "5 0 obj\n<< /Length " . strlen($content) . " >>\nstream\n{$content}\nendstream\nendobj\n";

        $pdf = "%PDF-1.4\n";
        $offsets = [0];
        foreach ($objects as $object) {
            $offsets[] = strlen($pdf);
            $pdf .= $object;
        }

        $xrefStart = strlen($pdf);
        $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        foreach (array_slice($offsets, 1) as $offset) {
            $pdf .= sprintf('%010d 00000 n ', $offset) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n{$xrefStart}\n%%EOF";

        return $pdf;
    }
}
