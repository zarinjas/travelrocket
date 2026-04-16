<?php

namespace Tests\Feature;

use App\Models\PendingRegistration;
use App\Models\Plan;
use App\Models\User;
use App\Services\PaymentOrchestrationService;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class PaymentOrchestrationTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    protected string $seeder = PlanSeeder::class;

    public function test_mock_payment_can_provision_a_workspace_and_log_the_user_in(): void
    {
        $this->seed(PlanSeeder::class);

        $plan = Plan::query()->where('slug', 'growth')->firstOrFail();

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

        $response = $this->post("/checkout/intents/{$intent->public_id}/simulate-paid");

        $intent->refresh();
        $user = User::query()->where('email', 'aisyah@example.com')->first();

        $this->assertNotNull($user);
        $this->assertSame(PendingRegistration::STATUS_PROVISIONED, $intent->status);
        $this->assertAuthenticatedAs($user);
        $response->assertRedirect("/checkout/status?intent={$intent->public_id}");
    }

    public function test_webhook_can_mark_payment_as_paid_and_provision(): void
    {
        $this->seed(PlanSeeder::class);

        $plan = Plan::query()->where('slug', 'starter')->firstOrFail();

        $intent = PendingRegistration::query()->create([
            'plan_id' => $plan->id,
            'agency_name' => 'Nova Travel',
            'owner_name' => 'Farah',
            'email' => 'farah@example.com',
            'status' => PendingRegistration::STATUS_PENDING_PAYMENT,
            'meta' => [
                'owner_password_hash' => Hash::make('password'),
            ],
            'expires_at' => now()->addDay(),
        ]);

        $response = $this->postJson('/webhooks/payments/mock-gateway', [
            'event' => 'payment.succeeded',
            'intent_id' => $intent->public_id,
            'provider' => 'mock_gateway',
            'reference' => 'pay_123',
        ]);

        $intent->refresh();

        $response->assertOk()
            ->assertJson([
                'status' => 'provisioned',
                'intent_id' => $intent->public_id,
            ]);

        $this->assertSame(PendingRegistration::STATUS_PROVISIONED, $intent->status);
        $this->assertDatabaseHas('users', ['email' => 'farah@example.com']);
    }
}
