<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContentBlock;
use App\Models\ContentMedia;
use App\Models\ContentRevision;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class ContentManagementController extends Controller
{
    public function index(): Response
    {
        $resolved = [
            'home' => $this->resolvePageValues('home', [
                'hero_badge' => 'Travel Agency Operating System',
                'hero_title' => 'Landing page yang lebih tenang, jelas, dan high-conviction.',
                'hero_subtitle' => 'Urus package, pelanggan, tempahan, dan prestasi agensi dalam satu workspace yang dibina untuk tindakan pantas, bukan dashboard yang serabut.',
                'primary_cta_label' => 'Start Free Setup',
                'primary_cta_url' => '/register?plan=growth',
                'secondary_cta_label' => 'Explore Plans',
                'secondary_cta_url' => '/pricing',
                'highlights' => [
                    'Travel agency operations in one tenant-isolated workspace',
                    'Built for packages, itineraries, customers, and bookings',
                    'Ready for self-serve onboarding and payment-first activation',
                ],
            ]),
            'features' => $this->resolvePageValues('features', [
                'hero_title' => 'Setiap workflow agensi, dalam satu sistem yang konsisten.',
                'hero_subtitle' => 'Daripada katalog package hingga follow-up pelanggan, TravelRocket satukan proses operasi dengan permukaan kerja yang lebih fokus dan predictable.',
                'partner_logos' => $this->defaultPartnerLogos(),
                'testimonials' => $this->defaultFeatureTestimonials(),
            ]),
            'pricing' => $this->resolvePageValues('pricing', [
                'hero_title' => 'Pilih plan yang padan dengan momentum agensi anda.',
                'hero_subtitle' => 'Struktur pricing ini direka untuk onboarding pantas, dengan trial flow, checkout, dan provisioning tenant yang terus bersambung.',
                'partner_logos' => $this->defaultPartnerLogos(),
                'testimonials' => $this->defaultPricingTestimonials(),
            ]),
        ];

        return Inertia::render('Admin/ContentPage', [
            'content' => $resolved,
            'revisions' => ContentRevision::query()
                ->latest()
                ->take(20)
                ->get()
                ->map(fn (ContentRevision $revision) => [
                    'id' => $revision->id,
                    'status' => $revision->status,
                    'note' => $revision->note,
                    'published_at' => $revision->published_at?->toIso8601String(),
                    'created_at' => $revision->created_at?->toIso8601String(),
                ])
                ->all(),
            'media' => ContentMedia::query()
                ->latest()
                ->take(50)
                ->get()
                ->map(fn (ContentMedia $media) => [
                    'id' => $media->id,
                    'original_name' => $media->original_name,
                    'mime_type' => $media->mime_type,
                    'size_bytes' => $media->size_bytes,
                    'url' => $media->url,
                    'created_at' => $media->created_at?->toIso8601String(),
                ])
                ->all(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $this->validatedPayload($request);

        $this->persistPageContent('home', $data['home'], (int) $request->user()->id);
        $this->persistPageContent('features', $data['features'], (int) $request->user()->id);
        $this->persistPageContent('pricing', $data['pricing'], (int) $request->user()->id);

        ContentRevision::query()->create([
            'status' => 'published',
            'payload' => $data,
            'note' => (string) $request->input('note', 'Published content update'),
            'published_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Marketing content has been published.');
    }

    public function saveDraft(Request $request): RedirectResponse
    {
        $data = $this->validatedPayload($request);

        ContentRevision::query()->create([
            'status' => 'draft',
            'payload' => $data,
            'note' => (string) $request->input('note', 'Draft saved'),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Draft has been saved.');
    }

    public function rollback(ContentRevision $revision, Request $request): RedirectResponse
    {
        $payload = $revision->payload;

        if (! is_array($payload) || ! isset($payload['home'], $payload['features'], $payload['pricing'])) {
            return back()->with('error', 'Revision payload is invalid.');
        }

        $this->persistPageContent('home', $payload['home'], (int) $request->user()->id);
        $this->persistPageContent('features', $payload['features'], (int) $request->user()->id);
        $this->persistPageContent('pricing', $payload['pricing'], (int) $request->user()->id);

        ContentRevision::query()->create([
            'status' => 'published',
            'payload' => $payload,
            'note' => 'Rollback from revision #'.$revision->id,
            'published_at' => now(),
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Revision has been published successfully.');
    }

    public function uploadMedia(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'file' => ['required', 'file', 'max:4096', 'mimes:jpg,jpeg,png,webp,svg,gif'],
        ]);

        $file = $validated['file'];
        $path = $file->store('content-media', 'public');

        ContentMedia::query()->create([
            'disk' => 'public',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'size_bytes' => (int) $file->getSize(),
            'uploaded_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Media uploaded successfully.');
    }

    public function destroyMedia(ContentMedia $media): RedirectResponse
    {
        Storage::disk($media->disk)->delete($media->path);
        $media->delete();

        return back()->with('success', 'Media deleted successfully.');
    }

    protected function validatedPayload(Request $request): array
    {
        return $request->validate([
            'home.hero_badge' => ['required', 'string', 'max:140'],
            'home.hero_title' => ['required', 'string', 'max:220'],
            'home.hero_subtitle' => ['required', 'string', 'max:800'],
            'home.primary_cta_label' => ['required', 'string', 'max:80'],
            'home.primary_cta_url' => ['required', 'string', 'max:220'],
            'home.secondary_cta_label' => ['required', 'string', 'max:80'],
            'home.secondary_cta_url' => ['required', 'string', 'max:220'],
            'home.highlights' => ['required', 'array', 'min:1', 'max:6'],
            'home.highlights.*' => ['required', 'string', 'max:200'],

            'features.hero_title' => ['required', 'string', 'max:220'],
            'features.hero_subtitle' => ['required', 'string', 'max:800'],
            'features.partner_logos' => ['required', 'array', 'min:1', 'max:10'],
            'features.partner_logos.*' => ['required', 'string', 'max:80'],
            'features.testimonials' => ['required', 'array', 'min:1', 'max:6'],
            'features.testimonials.*.quote' => ['required', 'string', 'max:600'],
            'features.testimonials.*.author' => ['required', 'string', 'max:100'],
            'features.testimonials.*.role' => ['required', 'string', 'max:140'],

            'pricing.hero_title' => ['required', 'string', 'max:220'],
            'pricing.hero_subtitle' => ['required', 'string', 'max:800'],
            'pricing.partner_logos' => ['required', 'array', 'min:1', 'max:10'],
            'pricing.partner_logos.*' => ['required', 'string', 'max:80'],
            'pricing.testimonials' => ['required', 'array', 'min:1', 'max:6'],
            'pricing.testimonials.*.quote' => ['required', 'string', 'max:600'],
            'pricing.testimonials.*.author' => ['required', 'string', 'max:100'],
            'pricing.testimonials.*.role' => ['required', 'string', 'max:140'],
        ]);
    }

    protected function persistPageContent(string $page, array $values, int $userId): void
    {
        foreach ($values as $blockKey => $value) {
            $record = ContentBlock::query()->firstOrNew([
                'page' => $page,
                'block_key' => $blockKey,
            ]);

            if (! $record->exists) {
                $record->created_by = $userId;
            }

            $record->payload = ['value' => $value];
            $record->is_active = true;
            $record->published_at = now();
            $record->updated_by = $userId;
            $record->save();
        }
    }

    protected function resolvePageValues(string $page, array $defaults): array
    {
        $records = ContentBlock::query()
            ->where('page', $page)
            ->where('is_active', true)
            ->get(['block_key', 'payload']);

        if ($records->isEmpty()) {
            return $defaults;
        }

        $resolved = $defaults;

        foreach ($records as $record) {
            $resolved[$record->block_key] = data_get($record->payload, 'value', $defaults[$record->block_key] ?? null);
        }

        return $resolved;
    }

    protected function defaultPartnerLogos(): array
    {
        return [
            'AeroVista Travel',
            'Madinah Gate Tours',
            'Nusa Journey Co.',
            'Safa Transit Hub',
            'Langit Timur Holidays',
        ];
    }

    protected function defaultFeatureTestimonials(): array
    {
        return [
            [
                'quote' => 'Sebelum ini team kami guna banyak sheet. Lepas pindah ke TravelRocket, follow-up customer dan booking status jadi jauh lebih jelas.',
                'author' => 'Nadia Rahman',
                'role' => 'Operations Lead, AeroVista Travel',
            ],
            [
                'quote' => 'Apa yang paling membantu ialah semua modul rasa connected. Owner boleh tengok angka, staff pula terus boleh execute tugas.',
                'author' => 'Farid Azman',
                'role' => 'Founder, Madinah Gate Tours',
            ],
        ];
    }

    protected function defaultPricingTestimonials(): array
    {
        return [
            [
                'quote' => 'Trial onboarding memang smooth. Dalam masa singkat team kami dah migrate proses booking tanpa training yang berat.',
                'author' => 'Shafiq Musa',
                'role' => 'Owner, Nusa Journey Co.',
            ],
            [
                'quote' => 'Plan Growth paling ngam sebab team boleh terus guna module CRM, booking, dan reporting dalam satu aliran kerja.',
                'author' => 'Aina Salleh',
                'role' => 'Customer Success Manager, Langit Timur Holidays',
            ],
        ];
    }
}
