<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import SalesOverviewChart from '@/Components/Workspace/SalesOverviewChart.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    stats: { type: Object, required: true },
    dashboardMetrics: { type: Object, required: true },
    recentBookings: { type: Array, default: () => [] },
    salesChart: { type: Object, default: () => ({ days: [], weeklyTotal: 0, weeklyBookings: 0 }) },
    quotationMetrics: { type: Object, default: () => ({ expiring: 0, expired: 0 }) },
    inventoryMetrics: { type: Object, default: () => ({ available_seats: 0, sold_out_packages: 0, low_inventory_packages: 0 }) },
    upcomingDepartures: { type: Array, default: () => [] },
});

const formatRM = (v) => 'RM' + Number(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 });

// Month-over-month change
const bookingMoM = computed(() => {
    const curr = props.dashboardMetrics.bookings_this_month || 0;
    const prev = props.dashboardMetrics.bookings_last_month || 0;
    if (prev === 0) return curr > 0 ? 100 : 0;
    return Math.round(((curr - prev) / prev) * 100);
});

const bookingStatusClass = (status) => {
    switch (status) {
        case 'confirmed': return 'bg-emerald-50 text-emerald-700';
        case 'pending': return 'bg-amber-50 text-amber-700';
        case 'cancelled': return 'bg-red-50 text-red-700';
        default: return 'bg-gray-100 text-gray-600';
    }
};

const paymentStatusClass = (status) => {
    switch (status) {
        case 'paid': return 'bg-emerald-50 text-emerald-700';
        case 'partial': return 'bg-amber-50 text-amber-700';
        case 'unpaid': return 'bg-red-50 text-red-700';
        default: return 'bg-gray-100 text-gray-600';
    }
};
</script>

<template>
    <Head :title="`${workspace.name} Dashboard`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Dashboard</h1>
                    <p class="mt-1 text-sm text-gray-500">Overview of your travel business operations.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/workspace/${workspace.slug}/bookings/create`" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        New Booking
                    </a>
                </div>
            </div>

            <!-- KPI Cards -->
            <div class="mb-6 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <!-- Revenue -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Total Revenue</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">{{ formatRM(stats.revenue) }}</p>
                            <p class="mt-1 text-xs text-gray-500">From {{ stats.status_breakdown?.paid || 0 }} paid bookings</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-emerald-600"><path fill-rule="evenodd" d="M1 4a1 1 0 011-1h16a1 1 0 011 1v8a1 1 0 01-1 1H2a1 1 0 01-1-1V4zm12 4a3 3 0 11-6 0 3 3 0 016 0zM4 9a1 1 0 100-2 1 1 0 000 2zm13-1a1 1 0 11-2 0 1 1 0 012 0zM1.75 14.5a.75.75 0 000 1.5c4.417 0 8.693.603 12.749 1.73 1.111.309 2.251-.512 2.251-1.696v-.784a.75.75 0 00-1.5 0v.784a.272.272 0 01-.35.25A49.043 49.043 0 001.75 14.5z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Outstanding -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Outstanding Balance</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums" :class="Number(stats.outstanding) > 0 ? 'text-red-600' : 'text-gray-900'">{{ formatRM(stats.outstanding) }}</p>
                            <p class="mt-1 text-xs text-gray-500">Pending collections</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-amber-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-amber-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Bookings This Month -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Bookings This Month</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">{{ dashboardMetrics.bookings_this_month || 0 }}</p>
                            <p class="mt-1 text-xs" :class="bookingMoM >= 0 ? 'text-emerald-600' : 'text-red-600'">
                                <span v-if="bookingMoM >= 0">+</span>{{ bookingMoM }}% vs last month
                            </p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2zm-1 5.5c-.69 0-1.25.56-1.25 1.25v6.5c0 .69.56 1.25 1.25 1.25h10.5c.69 0 1.25-.56 1.25-1.25v-6.5c0-.69-.56-1.25-1.25-1.25H4.75z" clip-rule="evenodd" /></svg>
                        </div>
                    </div>
                </div>

                <!-- Active Customers -->
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-start justify-between">
                        <div>
                            <p class="text-xs font-medium text-gray-500">Active Customers</p>
                            <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">{{ stats.customers || 0 }}</p>
                            <p class="mt-1 text-xs text-gray-500">{{ dashboardMetrics.new_leads_today || 0 }} new today</p>
                        </div>
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-indigo-600"><path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z" /></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Main grid: Chart + Sidebar -->
            <div class="mb-6 grid gap-6 lg:grid-cols-[1fr_320px] xl:grid-cols-[1fr_360px]">

                <!-- Sales Chart -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Sales Overview</h2>
                        <div class="flex items-center gap-2">
                            <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-600">Last 7 days</span>
                            <a :href="`/workspace/${workspace.slug}/cashflow-command-center`" class="text-xs font-medium text-gray-500 hover:text-gray-900">View Reports →</a>
                        </div>
                    </div>
                    <div class="p-6">
                        <SalesOverviewChart
                            :days="salesChart?.days || []"
                            :weekly-total="salesChart?.weeklyTotal || 0"
                            :weekly-bookings="salesChart?.weeklyBookings || 0"
                            currency="RM"
                        />
                    </div>
                </div>

                <!-- Right sidebar widgets -->
                <div class="space-y-4">

                    <!-- Status Breakdown -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Booking Status</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-gray-600">Pending</span>
                                <span class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-amber-50 px-2 py-0.5 text-xs font-bold tabular-nums text-amber-700">{{ stats.status_breakdown?.pending || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-gray-600">Confirmed</span>
                                <span class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-bold tabular-nums text-emerald-700">{{ stats.status_breakdown?.confirmed || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-gray-600">Paid</span>
                                <span class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-blue-50 px-2 py-0.5 text-xs font-bold tabular-nums text-blue-700">{{ stats.status_breakdown?.paid || 0 }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-sm text-gray-600">Cancelled</span>
                                <span class="inline-flex min-w-[28px] items-center justify-center rounded-full bg-red-50 px-2 py-0.5 text-xs font-bold tabular-nums text-red-700">{{ stats.status_breakdown?.cancelled || 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <!-- Alerts -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Alerts</h3>
                        </div>
                        <div class="space-y-2 p-4">
                            <div v-if="quotationMetrics.expiring > 0" class="flex items-center gap-3 rounded-lg bg-amber-50 px-4 py-3 ring-1 ring-amber-200/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-amber-600"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                <div>
                                    <p class="text-xs font-semibold text-amber-800">{{ quotationMetrics.expiring }} quotation(s) expiring soon</p>
                                    <a :href="`/workspace/${workspace.slug}/quotations`" class="text-[10px] font-medium text-amber-700 underline">Review →</a>
                                </div>
                            </div>
                            <div v-if="quotationMetrics.expired > 0" class="flex items-center gap-3 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-red-600"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                <div>
                                    <p class="text-xs font-semibold text-red-800">{{ quotationMetrics.expired }} expired quotation(s)</p>
                                    <a :href="`/workspace/${workspace.slug}/quotations`" class="text-[10px] font-medium text-red-700 underline">View →</a>
                                </div>
                            </div>
                            <div v-if="inventoryMetrics.sold_out_packages > 0" class="flex items-center gap-3 rounded-lg bg-red-50 px-4 py-3 ring-1 ring-red-200/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-red-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                                <div>
                                    <p class="text-xs font-semibold text-red-800">{{ inventoryMetrics.sold_out_packages }} package(s) sold out</p>
                                    <a :href="`/workspace/${workspace.slug}/packages`" class="text-[10px] font-medium text-red-700 underline">Manage →</a>
                                </div>
                            </div>
                            <div v-if="inventoryMetrics.low_inventory_packages > 0" class="flex items-center gap-3 rounded-lg bg-amber-50 px-4 py-3 ring-1 ring-amber-200/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-amber-600"><path fill-rule="evenodd" d="M8.485 2.495c.673-1.167 2.357-1.167 3.03 0l6.28 10.875c.673 1.167-.17 2.625-1.516 2.625H3.72c-1.347 0-2.189-1.458-1.515-2.625L8.485 2.495zM10 5a.75.75 0 01.75.75v3.5a.75.75 0 01-1.5 0v-3.5A.75.75 0 0110 5zm0 9a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                                <div>
                                    <p class="text-xs font-semibold text-amber-800">{{ inventoryMetrics.low_inventory_packages }} package(s) low on seats</p>
                                    <a :href="`/workspace/${workspace.slug}/packages`" class="text-[10px] font-medium text-amber-700 underline">View →</a>
                                </div>
                            </div>
                            <div v-if="!quotationMetrics.expiring && !quotationMetrics.expired && !inventoryMetrics.sold_out_packages && !inventoryMetrics.low_inventory_packages" class="flex items-center gap-3 rounded-lg bg-emerald-50 px-4 py-3 ring-1 ring-emerald-200/50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 shrink-0 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                <p class="text-xs font-semibold text-emerald-800">All clear — no alerts right now.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Inventory</h3>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-gray-100 text-center">
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Packages</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">{{ dashboardMetrics.total_packages || 0 }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Seats</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">{{ inventoryMetrics.available_seats || 0 }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Pax Booked</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">{{ dashboardMetrics.total_participants || 0 }}</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Bottom grid: Upcoming Departures + Recent Bookings -->
            <div class="grid gap-6 lg:grid-cols-2">

                <!-- Upcoming Departures -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Upcoming Departures</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-600">Next 14 days</span>
                    </div>
                    <div v-if="upcomingDepartures.length" class="divide-y divide-gray-100">
                        <div v-for="dep in upcomingDepartures" :key="dep.id" class="flex items-center justify-between px-6 py-3.5">
                            <div>
                                <a :href="`/workspace/${workspace.slug}/bookings/${dep.id}`" class="text-sm font-semibold text-gray-900 hover:underline">{{ dep.customer_name }}</a>
                                <p class="mt-0.5 text-xs text-gray-500">{{ dep.package_name }} · {{ dep.total_pax }} pax</p>
                            </div>
                            <div class="text-right">
                                <p class="text-sm font-medium tabular-nums text-gray-900">{{ dep.departure_date }}</p>
                                <p class="mt-0.5 text-xs text-gray-500">{{ dep.booking_number }}</p>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">No upcoming departures in the next 14 days.</p>
                    </div>
                </div>

                <!-- Recent Bookings -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Recent Bookings</h2>
                        <a :href="`/workspace/${workspace.slug}/bookings`" class="text-xs font-medium text-gray-500 hover:text-gray-900">View all →</a>
                    </div>
                    <div v-if="recentBookings.length" class="divide-y divide-gray-100">
                        <div v-for="booking in recentBookings.slice(0, 6)" :key="booking.id" class="flex items-center justify-between gap-3 px-4 py-3.5 sm:px-6">
                            <div>
                                <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}`" class="text-sm font-semibold text-gray-900 hover:underline">{{ booking.customer_name }}</a>
                                <p class="mt-0.5 text-xs text-gray-500">{{ booking.package_name }} · {{ booking.booking_number }}</p>
                            </div>
                            <div class="flex items-center gap-3">
                                <div class="text-right">
                                    <p class="text-sm font-medium tabular-nums text-gray-900">{{ formatRM(booking.total_price) }}</p>
                                    <p v-if="Number(booking.balance_due) > 0" class="mt-0.5 text-[10px] font-medium tabular-nums text-red-600">Bal: {{ formatRM(booking.balance_due) }}</p>
                                </div>
                                <div class="flex flex-col items-end gap-1">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase" :class="bookingStatusClass(booking.booking_status)">{{ booking.booking_status }}</span>
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[9px] font-semibold uppercase" :class="paymentStatusClass(booking.payment_status)">{{ booking.payment_status }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">No bookings yet. Create your first booking to get started.</p>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
