<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, nextTick, ref, watch } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    customers: {
        type: Object,
        default: () => ({
            data: [],
            current_page: 1,
            last_page: 1,
            total: 0,
            from: 0,
            to: 0,
        }),
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
    availableTags: {
        type: Array,
        default: () => [],
    },
    blastSummary: {
        type: Object,
        default: () => ({
            filtered_total: 0,
            blast_eligible: 0,
            whatsapp_eligible: 0,
            email_eligible: 0,
        }),
    },
});

const showImportModal = ref(false);
const page = usePage();
const importReport = page.props.flash?.importReport ?? null;
const customerSearch = ref(props.filters?.search ?? '');
const customerPassportFilter = ref(props.filters?.passport_filter ?? 'all');
const customerMarketingFilter = ref(props.filters?.marketing_filter ?? 'all');
const customerTagFilter = ref(props.filters?.tag ?? '');
const customerSort = ref(props.filters?.sort ?? 'name_asc');
const customersPerPage = ref(Number(props.filters?.per_page ?? 20));
const isResettingFilters = ref(false);
const blastMessage = ref('Hi! We have travel packages that might match your interests. Reply for details.');
const selectedCustomerIds = ref([]);
const allFilteredSelected = ref(false);
const selectionToken = ref(null);
const selectionCount = ref(0);
const selectionMode = ref(null);
const isSelectionLoading = ref(false);
const undoDeleteToken = ref(null);
const undoDeleteNotice = ref('');
const initialDetailId = (() => {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = new URLSearchParams(window.location.search).get('detail_id');
    const parsed = Number(raw);
    return Number.isInteger(parsed) && parsed > 0 ? parsed : null;
})();

const expandedCustomerId = ref(initialDetailId);
const failureSearch = ref('');
const failurePage = ref(1);
const failuresPerPage = 25;

const importForm = useForm({
    csv_file: null,
});

const destroyCustomer = (customerId, customerName) => {
    if (!window.confirm(`Delete customer "${customerName}"?`)) {
        return;
    }

    router.delete(`/workspace/${props.workspace.slug}/customers/${customerId}`);
};

const handleImportFile = (event) => {
    importForm.csv_file = event.target.files?.[0] ?? null;
};

const submitImport = () => {
    importForm.post(`/workspace/${props.workspace.slug}/customers/import`, {
        forceFormData: true,
        onSuccess: () => {
            showImportModal.value = false;
            importForm.reset();
        },
    });
};

const syncDetailQuery = (customerId) => {
    if (typeof window === 'undefined') {
        return;
    }

    const params = new URLSearchParams(window.location.search);

    if (customerId) {
        params.set('detail_id', String(customerId));
    } else {
        params.delete('detail_id');
    }

    const nextUrl = `${window.location.pathname}${params.toString() ? `?${params.toString()}` : ''}`;
    window.history.replaceState({}, '', nextUrl);
};

let searchDebounceTimer = null;

const applyCustomerQuery = (pageNumber = 1) => {
    router.get(
        `/workspace/${props.workspace.slug}/customers`,
        {
            search: customerSearch.value || undefined,
            passport_filter: customerPassportFilter.value === 'all' ? undefined : customerPassportFilter.value,
            marketing_filter: customerMarketingFilter.value === 'all' ? undefined : customerMarketingFilter.value,
            tag: customerTagFilter.value || undefined,
            sort: customerSort.value,
            per_page: customersPerPage.value,
            page: pageNumber,
            detail_id: expandedCustomerId.value || undefined,
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        }
    );
};

const customerRows = computed(() => props.customers?.data ?? []);

const selectedCustomersOnPage = computed(() => {
    const selectedSet = new Set(selectedCustomerIds.value);
    return customerRows.value.filter((customer) => selectedSet.has(customer.id));
});

const selectAllOnPage = computed({
    get: () => customerRows.value.length > 0 && selectedCustomerIds.value.length === customerRows.value.length,
    set: (value) => {
        if (value) {
            selectedCustomerIds.value = customerRows.value.map((customer) => customer.id);
            return;
        }

        selectedCustomerIds.value = [];
    },
});

const hasAnySelection = computed(() => allFilteredSelected.value || selectedCustomerIds.value.length > 0);

const sanitizePhoneForWhatsApp = (phone) => String(phone ?? '').replace(/\D+/g, '');

const buildWhatsAppLink = (phone, message) => {
    const normalizedPhone = sanitizePhoneForWhatsApp(phone);
    if (!normalizedPhone) {
        return '#';
    }

    return `https://wa.me/${normalizedPhone}?text=${encodeURIComponent(message)}`;
};

const buildMailtoLink = (email, subject = 'TravelRocket Update') => {
    const normalizedEmail = String(email ?? '').trim();
    if (!normalizedEmail) {
        return '#';
    }

    return `mailto:${normalizedEmail}?subject=${encodeURIComponent(subject)}`;
};

const clearSelection = () => {
    selectedCustomerIds.value = [];
    allFilteredSelected.value = false;
    selectionToken.value = null;
    selectionCount.value = 0;
    selectionMode.value = null;
};

const createSelectionToken = async (mode) => {
    isSelectionLoading.value = true;

    try {
        const payload = {
            mode,
            search: customerSearch.value || null,
            passport_filter: customerPassportFilter.value,
            marketing_filter: customerMarketingFilter.value,
            tag: customerTagFilter.value || null,
            customer_ids: mode === 'ids' ? selectedCustomerIds.value : [],
        };

        const { data } = await window.axios.post(`/workspace/${props.workspace.slug}/customers/selection-token`, payload);
        selectionToken.value = data.token;
        selectionCount.value = Number(data.selected_count ?? 0);
        selectionMode.value = data.mode;

        if (mode === 'filtered') {
            allFilteredSelected.value = true;
        }
    } finally {
        isSelectionLoading.value = false;
    }
};

const selectAllFilteredCustomers = async () => {
    await createSelectionToken('filtered');
};

const exportSelectedCustomers = async () => {
    if (!hasAnySelection.value) {
        return;
    }

    const mode = allFilteredSelected.value ? 'filtered' : 'ids';

    if (!selectionToken.value || selectionMode.value !== mode) {
        await createSelectionToken(mode);
    }

    if (!selectionToken.value) {
        return;
    }

    window.location.href = `/workspace/${props.workspace.slug}/customers/export-selected?selection_token=${encodeURIComponent(selectionToken.value)}`;
};

const deleteSelectedCustomers = async () => {
    if (!hasAnySelection.value) {
        return;
    }

    if (!window.confirm('Delete selected customers from database? This action cannot be undone.')) {
        return;
    }

    const mode = allFilteredSelected.value ? 'filtered' : 'ids';

    if (!selectionToken.value || selectionMode.value !== mode) {
        await createSelectionToken(mode);
    }

    if (!selectionToken.value) {
        return;
    }

    isSelectionLoading.value = true;

    try {
        const { data } = await window.axios.delete(`/workspace/${props.workspace.slug}/customers/bulk-delete`, {
            data: {
                selection_token: selectionToken.value,
            },
        });

        undoDeleteToken.value = data.undo_token ?? null;
        undoDeleteNotice.value = `Deleted ${Number(data.deleted_count ?? 0)} customer(s). Undo is available for ${Number(data.undo_ttl_seconds ?? 30)} seconds.`;

        clearSelection();
        applyCustomerQuery(1);
    } finally {
        isSelectionLoading.value = false;
    }
};

const undoBulkDelete = async () => {
    if (!undoDeleteToken.value) {
        return;
    }

    isSelectionLoading.value = true;

    try {
        const { data } = await window.axios.post(`/workspace/${props.workspace.slug}/customers/bulk-delete/undo`, {
            undo_token: undoDeleteToken.value,
        });

        undoDeleteNotice.value = `Restored ${Number(data.restored_count ?? 0)} customer(s).`;
        undoDeleteToken.value = null;
        applyCustomerQuery(1);
    } finally {
        isSelectionLoading.value = false;
    }
};

const toggleCustomerDetails = (customerId) => {
    const nextId = expandedCustomerId.value === customerId ? null : customerId;
    expandedCustomerId.value = nextId;
    syncDetailQuery(expandedCustomerId.value);

    if (nextId) {
        nextTick(() => {
            const row = document.getElementById(`customer-row-${nextId}`);
            row?.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        });
    }
};

const gotoPreviousCustomerPage = () => {
    const current = Number(props.customers?.current_page ?? 1);
    if (current > 1) {
        applyCustomerQuery(current - 1);
    }
};

const gotoNextCustomerPage = () => {
    const current = Number(props.customers?.current_page ?? 1);
    const last = Number(props.customers?.last_page ?? 1);
    if (current < last) {
        applyCustomerQuery(current + 1);
    }
};

watch(customerSearch, () => {
    if (isResettingFilters.value) {
        return;
    }

    if (searchDebounceTimer) {
        clearTimeout(searchDebounceTimer);
    }

    searchDebounceTimer = setTimeout(() => {
        clearSelection();
        applyCustomerQuery(1);
    }, 350);
});

watch([customerPassportFilter, customerMarketingFilter, customerTagFilter, customerSort, customersPerPage], () => {
    if (isResettingFilters.value) {
        return;
    }

    clearSelection();
    applyCustomerQuery(1);
});

watch(customerRows, () => {
    selectedCustomerIds.value = [];
});

const resetFilters = async () => {
    isResettingFilters.value = true;

    try {
        customerSearch.value = '';
        customerPassportFilter.value = 'all';
        customerMarketingFilter.value = 'all';
        customerTagFilter.value = '';
        customerSort.value = 'name_asc';
        customersPerPage.value = 20;
        expandedCustomerId.value = null;
        syncDetailQuery(null);

        clearSelection();
        await nextTick();
        applyCustomerQuery(1);
    } finally {
        isResettingFilters.value = false;
    }
};

const filteredFailures = computed(() => {
    const failures = importReport?.failures ?? [];
    const keyword = failureSearch.value.trim().toLowerCase();

    if (!keyword) {
        return failures;
    }

    return failures.filter((failure) => {
        const haystack = [
            failure.row,
            failure.name,
            failure.passport_number,
            failure.reason,
        ]
            .map((value) => String(value ?? '').toLowerCase())
            .join(' ');

        return haystack.includes(keyword);
    });
});

const totalFailurePages = computed(() => {
    const total = Math.ceil(filteredFailures.value.length / failuresPerPage);
    return total > 0 ? total : 1;
});

const paginatedFailures = computed(() => {
    const safePage = Math.min(failurePage.value, totalFailurePages.value);
    const start = (safePage - 1) * failuresPerPage;
    return filteredFailures.value.slice(start, start + failuresPerPage);
});

const gotoPreviousFailurePage = () => {
    failurePage.value = Math.max(1, failurePage.value - 1);
};

const gotoNextFailurePage = () => {
    failurePage.value = Math.min(totalFailurePages.value, failurePage.value + 1);
};
</script>

<template>
    <Head :title="`${workspace.name} Customers`" />

    <WorkspaceLayout>
        <div class="w-full">
            <section class="tr-bento tr-bento-main">
                <div class="tr-surface rounded-[1.7rem] border border-slate-200 p-7 md:p-8">
                    <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Traveler CRM</p>
                            <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Customers</h2>
                            <p class="mt-2 text-sm text-slate-700">Manage traveler profiles, passport details, and emergency contact records.</p>
                        </div>

                        <div class="flex flex-col gap-2 sm:flex-row">
                            <a
                                :href="`/workspace/${workspace.slug}/customers/blast`"
                                class="tr-btn-secondary rounded-xl px-5 py-3 text-center text-sm font-semibold transition"
                            >
                                Blast Campaigns
                            </a>
                            <button
                                type="button"
                                class="tr-btn-secondary rounded-xl px-5 py-3 text-sm font-semibold transition"
                                @click="showImportModal = true"
                            >
                                Import CSV
                            </button>
                            <a
                                :href="`/workspace/${workspace.slug}/customers/create`"
                                class="tr-btn-primary rounded-xl px-5 py-3 text-center text-sm font-semibold transition"
                            >
                                Add Customer
                            </a>
                        </div>
                    </div>

                    <div class="mt-6 grid gap-3 rounded-[1rem] border border-slate-200 bg-white/70 p-4 md:grid-cols-3">
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Search</label>
                            <input
                                v-model="customerSearch"
                                type="search"
                                placeholder="Name, passport, phone..."
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                            />
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Passport</label>
                            <select
                                v-model="customerPassportFilter"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                            >
                                <option value="all">All records</option>
                                <option value="with_passport_copy">With passport copy</option>
                                <option value="without_passport_copy">Without passport copy</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Marketing</label>
                            <select
                                v-model="customerMarketingFilter"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                            >
                                <option value="all">All consent states</option>
                                <option value="allowed">Marketing allowed</option>
                                <option value="blocked">Marketing blocked</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Tag</label>
                            <select
                                v-model="customerTagFilter"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                            >
                                <option value="">All tags</option>
                                <option v-for="tag in availableTags" :key="tag" :value="tag">{{ tag }}</option>
                            </select>
                        </div>
                        <div>
                            <label class="mb-2 block text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-600">Sort</label>
                            <select
                                v-model="customerSort"
                                class="w-full rounded-xl border border-slate-200 bg-white px-4 py-3 text-sm text-slate-900 shadow-sm transition focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200"
                            >
                                <option value="name_asc">Name A-Z</option>
                                <option value="name_desc">Name Z-A</option>
                                <option value="passport_asc">Passport A-Z</option>
                                <option value="passport_desc">Passport Z-A</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-3 rounded-[1rem] border border-slate-200 bg-white/70 p-4">
                        <div v-if="undoDeleteNotice" class="mb-3 flex flex-wrap items-center gap-2 rounded-xl border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-700">
                            <span>{{ undoDeleteNotice }}</span>
                            <button v-if="undoDeleteToken" type="button" class="rounded-full border border-amber-200 bg-amber-50 px-2.5 py-1 text-[11px] font-semibold text-amber-700" @click="undoBulkDelete">
                                Undo
                            </button>
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            <button type="button" class="tr-btn-secondary rounded-full px-3 py-1 text-[11px] font-semibold" @click="resetFilters">
                                Reset filters
                            </button>
                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white px-3 py-1 text-[11px] text-slate-700">
                                <input v-model="selectAllOnPage" type="checkbox" class="h-4 w-4" />
                                Select all on this page
                            </label>
                            <button
                                v-if="selectedCustomerIds.length === customerRows.length && (props.customers?.total ?? 0) > customerRows.length && !allFilteredSelected"
                                type="button"
                                class="rounded-full border border-emerald-200 bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700"
                                :disabled="isSelectionLoading"
                                @click="selectAllFilteredCustomers"
                            >
                                Select all {{ props.customers?.total ?? 0 }} filtered customers
                            </button>
                            <button
                                type="button"
                                class="tr-btn-secondary rounded-full px-3 py-1 text-[11px] font-semibold"
                                :disabled="!hasAnySelection || isSelectionLoading"
                                @click="exportSelectedCustomers"
                            >
                                Export Selected
                            </button>
                            <button
                                type="button"
                                class="tr-btn-danger rounded-full px-3 py-1 text-[11px] font-semibold"
                                :disabled="!hasAnySelection || isSelectionLoading"
                                @click="deleteSelectedCustomers"
                            >
                                Delete Selected
                            </button>
                            <button
                                type="button"
                                class="tr-btn-secondary rounded-full px-3 py-1 text-[11px] font-semibold"
                                :disabled="!hasAnySelection"
                                @click="clearSelection"
                            >
                                Clear Selection
                            </button>
                        </div>
                        <p class="mt-2 text-xs" :class="allFilteredSelected ? 'text-emerald-700' : 'text-slate-500'">
                            {{ allFilteredSelected ? `All filtered selected (${selectionCount}).` : `Selected on this page: ${selectedCustomersOnPage.length}` }}
                        </p>
                    </div>

                    <div v-if="customerRows.length" class="mt-6 overflow-hidden rounded-[1rem] border border-slate-200 bg-white/70">
                        <div class="max-h-[520px] overflow-auto -mx-px">
                            <table class="min-w-[640px] w-full text-left text-xs">
                                <thead class="sticky top-0 z-10 bg-slate-50 text-[11px] uppercase tracking-[0.14em] text-slate-600">
                                    <tr>
                                        <th class="px-3 py-3 text-center font-semibold">
                                            <input v-model="selectAllOnPage" type="checkbox" class="h-4 w-4" />
                                        </th>
                                        <th class="px-4 py-3 font-semibold">Traveler</th>
                                        <th class="px-4 py-3 font-semibold">Passport</th>
                                        <th class="px-4 py-3 font-semibold">Contact</th>
                                        <th class="px-4 py-3 text-right font-semibold">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <template v-for="customer in customerRows" :key="customer.id">
                                        <tr :id="`customer-row-${customer.id}`" class="border-t border-slate-200 align-top text-slate-800">
                                            <td class="px-3 py-3 text-center">
                                                <input v-model="selectedCustomerIds" :value="customer.id" type="checkbox" class="h-4 w-4" />
                                            </td>
                                            <td class="px-4 py-3">
                                                <p class="font-semibold text-slate-900">{{ customer.name }}</p>
                                                <p class="mt-1 text-[11px] text-slate-500">{{ customer.email || 'No email' }}</p>
                                                <div v-if="customer.tags?.length" class="mt-1 flex flex-wrap gap-1">
                                                    <span v-for="tag in customer.tags" :key="`${customer.id}-${tag}`" class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] text-slate-700">{{ tag }}</span>
                                                </div>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p>{{ customer.passport_number }}</p>
                                                <p class="mt-1 text-[11px] text-slate-500">{{ customer.passport_copy_url ? 'Copy uploaded' : 'No copy' }}</p>
                                                <p class="mt-1 text-[11px]" :class="customer.allow_marketing ? 'text-emerald-700' : 'text-rose-700'">
                                                    {{ customer.allow_marketing ? 'Marketing allowed' : 'Marketing blocked' }}
                                                </p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <p>{{ customer.phone }}</p>
                                                <p class="mt-1 truncate text-[11px] text-slate-500">{{ customer.address }}</p>
                                            </td>
                                            <td class="px-4 py-3">
                                                <div class="flex flex-wrap justify-end gap-1.5">
                                                    <a
                                                        v-if="customer.passport_copy_url"
                                                        :href="customer.passport_copy_url"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100"
                                                    >
                                                        Passport
                                                    </a>
                                                    <a
                                                        :href="buildWhatsAppLink(customer.phone, blastMessage)"
                                                        target="_blank"
                                                        rel="noopener"
                                                        class="rounded-full border border-emerald-200 bg-emerald-50 px-2.5 py-1 text-[11px] font-semibold text-emerald-700 transition hover:border-emerald-300 hover:bg-emerald-100"
                                                        :class="!customer.allow_marketing || !customer.phone ? 'pointer-events-none opacity-40' : ''"
                                                    >
                                                        WhatsApp
                                                    </a>
                                                    <a
                                                        :href="buildMailtoLink(customer.email)"
                                                        class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition-all duration-200 ease-in-out hover:border-slate-300 hover:bg-slate-50"
                                                        :class="!customer.allow_marketing || !customer.email ? 'pointer-events-none opacity-40' : ''"
                                                    >
                                                        Email
                                                    </a>
                                                    <button
                                                        type="button"
                                                        class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                                        @click="toggleCustomerDetails(customer.id)"
                                                    >
                                                        {{ expandedCustomerId === customer.id ? 'Hide' : 'Details' }}
                                                    </button>
                                                    <a
                                                        :href="`/workspace/${workspace.slug}/customers/${customer.id}/edit`"
                                                        class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                                    >
                                                        Edit
                                                    </a>
                                                    <button
                                                        type="button"
                                                        class="rounded-full border border-rose-200 bg-rose-50 px-2.5 py-1 text-[11px] font-semibold text-rose-700 transition hover:border-rose-300 hover:bg-rose-100"
                                                        @click="destroyCustomer(customer.id, customer.name)"
                                                    >
                                                        Delete
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <Transition name="row-expand">
                                            <tr v-if="expandedCustomerId === customer.id" class="border-t border-slate-200 bg-white text-slate-700">
                                                <td colspan="5" class="px-4 py-3">
                                                    <div class="grid gap-3 md:grid-cols-2">
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Traveler Address</p>
                                                            <p class="mt-1 text-xs text-slate-700">{{ customer.address }}</p>
                                                        </div>
                                                        <div>
                                                            <p class="text-[11px] uppercase tracking-[0.14em] text-slate-500">Emergency Contact</p>
                                                            <p class="mt-1 text-xs text-slate-700">{{ customer.emergency_name }} · {{ customer.emergency_relation }}</p>
                                                            <p class="mt-1 text-xs text-slate-500">{{ customer.emergency_phone }}</p>
                                                            <p class="mt-1 text-xs text-slate-700">{{ customer.emergency_address }}</p>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        </Transition>
                                    </template>
                                </tbody>
                            </table>
                        </div>

                        <div class="flex items-center justify-between gap-2 border-t border-slate-200 px-4 py-3">
                            <p class="text-[11px] text-slate-500">
                                Showing {{ props.customers?.from ?? 0 }}-{{ props.customers?.to ?? 0 }} of {{ props.customers?.total ?? 0 }} customers · Page {{ props.customers?.current_page ?? 1 }} / {{ props.customers?.last_page ?? 1 }}
                            </p>
                            <div class="flex items-center gap-2">
                                <select
                                    v-model.number="customersPerPage"
                                    class="rounded-full border border-slate-200 bg-white px-2.5 py-1 text-[11px] font-semibold text-slate-700 outline-none transition hover:border-slate-300"
                                >
                                    <option :value="10">10 / page</option>
                                    <option :value="20">20 / page</option>
                                    <option :value="50">50 / page</option>
                                    <option :value="100">100 / page</option>
                                </select>
                                <button
                                    type="button"
                                    class="tr-btn-secondary rounded-full px-2.5 py-1 text-[11px] font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="(props.customers?.current_page ?? 1) <= 1"
                                    @click="gotoPreviousCustomerPage"
                                >
                                    Previous
                                </button>
                                <button
                                    type="button"
                                    class="tr-btn-secondary rounded-full px-2.5 py-1 text-[11px] font-semibold disabled:cursor-not-allowed disabled:opacity-40"
                                    :disabled="(props.customers?.current_page ?? 1) >= (props.customers?.last_page ?? 1)"
                                    @click="gotoNextCustomerPage"
                                >
                                    Next
                                </button>
                            </div>
                        </div>
                    </div>

                    <div v-else class="mt-6 rounded-[1.7rem] border border-slate-200 bg-white/70 p-8 text-slate-700">
                        No matching records. Try adjusting search/filter or add a new traveler.
                    </div>

                    <div
                        v-if="importReport"
                        class="mt-6 rounded-[1.15rem] border border-slate-200 bg-white/90 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.7)] p-5"
                    >
                        <p class="text-sm font-semibold text-white">Last Import Summary</p>
                        <div class="mt-3 grid gap-2 sm:grid-cols-3">
                            <p class="text-xs text-slate-300">Rows: {{ importReport.total_rows }}</p>
                            <p class="text-xs text-emerald-300">Imported: {{ importReport.imported_count }}</p>
                            <p class="text-xs text-rose-300">Failed: {{ importReport.failed_count }}</p>
                        </div>

                        <a
                            v-if="importReport.failures?.length"
                            :href="`/workspace/${workspace.slug}/customers/import/failures`"
                            class="mt-3 inline-flex rounded-full border border-slate-200 bg-white/90 px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-white/20 hover:bg-white/10"
                        >
                            Download Failure CSV
                        </a>

                        <div v-if="importReport.failures?.length" class="mt-4 max-h-56 overflow-y-auto rounded-xl border border-white/10 bg-[#0b1626] p-3">
                            <p class="mb-2 text-[11px] font-semibold uppercase tracking-[0.14em] text-slate-400">Failure details</p>
                            <input
                                v-model="failureSearch"
                                type="text"
                                placeholder="Search row, name, passport or reason"
                                class="mb-3 w-full rounded-xl border border-slate-200 bg-white/90 px-3 py-2 text-xs text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-white/20"
                                @input="failurePage = 1"
                            />
                            <ul class="space-y-1 text-xs text-slate-300">
                                <li v-for="(failure, index) in paginatedFailures" :key="`failure-${index}`">
                                    Row {{ failure.row ?? '-' }} · {{ failure.name || 'N/A' }} · {{ failure.passport_number || 'N/A' }}: {{ failure.reason }}
                                </li>
                            </ul>

                            <div v-if="filteredFailures.length" class="mt-3 flex items-center justify-between gap-2 border-t border-white/10 pt-3">
                                <p class="text-[11px] text-slate-500">
                                    Showing {{ paginatedFailures.length }} of {{ filteredFailures.length }} failures · Page {{ Math.min(failurePage, totalFailurePages) }} / {{ totalFailurePages }}
                                </p>
                                <div class="flex items-center gap-2">
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-white/20 hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="failurePage <= 1"
                                        @click="gotoPreviousFailurePage"
                                    >
                                        Previous
                                    </button>
                                    <button
                                        type="button"
                                        class="rounded-full border border-slate-200 bg-white/90 px-2.5 py-1 text-[11px] font-semibold text-slate-600 transition hover:border-white/20 hover:bg-white/10 disabled:cursor-not-allowed disabled:opacity-40"
                                        :disabled="failurePage >= totalFailurePages"
                                        @click="gotoNextFailurePage"
                                    >
                                        Next
                                    </button>
                                </div>
                            </div>

                            <p v-if="!filteredFailures.length" class="mt-2 text-xs text-slate-500">No failures match your search.</p>
                        </div>
                    </div>
                </div>

                <aside class="tr-surface space-y-4 rounded-[1.7rem] border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">CRM Summary</p>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Traveler count</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ props.customers?.total ?? 0 }}</p>
                        <p class="mt-2 text-xs text-slate-500">Showing {{ props.customers?.from ?? 0 }}-{{ props.customers?.to ?? 0 }} this page</p>
                    </div>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Blast eligible</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ blastSummary.blast_eligible ?? 0 }}</p>
                        <p class="mt-2 text-xs text-slate-500">WhatsApp: {{ blastSummary.whatsapp_eligible ?? 0 }} · Email: {{ blastSummary.email_eligible ?? 0 }}</p>
                    </div>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Import support</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">Use CSV import for fast onboarding. Always follow the sample structure.</p>
                    </div>
                </aside>
            </section>
        </div>

        <div
            v-if="showImportModal"
            class="fixed inset-0 z-50 flex items-center justify-center bg-[#020712]/80 px-4"
        >
            <div class="w-full max-w-xl rounded-[1.15rem] border border-white/10 bg-[#0b1626] p-6 shadow-[0_30px_80px_rgba(0,0,0,0.45)]">
                <div class="flex items-start justify-between gap-3">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.16em] text-brand-300">Bulk Import</p>
                        <h3 class="mt-2 text-xl font-semibold text-white">Import Travelers from CSV</h3>
                        <p class="mt-2 text-sm text-slate-400">Upload a CSV file and records will be inserted under this tenant only.</p>
                    </div>
                    <button
                        type="button"
                        class="rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-xs text-slate-600 transition hover:bg-white/10"
                        @click="showImportModal = false"
                    >
                        Close
                    </button>
                </div>

                <div class="mt-5 rounded-xl border border-slate-200 bg-white/90 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.7)] p-4">
                    <p class="text-sm text-slate-300">Need template?</p>
                    <a
                        :href="`/workspace/${workspace.slug}/customers/import/sample`"
                        class="mt-2 inline-flex text-sm font-semibold text-brand-300 underline"
                    >
                        Download sample CSV structure
                    </a>
                    <p class="mt-3 text-xs leading-5 text-slate-400">
                        Required columns:
                        <span class="text-slate-200">name, passport_number, address, phone, emergency_name, emergency_phone, emergency_relation, emergency_address</span>.
                        Optional columns: <span class="text-slate-200">email, tags (pipe-separated), allow_marketing (1/0)</span>.
                    </p>
                </div>

                <form class="mt-5" @submit.prevent="submitImport">
                    <label class="mb-2 block text-sm font-medium text-slate-200">CSV file</label>
                    <input
                        type="file"
                        accept=".csv"
                        class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-brand-500 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-slate-800"
                        @change="handleImportFile"
                    />
                    <p v-if="importForm.errors.csv_file" class="mt-2 text-sm text-rose-300">{{ importForm.errors.csv_file }}</p>

                    <div class="mt-6 flex justify-end gap-2">
                        <button
                            type="button"
                            class="rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-white/20 hover:bg-white/10"
                            @click="showImportModal = false"
                        >
                            Cancel
                        </button>
                        <button
                            type="submit"
                            class="rounded-full bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-300 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="importForm.processing"
                        >
                            Import CSV
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </WorkspaceLayout>
</template>

<style scoped>
.row-expand-enter-active,
.row-expand-leave-active {
    transition: opacity 0.18s ease, transform 0.18s ease;
}

.row-expand-enter-from,
.row-expand-leave-to {
    opacity: 0;
    transform: translateY(-4px);
}
</style>
