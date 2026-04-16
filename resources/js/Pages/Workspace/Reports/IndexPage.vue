<script setup>
import { Head } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    metrics: {
        type: Object,
        default: () => ({
            booking_count: 0,
            paid_bookings: 0,
            pending_bookings: 0,
            cancelled_bookings: 0,
            revenue: 0,
            average_package_price: 0,
        }),
    },
    packageBreakdown: {
        type: Array,
        default: () => [],
    },
});
</script>

<template>
    <Head :title="`${workspace.name} Reports`" />

    <WorkspaceLayout>
        <div class="w-full">
            <section class="tr-bento tr-bento-main">
                <div class="tr-surface rounded-[1.7rem] border border-slate-200 p-7 md:p-8">
                    <div class="border-b border-slate-200 pb-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Reports</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Financials & Exports</h2>
                        <p class="mt-2 text-sm text-slate-700">Monitor revenue movement and prepare exports in a visual, decision-ready view.</p>
                    </div>

                    <div class="tr-bento tr-bento-3 mt-8">
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Revenue</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">RM{{ metrics.revenue }}</p>
                        </article>
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Paid bookings</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">{{ metrics.paid_bookings }}</p>
                        </article>
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Average package</p>
                            <p class="mt-2 text-2xl font-bold text-slate-900">RM{{ metrics.average_package_price }}</p>
                        </article>
                    </div>

                    <div class="mt-6 rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">What should be on reports</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">
                            Booking revenue, paid vs pending mix, package performance, and exportable financial snapshots for management review.
                        </p>
                    </div>

                    <div class="mt-6 grid gap-4 md:grid-cols-3">
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Bookings</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ metrics.booking_count }}</p>
                        </article>
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Pending</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ metrics.pending_bookings }}</p>
                            <div class="tr-progress-track mt-3">
                                <div class="tr-progress-fill" :style="{ width: `${metrics.booking_count ? Math.round((Number(metrics.pending_bookings || 0) / Number(metrics.booking_count || 1)) * 100) : 0}%` }"></div>
                            </div>
                        </article>
                        <article class="tr-surface rounded-[1rem] border border-slate-200 p-5">
                            <p class="text-sm text-slate-700">Cancelled</p>
                            <p class="mt-2 text-xl font-bold text-slate-900">{{ metrics.cancelled_bookings }}</p>
                            <div class="tr-progress-track mt-3">
                                <div class="tr-progress-fill" :style="{ width: `${metrics.booking_count ? Math.round((Number(metrics.cancelled_bookings || 0) / Number(metrics.booking_count || 1)) * 100) : 0}%` }"></div>
                            </div>
                        </article>
                    </div>

                    <div class="mt-6 rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Package type breakdown</p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <span
                                v-for="item in packageBreakdown"
                                :key="item.type"
                                class="rounded-full border border-slate-300 bg-white px-3 py-1 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-700"
                            >
                                {{ item.type }} · {{ item.total }}
                            </span>
                        </div>
                    </div>
                </div>

                <aside class="tr-surface space-y-4 rounded-[1.7rem] border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Exports</p>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Recommended output</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">Financials and booking summaries</p>
                    </div>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Next step</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">Hook this page to export endpoints when reporting data is fully wired.</p>
                    </div>
                </aside>
            </section>
        </div>
    </WorkspaceLayout>
</template>
