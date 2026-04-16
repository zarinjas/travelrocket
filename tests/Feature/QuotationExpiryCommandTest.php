<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QuotationExpiryCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_quotations_expire_command_updates_only_pending_past_due_quotations(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Agency',
            'slug' => 'test-agency',
        ]);

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Umrah',
            'type' => 'Umrah',
            'price' => 5000,
            'status' => 'published',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'full_name' => 'John Doe',
        ]);

        // Pending quotation with future valid_until (should NOT be expired)
        Quotation::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'quotation_number' => 'QT-001',
            'subtotal' => 5000,
            'total_amount' => 5000,
            'status' => Quotation::STATUS_PENDING,
            'valid_until' => now()->addDays(5)->toDateString(),
        ]);

        // Pending quotation with past valid_until (should be expired)
        Quotation::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'quotation_number' => 'QT-002',
            'subtotal' => 5000,
            'total_amount' => 5000,
            'status' => Quotation::STATUS_PENDING,
            'valid_until' => now()->subDays(3)->toDateString(),
        ]);

        // Already expired quotation (should remain expired, not changed)
        Quotation::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'quotation_number' => 'QT-003',
            'subtotal' => 5000,
            'total_amount' => 5000,
            'status' => Quotation::STATUS_EXPIRED,
            'valid_until' => now()->subDays(10)->toDateString(),
        ]);

        // Converted quotation with past valid_until (should NOT be expired / status changed)
        Quotation::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'quotation_number' => 'QT-004',
            'subtotal' => 5000,
            'total_amount' => 5000,
            'status' => Quotation::STATUS_CONVERTED,
            'valid_until' => now()->subDays(5)->toDateString(),
        ]);

        // Execute the command
        $this->artisan('quotations:expire')
            ->assertExitCode(0);

        // Verify only the pending past-due quotation was marked as expired
        $qt1 = Quotation::query()->where('quotation_number', 'QT-001')->first();
        $qt2 = Quotation::query()->where('quotation_number', 'QT-002')->first();
        $qt3 = Quotation::query()->where('quotation_number', 'QT-003')->first();
        $qt4 = Quotation::query()->where('quotation_number', 'QT-004')->first();

        $this->assertEquals(Quotation::STATUS_PENDING, $qt1->status, 'Future pending quotation should remain pending');
        $this->assertEquals(Quotation::STATUS_EXPIRED, $qt2->status, 'Past-due pending quotation should be expired');
        $this->assertEquals(Quotation::STATUS_EXPIRED, $qt3->status, 'Already expired quotation should remain expired');
        $this->assertEquals(Quotation::STATUS_CONVERTED, $qt4->status, 'Converted quotation should not be changed');
    }

    public function test_quotations_expire_command_output_reports_count(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Test Agency',
            'slug' => 'test-agency',
        ]);

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Umrah',
            'type' => 'Umrah',
            'price' => 5000,
            'status' => 'published',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'John Doe',
            'full_name' => 'John Doe',
        ]);

        // Create 3 pending past-due quotations
        for ($i = 0; $i < 3; $i++) {
            Quotation::query()->create([
                'tenant_id' => $tenant->id,
                'package_id' => $package->id,
                'lead_customer_id' => $customer->id,
                'quotation_number' => "QT-{$i}",
                'subtotal' => 5000,
                'total_amount' => 5000,
                'status' => Quotation::STATUS_PENDING,
                'valid_until' => now()->subDays(2)->toDateString(),
            ]);
        }

        $this->artisan('quotations:expire')
            ->expectsOutput('Marked 3 quotation(s) as expired.')
            ->assertExitCode(0);
    }
}
