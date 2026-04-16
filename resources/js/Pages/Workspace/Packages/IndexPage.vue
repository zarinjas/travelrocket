<script setup>
import { Head, router } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    packages: { type: Array, default: () => [] },
    packageTypes: { type: Array, default: () => [] },
    activeCategory: { type: String, default: '' },
});

const query = ref('');
const sortKey = ref('name');
const sortDir = ref('asc');
const selected = ref([]);

const allIds = computed(() => props.packages.map((p) => p.id));
const isAllSelected = computed(() => props.packages.length > 0 && selected.value.length === props.packages.length);

const toggleAll = () => {
    selected.value = isAllSelected.value ? [] : [...allIds.value];
};

const setSorting = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
};

const sortIcon = (key) => {
    if (sortKey.value !== key) return '↕';
    return sortDir.value === 'asc' ? '↑' : '↓';
};

const filteredPackages = computed(() => {
    const search = String(query.value || '').trim().toLowerCase();

    let results = props.packages;

    if (search) {
        results = results.filter((p) => {
            return [p.name, p.destination, p.category, p.status]
                .filter(Boolean)
                .join(' ')
                .toLowerCase()
                .includes(search);
        });
    }

    return [...results].sort((a, b) => {
        let aVal = a[sortKey.value];
        let bVal = b[sortKey.value];

        if (typeof aVal === 'string') aVal = aVal.toLowerCase();
        if (typeof bVal === 'string') bVal = bVal.toLowerCase();

        if (aVal < bVal) return sortDir.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });
});

// Stats
const totalRevenue = computed(() => props.packages.reduce((s, p) => s + Number(p.revenue || 0), 0));
const publishedCount = computed(() => props.packages.filter((p) => p.status === 'published').length);
const soldOutCount = computed(() => props.packages.filter((p) => p.is_sold_out).length);
const totalCapacity = computed(() => props.packages.reduce((s, p) => s + Number(p.booking_capacity || 0), 0));
const totalBooked = computed(() => props.packages.reduce((s, p) => s + Number(p.current_bookings || 0), 0));
const overallCapacityPercent = computed(() => totalCapacity.value > 0 ? Math.round((totalBooked.value / totalCapacity.value) * 100) : 0);

const capacityPercent = (p) => {
    const cap = Number(p.booking_capacity || 0);
    const booked = Number(p.current_bookings || 0);
    return cap <= 0 ? 0 : Math.min(100, Math.round((booked / cap) * 100));
};

const capacityBarColor = (p) => {
    const pct = capacityPercent(p);
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const rm = (val) => `RM ${Number(val || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const destroyPackage = (id, name) => {
    if (!window.confirm(`Delete "${name}"? This cannot be undone.`)) return;
    router.delete(`/workspace/${props.workspace.slug}/packages/${id}`, { preserveScroll: true });
};

const toggleStatus = (id) => {
    router.post(`/workspace/${props.workspace.slug}/packages/${id}/toggle-status`, {}, { preserveScroll: true });
};

const duplicatePackage = (id) => {
    router.post(`/workspace/${props.workspace.slug}/packages/${id}/duplicate`);
};

const bulkDelete = () => {
    if (!selected.value.length) return;
    if (!window.confirm(`Delete ${selected.value.length} package(s)? This cannot be undone.`)) return;
    router.delete(`/workspace/${props.workspace.slug}/packages/bulk-delete`, {
        data: { ids: selected.value },
        preserveScroll: true,
        onSuccess: () => (selected.value = []),
    });
};
</script>

<template>
    <Head :title="`${workspace.name} — Packages`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Packages</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage travel products — capacity, pricing, and performance</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        :href="`/workspace/${workspace.slug}/packages/export-csv`"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                        Export CSV
                    </a>
                    <a
                        :href="activeCategory ? `/workspace/${workspace.slug}/packages/create?category=${encodeURIComponent(activeCategory)}` : `/workspace/${workspace.slug}/packages/create`"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        Create Package
                    </a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Total Packages</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-gray-900">{{ packages.length }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Published</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-emerald-600">{{ publishedCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Sold Out</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-red-600">{{ soldOutCount }}</p>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Capacity Usage</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-gray-900">{{ overallCapacityPercent }}%</p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full bg-emerald-500 transition-all" :style="{ width: `${overallCapacityPercent}%` }"></div>
                    </div>
                </div>
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500">Total Revenue</p>
                    <p class="mt-2 text-xl font-bold tabular-nums text-gray-900">{{ rm(totalRevenue) }}</p>
                </div>
            </div>

            <!-- Main Table Card -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">

                <!-- Toolbar -->
                <div class="border-b border-gray-100 px-5 py-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-wrap items-center gap-2">
                            <!-- Category Tabs -->
                            <a
                                :href="`/workspace/${workspace.slug}/packages`"
                                class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                                :class="!activeCategory ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >All</a>
                            <a
                                v-for="cat in packageTypes"
                                :key="cat"
                                :href="`/workspace/${workspace.slug}/packages?category=${encodeURIComponent(cat)}`"
                                class="rounded-full px-3.5 py-1.5 text-xs font-semibold transition"
                                :class="activeCategory === cat ? 'bg-gray-900 text-white' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                            >{{ cat }}</a>
                        </div>

                        <!-- Search -->
                        <div class="relative min-w-0 sm:w-72">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                            <input
                                v-model="query"
                                type="search"
                                placeholder="Search packages..."
                                class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900 transition"
                            />
                        </div>
                    </div>
                </div>

                <!-- Bulk Actions Bar -->
                <div v-if="selected.length" class="flex items-center gap-3 border-b border-gray-100 bg-gray-50 px-5 py-3">
                    <span class="text-sm font-medium text-gray-700">{{ selected.length }} selected</span>
                    <button
                        type="button"
                        class="rounded-lg bg-red-600 px-3 py-1.5 text-xs font-semibold text-white shadow-sm transition hover:bg-red-700"
                        @click="bulkDelete"
                    >Delete Selected</button>
                    <button
                        type="button"
                        class="rounded-lg bg-white px-3 py-1.5 text-xs font-semibold text-gray-700 ring-1 ring-inset ring-gray-200 shadow-sm transition hover:bg-gray-50"
                        @click="selected = []"
                    >Clear</button>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/50 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">
                                <th class="w-10 px-5 py-3">
                                    <input type="checkbox" :checked="isAllSelected" class="rounded border-gray-300 text-gray-900 focus:ring-gray-900" @change="toggleAll" />
                                </th>
                                <th class="cursor-pointer px-4 py-3 select-none" @click="setSorting('name')">
                                    Package <span class="text-gray-400">{{ sortIcon('name') }}</span>
                                </th>
                                <th class="cursor-pointer px-4 py-3 select-none" @click="setSorting('category')">
                                    Category <span class="text-gray-400">{{ sortIcon('category') }}</span>
                                </th>
                                <th class="cursor-pointer px-4 py-3 select-none" @click="setSorting('price')">
                                    Price <span class="text-gray-400">{{ sortIcon('price') }}</span>
                                </th>
                                <th class="px-4 py-3">Capacity</th>
                                <th class="cursor-pointer px-4 py-3 select-none" @click="setSorting('revenue')">
                                    Revenue <span class="text-gray-400">{{ sortIcon('revenue') }}</span>
                                </th>
                                <th class="cursor-pointer px-4 py-3 select-none" @click="setSorting('status')">
                                    Status <span class="text-gray-400">{{ sortIcon('status') }}</span>
                                </th>
                                <th class="px-4 py-3 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr
                                v-for="pkg in filteredPackages"
                                :key="pkg.id"
                                class="group hover:bg-gray-50/80 transition-colors"
                                :class="selected.includes(pkg.id) ? 'bg-gray-50' : ''"
                            >
                                <td class="px-5 py-3.5">
                                    <input
                                        type="checkbox"
                                        :value="pkg.id"
                                        v-model="selected"
                                        class="rounded border-gray-300 text-gray-900 focus:ring-gray-900"
                                    />
                                </td>
                                <td class="px-4 py-3.5">
                                    <a :href="`/workspace/${workspace.slug}/packages/${pkg.id}`" class="font-semibold text-gray-900 hover:underline">
                                        {{ pkg.name }}
                                    </a>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ pkg.destination }} · {{ pkg.date_range || 'No dates' }}</p>
                                </td>
                                <td class="px-4 py-3.5">
                                    <span class="inline-flex rounded-full bg-gray-100 px-2.5 py-0.5 text-xs font-semibold text-gray-700">
                                        {{ pkg.category }}
                                    </span>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums font-semibold text-gray-900">{{ rm(pkg.price) }}</td>
                                <td class="px-4 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <div class="w-20">
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                                <div class="h-1.5 rounded-full transition-all" :class="capacityBarColor(pkg)" :style="{ width: `${capacityPercent(pkg)}%` }"></div>
                                            </div>
                                        </div>
                                        <span class="text-xs tabular-nums text-gray-600">{{ pkg.current_bookings }}/{{ pkg.booking_capacity }}</span>
                                        <span v-if="pkg.is_sold_out" class="inline-flex rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-700 ring-1 ring-inset ring-red-600/20">FULL</span>
                                        <span v-else-if="capacityPercent(pkg) >= 90" class="inline-flex rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700 ring-1 ring-inset ring-amber-600/20">HOT</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5 tabular-nums text-gray-700">
                                    <div>
                                        <span class="font-semibold text-gray-900">{{ rm(pkg.revenue) }}</span>
                                        <p class="text-xs text-gray-400">{{ pkg.bookings_count }} bookings</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3.5">
                                    <button
                                        type="button"
                                        class="inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-semibold ring-1 ring-inset cursor-pointer transition"
                                        :class="pkg.status === 'published' ? 'bg-emerald-50 text-emerald-700 ring-emerald-600/20 hover:bg-emerald-100' : 'bg-gray-50 text-gray-600 ring-gray-500/20 hover:bg-gray-100'"
                                        :title="`Click to ${pkg.status === 'published' ? 'unpublish' : 'publish'}`"
                                        @click="toggleStatus(pkg.id)"
                                    >
                                        <span class="mr-1.5 h-1.5 w-1.5 rounded-full" :class="pkg.status === 'published' ? 'bg-emerald-500' : 'bg-gray-400'"></span>
                                        {{ pkg.status === 'published' ? 'Published' : 'Draft' }}
                                    </button>
                                </td>
                                <td class="px-4 py-3.5 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <a
                                            :href="`/workspace/${workspace.slug}/packages/${pkg.id}`"
                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                            title="View"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41z" clip-rule="evenodd" /></svg>
                                        </a>
                                        <a
                                            :href="`/workspace/${workspace.slug}/packages/${pkg.id}/edit`"
                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                            title="Edit"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                                        </a>
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                            title="Duplicate"
                                            @click="duplicatePackage(pkg.id)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M7 3.5A1.5 1.5 0 018.5 2h3.879a1.5 1.5 0 011.06.44l3.122 3.12A1.5 1.5 0 0117 6.622V12.5a1.5 1.5 0 01-1.5 1.5h-1v-3.379a3 3 0 00-.879-2.121L10.5 5.379A3 3 0 008.379 4.5H7v-1z" /><path d="M4.5 6A1.5 1.5 0 003 7.5v9A1.5 1.5 0 004.5 18h7a1.5 1.5 0 001.5-1.5v-5.879a1.5 1.5 0 00-.44-1.06L9.44 6.439A1.5 1.5 0 008.378 6H4.5z" /></svg>
                                        </button>
                                        <a
                                            v-if="pkg.brochure_url"
                                            :href="pkg.brochure_url"
                                            target="_blank"
                                            rel="noopener"
                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700"
                                            title="Brochure"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5z" /></svg>
                                        </a>
                                        <button
                                            type="button"
                                            class="rounded-lg p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600"
                                            title="Delete"
                                            @click="destroyPackage(pkg.id, pkg.name)"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022 1.005 11.07A2.75 2.75 0 007.77 19.5h4.46a2.75 2.75 0 002.752-2.479l1.005-11.07.149.022a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 01.7.797l-.4 5a.75.75 0 01-1.497-.12l.4-5a.75.75 0 01.797-.677zm3.637.797a.75.75 0 10-1.497-.12l-.4 5a.75.75 0 101.497.12l.4-5z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty State -->
                <div v-if="!filteredPackages.length" class="py-16 text-center">
                    <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-gray-400"><path fill-rule="evenodd" d="M1 8.25a1.25 1.25 0 112.5 0v7.5a1.25 1.25 0 11-2.5 0v-7.5z" clip-rule="evenodd" /></svg>
                    </div>
                    <p class="mt-3 text-sm font-medium text-gray-500" v-if="packages.length">No packages match your search.</p>
                    <p class="mt-3 text-sm font-medium text-gray-500" v-else>No packages yet. Create your first travel product.</p>
                </div>

                <!-- Footer -->
                <div class="border-t border-gray-100 bg-gray-50/50 px-5 py-3">
                    <p class="text-xs text-gray-500">Showing {{ filteredPackages.length }} of {{ packages.length }} packages</p>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
