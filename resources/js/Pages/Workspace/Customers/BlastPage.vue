<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
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
    templates: {
        type: Array,
        default: () => [],
    },
    blastLogs: {
        type: Array,
        default: () => [],
    },
    draftMessage: {
        type: String,
        default: '',
    },
});

const customerSearch = ref(props.filters?.search ?? '');
const customerPassportFilter = ref(props.filters?.passport_filter ?? 'all');
const customerMarketingFilter = ref(props.filters?.marketing_filter ?? 'allowed');
const customerTagFilter = ref(props.filters?.tag ?? '');
const customersPerPage = ref(Number(props.filters?.per_page ?? 20));
const blastMessage = ref(props.draftMessage ?? '');
const blastChannel = ref('whatsapp');
const exportFormat = ref('crm_full');

const selectedCustomerIds = ref([]);
const allFilteredSelected = ref(false);
const selectionToken = ref(null);
const selectionCount = ref(0);
const selectionMode = ref(null);
const isCreatingToken = ref(false);
const draftStatus = ref('saved');
const saveDraftError = ref('');
const logs = ref([...(props.blastLogs ?? [])]);
const logsChannelFilter = ref('all');
const logsDateFrom = ref('');
const logsDateTo = ref('');
const isLogsLoading = ref(false);

const editingTemplateId = ref(null);
const activeTab = ref('compose');

const templateForm = useForm({
    name: '',
    body: '',
});

const templateEditForm = useForm({
    name: '',
    body: '',
});

const customerRows = computed(() => props.customers?.data ?? []);

const sanitizePhoneForWhatsApp = (phone) => String(phone ?? '').replace(/\D+/g, '');

const normalizePhoneForWhatsApp = (phone) => {
    const raw = sanitizePhoneForWhatsApp(phone);
    if (!raw) return '';
    if (raw.startsWith('0')) return `62${raw.slice(1)}`;
    if (raw.startsWith('8')) return `62${raw}`;
    return raw;
};

const buildWhatsAppLink = (phone, message) => {
    const normalized = normalizePhoneForWhatsApp(phone);
    if (!normalized) return '';
    return `https://wa.me/${normalized}?text=${encodeURIComponent(message || '')}`;
};

const selectAllOnPage = computed({
    get: () => customerRows.value.length > 0 && selectedCustomerIds.value.length === customerRows.value.length,
    set: (value) => {
        selectedCustomerIds.value = value ? customerRows.value.map((c) => c.id) : [];
    },
});

const hasAnySelection = computed(() => allFilteredSelected.value || selectedCustomerIds.value.length > 0);

const selectedRowsOnPage = computed(() => {
    const selectedSet = new Set(selectedCustomerIds.value);
    return customerRows.value.filter((row) => selectedSet.has(row.id));
});

const selectedStats = computed(() => {
    const rows = selectedRowsOnPage.value;
    return {
        total: rows.length,
        waReady: rows.filter((row) => row.allow_marketing && normalizePhoneForWhatsApp(row.phone)).length,
        emailReady: rows.filter((row) => row.allow_marketing && row.email).length,
    };
});

const canRunBlast = computed(() => {
    if (selectedStats.value.total === 0) return false;
    if (blastChannel.value === 'email') return selectedStats.value.emailReady > 0;
    return selectedStats.value.waReady > 0;
});

const blastActionLabel = computed(() => (blastChannel.value === 'email' ? 'Send Email Blast' : 'Start WhatsApp Blast'));

const blastActionHelper = computed(() => {
    if (blastChannel.value === 'email') return 'Opens email composer with eligible recipients in BCC.';
    return 'Opens WhatsApp for the first recipient and copies remaining links.';
});

const blastReadinessText = computed(() => {
    if (blastChannel.value === 'email') return `${selectedStats.value.emailReady} email recipients ready`;
    return `${selectedStats.value.waReady} WhatsApp numbers ready`;
});

const blastFinalActionLabel = computed(() => (blastChannel.value === 'email' ? 'Confirm & Open Email Draft' : 'Confirm & Start WhatsApp Blast'));

let searchDebounceTimer = null;
let draftDebounceTimer = null;
let logsRefreshTimer = null;

const applyQuery = (pageNumber = 1) => {
    router.get(
        `/workspace/${props.workspace.slug}/customers/blast`,
        {
            search: customerSearch.value || undefined,
            passport_filter: customerPassportFilter.value === 'all' ? undefined : customerPassportFilter.value,
            marketing_filter: customerMarketingFilter.value === 'all' ? undefined : customerMarketingFilter.value,
            tag: customerTagFilter.value || undefined,
            per_page: customersPerPage.value,
            page: pageNumber,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
};

const clearSelection = () => {
    selectedCustomerIds.value = [];
    allFilteredSelected.value = false;
    selectionToken.value = null;
    selectionCount.value = 0;
    selectionMode.value = null;
};

const createSelectionToken = async (mode) => {
    isCreatingToken.value = true;
    try {
        const payload = {
            mode,
            search: customerSearch.value || null,
            passport_filter: customerPassportFilter.value,
            marketing_filter: customerMarketingFilter.value,
            tag: customerTagFilter.value || null,
            customer_ids: mode === 'ids' ? selectedCustomerIds.value : [],
        };
        const { data } = await window.axios.post(`/workspace/${props.workspace.slug}/customers/blast/selection-token`, payload);
        selectionToken.value = data.token;
        selectionCount.value = Number(data.selected_count ?? 0);
        selectionMode.value = data.mode;
        if (mode === 'filtered') allFilteredSelected.value = true;
    } finally {
        isCreatingToken.value = false;
    }
};

const selectAllFilteredAudience = async () => {
    await createSelectionToken('filtered');
};

const exportSelected = async () => {
    if (!hasAnySelection.value) return;
    const mode = allFilteredSelected.value ? 'filtered' : 'ids';
    if (!selectionToken.value || selectionMode.value !== mode) await createSelectionToken(mode);
    if (!selectionToken.value) return;
    const query = new URLSearchParams({
        selection_token: String(selectionToken.value),
        message: blastMessage.value || '',
        format: exportFormat.value,
    });
    window.location.href = `/workspace/${props.workspace.slug}/customers/blast/export-selected?${query.toString()}`;
};

const copyText = async (text) => {
    if (!text) return;
    await navigator.clipboard.writeText(text);
};

const copySelectedNormalizedPhones = async () => {
    const lines = selectedRowsOnPage.value
        .filter((row) => row.allow_marketing)
        .map((row) => normalizePhoneForWhatsApp(row.phone))
        .filter(Boolean);
    await copyText(lines.join('\n'));
};

const copySelectedWhatsAppLinks = async () => {
    const links = selectedRowsOnPage.value
        .filter((row) => row.allow_marketing)
        .map((row) => buildWhatsAppLink(row.phone, blastMessage.value))
        .filter(Boolean);
    await copyText(links.join('\n'));
};

const saveDraftNow = async () => {
    draftStatus.value = 'saving';
    saveDraftError.value = '';
    try {
        await window.axios.post(`/workspace/${props.workspace.slug}/customers/blast/draft`, {
            draft_message: blastMessage.value || null,
        });
        draftStatus.value = 'saved';
    } catch (error) {
        draftStatus.value = 'error';
        saveDraftError.value = error?.response?.data?.message ?? 'Failed to save draft.';
    }
};

const runBlast = async () => {
    if (!canRunBlast.value) return;
    if (!selectionToken.value || selectionMode.value !== 'ids') await createSelectionToken('ids');
    if (!selectionToken.value) return;

    const { data } = await window.axios.post(`/workspace/${props.workspace.slug}/customers/blast/log`, {
        selection_token: selectionToken.value,
        channel: blastChannel.value,
        message: blastMessage.value || null,
    });

    const freshLog = data?.log ?? null;
    if (freshLog) {
        logs.value = [freshLog, ...logs.value.filter((item) => item.id !== freshLog.id)].slice(0, 100);
    }

    if (blastChannel.value === 'email') {
        const emails = selectedRowsOnPage.value
            .filter((row) => row.allow_marketing && row.email)
            .map((row) => String(row.email).trim())
            .filter(Boolean)
            .join(',');
        if (!emails) return;
        const params = new URLSearchParams({ bcc: emails, subject: 'TravelRocket Update', body: blastMessage.value || '' });
        window.location.href = `mailto:?${params.toString()}`;
        return;
    }

    const links = selectedRowsOnPage.value
        .filter((row) => row.allow_marketing)
        .map((row) => buildWhatsAppLink(row.phone, blastMessage.value))
        .filter(Boolean);
    if (!links.length) return;
    window.open(links[0], '_blank', 'noopener');
    if (links.length > 1) await copyText(links.slice(1).join('\n'));
};

const fetchLogs = async () => {
    isLogsLoading.value = true;
    try {
        const { data } = await window.axios.get(`/workspace/${props.workspace.slug}/customers/blast/logs`, {
            params: {
                channel: logsChannelFilter.value,
                date_from: logsDateFrom.value || null,
                date_to: logsDateTo.value || null,
                limit: 100,
            },
        });
        logs.value = Array.isArray(data?.logs) ? data.logs : [];
    } finally {
        isLogsLoading.value = false;
    }
};

const applyTemplate = (template) => {
    blastMessage.value = template.body;
};

const saveTemplate = () => {
    templateForm.post(`/workspace/${props.workspace.slug}/customers/blast/templates`, {
        preserveScroll: true,
        onSuccess: () => templateForm.reset(),
    });
};

const startEditTemplate = (template) => {
    editingTemplateId.value = template.id;
    templateEditForm.name = template.name;
    templateEditForm.body = template.body;
};

const cancelEditTemplate = () => {
    editingTemplateId.value = null;
    templateEditForm.reset();
};

const updateTemplate = (templateId) => {
    templateEditForm.put(`/workspace/${props.workspace.slug}/customers/blast/templates/${templateId}`, {
        preserveScroll: true,
        onSuccess: () => cancelEditTemplate(),
    });
};

const deleteTemplate = (templateId, templateName) => {
    if (!window.confirm(`Delete template "${templateName}"?`)) return;
    router.delete(`/workspace/${props.workspace.slug}/customers/blast/templates/${templateId}`, { preserveScroll: true });
};

const gotoPreviousPage = () => {
    const current = Number(props.customers?.current_page ?? 1);
    if (current > 1) applyQuery(current - 1);
};

const gotoNextPage = () => {
    const current = Number(props.customers?.current_page ?? 1);
    const last = Number(props.customers?.last_page ?? 1);
    if (current < last) applyQuery(current + 1);
};

watch(customerSearch, () => {
    if (searchDebounceTimer) clearTimeout(searchDebounceTimer);
    searchDebounceTimer = setTimeout(() => { clearSelection(); applyQuery(1); }, 350);
});

watch([customerPassportFilter, customerMarketingFilter, customerTagFilter, customersPerPage], () => {
    clearSelection();
    applyQuery(1);
});

watch(customerRows, () => {
    selectedCustomerIds.value = [];
    if (selectionMode.value !== 'filtered') {
        selectionToken.value = null;
        selectionCount.value = 0;
        selectionMode.value = null;
    }
});

watch(blastMessage, () => {
    draftStatus.value = 'typing';
    if (draftDebounceTimer) clearTimeout(draftDebounceTimer);
    draftDebounceTimer = setTimeout(() => saveDraftNow(), 600);
});

watch([logsChannelFilter, logsDateFrom, logsDateTo], () => fetchLogs());

onMounted(() => {
    logsRefreshTimer = window.setInterval(() => fetchLogs(), 20000);
});

onUnmounted(() => {
    if (logsRefreshTimer) clearInterval(logsRefreshTimer);
});
</script>

<template>
    <Head :title="`${workspace.name} — Blast Campaigns`" />

    <WorkspaceLayout>
        <div class="w-full py-6">
            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-indigo-600 text-white text-xs font-bold shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4.5 w-4.5"><path d="M3.196 12.87l-.825.483a.75.75 0 000 1.294l7.25 4.25a.75.75 0 00.758 0l7.25-4.25a.75.75 0 000-1.294l-.825-.484-5.666 3.322a2.25 2.25 0 01-2.276 0L3.196 12.87z" /><path d="M3.196 8.87l-.825.483a.75.75 0 000 1.294l7.25 4.25a.75.75 0 00.758 0l7.25-4.25a.75.75 0 000-1.294l-.825-.484-5.666 3.322a2.25 2.25 0 01-2.276 0L3.196 8.87z" /><path d="M10.38 1.103a.75.75 0 00-.76 0l-7.25 4.25a.75.75 0 000 1.294l7.25 4.25a.75.75 0 00.76 0l7.25-4.25a.75.75 0 000-1.294l-7.25-4.25z" /></svg>
                        </span>
                        Blast Campaigns
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Select audience, compose messages &amp; blast via WhatsApp or Email</p>
                </div>
                <a
                    :href="`/workspace/${workspace.slug}/customers`"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z" /></svg>
                    Customer Database
                </a>
            </div>

            <!-- Stats Bar -->
            <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Filtered</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-gray-900">{{ blastSummary.filtered_total }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Eligible</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-indigo-600">{{ blastSummary.blast_eligible }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">WhatsApp</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-green-600">{{ blastSummary.whatsapp_eligible }}</p>
                </div>
                <div class="rounded-lg bg-white p-3.5 ring-1 ring-gray-950/5 shadow-sm">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email</p>
                    <p class="mt-1 text-lg font-bold tabular-nums text-blue-600">{{ blastSummary.email_eligible }}</p>
                </div>
            </div>

            <!-- Main Grid -->
            <div class="grid gap-4 xl:grid-cols-[1fr_320px]">

                <!-- Left Column -->
                <div class="space-y-4">

                    <!-- Audience Filters -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Audience Filters</h3>
                        </div>
                        <div class="grid gap-3 p-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Search</label>
                                <div class="relative">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                                    <input
                                        v-model="customerSearch"
                                        type="text"
                                        placeholder="Name, passport, phone..."
                                        class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-8 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Passport</label>
                                <select v-model="customerPassportFilter" class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                                    <option value="all">All records</option>
                                    <option value="with_passport_copy">Has passport</option>
                                    <option value="without_passport_copy">No passport</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Marketing</label>
                                <select v-model="customerMarketingFilter" class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                                    <option value="all">All consent</option>
                                    <option value="allowed">Allowed</option>
                                    <option value="blocked">Blocked</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-xs font-medium text-gray-600">Tag</label>
                                <select v-model="customerTagFilter" class="w-full rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                                    <option value="">All tags</option>
                                    <option v-for="tag in availableTags" :key="tag" :value="tag">{{ tag }}</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Selection & Blast Controls -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3 flex items-center justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Selection &amp; Blast</h3>
                            <div class="flex items-center gap-2">
                                <span class="text-xs tabular-nums text-gray-400">
                                    {{ allFilteredSelected ? `All filtered (${selectionCount})` : `${selectedCustomerIds.length} on page` }}
                                </span>
                            </div>
                        </div>
                        <div class="p-4">
                            <!-- Steps hint -->
                            <div class="mb-4 flex items-center gap-4 text-xs text-gray-500">
                                <span class="inline-flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600">1</span> Select</span>
                                <span class="h-px flex-1 bg-gray-200"></span>
                                <span class="inline-flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600">2</span> Channel</span>
                                <span class="h-px flex-1 bg-gray-200"></span>
                                <span class="inline-flex items-center gap-1.5"><span class="flex h-5 w-5 items-center justify-center rounded-full bg-indigo-100 text-[10px] font-bold text-indigo-600">3</span> Blast</span>
                            </div>

                            <!-- Selection controls -->
                            <div class="flex flex-wrap items-center gap-2">
                                <label class="inline-flex items-center gap-2 rounded-lg bg-gray-50 px-3 py-2 text-xs font-medium text-gray-700 ring-1 ring-inset ring-gray-200 cursor-pointer hover:bg-gray-100 transition">
                                    <input v-model="selectAllOnPage" type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                    Select page
                                </label>
                                <button
                                    v-if="selectedCustomerIds.length === customerRows.length && (props.customers?.total ?? 0) > customerRows.length && !allFilteredSelected"
                                    type="button"
                                    class="rounded-lg bg-indigo-50 px-3 py-2 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 transition hover:bg-indigo-100"
                                    :disabled="isCreatingToken"
                                    @click="selectAllFilteredAudience"
                                >
                                    Select all {{ props.customers?.total ?? 0 }} filtered
                                </button>
                                <button
                                    v-if="hasAnySelection"
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-gray-500 hover:bg-gray-50 hover:text-gray-700 transition"
                                    @click="clearSelection"
                                >
                                    Clear
                                </button>

                                <div class="h-5 w-px bg-gray-200"></div>

                                <!-- Channel selector -->
                                <div class="inline-flex rounded-lg ring-1 ring-inset ring-gray-200 overflow-hidden">
                                    <button
                                        type="button"
                                        class="px-3 py-2 text-xs font-medium transition"
                                        :class="blastChannel === 'whatsapp' ? 'bg-green-50 text-green-700' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                        @click="blastChannel = 'whatsapp'"
                                    >
                                        <span class="inline-flex items-center gap-1">
                                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="currentColor"><path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347z" /><path d="M12 2C6.477 2 2 6.477 2 12c0 1.89.525 3.66 1.438 5.168L2 22l4.832-1.438A9.955 9.955 0 0012 22c5.523 0 10-4.477 10-10S17.523 2 12 2zm0 18a8 8 0 01-4.243-1.214l-.252-.149-2.868.852.852-2.868-.168-.268A7.96 7.96 0 014 12a8 8 0 1116 0 8 8 0 01-8 8z" /></svg>
                                            WhatsApp
                                        </span>
                                    </button>
                                    <button
                                        type="button"
                                        class="px-3 py-2 text-xs font-medium transition"
                                        :class="blastChannel === 'email' ? 'bg-blue-50 text-blue-700' : 'bg-white text-gray-500 hover:bg-gray-50'"
                                        @click="blastChannel = 'email'"
                                    >
                                        <span class="inline-flex items-center gap-1">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3.5 w-3.5"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.162V6a2 2 0 00-2-2H3z" /><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" /></svg>
                                            Email
                                        </span>
                                    </button>
                                </div>

                                <div class="h-5 w-px bg-gray-200"></div>

                                <!-- Quick actions -->
                                <button type="button" class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 transition disabled:opacity-40" :disabled="selectedStats.total === 0" @click="copySelectedNormalizedPhones" title="Copy phone numbers">
                                    Copy Phones
                                </button>
                                <button type="button" class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 transition disabled:opacity-40" :disabled="selectedStats.total === 0" @click="copySelectedWhatsAppLinks" title="Copy WA links">
                                    Copy WA Links
                                </button>

                                <div class="h-5 w-px bg-gray-200"></div>

                                <select v-model="exportFormat" class="rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-xs text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                                    <option value="crm_full">CRM Full</option>
                                    <option value="wa_ready">WA Ready</option>
                                    <option value="email_ready">Email Ready</option>
                                </select>
                                <button
                                    type="button"
                                    class="rounded-lg px-3 py-2 text-xs font-medium text-gray-600 ring-1 ring-inset ring-gray-200 hover:bg-gray-50 transition disabled:opacity-40"
                                    :disabled="!hasAnySelection || isCreatingToken"
                                    @click="exportSelected"
                                >
                                    Export
                                </button>
                            </div>

                            <!-- Readiness info -->
                            <div class="mt-3 flex items-center gap-3 text-xs">
                                <span class="text-gray-500">Ready: WA <strong class="text-green-600">{{ selectedStats.waReady }}</strong> · Email <strong class="text-blue-600">{{ selectedStats.emailReady }}</strong></span>
                                <span class="text-indigo-600 font-medium">{{ blastReadinessText }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Message Composer -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3 flex items-center justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Message Composer</h3>
                            <span class="text-[11px]" :class="{
                                'text-gray-400': draftStatus === 'saved',
                                'text-amber-500': draftStatus === 'typing' || draftStatus === 'saving',
                                'text-red-500': draftStatus === 'error',
                            }">
                                <template v-if="draftStatus === 'saving'">Saving...</template>
                                <template v-else-if="draftStatus === 'typing'">Typing...</template>
                                <template v-else-if="draftStatus === 'saved'">Auto-saved</template>
                                <template v-else>{{ saveDraftError || 'Save failed' }}</template>
                            </span>
                        </div>
                        <div class="p-4 space-y-3">
                            <textarea
                                v-model="blastMessage"
                                rows="4"
                                placeholder="Compose your blast message..."
                                class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition resize-y"
                            ></textarea>
                            <button
                                type="button"
                                class="w-full rounded-lg px-4 py-3 text-sm font-semibold text-white shadow-sm transition disabled:opacity-40 disabled:cursor-not-allowed"
                                :class="blastChannel === 'email' ? 'bg-blue-600 hover:bg-blue-700' : 'bg-green-600 hover:bg-green-700'"
                                :disabled="!canRunBlast"
                                @click="runBlast"
                            >
                                {{ blastFinalActionLabel }}
                            </button>
                            <p class="text-[11px] text-gray-400">{{ blastActionHelper }}</p>
                        </div>
                    </div>

                    <!-- Customer Table -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Audience List</h3>
                        </div>
                        <div v-if="customerRows.length" class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead>
                                    <tr class="border-b border-gray-100 bg-gray-50/60">
                                        <th class="w-10 py-3.5 px-4">
                                            <input v-model="selectAllOnPage" type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </th>
                                        <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500">Traveler</th>
                                        <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500">Passport</th>
                                        <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500">Contact</th>
                                        <th class="whitespace-nowrap py-3.5 px-4 text-xs font-medium uppercase tracking-wider text-gray-500 text-center">Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr
                                        v-for="customer in customerRows"
                                        :key="customer.id"
                                        class="border-b border-gray-100 transition-colors"
                                        :class="selectedCustomerIds.includes(customer.id) ? 'bg-indigo-50/60' : 'hover:bg-gray-50/80'"
                                    >
                                        <td class="w-10 py-3.5 px-4">
                                            <input v-model="selectedCustomerIds" :value="customer.id" type="checkbox" class="h-3.5 w-3.5 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500" />
                                        </td>
                                        <td class="py-3.5 px-4">
                                            <p class="font-medium text-gray-900">{{ customer.name }}</p>
                                            <div v-if="customer.tags?.length" class="mt-1 flex flex-wrap gap-1">
                                                <span v-for="tag in customer.tags" :key="`${customer.id}-${tag}`" class="inline-flex items-center rounded-full bg-gray-100 px-2 py-0.5 text-[10px] font-medium text-gray-600">{{ tag }}</span>
                                            </div>
                                        </td>
                                        <td class="py-3.5 px-4 text-gray-600">{{ customer.passport_number || '—' }}</td>
                                        <td class="py-3.5 px-4">
                                            <p class="text-gray-900">{{ customer.phone || '—' }}</p>
                                            <p class="mt-0.5 text-xs text-gray-400">{{ customer.email || 'No email' }}</p>
                                        </td>
                                        <td class="py-3.5 px-4 text-center">
                                            <span
                                                class="inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset"
                                                :class="customer.allow_marketing ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-red-50 text-red-700 ring-red-600/20'"
                                            >
                                                {{ customer.allow_marketing ? 'Allowed' : 'Blocked' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div v-else class="py-12 text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-gray-100">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-gray-400"><path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z" /></svg>
                            </div>
                            <p class="mt-3 text-sm font-medium text-gray-500">No matching customers</p>
                            <p class="mt-1 text-xs text-gray-400">Try adjusting your filters.</p>
                        </div>

                        <!-- Pagination -->
                        <div v-if="customerRows.length" class="flex items-center justify-between border-t border-gray-100 px-4 py-3">
                            <p class="text-xs text-gray-500 tabular-nums">
                                {{ props.customers?.from ?? 0 }}–{{ props.customers?.to ?? 0 }} of {{ props.customers?.total ?? 0 }}
                            </p>
                            <div class="flex items-center gap-2">
                                <select
                                    v-model.number="customersPerPage"
                                    class="rounded-md border-0 bg-gray-50 py-1 pl-2 pr-6 text-xs ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500"
                                >
                                    <option :value="10">10</option>
                                    <option :value="20">20</option>
                                    <option :value="50">50</option>
                                    <option :value="100">100</option>
                                </select>
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                    :disabled="(props.customers?.current_page ?? 1) <= 1"
                                    @click="gotoPreviousPage"
                                >← Prev</button>
                                <span class="text-xs text-gray-500 tabular-nums">{{ props.customers?.current_page ?? 1 }} / {{ props.customers?.last_page ?? 1 }}</span>
                                <button
                                    type="button"
                                    class="rounded-md px-2.5 py-1.5 text-xs font-medium text-gray-600 hover:bg-gray-100 disabled:opacity-40 disabled:cursor-not-allowed transition"
                                    :disabled="(props.customers?.current_page ?? 1) >= (props.customers?.last_page ?? 1)"
                                    @click="gotoNextPage"
                                >Next →</button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Right Sidebar -->
                <div class="space-y-4">

                    <!-- Saved Templates -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Saved Templates</h3>
                        </div>
                        <div class="p-4 space-y-3">
                            <!-- New template form -->
                            <form class="space-y-2" @submit.prevent="saveTemplate">
                                <input
                                    v-model="templateForm.name"
                                    type="text"
                                    placeholder="Template name"
                                    class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition"
                                />
                                <textarea
                                    v-model="templateForm.body"
                                    rows="2"
                                    placeholder="Message body"
                                    class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-indigo-500 transition resize-y"
                                />
                                <button
                                    type="submit"
                                    class="w-full rounded-lg bg-indigo-600 px-3 py-2 text-xs font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-50"
                                    :disabled="templateForm.processing"
                                >
                                    Save Template
                                </button>
                            </form>

                            <!-- Template list -->
                            <div v-for="template in templates" :key="template.id" class="rounded-lg bg-gray-50 p-3 ring-1 ring-inset ring-gray-200">
                                <template v-if="editingTemplateId === template.id">
                                    <input
                                        v-model="templateEditForm.name"
                                        type="text"
                                        class="w-full rounded-lg border-0 bg-white px-3 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500 transition"
                                    />
                                    <textarea
                                        v-model="templateEditForm.body"
                                        rows="2"
                                        class="mt-2 w-full rounded-lg border-0 bg-white px-3 py-1.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500 transition resize-y"
                                    />
                                    <div class="mt-2 flex gap-2">
                                        <button type="button" class="rounded-md bg-indigo-600 px-2.5 py-1 text-xs font-medium text-white hover:bg-indigo-700 transition" @click="updateTemplate(template.id)">Save</button>
                                        <button type="button" class="rounded-md px-2.5 py-1 text-xs font-medium text-gray-500 hover:text-gray-700 transition" @click="cancelEditTemplate">Cancel</button>
                                    </div>
                                </template>
                                <template v-else>
                                    <p class="text-sm font-medium text-gray-900">{{ template.name }}</p>
                                    <p class="mt-1 text-xs text-gray-500 line-clamp-2">{{ template.body }}</p>
                                    <div class="mt-2 flex gap-1.5">
                                        <button type="button" class="rounded-md bg-indigo-50 px-2.5 py-1 text-xs font-medium text-indigo-700 ring-1 ring-inset ring-indigo-200 hover:bg-indigo-100 transition" @click="applyTemplate(template)">Apply</button>
                                        <button type="button" class="rounded-md px-2.5 py-1 text-xs font-medium text-gray-500 ring-1 ring-inset ring-gray-200 hover:bg-gray-100 transition" @click="startEditTemplate(template)">Edit</button>
                                        <button type="button" class="rounded-md px-2.5 py-1 text-xs font-medium text-red-600 ring-1 ring-inset ring-red-200 hover:bg-red-50 transition" @click="deleteTemplate(template.id, template.name)">Delete</button>
                                    </div>
                                </template>
                            </div>

                            <p v-if="!templates.length" class="py-3 text-center text-xs text-gray-400">No templates yet. Save one above.</p>
                        </div>
                    </div>

                    <!-- Blast Logs -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-4 py-3 flex items-center justify-between">
                            <h3 class="text-xs font-semibold uppercase tracking-wider text-gray-500">Recent Logs</h3>
                            <span v-if="isLogsLoading" class="text-[10px] text-gray-400">Refreshing...</span>
                        </div>
                        <div class="p-4 space-y-3">
                            <!-- Log filters -->
                            <div class="grid grid-cols-1 gap-2">
                                <select v-model="logsChannelFilter" class="rounded-lg border-0 bg-gray-50 py-2 pl-3 pr-8 text-xs text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500">
                                    <option value="all">All channels</option>
                                    <option value="whatsapp">WhatsApp</option>
                                    <option value="email">Email</option>
                                </select>
                                <div class="grid grid-cols-2 gap-2">
                                    <input v-model="logsDateFrom" type="date" class="rounded-lg border-0 bg-gray-50 py-2 px-2.5 text-xs text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500" />
                                    <input v-model="logsDateTo" type="date" class="rounded-lg border-0 bg-gray-50 py-2 px-2.5 text-xs text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-indigo-500" />
                                </div>
                            </div>

                            <!-- Log entries -->
                            <div v-if="logs.length" class="space-y-2 max-h-[400px] overflow-y-auto">
                                <div v-for="log in logs" :key="log.id" class="rounded-lg bg-gray-50 p-3 ring-1 ring-inset ring-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset"
                                            :class="log.channel === 'whatsapp' ? 'bg-green-50 text-green-700 ring-green-600/20' : 'bg-blue-50 text-blue-700 ring-blue-600/20'"
                                        >
                                            {{ log.channel === 'whatsapp' ? 'WhatsApp' : 'Email' }}
                                        </span>
                                        <span class="text-[10px] text-gray-400 tabular-nums">{{ new Date(log.created_at).toLocaleDateString('en-MY', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' }) }}</span>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-700">
                                        <strong class="tabular-nums">{{ log.recipient_count }}</strong> recipients
                                        <span class="text-gray-400 mx-1">·</span>
                                        WA {{ log.whatsapp_ready_count }}
                                        <span class="text-gray-400 mx-1">·</span>
                                        Email {{ log.email_ready_count }}
                                    </p>
                                    <p v-if="log.selection_mode" class="mt-0.5 text-[10px] text-gray-400">Mode: {{ log.selection_mode }}</p>
                                </div>
                            </div>
                            <p v-else class="py-3 text-center text-xs text-gray-400">No blast logs yet.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
