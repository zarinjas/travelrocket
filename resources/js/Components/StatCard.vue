<script setup>
defineProps({
    title: String,
    value: [String, Number],
    delta: {
        type: String,
        default: '',
    },
    deltaTone: {
        type: String,
        default: 'positive',
    },
    accent: {
        type: String,
        default: '#5b7cff',
    },
    tone: {
        type: String,
        default: 'neutral',
    },
    icon: {
        type: String,
        default: '📊',
    },
});

const toneClasses = {
    neutral: 'border-slate-200 bg-white hover:shadow-lg',
    blue: 'border-blue-200 bg-gradient-to-br from-blue-50 to-blue-50/50 hover:shadow-lg',
    green: 'border-emerald-200 bg-gradient-to-br from-emerald-50 to-emerald-50/50 hover:shadow-lg',
    purple: 'border-purple-200 bg-gradient-to-br from-purple-50 to-purple-50/50 hover:shadow-lg',
    orange: 'border-orange-200 bg-gradient-to-br from-orange-50 to-orange-50/50 hover:shadow-lg',
};

const toneBorders = {
    neutral: 'text-slate-700',
    blue: 'text-blue-700',
    green: 'text-emerald-700',
    purple: 'text-purple-700',
    orange: 'text-orange-700',
};
</script>

<template>
    <article class="rounded-xl border p-4 shadow-sm transition-all duration-200 ease-in-out hover:-translate-y-0.5" :class="toneClasses[tone] ?? toneClasses.neutral">
        <div class="flex items-start justify-between gap-3">
            <div class="flex-1">
                <p class="text-xs font-semibold uppercase tracking-wider text-slate-500">{{ title }}</p>
                <p class="mt-3 whitespace-nowrap text-2xl font-bold text-slate-900">{{ value }}</p>
                <p
                    v-if="delta"
                    class="mt-2 text-xs text-slate-600"
                >
                    {{ delta }}
                </p>
            </div>
            <div class="flex h-10 w-10 items-center justify-center rounded-lg text-lg opacity-75">
                {{ icon }}
            </div>
        </div>
    </article>
</template>
