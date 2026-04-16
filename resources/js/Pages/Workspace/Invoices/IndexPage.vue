<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        default: () => ({ name: 'Workspace', slug: 'demo' }),
    },
    invoices: {
        type: Array,
        default: () => [],
    },
});

// --- Search & Filter ---
const search = ref('');
const statusFilter = ref('');
const dateFrom = ref('');
const dateTo = ref('');
const sortKey = ref('created_at');
const sortDir = ref('desc');
const searchInput = ref(null);

const statuses = computed(() => {
    const set = new Set(props.invoices.map((i) => i.status));
    return [...set].sort();
});

// --- Pagination ---
const currentPage = ref(1);
const perPage = ref(10);
const perPageOptions = [10, 25, 50, 100];

const filteredBeforePagination = computed(() => {
    let list = [...(props.invoices || [])];

    if (search.value) {
        const q = search.value.toLowerCase();
        list = list.filter(
            (item) =>
                (item.public_id || '').toLowerCase().includes(q) ||
                (item.customer?.name || item.manual_customer_data?.name || '').toLowerCase().includes(q) ||
                (item.subject || '').toLowerCase().includes(q),
        );
    }

    if (statusFilter.value) {
        list = list.filter((item) => item.status === statusFilter.value);
    }

    // Date range filter
    if (dateFrom.value) {
        const from = new Date(dateFrom.value);
        list = list.filter((item) => new Date(item.created_at) >= from);
    }
    if (dateTo.value) {
        const to = new Date(dateTo.value);
        to.setHours(23, 59, 59, 999);
        list = list.filter((item) => new Date(item.created_at) <= to);
    }

    list.sort((a, b) => {
        let aVal = a[sortKey.value];
        let bVal = b[sortKey.value];
        if (['total', 'paid_amount'].includes(sortKey.value)) {
            aVal = Number(aVal || 0);
            bVal = Number(bVal || 0);
        } else if (sortKey.value === 'customer') {
            aVal = (a.customer?.name || a.manual_customer_data?.name || '').toLowerCase();
            bVal = (b.customer?.name || b.manual_customer_data?.name || '').toLowerCase();
        } else {
            aVal = String(aVal || '').toLowerCase();
            bVal = String(bVal || '').toLowerCase();
        }
        if (aVal < bVal) return sortDir.value === 'asc' ? -1 : 1;
        if (aVal > bVal) return sortDir.value === 'asc' ? 1 : -1;
        return 0;
    });

    return list;
});

const totalPages = computed(() => Math.max(1, Math.ceil(filteredBeforePagination.value.length / perPage.value)));

const filteredInvoices = computed(() => {
    const start = (currentPage.value - 1) * perPage.value;
    return filteredBeforePagination.value.slice(start, start + perPage.value);
});

const resetPage = () => { currentPage.value = 1; };

const toggleSort = (key) => {
    if (sortKey.value === key) {
        sortDir.value = sortDir.value === 'asc' ? 'desc' : 'asc';
    } else {
        sortKey.value = key;
        sortDir.value = 'asc';
    }
    resetPage();
};

// --- Checkbox ---
const selectedIds = ref(new Set());
const selectAll = computed({
    get: () => filteredInvoices.value.length > 0 && filteredInvoices.value.every((i) => selectedIds.value.has(i.id)),
    set: (val) => {
        if (val) {
            filteredInvoices.value.forEach((i) => selectedIds.value.add(i.id));
        } else {
            selectedIds.value.clear();
        }
    },
});
const toggleSelect = (id) => {
    if (selectedIds.value.has(id)) {
        selectedIds.value.delete(id);
    } else {
        selectedIds.value.add(id);
    }
};
const selectedCount = computed(() => selectedIds.value.size);

// --- Bulk Actions ---
const bulkDeleting = ref(false);
const bulkDelete = () => {
    if (!confirm(`Delete ${selectedCount.value} invoice(s)? This cannot be undone.`)) return;
    bulkDeleting.value = true;
    router.delete(route('invoices.bulk-delete', { tenant: props.workspace.slug }), {
        data: { ids: [...selectedIds.value] },
        onFinish: () => {
            bulkDeleting.value = false;
            selectedIds.value.clear();
        },
    });
};

const exportCsv = (onlySelected = false) => {
    let url = route('invoices.export-csv', { tenant: props.workspace.slug });
    if (onlySelected && selectedCount.value > 0) {
        url += '?ids=' + [...selectedIds.value].join(',');
    }
    window.open(url, '_blank');
};

// --- Keyboard Shortcuts ---
const handleKeyboard = (e) => {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.value?.focus();
    }
    if (e.key === 'Escape') {
        if (document.activeElement === searchInput.value) {
            search.value = '';
            searchInput.value?.blur();
        }
    }
};

onMounted(() => {
    document.addEventListener('keydown', handleKeyboard);
});
onUnmounted(() => {
    document.removeEventListener('keydown', handleKeyboard);
});

// --- Helpers ---
const statusColors = {
    Draft: 'bg-gray-50 text-gray-600 ring-gray-500/20',
    Sent: 'bg-blue-50 text-blue-700 ring-blue-600/20',
    Unpaid: 'bg-orange-50 text-orange-700 ring-orange-600/20',
    'Partially Paid': 'bg-amber-50 text-amber-700 ring-amber-600/20',
    'Fully Paid': 'bg-emerald-50 text-emerald-700 ring-emerald-600/20',
    Overdue: 'bg-red-50 text-red-700 ring-red-600/20',
    Void: 'bg-violet-50 text-violet-600 ring-violet-500/20',
    Cancelled: 'bg-rose-50 text-rose-700 ring-rose-600/20',
    Refunded: 'bg-pink-50 text-pink-700 ring-pink-600/20',
};
const getStatusColor = (status) => statusColors[status] || 'bg-gray-50 text-gray-600 ring-gray-500/20';
const paidPercent = (inv) => {
    const total = Number(inv.total || 0);
    const paid = Number(inv.paid_amount || 0);
    if (total <= 0) return 0;
    return Math.min(100, Math.round((paid / total) * 100));
};
const formatDate = (date) => {
    if (!date) return 'N/A';
    return new Date(date).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', year: 'numeric' });
};
const formatCurrency = (amount) => 'RM ' + Number(amount || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 });

// --- Summary ---
const totalAmount = computed(() => filteredBeforePagination.value.reduce((sum, i) => sum + Number(i.total || 0), 0));
const totalPaid = computed(() => filteredBeforePagination.value.reduce((sum, i) => sum + Number(i.paid_amount || 0), 0));
const overdueCount = computed(() => props.invoices.filter((i) => i.status === 'Overdue').length);

const hasActiveFilters = computed(() => search.value || statusFilter.value || dateFrom.value || dateTo.value);
const clearFilters = () => {
    search.value = '';
    statusFilter.value = '';
    dateFrom.value = '';
    dateTo.value = '';
    resetPage();
};
</script>

<template>
    <Head title="Invoices" />

    <WorkspaceLayout>
        <div class="w-full py-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-blue-600 text-white text-xs font-bold shadow-sm">I</span>
                        Invoices
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Revenue tracking &amp; payment management</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="exportCsv(false)"
                        class="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-700 shadow-sm transition hover:bg-gray-50"
                        title="Export all to CSV"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 2.75a.75.75 0 0 0-1.5 0v8.614L6.295 8.235a.75.75 0 1 0-1.09 1.03l4.25 4.5a.75.75 0 0 0 1.09 0l4.25-4.5a.75.75 0 0 0-1.09-1.03l-2.955 3.129V2.75Z" /><path d="M3.5 12.75a.75.75 0 0 0-1.5 0v2.5A2.75 2.75 0 0 0 4.75 18h10.5A2.75 2.75 0 0 0 18 15.25v-2.5a.75.75 0 0 0-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5Z" /></svg>
                        CSV
                    </button>
                    <Link
                        v-if="workspace?.slug"
                        :href="route('invoices.create', { tenant: workspace.slug })"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-600"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 0 0-1.5 0v4.5h-4.5a.75.75 0 0 0 0 1.5h4.5v4.5a.75.75 0 0 0 1.5 0v-4.5h4.5a.75.75 0 0 0 0-1.5h-4.5v-4.5Z" /></svg>
                        New Invoice
                    </Link>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Total</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-gray-900">{{ filteredBeforePagination.length }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Invoiced</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-gray-900">{{ formatCurrency(totalAmount) }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Collected</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-green-600">{{ formatCurrency(totalPaid) }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Overdue</p>
                    <p class="mt-1 text-lg font-bold tabular-nums" :class="overdueCount > 0 ? 'text-red-600' : 'text-gray-900'">{{ overdueCount }}</p>
                </div>
            </div>

            <!-- Table Card -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">

                <!-- Toolbar -->
                <div class="flex flex-col gap-3 border-b border-gray-100 p-4">
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex flex-1 flex-wrap items-center gap-3">
                            <div class="relative flex-1 min-w-[200px] max-w-sm">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 1 0 0 11 5.5 5.5 0 0 0 0-11ZM2 9a7 7 0 1 1 12.452 4.391l3.328 3.329a.75.75 0 1 1-1.06 1.06l-3.329-3.328A7 7 0 0 1 2 9Z" clip-rule="evenodd" /></svg>
                                <input
                                    ref="searchInput"
                                    v-model="search"
                                    @input="resetPage"
                                    type="text"
                                    placeholder="Search... (⌘K)"
                                    class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-9 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500 transition"
                                />
                            </div>
                            <select
                                v-model="statusFilter"
                                @change="resetPage"
                                class="rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500"
                            >
                                <option value="">All Status</option>
                                <option v-for="s in statuses" :key="s" :value="s">{{ s }}</option>
                            </select>

                            <!-- Date Range -->
                            <div class="flex items-center gap-1.5">
                                <input
                                    v-model="dateFrom"
                                    @change="resetPage"
                                    type="date"
                                    class="rounded-lg border-0 bg-gray-50 py-2 px-2.5 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500"
                                    title="From date"
                                />
                                <span class="text-xs text-gray-400">to</span>
                                <input
                                    v-model="dateTo"
                                    @change="resetPage"
                                    type="date"
                                    class="rounded-lg border-0 bg-gray-50 py-2 px-2.5 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500"
                                    title="To date"
                                />
                            </div>

                            <button
                                v-if="hasActiveFilters"
                                @click="clearFilters"
                                class="inline-flex items-center gap-1 rounded-md px-2 py-1.5 text-xs font-medium text-gray-500 hover:bg-gray-100 hover:text-gray-700 transition"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M5.28 4.22a.75.75 0 0 0-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 1 0 1.06 1.06L8 9.06l2.72 2.72a.75.75 0 1 0 1.06-1.06L9.06 8l2.72-2.72a.75.75 0 0 0-1.06-1.06L8 6.94 5.28 4.22Z" /></svg>
                                Clear
                            </button>
                        </div>

                        <div class="flex items-center gap-2 text-xs text-gray-500">
                            <span class="tabular-nums">{{ filteredBeforePagination.length }} result{{ filteredBeforePagination.length !== 1 ? 's' : '' }}</span>
                        </div>
                    </div>

                    <!-- Bulk action bar -->
                    <div v-if="selectedCount > 0" class="flex items-center gap-3 rounded-lg bg-blue-50 px-4 py-2.5 ring-1 ring-inset ring-blue-200">
                        <span class="text-sm font-medium text-blue-800">{{ selectedCount }} selected</span>
                        <div class="h-4 w-px bg-blue-200"></div>
                        <button
                            @click="exportCsv(true)"
                            class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-blue-700 hover:bg-blue-100 transition"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5"><path d="M8.75 2.75a.75.75 0 0 0-1.5 0v5.69L5.03 6.22a.75.75 0 0 0-1.06 1.06l3.5 3.5a.75.75 0 0 0 1.06 0l3.5-3.5a.75.75 0 0 0-1.06-1.06L8.75 8.44V2.75Z" /><path d="M3.5 9.75a.75.75 0 0 0-1.5 0v1.5A2.75 2.75 0 0 0 4.75 14h6.5A2.75 2.75 0 0 0 14 11.25v-1.5a.75.75 0 0 0-1.5 0v1.5c0 .69-.56 1.25-1.25 1.25h-6.5c-.69 0-1.25-.56-1.25-1.25v-1.5Z" /></svg>
                            Export Selected
                        </button>
                        <button
                            @click="bulkDelete"
                            :disabled="bulkDeleting"
                            class="inline-flex items-center gap-1 rounded-md px-2.5 py-1 text-xs font-medium text-red-600 hover:bg-red-50 transition disabled:opacity-50"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5"><path fill-rule="evenodd" d="M5 3.25V4H2.75a.75.75 0 0 0 0 1.5h.3l.815 8.15A1.5 1.5 0 0 0 5.357 15h5.285a1.5 1.5 0 0 0 1.493-1.35l.815-8.15h.3a.75.75 0 0 0 0-1.5H11v-.75A2.25 2.25 0 0 0 8.75 1h-1.5A2.25 2.25 0 0 0 5 3.25Zm2.25-.75a.75.75 0 0 0-.75.75V4h3v-.75a.75.75 0 0 0-.75-.75h-1.5ZM6.05 6a.75.75 0 0 1 .787.713l.275 5.5a.75.75 0 0 1-1.498.075l-.275-5.5A.75.75 0 0 1 6.05 6Zm3.9 0a.75.75 0 0 1 .712.787l-.275 5.5a.75.75 0 0 1-1.498-.075l.275-5.5a.75.75 0 0 1 .786-.711Z" clip-rule="evenodd" /></svg>
                            Delete
                        </button>
                        <button
                            @click="selectedIds.clear()"
                            class="ml-auto text-xs text-blue-600 hover:text-blue-800 transition"
                        >
                            Deselect all
                        </button>
                    </div>
                </div>

                <!-- Table -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/60">
                                <th class="w-10 py-3.5 px-4">
                                    <input type="checkbox" v-model="selectAll" class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                </th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none hover:text-gray-900 transition" @click="toggleSort('public_id')">
                                    <span class="inline-flex items-center gap-1">Invoice <span v-if="sortKey === 'public_id'" class="text-blue-500">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                                </th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 cursor-pointer select-none hover:text-gray-900 transition" @click="toggleSort('customer')">
                                    <span class="inline-flex items-center gap-1">Customer <span v-if="sortKey === 'customer'" class="text-blue-500">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                                </th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500">Payment</th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right cursor-pointer select-none hover:text-gray-900 transition" @click="toggleSort('total')">
                                    <span class="inline-flex items-center gap-1 justify-end">Total <span v-if="sortKey === 'total'" class="text-blue-500">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                                </th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-center cursor-pointer select-none hover:text-gray-900 transition" @click="toggleSort('status')">
                                    <span class="inline-flex items-center gap-1">Status <span v-if="sortKey === 'status'" class="text-blue-500">{{ sortDir === 'asc' ? '↑' : '↓' }}</span></span>
                                </th>
                                <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="inv in filteredInvoices"
                                :key="inv.id"
                                class="group border-b border-gray-100 transition-colors"
                                :class="selectedIds.has(inv.id) ? 'bg-blue-50/60' : 'hover:bg-gray-50/80'"
                            >
                                <td class="w-10 py-4 px-4">
                                    <input type="checkbox" :checked="selectedIds.has(inv.id)" @change="toggleSelect(inv.id)" class="h-3.5 w-3.5 rounded border-gray-300 text-blue-600 focus:ring-blue-500" />
                                </td>
                                <td class="whitespace-nowrap py-4 px-4">
                                    <div class="flex items-center gap-2">
                                        <Link v-if="workspace?.slug" :href="route('invoices.show', { tenant: workspace.slug, invoice: inv.id })" class="font-semibold text-gray-900 hover:text-blue-600 transition-colors">
                                            {{ inv.public_id }}
                                        </Link>
                                        <span v-if="inv.quote_id" class="inline-flex items-center rounded bg-gray-100 px-1.5 py-0.5 text-[10px] font-medium text-gray-500">
                                            from quote
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ formatDate(inv.created_at) }}</p>
                                </td>
                                <td class="whitespace-nowrap py-4 px-4">
                                    <p class="font-medium text-gray-900">{{ inv.customer?.name || inv.manual_customer_data?.name || 'Walk-in' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-400">{{ inv.customer?.email || inv.manual_customer_data?.email || '—' }}</p>
                                </td>
                                <td class="py-4 px-4 min-w-[160px]">
                                    <div class="flex items-center gap-2.5">
                                        <div class="flex-1">
                                            <div class="h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                                <div
                                                    class="h-full rounded-full transition-all duration-700"
                                                    :class="paidPercent(inv) >= 100 ? 'bg-green-500' : paidPercent(inv) > 0 ? 'bg-amber-400' : 'bg-gray-200'"
                                                    :style="{ width: paidPercent(inv) + '%' }"
                                                ></div>
                                            </div>
                                        </div>
                                        <span class="text-xs font-medium tabular-nums" :class="paidPercent(inv) >= 100 ? 'text-green-600' : 'text-gray-500'">{{ paidPercent(inv) }}%</span>
                                    </div>
                                    <p class="mt-1 text-xs text-gray-400">{{ formatCurrency(inv.paid_amount) }} of {{ formatCurrency(inv.total) }}</p>
                                </td>
                                <td class="whitespace-nowrap py-4 px-4 text-right font-semibold tabular-nums text-gray-900">
                                    {{ formatCurrency(inv.total) }}
                                </td>
                                <td class="whitespace-nowrap py-4 px-4 text-center">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset" :class="getStatusColor(inv.status)">
                                        {{ inv.status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap py-4 px-4">
                                    <div class="flex items-center justify-end gap-1">
                                        <Link v-if="workspace?.slug" :href="route('invoices.show', { tenant: workspace.slug, invoice: inv.id })" class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="View">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 12.5a2.5 2.5 0 1 0 0-5 2.5 2.5 0 0 0 0 5Z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 0 1 0-1.186A10.004 10.004 0 0 1 10 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0 1 10 17c-4.257 0-7.893-2.66-9.336-6.41ZM14 10a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z" clip-rule="evenodd" /></svg>
                                        </Link>
                                        <Link
                                            v-if="workspace?.slug && inv.status !== 'Fully Paid' && inv.status !== 'Cancelled' && inv.status !== 'Void'"
                                            :href="route('invoices.show', { tenant: workspace.slug, invoice: inv.id })"
                                            class="rounded-md p-1.5 text-gray-400 transition hover:bg-blue-50 hover:text-blue-600"
                                            title="Record Payment"
                                        >
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M1 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4Zm12 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm13-1a1 1 0 1 1-2 0 1 1 0 0 1 2 0ZM1.75 14.5a.75.75 0 0 0 0 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 0 0-1.5 0v.784a.272.272 0 0 1-.35.25A49.043 49.043 0 0 0 1.75 14.5Z" clip-rule="evenodd" /></svg>
                                        </Link>
                                    </div>
                                </td>
                            </tr>

                            <!-- Empty State -->
                            <tr v-if="filteredInvoices.length === 0">
                                <td colspan="7" class="py-16 text-center">
                                    <div class="mx-auto max-w-xs space-y-3">
                                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-gray-400"><path fill-rule="evenodd" d="M1 4a1 1 0 0 1 1-1h16a1 1 0 0 1 1 1v8a1 1 0 0 1-1 1H2a1 1 0 0 1-1-1V4Zm12 4a3 3 0 1 1-6 0 3 3 0 0 1 6 0ZM4 9a1 1 0 1 0 0-2 1 1 0 0 0 0 2Zm13-1a1 1 0 1 1-2 0 1 1 0 0 1 2 0Z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <p class="text-sm font-medium text-gray-500" v-if="hasActiveFilters">No matching invoices</p>
                                        <p class="text-sm font-medium text-gray-500" v-else>No invoices yet</p>
                                        <p class="text-xs text-gray-400" v-if="hasActiveFilters">Try adjusting your search or filter.</p>
                                        <p class="text-xs text-gray-400" v-else>Create your first invoice to start tracking revenue.</p>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="totalPages > 1 || filteredBeforePagination.length > 10" class="flex flex-col gap-3 border-t border-gray-100 px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-2 text-xs text-gray-500">
                        <span>Show</span>
                        <select
                            v-model.number="perPage"
                            @change="resetPage"
                            class="rounded-md border-0 bg-gray-50 py-1 pl-2 pr-6 text-xs ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500"
                        >
                            <option v-for="n in perPageOptions" :key="n" :value="n">{{ n }}</option>
                        </select>
                        <span>per page</span>
                    </div>
                    <div class="flex items-center gap-1">
                        <button
                            :disabled="currentPage <= 1"
                            @click="currentPage--"
                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        >
                            ← Prev
                        </button>
                        <template v-for="page in totalPages" :key="page">
                            <button
                                v-if="page === 1 || page === totalPages || (page >= currentPage - 1 && page <= currentPage + 1)"
                                @click="currentPage = page"
                                class="min-w-[32px] rounded-md px-2.5 py-1.5 text-xs font-medium transition"
                                :class="currentPage === page ? 'bg-blue-600 text-white' : 'text-gray-600 hover:bg-gray-100'"
                            >
                                {{ page }}
                            </button>
                            <span v-else-if="page === currentPage - 2 || page === currentPage + 2" class="px-1 text-xs text-gray-400">...</span>
                        </template>
                        <button
                            :disabled="currentPage >= totalPages"
                            @click="currentPage++"
                            class="rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
                        >
                            Next →
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
