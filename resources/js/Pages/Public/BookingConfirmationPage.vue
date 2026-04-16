<script setup>
import { Head } from '@inertiajs/vue3';

const props = defineProps({
    booking: { type: Object, required: true },
});

const b = props.booking;

const formatPrice = (price) => 'RM ' + Number(price).toLocaleString('en-MY', { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Booking Confirmed — TravelRocket" />

    <div class="min-h-screen bg-gray-50">

        <!-- Navbar -->
        <header class="bg-white border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between">
                    <a href="/" class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">TR</span>
                        <span class="text-base font-bold tracking-tight text-gray-900">TravelRocket</span>
                    </a>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-2xl px-4 py-12 sm:px-6 lg:px-8">

            <!-- Success Icon -->
            <div class="text-center mb-8">
                <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-emerald-100">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-10 w-10 text-emerald-600">
                        <path fill-rule="evenodd" d="M2.25 12c0-5.385 4.365-9.75 9.75-9.75s9.75 4.365 9.75 9.75-4.365 9.75-9.75 9.75S2.25 17.385 2.25 12zm13.36-1.814a.75.75 0 10-1.22-.872l-3.236 4.53L9.53 12.22a.75.75 0 00-1.06 1.06l2.25 2.25a.75.75 0 001.14-.094l3.75-5.25z" clip-rule="evenodd" />
                    </svg>
                </div>
                <h1 class="mt-4 text-2xl font-extrabold text-gray-900 sm:text-3xl">Booking Confirmed!</h1>
                <p class="mt-2 text-sm text-gray-500">Your travel booking has been successfully processed.</p>
            </div>

            <!-- Booking Card -->
            <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">

                <!-- Booking Number Header -->
                <div class="bg-blue-600 px-6 py-4 text-center">
                    <p class="text-xs font-semibold uppercase tracking-widest text-blue-200">Booking Reference</p>
                    <p class="mt-1 text-2xl font-extrabold tracking-wider text-white">{{ b.booking_number }}</p>
                </div>

                <div class="p-6 space-y-6">

                    <!-- Package Info -->
                    <div class="rounded-xl bg-gray-50 p-4">
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-2">Package</p>
                        <h2 class="text-lg font-bold text-gray-900">{{ b.package.name }}</h2>
                        <div class="mt-2 flex flex-wrap gap-3 text-xs text-gray-500">
                            <span v-if="b.package.destination" class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="m7.539 14.841.003.003.002.002a.755.755 0 00.912 0l.002-.002.003-.003.012-.009a5.57 5.57 0 00.19-.153 15.588 15.588 0 002.046-2.082c1.101-1.362 2.291-3.342 2.291-5.597A5 5 0 003 7c0 2.255 1.19 4.235 2.291 5.597a15.591 15.591 0 002.046 2.082 8.916 8.916 0 00.19.153l.012.01zM8 8.5a1.5 1.5 0 100-3 1.5 1.5 0 000 3z" clip-rule="evenodd" /></svg>
                                {{ b.package.destination }}
                            </span>
                            <span v-if="b.package.category" class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M4.5 2A2.5 2.5 0 002 4.5v2.879a2.5 2.5 0 00.732 1.767l4.5 4.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-4.5-4.5A2.5 2.5 0 007.38 2H4.5zM5 6a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                {{ b.package.category }}
                            </span>
                            <span v-if="b.package.start_date" class="flex items-center gap-1">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M5.75 7.5a.75.75 0 100 1.5.75.75 0 000-1.5zM5 10.25a.75.75 0 011.5 0 .75.75 0 01-1.5 0zM7.25 7.5a.75.75 0 100 1.5.75.75 0 000-1.5z" /><path fill-rule="evenodd" d="M4.75 1a.75.75 0 01.75.75V3h5V1.75a.75.75 0 011.5 0V3h.25A2.75 2.75 0 0115 5.75v7.5A2.75 2.75 0 0112.25 16h-8.5A2.75 2.75 0 011 13.25v-7.5A2.75 2.75 0 013.75 3H4V1.75A.75.75 0 014.75 1zM2.5 7.5v5.75c0 .69.56 1.25 1.25 1.25h8.5c.69 0 1.25-.56 1.25-1.25V7.5h-11z" clip-rule="evenodd" /></svg>
                                {{ b.package.start_date }} — {{ b.package.end_date }}
                            </span>
                        </div>
                    </div>

                    <!-- Guest Details -->
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-400 mb-3">Guest Details</p>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500">Name</p>
                                <p class="text-sm font-semibold text-gray-900">{{ b.buyer_name }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Email</p>
                                <p class="text-sm font-semibold text-gray-900">{{ b.buyer_email }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Phone</p>
                                <p class="text-sm font-semibold text-gray-900">{{ b.buyer_phone }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500">Passengers</p>
                                <p class="text-sm font-semibold text-gray-900">{{ b.total_pax }} pax</p>
                            </div>
                        </div>
                    </div>

                    <!-- Payment Summary -->
                    <div class="rounded-xl bg-blue-50 p-4 space-y-3">
                        <p class="text-xs font-bold uppercase tracking-wider text-blue-600">Payment Summary</p>

                        <div class="flex justify-between text-sm">
                            <span class="text-gray-600">Total Package Price</span>
                            <span class="font-semibold text-gray-900">{{ formatPrice(b.total_price) }}</span>
                        </div>

                        <div v-if="b.payment" class="flex justify-between text-sm">
                            <span class="text-gray-600">
                                {{ b.payment.payment_type === 'deposit' ? 'Deposit Paid' : 'Full Payment' }}
                            </span>
                            <span class="font-bold text-emerald-600">- {{ formatPrice(b.payment.amount) }}</span>
                        </div>

                        <div v-if="b.balance_due > 0" class="flex justify-between text-sm border-t border-blue-200 pt-2">
                            <span class="font-semibold text-gray-900">Balance Due</span>
                            <span class="font-bold text-amber-600">{{ formatPrice(b.balance_due) }}</span>
                        </div>

                        <div v-if="b.payment?.gateway_reference" class="flex justify-between text-xs pt-1">
                            <span class="text-gray-400">Transaction Ref</span>
                            <span class="font-mono text-gray-500">{{ b.payment.gateway_reference }}</span>
                        </div>
                    </div>

                    <!-- Status Badges -->
                    <div class="flex flex-wrap gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                            :class="b.booking_status === 'confirmed' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-600'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M8 15A7 7 0 108 1a7 7 0 000 14zm3.844-8.791a.75.75 0 00-1.188-.918l-3.7 4.79-1.649-1.833a.75.75 0 10-1.114 1.004l2.25 2.5a.75.75 0 001.15-.043l4.25-5.5z" clip-rule="evenodd" /></svg>
                            Booking {{ b.booking_status }}
                        </span>
                        <span class="inline-flex items-center gap-1 rounded-full px-3 py-1 text-xs font-bold"
                            :class="{
                                'bg-emerald-100 text-emerald-700': b.payment_status === 'paid',
                                'bg-amber-100 text-amber-700': b.payment_status === 'partial',
                                'bg-red-100 text-red-700': b.payment_status === 'unpaid',
                            }">
                            Payment {{ b.payment_status }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="mt-8 flex flex-col items-center gap-3">
                <a href="/" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-7 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                    Browse More Packages
                </a>
                <p class="text-xs text-gray-400">A confirmation email will be sent to {{ b.buyer_email }}</p>
            </div>
        </main>
    </div>
</template>
