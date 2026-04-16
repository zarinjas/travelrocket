<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    newsletters: { type: Array, default: () => [] },
});

const statusColor = (status) => {
    const map = {
        draft: 'bg-gray-100 text-gray-700',
        scheduled: 'bg-blue-50 text-blue-700',
        sending: 'bg-amber-50 text-amber-700',
        sent: 'bg-emerald-50 text-emerald-700',
    };
    return map[status] || 'bg-gray-100 text-gray-600';
};

const formatDate = (d) => d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' }) : '-';

const selectedIds = ref([]);
const allSelected = computed(() => selectedIds.value.length === props.newsletters.length && props.newsletters.length > 0);

const toggleAll = () => {
    selectedIds.value = allSelected.value ? [] : props.newsletters.map(n => n.id);
};

const deleteSelected = () => {
    if (!confirm(`Delete ${selectedIds.value.length} newsletter(s)?`)) return;
    selectedIds.value.forEach(id => {
        router.delete(route('newsletters.destroy', { tenant: props.workspace.slug, newsletter: id }), { preserveScroll: true });
    });
    selectedIds.value = [];
};
</script>

<template>
    <Head title="Newsletters" />
    <WorkspaceLayout>
        <div class="w-full py-6">
            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">Newsletters</h1>
                    <p class="mt-1 text-sm text-gray-500">Create and send email campaigns to your customers.</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        v-if="selectedIds.length"
                        @click="deleteSelected"
                        class="inline-flex items-center gap-2 rounded-lg border border-red-200 bg-white px-4 py-2.5 text-sm font-semibold text-red-600 shadow-sm transition hover:bg-red-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 00-1.5.06l.3 7.5a.75.75 0 101.5-.06l-.3-7.5zm4.34.06a.75.75 0 10-1.5-.06l-.3 7.5a.75.75 0 101.5.06l.3-7.5z" clip-rule="evenodd" /></svg>
                        Delete ({{ selectedIds.length }})
                    </button>
                    <Link
                        :href="route('newsletters.create', { tenant: workspace.slug })"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        New Newsletter
                    </Link>
                </div>
            </div>

            <!-- Empty state -->
            <div v-if="!newsletters.length" class="flex flex-col items-center justify-center rounded-xl border border-dashed border-gray-200 bg-white py-16">
                <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-gray-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" class="h-6 w-6 text-gray-400"><path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" /></svg>
                </div>
                <p class="mt-4 text-sm font-semibold text-gray-900">No newsletters yet</p>
                <p class="mt-1 text-xs text-gray-500">Create your first email campaign to reach your customers.</p>
                <Link :href="route('newsletters.create', { tenant: workspace.slug })" class="mt-4 inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800">
                    Create Newsletter
                </Link>
            </div>

            <!-- Table -->
            <div v-else class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                <div class="overflow-x-auto">
                <table class="w-full text-left">
                    <thead>
                        <tr class="border-b border-gray-100">
                            <th class="py-3 pl-6 pr-2 w-10">
                                <input type="checkbox" :checked="allSelected" @change="toggleAll" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                            </th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">Subject</th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-28">Status</th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-28">Recipients</th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-24">Sent</th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-24">Failed</th>
                            <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 w-40">Date</th>
                            <th class="py-3 pr-6 w-10"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="nl in newsletters" :key="nl.id" class="border-b border-gray-50 transition hover:bg-gray-50/50">
                            <td class="py-3.5 pl-6 pr-2">
                                <input type="checkbox" v-model="selectedIds" :value="nl.id" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                            </td>
                            <td class="py-3.5 px-4">
                                <Link :href="route('newsletters.show', { tenant: workspace.slug, newsletter: nl.id })" class="text-sm font-semibold text-gray-900 hover:text-gray-700 transition">
                                    {{ nl.subject }}
                                </Link>
                            </td>
                            <td class="py-3.5 px-4 text-center">
                                <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[10px] font-bold uppercase tracking-wide" :class="statusColor(nl.status)">{{ nl.status }}</span>
                            </td>
                            <td class="py-3.5 px-4 text-center text-sm tabular-nums text-gray-700">{{ nl.recipient_count }}</td>
                            <td class="py-3.5 px-4 text-center text-sm tabular-nums text-emerald-600 font-medium">{{ nl.sent_count }}</td>
                            <td class="py-3.5 px-4 text-center text-sm tabular-nums text-red-500 font-medium">{{ nl.failed_count }}</td>
                            <td class="py-3.5 px-4 text-xs text-gray-500">{{ nl.sent_at ? formatDate(nl.sent_at) : formatDate(nl.created_at) }}</td>
                            <td class="py-3.5 pr-6">
                                <Link :href="route('newsletters.show', { tenant: workspace.slug, newsletter: nl.id })" class="text-xs font-medium text-gray-500 hover:text-gray-900 transition">View</Link>
                            </td>
                        </tr>
                    </tbody>
                </table>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
