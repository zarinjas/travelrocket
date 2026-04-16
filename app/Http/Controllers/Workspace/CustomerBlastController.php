<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\CustomerBlastLog;
use App\Models\CustomerBlastSetting;
use App\Models\CustomerBlastTemplate;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CustomerBlastController extends Controller
{
    private const TOKEN_TTL_SECONDS = 7200;

    public function index(Request $request, Tenant $tenant): Response
    {
        $filters = $this->extractFilters($request);
        $perPage = $this->extractPerPage($request);

        $query = $this->buildAudienceQuery($filters);
        $pagedQuery = clone $query;

        $customers = $pagedQuery
            ->orderBy('name')
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'passport_number' => $customer->passport_number,
                'phone' => $customer->phone,
                'email' => $customer->email,
                'tags' => $customer->tags ?? [],
                'allow_marketing' => (bool) $customer->allow_marketing,
            ]);

        $availableTags = Customer::query()
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn ($tags) => is_array($tags) ? $tags : [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter(fn ($tag) => $tag !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $blastEligibleCount = (clone $query)
            ->where('allow_marketing', true)
            ->where(fn (Builder $builder) => $builder
                ->where(fn (Builder $wa) => $wa->whereNotNull('phone')->where('phone', '!=', ''))
                ->orWhere(fn (Builder $mail) => $mail->whereNotNull('email')->where('email', '!=', '')))
            ->count();

        $whatsAppEligibleCount = (clone $query)
            ->where('allow_marketing', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        $emailEligibleCount = (clone $query)
            ->where('allow_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        $templates = CustomerBlastTemplate::query()
            ->orderBy('name')
            ->get(['id', 'name', 'body'])
            ->map(fn (CustomerBlastTemplate $template) => [
                'id' => $template->id,
                'name' => $template->name,
                'body' => $template->body,
            ])
            ->values()
            ->all();

        $setting = CustomerBlastSetting::query()->first();
        $logs = $this->queryBlastLogs(
            channel: 'all',
            dateFrom: null,
            dateTo: null,
            limit: 20
        )
            ->get()
            ->map(fn (CustomerBlastLog $log) => $this->formatBlastLog($log))
            ->values()
            ->all();

        return Inertia::render('Workspace/Customers/BlastPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'filters' => [
                ...$filters,
                'per_page' => $perPage,
            ],
            'customers' => $customers,
            'availableTags' => $availableTags,
            'blastSummary' => [
                'filtered_total' => (int) ($customers->total() ?? 0),
                'blast_eligible' => $blastEligibleCount,
                'whatsapp_eligible' => $whatsAppEligibleCount,
                'email_eligible' => $emailEligibleCount,
            ],
            'templates' => $templates,
            'draftMessage' => (string) ($setting?->draft_message ?? ''),
            'blastLogs' => $logs,
        ]);
    }

    public function createSelectionToken(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'mode' => ['required', 'string', 'in:filtered,ids'],
            'search' => ['nullable', 'string', 'max:255'],
            'passport_filter' => ['nullable', 'string', 'in:all,with_passport_copy,without_passport_copy'],
            'marketing_filter' => ['nullable', 'string', 'in:all,allowed,blocked'],
            'tag' => ['nullable', 'string', 'max:50'],
            'customer_ids' => ['nullable', 'array'],
            'customer_ids.*' => ['integer'],
        ]);

        $mode = $validated['mode'];
        $token = Str::uuid()->toString();

        if ($mode === 'filtered') {
            $filters = [
                'search' => trim((string) ($validated['search'] ?? '')),
                'passport_filter' => (string) ($validated['passport_filter'] ?? 'all'),
                'marketing_filter' => (string) ($validated['marketing_filter'] ?? 'all'),
                'tag' => trim((string) ($validated['tag'] ?? '')),
            ];

            $selectionQuery = $this->buildAudienceQuery($filters);
            $count = (clone $selectionQuery)->count();

            $this->putSelectionToken($tenant, $token, [
                'mode' => 'filtered',
                'filters' => $filters,
                'customer_ids' => [],
            ]);

            return response()->json([
                'token' => $token,
                'selected_count' => $count,
                'mode' => 'filtered',
            ]);
        }

        $requestedIds = collect($validated['customer_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values();

        $selectedIds = Customer::query()
            ->whereIn('id', $requestedIds->all())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->putSelectionToken($tenant, $token, [
            'mode' => 'ids',
            'filters' => null,
            'customer_ids' => $selectedIds,
        ]);

        return response()->json([
            'token' => $token,
            'selected_count' => count($selectedIds),
            'mode' => 'ids',
        ]);
    }

    public function exportSelected(Request $request, Tenant $tenant): StreamedResponse
    {
        $validated = $request->validate([
            'selection_token' => ['required', 'string'],
            'message' => ['nullable', 'string', 'max:2000'],
            'format' => ['nullable', 'string', 'in:crm_full,wa_ready,email_ready'],
        ]);

        [$selection] = $this->resolveSelection($tenant, $validated['selection_token']);
        $query = $this->selectionQuery($selection)->orderBy('name');

        $message = trim((string) ($validated['message'] ?? ''));
        $format = (string) ($validated['format'] ?? 'crm_full');

        if ($message === '') {
            $message = (string) (CustomerBlastSetting::query()->first()?->draft_message ?? '');
        }

        $rows = $query->get([
            'name',
            'passport_number',
            'phone',
            'email',
            'tags',
            'allow_marketing',
        ]);

        return response()->streamDownload(function () use ($rows, $message, $format): void {
            $output = fopen('php://output', 'w');

            if ($format === 'wa_ready') {
                fputcsv($output, ['name', 'phone_normalized', 'message', 'wa_link']);
            } elseif ($format === 'email_ready') {
                fputcsv($output, ['name', 'email', 'subject', 'message']);
            } else {
                fputcsv($output, ['name', 'passport_number', 'phone_normalized', 'message', 'wa_link', 'email', 'tags', 'allow_marketing']);
            }

            foreach ($rows as $customer) {
                $normalizedPhone = $this->normalizePhoneForWhatsApp((string) ($customer->phone ?? ''));
                $waLink = $normalizedPhone !== ''
                    ? sprintf('https://wa.me/%s?text=%s', $normalizedPhone, rawurlencode($message))
                    : '';

                if ($format === 'wa_ready') {
                    if (! $customer->allow_marketing || $normalizedPhone === '') {
                        continue;
                    }

                    fputcsv($output, [
                        $customer->name,
                        $normalizedPhone,
                        $message,
                        $waLink,
                    ]);

                    continue;
                }

                if ($format === 'email_ready') {
                    if (! $customer->allow_marketing || empty($customer->email)) {
                        continue;
                    }

                    fputcsv($output, [
                        $customer->name,
                        $customer->email,
                        'TravelRocket Update',
                        $message,
                    ]);

                    continue;
                }

                fputcsv($output, [
                    $customer->name,
                    $customer->passport_number,
                    $normalizedPhone,
                    $message,
                    $waLink,
                    $customer->email,
                    implode('|', $customer->tags ?? []),
                    $customer->allow_marketing ? '1' : '0',
                ]);
            }

            fclose($output);
        }, sprintf('customer-selected-%s.csv', $format), [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function logBlast(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'selection_token' => ['required', 'string'],
            'channel' => ['required', 'string', 'in:whatsapp,email'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        [$selection] = $this->resolveSelection($tenant, $validated['selection_token']);
        $query = $this->selectionQuery($selection);

        $recipientCount = (clone $query)->count();
        $whatsappReadyCount = (clone $query)
            ->where('allow_marketing', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();
        $emailReadyCount = (clone $query)
            ->where('allow_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        $log = CustomerBlastLog::query()->create([
            'channel' => $validated['channel'],
            'recipient_count' => $recipientCount,
            'whatsapp_ready_count' => $whatsappReadyCount,
            'email_ready_count' => $emailReadyCount,
            'selection_mode' => (string) ($selection['mode'] ?? null),
            'message' => trim((string) ($validated['message'] ?? '')),
            'meta' => [
                'selection_token' => $validated['selection_token'],
            ],
        ]);

        return response()->json([
            'log' => $this->formatBlastLog($log),
        ]);
    }

    public function listLogs(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'channel' => ['nullable', 'string', 'in:all,whatsapp,email'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date'],
            'limit' => ['nullable', 'integer', 'min:1', 'max:200'],
        ]);

        $logs = $this->queryBlastLogs(
            channel: (string) ($validated['channel'] ?? 'all'),
            dateFrom: $validated['date_from'] ?? null,
            dateTo: $validated['date_to'] ?? null,
            limit: (int) ($validated['limit'] ?? 100)
        )
            ->get()
            ->map(fn (CustomerBlastLog $log) => $this->formatBlastLog($log))
            ->values()
            ->all();

        return response()->json([
            'logs' => $logs,
        ]);
    }

    public function saveDraft(Request $request, Tenant $tenant): JsonResponse
    {
        $validated = $request->validate([
            'draft_message' => ['nullable', 'string', 'max:2000'],
        ]);

        $message = trim((string) ($validated['draft_message'] ?? ''));

        DB::transaction(function () use ($message): void {
            $setting = CustomerBlastSetting::query()->first();

            if ($setting) {
                $setting->update([
                    'draft_message' => $message === '' ? null : $message,
                ]);

                return;
            }

            CustomerBlastSetting::query()->create([
                'draft_message' => $message === '' ? null : $message,
            ]);
        });

        return response()->json([
            'saved' => true,
            'draft_message' => $message,
        ]);
    }

    public function storeTemplate(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('customer_blast_templates', 'name')->where(fn ($query) => $query->where('tenant_id', $tenant->id)),
            ],
            'body' => ['required', 'string'],
        ]);

        CustomerBlastTemplate::query()->create($validated);

        return redirect()
            ->route('customers.blast.index', ['tenant' => $tenant])
            ->with('success', 'Blast template saved.');
    }

    public function updateTemplate(Request $request, Tenant $tenant, CustomerBlastTemplate $template): RedirectResponse
    {
        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('customer_blast_templates', 'name')
                    ->where(fn ($query) => $query->where('tenant_id', $tenant->id))
                    ->ignore($template->id),
            ],
            'body' => ['required', 'string'],
        ]);

        $template->update($validated);

        return redirect()
            ->route('customers.blast.index', ['tenant' => $tenant])
            ->with('success', 'Blast template updated.');
    }

    public function destroyTemplate(Tenant $tenant, CustomerBlastTemplate $template): RedirectResponse
    {
        $template->delete();

        return redirect()
            ->route('customers.blast.index', ['tenant' => $tenant])
            ->with('success', 'Blast template deleted.');
    }

    private function extractFilters(Request $request): array
    {
        return [
            'search' => trim((string) $request->string('search')->toString()),
            'passport_filter' => $request->string('passport_filter')->toString() ?: 'all',
            'marketing_filter' => $request->string('marketing_filter')->toString() ?: 'allowed',
            'tag' => trim((string) $request->string('tag')->toString()),
        ];
    }

    private function extractPerPage(Request $request): int
    {
        $perPage = (int) $request->integer('per_page', 20);
        $allowedPerPage = [10, 20, 50, 100];

        if (! in_array($perPage, $allowedPerPage, true)) {
            return 20;
        }

        return $perPage;
    }

    private function buildAudienceQuery(array $filters): Builder
    {
        $passportFilter = (string) ($filters['passport_filter'] ?? 'all');
        $marketingFilter = (string) ($filters['marketing_filter'] ?? 'allowed');
        $tagFilter = trim((string) ($filters['tag'] ?? ''));

        return Customer::query()
            ->search($filters['search'] ?? null)
            ->when($passportFilter === 'with_passport_copy', fn (Builder $query) => $query->whereNotNull('passport_copy_path')->where('passport_copy_path', '!=', ''))
            ->when($passportFilter === 'without_passport_copy', fn (Builder $query) => $query->where(fn (Builder $nested) => $nested->whereNull('passport_copy_path')->orWhere('passport_copy_path', '')))
            ->when($marketingFilter === 'allowed', fn (Builder $query) => $query->where('allow_marketing', true))
            ->when($marketingFilter === 'blocked', fn (Builder $query) => $query->where('allow_marketing', false))
            ->when($tagFilter !== '', fn (Builder $query) => $query->whereJsonContains('tags', $tagFilter));
    }

    private function putSelectionToken(Tenant $tenant, string $token, array $payload): void
    {
        Cache::put($this->selectionCacheKey($tenant, $token), [
            ...$payload,
            'tenant_id' => $tenant->id,
            'created_at' => now()->toIso8601String(),
        ], now()->addSeconds(self::TOKEN_TTL_SECONDS));
    }

    private function resolveSelection(Tenant $tenant, string $token): array
    {
        $selection = Cache::get($this->selectionCacheKey($tenant, $token));

        abort_if(! is_array($selection), 422, 'Selection token invalid or expired. Please select audience again.');

        return [$selection, $this->selectionCacheKey($tenant, $token)];
    }

    private function selectionQuery(array $selection): Builder
    {
        $mode = (string) ($selection['mode'] ?? 'ids');

        if ($mode === 'filtered') {
            return $this->buildAudienceQuery((array) ($selection['filters'] ?? []));
        }

        $ids = collect((array) ($selection['customer_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return Customer::query()->whereIn('id', $ids);
    }

    private function selectionCacheKey(Tenant $tenant, string $token): string
    {
        return sprintf('customer-blast-selection:%d:%s', $tenant->id, $token);
    }

    private function normalizePhoneForWhatsApp(string $phone): string
    {
        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if ($digits === '') {
            return '';
        }

        if (str_starts_with($digits, '0')) {
            return '62'.substr($digits, 1);
        }

        if (str_starts_with($digits, '8')) {
            return '62'.$digits;
        }

        return $digits;
    }

    private function queryBlastLogs(string $channel, ?string $dateFrom, ?string $dateTo, int $limit)
    {
        return CustomerBlastLog::query()
            ->when($channel !== 'all', fn (Builder $query) => $query->where('channel', $channel))
            ->when($dateFrom, fn (Builder $query) => $query->whereDate('created_at', '>=', $dateFrom))
            ->when($dateTo, fn (Builder $query) => $query->whereDate('created_at', '<=', $dateTo))
            ->latest()
            ->limit($limit);
    }

    private function formatBlastLog(CustomerBlastLog $log): array
    {
        return [
            'id' => $log->id,
            'channel' => $log->channel,
            'recipient_count' => (int) $log->recipient_count,
            'whatsapp_ready_count' => (int) $log->whatsapp_ready_count,
            'email_ready_count' => (int) $log->email_ready_count,
            'selection_mode' => $log->selection_mode,
            'message' => $log->message,
            'created_at' => $log->created_at?->toIso8601String(),
        ];
    }
}
