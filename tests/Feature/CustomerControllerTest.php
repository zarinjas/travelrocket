<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerControllerTest extends TestCase
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

    public function test_authenticated_user_can_view_only_their_tenant_customers(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');

        Customer::query()->create([
            'tenant_id' => $tenantA->id,
            'full_name' => 'Nur Aisyah',
            'email' => 'nur@example.com',
        ]);

        Customer::query()->create([
            'tenant_id' => $tenantB->id,
            'full_name' => 'Farah Zahra',
            'email' => 'farah@example.com',
        ]);

        $this->actingAs($userA);

        $response = $this->get("/workspace/{$tenantA->slug}/customers");

        $response->assertOk();
        $response->assertSee('Nur Aisyah');
        $response->assertDontSee('Farah Zahra');
    }

    public function test_authenticated_user_can_create_a_customer_for_their_workspace(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $this->actingAs($user);

        $response = $this->post("/workspace/{$tenant->slug}/customers", [
            'full_name' => 'Nur Alina',
            'email' => 'alina@example.com',
            'phone' => '0123456789',
            'document_no' => 'A12345678',
            'nationality' => 'Malaysian',
            'notes' => 'Vegetarian meal preference.',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/customers");

        $this->assertDatabaseHas('customers', [
            'tenant_id' => $tenant->id,
            'full_name' => 'Nur Alina',
            'email' => 'alina@example.com',
            'document_no' => 'A12345678',
        ]);
    }

    public function test_authenticated_user_cannot_edit_another_tenants_customer(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');

        $foreignCustomer = Customer::query()->create([
            'tenant_id' => $tenantB->id,
            'full_name' => 'Siti Hajar',
            'email' => 'siti@example.com',
        ]);

        $this->actingAs($userA);

        $this->get("/workspace/{$tenantA->slug}/customers/{$foreignCustomer->id}/edit")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_delete_their_customer(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'full_name' => 'Nur Alina',
            'email' => 'alina@example.com',
        ]);

        $this->actingAs($user);

        $response = $this->delete("/workspace/{$tenant->slug}/customers/{$customer->id}");

        $response->assertRedirect("/workspace/{$tenant->slug}/customers");
        $this->assertDatabaseMissing('customers', ['id' => $customer->id]);
    }
}
