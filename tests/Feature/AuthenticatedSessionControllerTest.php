<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthenticatedSessionControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_page_is_accessible(): void
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }

    public function test_user_can_login_and_is_redirected_to_their_workspace(): void
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

        $response = $this->post('/login', [
            'email' => 'aisyah@example.com',
            'password' => 'password',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/dashboard");
        $this->assertAuthenticatedAs($user);
    }

    public function test_authenticated_user_can_logout(): void
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

        $this->actingAs($user);

        $response = $this->post('/logout');

        $response->assertRedirect('/login');
        $this->assertGuest();
    }

    public function test_quick_login_creates_a_demo_workspace_with_sample_data(): void
    {
        $response = $this->post('/quick-login');

        $user = User::query()->where('email', 'demo@travelrocket.test')->first();

        $this->assertNotNull($user);
        $this->assertDatabaseHas('tenants', ['id' => $user->tenant_id, 'slug' => 'demo-travel']);
        $this->assertDatabaseCount('packages', 2);
        $this->assertDatabaseCount('customers', 2);
        $this->assertDatabaseCount('bookings', 3);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect("/workspace/demo-travel/dashboard");
    }
}
