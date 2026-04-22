<script setup>
import { computed, ref } from 'vue';

const props = defineProps({
    productName: {
        type: String,
        default: 'TravelRocket',
    },
    workspaceName: {
        type: String,
        default: 'Workspace',
    },
    logoUrl: {
        type: String,
        default: null,
    },
    userName: {
        type: String,
        default: 'Muhamad Hafizzudin',
    },
    userRole: {
        type: String,
        default: 'Admin',
    },
    roleForScheme: {
        type: String,
        default: 'Owner',
    },
    schemes: {
        type: Object,
        default: () => ({}),
    },
    sections: {
        type: Array,
        default: () => [],
    },
    collapsed: {
        type: Boolean,
        default: false,
    },
});

const emit = defineEmits(['navigate', 'logout']);

const openGroups = ref({});

const iconPaths = {
    dashboard: [
        'M3 4.5A1.5 1.5 0 0 1 4.5 3h4A1.5 1.5 0 0 1 10 4.5v4A1.5 1.5 0 0 1 8.5 10h-4A1.5 1.5 0 0 1 3 8.5v-4Z',
        'M14 4.5A1.5 1.5 0 0 1 15.5 3h4A1.5 1.5 0 0 1 21 4.5v4A1.5 1.5 0 0 1 19.5 10h-4A1.5 1.5 0 0 1 14 8.5v-4Z',
        'M3 15.5A1.5 1.5 0 0 1 4.5 14h4a1.5 1.5 0 0 1 1.5 1.5v4A1.5 1.5 0 0 1 8.5 21h-4A1.5 1.5 0 0 1 3 19.5v-4Z',
        'M14 15.5a1.5 1.5 0 0 1 1.5-1.5h4a1.5 1.5 0 0 1 1.5 1.5v4a1.5 1.5 0 0 1-1.5 1.5h-4a1.5 1.5 0 0 1-1.5-1.5v-4Z',
    ],
    settings: [
        'M10.325 4.317a1 1 0 0 1 1.35-.936l.825.34a1 1 0 0 0 1.09-.217l.585-.585a1 1 0 0 1 1.414 0l1.172 1.172a1 1 0 0 1 0 1.414l-.585.585a1 1 0 0 0-.217 1.09l.34.825a1 1 0 0 1-.936 1.35h-.828a1 1 0 0 0-.993.883l-.1.86a1 1 0 0 1-.993.883h-1.658a1 1 0 0 1-.993-.883l-.1-.86a1 1 0 0 0-.993-.883h-.828a1 1 0 0 1-.936-1.35l.34-.825a1 1 0 0 0-.217-1.09l-.585-.585a1 1 0 0 1 0-1.414L7.335 2.92a1 1 0 0 1 1.414 0l.585.585a1 1 0 0 0 1.09.217l.825-.34Z',
        'M12 8.75A3.25 3.25 0 1 1 8.75 12 3.25 3.25 0 0 1 12 8.75Z',
    ],
    package: ['M3 7.5 12 3l9 4.5-9 4.5-9-4.5Zm0 4.5 9 4.5 9-4.5M3 16.5 12 21l9-4.5'],
    quotation: ['M6 3.75h9A2.25 2.25 0 0 1 17.25 6v12a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z', 'M7.5 8.25h6M7.5 12h6M7.5 15.75h4.5'],
    invoice: ['M7.5 3.75h7.5l3 3V19.5A1.5 1.5 0 0 1 16.5 21h-9A1.5 1.5 0 0 1 6 19.5v-14A1.75 1.75 0 0 1 7.5 3.75Z', 'M15 3.75V7.5h3', 'M9 12h6M9 15h4.5'],
    booking: ['M4.5 6.75h15v10.5a1.5 1.5 0 0 1-1.5 1.5h-12A1.5 1.5 0 0 1 4.5 17.25V6.75Z', 'M7.5 4.5v4.5M16.5 4.5v4.5M4.5 10.5h15'],
    document: ['M6.75 3.75h7.5l3 3v12.75a1.5 1.5 0 0 1-1.5 1.5h-9a1.5 1.5 0 0 1-1.5-1.5V5.25a1.5 1.5 0 0 1 1.5-1.5Z', 'M14.25 3.75V7.5H18'],
    customers: ['M16.5 18.75v-1.5A3.75 3.75 0 0 0 12.75 13.5h-6A3.75 3.75 0 0 0 3 17.25v1.5', 'M9.75 9.75A3 3 0 1 0 9.75 3.75a3 3 0 0 0 0 6ZM19.5 18.75v-1.5a3.75 3.75 0 0 0-2.25-3.43', 'M15.75 3.97a3 3 0 0 1 0 5.78'],
    campaign: ['M3.75 6.75h16.5v10.5H3.75z', 'M3.75 7.5 12 13.5l8.25-6'],
    newsletter: ['M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75'],
    cashflow: ['M3.75 15.75 8.25 11.25l3 3 5.25-6 3 3.75', 'M3.75 20.25h16.5'],
    report: ['M6 3.75h12A2.25 2.25 0 0 1 20.25 6v12A2.25 2.25 0 0 1 18 20.25H6A2.25 2.25 0 0 1 3.75 18V6A2.25 2.25 0 0 1 6 3.75Z', 'M8.25 15V9M12 15V6.75M15.75 15v-3.75'],
    rooming: ['M4.5 6.75h15v9a2.25 2.25 0 0 1-2.25 2.25h-10.5A2.25 2.25 0 0 1 4.5 15.75v-9Z', 'M7.5 3.75v3M16.5 3.75v3M4.5 9h15'],
    dot: ['M12 12h.01'],
};

const iconViewBox = (name) => (name === 'dot' ? '0 0 24 24' : '0 0 24 24');

const iconStrokeWidth = (name) => (name === 'dot' ? '4' : '1.8');

const fallbackSections = computed(() => [
    {
        title: 'QUICK ACTIONS',
        items: [
            { label: 'Dashboard', href: '#', icon: 'dashboard', active: true },
            { label: 'Settings', href: '#', icon: 'settings' },
        ],
    },
    {
        title: 'FINANCE (Primary)',
        items: [
            { label: 'Invoices', href: '#', icon: 'invoice', badge: '18' },
            { label: 'Quotations', href: '#', icon: 'quotation', badge: '14' },
            { label: 'Bookings & Sales', href: '#', icon: 'booking', badge: '29' },
        ],
    },
    {
        title: 'PACKAGES',
        items: [
            { label: 'Packages', href: '#', icon: 'package', badge: '8' },
        ],
    },
    {
        title: 'DOCUMENTS',
        items: [
            { label: 'Rooming List', href: '/workspace/rooming-list', icon: 'rooming' },
        ],
    },
    {
        title: 'CUSTOMERS & MARKETING',
        items: [
            { label: 'Customers', href: '#', icon: 'customers', badge: '56' },
            { label: 'Campaigns', href: '#', icon: 'campaign', badge: '7' },
        ],
    },
    {
        title: 'REPORTS & ANALYTICS',
        items: [
            { label: 'Cashflow Report', href: '#', icon: 'cashflow' },
            { label: 'Financial Export', href: '#', icon: 'report' },
        ],
    },
]);

const navSections = computed(() => (props.sections?.length ? props.sections : fallbackSections.value));

const groupKey = (section, item) => `${section.title}-${item.label}`;

const isItemActive = (item) => {
    if (item.active) {
        return true;
    }

    return Array.isArray(item.children) ? item.children.some((child) => child.active) : false;
};

const isGroupOpen = (section, item) => {
    const key = groupKey(section, item);
    const explicit = openGroups.value[key];

    if (typeof explicit === 'boolean') {
        return explicit;
    }

    return isItemActive(item);
};

const toggleGroup = (section, item) => {
    const key = groupKey(section, item);
    openGroups.value[key] = !isGroupOpen(section, item);
};

const onNavigate = (section, item) => {
    if (item?.children?.length) {
        if (props.collapsed && item.href) {
            emit('navigate', item);
            return;
        }

        toggleGroup(section, item);
        return;
    }

    emit('navigate', item);
};

const logout = () => {
    emit('logout');
};

</script>

<template>
    <aside class="workspace-sidebar flex h-full min-h-0 flex-col overflow-hidden bg-white/80 p-4">
        <div class="px-1 py-2">
            <div class="flex items-center" :class="props.collapsed ? 'justify-center' : 'gap-2.5'">
                <img v-if="props.logoUrl" :src="props.logoUrl" alt="Logo" class="h-10 w-10 rounded-xl object-contain shadow-[0_10px_24px_rgba(17,24,39,0.35)]" />
                <span v-else class="inline-flex h-10 w-10 items-center justify-center rounded-xl bg-gradient-to-br from-slate-700 to-slate-900 text-xs font-bold text-white shadow-[0_10px_24px_rgba(17,24,39,0.35)]">TR</span>
                <div v-if="!props.collapsed">
                    <p class="text-[1.75rem] leading-none font-extrabold tracking-tight text-slate-900">{{ productName }}</p>
                    <p class="mt-0.5 text-[10px] uppercase tracking-[0.14em] text-slate-500">{{ workspaceName }}</p>
                </div>
            </div>
        </div>

        <div class="mt-3 min-h-0 flex-1 space-y-3 overflow-y-auto px-1">
            <section v-for="section in navSections" :key="section.title" class="space-y-1.5">
                <p v-if="!props.collapsed" class="px-3 py-2 text-[10px] font-bold uppercase tracking-[0.16em] text-slate-500" :class="{ 'text-emerald-600': section.title.includes('FINANCE') }">
                    {{ section.title }}
                </p>

                <template v-for="item in section.items" :key="`${section.title}-${item.label}`">
                    <button
                        type="button"
                        class="workspace-nav-item flex w-full items-center transition-all duration-200 ease-in-out group"
                        :class="[
                            props.collapsed ? 'justify-center' : 'justify-between',
                            isItemActive(item)
                                ? 'bg-[linear-gradient(140deg,#4b5563_0%,#111827_65%,#0b0f17_100%)] text-white font-semibold border-transparent flex items-center px-3 py-2.5 text-sm rounded-full cursor-default shadow-[0_14px_28px_rgba(17,24,39,0.34)]'
                                : 'cursor-pointer rounded-full border border-transparent bg-transparent px-3 py-2.5 text-left text-sm font-medium text-slate-600 hover:bg-white/90 hover:text-slate-900'
                        ]"
                        :title="props.collapsed ? item.label : ''"
                        @click="onNavigate(section, item)"
                    >
                        <span class="flex items-center" :class="props.collapsed ? 'justify-center' : 'gap-2.5'">
                            <span class="inline-flex h-4 w-4 items-center justify-center">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" :viewBox="iconViewBox(item.icon || 'dot')" stroke="currentColor" :stroke-width="iconStrokeWidth(item.icon || 'dot')" class="h-4 w-4 transition-transform group-hover:scale-110">
                                    <path v-for="(path, index) in (iconPaths[item.icon] || iconPaths.dot)" :key="`icon-${index}`" stroke-linecap="round" stroke-linejoin="round" :d="path" />
                                </svg>
                            </span>
                            <span v-if="!props.collapsed" class="flex-1 text-left">{{ item.label }}</span>
                        </span>
                        <span v-if="!props.collapsed && item.children?.length" class="ml-auto text-xs" :class="isItemActive(item) ? 'text-white/85' : 'text-slate-500'">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4 transition-transform" :class="{ 'rotate-180': isGroupOpen(section, item) }">
                                <path d="M6 8l4 4 4-4" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </span>
                        <span v-else-if="!props.collapsed && item.badge" class="ml-auto rounded-full bg-slate-200 px-2 py-0.5 text-[10px] font-bold text-slate-700 group-hover:bg-slate-300">{{ item.badge }}</span>
                    </button>

                    <div v-if="!props.collapsed && item.children?.length && isGroupOpen(section, item)" class="mt-1 space-y-1 pl-9">
                        <button
                            v-for="child in item.children"
                            :key="`${section.title}-${item.label}-${child.label}`"
                            type="button"
                            class="flex w-full items-center justify-between rounded-full px-3 py-2 text-left text-xs font-semibold transition"
                            :class="child.active ? 'bg-slate-900 text-white shadow-[0_8px_20px_rgba(17,24,39,0.25)]' : 'text-slate-600 hover:bg-white/90 hover:text-slate-900'"
                            @click="emit('navigate', child)"
                        >
                            <span class="flex items-center gap-2">
                                <span class="inline-flex h-2 w-2 rounded-full" :class="child.active ? 'bg-emerald-300' : 'bg-slate-300'"></span>
                                <span>{{ child.label }}</span>
                            </span>
                        </button>
                    </div>

                </template>
            </section>
        </div>

        <div class="mt-auto border-t border-slate-200 pt-4">
            <button
                type="button"
                class="flex w-full items-center rounded-full border border-transparent bg-transparent px-3 py-2.5 text-left text-xs font-semibold text-slate-500 transition hover:bg-white/90 hover:text-rose-600"
                :class="props.collapsed ? 'justify-center' : 'justify-between'"
                :title="props.collapsed ? 'Sign out' : ''"
                @click="logout"
            >
                <span class="flex items-center" :class="props.collapsed ? 'justify-center' : 'gap-2'">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" class="h-4 w-4">
                        <path d="M10 17l5-5-5-5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                        <path d="M15 12H3" stroke-width="1.8" stroke-linecap="round" />
                        <path d="M7 21h10a2 2 0 0 0 2-2V5a2 2 0 0 0-2-2H7" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                    </svg>
                    <span v-if="!props.collapsed">Sign Out</span>
                </span>
            </button>
        </div>
    </aside>
</template>
