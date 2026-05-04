<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    booking: { type: Object, required: true },
    paymentMethods: { type: Array, required: true },
    banks: { type: Array, default: () => [] },
    wallets: { type: Array, default: () => [] },
});

const form = useForm({
    payment_method: 'fpx',
    bank: props.banks[0] ?? '',
    wallet: props.wallets[0] ?? '',
});

const formatPrice = (price) => 'RM ' + Number(price).toLocaleString('en-MY', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
});

const selectedMethod = computed(() => props.paymentMethods.find((method) => method.id === form.payment_method));

const submit = () => {
    form.post(`/booking/${props.booking.id}/payment/complete`);
};
</script>

<template>
    <Head title="Dummy Payment Gateway - TravelRocket" />

    <div class="min-h-screen bg-[#eef2f1] text-slate-900">
        <header class="border-b border-slate-200 bg-white">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <a href="/" class="flex items-center gap-3">
                    <span class="flex h-9 w-9 items-center justify-center rounded-lg bg-slate-900 text-xs font-bold text-white">TR</span>
                    <span class="text-sm font-bold tracking-tight text-slate-900 sm:text-base">TravelRocket Checkout</span>
                </a>

                <div class="flex items-center gap-3">
                    <div class="rounded-md border border-[#0a6fb8] bg-white px-3 py-1.5">
                        <span class="text-lg font-black italic leading-none text-[#0a6fb8]">FPX</span>
                        <span class="ml-1 text-[10px] font-bold text-[#56a944]">DEMO</span>
                    </div>
                    <span class="hidden text-xs font-semibold uppercase tracking-widest text-slate-500 sm:inline">Secure Sandbox</span>
                </div>
            </div>
        </header>

        <main class="mx-auto grid max-w-6xl gap-6 px-4 py-8 sm:px-6 lg:grid-cols-[1fr_360px] lg:px-8">
            <section class="space-y-5">
                <div class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Dummy Payment Gateway</p>
                            <h1 class="mt-2 text-2xl font-extrabold text-slate-950">Complete your package payment</h1>
                            <p class="mt-2 max-w-2xl text-sm leading-6 text-slate-600">
                                This sandbox screen is for walkthrough only. Choose any payment method and approve to simulate a successful purchase.
                            </p>
                        </div>

                        <div class="shrink-0 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-right">
                            <p class="text-[10px] font-bold uppercase tracking-widest text-emerald-700">Payable Amount</p>
                            <p class="mt-1 text-xl font-extrabold text-emerald-800">{{ formatPrice(booking.payment.amount) }}</p>
                        </div>
                    </div>
                </div>

                <form class="space-y-5" @submit.prevent="submit">
                    <div class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div class="mb-4 flex items-center justify-between gap-3">
                            <div>
                                <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500">Payment Method</h2>
                                <p class="mt-1 text-sm text-slate-500">{{ selectedMethod?.description }}</p>
                            </div>
                            <span class="rounded bg-slate-100 px-2.5 py-1 text-xs font-bold text-slate-600">Sandbox</span>
                        </div>

                        <div class="grid gap-3 md:grid-cols-3">
                            <label
                                v-for="method in paymentMethods"
                                :key="method.id"
                                class="cursor-pointer border p-4 transition"
                                :class="form.payment_method === method.id ? 'border-slate-900 bg-slate-50 shadow-sm' : 'border-slate-200 bg-white hover:border-slate-400'"
                            >
                                <input v-model="form.payment_method" type="radio" class="sr-only" :value="method.id" />
                                <div class="flex items-start justify-between gap-3">
                                    <div>
                                        <p class="text-sm font-extrabold text-slate-950">{{ method.name }}</p>
                                        <p class="mt-2 text-xs leading-5 text-slate-500">{{ method.description }}</p>
                                    </div>
                                    <span
                                        class="mt-0.5 h-4 w-4 rounded-full border"
                                        :class="form.payment_method === method.id ? 'border-slate-900 bg-slate-900 ring-2 ring-slate-200' : 'border-slate-300'"
                                    ></span>
                                </div>
                            </label>
                        </div>
                    </div>

                    <div class="border border-slate-200 bg-white p-5 shadow-sm sm:p-6">
                        <div v-if="form.payment_method === 'fpx'" class="space-y-4">
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500">FPX Bank Selection</h2>
                                    <p class="mt-1 text-sm text-slate-500">Pick any bank for the demo redirect.</p>
                                </div>
                                <div class="rounded border border-[#0a6fb8] px-3 py-1">
                                    <span class="text-base font-black italic text-[#0a6fb8]">FPX</span>
                                </div>
                            </div>

                            <select
                                v-model="form.bank"
                                class="w-full border border-slate-300 bg-white px-4 py-3 text-sm font-semibold text-slate-900 focus:border-slate-900 focus:outline-none"
                            >
                                <option v-for="bank in banks" :key="bank" :value="bank">{{ bank }}</option>
                            </select>
                        </div>

                        <div v-else-if="form.payment_method === 'card'" class="space-y-4">
                            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500">Card Authorization</h2>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <input value="4242 4242 4242 4242" readonly class="border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700" />
                                <input value="Ahmad bin Ali" readonly class="border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700" />
                                <input value="12/28" readonly class="border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700" />
                                <input value="123" readonly class="border border-slate-300 bg-slate-50 px-4 py-3 text-sm font-semibold text-slate-700" />
                            </div>
                        </div>

                        <div v-else class="space-y-4">
                            <h2 class="text-sm font-bold uppercase tracking-widest text-slate-500">E-Wallet Selection</h2>
                            <div class="grid gap-3 sm:grid-cols-3">
                                <label
                                    v-for="wallet in wallets"
                                    :key="wallet"
                                    class="cursor-pointer border px-4 py-3 text-center text-sm font-bold transition"
                                    :class="form.wallet === wallet ? 'border-slate-900 bg-slate-50 text-slate-950' : 'border-slate-200 text-slate-600 hover:border-slate-400'"
                                >
                                    <input v-model="form.wallet" type="radio" class="sr-only" :value="wallet" />
                                    {{ wallet }}
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <a
                            :href="`/packages/${booking.package?.id ?? ''}`"
                            class="text-center text-sm font-bold text-slate-500 transition hover:text-slate-900"
                        >
                            Cancel demo payment
                        </a>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="border border-emerald-700 bg-emerald-700 px-6 py-3 text-sm font-extrabold text-white shadow-sm transition hover:bg-emerald-800 disabled:cursor-not-allowed disabled:opacity-60"
                        >
                            <span v-if="form.processing">Approving payment...</span>
                            <span v-else>Approve Dummy Payment</span>
                        </button>
                    </div>
                </form>
            </section>

            <aside class="space-y-4">
                <div class="border border-slate-200 bg-white p-5 shadow-sm">
                    <p class="text-xs font-bold uppercase tracking-widest text-slate-400">Order Summary</p>
                    <h2 class="mt-3 text-lg font-extrabold text-slate-950">{{ booking.package.name }}</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Booking Ref</span>
                            <span class="font-mono font-bold text-slate-900">{{ booking.booking_number }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Customer</span>
                            <span class="text-right font-semibold text-slate-900">{{ booking.buyer_name }}</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Passengers</span>
                            <span class="font-semibold text-slate-900">{{ booking.total_pax }} pax</span>
                        </div>
                        <div class="flex justify-between gap-4">
                            <span class="text-slate-500">Payment Type</span>
                            <span class="font-semibold capitalize text-slate-900">{{ booking.payment.payment_type }}</span>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-slate-200 pt-4">
                        <div class="flex justify-between text-sm">
                            <span class="font-bold text-slate-600">Total Package</span>
                            <span class="font-extrabold text-slate-950">{{ formatPrice(booking.total_price) }}</span>
                        </div>
                        <div v-if="booking.balance_due > 0" class="mt-2 flex justify-between text-sm">
                            <span class="text-slate-500">Balance After Payment</span>
                            <span class="font-bold text-amber-700">{{ formatPrice(booking.balance_due) }}</span>
                        </div>
                        <div class="mt-4 flex justify-between border-t border-slate-200 pt-4">
                            <span class="text-sm font-extrabold text-slate-950">Pay Now</span>
                            <span class="text-xl font-black text-emerald-700">{{ formatPrice(booking.payment.amount) }}</span>
                        </div>
                    </div>
                </div>

                <div class="border border-slate-200 bg-white p-5 text-sm leading-6 text-slate-600 shadow-sm">
                    <p class="font-bold text-slate-900">Demo notes</p>
                    <p class="mt-2">All payment choices approve successfully and generate a fake transaction reference for presentation.</p>
                </div>
            </aside>
        </main>
    </div>
</template>
