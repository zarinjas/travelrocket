<?php

namespace Tests\Feature;

use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PlanStrategyTest extends TestCase
{
    use RefreshDatabase;

    public function test_plans_are_seeded_as_free_trial_starter_and_growth(): void
    {
        $this->seed(PlanSeeder::class);

        $this->assertDatabaseCount('plans', 3);
        $this->assertDatabaseHas('plans', [
            'slug' => 'free-trial',
            'is_trial' => true,
            'trial_days' => 14,
        ]);
        $this->assertDatabaseHas('plans', [
            'slug' => 'starter',
            'is_trial' => false,
        ]);
        $this->assertDatabaseHas('plans', [
            'slug' => 'growth',
            'is_trial' => false,
        ]);
    }

    public function test_paid_checkout_intents_cannot_be_created_for_the_free_trial_plan(): void
    {
        $this->seed(PlanSeeder::class);

        $trial = Plan::query()->where('slug', 'free-trial')->firstOrFail();

        $response = $this->from('/checkout?plan=free-trial')->post('/checkout/intents', [
            'plan_slug' => $trial->slug,
            'agency_name' => 'Orbit Travel',
            'owner_name' => 'Aisyah',
            'email' => 'aisyah@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422);
    }
}
