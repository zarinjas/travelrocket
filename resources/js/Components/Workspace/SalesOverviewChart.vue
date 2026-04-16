<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    days: { type: Array, default: () => [] },
    weeklyTotal: { type: Number, default: 0 },
    weeklyBookings: { type: Number, default: 0 },
    currency: { type: String, default: 'RM' },
});

const hasData = computed(() => props.days?.some((d) => d.value > 0));

const niceMax = computed(() => {
    const m = Math.max(...(props.days || []).map((d) => d.value), 1);
    if (m <= 0) return 100;
    const mag = Math.pow(10, Math.floor(Math.log10(m)));
    return Math.ceil(m / mag) * mag;
});

const formatCompact = (v) => {
    const n = Number(v || 0);
    if (n >= 1_000_000) return `${props.currency}${(n / 1_000_000).toFixed(1)}M`;
    if (n >= 1_000) return `${props.currency}${(n / 1_000).toFixed(n >= 100_000 ? 0 : 1)}k`;
    return `${props.currency}${n.toFixed(0)}`;
};

const formatFull = (v) =>
    `${props.currency}${Number(v || 0).toLocaleString('en-MY', { minimumFractionDigits: 2, maximumFractionDigits: 2 })}`;

// SVG viewBox — VH intentionally equals rendered px height so bottom% math works 1:1
const VW = 600;
const VH = 160;
const PT = 14;  // top padding
const PB = 6;   // bottom padding
const PL = 10;  // left padding
const PR = 16;  // right padding
const cH = VH - PT - PB; // usable plot height

const toY = (value) => {
    const pct = niceMax.value > 0 ? value / niceMax.value : 0;
    return PT + cH * (1 - pct);
};

const toX = (index) => {
    const n = Math.max((props.days?.length ?? 1) - 1, 1);
    return PL + (index / n) * (VW - PL - PR);
};

const svgPoints = computed(() =>
    (props.days || []).map((d, i) => ({ x: toX(i), y: toY(d.value) }))
);

// Smooth cubic bezier path
const linePath = computed(() => {
    const pts = svgPoints.value;
    if (pts.length < 2) return '';
    let d = `M ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const cpx = (pts[i - 1].x + pts[i].x) / 2;
        d += ` C ${cpx} ${pts[i - 1].y} ${cpx} ${pts[i].y} ${pts[i].x} ${pts[i].y}`;
    }
    return d;
});

// Closed area path for gradient fill
const areaPath = computed(() => {
    const pts = svgPoints.value;
    if (pts.length < 2) return '';
    const base = PT + cH;
    let d = `M ${pts[0].x} ${base} L ${pts[0].x} ${pts[0].y}`;
    for (let i = 1; i < pts.length; i++) {
        const cpx = (pts[i - 1].x + pts[i].x) / 2;
        d += ` C ${cpx} ${pts[i - 1].y} ${cpx} ${pts[i].y} ${pts[i].x} ${pts[i].y}`;
    }
    d += ` L ${pts[pts.length - 1].x} ${base} Z`;
    return d;
});

const gridLines = computed(() => {
    const n = niceMax.value;
    return [0.25, 0.5, 0.75, 1.0].map((f) => ({
        label: formatCompact(n * f),
        y: toY(n * f),
    }));
});

const bestDay = computed(() => {
    if (!hasData.value) return null;
    return props.days.reduce((a, b) => (b.value > a.value ? b : a), props.days[0]);
});

const hoveredIndex = ref(null);

// Computes tooltip bottom% + left/right anchor per column index
const getTooltipStyle = (i) => {
    const pt = svgPoints.value[i];
    if (!pt) return '';
    const bottomPct = ((VH - pt.y) / VH) * 100;
    const base = `bottom: calc(${bottomPct}% + 14px);`;
    if (i === 0) return base + ' left: 0;';
    if (i >= props.days.length - 1) return base + ' right: 0; left: auto; transform: none;';
    return base + ' left: 50%; transform: translateX(-50%);';
};
</script>

<template>
    <!-- Empty state -->
    <div v-if="!hasData" class="flex items-center justify-center rounded-2xl border border-dashed border-gray-200 bg-gray-50/80 px-4 text-center" style="height: 260px;">
        <div>
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="mx-auto mb-3 h-8 w-8 text-gray-300">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 013 19.875v-6.75zM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V8.625zM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 01-1.125-1.125V4.125z" />
            </svg>
            <p class="text-sm font-semibold text-gray-900">No sales data yet</p>
            <p class="mt-1 text-xs text-gray-500">Once bookings come in, your 7-day trend appears here.</p>
        </div>
    </div>

    <!-- Chart -->
    <div v-else class="select-none">

        <!-- Summary row -->
        <div class="mb-5 flex flex-wrap items-end gap-x-6 gap-y-2">
            <div>
                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Weekly Revenue</p>
                <p class="mt-1 text-3xl font-bold tabular-nums text-gray-900">{{ formatFull(weeklyTotal) }}</p>
            </div>
            <div class="mb-0.5 hidden h-7 w-px self-end bg-gray-200 sm:block"></div>
            <div class="hidden sm:block">
                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Bookings</p>
                <p class="mt-1 text-2xl font-bold tabular-nums text-gray-900">{{ weeklyBookings }}</p>
            </div>
            <div v-if="bestDay?.value" class="ml-auto hidden items-center sm:flex">
                <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-3 py-1 text-[11px] font-semibold text-emerald-700 ring-1 ring-inset ring-emerald-200/60">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 shrink-0">
                        <path fill-rule="evenodd" d="M8 1.75a.75.75 0 0 1 .692.462l1.41 3.393 3.664.293a.75.75 0 0 1 .428 1.317l-2.791 2.39.853 3.595a.75.75 0 0 1-1.12.814L8 11.773l-3.136 2.241a.75.75 0 0 1-1.12-.814l.852-3.595-2.79-2.39a.75.75 0 0 1 .427-1.318l3.663-.292 1.41-3.393A.75.75 0 0 1 8 1.75Z" clip-rule="evenodd" />
                    </svg>
                    Best: {{ bestDay.day }} · {{ formatCompact(bestDay.value) }}
                </span>
            </div>
        </div>

        <!-- Chart layout -->
        <div class="flex gap-3">

            <!-- Y-axis labels -->
            <div class="flex shrink-0 flex-col justify-between pb-8" style="width: 52px;">
                <span
                    v-for="l in [...gridLines].reverse()"
                    :key="l.label"
                    class="block text-right text-[10px] font-medium tabular-nums leading-none text-gray-400"
                >{{ l.label }}</span>
                <span class="block text-right text-[10px] font-medium tabular-nums leading-none text-gray-400">{{ currency }}0</span>
            </div>

            <!-- Chart area -->
            <div class="min-w-0 flex-1">

                <!-- SVG + invisible hover columns stacked -->
                <div class="relative" :style="`height: ${VH}px;`">

                    <!-- SVG chart -->
                    <svg
                        :viewBox="`0 0 ${VW} ${VH}`"
                        class="absolute inset-0 h-full w-full"
                        preserveAspectRatio="none"
                    >
                        <defs>
                            <linearGradient id="soAreaGrad" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%"   stop-color="#10b981" stop-opacity="0.22" />
                                <stop offset="65%"  stop-color="#10b981" stop-opacity="0.05" />
                                <stop offset="100%" stop-color="#10b981" stop-opacity="0"    />
                            </linearGradient>
                            <linearGradient id="soLineGrad" x1="0" y1="0" x2="1" y2="0">
                                <stop offset="0%"   stop-color="#34d399" />
                                <stop offset="100%" stop-color="#059669" />
                            </linearGradient>
                        </defs>

                        <!-- Horizontal grid lines -->
                        <line
                            v-for="gl in gridLines" :key="'g' + gl.y"
                            :x1="PL" :y1="gl.y" :x2="VW - PR" :y2="gl.y"
                            stroke="#f3f4f6" stroke-width="1"
                        />
                        <!-- Baseline -->
                        <line :x1="PL" :y1="PT + cH" :x2="VW - PR" :y2="PT + cH" stroke="#e5e7eb" stroke-width="1" />

                        <!-- Dashed vertical hover indicator -->
                        <line
                            v-if="hoveredIndex !== null && svgPoints[hoveredIndex]"
                            :x1="svgPoints[hoveredIndex].x" :y1="PT"
                            :x2="svgPoints[hoveredIndex].x" :y2="PT + cH"
                            stroke="#a7f3d0" stroke-width="1.5" stroke-dasharray="4 3"
                        />

                        <!-- Area fill -->
                        <path :d="areaPath" fill="url(#soAreaGrad)" />

                        <!-- Line stroke -->
                        <path
                            :d="linePath"
                            fill="none"
                            stroke="url(#soLineGrad)"
                            stroke-width="2.5"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        />

                        <!-- Dot markers (visible on hover) -->
                        <circle
                            v-for="(pt, i) in svgPoints" :key="'d' + i"
                            :cx="pt.x" :cy="pt.y" r="5"
                            fill="#10b981" stroke="white" stroke-width="2.5"
                            :opacity="hoveredIndex === i ? 1 : 0"
                            style="transition: opacity 0.15s"
                        />
                    </svg>

                    <!-- Invisible hover columns (sit on top of SVG for mouse events) -->
                    <div class="absolute inset-0 flex">
                        <div
                            v-for="(day, i) in days"
                            :key="'h' + i"
                            class="relative flex-1"
                            @mouseenter="hoveredIndex = i"
                            @mouseleave="hoveredIndex = null"
                        >
                            <!-- Tooltip -->
                            <div
                                v-if="hoveredIndex === i"
                                class="pointer-events-none absolute z-30 whitespace-nowrap rounded-xl bg-gray-900 px-3.5 py-2.5 shadow-2xl ring-1 ring-white/10"
                                :style="getTooltipStyle(i)"
                            >
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-emerald-400">{{ day.date }}</p>
                                <p class="mt-1 text-sm font-bold tabular-nums text-white">{{ formatFull(day.value) }}</p>
                                <p class="mt-0.5 text-[10px] text-gray-400">{{ day.count }} booking{{ day.count !== 1 ? 's' : '' }}</p>
                                <!-- Arrow -->
                                <div class="absolute -bottom-1.5 left-1/2 h-3 w-3 -translate-x-1/2 rotate-45 rounded-sm bg-gray-900"></div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Day labels -->
                <div class="mt-2 flex">
                    <div v-for="(day, i) in days" :key="'l' + i" class="flex flex-1 flex-col items-center">
                        <p
                            class="text-[11px] font-semibold leading-none transition-colors duration-100"
                            :class="hoveredIndex === i ? 'text-gray-900' : 'text-gray-400'"
                        >{{ day.day }}</p>
                        <p class="mt-0.5 text-[9px] tabular-nums leading-none text-gray-400">{{ day.date }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
