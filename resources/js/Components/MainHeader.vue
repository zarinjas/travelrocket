<script setup>
defineProps({
    title: {
        type: String,
        required: true,
    },
    subtitle: {
        type: String,
        default: '',
    },
    preparationLabel: {
        type: String,
        default: '',
    },
    progressValue: {
        type: Number,
        default: 0,
    },
    primaryAccentColor: {
        type: String,
        default: '#5b7cff',
    },
    user: {
        type: Object,
        default: () => ({
            name: 'User',
            role: 'owner',
        }),
    },
    notificationCount: {
        type: Number,
        default: 0,
    },
});
</script>

<template>
    <div class="space-y-3">
        <div class="grid gap-3 xl:grid-cols-[minmax(0,1fr)_320px] xl:items-stretch">
            <div class="rounded-[28px] border border-white/10 bg-[#0b1626] px-5 py-4 shadow-[0_16px_40px_rgba(0,0,0,0.25)]">
                <div>
                    <p class="text-xs font-medium uppercase tracking-[0.2em] text-slate-400 whitespace-nowrap">Workspace overview</p>
                    <h1 class="mt-1 text-[1.25rem] font-semibold tracking-tight text-white whitespace-nowrap overflow-hidden text-ellipsis">
                        {{ title }}
                    </h1>
                    <p v-if="subtitle" class="mt-1 max-w-2xl truncate text-xs text-slate-400">{{ subtitle }}</p>
                </div>

                <div class="mt-4 flex flex-wrap items-center gap-2">
                    <span class="inline-flex whitespace-nowrap rounded-full px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em]" :style="{ backgroundColor: `${primaryAccentColor}14`, color: primaryAccentColor }">
                        Active workspace
                    </span>
                    <span class="inline-flex whitespace-nowrap rounded-full border border-white/10 bg-white/5 px-3 py-1.5 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-300">
                        {{ preparationLabel || 'Preparation pending' }}
                    </span>
                </div>

                <div class="mt-4 h-2.5 overflow-hidden rounded-full bg-white/10">
                    <div
                        class="h-full rounded-full transition-all"
                        :style="{ width: `${progressValue}%`, backgroundColor: primaryAccentColor }"
                    />
                </div>
            </div>

            <div class="rounded-[28px] border border-white/10 bg-[#0b1626] px-4 py-4 shadow-[0_16px_40px_rgba(0,0,0,0.25)]">
                <div class="flex items-center justify-between gap-2">
                    <div class="flex min-w-0 items-center gap-3 rounded-full border border-white/10 bg-white/5 px-4 py-3 text-xs text-slate-400">
                        <span class="text-slate-300">⌕</span>
                        <span class="truncate whitespace-nowrap">Search workspace</span>
                    </div>

                    <button type="button" class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300">
                        ◐
                    </button>
                </div>

                <div class="mt-4 flex items-center justify-between gap-2">
                    <button type="button" class="flex items-center gap-2 rounded-full border border-white/10 bg-white/5 px-4 py-2 text-xs font-medium text-slate-300 whitespace-nowrap">
                        <span>▣</span>
                        Monthly
                    </button>

                    <button type="button" class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-full border border-white/10 bg-white/5 text-slate-300">
                        ◌
                        <span
                            v-if="notificationCount > 0"
                            class="absolute right-0 top-0 flex h-5 min-w-[20px] items-center justify-center rounded-full px-1 text-[10px] font-semibold text-white"
                            :style="{ backgroundColor: primaryAccentColor }"
                        >
                            {{ notificationCount }}
                        </span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
