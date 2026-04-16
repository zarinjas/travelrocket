<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class SettingsController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $requiredFields = [
            'company_name' => 'Company Name',
            'company_address' => 'Company Address',
            'company_phone' => 'Phone Number',
            'company_email' => 'Email Address',
            'logo_path' => 'Company Logo',
            'bank_name' => 'Bank Name',
            'bank_account_number' => 'Account Number',
        ];

        $checklist = collect($requiredFields)->map(fn ($label, $key) => [
            'key' => $key,
            'label' => $label,
            'done' => filled($tenant->{$key}),
        ])->values()->all();

        $filledCount = collect($checklist)->where('done', true)->count();
        $profileCompleteness = (int) round(($filledCount / count($requiredFields)) * 100);

        return Inertia::render('Workspace/Settings/IndexPage', [
            'workspace' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'logo_url' => $tenant->logo_path ? '/storage/' . $tenant->logo_path : null,
                'company_name' => $tenant->company_name,
                'company_address' => $tenant->company_address,
                'company_phone' => $tenant->company_phone,
                'company_email' => $tenant->company_email,
                'company_website' => $tenant->company_website,
                'bank_name' => $tenant->bank_name,
                'bank_account_name' => $tenant->bank_account_name,
                'bank_account_number' => $tenant->bank_account_number,
                'bank_swift' => $tenant->bank_swift,
                'quotation_terms' => $tenant->quotation_terms,
                'social_links' => $tenant->social_links ?? [
                    'facebook' => null,
                    'instagram' => null,
                    'tiktok' => null,
                    'x' => null,
                    'linkedin' => null,
                ],
                'profile_completeness' => $profileCompleteness,
                'checklist' => $checklist,
            ],
        ]);
    }

    public function updateBranding(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'logo' => ['nullable', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'name' => ['nullable', 'string', 'max:255'],
            'company_name' => ['nullable', 'string', 'max:255'],
            'company_address' => ['nullable', 'string', 'max:2000'],
            'company_phone' => ['nullable', 'string', 'max:50'],
            'company_email' => ['nullable', 'email', 'max:255'],
            'company_website' => ['nullable', 'url:http,https', 'max:255'],
            'bank_name' => ['nullable', 'string', 'max:255'],
            'bank_account_name' => ['nullable', 'string', 'max:255'],
            'bank_account_number' => ['nullable', 'string', 'max:100'],
            'bank_swift' => ['nullable', 'string', 'max:50'],
            'quotation_terms' => ['nullable', 'string', 'max:4000'],
            'social_links' => ['nullable', 'array'],
            'social_links.facebook' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.instagram' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.tiktok' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.x' => ['nullable', 'url:http,https', 'max:255'],
            'social_links.linkedin' => ['nullable', 'url:http,https', 'max:255'],
        ]);

        $logoFile = $validated['logo'] ?? null;
        unset($validated['logo']);

        $tenant->update($validated);

        if ($logoFile) {
            $directory = "tenant-branding/{$tenant->id}";
            $extension = strtolower((string) $logoFile->getClientOriginalExtension());
            $fileName = hash('sha256', $tenant->id . '|' . Str::uuid()->toString()) . ($extension ? ".{$extension}" : '');

            if ($tenant->logo_path) {
                Storage::disk('public')->delete($tenant->logo_path);
            }

            $tenant->update([
                'logo_path' => $logoFile->storeAs($directory, $fileName, 'public'),
            ]);
        }

        return redirect()
            ->route('settings.index', ['tenant' => $tenant])
            ->with('success', 'Settings updated successfully.');
    }
}
