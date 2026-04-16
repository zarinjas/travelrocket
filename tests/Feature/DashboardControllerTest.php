<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_real_tenant_insights(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Orbit Travel',
            'slug' => 'orbit-travel',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $paidPackage = Package::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Umrah Premium',
            'type' => 'Umrah',
            'price_amount' => 5990,
            'status' => 'published',
        ]);

        $pendingPackage = Package::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Langkawi Escape',
            'type' => 'Tour',
            'price_amount' => 1890,
            'status' => 'published',
        ]);

        $customerA = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'full_name' => 'Nur Aisyah',
        ]);

        $customerB = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'full_name' => 'Faris Hakim',
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $paidPackage->id,
            'customer_id' => $customerA->id,
            'status' => 'paid',
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $pendingPackage->id,
            'customer_id' => $customerB->id,
            'status' => 'pending',
        ]);

        Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $pendingPackage->id,
            'customer_id' => $customerA->id,
            'status' => 'cancelled',
        ]);

        $this->actingAs($user);

        $response = $this->get("/workspace/{$tenant->slug}/dashboard");

        $response->assertOk();
        $response->assertSee('3');
        $response->assertSee('2');
        $response->assertSee('RM5990');
        $response->assertSee('pending');
        $response->assertSee('paid');
        $response->assertSee('cancelled');
    }
}
