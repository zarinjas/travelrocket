<script setup>
import { computed, ref } from 'vue';
import { Head, Link, usePage } from '@inertiajs/vue3';

const page = usePage();
const tenantLogoUrl = computed(() => page.props.tenant?.logo_url);
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import PaymentModal from '@/Pages/Workspace/Invoices/Partials/PaymentModal.vue';

const props = defineProps({
    workspace: Object,
    invoice: Object,
});

const showPaymentModal = ref(false);

const formatRM = (v) => 'RM ' + Number(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 });

const balanceDue = computed(() => Number(props.invoice.total) - Number(props.invoice.paid_amount || 0));

const statusMeta = computed(() => {
    const s = props.invoice.status;
    if (s === 'Fully Paid') return { bg: 'bg-emerald-50', text: 'text-emerald-700', ring: 'ring-emerald-600/10', dot: 'bg-emerald-500' };
    if (s === 'Partially Paid') return { bg: 'bg-amber-50', text: 'text-amber-700', ring: 'ring-amber-600/10', dot: 'bg-amber-500' };
    return { bg: 'bg-red-50', text: 'text-red-700', ring: 'ring-red-600/10', dot: 'bg-red-500' };
});

const invoiceDate = computed(() => {
    const d = props.invoice.created_at;
    return d ? new Date(d).toLocaleDateString('en-MY', { day: 'numeric', month: 'long', year: 'numeric' }) : '-';
});
</script>

<template>
    <Head title="View Invoice" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header Bar -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex items-center gap-4">
                    <Link :href="route('invoices.index', { tenant: workspace.slug })" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                        Back
                    </Link>
                    <div>
                        <div class="flex items-center gap-3">
                            <h1 class="text-2xl font-bold tracking-tight text-gray-900">Invoice {{ invoice.public_id }}</h1>
                            <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-[10px] font-bold uppercase tracking-wide ring-1 ring-inset" :class="[statusMeta.bg, statusMeta.text, statusMeta.ring]">
                                <span class="h-1.5 w-1.5 rounded-full" :class="statusMeta.dot"></span>
                                {{ invoice.status }}
                            </span>
                        </div>
                        <p class="mt-0.5 text-sm text-gray-500">
                            <span v-if="invoice.quotation">From Quotation #{{ invoice.quotation.public_id }} · </span>
                            Created {{ invoiceDate }}
                        </p>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="route('invoices.pdf', { tenant: workspace.slug, invoice: invoice.id })" target="_blank" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5 2.75C5 1.784 5.784 1 6.75 1h6.5c.966 0 1.75.784 1.75 1.75v3.552c.377.046.752.097 1.126.153A2.212 2.212 0 0118 8.653v4.097A2.25 2.25 0 0115.75 15h-.85l.124.497A2.25 2.25 0 0112.848 18H7.153a2.25 2.25 0 01-2.175-2.803l.124-.497H5.25A2.25 2.25 0 013 12.75V8.653c0-1.082.775-2.034 1.874-2.198.374-.056.749-.107 1.126-.153V2.75zm1.5 0v3.379a49.71 49.71 0 017 0V2.75a.25.25 0 00-.25-.25h-6.5a.25.25 0 00-.25.25zM6.75 15h6.5a.75.75 0 01.727.935l-.5 2a.75.75 0 01-.727.565H7.25a.75.75 0 01-.727-.565l-.5-2A.75.75 0 016.75 15z" clip-rule="evenodd" /></svg>
                        Download PDF
                    </a>
                    <button
                        v-if="invoice.status !== 'Fully Paid'"
                        @click="showPaymentModal = true"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        Record Payment
                    </button>
                </div>
            </div>

            <!-- Invoice Document -->
            <div id="invoice-print-area" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 relative">

                <!-- Paid Watermark -->
                <div v-if="invoice.status === 'Fully Paid'" class="pointer-events-none absolute right-8 top-20 -rotate-12 select-none opacity-[0.04]">
                    <p class="text-[120px] font-black leading-none text-emerald-600 ring-[12px] ring-emerald-600 rounded-3xl px-8 py-4">PAID</p>
                </div>

                <div class="p-8 sm:p-10">

                    <!-- Company Header + Invoice Label -->
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
                            <p class="text-2xl font-bold tracking-tight text-gray-900">INVOICE</p>
                            <p class="mt-1 text-sm font-semibold text-gray-500"># {{ invoice.public_id }}</p>
                        </div>
                    </div>

                    <!-- Meta: Bill To + Details -->
                    <div class="mt-8 grid gap-8 sm:grid-cols-2">
                        <div>
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Bill To</p>
                            <template v-if="invoice.customer">
                                <p class="text-sm font-bold text-gray-900">{{ invoice.customer.name }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ invoice.customer.phone || '-' }}</p>
                                <p class="text-xs text-gray-500">{{ invoice.customer.email }}</p>
                            </template>
                            <template v-else>
                                <p class="text-sm font-bold text-gray-900">{{ invoice.manual_customer_data?.name || 'Walk-in Customer' }}</p>
                                <p v-if="invoice.manual_customer_data?.address" class="mt-0.5 text-xs text-gray-500 whitespace-pre-line">{{ invoice.manual_customer_data.address }}</p>
                            </template>
                        </div>
                        <div class="space-y-3 sm:text-right">
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Invoice Date</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ invoiceDate }}</p>
                            </div>
                            <div v-if="invoice.subject">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Subject</p>
                                <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ invoice.subject }}</p>
                            </div>
                            <div>
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400">Status</p>
                                <p class="mt-0.5 text-sm font-semibold" :class="statusMeta.text">{{ invoice.status }}</p>
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
                                <tr v-for="(item, index) in invoice.items" :key="index" class="border-b border-gray-50">
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
                                <span class="font-semibold tabular-nums text-gray-900">{{ formatRM(invoice.sub_total) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-sm">
                                <span class="text-gray-500">Total Paid</span>
                                <span class="font-semibold tabular-nums text-emerald-600">{{ formatRM(invoice.paid_amount || 0) }}</span>
                            </div>
                            <div class="mt-3 flex items-center justify-between rounded-xl bg-gray-900 px-5 py-4">
                                <span class="text-xs font-bold uppercase tracking-widest text-gray-400">Balance Due</span>
                                <span class="text-xl font-bold tabular-nums text-white">{{ formatRM(balanceDue) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Notes & Terms -->
                    <div v-if="invoice.notes || invoice.terms" class="mt-8 grid gap-6 border-t border-gray-100 pt-8 sm:grid-cols-2">
                        <div v-if="invoice.notes">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Notes</p>
                            <p class="text-xs text-gray-600 leading-relaxed whitespace-pre-line">{{ invoice.notes }}</p>
                        </div>
                        <div v-if="invoice.terms">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-2">Payment Terms</p>
                            <p class="text-xs text-gray-500 leading-relaxed whitespace-pre-line">{{ invoice.terms }}</p>
                        </div>
                    </div>

                </div>
            </div>
        </div>

        <PaymentModal
            :show="showPaymentModal"
            :invoice="invoice"
            :workspace="workspace"
            @close="showPaymentModal = false"
        />
    </WorkspaceLayout>
</template>

<style>
@media print {
    body * { visibility: hidden; }
    #invoice-print-area, #invoice-print-area * { visibility: visible; }
    #invoice-print-area {
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
    .absolute { position: absolute !important; }
}
</style>
