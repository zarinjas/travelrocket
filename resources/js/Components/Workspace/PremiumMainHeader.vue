<script setup>
import { computed, toRef } from 'vue';
import { useRoleAccentScheme } from '@/composables/useRoleAccentScheme';

const props = defineProps({
    title: {
        type: String,
        default: 'Analytics Dashboard',
    },
    subtitle: {
        type: String,
        default: 'Operational snapshot and commercial performance.',
    },
    userName: {
        type: String,
        default: 'Workspace User',
    },
    userRole: {
        type: String,
        default: 'Owner',
    },
    schemes: {
        type: Object,
        default: () => ({}),
    },
    profileHref: {
        type: String,
        default: '#',
    },
    settingsHref: {
        type: String,
        default: '#',
    },
});

const emit = defineEmits(['logout', 'toggleMobileSidebar']);

const roleRef = toRef(props, 'userRole');
const schemesRef = toRef(props, 'schemes');
const { accentColor } = useRoleAccentScheme(roleRef, schemesRef);

const userInitials = computed(() => {
    const parts = String(props.userName || 'WU').trim().split(/\s+/).filter(Boolean);

    if (!parts.length) {
        return 'WU';
    }

    if (parts.length === 1) {
        return parts[0].slice(0, 2).toUpperCase();
    }

    return `${parts[0][0] || ''}${parts[1][0] || ''}`.toUpperCase();
});

const todayLabel = computed(() => new Intl.DateTimeFormat('en-MY', {
    weekday: 'short',
    month: 'long',
    day: 'numeric',
}).format(new Date()));
</script>

<template>
    <header class="workspace-header px-4 py-3 md:px-6">
        <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
            <div class="flex items-center gap-4">
                <!-- Mobile hamburger -->
                <button
                    type="button"
                    class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-slate-100 text-slate-600 md:hidden"
                    aria-label="Open sidebar"
                    @click="emit('toggleMobileSidebar')"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5">
                        <path fill-rule="evenodd" d="M2 4.75A.75.75 0 012.75 4h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 4.75zM2 10a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75A.75.75 0 012 10zm0 5.25a.75.75 0 01.75-.75h14.5a.75.75 0 010 1.5H2.75a.75.75 0 01-.75-.75z" clip-rule="evenodd" />
                    </svg>
                </button>

                <div class="relative hidden lg:block">
                    <input
                        type="text"
                        placeholder="Search"
                        class="h-11 w-64 rounded-full border border-transparent bg-slate-100/90 pl-10 pr-24 text-sm font-medium text-slate-700 placeholder:text-slate-500 focus:border-transparent focus:outline-none"
                    />
                    <span class="pointer-events-none absolute left-4 top-1/2 -translate-y-1/2 text-slate-500">⌕</span>
                    <span class="pointer-events-none absolute right-3 top-1/2 -translate-y-1/2 rounded-full bg-emerald-100 px-2.5 py-0.5 text-[10px] font-semibold text-emerald-700">⌘ + /</span>
                </div>

                <div>
                    <p class="text-[11px] font-semibold uppercase tracking-[0.18em] text-slate-500">{{ todayLabel }}</p>
                    <h1 class="mt-1 text-xl font-bold text-slate-900 md:text-2xl">{{ title }}</h1>
                    <p class="mt-0.5 text-xs font-medium text-slate-500">{{ subtitle }}</p>
                </div>
            </div>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    class="inline-flex h-10 items-center gap-2 rounded-full border border-transparent bg-white px-4 text-sm font-semibold text-slate-700 shadow-[0_8px_18px_rgba(17,24,39,0.08)]"
                >
                    <span>◍</span>
                    <span>English</span>
                    <span class="text-xs text-slate-400">⌄</span>
                </button>

                <details class="workspace-user-menu relative">
                    <summary class="flex list-none items-center gap-2 rounded-full border border-transparent bg-white px-2.5 py-1.5 shadow-[0_8px_18px_rgba(17,24,39,0.08)]">
                        <span
                            class="inline-flex h-9 w-9 items-center justify-center rounded-full text-xs font-semibold text-white"
                            :style="{ backgroundColor: accentColor }"
                        >
                            {{ userInitials }}
                        </span>
                        <div class="pr-2 text-left">
                            <p class="text-xs font-semibold text-slate-800">{{ userName }}</p>
                            <p class="text-[10px] tracking-[0.04em] text-slate-500">{{ userRole }}</p>
                        </div>
                    </summary>

                    <div class="workspace-user-dropdown absolute right-0 z-40 mt-2 w-48 rounded-2xl border border-slate-300 bg-white p-2 shadow-[0_12px_30px_rgba(15,23,42,0.12)]">
                        <a :href="profileHref" class="block cursor-pointer rounded-xl px-3 py-2 text-xs font-medium text-slate-700 transition-all duration-200 ease-in-out hover:bg-slate-50">Profile</a>
                        <a :href="settingsHref" class="mt-1 block cursor-pointer rounded-xl px-3 py-2 text-xs font-medium text-slate-700 transition-all duration-200 ease-in-out hover:bg-slate-50">Settings</a>
                        <button type="button" class="mt-1 block w-full cursor-pointer rounded-xl px-3 py-2 text-left text-xs font-medium text-rose-600 transition-all duration-200 ease-in-out hover:bg-rose-50 active:scale-95" @click="emit('logout')">
                            Sign out
                        </button>
                    </div>
                </details>
            </div>
        </div>
    </header>
</template>
