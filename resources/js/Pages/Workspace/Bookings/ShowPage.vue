<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    booking: { type: Object, required: true },
    invoiceSummary: { type: Object, required: true },
});

const totalPaid = computed(() => Number(props.booking.payments?.reduce((s, p) => s + Number(p.amount || 0), 0) ?? 0));
const lastPayment = computed(() => {
    const list = props.booking.payments ?? [];
    return list.length ? list[list.length - 1] : null;
});

const paymentForm = useForm({
    amount: '',
    payment_method: 'cash',
    payment_date: new Date().toISOString().slice(0, 10),
    receipt: null,
});

const handleReceiptFile = (e) => { paymentForm.receipt = e.target.files?.[0] ?? null; };

const submitPayment = () => {
    paymentForm.post(`/workspace/${props.workspace.slug}/bookings/${props.booking.id}/payments`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('amount', 'receipt'),
    });
};

const sendTourismLetter = () => {
    router.post(`/workspace/${props.workspace.slug}/bookings/${props.booking.id}/tourism-letter/email`);
};

const printInvoice = () => window.print();

const bookingStatusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        case 'pending': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
        case 'cancelled': return 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
        default: return 'bg-gray-100 text-gray-600 ring-1 ring-gray-900/10';
    }
};

const paymentStatusClass = (status) => {
    switch (status) {
        case 'paid': return 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20';
        case 'partial': return 'bg-amber-50 text-amber-700 ring-1 ring-amber-600/20';
        case 'unpaid': return 'bg-red-50 text-red-700 ring-1 ring-red-600/20';
        default: return 'bg-gray-100 text-gray-600 ring-1 ring-gray-900/10';
    }
};

const balancePct = computed(() => {
    const total = Number(props.invoiceSummary.subtotal || 0);
    if (total <= 0) return 0;
    return Math.min(100, Math.round((totalPaid.value / total) * 100));
});
</script>

<template>
    <Head :title="`${booking.booking_number} — ${workspace.name}`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="no-print mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ booking.booking_number }}</h1>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide" :class="bookingStatusClass(booking.booking_status)">{{ booking.booking_status }}</span>
                        <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide" :class="paymentStatusClass(booking.payment_status)">{{ booking.payment_status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ booking.lead_customer?.full_name }} · {{ booking.package?.name }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}/tourism-letter/download`" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" clip-rule="evenodd" /></svg>
                        Download Letter
                    </a>
                    <button @click="sendTourismLetter" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.161V6a2 2 0 00-2-2H3z" /><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" /></svg>
                        Email Letter
                    </button>
                    <button @click="printInvoice" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M5 2.75C5 1.784 5.784 1 6.75 1h6.5c.966 0 1.75.784 1.75 1.75v3.552c.377.046.752.097 1.126.153A2.212 2.212 0 0118 8.653v4.097A2.25 2.25 0 0115.75 15h-.75v3.25c0 .966-.784 1.75-1.75 1.75h-6.5A1.75 1.75 0 015 18.25V15h-.75A2.25 2.25 0 012 12.75V8.653c0-1.082.775-2.034 1.874-2.198.374-.056.749-.107 1.126-.153V2.75zm1.5 0v3.379a49.28 49.28 0 017 0V2.75a.25.25 0 00-.25-.25h-6.5a.25.25 0 00-.25.25zm-1.665 5.722a.75.75 0 000 1.5H6.5a.75.75 0 000-1.5H4.835zM6.5 14h7a.25.25 0 01.25.25v4a.25.25 0 01-.25.25h-6.5a.25.25 0 01-.25-.25v-4A.25.25 0 016.75 14z" clip-rule="evenodd" /></svg>
                        Print
                    </button>
                    <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}/edit`" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                        Edit
                    </a>
                    <a :href="`/workspace/${workspace.slug}/bookings`" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                        Back
                    </a>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_360px]">

                <!-- Main content -->
                <div class="space-y-6">

                    <!-- Finance stats -->
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Total Price</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-gray-900">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.subtotal || 0).toFixed(2) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Total Paid</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-emerald-600">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.total_paid || 0).toFixed(2) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Balance Due</p>
                            <p class="mt-2 text-lg font-bold tabular-nums" :class="Number(invoiceSummary.balance_due || 0) > 0 ? 'text-red-600' : 'text-gray-900'">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.balance_due || 0).toFixed(2) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Payment Progress</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-gray-900">{{ balancePct }}%</p>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full transition-all" :class="balancePct >= 100 ? 'bg-emerald-500' : balancePct >= 50 ? 'bg-amber-500' : 'bg-red-500'" :style="{ width: balancePct + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Customer & Package -->
                    <div class="grid gap-4 sm:grid-cols-2">
                        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Lead Customer</h2>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ booking.lead_customer?.full_name || 'N/A' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ booking.lead_customer?.phone || '-' }} · {{ booking.lead_customer?.email || '-' }}</p>
                            </div>
                        </div>
                        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Package</h2>
                            </div>
                            <div class="px-6 py-4">
                                <p class="text-sm font-semibold text-gray-900">{{ booking.package?.name || 'N/A' }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ booking.package?.destination || '-' }} · RM{{ Number(booking.package?.price || 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Travel Details -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Travel Details</h2>
                        </div>
                        <div class="grid gap-4 px-6 py-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <p class="text-xs font-medium text-gray-500">Departure</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ booking.departure_date || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Return</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ booking.return_date || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Flight / Airline</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ booking.flight_name || '-' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-medium text-gray-500">Flight Number</p>
                                <p class="mt-1 text-sm font-medium text-gray-900">{{ booking.flight_number || '-' }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Passengers -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Passengers</h2>
                            <span class="text-xs text-gray-500">{{ booking.passengers?.length ?? 0 }} pax</span>
                        </div>
                        <div v-if="booking.passengers?.length" class="divide-y divide-gray-100">
                            <div v-for="pax in booking.passengers" :key="pax.id" class="flex items-center justify-between px-6 py-3">
                                <p class="text-sm font-medium text-gray-900">{{ pax.full_name }}</p>
                                <p class="text-xs tabular-nums text-gray-500">{{ pax.passport_number || '-' }}</p>
                            </div>
                        </div>
                        <div v-else class="px-6 py-6 text-center">
                            <p class="text-sm text-gray-500">No passengers listed.</p>
                        </div>
                    </div>

                    <!-- Payment Timeline -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Payment Timeline</h2>
                            <span class="text-xs text-gray-500">Total: RM{{ totalPaid.toFixed(2) }}</span>
                        </div>
                        <div v-if="booking.payments?.length" class="divide-y divide-gray-100">
                            <div v-for="payment in booking.payments" :key="payment.id" class="flex items-center justify-between px-6 py-3.5">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">RM{{ Number(payment.amount || 0).toFixed(2) }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ payment.payment_method }} · {{ payment.payment_date || '-' }}</p>
                                </div>
                                <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" class="text-xs font-medium text-gray-900 underline decoration-gray-300 underline-offset-4 hover:decoration-gray-900">Receipt</a>
                            </div>
                        </div>
                        <div v-else class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500">No payments recorded yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 xl:sticky xl:top-24 self-start">

                    <!-- Invoice Summary (printable) -->
                    <div class="print-card overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Invoice Summary</h3>
                        </div>
                        <div class="divide-y divide-gray-100 text-sm">
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-gray-500">Invoice Date</span>
                                <span class="font-medium text-gray-900">{{ invoiceSummary.issued_at }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-gray-500">Booking #</span>
                                <span class="font-medium text-gray-900">{{ booking.booking_number }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-gray-500">Total Pax</span>
                                <span class="font-medium text-gray-900">{{ booking.total_pax }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-gray-500">Subtotal</span>
                                <span class="font-medium tabular-nums text-gray-900">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.subtotal || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-gray-500">Total Paid</span>
                                <span class="font-medium tabular-nums text-emerald-600">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.total_paid || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3 bg-gray-50">
                                <span class="font-semibold text-gray-900">Balance Due</span>
                                <span class="font-bold tabular-nums" :class="Number(invoiceSummary.balance_due || 0) > 0 ? 'text-red-600' : 'text-gray-900'">{{ invoiceSummary.currency }}{{ Number(invoiceSummary.balance_due || 0).toFixed(2) }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Record Payment inline -->
                    <div class="no-print overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Record Payment</h3>
                        </div>
                        <form class="space-y-3 p-5" @submit.prevent="submitPayment">
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Amount (RM)</label>
                                <input v-model="paymentForm.amount" type="number" min="0" step="0.01" placeholder="0.00" class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Method</label>
                                <select v-model="paymentForm.payment_method" class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                    <option value="cash">Cash</option>
                                    <option value="transfer">Transfer</option>
                                    <option value="gateway">Gateway</option>
                                </select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Date</label>
                                <input v-model="paymentForm.payment_date" type="date" class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium text-gray-600">Receipt</label>
                                <input type="file" accept=".jpg,.jpeg,.png,.pdf" class="w-full rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 file:mr-2 file:rounded-md file:border-0 file:bg-gray-900 file:px-2 file:py-0.5 file:text-xs file:font-semibold file:text-white" @change="handleReceiptFile" />
                            </div>
                            <button type="submit" class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-gray-800 disabled:opacity-50" :disabled="paymentForm.processing">Add Payment</button>
                        </form>
                    </div>

                    <!-- Quick actions -->
                    <div class="no-print flex flex-col gap-2">
                        <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}/edit`" class="rounded-lg bg-gray-900 px-5 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">Edit Booking</a>
                        <a :href="`/workspace/${workspace.slug}/invoices`" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Open Invoices</a>
                        <a :href="`/workspace/${workspace.slug}/bookings`" class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Back to Bookings</a>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>

<style scoped>
@media print {
    .no-print { display: none !important; }
    .print-card { border-color: #d1d5db !important; background: #fff !important; }
}
</style>
