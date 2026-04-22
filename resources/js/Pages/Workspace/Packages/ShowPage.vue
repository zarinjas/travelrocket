<script setup>
import { Head, router } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    package: { type: Object, required: true },
});

const seatLabel = () => {
    const seats = props.package.available_seats ?? 0;
    return seats > 0 ? `${seats} seats left` : 'Sold out';
};

const capacityPct = () => {
    const cap = Number(props.package.booking_capacity) || 1;
    return Math.min(100, Math.round((Number(props.package.current_bookings) / cap) * 100));
};

const capacityColor = () => {
    const pct = capacityPct();
    if (pct >= 90) return 'bg-red-500';
    if (pct >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const toggleStatus = () => {
    router.post(`/workspace/${props.workspace.slug}/packages/${props.package.id}/toggle-status`, {}, { preserveScroll: true });
};
</script>

<template>
    <Head :title="`${package.name} — ${workspace.name}`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-2xl font-bold text-gray-900 tracking-tight">{{ package.name }}</h1>
                        <span
                            class="inline-flex items-center rounded-full px-2.5 py-1 text-[11px] font-semibold uppercase tracking-wide"
                            :class="package.status === 'published' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-600/20' : 'bg-gray-100 text-gray-600 ring-1 ring-gray-900/10'"
                        >{{ package.status }}</span>
                    </div>
                    <p class="mt-1 text-sm text-gray-500">{{ package.category }} · {{ package.destination || 'No destination' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        @click="toggleStatus"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        {{ package.status === 'published' ? 'Unpublish' : 'Publish' }}
                    </button>
                    <a
                        :href="`/workspace/${workspace.slug}/packages/${package.id}/rooming-list`"
                        class="inline-flex items-center gap-2 rounded-lg border border-indigo-200 bg-indigo-50 px-4 py-2.5 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-100"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10 9a3 3 0 100-6 3 3 0 000 6zM6 8a2 2 0 11-4 0 2 2 0 014 0zM1.49 15.326a.78.78 0 01-.358-.442 3 3 0 014.308-3.516 6.484 6.484 0 00-1.905 3.959c-.023.222-.014.442.025.654a4.97 4.97 0 01-2.07-.655zM16.44 15.98a4.97 4.97 0 002.07-.654.78.78 0 00.357-.442 3 3 0 00-4.308-3.517 6.484 6.484 0 011.907 3.96 2.32 2.32 0 01-.026.654zM18 8a2 2 0 11-4 0 2 2 0 014 0zM5.304 16.19a.844.844 0 01-.277-.71 5 5 0 019.947 0 .843.843 0 01-.277.71A6.975 6.975 0 0110 18a6.974 6.974 0 01-4.696-1.81z" /></svg>
                        Rooming List
                    </a>
                    <a
                        :href="`/workspace/${workspace.slug}/packages/${package.id}/edit`"
                        class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M2.695 14.763l-1.262 3.154a.5.5 0 00.65.65l3.155-1.262a4 4 0 001.343-.885L17.5 5.5a2.121 2.121 0 00-3-3L3.58 13.42a4 4 0 00-.885 1.343z" /></svg>
                        Edit
                    </a>
                    <a
                        :href="`/workspace/${workspace.slug}/packages`"
                        class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                        Back
                    </a>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-[1fr_320px]">

                <!-- Main content -->
                <div class="space-y-6">

                    <!-- Stat cards row -->
                    <div class="grid gap-4 sm:grid-cols-4">
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Price</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-gray-900">RM{{ Number(package.price || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Capacity</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-gray-900">{{ package.booking_capacity }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <p class="text-xs font-medium text-gray-500">Booked</p>
                            <p class="mt-2 text-lg font-bold tabular-nums text-gray-900">{{ package.current_bookings }}</p>
                        </div>
                        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                            <div class="flex items-center justify-between">
                                <p class="text-xs font-medium text-gray-500">Available</p>
                                <span
                                    class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                    :class="package.is_sold_out ? 'bg-red-50 text-red-700' : 'bg-emerald-50 text-emerald-700'"
                                >{{ seatLabel() }}</span>
                            </div>
                            <p class="mt-2 text-lg font-bold tabular-nums" :class="package.available_seats > 0 ? 'text-emerald-600' : 'text-red-600'">{{ package.available_seats }}</p>
                            <div class="mt-2.5 h-1.5 w-full overflow-hidden rounded-full bg-gray-100">
                                <div :class="capacityColor()" class="h-full rounded-full transition-all" :style="{ width: capacityPct() + '%' }"></div>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Tiers -->
                    <div v-if="package.pricing_tiers?.adult || package.pricing_tiers?.child || package.pricing_tiers?.infant" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Pricing Tiers</h2>
                        </div>
                        <div class="grid gap-4 p-6 sm:grid-cols-3">
                            <div v-if="package.pricing_tiers?.adult" class="rounded-lg bg-gray-50 p-4 text-center ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Adult</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">RM{{ Number(package.pricing_tiers.adult).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                            <div v-if="package.pricing_tiers?.child" class="rounded-lg bg-gray-50 p-4 text-center ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Child</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">RM{{ Number(package.pricing_tiers.child).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                            <div v-if="package.pricing_tiers?.infant" class="rounded-lg bg-gray-50 p-4 text-center ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Infant</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">RM{{ Number(package.pricing_tiers.infant).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Travel Dates -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Travel Dates</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="text-sm text-gray-900 font-medium">{{ package.date_range || 'Dates not set' }}</p>
                        </div>
                    </div>

                    <!-- Itinerary -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Itinerary</h2>
                        </div>
                        <!-- Structured itinerary days -->
                        <div v-if="package.itinerary_days?.length" class="divide-y divide-gray-100">
                            <div v-for="day in package.itinerary_days" :key="day.day" class="px-6 py-5">
                                <div class="mb-2 flex items-center gap-3">
                                    <span class="inline-flex items-center rounded-full bg-gray-900 px-2.5 py-0.5 text-[10px] font-bold text-white">Day {{ day.day }}</span>
                                    <h3 class="text-sm font-semibold text-gray-900">{{ day.title }}</h3>
                                </div>
                                <p v-if="day.description" class="mb-2 text-sm leading-relaxed text-gray-600">{{ day.description }}</p>
                                <ul v-if="day.activities?.length" class="space-y-1">
                                    <li v-for="(activity, i) in day.activities" :key="i" class="flex items-start gap-2 text-sm text-gray-700">
                                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-gray-400"></span>
                                        {{ activity }}
                                    </li>
                                </ul>
                            </div>
                        </div>
                        <!-- Legacy plain text itinerary -->
                        <div v-else-if="package.itinerary" class="px-6 py-4">
                            <p class="whitespace-pre-line text-sm leading-7 text-gray-700">{{ package.itinerary }}</p>
                        </div>
                        <div v-else class="px-6 py-4">
                            <p class="text-sm text-gray-500">No itinerary added yet.</p>
                        </div>
                    </div>

                    <!-- Inclusions & Exclusions -->
                    <div v-if="(package.inclusions?.length) || (package.exclusions?.length)" class="grid gap-6 sm:grid-cols-2">
                        <div v-if="package.inclusions?.length" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Inclusions</h2>
                            </div>
                            <ul class="space-y-2 p-5">
                                <li v-for="(item, i) in package.inclusions" :key="i" class="flex items-center gap-2.5 text-sm text-gray-700">
                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 text-emerald-600"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                                    </div>
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                        <div v-if="package.exclusions?.length" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Exclusions</h2>
                            </div>
                            <ul class="space-y-2 p-5">
                                <li v-for="(item, i) in package.exclusions" :key="i" class="flex items-center gap-2.5 text-sm text-gray-700">
                                    <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-50">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 text-red-600"><path d="M5.28 4.22a.75.75 0 00-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 101.06 1.06L8 9.06l2.72 2.72a.75.75 0 101.06-1.06L9.06 8l2.72-2.72a.75.75 0 00-1.06-1.06L8 6.94 5.28 4.22z" /></svg>
                                    </div>
                                    {{ item }}
                                </li>
                            </ul>
                        </div>
                    </div>

                    <!-- Terms & Conditions -->
                    <div v-if="package.terms_conditions" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Terms & Conditions</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="whitespace-pre-line text-sm leading-7 text-gray-700">{{ package.terms_conditions }}</p>
                        </div>
                    </div>

                    <!-- Internal Notes -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Internal Notes</h2>
                        </div>
                        <div class="px-6 py-4">
                            <p class="whitespace-pre-line text-sm leading-7 text-gray-700">{{ package.description || 'No internal notes.' }}</p>
                        </div>
                    </div>

                    <!-- Recent Bookings -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-sm font-semibold text-gray-900">Recent Bookings</h2>
                        </div>
                        <div v-if="package.recent_bookings?.length" class="divide-y divide-gray-100">
                            <div v-for="booking in package.recent_bookings" :key="booking.id" class="flex items-center justify-between gap-4 px-6 py-3.5">
                                <div>
                                    <p class="text-sm font-semibold text-gray-900">{{ booking.booking_number }}</p>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ booking.lead_customer?.full_name }}</p>
                                </div>
                                <div class="text-right">
                                    <span
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-[10px] font-semibold uppercase"
                                        :class="{
                                            'bg-emerald-50 text-emerald-700': booking.booking_status === 'confirmed',
                                            'bg-amber-50 text-amber-700': booking.booking_status === 'pending',
                                            'bg-red-50 text-red-700': booking.booking_status === 'cancelled',
                                            'bg-gray-100 text-gray-600': !['confirmed','pending','cancelled'].includes(booking.booking_status),
                                        }"
                                    >{{ booking.booking_status }}</span>
                                    <p class="mt-1 text-xs tabular-nums text-gray-500">RM{{ Number(booking.total_price || 0).toFixed(2) }}</p>
                                </div>
                            </div>
                        </div>
                        <div v-else class="px-6 py-8 text-center">
                            <p class="text-sm text-gray-500">No bookings linked to this package yet.</p>
                        </div>
                    </div>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4 xl:sticky xl:top-24 self-start">
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Quick Info</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Category</span>
                                <span class="text-sm font-medium text-gray-900">{{ package.category }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Status</span>
                                <span class="text-sm font-medium text-gray-900 capitalize">{{ package.status }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Price</span>
                                <span class="text-sm font-medium tabular-nums text-gray-900">RM{{ Number(package.price || 0).toFixed(2) }}</span>
                            </div>
                            <div class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Bookings</span>
                                <span class="text-sm font-medium text-gray-900">{{ package.bookings_count ?? package.current_bookings ?? 0 }}</span>
                            </div>
                        </div>
                    </div>

                    <div v-if="package.brochure_url" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Brochure</h3>
                        </div>
                        <div class="p-5">
                            <a
                                :href="package.brochure_url"
                                target="_blank"
                                class="inline-flex items-center gap-2 text-sm font-medium text-gray-900 underline decoration-gray-300 underline-offset-4 hover:decoration-gray-900"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path d="M10.75 2.75a.75.75 0 00-1.5 0v8.614L6.295 8.235a.75.75 0 10-1.09 1.03l4.25 4.5a.75.75 0 001.09 0l4.25-4.5a.75.75 0 00-1.09-1.03l-2.955 3.129V2.75z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                                Download Brochure
                            </a>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <a
                            :href="`/workspace/${workspace.slug}/packages/${package.id}/edit`"
                            class="rounded-lg bg-gray-900 px-5 py-2.5 text-center text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800"
                        >Edit Package</a>
                        <a
                            :href="`/workspace/${workspace.slug}/packages`"
                            class="rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-center text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                        >Back to Packages</a>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
