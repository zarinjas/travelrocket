<script setup>
import { Head, router } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import { computed, ref, watch } from 'vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    bookings: { type: Object, default: () => ({ data: [], current_page: 1, last_page: 1, total: 0, from: 0, to: 0, links: [] }) },
    filters: { type: Object, default: () => ({ search: '', booking_status: 'all', payment_status: 'all', seat: 'all', sort: 'newest', per_page: 20 }) },
    bookingStatusOptions: { type: Array, default: () => [] },
    paymentStatusOptions: { type: Array, default: () => [] },
});

const query = ref(props.filters?.search ?? '');
const bookingStatusFilter = ref(props.filters?.booking_status ?? 'all');
const paymentStatusFilter = ref(props.filters?.payment_status ?? 'all');
const sortBy = ref(props.filters?.sort ?? 'newest');
const perPage = ref(Number(props.filters?.per_page ?? 20));
const selectedIds = ref([]);

const rows = computed(() => props.bookings?.data ?? []);
const allSelected = computed(() => rows.value.length > 0 && selectedIds.value.length === rows.value.length);

// Stats
const totalBookings = computed(() => props.bookings?.total ?? 0);
const totalBalance = computed(() => rows.value.reduce((s, b) => s + Number(b.balance_due || 0), 0));
const totalRevenue = computed(() => rows.value.reduce((s, b) => s + Number(b.total_price || 0), 0));
const totalPaid = computed(() => rows.value.reduce((s, b) => s + Number(b.payments_total || 0), 0));

let debounce = null;
const applyQuery = (page = 1) => {
    router.get(`/workspace/${props.workspace.slug}/bookings`, {
        search: query.value || undefined,
        booking_status: bookingStatusFilter.value === 'all' ? undefined : bookingStatusFilter.value,
        payment_status: paymentStatusFilter.value === 'all' ? undefined : paymentStatusFilter.value,
        sort: sortBy.value || undefined,
        per_page: perPage.value || undefined,
        page,
    }, { preserveState: true, preserveScroll: true, replace: true });
};

watch(query, () => { clearTimeout(debounce); debounce = setTimeout(() => applyQuery(1), 350); });
watch([bookingStatusFilter, paymentStatusFilter, sortBy, perPage], () => applyQuery(1));

const goPage = (url) => { if (url) router.visit(url, { preserveState: true, preserveScroll: true }); };

const toggleAll = () => {
    if (allSelected.value) { selectedIds.value = []; }
    else { selectedIds.value = rows.value.map(b => b.id); }
};

const resetFilters = () => {
    query.value = '';
    bookingStatusFilter.value = 'all';
    paymentStatusFilter.value = 'all';
    sortBy.value = 'newest';
    perPage.value = 20;
    selectedIds.value = [];
    applyQuery(1);
};

const confirmBulkDelete = () => {
    if (!selectedIds.value.length || !window.confirm(`Delete ${selectedIds.value.length} booking(s)?`)) return;
    router.delete(`/workspace/${props.workspace.slug}/bookings/bulk-delete`, {
        data: { ids: selectedIds.value },
        onSuccess: () => { selectedIds.value = []; },
    });
};

const destroyBooking = (id, number) => {
    if (!window.confirm(`Delete booking "${number}"?`)) return;
    router.delete(`/workspace/${props.workspace.slug}/bookings/${id}`);
};

const sendTourismLetter = (id) => {
    router.post(`/workspace/${props.workspace.slug}/bookings/${id}/tourism-letter/email`);
};

const exportUrl = computed(() => {
    const params = new URLSearchParams();
    if (query.value) params.set('search', query.value);
    if (bookingStatusFilter.value !== 'all') params.set('booking_status', bookingStatusFilter.value);
    if (paymentStatusFilter.value !== 'all') params.set('payment_status', paymentStatusFilter.value);
    const qs = params.toString();
    return `/workspace/${props.workspace.slug}/bookings/export-csv${qs ? '?' + qs : ''}`;
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

const seatPct = (booking) => {
    const cap = Number(booking.package?.booking_capacity || 0);
    if (cap <= 0) return 0;
    return Math.min(100, Math.round((Number(booking.package?.current_bookings || 0) / cap) * 100));
};

const seatBarColor = (booking) => {
    const pct = seatPct(booking);
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
};

// Column sorting
const currentSort = ref(props.filters?.sort ?? 'newest');
const toggleSort = (field) => {
    // Map field to sort key
    const mapping = { created: 'newest', price: 'price_desc', balance: 'balance_desc' };
    const reverseMapping = { created: 'oldest' };
    if (sortBy.value === mapping[field]) {
        sortBy.value = reverseMapping[field] || mapping[field];
    } else {
        sortBy.value = mapping[field];
    }
};
</script>

<template>
    <Head :title="`${workspace.name} Bookings`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Bookings</h1>
                    <p class="mt-1 text-sm text-gray-500">Link travelers to packages and track payments.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="exportUrl" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                        CSV
                    </a>
                    <a :href="`/workspace/${workspace.slug}/bookings/create`" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        Create Booking
                    </a>
                </div>
            </div>

            <!-- Stats -->
            <div class="mb-6 grid gap-4 sm:grid-cols-4">
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-medium text-gray-500">Total Bookings</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">{{ totalBookings }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-medium text-gray-500">Revenue (this page)</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-gray-900">RM{{ totalRevenue.toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-medium text-gray-500">Collected</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums text-emerald-600">RM{{ totalPaid.toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                </div>
                <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                    <p class="text-xs font-medium text-gray-500">Balance Due</p>
                    <p class="mt-2 text-2xl font-bold tabular-nums" :class="totalBalance > 0 ? 'text-red-600' : 'text-gray-900'">RM{{ totalBalance.toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                </div>
            </div>

            <!-- Filters -->
            <div class="mb-4 rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5">
                <div class="grid gap-3 lg:grid-cols-12">
                    <div class="lg:col-span-4">
                        <div class="relative">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400"><path fill-rule="evenodd" d="M9 3.5a5.5 5.5 0 100 11 5.5 5.5 0 000-11zM2 9a7 7 0 1112.452 4.391l3.328 3.329a.75.75 0 11-1.06 1.06l-3.329-3.328A7 7 0 012 9z" clip-rule="evenodd" /></svg>
                            <input v-model="query" type="search" placeholder="Customer, booking #, package..." class="w-full rounded-lg border-0 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                        </div>
                    </div>
                    <div class="lg:col-span-2">
                        <select v-model="bookingStatusFilter" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                            <option value="all">All Booking Status</option>
                            <option v-for="s in bookingStatusOptions" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <select v-model="paymentStatusFilter" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                            <option value="all">All Payment Status</option>
                            <option v-for="s in paymentStatusOptions" :key="s" :value="s">{{ s.charAt(0).toUpperCase() + s.slice(1) }}</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2">
                        <select v-model="sortBy" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                            <option value="newest">Newest First</option>
                            <option value="oldest">Oldest First</option>
                            <option value="balance_desc">Highest Balance</option>
                            <option value="price_desc">Highest Price</option>
                        </select>
                    </div>
                    <div class="lg:col-span-2 flex items-center gap-2">
                        <select v-model.number="perPage" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                            <option :value="10">10 / page</option>
                            <option :value="20">20 / page</option>
                            <option :value="50">50 / page</option>
                            <option :value="100">100 / page</option>
                        </select>
                        <button type="button" @click="resetFilters" class="shrink-0 rounded-lg border border-gray-200 bg-white px-3 py-2.5 text-xs font-semibold text-gray-600 transition hover:bg-gray-50">Reset</button>
                    </div>
                </div>

                <!-- Bulk bar -->
                <div v-if="selectedIds.length" class="mt-3 flex items-center gap-3 rounded-lg bg-gray-900 px-4 py-2.5">
                    <span class="text-sm font-medium text-white">{{ selectedIds.length }} selected</span>
                    <button @click="confirmBulkDelete" class="rounded-md bg-red-600 px-3 py-1 text-xs font-semibold text-white transition hover:bg-red-700">Delete Selected</button>
                    <button @click="selectedIds = []" class="text-xs font-medium text-gray-300 hover:text-white">Clear</button>
                </div>
            </div>

            <!-- Table -->
            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/60">
                                <th class="w-10 px-4 py-3">
                                    <input type="checkbox" :checked="allSelected" @change="toggleAll" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Customer</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Package</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Pax</th>
                                <th class="cursor-pointer px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 select-none" @click="toggleSort('price')">
                                    Total
                                    <span v-if="sortBy === 'price_desc'" class="text-gray-400">↓</span>
                                </th>
                                <th class="cursor-pointer px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 select-none" @click="toggleSort('balance')">
                                    Balance
                                    <span v-if="sortBy === 'balance_desc'" class="text-gray-400">↓</span>
                                </th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Booking</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Payment</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Seats</th>
                                <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500 text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="booking in rows" :key="booking.id" class="group transition hover:bg-gray-50/60">
                                <td class="px-4 py-3">
                                    <input type="checkbox" :value="booking.id" v-model="selectedIds" class="h-4 w-4 rounded border-gray-300 text-gray-900 focus:ring-gray-900" />
                                </td>
                                <td class="px-4 py-3">
                                    <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}`" class="font-semibold text-gray-900 hover:underline">{{ booking.lead_customer?.full_name || 'N/A' }}</a>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ booking.booking_number }}</p>
                                </td>
                                <td class="px-4 py-3">
                                    <p class="font-medium text-gray-900">{{ booking.package?.name || '-' }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ booking.package?.destination || '' }}</p>
                                </td>
                                <td class="px-4 py-3 tabular-nums text-gray-700">{{ booking.total_pax }}</td>
                                <td class="px-4 py-3 tabular-nums font-medium text-gray-900">RM{{ Number(booking.total_price || 0).toFixed(2) }}</td>
                                <td class="px-4 py-3 tabular-nums font-medium" :class="Number(booking.balance_due || 0) > 0 ? 'text-red-600' : 'text-emerald-600'">
                                    RM{{ Number(booking.balance_due || 0).toFixed(2) }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase" :class="bookingStatusClass(booking.booking_status)">{{ booking.booking_status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase" :class="paymentStatusClass(booking.payment_status)">{{ booking.payment_status }}</span>
                                </td>
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-100">
                                            <div :class="seatBarColor(booking)" class="h-full rounded-full transition-all" :style="{ width: seatPct(booking) + '%' }"></div>
                                        </div>
                                        <span class="text-xs tabular-nums text-gray-500">{{ seatPct(booking) }}%</span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex items-center justify-end gap-1 opacity-0 transition group-hover:opacity-100">
                                        <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}`" class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="View">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 12.5a2.5 2.5 0 100-5 2.5 2.5 0 000 5z" /><path fill-rule="evenodd" d="M.664 10.59a1.651 1.651 0 010-1.186A10.004 10.004 0 0110 3c4.257 0 7.893 2.66 9.336 6.41.147.381.146.804 0 1.186A10.004 10.004 0 0110 17c-4.257 0-7.893-2.66-9.336-6.41zM14 10a4 4 0 11-8 0 4 4 0 018 0z" clip-rule="evenodd" /></svg>
                                        </a>
                                        <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}/edit`" class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="Edit">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                                        </a>
                                        <a :href="`/workspace/${workspace.slug}/bookings/${booking.id}/tourism-letter/download`" class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="Tourism Letter">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M4.5 2A1.5 1.5 0 003 3.5v13A1.5 1.5 0 004.5 18h11a1.5 1.5 0 001.5-1.5V7.621a1.5 1.5 0 00-.44-1.06l-4.12-4.122A1.5 1.5 0 0011.378 2H4.5zm2.25 8.5a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5zm0 3a.75.75 0 000 1.5h6.5a.75.75 0 000-1.5h-6.5z" clip-rule="evenodd" /></svg>
                                        </a>
                                        <button @click="sendTourismLetter(booking.id)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-700" title="Email Letter">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M3 4a2 2 0 00-2 2v1.161l8.441 4.221a1.25 1.25 0 001.118 0L19 7.161V6a2 2 0 00-2-2H3z" /><path d="M19 8.839l-7.77 3.885a2.75 2.75 0 01-2.46 0L1 8.839V14a2 2 0 002 2h14a2 2 0 002-2V8.839z" /></svg>
                                        </button>
                                        <button @click="destroyBooking(booking.id, booking.booking_number)" class="rounded-md p-1.5 text-gray-400 transition hover:bg-red-50 hover:text-red-600" title="Delete">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M8.75 1A2.75 2.75 0 006 3.75v.443c-.795.077-1.584.176-2.365.298a.75.75 0 10.23 1.482l.149-.022.841 10.518A2.75 2.75 0 007.596 19h4.807a2.75 2.75 0 002.742-2.53l.841-10.52.149.023a.75.75 0 00.23-1.482A41.03 41.03 0 0014 4.193V3.75A2.75 2.75 0 0011.25 1h-2.5zM10 4c.84 0 1.673.025 2.5.075V3.75c0-.69-.56-1.25-1.25-1.25h-2.5c-.69 0-1.25.56-1.25 1.25v.325C8.327 4.025 9.16 4 10 4zM8.58 7.72a.75.75 0 01.78.72l.5 6.5a.75.75 0 01-1.49.12l-.5-6.5a.75.75 0 01.71-.84zm3.62.72a.75.75 0 10-1.49-.12l-.5 6.5a.75.75 0 101.49.12l.5-6.5z" clip-rule="evenodd" /></svg>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Empty -->
                <div v-if="!rows.length" class="px-6 py-12 text-center">
                    <p class="text-sm text-gray-500">
                        <template v-if="bookings.total">No matches for "{{ query }}". Try a different keyword.</template>
                        <template v-else>No bookings yet. Create your first booking to get started.</template>
                    </p>
                </div>
            </div>

            <!-- Pagination -->
            <div v-if="bookings.links?.length" class="mt-4 flex flex-wrap items-center justify-between gap-3">
                <p class="text-xs text-gray-500">Showing {{ bookings.from ?? 0 }}-{{ bookings.to ?? 0 }} of {{ bookings.total ?? 0 }}</p>
                <div class="flex flex-wrap gap-1">
                    <button
                        v-for="(link, idx) in bookings.links"
                        :key="idx"
                        type="button"
                        class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                        :class="link.active ? 'border-gray-900 bg-gray-900 text-white' : 'border-gray-200 bg-white text-gray-600 hover:border-gray-300 hover:bg-gray-50'"
                        :disabled="!link.url"
                        @click="goPage(link.url)"
                    >
                        <span v-html="link.label" />
                    </button>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
