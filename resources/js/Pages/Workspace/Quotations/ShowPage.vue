<script setup>
import { computed } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tenantLogoUrl = computed(() => page.props.tenant?.logo_url);
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    quotation: Object,
});

const formatRM = (v) => 'RM ' + Number(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 });

const isExpired = computed(() => new Date(props.quotation.expiry_date) < new Date());

const statusMeta = computed(() => {
    if (props.quotation.status === 'Closed') return { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/10', dot: 'bg-emerald-500', label: 'Closed' };
    if (isExpired.value) return { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-600/10', dot: 'bg-red-500', label: 'Expired' };
    if (props.quotation.status === 'Sent') return { bg: 'bg-blue-50', text: 'text-blue-700', ring: 'ring-blue-600/10', dot: 'bg-blue-500', label: 'Sent' };
    return { bg: 'bg-gray-50', text: 'text-gray-700', ring: 'ring-gray-600/10', dot: 'bg-gray-500', label: props.quotation.status };
});

const quotationDate = computed(() => {
    const d = props.quotation.created_at;
    return d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
});

const expiryDate = computed(() => {
    const d = props.quotation.expiry_date;
    return d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
});
</script>

<template>
    <Head title="View Quotation" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header Bar -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('quotations.index', { tenant: workspace.slug })" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                        Back
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Quotation {{ quotation.public_id }}</h1>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset" :class="[statusMeta.bg, statusMeta.text, statusMeta.ring]">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta.dot"></span>
                                {{ statusMeta.label }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">Created {{ quotationDate }}</p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('quotations.pdf', { tenant: workspace.slug, quotation: quotation.id })" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5 2.75C5 1.784 5.784 1 6.75 1h6.5c.966 0 1.75.784 1.75 1.75v3.552c.377.046.752.097 1.126.153A2.212 2.212 0 0118 8.653v4.097A2.25 2.25 0 0115.75 15h-.85l.124.497A2.25 2.25 0 0112.848 18H7.153a2.25 2.25 0 01-2.175-2.803l.124-.497H5.25A2.25 2.25 0 013 12.75V8.653c0-1.082.775-2.034 1.874-2.198.374-.056.749-.107 1.126-.153V2.75zm1.5 0v3.379a49.71 49.71 0 017 0V2.75a.25.25 0 00-.25-.25h-6.5a.25.25 0 00-.25.25zM6.75 15h6.5a.75.75 0 01.727.935l-.5 2a.75.75 0 01-.727.565H7.25a.75.75 0 01-.727-.565l-.5-2A.75.75 0 016.75 15z" clip-rule="evenodd" /></svg>
                        Download PDF
                    </a>
                    <Link
                        v-if="quotation.status !== 'Closed'"
                        :href="route('quotations.convert', { tenant: workspace.slug, quotation: quotation.id })"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M13.2 2.24a.75.75 0 00.04 1.06l2.1 1.95H6.75a.75.75 0 000 1.5h8.59l-2.1 1.95a.75.75 0 101.02 1.1l3.5-3.25a.75.75 0 000-1.1l-3.5-3.25a.75.75 0 00-1.06.04zm-6.4 8a.75.75 0 00-1.06-.04l-3.5 3.25a.75.75 0 000 1.1l3.5 3.25a.75.75 0 101.02-1.1l-2.1-1.95h8.59a.75.75 0 000-1.5H4.66l2.1-1.95a.75.75 0 00.04-1.06z" clip-rule="evenodd" /></svg>
                        Convert to Invoice
                    </Link>
                </div>
            </div>

            <!-- Quotation Document -->
            <div id="quotation-print-area" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 relative">

                <div class="p-8 sm:p-10">

                    <!-- Company Header + Quotation Label -->
                    <div class="flex items-start justify-between gap-8 pb-8 border-b border-gray-100">
                        <div class="flex items-start gap-4">
                            <img v-if="tenantLogoUrl" :src="tenantLogoUrl" alt="Logo" class="h-11 w-11 shrink-0 rounded-xl object-contain shadow-sm ring-1 ring-gray-100" />
                            <div v-else class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 text-xs font-bold text-white shadow-sm">TR</div>
                            <div>
                                <p class="text-base font-bold text-gray-900">{{ workspace.name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500 max-w-xs leading-relaxed">Level 8, Menara Travel, Kuala Lumpur, Malaysia</p>
                            </div>
                        </div>
                        <div class="text-right shrink-0">
                            <p class="text-2xl font-bold tracking-tight text-gray-900">QUOTATION</p>
                            <p class="mt-1 text-sm font-semibold text-gray-500"># {{ quotation.public_id }}</p>
                        </div>
                    </div>

                    <!-- Meta: Bill To + Details -->
                    <div class="mt-8 grid gap-8 sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Bill To</p>
                            <template v-if="quotation.customer">
                                <p class="text-sm font-bold text-gray-900">{{ quotation.customer.name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ quotation.customer.phone || '-' }}</p>
                                <p class="text-xs text-gray-500">{{ quotation.customer.email }}</p>
                            </template>
                            <template v-else>
                                <p class="text-sm font-bold text-gray-900">{{ quotation.manual_customer_data?.name || 'Walk-in Customer' }}</p>
                                <p v-if="quotation.manual_customer_data?.address" class="mt-0.5 text-xs text-gray-500 whitespace-pre-line">{{ quotation.manual_customer_data.address }}</p>
                            </template>
                        </div>
                        <div class="space-y-3 sm:text-right">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Quotation Date</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ quotationDate }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Expiry Date</p>
                                <p class="mt-0.5 text-sm font-semibold" :class="isExpired ? 'text-red-600' : 'text-gray-900'">{{ expiryDate }}</p>
                            </div>
                            <div v-if="quotation.subject">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Subject</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ quotation.subject }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Status</p>
                                <p class="mt-0.5 text-sm font-semibold" :class="statusMeta.text">{{ statusMeta.label }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Line Items Table -->
                    <div class="mt-8 -mx-10 px-10">
                        <table class="w-full text-left">
                            <thead>
                                <tr class="border-y border-gray-100">
                                    <th class="py-3 pr-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 w-10">#</th>
                                    <th class="py-3 pr-4 text-[10px] font-bold uppercase tracking-widest text-gray-400">Item & Description</th>
                                    <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-center w-20">Qty</th>
                                    <th class="py-3 px-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-right w-28">Rate</th>
                                    <th class="py-3 pl-4 text-[10px] font-bold uppercase tracking-widest text-gray-400 text-right w-32">Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-for="(item, index) in quotation.items" :key="index" class="border-b border-gray-50">
                                    <td class="py-4 pr-4 text-xs tabular-nums text-gray-400">{{ index + 1 }}</td>
                                    <td class="py-4 pr-4">
                                        <p class="text-sm font-semibold text-gray-900">{{ item.description.split('\n')[0] }}</p>
                                        <p v-if="item.description.split('\n').length > 1" class="mt-0.5 text-xs text-gray-500 whitespace-pre-line leading-relaxed">{{ item.description.split('\n').slice(1).join('\n') }}</p>
                                    </td>
                                    <td class="py-4 px-4 text-center text-sm tabular-nums text-gray-700">{{ item.qty }}</td>
                                    <td class="py-4 px-4 text-right text-sm tabular-nums text-gray-700">{{ Number(item.rate).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</td>
                                    <td class="py-4 pl-4 text-right text-sm font-semibold tabular-nums text-gray-900">{{ formatRM(item.amount) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Summary -->
                    <div class="mt-6 flex justify-end">
                        <div class="w-full max-w-xs space-y-2">
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Sub Total</span>
                                <span class="font-semibold tabular-nums text-gray-900">{{ formatRM(quotation.sub_total) }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between rounded-xl bg-gray-900 px-5 py-4">
                                <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Total</span>
                                <span class="text-xl font-bold tabular-nums text-white">{{ formatRM(quotation.total) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Terms -->
                    <div v-if="quotation.notes || quotation.terms" class="mt-8 grid gap-6 border-t border-gray-100 pt-8 sm:grid-cols-2">
                        <div v-if="quotation.notes">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Notes</p>
                            <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-line">{{ quotation.notes }}</p>
                        </div>
                        <div v-if="quotation.terms">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Terms & Conditions</p>
                            <p class="text-xs text-gray-500 leading-relaxed whitespace-pre-line">{{ quotation.terms }}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #quotation-print-area, #quotation-print-area * { visibility: visible; }
    #quotation-print-area {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0 !important;
        padding: 2rem !important;
        border: none !important;
        box-shadow: none !important;
        border-radius: 0 !important;
    }
    .workspace-sidebar, .workspace-header, button, a[href] { display: none !important; }
    @page { size: auto; margin: 12mm; }
}
</style>
