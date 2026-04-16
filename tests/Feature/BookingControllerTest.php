<?php

namespace Tests\Feature;

use App\Mail\TourismLetterMail;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Quotation;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class BookingControllerTest extends TestCase
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

    protected function createPackageAndCustomer(
        Tenant $tenant,
        string $packageName = 'Umrah Premium',
        string $customerName = 'Nur Aisyah',
        string $customerEmail = 'nur@example.com'
    ): array
    {
        $package = Package::query()->create([
            'tenant_id' => $tenant->id,
            'category' => 'Umrah',
            'name' => $packageName,
            'type' => 'Umrah',
            'destination' => 'Makkah',
            'booking_capacity' => 10,
            'current_bookings' => 0,
            'price' => 5990,
            'status' => 'published',
            'itinerary' => 'Day 1 - Arrival',
            'start_date' => '2026-06-01',
            'end_date' => '2026-06-10',
        ]);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => $customerName,
            'full_name' => $customerName,
            'email' => $customerEmail,
        ]);

        return [$package, $customer];
    }

    public function test_authenticated_user_can_view_only_their_tenant_bookings(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');

        [$packageA, $customerA] = $this->createPackageAndCustomer($tenantA);
        [$packageB, $customerB] = $this->createPackageAndCustomer($tenantB, 'Bali Retreat', 'Farah Zahra', 'farah.customer@example.com');

        Booking::query()->create([
            'tenant_id' => $tenantA->id,
            'package_id' => $packageA->id,
            'lead_customer_id' => $customerA->id,
            'customer_id' => $customerA->id,
            'status' => 'pending',
        ]);

        Booking::query()->create([
            'tenant_id' => $tenantB->id,
            'package_id' => $packageB->id,
            'lead_customer_id' => $customerB->id,
            'customer_id' => $customerB->id,
            'status' => 'paid',
        ]);

        $this->actingAs($userA);

        $response = $this->get("/workspace/{$tenantA->slug}/bookings");

        $response->assertOk();
        $response->assertSee('Nur Aisyah');
        $response->assertDontSee('Farah Zahra');
    }

    public function test_authenticated_user_can_create_a_booking_for_their_workspace(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $this->actingAs($user);

        $response = $this->post("/workspace/{$tenant->slug}/bookings", [
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'passenger_ids' => [],
            'total_price' => 5990,
            'booking_status' => 'pending',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/bookings");

        $this->assertDatabaseHas('bookings', [
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'booking_status' => 'pending',
            'total_pax' => 1,
        ]);
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'current_bookings' => 1,
        ]);
    }

    public function test_booking_creation_is_blocked_when_package_has_no_remaining_seats(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $package->update([
            'booking_capacity' => 1,
            'current_bookings' => 1,
        ]);

        $secondCustomer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Haslina',
            'full_name' => 'Haslina',
            'email' => 'haslina@example.com',
        ]);

        $this->actingAs($user);

        $response = $this->from("/workspace/{$tenant->slug}/bookings/create")->post("/workspace/{$tenant->slug}/bookings", [
            'package_id' => $package->id,
            'lead_customer_id' => $secondCustomer->id,
            'passenger_ids' => [],
            'total_price' => 5990,
            'booking_status' => 'pending',
        ]);

        $response->assertRedirect("/workspace/{$tenant->slug}/bookings/create");
        $response->assertSessionHasErrors('package_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_authenticated_user_cannot_edit_another_tenants_booking(): void
    {
        [$tenantA, $userA] = $this->createTenantUserPair();
        [$tenantB] = $this->createTenantUserPair('nova-travel', 'farah@example.com');
        [$packageB, $customerB] = $this->createPackageAndCustomer($tenantB);

        $foreignBooking = Booking::query()->create([
            'tenant_id' => $tenantB->id,
            'package_id' => $packageB->id,
            'customer_id' => $customerB->id,
            'status' => 'paid',
        ]);

        $this->actingAs($userA);

        $this->get("/workspace/{$tenantA->slug}/bookings/{$foreignBooking->id}/edit")
            ->assertNotFound();
    }

    public function test_authenticated_user_can_delete_their_booking(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260404-001',
            'total_pax' => 2,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'status' => 'pending',
        ]);

        $package->update(['current_bookings' => 2]);

        $this->actingAs($user);

        $response = $this->delete("/workspace/{$tenant->slug}/bookings/{$booking->id}");

        $response->assertRedirect("/workspace/{$tenant->slug}/bookings");
        $this->assertDatabaseMissing('bookings', ['id' => $booking->id]);
        $this->assertDatabaseHas('packages', [
            'id' => $package->id,
            'current_bookings' => 0,
        ]);
    }

    public function test_quotation_conversion_is_blocked_when_package_is_sold_out(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $package->update([
            'booking_capacity' => 1,
            'current_bookings' => 1,
        ]);

        $quotation = Quotation::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'quotation_number' => 'QT-20260404-001',
            'subtotal' => 5990,
            'discount' => 0,
            'total_amount' => 5990,
            'status' => 'pending',
            'valid_until' => now()->addDay(),
        ]);

        $this->actingAs($user);

        $response = $this->from("/workspace/{$tenant->slug}/quotations/{$quotation->id}")->post("/workspace/{$tenant->slug}/quotations/{$quotation->id}/convert");

        $response->assertRedirect("/workspace/{$tenant->slug}/quotations/{$quotation->id}");
        $response->assertSessionHasErrors('package_id');
        $this->assertDatabaseCount('bookings', 0);
    }

    public function test_tourism_letter_preview_and_download_are_accessible(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-001',
            'total_pax' => 1,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'departure_date' => '2026-06-01',
            'return_date' => '2026-06-10',
            'flight_name' => 'Qatar Airways',
            'flight_number' => 'QR852',
            'status' => 'pending',
        ]);
        $booking->passengers()->sync([$customer->id]);

        $this->actingAs($user);

        $this->get("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');

        $this->get("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter/download")
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_tourism_letter_has_dedicated_edit_and_update_flow(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-002',
            'total_pax' => 1,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'departure_date' => '2026-06-01',
            'return_date' => '2026-06-10',
            'flight_name' => 'Malaysia Airlines',
            'flight_number' => 'MH123',
            'status' => 'pending',
        ]);

        $this->actingAs($user);

        $this->get("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter/edit")
            ->assertOk();

        $this->put("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter", [
            'departure_date' => '2026-06-03',
            'return_date' => '2026-06-12',
            'flight_name' => 'Qatar Airways',
            'flight_number' => 'QR283',
        ])->assertRedirect("/workspace/{$tenant->slug}/tourism-letters");

        $booking->refresh();
        $this->assertSame('2026-06-03', $booking->departure_date?->toDateString());
        $this->assertSame('2026-06-12', $booking->return_date?->toDateString());
        $this->assertSame('Qatar Airways', $booking->flight_name);
        $this->assertSame('QR283', $booking->flight_number);
    }

    public function test_tourism_letter_email_returns_clear_error_when_lead_email_missing(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package] = $this->createPackageAndCustomer($tenant);

        $customer = Customer::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Nur Aisyah',
            'full_name' => 'Nur Aisyah',
            'email' => null,
        ]);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-003',
            'total_pax' => 1,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'departure_date' => '2026-06-01',
            'return_date' => '2026-06-10',
            'flight_name' => 'Qatar Airways',
            'flight_number' => 'QR852',
            'status' => 'pending',
        ]);

        Mail::fake();
        $this->actingAs($user);

        $response = $this->from("/workspace/{$tenant->slug}/tourism-letters")
            ->post("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter/email");

        $response->assertRedirect("/workspace/{$tenant->slug}/tourism-letters");
        $response->assertSessionHasErrors('tourism_letter');
        Mail::assertNothingSent();
    }

    public function test_tourism_letter_email_sends_mail_to_lead_customer(): void
    {
        [$tenant, $user] = $this->createTenantUserPair();
        [$package, $customer] = $this->createPackageAndCustomer($tenant);

        $booking = Booking::query()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $package->id,
            'lead_customer_id' => $customer->id,
            'customer_id' => $customer->id,
            'booking_number' => 'BK-20260405-004',
            'total_pax' => 1,
            'total_price' => 5990,
            'balance_due' => 5990,
            'booking_status' => 'pending',
            'payment_status' => 'unpaid',
            'departure_date' => '2026-06-01',
            'return_date' => '2026-06-10',
            'flight_name' => 'Qatar Airways',
            'flight_number' => 'QR852',
            'status' => 'pending',
        ]);

        Mail::fake();
        $this->actingAs($user);

        $this->post("/workspace/{$tenant->slug}/bookings/{$booking->id}/tourism-letter/email")
            ->assertRedirect();

        Mail::assertSent(TourismLetterMail::class, function (TourismLetterMail $mail) use ($customer): bool {
            return $mail->hasTo($customer->email);
        });
    }
}
