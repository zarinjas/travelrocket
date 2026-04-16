<script setup>
defineProps({
    title: String,
    monthLabel: String,
    items: {
        type: Array,
        default: () => [],
    },
    primaryAccentColor: {
        type: String,
        default: '#5b7cff',
    },
});
</script>

<template>
    <article class="rounded-2xl border border-slate-100 bg-white p-5 shadow-[0_2px_20px_rgb(0,0,0,0.02)] transition-all duration-200 ease-in-out hover:-translate-y-0.5">
        <div class="flex items-center justify-between">
            <p class="truncate whitespace-nowrap text-[11px] font-semibold uppercase tracking-[0.2em] text-slate-500">{{ title }}</p>
            <p class="truncate whitespace-nowrap text-xs text-slate-500">{{ monthLabel }}</p>
        </div>

        <div class="mt-4 space-y-3">
            <article
                v-for="item in items"
                :key="item.title"
                class="rounded-xl border border-slate-200 bg-white px-4 py-3 transition hover:-translate-y-0.5 hover:bg-slate-50"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0 flex-1">
                        <p class="truncate whitespace-nowrap text-xs font-semibold text-slate-900">{{ item.title }}</p>
                        <p class="mt-1 truncate whitespace-nowrap text-[11px] leading-5 text-slate-500">{{ item.body }}</p>
                    </div>
                    <span
                        class="shrink-0 whitespace-nowrap rounded-full px-2.5 py-1 text-[10px] font-semibold uppercase tracking-[0.16em] shadow-sm"
                        :style="{ backgroundColor: `${primaryAccentColor}14`, color: primaryAccentColor }"
                    >
                        {{ item.time }}
                    </span>
                </div>
            </article>
        </div>
    </article>
</template>
