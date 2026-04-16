<?php

namespace Tests\Feature;

use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PackageControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function createTenantUserPair(string $tenantSlug = 'orbit-travel', string $email = 'aisyah@example.com'): array
    {
        $tenant = Tenant::query()->create([
            'name' => 'Orbit Travel',
            'slug' => $tenantSlug,
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aisyah',
            'email' => $email,
            'password' => 'password',
            'role' => 'owner',
        ]);

        return [$tenant, $user];
    }

    public function test_authenticated_user_can_view_only_their_tenant_packages(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');

        Package::query()->create([
            'tenant_id' => $tenantA->id,
            'category' => 'Umrah',
            'name' => 'Umrah Premium',
            'type' => 'Umrah',
            'destination' => 'Makkah',
            'booking_capacity' => 20,
            'current_bookings' => 0,
            'price' => 5990,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
        ]);

        Package::query()->create([
            'tenant_id' => $tenantB->id,
            'category' => 'Outbound Tours',
            'name' => 'Langkawi Escape',
            'type' => 'Tour',
            'destination' => 'Langkawi',
            'booking_capacity' => 20,
            'current_bookings' => 0,
            'price' => 1290,
            'status' => 'draft',
            'itinerary' => 'Day 1 - Arrival',
        ]);

        $this->actingAs($userA);

        $response = $this->get("/workspace/{$tenantA->slug}/packages");

        $response->assertOk();
        $response->assertSee('Umrah Premium');
        $response->assertDontSee('Langkawi Escape');
    }

    public function test_authenticated_user_can_create_a_package_for_their_workspace(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $this->actingAs($user);

        $response = $this->post("/workspace/{$tenant->slug}/packages", [
            'category' => 'Outbound Tours',
            'name' => 'Sabah Adventure',
            'destination' => 'Kota Kinabalu',
            'start_date' => '2026-07-01',
            'end_date' => '2026-07-07',
            'itinerary' => 'Day 1 - Arrival',
            'booking_capacity' => 24,
            'price' => 1890,
            'status' => 'published',
            'description' => 'A guided East Malaysia travel package.',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/packages");

        $this->assertDatabaseHas('packages', [
            'tenant_id' => $tenant->id,
            'category' => 'Outbound Tours',
            'name' => 'Sabah Adventure',
            'destination' => 'Kota Kinabalu',
            'booking_capacity' => 24,
            'current_bookings' => 0,
            'price' => 1890,
            'status' => 'published',
        ]);
    }

    public function test_authenticated_user_cannot_override_current_bookings_on_create_or_update(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $this->actingAs($user);

        $response = $this->post("/workspace/{$tenant->slug}/packages", [
            'category' => 'Umrah',
            'name' => 'Managed Inventory Package',
            'destination' => 'Makkah',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-10',
            'itinerary' => 'Day 1 - Arrival',
            'booking_capacity' => 30,
            'current_bookings' => 28,
            'price' => 4990,
            'status' => 'published',
            'description' => 'Inventory should be server-managed.',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/packages");

        $package = Package::query()->where('tenant_id', $tenant->id)->firstOrFail();
        $this->assertSame(0, (int) $package->current_bookings);

        $this->put("/workspace/{$tenant->slug}/packages/{$package->id}", [
            'category' => 'Umrah',
            'name' => 'Managed Inventory Package Updated',
            'destination' => 'Makkah',
            'start_date' => '2026-08-01',
            'end_date' => '2026-08-10',
            'itinerary' => 'Day 1 - Arrival',
            'booking_capacity' => 30,
            'current_bookings' => 27,
            'price' => 4990,
            'status' => 'published',
            'description' => 'Still server-managed.',
        ])->assertRedirect("/workspace/{$tenant->slug}/packages");

        $package->refresh();
        $this->assertSame(0, (int) $package->current_bookings);
    }

    public function test_authenticated_user_cannot_edit_another_tenants_package(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');

        $foreignPackage = Package::query()->create([
            'tenant_id' => $tenantB->id,
            'category' => 'Outbound Tours',
            'name' => 'Bali Retreat',
            'type' => 'Tour',
            'destination' => 'Bali',
            'booking_capacity' => 20,
            'current_bookings' => 0,
            'price' => 2090,
            'status' => 'draft',
            'itinerary' => 'Day 1 - Arrival',
        ]);

        $this->actingAs($userA);

        $this->get("/workspace/{$tenantA->slug}/packages/{$foreignPackage->id}/edit")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_delete_their_package(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'category' => 'Outbound Tours',
            'name' => 'Sabah Adventure',
            'type' => 'Tour',
            'destination' => 'Kota Kinabalu',
            'booking_capacity' => 24,
            'current_bookings' => 0,
            'price' => 1890,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
        ]);

        $this->actingAs($user);

        $response = $this->delete("/workspace/{$tenant->slug}/packages/{$package->id}");

        $response->assertRedirect("/workspace/{$tenant->slug}/packages");
        $this->assertDatabaseMissing('packages', ['id' => $package->id]);
    }

    public function test_authenticated_user_can_view_package_details(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'category' => 'Outbound Tours',
            'name' => 'Bali Premium',
            'type' => 'Tour',
            'destination' => 'Bali',
            'booking_capacity' => 30,
            'current_bookings' => 4,
            'price' => 2890,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
        ]);

        $this->actingAs($user);

        $response = $this->get("/workspace/{$tenant->slug}/packages/{$package->id}");

        $response->assertOk();
    }
}
