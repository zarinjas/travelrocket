<script setup>
import { Head } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import { computed } from 'vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    setupChecklist: { type: Array, default: () => [] },
    categoryBreakdown: { type: Array, default: () => [] },
    incompletePackages: { type: Array, default: () => [] },
    topPerformers: { type: Array, default: () => [] },
    upcomingPackages: { type: Array, default: () => [] },
    inventoryHealth: { type: Object, default: () => ({}) },
    packageTypes: { type: Array, default: () => [] },
});

const formatRM = (v) => 'RM' + Number(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2 });

const checklistDone = computed(() => props.setupChecklist.filter(c => c.done).length);
const checklistTotal = computed(() => props.setupChecklist.length);
const checklistPct = computed(() => checklistTotal.value > 0 ? Math.round((checklistDone.value / checklistTotal.value) * 100) : 0);

const fillRateColor = (rate) => {
    if (rate >= 90) return 'bg-red-500';
    if (rate >= 70) return 'bg-amber-500';
    return 'bg-emerald-500';
};

const fillRateTextColor = (rate) => {
    if (rate >= 90) return 'text-red-600';
    if (rate >= 70) return 'text-amber-600';
    return 'text-emerald-600';
};
</script>

<template>
    <Head :title="`${workspace.name} Tour Setup`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Tour Setup</h1>
                    <p class="mt-1 text-sm text-gray-500">Configure and manage your travel packages.</p>
                </div>
                <div class="flex items-center gap-2">
                    <a :href="`/workspace/${workspace.slug}/packages`" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M6 4.75A.75.75 0 016.75 4h10.5a.75.75 0 010 1.5H6.75A.75.75 0 016 4.75zM6 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75A.75.75 0 016 10zm0 5.25a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H6.75a.75.75 0 01-.75-.75zM1.99 4.75a1 1 0 011-1h.01a1 1 0 010 2h-.01a1 1 0 01-1-1zM2.99 9a1 1 0 100 2h.01a1 1 0 100-2h-.01zM1.99 15.25a1 1 0 011-1h.01a1 1 0 010 2h-.01a1 1 0 01-1-1z" clip-rule="evenodd" /></svg>
                        View Catalog
                    </a>
                    <a :href="`/workspace/${workspace.slug}/packages/create`" class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        New Package
                    </a>
                </div>
            </div>

            <!-- Top row: Checklist + Inventory Health -->
            <div class="mb-6 grid gap-6 xl:grid-cols-[1fr_380px]">

                <!-- Setup Checklist -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <div>
                            <h2 class="text-sm font-semibold text-gray-900">Setup Checklist</h2>
                            <p class="mt-0.5 text-xs text-gray-500">Complete these to get selling-ready.</p>
                        </div>
                        <span class="text-sm font-bold tabular-nums" :class="checklistPct >= 100 ? 'text-emerald-600' : checklistPct >= 50 ? 'text-amber-600' : 'text-gray-500'">{{ checklistDone }}/{{ checklistTotal }}</span>
                    </div>
                    <div class="p-6">
                        <div class="mb-4 h-2 w-full overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full transition-all duration-500" :class="checklistPct >= 100 ? 'bg-emerald-500' : checklistPct >= 50 ? 'bg-amber-500' : 'bg-gray-400'" :style="{ width: checklistPct + '%' }"></div>
                        </div>
                        <div class="grid gap-2 sm:grid-cols-2">
                            <div v-for="item in setupChecklist" :key="item.key" class="flex items-center gap-3 rounded-lg px-3 py-2.5" :class="item.done ? 'bg-emerald-50/50' : 'bg-gray-50'">
                                <div class="flex h-6 w-6 shrink-0 items-center justify-center rounded-full" :class="item.done ? 'bg-emerald-100' : 'bg-gray-200'">
                                    <svg v-if="item.done" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5 text-emerald-600"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                                    <span v-else class="h-2 w-2 rounded-full bg-gray-400"></span>
                                </div>
                                <span class="text-sm" :class="item.done ? 'text-emerald-800 line-through decoration-emerald-300' : 'text-gray-700'">{{ item.label }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Inventory Health -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Inventory Health</h2>
                    </div>
                    <div class="p-6">
                        <div class="grid grid-cols-2 gap-4">
                            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Total Packages</p>
                                <p class="mt-1.5 text-2xl font-bold tabular-nums text-gray-900">{{ inventoryHealth.total_packages || 0 }}</p>
                                <p class="mt-0.5 text-[10px] text-gray-500">{{ inventoryHealth.published || 0 }} published · {{ inventoryHealth.draft || 0 }} draft</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Total Revenue</p>
                                <p class="mt-1.5 text-2xl font-bold tabular-nums text-gray-900">{{ formatRM(inventoryHealth.total_revenue) }}</p>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Overall Fill Rate</p>
                                <p class="mt-1.5 text-2xl font-bold tabular-nums" :class="fillRateTextColor(inventoryHealth.overall_fill_rate || 0)">{{ inventoryHealth.overall_fill_rate || 0 }}%</p>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-gray-200">
                                    <div :class="fillRateColor(inventoryHealth.overall_fill_rate || 0)" class="h-full rounded-full transition-all" :style="{ width: (inventoryHealth.overall_fill_rate || 0) + '%' }"></div>
                                </div>
                            </div>
                            <div class="rounded-lg bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-xs font-medium text-gray-500">Capacity</p>
                                <p class="mt-1.5 text-2xl font-bold tabular-nums text-gray-900">{{ inventoryHealth.total_booked || 0 }}<span class="text-sm font-normal text-gray-400">/{{ inventoryHealth.total_capacity || 0 }}</span></p>
                                <p class="mt-0.5 text-[10px] text-gray-500">
                                    <span v-if="inventoryHealth.sold_out" class="text-red-600">{{ inventoryHealth.sold_out }} sold out</span>
                                    <span v-if="inventoryHealth.sold_out && inventoryHealth.low_inventory"> · </span>
                                    <span v-if="inventoryHealth.low_inventory" class="text-amber-600">{{ inventoryHealth.low_inventory }} low</span>
                                    <span v-if="!inventoryHealth.sold_out && !inventoryHealth.low_inventory" class="text-emerald-600">All healthy</span>
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Create by Category -->
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Quick Create</h2>
                </div>
                <div class="grid gap-3 p-5 sm:grid-cols-4">
                    <a
                        v-for="type in packageTypes"
                        :key="type"
                        :href="`/workspace/${workspace.slug}/packages/create?category=${encodeURIComponent(type)}`"
                        class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-4 py-3 text-sm font-medium text-gray-700 shadow-sm transition hover:border-gray-900 hover:bg-gray-50 hover:text-gray-900"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                        {{ type }}
                    </a>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="mb-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                <div class="border-b border-gray-100 px-6 py-4">
                    <h2 class="text-sm font-semibold text-gray-900">Category Breakdown</h2>
                </div>
                <div v-if="categoryBreakdown.length">
                    <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm">
                        <thead>
                            <tr class="border-b border-gray-100 bg-gray-50/80">
                                <th class="px-6 py-3 text-xs font-semibold uppercase tracking-wide text-gray-500">Category</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Packages</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Published</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Revenue</th>
                                <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wide text-gray-500">Avg Price</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Capacity</th>
                                <th class="px-6 py-3 text-center text-xs font-semibold uppercase tracking-wide text-gray-500">Fill Rate</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="cat in categoryBreakdown" :key="cat.category" class="hover:bg-gray-50/50">
                                <td class="px-6 py-3.5">
                                    <a :href="`/workspace/${workspace.slug}/packages?category=${encodeURIComponent(cat.category)}`" class="font-semibold text-gray-900 hover:underline">{{ cat.category }}</a>
                                </td>
                                <td class="px-4 py-3.5 text-center tabular-nums text-gray-700">{{ cat.count }}</td>
                                <td class="px-4 py-3.5 text-center tabular-nums text-gray-700">{{ cat.published }}</td>
                                <td class="px-4 py-3.5 text-right tabular-nums font-medium text-gray-900">{{ formatRM(cat.revenue) }}</td>
                                <td class="px-4 py-3.5 text-right tabular-nums text-gray-700">{{ formatRM(cat.avg_price) }}</td>
                                <td class="px-4 py-3.5 text-center tabular-nums text-gray-700">{{ cat.total_booked }}/{{ cat.total_capacity }}</td>
                                <td class="px-6 py-3.5">
                                    <div class="flex items-center justify-center gap-2">
                                        <div class="h-1.5 w-16 overflow-hidden rounded-full bg-gray-100">
                                            <div :class="fillRateColor(cat.fill_rate)" class="h-full rounded-full transition-all" :style="{ width: cat.fill_rate + '%' }"></div>
                                        </div>
                                        <span class="text-xs font-medium tabular-nums" :class="fillRateTextColor(cat.fill_rate)">{{ cat.fill_rate }}%</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    </div>
                </div>
                <div v-else class="px-6 py-8 text-center">
                    <p class="text-sm text-gray-500">No packages yet. Create your first package to see the breakdown.</p>
                </div>
            </div>

            <!-- Bottom grid: Incomplete + Top Performers + Upcoming -->
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">

                <!-- Incomplete Packages -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Needs Attention</h2>
                        <span v-if="incompletePackages.length" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[10px] font-bold text-amber-700">{{ incompletePackages.length }}</span>
                    </div>
                    <div v-if="incompletePackages.length" class="divide-y divide-gray-100">
                        <div v-for="pkg in incompletePackages" :key="pkg.id" class="px-6 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <a :href="`/workspace/${workspace.slug}/packages/${pkg.id}/edit`" class="text-sm font-semibold text-gray-900 hover:underline">{{ pkg.name }}</a>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ pkg.category }}</p>
                                </div>
                                <a :href="`/workspace/${workspace.slug}/packages/${pkg.id}/edit`" class="shrink-0 text-[10px] font-semibold text-gray-500 hover:text-gray-900">Fix →</a>
                            </div>
                            <div class="mt-2 flex flex-wrap gap-1">
                                <span v-for="issue in pkg.issues" :key="issue" class="inline-flex items-center rounded-full bg-amber-50 px-2 py-0.5 text-[9px] font-semibold text-amber-700 ring-1 ring-amber-200/50">{{ issue }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="flex items-center gap-3 px-6 py-8">
                        <div class="flex h-8 w-8 items-center justify-center rounded-full bg-emerald-50">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4 text-emerald-600"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                        </div>
                        <p class="text-sm text-gray-500">All packages are complete!</p>
                    </div>
                </div>

                <!-- Top Performers -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Top Performers</h2>
                    </div>
                    <div v-if="topPerformers.length" class="divide-y divide-gray-100">
                        <div v-for="(pkg, i) in topPerformers" :key="pkg.id" class="flex items-center justify-between px-6 py-3.5">
                            <div class="flex items-center gap-3">
                                <span class="flex h-6 w-6 items-center justify-center rounded-full text-xs font-bold" :class="i === 0 ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-500'">{{ i + 1 }}</span>
                                <div>
                                    <a :href="`/workspace/${workspace.slug}/packages/${pkg.id}`" class="text-sm font-semibold text-gray-900 hover:underline">{{ pkg.name }}</a>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ pkg.bookings_count }} booking(s)</p>
                                </div>
                            </div>
                            <span class="text-sm font-medium tabular-nums text-gray-900">{{ formatRM(pkg.revenue) }}</span>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">No revenue data yet.</p>
                    </div>
                </div>

                <!-- Upcoming Departures -->
                <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                    <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                        <h2 class="text-sm font-semibold text-gray-900">Upcoming Departures</h2>
                        <span class="rounded-full bg-gray-100 px-2.5 py-1 text-[10px] font-semibold uppercase tracking-wide text-gray-600">30 days</span>
                    </div>
                    <div v-if="upcomingPackages.length" class="divide-y divide-gray-100">
                        <div v-for="pkg in upcomingPackages" :key="pkg.id" class="px-6 py-3.5">
                            <div class="flex items-start justify-between gap-2">
                                <div>
                                    <a :href="`/workspace/${workspace.slug}/packages/${pkg.id}`" class="text-sm font-semibold text-gray-900 hover:underline">{{ pkg.name }}</a>
                                    <p class="mt-0.5 text-xs text-gray-500">{{ pkg.destination }} · {{ pkg.start_date }}</p>
                                </div>
                                <span class="shrink-0 rounded-full px-2 py-0.5 text-[10px] font-bold tabular-nums" :class="pkg.days_until <= 7 ? 'bg-red-50 text-red-700' : pkg.days_until <= 14 ? 'bg-amber-50 text-amber-700' : 'bg-gray-100 text-gray-600'">
                                    {{ pkg.days_until === 0 ? 'Today' : pkg.days_until === 1 ? 'Tomorrow' : `${pkg.days_until}d` }}
                                </span>
                            </div>
                            <div class="mt-2 flex items-center gap-2">
                                <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-gray-100">
                                    <div :class="fillRateColor(pkg.fill_rate)" class="h-full rounded-full transition-all" :style="{ width: pkg.fill_rate + '%' }"></div>
                                </div>
                                <span class="text-[10px] font-medium tabular-nums text-gray-500">{{ pkg.booked }}/{{ pkg.capacity }}</span>
                            </div>
                        </div>
                    </div>
                    <div v-else class="px-6 py-8 text-center">
                        <p class="text-sm text-gray-500">No departures in the next 30 days.</p>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
