<?php

namespace App\Http\Controllers\Workspace;

use App\Http\Controllers\Controller;
use App\Mail\NewsletterMail;
use App\Models\Customer;
use App\Models\Newsletter;
use App\Models\NewsletterRecipient;
use App\Models\Tenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Inertia\Inertia;
use Inertia\Response;

class NewsletterController extends Controller
{
    public function index(Tenant $tenant): Response
    {
        $newsletters = Newsletter::query()
            ->where('tenant_id', $tenant->id)
            ->latest()
            ->get()
            ->map(fn (Newsletter $n) => [
                'id' => $n->id,
                'subject' => $n->subject,
                'status' => $n->status,
                'recipient_count' => $n->recipient_count,
                'sent_count' => $n->sent_count,
                'failed_count' => $n->failed_count,
                'scheduled_at' => $n->scheduled_at?->toDateTimeString(),
                'sent_at' => $n->sent_at?->toDateTimeString(),
                'created_at' => $n->created_at->toDateTimeString(),
            ]);

        return Inertia::render('Workspace/Newsletters/IndexPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'newsletters' => $newsletters,
        ]);
    }

    public function create(Tenant $tenant): Response
    {
        $availableTags = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn ($tags) => is_array($tags) ? $tags : [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter(fn ($tag) => $tag !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $eligibleCount = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('allow_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        return Inertia::render('Workspace/Newsletters/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'availableTags' => $availableTags,
            'eligibleCount' => $eligibleCount,
            'newsletter' => null,
        ]);
    }

    public function edit(Tenant $tenant, Newsletter $newsletter): Response
    {
        $availableTags = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('tags')
            ->pluck('tags')
            ->flatMap(fn ($tags) => is_array($tags) ? $tags : [])
            ->map(fn ($tag) => trim((string) $tag))
            ->filter(fn ($tag) => $tag !== '')
            ->unique()
            ->sort()
            ->values()
            ->all();

        $eligibleCount = Customer::query()
            ->where('tenant_id', $tenant->id)
            ->where('allow_marketing', true)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->count();

        return Inertia::render('Workspace/Newsletters/FormPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'availableTags' => $availableTags,
            'eligibleCount' => $eligibleCount,
            'newsletter' => $newsletter,
        ]);
    }

    public function store(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'recipient_filter' => ['nullable', 'array'],
            'recipient_filter.tags' => ['nullable', 'array'],
        ]);

        $newsletter = Newsletter::create([
            'tenant_id' => $tenant->id,
            ...$validated,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('newsletters.show', ['tenant' => $tenant, 'newsletter' => $newsletter])
            ->with('success', 'Newsletter draft saved.');
    }

    public function update(Request $request, Tenant $tenant, Newsletter $newsletter): RedirectResponse
    {
        if ($newsletter->status === 'sent') {
            return back()->with('error', 'Cannot edit a sent newsletter.');
        }

        $validated = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'body_html' => ['required', 'string'],
            'recipient_filter' => ['nullable', 'array'],
            'recipient_filter.tags' => ['nullable', 'array'],
        ]);

        $newsletter->update($validated);

        return redirect()
            ->route('newsletters.show', ['tenant' => $tenant, 'newsletter' => $newsletter])
            ->with('success', 'Newsletter updated.');
    }

    public function show(Tenant $tenant, Newsletter $newsletter): Response
    {
        $recipients = $newsletter->recipients()
            ->with('customer:id,name,email')
            ->latest()
            ->limit(100)
            ->get()
            ->map(fn (NewsletterRecipient $r) => [
                'id' => $r->id,
                'customer_name' => $r->customer?->name ?? '-',
                'email' => $r->email,
                'status' => $r->status,
                'sent_at' => $r->sent_at?->toDateTimeString(),
                'error' => $r->error,
            ]);

        return Inertia::render('Workspace/Newsletters/ShowPage', [
            'workspace' => $tenant->only(['id', 'name', 'slug']),
            'newsletter' => [
                ...$newsletter->toArray(),
                'scheduled_at' => $newsletter->scheduled_at?->toDateTimeString(),
                'sent_at' => $newsletter->sent_at?->toDateTimeString(),
            ],
            'recipients' => $recipients,
        ]);
    }

    public function send(Request $request, Tenant $tenant, Newsletter $newsletter): RedirectResponse
    {
        if ($newsletter->status === 'sent') {
            return back()->with('error', 'Newsletter already sent.');
        }

        return DB::transaction(function () use ($tenant, $newsletter) {
            $query = Customer::query()
                ->where('tenant_id', $tenant->id)
                ->where('allow_marketing', true)
                ->whereNotNull('email')
                ->where('email', '!=', '');

            $filter = $newsletter->recipient_filter;
            if (!empty($filter['tags'])) {
                $query->where(function ($q) use ($filter) {
                    foreach ($filter['tags'] as $tag) {
                        $q->orWhereJsonContains('tags', $tag);
                    }
                });
            }

            $customers = $query->get(['id', 'name', 'email']);

            $sentCount = 0;
            $failedCount = 0;

            foreach ($customers as $customer) {
                try {
                    Mail::to($customer->email)->send(new NewsletterMail($newsletter, $customer->name));

                    NewsletterRecipient::create([
                        'newsletter_id' => $newsletter->id,
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'status' => 'sent',
                        'sent_at' => now(),
                    ]);

                    $sentCount++;
                } catch (\Throwable $e) {
                    NewsletterRecipient::create([
                        'newsletter_id' => $newsletter->id,
                        'customer_id' => $customer->id,
                        'email' => $customer->email,
                        'status' => 'failed',
                        'error' => substr($e->getMessage(), 0, 500),
                    ]);

                    $failedCount++;
                }
            }

            $newsletter->update([
                'status' => 'sent',
                'recipient_count' => $sentCount + $failedCount,
                'sent_count' => $sentCount,
                'failed_count' => $failedCount,
                'sent_at' => now(),
            ]);

            return back()->with('success', "Newsletter sent to {$sentCount} recipients.");
        });
    }

    public function duplicate(Tenant $tenant, Newsletter $newsletter): RedirectResponse
    {
        $copy = Newsletter::create([
            'tenant_id' => $tenant->id,
            'subject' => $newsletter->subject . ' (Copy)',
            'body_html' => $newsletter->body_html,
            'recipient_filter' => $newsletter->recipient_filter,
            'status' => 'draft',
        ]);

        return redirect()
            ->route('newsletters.edit', ['tenant' => $tenant, 'newsletter' => $copy])
            ->with('success', 'Newsletter duplicated as draft.');
    }

    public function destroy(Tenant $tenant, Newsletter $newsletter): RedirectResponse
    {
        $newsletter->delete();

        return redirect()
            ->route('newsletters.index', ['tenant' => $tenant])
            ->with('success', 'Newsletter deleted.');
    }
}
