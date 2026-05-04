<?php

namespace Database\Seeders;

use App\Models\Customer;
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

        $quotations = [
            [
                'public_id' => 'EST-000901',
                'subject' => 'Ramadan Umrah Special',
                'items' => [
                    ['description' => "Umrah Plus Istanbul\n14D12N package", 'qty' => 2, 'rate' => 6490, 'amount' => 12980],
                    ['description' => "Visa & handling", 'qty' => 2, 'rate' => 150, 'amount' => 300],
                ],
                'sub_total' => 13280,
                'total' => 12490,
                'status' => Quotation::STATUS_DRAFT,
                'expiry_date' => now()->addDays(10)->toDateString(),
                'notes' => 'Promo Ramadan special rate.',
            ],
            [
                'public_id' => 'EST-000902',
                'subject' => 'Expired Sample Quote',
                'items' => [
                    ['description' => "Langkawi Family Break\n3D2N package", 'qty' => 4, 'rate' => 399, 'amount' => 1596],
                ],
                'sub_total' => 1596,
                'total' => 1596,
                'status' => Quotation::STATUS_EXPIRED,
                'expiry_date' => now()->subDays(2)->toDateString(),
                'notes' => 'This quotation is already expired.',
            ],
            [
                'public_id' => 'EST-000903',
                'subject' => 'Converted Sample Quote',
                'items' => [
                    ['description' => "Japan Golden Route\n8D6N package", 'qty' => 2, 'rate' => 4495, 'amount' => 8990],
                ],
                'sub_total' => 8990,
                'total' => 8490,
                'status' => Quotation::STATUS_CONVERTED,
                'expiry_date' => now()->subDays(7)->toDateString(),
                'notes' => 'Converted sample quotation.',
            ],
        ];

        foreach ($quotations as $data) {
            Quotation::query()->updateOrCreate(
                ['public_id' => $data['public_id']],
                [
                    'tenant_id' => $tenant->id,
                    'customer_id' => $leadCustomer->id,
                    'subject' => $data['subject'],
                    'items' => $data['items'],
                    'sub_total' => $data['sub_total'],
                    'total' => $data['total'],
                    'status' => $data['status'],
                    'expiry_date' => $data['expiry_date'],
                    'notes' => $data['notes'],
                    'terms' => 'Payment due within 7 days of issue date.',
                ]
            );
        }
    }
}
