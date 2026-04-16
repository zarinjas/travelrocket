<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CheckoutIntentControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PlanSeeder::class;

    public function test_it_creates_a_pending_registration_for_the_selected_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $plan = Plan::query()->where('slug', 'growth')->firstOrFail();

        $response = $this->post('/checkout/intents', [
            'plan_slug' => $plan->slug,
            'agency_name' => 'Orbit Travel',
            'owner_name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $intent = PendingRegistration::query()->first();

        $this->assertNotNull($intent);
        $this->assertSame($plan->id, $intent->plan_id);
        $this->assertSame('pending_payment', $intent->status);
        $this->assertNotEmpty($intent->public_id);
        $this->assertArrayHasKey('owner_password_hash', $intent->meta);

        $response->assertRedirect("/checkout?plan={$plan->slug}&intent={$intent->public_id}");
    }
}
