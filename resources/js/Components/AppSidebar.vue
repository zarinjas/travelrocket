<script setup>
import { computed } from 'vue';

defineProps({
    sections: {
        type: Array,
        default: () => [],
    },
    primaryAccentColor: {
        type: String,
        default: '#d77757',
    },
    cta: {
        type: Object,
        default: null,
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

defineEmits(['toggle-collapse']);

const inactiveIconStyle = computed(() => ({
    backgroundColor: 'rgba(15,35,62,0.08)',
    color: '#3b506f',
}));
</script>

<template>
    <aside class="workspace-sidebar group relative flex h-[calc(100vh-7.75rem)] flex-col overflow-hidden border-r border-white/10 bg-[#06101d] px-2.5 py-2.5">
        <button
            type="button"
            class="workspace-sidebar-toggle absolute -right-2 top-1/2 z-20 hidden h-12 w-5 -translate-y-1/2 items-center justify-center rounded-r-xl border border-white/10 border-l-0 bg-[#0b1727] text-[11px] font-semibold text-slate-300 opacity-35 shadow-[0_8px_24px_rgba(0,0,0,0.35)] transition hover:bg-[#112035] hover:opacity-100 focus-visible:opacity-100 group-hover:opacity-100 lg:flex"
            :aria-label="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            :title="collapsed ? 'Expand sidebar' : 'Collapse sidebar'"
            @click="$emit('toggle-collapse')"
        >
            {{ collapsed ? '›' : '‹' }}
        </button>

        <div class="space-y-3 overflow-y-auto pr-1">
            <section
                v-for="section in sections"
                :key="section.title"
                class="space-y-2"
            >
                <p v-if="!collapsed" class="px-2 text-[10px] font-semibold uppercase tracking-[0.16em] text-slate-500">
                    {{ section.title }}
                </p>

                <nav class="space-y-1">
                    <a
                        v-for="item in section.items"
                        :key="item.label"
                        :href="item.href"
                        class="workspace-nav-item flex items-center justify-between rounded-xl px-2.5 py-2 text-xs font-medium transition"
                        :class="item.active ? 'shadow-sm' : 'text-slate-300 hover:bg-white/5 hover:text-white'"
                        :style="item.active ? { backgroundColor: primaryAccentColor, color: '#fff' } : {}"
                    >
                        <span class="flex items-center gap-3">
                            <span
                                class="inline-flex h-7 w-7 items-center justify-center rounded-lg text-[10px] font-semibold"
                                :style="item.active ? { backgroundColor: 'rgba(255,255,255,0.18)', color: '#fff' } : inactiveIconStyle"
                            >
                                {{ item.icon ?? '•' }}
                            </span>
                            <span v-if="!collapsed">{{ item.label }}</span>
                        </span>

                        <span
                            v-if="item.badge && !collapsed"
                            class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            :style="item.active ? { backgroundColor: 'rgba(255,255,255,0.18)', color: '#fff' } : { backgroundColor: 'rgba(255,255,255,0.05)', color: '#cbd5e1' }"
                        >
                            {{ item.badge }}
                        </span>
                    </a>
                </nav>
            </section>
        </div>

        <div class="mt-auto space-y-3 pt-4">
            <a
                v-if="cta && cta.href"
                :href="cta.href"
                class="flex items-center justify-center rounded-2xl px-4 py-3 text-sm font-semibold text-white transition hover:brightness-110"
                :style="{ backgroundColor: primaryAccentColor }"
            >
                <span v-if="!collapsed">{{ cta.label }}</span>
                <span v-else>+</span>
            </a>
        </div>
    </aside>
</template>
