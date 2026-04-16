<script setup>
defineProps({
    title: String,
    legend: {
        type: Array,
        default: () => [],
    },
    rows: {
        type: Array,
        default: () => [],
    },
    accent: {
        type: String,
        default: '#5b7cff',
    },
});

const toneClasses = {
    low: 'bg-slate-200',
    medium: 'bg-sky-100',
    high: 'bg-indigo-100',
    full: 'bg-amber-100',
    empty: 'bg-slate-100',
};
</script>

<template>
    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_2px_20px_rgb(0,0,0,0.02)] transition-all duration-200 ease-in-out hover:-translate-y-0.5">
        <div class="flex items-center justify-between gap-3">
            <p class="truncate whitespace-nowrap text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ title }}</p>
            <div class="flex flex-nowrap items-center gap-2 overflow-x-auto text-[10px] text-slate-500">
                <span
                    v-for="item in legend"
                    :key="item.label"
                    class="inline-flex items-center gap-2 whitespace-nowrap"
                >
                    <span class="h-2.5 w-2.5 rounded-full" :class="toneClasses[item.tone]" />
                    {{ item.label }}
                </span>
            </div>
        </div>

        <div class="mt-4 grid gap-2.5">
            <div
                v-for="row in rows"
                :key="row.label"
                class="grid grid-cols-[42px_1fr] items-center gap-2"
            >
                <p class="truncate whitespace-nowrap text-[10px] font-medium uppercase tracking-[0.12em] text-slate-500">{{ row.label }}</p>
                <div class="grid grid-cols-7 gap-2">
                    <div
                        v-for="(cell, index) in row.cells"
                        :key="`${row.label}-${index}`"
                        class="h-8 rounded-lg shadow-[inset_0_1px_0_rgba(255,255,255,0.45)]"
                        :class="toneClasses[cell]"
                    />
                </div>
            </div>
        </div>
    </article>
</template>
