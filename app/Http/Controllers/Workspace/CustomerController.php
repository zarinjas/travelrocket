<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

class CustomerController extends Controller
{
    private const SELECTION_TOKEN_TTL_SECONDS = 7200;
    private const DELETE_UNDO_TTL_SECONDS = 30;

    public function index(Request $request, Tenant $tenant): Response
    {
        $search = trim((string) $request->string('search')->toString());
        $passportFilter = $request->string('passport_filter')->toString();
        $marketingFilter = $request->string('marketing_filter')->toString();
        $tagFilter = trim((string) $request->string('tag')->toString());
        $sort = $request->string('sort')->toString() ?: 'name_asc';
        $perPage = (int) $request->integer('per_page', 20);

        $allowedPerPage = [10, 20, 50, 100];
        if (! in_array($perPage, $allowedPerPage, true)) {
            $perPage = 20;
        }

        $allowedSort = ['name_asc', 'name_desc', 'passport_asc', 'passport_desc'];
        if (! in_array($sort, $allowedSort, true)) {
            $sort = 'name_asc';
        }

        $customersQuery = $this->buildCustomerDatabaseQuery([
            'search' => $search,
            'passport_filter' => $passportFilter,
            'marketing_filter' => $marketingFilter,
            'tag' => $tagFilter,
        ]);

        $blastEligibleQuery = clone $customersQuery;
        $whatsAppEligibleQuery = clone $customersQuery;
        $emailEligibleQuery = clone $customersQuery;

        if ($sort === 'name_desc') {
            $customersQuery->orderByDesc('name');
        } elseif ($sort === 'passport_asc') {
            $customersQuery->orderBy('passport_number');
        } elseif ($sort === 'passport_desc') {
            $customersQuery->orderByDesc('passport_number');
        } else {
            $customersQuery->orderBy('name');
        }

        $customers = $customersQuery
            ->paginate($perPage)
            ->withQueryString()
            ->through(fn (Customer $customer) => [
                'id' => $customer->id,
                'name' => $customer->name,
                'full_name' => $customer->name,
                'passport_number' => $customer->passport_number,
                'passport_copy_path' => $customer->passport_copy_path,
                'passport_copy_url' => $customer->passport_copy_path ? '/storage/' . $customer->passport_copy_path : null,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'emergency_name' => $customer->emergency_name,
                'emergency_phone' => $customer->emergency_phone,
                'emergency_relation' => $customer->emergency_relation,
                'emergency_address' => $customer->emergency_address,
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

        $blastEligibleCount = (clone $blastEligibleQuery)
            ->where('allow_marketing', true)
            ->where(fn ($query) => $query->whereNotNull('phone')->where('phone', '!=', '')->orWhere(fn ($emailQuery) => $emailQuery->whereNotNull('email')->where('email', '!=' , '')))
            ->count();

        $whatsAppEligibleCount = (clone $whatsAppEligibleQuery)
            ->where('allow_marketing', true)
            ->whereNotNull('phone')
            ->where('phone', '!=', '')
            ->count();

        $emailEligibleCount = (clone $emailEligibleQuery)
            ->where('allow_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        return Inertia::render('Workspace/Customers/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customers' => $customers,
            'filters' => [
                'search' => $search,
                'passport_filter' => $passportFilter,
                'marketing_filter' => $marketingFilter,
                'tag' => $tagFilter,
                'sort' => $sort,
                'per_page' => $perPage,
            ],
            'availableTags' => $availableTags,
            'blastSummary' => [
                'filtered_total' => (int) ($customers->total() ?? 0),
                'blast_eligible' => $blastEligibleCount,
                'whatsapp_eligible' => $whatsAppEligibleCount,
                'email_eligible' => $emailEligibleCount,
            ],
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        return Inertia::render('Workspace/Customers/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customer' => null,
            'formAction' => route('customers.store', ['tenant' => $tenant]),
            'formMethod' => 'post',
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $this->validateCustomer($request);
        $passportCopy = $validated['passport_copy'] ?? null;

        unset($validated['passport_copy']);

        $validated['full_name'] = $validated['name'];
        $validated['document_no'] = $validated['passport_number'];

        if ($passportCopy) {
            $validated['passport_copy_path'] = $this->storePassportCopy($passportCopy, $tenant);
        }

        Customer::query()->create($validated);

        return redirect()
            ->route('customers.index', ['tenant' => $tenant])
            ->with('success', 'Customer created successfully.');
    }

    public function edit(Tenant $tenant, Customer $customer): Response
    {
        return Inertia::render('Workspace/Customers/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'customer' => [
                'id' => $customer->id,
                'name' => $customer->name,
                'full_name' => $customer->name,
                'passport_number' => $customer->passport_number,
                'passport_copy_path' => $customer->passport_copy_path,
                'passport_copy_url' => $customer->passport_copy_path ? '/storage/' . $customer->passport_copy_path : null,
                'address' => $customer->address,
                'email' => $customer->email,
                'phone' => $customer->phone,
                'emergency_name' => $customer->emergency_name,
                'emergency_phone' => $customer->emergency_phone,
                'emergency_relation' => $customer->emergency_relation,
                'emergency_address' => $customer->emergency_address,
                'tags' => $customer->tags ?? [],
                'allow_marketing' => (bool) $customer->allow_marketing,
            ],
            'formAction' => route('customers.update', ['tenant' => $tenant, 'customer' => $customer]),
            'formMethod' => 'put',
        ]);
    }

    public function update(Request $request, Tenant $tenant, Customer $customer): RedirectResponse
    {
        $validated = $this->validateCustomer($request);
        $passportCopy = $validated['passport_copy'] ?? null;

        unset($validated['passport_copy']);

        $validated['full_name'] = $validated['name'];
        $validated['document_no'] = $validated['passport_number'];

        if ($passportCopy) {
            $validated['passport_copy_path'] = $this->storePassportCopy($passportCopy, $tenant, $customer->passport_copy_path);
        }

        $customer->update($validated);

        return redirect()
            ->route('customers.index', ['tenant' => $tenant])
            ->with('success', 'Customer updated successfully.');
    }

    public function destroy(Tenant $tenant, Customer $customer): RedirectResponse
    {
        $customer->delete();

        return redirect()
            ->route('customers.index', ['tenant' => $tenant])
            ->with('success', 'Customer deleted successfully.');
    }

    protected function validateCustomer(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'passport_number' => ['required', 'string', 'max:100'],
            'passport_copy' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
            'address' => ['required', 'string'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'emergency_name' => ['required', 'string', 'max:255'],
            'emergency_phone' => ['required', 'string', 'max:50'],
            'emergency_relation' => ['required', 'string', 'max:100'],
            'emergency_address' => ['required', 'string'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'allow_marketing' => ['required', 'boolean'],
        ]);
    }

    protected function storePassportCopy(mixed $passportCopy, Tenant $tenant, ?string $oldPath = null): string
    {
        $directory = "passports/{$tenant->id}";
        $extension = strtolower((string) $passportCopy->getClientOriginalExtension());
        $hash = hash('sha256', $tenant->id.'|'.Str::uuid()->toString().'|'.microtime(true));
        $fileName = $hash.($extension ? ".{$extension}" : '');

        if ($oldPath) {
            Storage::disk('public')->delete($oldPath);
        }

        return $passportCopy->storeAs($directory, $fileName, 'public');
    }

    public function createSelectionToken(Request $request, Tenant $tenant)
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
        $token = (string) Str::uuid();

        if ($mode === 'filtered') {
            $filters = [
                'search' => trim((string) ($validated['search'] ?? '')),
                'passport_filter' => (string) ($validated['passport_filter'] ?? 'all'),
                'marketing_filter' => (string) ($validated['marketing_filter'] ?? 'all'),
                'tag' => trim((string) ($validated['tag'] ?? '')),
            ];

            $count = $this->buildCustomerDatabaseQuery($filters)->count();

            Cache::put($this->selectionCacheKey($tenant, $token), [
                'mode' => 'filtered',
                'filters' => $filters,
                'customer_ids' => [],
            ], now()->addSeconds(self::SELECTION_TOKEN_TTL_SECONDS));

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

        Cache::put($this->selectionCacheKey($tenant, $token), [
            'mode' => 'ids',
            'filters' => null,
            'customer_ids' => $selectedIds,
        ], now()->addSeconds(self::SELECTION_TOKEN_TTL_SECONDS));

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
        ]);

        $selection = Cache::get($this->selectionCacheKey($tenant, $validated['selection_token']));
        abort_if(! is_array($selection), 422, 'Selection token invalid or expired. Please select again.');

        $query = $this->queryFromSelection($selection)->orderBy('name');

        $rows = $query->get([
            'name',
            'passport_number',
            'phone',
            'email',
            'address',
            'emergency_name',
            'emergency_phone',
            'emergency_relation',
            'emergency_address',
            'tags',
            'allow_marketing',
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['name', 'passport_number', 'phone', 'email', 'address', 'emergency_name', 'emergency_phone', 'emergency_relation', 'emergency_address', 'tags', 'allow_marketing']);

            foreach ($rows as $customer) {
                fputcsv($output, [
                    $customer->name,
                    $customer->passport_number,
                    $customer->phone,
                    $customer->email,
                    $customer->address,
                    $customer->emergency_name,
                    $customer->emergency_phone,
                    $customer->emergency_relation,
                    $customer->emergency_address,
                    implode('|', $customer->tags ?? []),
                    $customer->allow_marketing ? '1' : '0',
                ]);
            }

            fclose($output);
        }, 'customer-database-selected.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    public function bulkDelete(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'selection_token' => ['required', 'string'],
        ]);

        $selection = Cache::get($this->selectionCacheKey($tenant, $validated['selection_token']));
        abort_if(! is_array($selection), 422, 'Selection token invalid or expired. Please select again.');

        $query = $this->queryFromSelection($selection);
        $deleted = 0;
        $deletedIds = [];

        $query
            ->orderBy('id')
            ->chunkById(200, function ($customers) use (&$deleted, &$deletedIds): void {
                foreach ($customers as $customer) {
                    $customer->delete();
                    $deleted++;
                    $deletedIds[] = (int) $customer->id;
                }
            });

        $undoToken = (string) Str::uuid();
        Cache::put($this->undoCacheKey($tenant, $undoToken), [
            'customer_ids' => array_values(array_unique($deletedIds)),
        ], now()->addSeconds(self::DELETE_UNDO_TTL_SECONDS));

        return response()->json([
            'deleted_count' => $deleted,
            'undo_token' => $undoToken,
            'undo_ttl_seconds' => self::DELETE_UNDO_TTL_SECONDS,
        ]);
    }

    public function undoBulkDelete(Request $request, Tenant $tenant)
    {
        $validated = $request->validate([
            'undo_token' => ['required', 'string'],
        ]);

        $cacheKey = $this->undoCacheKey($tenant, $validated['undo_token']);
        $payload = Cache::pull($cacheKey);
        abort_if(! is_array($payload), 422, 'Undo token invalid or expired.');

        $ids = collect((array) ($payload['customer_ids'] ?? []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        $restored = Customer::withTrashed()
            ->whereIn('id', $ids)
            ->restore();

        return response()->json([
            'restored_count' => (int) $restored,
        ]);
    }

    public function exportAudience(Request $request, Tenant $tenant): StreamedResponse
    {
        $search = trim((string) $request->string('search')->toString());
        $passportFilter = $request->string('passport_filter')->toString();
        $marketingFilter = $request->string('marketing_filter')->toString();
        $tagFilter = trim((string) $request->string('tag')->toString());

        $query = Customer::query()
            ->search($search)
            ->when($passportFilter === 'with_passport_copy', fn ($builder) => $builder->whereNotNull('passport_copy_path')->where('passport_copy_path', '!=', ''))
            ->when($passportFilter === 'without_passport_copy', fn ($builder) => $builder->where(fn ($nested) => $nested->whereNull('passport_copy_path')->orWhere('passport_copy_path', '')))
            ->when($marketingFilter === 'allowed', fn ($builder) => $builder->where('allow_marketing', true))
            ->when($marketingFilter === 'blocked', fn ($builder) => $builder->where('allow_marketing', false))
            ->when($tagFilter !== '', fn ($builder) => $builder->whereJsonContains('tags', $tagFilter))
            ->where('allow_marketing', true)
            ->where(fn ($builder) => $builder
                ->where(fn ($wa) => $wa->whereNotNull('phone')->where('phone', '!=', ''))
                ->orWhere(fn ($mail) => $mail->whereNotNull('email')->where('email', '!=' , '')))
            ->orderBy('name');

        $rows = $query->get([
            'name',
            'passport_number',
            'phone',
            'email',
            'tags',
            'allow_marketing',
        ]);

        return response()->streamDownload(function () use ($rows): void {
            $output = fopen('php://output', 'w');
            fputcsv($output, ['name', 'passport_number', 'phone', 'email', 'tags', 'allow_marketing']);

            foreach ($rows as $customer) {
                fputcsv($output, [
                    $customer->name,
                    $customer->passport_number,
                    $customer->phone,
                    $customer->email,
                    implode('|', $customer->tags ?? []),
                    $customer->allow_marketing ? '1' : '0',
                ]);
            }

            fclose($output);
        }, 'customer-audience.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    private function buildCustomerDatabaseQuery(array $filters): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $passportFilter = (string) ($filters['passport_filter'] ?? 'all');
        $marketingFilter = (string) ($filters['marketing_filter'] ?? 'all');
        $tagFilter = trim((string) ($filters['tag'] ?? ''));

        return Customer::query()
            ->search($search)
            ->when($passportFilter === 'with_passport_copy', fn ($query) => $query->whereNotNull('passport_copy_path')->where('passport_copy_path', '!=', ''))
            ->when($passportFilter === 'without_passport_copy', fn ($query) => $query->where(fn ($nested) => $nested->whereNull('passport_copy_path')->orWhere('passport_copy_path', '')))
            ->when($marketingFilter === 'allowed', fn ($query) => $query->where('allow_marketing', true))
            ->when($marketingFilter === 'blocked', fn ($query) => $query->where('allow_marketing', false))
            ->when($tagFilter !== '', fn ($query) => $query->whereJsonContains('tags', $tagFilter));
    }

    private function queryFromSelection(array $selection): Builder
    {
        if (($selection['mode'] ?? 'ids') === 'filtered') {
            return $this->buildCustomerDatabaseQuery((array) ($selection['filters'] ?? []));
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
        return sprintf('customer-db-selection:%d:%s', $tenant->id, $token);
    }

    private function undoCacheKey(Tenant $tenant, string $token): string
    {
        return sprintf('customer-db-undo:%d:%s', $tenant->id, $token);
    }
}
