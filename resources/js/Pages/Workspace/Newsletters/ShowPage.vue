<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    newsletter: Object,
    recipients: { type: Array, default: () => [] },
});

const isSending = ref(false);
const showConfirm = ref(false);

const statusMeta = computed(() => {
    const map = {
        draft: { bg: 'bg-gray-100', text: 'text-gray-700', dot: 'bg-gray-400' },
        scheduled: { bg: 'bg-blue-50', text: 'text-blue-700', dot: 'bg-blue-500' },
        sending: { bg: 'bg-amber-50', text: 'text-amber-700', dot: 'bg-amber-500' },
        sent: { bg: 'bg-emerald-50', text: 'text-emerald-700', dot: 'bg-emerald-500' },
    };
    return map[props.newsletter.status] || map.draft;
});

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

const sendNow = () => {
    isSending.value = true;
    router.post(route('newsletters.send', { tenant: props.workspace.slug, newsletter: props.newsletter.id }), {}, {
        preserveScroll: true,
        onFinish: () => {
            isSending.value = false;
            showConfirm.value = false;
        },
    });
};

const duplicateNewsletter = () => {
    router.post(route('newsletters.duplicate', { tenant: props.workspace.slug, newsletter: props.newsletter.id }));
};

const deleteNewsletter = () => {
    if (!confirm('Delete this newsletter?')) return;
    router.delete(route('newsletters.destroy', { tenant: props.workspace.slug, newsletter: props.newsletter.id }));
};

const recipientStatusColor = (status) => {
    if (status === 'sent') return 'text-emerald-600';
    if (status === 'failed') return 'text-red-500';
    return 'text-gray-400';
};

const tags = computed(() => props.newsletter.recipient_filter?.tags ?? []);
</script>

<template>
    <Head :title="newsletter.subject" />
    <WorkspaceLayout>
        <div class="w-full py-6">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('newsletters.index', { tenant: workspace.slug })" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                        Back
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ newsletter.subject }}</h1>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset ring-gray-200" :class="[statusMeta.bg, statusMeta.text]">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta.dot"></span>
                                {{ newsletter.status }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">Created {{ formatDate(newsletter.created_at) }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <button @click="duplicateNewsletter" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" /><path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" /></svg>
                        Duplicate
                    </button>
                    <Link
                        v-if="newsletter.status === 'draft'"
                        :href="route('newsletters.edit', { tenant: workspace.slug, newsletter: newsletter.id })"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                        Edit
                    </Link>
                    <button
                        v-if="newsletter.status === 'draft'"
                        @click="showConfirm = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 005.135 9.25h6.115a.75.75 0 010 1.5H5.135a1.5 1.5 0 00-1.442 1.086l-1.414 4.926a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z" /></svg>
                        Send Now
                    </button>
                    <button @click="deleteNewsletter" class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                        Delete
                    </button>
                </div>
            </div>

            <!-- Send Confirmation Modal -->
            <Teleport to="body">
                <div v-if="showConfirm" class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 backdrop-blur-sm" @click.self="showConfirm = false">
                    <div class="mx-4 w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-amber-50 mb-4">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-amber-600"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 005.135 9.25h6.115a.75.75 0 010 1.5H5.135a1.5 1.5 0 00-1.442 1.086l-1.414 4.926a.75.75 0 00.826.95 28.896 28.896 0 0015.293-7.154.75.75 0 000-1.115A28.897 28.897 0 003.105 2.289z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900">Send this newsletter?</h3>
                        <p class="mt-2 text-sm text-gray-500">This will immediately send the email to all eligible customers. This action cannot be undone.</p>
                        <div v-if="tags.length" class="mt-3 flex flex-wrap gap-1.5">
                            <span v-for="tag in tags" :key="tag" class="inline-flex items-center rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold text-gray-600">{{ tag }}</span>
                        </div>
                        <div class="mt-6 flex gap-3">
                            <button @click="showConfirm = false" class="flex-1 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 transition hover:bg-gray-50">Cancel</button>
                            <button @click="sendNow" :disabled="isSending" class="flex-1 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50">
                                {{ isSending ? 'Sending...' : 'Confirm & Send' }}
                            </button>
                        </div>
                    </div>
                </div>
            </Teleport>

            <div class="grid gap-6 lg:grid-cols-3">
                <!-- Email preview (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">Email Preview</h2>
                        </div>
                        <div class="p-6">
                            <div class="mx-auto max-w-lg rounded-xl border border-gray-100 bg-gray-50 p-6">
                                <p class="text-sm text-gray-600 mb-4">Hi <span class="font-semibold">Customer Name</span>,</p>
                                <div class="prose prose-sm max-w-none text-gray-700" v-html="newsletter.body_html"></div>
                                <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                                    <p class="text-[10px] text-gray-400">Sent by {{ workspace.name }} via TravelRocket</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Recipients table -->
                    <div v-if="recipients.length" class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-xs font-bold uppercase tracking-widest text-gray-400">Recipients ({{ newsletter.recipient_count }})</h2>
                        </div>
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-b border-gray-100">
                                    <th class="py-3 px-6 text-[10px] font-bold uppercase tracking-widest text-gray-400">Name</th>
                                    <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">Email</th>
                                    <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-24">Status</th>
                                    <th class="py-3 pr-6 text-[10px] font-bold uppercase tracking-widest text-gray-400 w-44">Sent At</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="r in recipients" :key="r.id" class="border-b border-gray-50">
                                    <td class="py-3 px-6 text-sm font-medium text-gray-900">{{ r.customer_name }}</td>
                                    <td class="py-3 px-4 text-sm text-gray-500">{{ r.email }}</td>
                                    <td class="py-3 px-4 text-center">
                                        <span class="text-xs font-semibold uppercase" :class="recipientStatusColor(r.status)">{{ r.status }}</span>
                                    </td>
                                    <td class="py-3 pr-6 text-xs text-gray-500">{{ r.sent_at ? formatDate(r.sent_at) : '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sidebar stats -->
                <div class="space-y-6">
                    <!-- Stats card -->
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-4">Campaign Stats</h3>
                        <div class="space-y-4">
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Status</span>
                                <span class="text-sm font-semibold capitalize" :class="statusMeta.text">{{ newsletter.status }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Total Recipients</span>
                                <span class="text-sm font-bold tabular-nums text-gray-900">{{ newsletter.recipient_count }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Sent</span>
                                <span class="text-sm font-bold tabular-nums text-emerald-600">{{ newsletter.sent_count }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Failed</span>
                                <span class="text-sm font-bold tabular-nums text-red-500">{{ newsletter.failed_count }}</span>
                            </div>
                            <div v-if="newsletter.sent_at" class="flex items-center justify-between">
                                <span class="text-sm text-gray-500">Sent At</span>
                                <span class="text-xs font-medium text-gray-700">{{ formatDate(newsletter.sent_at) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Tags -->
                    <div v-if="tags.length" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Targeted Tags</h3>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="tag in tags" :key="tag" class="inline-flex items-center rounded-full bg-gray-100 px-3 py-1.5 text-xs font-semibold text-gray-600">{{ tag }}</span>
                        </div>
                    </div>
                    <div v-else class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Target Audience</h3>
                        <p class="text-xs text-gray-500">All marketing-eligible customers</p>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
