<?php

namespace Database\Seeders;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class QuotationDemoSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'demo-travel-agency'],
            ['name' => 'Demo Travel Agency']
        );

        $package = Package::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Umrah Plus Istanbul',
            ],
            [
                'category' => 'Umrah',
                'type' => 'Umrah',
                'destination' => 'Makkah, Madinah, Istanbul',
                'price' => 12990,
                'status' => Package::STATUS_PUBLISHED,
                'description' => '14D12N package with guided itinerary.',
            ]
        );

        $leadCustomer = Customer::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'email' => 'ahmad.demo@example.com',
            ],
            [
                'name' => 'Ahmad Farid',
                'full_name' => 'Ahmad Farid',
                'passport_number' => 'A12345678',
                'phone' => '0123456789',
                'address' => 'Kuala Lumpur',
                'allow_marketing' => true,
            ]
        );

        Quotation::query()->updateOrCreate(
            ['quotation_number' => 'QT-20260404-901'],
            [
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'lead_customer_id' => $leadCustomer->id,
                'subtotal' => 12990,
                'discount' => 500,
                'total_amount' => 12490,
                'remarks' => 'Promo Ramadan special rate.',
                'status' => Quotation::STATUS_PENDING,
                'valid_until' => now()->addDays(10)->toDateString(),
            ]
        );

        Quotation::query()->updateOrCreate(
            ['quotation_number' => 'QT-20260404-902'],
            [
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'lead_customer_id' => $leadCustomer->id,
                'subtotal' => 12990,
                'discount' => 0,
                'total_amount' => 12990,
                'remarks' => 'Will auto-expire via scheduler due to past valid date.',
                'status' => Quotation::STATUS_PENDING,
                'valid_until' => now()->subDays(2)->toDateString(),
            ]
        );

        Quotation::query()->updateOrCreate(
            ['quotation_number' => 'QT-20260404-903'],
            [
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'lead_customer_id' => $leadCustomer->id,
                'subtotal' => 12990,
                'discount' => 1000,
                'total_amount' => 11990,
                'remarks' => 'Converted sample quotation.',
                'status' => 'converted',
                'valid_until' => now()->subDays(7)->toDateString(),
            ]
        );
    }
}
