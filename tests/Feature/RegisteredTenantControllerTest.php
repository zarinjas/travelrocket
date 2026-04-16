<?php

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegisteredTenantControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_registers_a_tenant_owner_and_logs_them_in(): void
    {
        $response = $this->post('/register', [
            'agency_name' => 'Orbit Travel',
            'name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $tenant = Tenant::first();
        $user = User::first();

        $this->assertNotNull($tenant);
        $response->assertRedirect("/workspace/{$tenant->slug}/dashboard");
        $this->assertSame('Orbit Travel', $tenant->name);
        $this->assertSame('orbit-travel', $tenant->slug);

        $this->assertNotNull($user);
        $this->assertSame($tenant->id, $user->tenant_id);
        $this->assertSame('owner', $user->role);
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_can_only_access_their_own_workspace(): void
    {
        $tenantA = Tenant::create([
            'name' => 'Orbit Travel',
            'slug' => 'orbit-travel',
        ]);

        $tenantB = Tenant::create([
            'name' => 'Nova Travel',
            'slug' => 'nova-travel',
        ]);

        $user = User::create([
            'tenant_id' => $tenantA->id,
            'name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $this->actingAs($user);

        $this->get("/workspace/{$tenantA->slug}/dashboard")->assertOk();
        $this->get("/workspace/{$tenantB->slug}/dashboard")->assertForbidden();
    }
}
