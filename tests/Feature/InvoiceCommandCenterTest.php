<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\Customer;
use App\Models\Invoice;
use App\Models\InvoiceReminderLog;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InvoiceCommandCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_invoice_index_includes_command_center_insights_payload(): void
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

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'category' => 'Umrah',
            'name' => 'Umrah Premium',
            'type' => 'Umrah',
            'destination' => 'Makkah',
            'booking_capacity' => 20,
            'current_bookings' => 1,
            'price' => 6990,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
            'start_date' => now()->addDays(7)->toDateString(),
            'end_date' => now()->addDays(16)->toDateString(),
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nur Aisyah',
            'full_name' => 'Nur Aisyah',
            'email' => 'nur@example.com',
            'phone' => '0123456789',
        ]);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-900',
            'total_pax' => 1,
            'total_price' => 6990,
            'balance_due' => 6990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'lead_customer_id' => $customer->id,
            'invoice_number' => 'INV-20260405-001',
            'subtotal' => 6990,
            'discount' => 0,
            'total_amount' => 6990,
            'amount_paid' => 1000,
            'status' => 'partial',
            'issued_date' => now()->subDays(2)->toDateString(),
            'due_date' => now()->addDays(2)->toDateString(),
        ]);

        InvoiceReminderLog::query()->create([
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'stage' => 'due_soon',
            'recipient' => $customer->email,
            'status' => 'sent',
            'message_preview' => 'Reminder sent',
            'sent_by_user_id' => $user->id,
            'sent_at' => now()->subDay(),
        ]);

        $this->actingAs($user);

        $response = $this->get("/workspace/{$tenant->slug}/invoices");

        $response->assertOk();
        $response->assertSee('cashflow_forecast');
        $response->assertSee('collection_actions');
        $response->assertSee('collector_snapshot');
        $response->assertSee('due_timeline');
    }

    public function test_collection_action_can_be_marked_done_and_exported(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'Orbit Travel',
            'slug' => 'orbit-travel',
        ]);

        $user = User::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Aisyah',
            'email' => 'owner2@example.com',
            'password' => 'password',
            'role' => 'owner',
        ]);

        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'category' => 'Umrah',
            'name' => 'Umrah Value',
            'type' => 'Umrah',
            'destination' => 'Madinah',
            'booking_capacity' => 20,
            'current_bookings' => 1,
            'price' => 5990,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
            'start_date' => now()->addDays(5)->toDateString(),
            'end_date' => now()->addDays(12)->toDateString(),
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Faris',
            'full_name' => 'Faris',
            'email' => 'faris@example.com',
            'phone' => '0198887766',
        ]);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-910',
            'total_pax' => 1,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $invoice = Invoice::query()->create([
            'tenant_id' => $tenant->id,
            'booking_id' => $booking->id,
            'lead_customer_id' => $customer->id,
            'invoice_number' => 'INV-20260405-090',
            'subtotal' => 5990,
            'discount' => 0,
            'total_amount' => 5990,
            'amount_paid' => 0,
            'status' => 'unpaid',
            'issued_date' => now()->subDays(3)->toDateString(),
            'due_date' => now()->addDay()->toDateString(),
        ]);

        $this->actingAs($user);

        $this->post("/workspace/{$tenant->slug}/invoices/{$invoice->id}/mark-collection-action", [
            'stage' => 'due_soon',
            'notes' => 'Called customer and confirmed transfer.',
        ])->assertRedirect();

        $this->assertDatabaseHas('invoice_reminder_logs', [
            'tenant_id' => $tenant->id,
            'invoice_id' => $invoice->id,
            'channel' => 'internal',
            'status' => 'completed',
        ]);

        $this->get("/workspace/{$tenant->slug}/invoices/collection-actions-export")
            ->assertOk()
            ->assertHeader('content-type', 'text/csv; charset=UTF-8');
    }
}
