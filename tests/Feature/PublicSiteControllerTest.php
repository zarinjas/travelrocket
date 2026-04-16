<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PublicSiteControllerTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PlanSeeder::class;

    public function test_public_marketing_pages_are_accessible(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/')->assertOk();
        $this->get('/features')->assertOk();
        $this->get('/pricing')->assertOk();
        $this->get('/register')->assertOk();
        $this->get('/checkout')->assertOk();
        $this->get('/checkout/status')->assertOk();
        $this->get('/dev/onboarding-monitor')->assertOk();
    }

    public function test_public_pages_accept_plan_selection_queries(): void
    {
        $this->seed(PlanSeeder::class);

        $this->get('/register?plan=growth')->assertOk();
        $this->get('/checkout?plan=starter')->assertOk();
    }

    public function test_status_page_can_render_a_real_pending_intent(): void
    {
        $this->seed(PlanSeeder::class);

        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        $intent = PendingRegistration::query()->create([
            'plan_id' => $plan->id,
            'agency_name' => 'Orbit Travel',
            'owner_name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'status' => PendingRegistration::STATUS_PENDING_PAYMENT,
            'meta' => [
                'owner_password_hash' => Hash::make('password'),
            ],
            'expires_at' => now()->addDay(),
        ]);

        $this->get("/checkout/status?intent={$intent->public_id}")->assertOk();
    }
}
