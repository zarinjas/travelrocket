<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    booking: { type: Object, default: null },
    bookingStatuses: { type: Array, default: () => [] },
    packages: { type: Array, default: () => [] },
    customers: { type: Array, default: () => [] },
    bookingDetail: { type: Object, default: null },
    formAction: { type: String, required: true },
    formMethod: { type: String, required: true },
});

const form = useForm({
    package_id: props.booking?.package_id ?? props.packages[0]?.id ?? '',
    lead_customer_id: props.booking?.lead_customer_id ?? props.customers[0]?.id ?? '',
    passenger_ids: props.booking?.passenger_ids ?? [],
    total_price: props.booking?.total_price ?? '',
    booking_status: props.booking?.booking_status ?? props.bookingStatuses[0] ?? 'pending',
    departure_date: props.booking?.departure_date ?? '',
    return_date: props.booking?.return_date ?? '',
    flight_name: props.booking?.flight_name ?? '',
    flight_number: props.booking?.flight_number ?? '',
});

const paymentForm = useForm({
    amount: '',
    payment_method: 'cash',
    payment_date: new Date().toISOString().slice(0, 10),
    receipt: null,
});

const selectedPackage = computed(() => props.packages.find((p) => Number(p.id) === Number(form.package_id)) ?? null);
const seatsRequired = computed(() => 1 + form.passenger_ids.length);
const seatsRemaining = computed(() => Number(selectedPackage.value?.available_seats ?? 0));
const seatStatusLabel = computed(() => {
    if (!selectedPackage.value) return 'Select a package to view availability.';
    if (selectedPackage.value.is_sold_out) return 'Sold out';
    return `${selectedPackage.value.available_seats} seats available`;
});

const totalPaid = computed(() => Number(props.bookingDetail?.payments?.reduce((acc, p) => acc + Number(p.amount || 0), 0) ?? 0));

const submit = () => {
    if (props.formMethod === 'put') { form.put(props.formAction); return; }
    form.post(props.formAction);
};

const handleReceiptFile = (e) => { paymentForm.receipt = e.target.files?.[0] ?? null; };

const submitPayment = () => {
    if (!props.bookingDetail?.id) return;
    paymentForm.post(`/workspace/${props.workspace.slug}/bookings/${props.bookingDetail.id}/payments`, {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => paymentForm.reset('amount', 'receipt'),
    });
};

const bookingStatusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-50 text-emerald-700';
        case 'pending': return 'bg-amber-50 text-amber-700';
        case 'cancelled': return 'bg-red-50 text-red-700';
        default: return 'bg-gray-100 text-gray-600';
    }
};
</script>

<template>
    <Head :title="booking ? 'Edit Booking' : 'Create Booking'" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ booking ? 'Edit Booking' : 'Create Booking' }}</h1>
                    <p class="mt-1 text-sm text-gray-500">Connect a package with travelers and track payment progress.</p>
                </div>
                <a :href="`/workspace/${workspace.slug}/bookings`" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                    Back
                </a>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_360px]">

                <!-- Main Form -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Booking Details</h2>
                    </div>

                    <form class="space-y-5 p-6" @submit.prevent="submit">

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Package</label>
                                <select v-model="form.package_id" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                    <option v-for="pkg in packages" :key="pkg.id" :value="pkg.id" :disabled="pkg.is_sold_out && Number(pkg.id) !== Number(form.package_id)">
                                        {{ pkg.category }} · {{ pkg.name }} · RM{{ pkg.price }} · {{ pkg.available_seats }} seats
                                    </option>
                                </select>
                                <p v-if="form.errors.package_id" class="mt-1.5 text-sm text-red-600">{{ form.errors.package_id }}</p>

                                <!-- Seat availability card -->
                                <div v-if="selectedPackage" class="mt-3 rounded-lg bg-gray-50 p-3 ring-1 ring-inset ring-gray-200">
                                    <div class="flex items-center justify-between">
                                        <span class="text-xs text-gray-500">Seat availability</span>
                                        <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase" :class="selectedPackage.is_sold_out ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'">{{ seatStatusLabel }}</span>
                                    </div>
                                    <p class="mt-1.5 text-xs text-gray-500">Seats needed: {{ seatsRequired }}</p>
                                    <p v-if="seatsRequired > seatsRemaining && !selectedPackage.is_sold_out" class="mt-1 text-xs text-amber-600">Not enough seats. Reduce passengers or pick another package.</p>
                                </div>
                            </div>

                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Lead Customer</label>
                                <select v-model="form.lead_customer_id" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                    <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }}</option>
                                </select>
                                <p v-if="form.errors.lead_customer_id" class="mt-1.5 text-sm text-red-600">{{ form.errors.lead_customer_id }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Total Price (RM)</label>
                                <input v-model="form.total_price" type="number" min="0" step="0.01" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                <p v-if="form.errors.total_price" class="mt-1.5 text-sm text-red-600">{{ form.errors.total_price }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Booking Status</label>
                                <select v-model="form.booking_status" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                    <option v-for="s in bookingStatuses" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                                </select>
                                <p v-if="form.errors.booking_status" class="mt-1.5 text-sm text-red-600">{{ form.errors.booking_status }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Departure Date</label>
                                <input v-model="form.departure_date" type="date" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                <p v-if="form.errors.departure_date" class="mt-1.5 text-sm text-red-600">{{ form.errors.departure_date }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Return Date</label>
                                <input v-model="form.return_date" type="date" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                <p v-if="form.errors.return_date" class="mt-1.5 text-sm text-red-600">{{ form.errors.return_date }}</p>
                            </div>
                        </div>

                        <div class="grid gap-5 sm:grid-cols-2">
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Flight / Airline</label>
                                <input v-model="form.flight_name" type="text" placeholder="e.g. Malaysia Airlines" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                <p v-if="form.errors.flight_name" class="mt-1.5 text-sm text-red-600">{{ form.errors.flight_name }}</p>
                            </div>
                            <div>
                                <label class="mb-1.5 block text-sm font-medium text-gray-700">Flight Number</label>
                                <input v-model="form.flight_number" type="text" placeholder="e.g. MH123" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                <p v-if="form.errors.flight_number" class="mt-1.5 text-sm text-red-600">{{ form.errors.flight_number }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-1.5 block text-sm font-medium text-gray-700">Additional Passengers</label>
                            <select v-model="form.passenger_ids" multiple class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" style="min-height: 120px">
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.full_name }}</option>
                            </select>
                            <p class="mt-1.5 text-xs text-gray-500">Lead customer is auto-included. Hold Cmd/Ctrl to select multiple.</p>
                            <p class="mt-0.5 text-xs text-gray-500">Seats needed: {{ seatsRequired }}</p>
                            <p v-if="form.errors.passenger_ids" class="mt-1.5 text-sm text-red-600">{{ form.errors.passenger_ids }}</p>
                        </div>

                        <div class="flex gap-3 border-t border-gray-100 pt-5">
                            <button type="submit" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50" :disabled="form.processing">
                                {{ booking ? 'Update Booking' : 'Save Booking' }}
                            </button>
                            <a v-if="bookingDetail" :href="`/workspace/${workspace.slug}/bookings/${bookingDetail.id}`" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">View Booking</a>
                            <a :href="`/workspace/${workspace.slug}/bookings`" class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">

                    <!-- Finance overview -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Payment Overview</h3>
                        </div>
                        <div class="grid grid-cols-2 divide-x divide-gray-100 text-center">
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Paid</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-emerald-600">RM{{ totalPaid.toFixed(2) }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Balance</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums" :class="Number(bookingDetail?.balance_due ?? 0) > 0 ? 'text-red-600' : 'text-gray-900'">RM{{ Number(bookingDetail?.balance_due ?? 0).toFixed(2) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Record payment -->
                    <div v-if="bookingDetail" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
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

                        <!-- Payment history -->
                        <div v-if="bookingDetail.payments?.length" class="border-t border-gray-100">
                            <div class="divide-y divide-gray-100">
                                <div v-for="payment in bookingDetail.payments" :key="payment.id" class="flex items-center justify-between px-5 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">RM{{ Number(payment.amount).toFixed(2) }}</p>
                                        <p class="mt-0.5 text-xs text-gray-500">{{ payment.payment_method }} · {{ payment.payment_date }}</p>
                                    </div>
                                    <a v-if="payment.receipt_url" :href="payment.receipt_url" target="_blank" class="text-xs font-medium text-gray-900 underline decoration-gray-300 underline-offset-4 hover:decoration-gray-900">Receipt</a>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Tips -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Quick Tips</h3>
                        </div>
                        <div class="space-y-3 p-5 text-sm text-gray-600">
                            <p><strong class="text-gray-900">Seats:</strong> Lead customer counts as 1 pax. Additional passengers add to the total.</p>
                            <p><strong class="text-gray-900">Cancel:</strong> Cancelled bookings release their seats back to the package.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
