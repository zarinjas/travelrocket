<script setup>
import { Head, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    invoices: { type: Array, default: () => [] },
    financialInsights: {
        type: Object,
        default: () => ({
            aging: {},
            collection_radar: [],
            reminder_queue: [],
            package_performance: [],
            cashflow_forecast: [],
            collection_actions: [],
            collector_snapshot: {
                reminders_sent_7d: 0,
                email_sent_7d: 0,
                whatsapp_opened_7d: 0,
                unique_recipients_7d: 0,
            },
            collector_options: [],
            due_timeline: [],
            outstanding_total: 0,
            summary: {
                overdue_amount: 0,
                due_soon_amount: 0,
                at_risk_accounts: 0,
                collection_rate: 0,
            },
        }),
    },
});

const selectedCollector = ref('all');
const selectedStage = ref('all');
const actionSearch = ref('');

const totalInvoiced = computed(() => props.invoices.reduce((sum, inv) => sum + Number(inv.total || 0), 0));
const totalCollected = computed(() => props.invoices.reduce((sum, inv) => sum + Number(inv.paid_amount || 0), 0));
const netCollectable = computed(() => Math.max(0, totalInvoiced.value - totalCollected.value));
const collectionPercent = computed(() => totalInvoiced.value > 0 ? ((totalCollected.value / totalInvoiced.value) * 100).toFixed(1) : '0.0');

const filteredActions = computed(() => {
    return (props.financialInsights.collection_actions || []).filter((action) => {
        const matchCollector = selectedCollector.value === 'all' || action.collector_name === selectedCollector.value;
        const matchStage = selectedStage.value === 'all' || action.stage === selectedStage.value;
        const matchSearch = !actionSearch.value || action.customer_name?.toLowerCase().includes(actionSearch.value.toLowerCase()) || action.invoice_number?.toLowerCase().includes(actionSearch.value.toLowerCase());
        return matchCollector && matchStage && matchSearch;
    });
});

const topRiskAmount = computed(() => filteredActions.value.slice(0, 5).reduce((sum, a) => sum + Number(a.balance_due || 0), 0));

const urgencyColor = (urgency) => {
    switch (urgency) {
        case 'critical': return 'bg-red-50 text-red-700 ring-red-600/20';
        case 'high': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
        case 'medium': return 'bg-amber-50 text-amber-700 ring-amber-600/20';
        default: return 'bg-gray-50 text-gray-600 ring-gray-500/20';
    }
};

const urgencyDot = (urgency) => {
    switch (urgency) {
        case 'critical': return 'bg-red-500';
        case 'high': return 'bg-orange-500';
        case 'medium': return 'bg-amber-500';
        default: return 'bg-gray-400';
    }
};

const stageLabel = (stage) => {
    switch (stage) {
        case 'overdue': return 'Overdue';
        case 'due_today': return 'Due Today';
        case 'due_soon': return 'Due Soon';
        default: return 'General';
    }
};

const stageColor = (stage) => {
    switch (stage) {
        case 'overdue': return 'bg-red-50 text-red-700 ring-red-600/20';
        case 'due_today': return 'bg-orange-50 text-orange-700 ring-orange-600/20';
        case 'due_soon': return 'bg-amber-50 text-amber-700 ring-amber-600/20';
        default: return 'bg-gray-50 text-gray-600 ring-gray-500/20';
    }
};

const rm = (val) => `RM ${Number(val || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

const markActionDone = (action) => {
    router.post(
        `/workspace/${props.workspace.slug}/invoices/${action.id}/mark-collection-action`,
        { stage: action.stage || 'general' },
        { preserveScroll: true },
    );
};
</script>

<template>
    <Head :title="`${workspace.name} — Cashflow Command Center`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between mb-6">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-600 text-white shadow-sm">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4.5 w-4.5"><path d="M10.75 10.818a4.478 4.478 0 00-1.065-.502A3.502 3.502 0 0013 6.5 3.5 3.5 0 106.5 6.5c0 1.148.585 2.158 1.467 2.753-.344.12-.668.284-.966.486A4.478 4.478 0 005 13.5v.5a.5.5 0 00.5.5h9a.5.5 0 00.5-.5v-.5a4.478 4.478 0 00-4.25-4.182z" /><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 4a.75.75 0 000 1.5h.25v.832a3.249 3.249 0 00-1.643 1.025.75.75 0 001.136.98A1.75 1.75 0 018.5 7.75h3a.25.25 0 01.25.25v.118a.25.25 0 01-.168.236l-3.9 1.354A1.75 1.75 0 006.75 11.4v.118c0 .746.467 1.412 1.168 1.667l.582.203V14a.75.75 0 001.5 0v-.507l1.168-.407A1.75 1.75 0 0012.25 11.4v-.118a1.75 1.75 0 00-1.168-1.667L8.5 8.75h3a1.75 1.75 0 001.75-1.75V6.882a1.75 1.75 0 00-1.168-1.667L11.5 4.982V4.5h.25a.75.75 0 000-1.5H7z" clip-rule="evenodd" /></svg>
                        </span>
                        Cashflow Command Center
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">Monitor cash flow, collection risks, and execute financial actions</p>
                </div>
                <div class="flex flex-wrap gap-2">
                    <a
                        :href="`/workspace/${workspace.slug}/invoices/financial-export`"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                        Export Financials
                    </a>
                    <a
                        :href="`/workspace/${workspace.slug}/invoices/collection-actions-export`"
                        class="inline-flex items-center gap-2 rounded-lg bg-emerald-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                        Export Collections
                    </a>
                </div>
            </div>

            <!-- Financial Summary Cards -->
            <div class="mb-6 grid grid-cols-2 gap-3 lg:grid-cols-5">
                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-600"><path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm10-1a1 1 0 11-2 0 1 1 0 012 0zM2 15.25h16a.75.75 0 010 1.5H2a.75.75 0 010-1.5z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-500">Total Billed</p>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums text-gray-900">{{ rm(totalInvoiced) }}</p>
                </div>

                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-emerald-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-500">Collected</p>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums text-emerald-600">{{ rm(totalCollected) }}</p>
                    <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                        <div class="h-1.5 rounded-full bg-emerald-500 transition-all" :style="{ width: `${collectionPercent}%` }"></div>
                    </div>
                    <p class="mt-1 text-[11px] text-gray-400 tabular-nums">{{ collectionPercent }}% collection rate</p>
                </div>

                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-red-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-red-600"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-500">Outstanding</p>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums text-red-600">{{ rm(financialInsights.outstanding_total) }}</p>
                </div>

                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-amber-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-amber-600"><path d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495z" /></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-500">At-Risk Accounts</p>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums text-amber-600">{{ Number(financialInsights.summary?.at_risk_accounts || 0) }}</p>
                </div>

                <div class="rounded-xl bg-white p-4 ring-1 ring-gray-950/5 shadow-sm">
                    <div class="flex items-center gap-2">
                        <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-100">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-blue-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-xs font-medium text-gray-500">Due Soon</p>
                    </div>
                    <p class="mt-3 text-xl font-bold tabular-nums text-blue-600">{{ rm(financialInsights.summary?.due_soon_amount) }}</p>
                    <p class="mt-1 text-[11px] text-gray-400">within 7 days</p>
                </div>
            </div>

            <!-- Main Grid: Forecast + Aging -->
            <div class="mb-6 grid gap-4 xl:grid-cols-[1.3fr_1fr]">

                <!-- Cashflow Forecast -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Collection Forecast</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Estimated incoming cash based on invoice due dates</p>
                    </div>
                    <div class="p-5 space-y-3">
                        <div v-for="row in financialInsights.cashflow_forecast" :key="row.days" class="rounded-lg bg-gray-50 p-4 ring-1 ring-inset ring-gray-200">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ row.label }}</p>
                                    <p class="mt-1 text-xs text-gray-500">{{ row.invoice_count }} invoices</p>
                                </div>
                                <div class="text-right">
                                    <p class="text-lg font-bold tabular-nums text-gray-900">{{ rm(row.expected_collection) }}</p>
                                    <p v-if="row.at_risk_amount > 0" class="mt-0.5 text-[11px] text-red-500 tabular-nums">
                                        {{ rm(row.at_risk_amount) }} at risk
                                    </p>
                                </div>
                            </div>
                            <div class="mt-3 h-2 overflow-hidden rounded-full bg-gray-200">
                                <div
                                    class="h-2 rounded-full bg-gradient-to-r from-emerald-500 to-emerald-400 transition-all"
                                    :style="{ width: `${Math.min(100, Math.max(6, ((row.expected_collection || 0) / Math.max(1, netCollectable)) * 100))}%` }"
                                />
                            </div>
                        </div>

                        <div v-if="!financialInsights.cashflow_forecast?.length" class="py-8 text-center">
                            <p class="text-sm text-gray-400">No forecast data available</p>
                        </div>
                    </div>
                </div>

                <!-- Aging Breakdown -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">AR Aging Breakdown</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Outstanding invoices by overdue period</p>
                    </div>
                    <div class="p-5 space-y-2">
                        <div v-for="(bucket, key) in financialInsights.aging" :key="key" class="flex items-center gap-4 rounded-lg px-4 py-3"
                            :class="bucket.amount > 0 ? 'bg-gray-50 ring-1 ring-inset ring-gray-200' : 'bg-gray-50/50'"
                        >
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-700">{{ bucket.label }}</p>
                                <p class="text-xs text-gray-400 tabular-nums">{{ bucket.count || 0 }} invoices</p>
                            </div>
                            <p class="text-sm font-bold tabular-nums" :class="bucket.amount > 0 ? 'text-gray-900' : 'text-gray-300'">{{ rm(bucket.amount) }}</p>
                        </div>

                        <div v-if="!Object.keys(financialInsights.aging || {}).length" class="py-8 text-center">
                            <p class="text-sm text-gray-400">No outstanding receivables</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Collection Actions + Top Overdue Grid -->
            <div class="grid gap-4 xl:grid-cols-[1.3fr_1fr]">

                <!-- Collection Action Queue -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                            <div>
                                <h2 class="text-sm font-semibold text-gray-900">Collection Action Queue</h2>
                                <p class="mt-0.5 text-xs text-gray-500">Invoices requiring follow-up — prioritized by risk</p>
                            </div>
                            <p class="text-xs tabular-nums text-gray-400">
                                Top exposure: <strong class="text-red-600">{{ rm(topRiskAmount) }}</strong>
                            </p>
                        </div>
                    </div>

                    <!-- Filters -->
                    <div class="border-b border-gray-100 bg-gray-50/50 px-5 py-3">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <div class="relative">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-2.5 top-1/2 h-3.5 w-3.5 -translate-y-1/2 text-gray-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                                <input v-model="actionSearch" type="text" placeholder="Search customer / invoice..." class="w-full rounded-lg border-0 bg-white py-2 pl-8 pr-3 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-emerald-500 transition" />
                            </div>
                            <select v-model="selectedStage" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-emerald-500">
                                <option value="all">All Statuses</option>
                                <option value="overdue">Overdue</option>
                                <option value="due_today">Due Today</option>
                                <option value="due_soon">Due Soon</option>
                                <option value="general">General</option>
                            </select>
                            <select v-model="selectedCollector" class="rounded-lg border-0 bg-white py-2 pl-3 pr-8 text-sm text-gray-700 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-emerald-500">
                                <option value="all">All Collectors</option>
                                <option v-for="collector in financialInsights.collector_options" :key="collector" :value="collector">{{ collector }}</option>
                            </select>
                        </div>
                    </div>

                    <!-- Action Items -->
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="action in filteredActions.slice(0, 10)"
                            :key="action.id"
                            class="px-5 py-4 hover:bg-gray-50/80 transition-colors"
                        >
                            <div class="flex items-start gap-4">
                                <!-- Urgency dot -->
                                <div class="mt-1.5 flex-shrink-0">
                                    <div class="h-2.5 w-2.5 rounded-full" :class="urgencyDot(action.days_overdue >= 31 ? 'critical' : action.days_overdue >= 8 ? 'high' : action.days_overdue >= 1 ? 'medium' : 'low')"></div>
                                </div>

                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <p class="text-sm font-semibold text-gray-900">{{ action.customer_name }}</p>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset" :class="stageColor(action.stage)">
                                            {{ stageLabel(action.stage) }}
                                        </span>
                                    </div>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ action.invoice_number }}</p>
                                    <p class="mt-1.5 text-xs text-gray-600 leading-relaxed">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="inline h-3 w-3 text-gray-400 mr-0.5"><path fill-rule="evenodd" d="M15 8A7 7 0 111 8a7 7 0 0114 0zm-6-3.5a.5.5 0 01.5.5v3.362l2.01 1.34a.5.5 0 01-.555.832l-2.232-1.488A.5.5 0 018 8.5V5a.5.5 0 01.5-.5z" clip-rule="evenodd" /></svg>
                                        {{ action.recommended_action }}
                                    </p>
                                </div>

                                <div class="flex-shrink-0 text-right">
                                    <p class="text-sm font-bold tabular-nums text-gray-900">{{ rm(action.balance_due) }}</p>
                                    <p v-if="action.days_overdue > 0" class="mt-0.5 text-[11px] tabular-nums text-red-500">{{ action.days_overdue }} days overdue</p>
                                    <button
                                        type="button"
                                        class="mt-2 rounded-lg bg-gray-900 px-3 py-1.5 text-[11px] font-semibold text-white shadow-sm transition hover:bg-gray-800"
                                        @click="markActionDone(action)"
                                    >
                                        Done
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div v-if="!filteredActions.length" class="py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-500">All clear!</p>
                        <p class="mt-1 text-xs text-gray-400">No collection actions required.</p>
                    </div>
                </div>

                <!-- Top Overdue Accounts -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-5 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Top Overdue Accounts</h2>
                        <p class="mt-0.5 text-xs text-gray-500">Longest outstanding invoices</p>
                    </div>
                    <div class="divide-y divide-gray-100">
                        <div
                            v-for="row in financialInsights.collection_radar.slice(0, 7)"
                            :key="row.id"
                            class="flex items-center gap-4 px-5 py-3.5 hover:bg-gray-50/80 transition-colors"
                        >
                            <div class="flex-shrink-0">
                                <div class="h-2.5 w-2.5 rounded-full" :class="urgencyDot(row.urgency)"></div>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium text-gray-900 truncate">{{ row.customer_name }}</p>
                                <p class="text-xs text-gray-400">{{ row.invoice_number }}</p>
                            </div>
                            <div class="text-right flex-shrink-0">
                                <p class="text-sm font-bold tabular-nums text-gray-900">{{ rm(row.balance_due) }}</p>
                                <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold ring-1 ring-inset" :class="urgencyColor(row.urgency)">
                                    {{ row.days_overdue > 0 ? `${row.days_overdue}d overdue` : 'On track' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div v-if="!financialInsights.collection_radar?.length" class="py-12 text-center">
                        <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="mt-3 text-sm font-medium text-gray-500">No overdue accounts</p>
                    </div>

                    <!-- Due Timeline -->
                    <div v-if="financialInsights.due_timeline?.length" class="border-t border-gray-100 px-5 py-4">
                        <h3 class="mb-3 text-xs font-semibold uppercase tracking-wider text-gray-500">Next 30 Days Due</h3>
                        <div class="flex flex-wrap gap-1.5">
                            <div
                                v-for="day in financialInsights.due_timeline"
                                :key="day.date"
                                class="rounded-md bg-amber-50 px-2.5 py-1.5 text-center ring-1 ring-inset ring-amber-200"
                                :title="`${day.count} invoices — ${rm(day.amount)}`"
                            >
                                <p class="text-[10px] font-medium text-amber-700">{{ day.label }}</p>
                                <p class="text-[10px] font-bold tabular-nums text-amber-900">{{ day.count }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
