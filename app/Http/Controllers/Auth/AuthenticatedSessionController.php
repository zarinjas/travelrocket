<?php

namespace App\Http\Controllers\Auth;

use Illuminate\Auth\Events\PasswordReset;
use App\Models\Booking;
use App\Models\Customer;
use App\Models\Package;
use App\Models\Tenant;
use App\Models\User;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    public function create(): Response
    {
        return Inertia::render('Auth/LoginPage');
    }

    public function forgotPassword(): Response
    {
        return Inertia::render('Auth/ForgotPasswordPage');
    }

    public function sendResetLink(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        $status = Password::sendResetLink($validated);

        if ($status === Password::RESET_LINK_SENT) {
            return back()->with('status', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }

    public function showResetPasswordForm(Request $request, string $token): Response
    {
        return Inertia::render('Auth/ResetPasswordPage', [
            'token' => $token,
            'email' => $request->string('email')->toString(),
        ]);
    }

    public function resetPassword(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string'],
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $status = Password::reset($validated, function (User $user, string $password): void {
            $user->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ])->save();

            event(new PasswordReset($user));
        });

        if ($status === Password::PASSWORD_RESET) {
            return redirect()->route('login')->with('success', __($status));
        }

        return back()->withErrors([
            'email' => __($status),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'string', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        $request->session()->regenerate();

        /** @var User $user */
        $user = $request->user();
        $user->load('tenant');

        if ($user->isSuperAdmin()) {
            return redirect()
                ->route('admin.content.index')
                ->with('success', 'Welcome back, platform admin.');
        }

        if (! $user->tenant) {
            Auth::logout();

            return redirect()->route('login')->with('error', 'Your account is not linked to a workspace yet.');
        }

        return redirect()
            ->route('dashboard', ['tenant' => $user->tenant])
            ->with('success', 'Welcome back to your workspace.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('success', 'You have been signed out.');
    }

    public function quickLogin(Request $request): RedirectResponse
    {
        $user = User::query()->where('email', 'demo@travelrocket.test')->first();

        if (! $user) {
            $tenant = Tenant::create([
                'name' => 'Demo Travel',
                'slug' => 'demo-travel',
                'company_name' => 'Demo Travel',
            ]);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => 'Demo Owner',
                'email' => 'demo@travelrocket.test',
                'password' => Hash::make('password'),
                'role' => 'owner',
            ]);
        }

        $user->load('tenant');

        $this->seedDemoWorkspaceData($user->tenant);

        Auth::login($user);
        $request->session()->regenerate();

        return redirect()
            ->route('dashboard', ['tenant' => $user->tenant])
            ->with('success', 'Quick login activated with demo workspace data.');
    }

    protected function seedDemoWorkspaceData(?Tenant $tenant): void
    {
        if (! $tenant) {
            return;
        }

        if (Package::withoutGlobalScopes()->where('tenant_id', $tenant->id)->exists()) {
            return;
        }

        $umrah = Package::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Umrah Premium',
            'type' => 'Umrah',
            'price_amount' => 5990,
            'status' => Package::STATUS_PUBLISHED,
            'description' => 'A premium Umrah package for quick dashboard testing.',
        ]);

        $tour = Package::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Langkawi Escape',
            'type' => 'Tour',
            'price_amount' => 1890,
            'status' => Package::STATUS_PUBLISHED,
            'description' => 'A short local escape package for demo bookings.',
        ]);

        $aisyah = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'full_name' => 'Nur Aisyah',
            'email' => 'nur.aisyah@example.com',
            'phone' => '0123000001',
            'document_no' => 'A1234567',
            'nationality' => 'Malaysian',
        ]);

        $faris = Customer::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'full_name' => 'Faris Hakim',
            'email' => 'faris.hakim@example.com',
            'phone' => '0123000002',
            'document_no' => 'B7654321',
            'nationality' => 'Malaysian',
        ]);

        Booking::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $umrah->id,
            'customer_id' => $aisyah->id,
            'travel_date' => now()->addDays(30)->toDateString(),
            'status' => 'paid',
            'booking_status' => Booking::BOOKING_STATUS_CONFIRMED,
            'payment_status' => Booking::PAYMENT_STATUS_PAID,
            'notes' => 'Paid booking for dashboard revenue testing.',
        ]);

        Booking::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $tour->id,
            'customer_id' => $faris->id,
            'travel_date' => now()->addDays(14)->toDateString(),
            'status' => 'pending',
            'booking_status' => Booking::BOOKING_STATUS_PENDING,
            'payment_status' => Booking::PAYMENT_STATUS_UNPAID,
            'notes' => 'Pending booking for status count testing.',
        ]);

        Booking::withoutGlobalScopes()->create([
            'tenant_id' => $tenant->id,
            'package_id' => $tour->id,
            'customer_id' => $aisyah->id,
            'travel_date' => now()->addDays(45)->toDateString(),
            'status' => 'cancelled',
            'booking_status' => Booking::BOOKING_STATUS_CANCELLED,
            'payment_status' => Booking::PAYMENT_STATUS_UNPAID,
            'notes' => 'Cancelled booking for lifecycle coverage.',
        ]);
    }
}
