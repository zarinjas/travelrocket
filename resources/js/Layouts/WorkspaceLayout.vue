<script setup>
import { computed, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';
import PremiumSidebar from '@/Components/Workspace/PremiumSidebar.vue';
import PremiumMainHeader from '@/Components/Workspace/PremiumMainHeader.vue';

const page = usePage();

const tenantSlug = page.props.tenant?.slug;
const workspaceSlug = page.props.workspace?.slug;
const activeWorkspaceSlug = tenantSlug ?? workspaceSlug ?? '';
const userName = page.props.auth.user?.name ?? 'Workspace User';
const userRole = page.props.ui?.role?.label ?? page.props.auth.user?.role ?? 'Owner';
const roleForScheme = page.props.auth.user?.role ?? 'owner';

const roleSchemes = {
    owner: '#d97706',
    staff: '#0f766e',
    platform_admin: '#334155',
};

const isSidebarCollapsed = ref(false);
const isMobileSidebarOpen = ref(false);

// Close mobile sidebar on navigation
watch(() => page.url, () => {
    isMobileSidebarOpen.value = false;
});

const pageTitle = computed(() => {
    if (page.url.includes('/dashboard')) {
        return 'Analytics Dashboard';
    }

    if (page.url.includes('/bookings')) {
        return 'Bookings Control';
    }

    if (page.url.includes('/customers')) {
        return 'CRM Workspace';
    }

    if (page.url.includes('/reports')) {
        return 'Financial Analytics';
    }

    if (page.url.includes('/settings')) {
        return 'Workspace Settings';
    }

    return `${page.props.workspace?.name ?? 'Workspace'} Control Center`;
});

const workspacePath = (path = '') => (activeWorkspaceSlug ? `/workspace/${activeWorkspaceSlug}${path}` : '#');

const logout = () => {
    router.post('/logout');
};

const toggleSidebar = () => {
    isSidebarCollapsed.value = !isSidebarCollapsed.value;
};

const handleNavigate = (item) => {
    if (!item?.href || item.href === '#') {
        return;
    }

    router.visit(item.href);
};

const navigationSections = [
    {
        title: 'MAIN',
        items: [
            { label: 'Dashboard', href: workspacePath('/dashboard'), active: page.url.includes('/dashboard'), icon: 'dashboard' },
            { label: 'Settings', href: workspacePath('/settings'), active: page.url.includes('/settings'), icon: 'settings' },
        ],
    },
    {
        title: 'PACKAGES',
        items: [
            {
                label: 'Packages',
                href: workspacePath('/packages'),
                icon: 'package',
                active: page.url.includes('/packages') || page.url.includes('/tour-setup'),
                children: [
                    { label: 'Umrah', href: workspacePath('/packages?category=Umrah'), active: page.url.includes('/packages') && page.url.includes('category=Umrah'), icon: 'dot' },
                    { label: 'Inbound', href: workspacePath('/packages?category=Inbound%20Tours'), active: page.url.includes('/packages') && page.url.includes('category=Inbound%20Tours'), icon: 'dot' },
                    { label: 'Outbound', href: workspacePath('/packages?category=Outbound%20Tours'), active: page.url.includes('/packages') && page.url.includes('category=Outbound%20Tours'), icon: 'dot' },
                    { label: 'Domestic', href: workspacePath('/packages?category=Domestic%20Tours'), active: page.url.includes('/packages') && page.url.includes('category=Domestic%20Tours'), icon: 'dot' },
                    { label: 'Tour Setup', href: workspacePath('/tour-setup'), active: page.url.includes('/tour-setup'), icon: 'dot' },
                ],
            },
        ],
    },
    {
        title: 'SALES & FINANCE',
        items: [],
    },
    {
        title: 'DOCUMENTS',
        items: [],
    },
    {
        title: 'CRM',
        items: [],
    },
    {
        title: 'REPORTS',
        items: [],
    },
];

navigationSections[2].items = [
    { label: 'Booking & Sales', href: workspacePath('/bookings'), active: page.url.includes('/bookings'), icon: 'booking' },
    { label: 'Quotations', href: workspacePath('/quotations'), active: page.url.includes('/quotations'), icon: 'quotation' },
    { label: 'Invoices', href: workspacePath('/invoices'), active: page.url.includes('/invoices'), icon: 'invoice' },
];

navigationSections[3].items = [
    { label: 'Surat Melancong', href: workspacePath('/tourism-letters'), active: page.url.includes('/tourism-letters'), icon: 'document' },
];

navigationSections[4].items = [
    { label: 'Customer Database', href: workspacePath('/customers'), active: page.url.includes('/customers') && !page.url.includes('/customers/blast'), icon: 'customers' },
    { label: 'Blast Campaigns', href: workspacePath('/customers/blast'), active: page.url.includes('/customers/blast'), icon: 'campaign' },
    { label: 'Newsletters', href: workspacePath('/newsletters'), active: page.url.includes('/newsletters'), icon: 'newsletter' },
];

navigationSections[5].items = [
    { label: 'Cashflow Command Center', href: workspacePath('/cashflow-command-center'), active: page.url.includes('/cashflow-command-center'), icon: 'cashflow' },
    { label: 'Financials & Exports', href: workspacePath('/reports'), active: page.url.includes('/reports'), icon: 'report' },
];

</script>

<template>
    <div class="workspace-shell flex h-screen w-full">
        <!-- Mobile sidebar overlay -->
        <Teleport to="body">
            <Transition name="mobile-sidebar">
                <div v-if="isMobileSidebarOpen" class="fixed inset-0 z-[60] md:hidden">
                    <div class="absolute inset-0 bg-black/40 backdrop-blur-sm" @click="isMobileSidebarOpen = false" />
                    <aside class="absolute inset-y-0 left-0 w-72 overflow-y-auto bg-white shadow-xl">
                        <PremiumSidebar
                            :product-name="'TravelRocket'"
                            :workspace-name="page.props.workspace?.name ?? page.props.tenant?.name ?? 'Workspace'"
                            :logo-url="page.props.tenant?.logo_url"
                            :user-name="userName"
                            :user-role="userRole"
                            :role-for-scheme="roleForScheme"
                            :schemes="roleSchemes"
                            :sections="navigationSections"
                            :collapsed="false"
                            @navigate="handleNavigate"
                            @logout="logout"
                        />
                    </aside>
                </div>
            </Transition>
        </Teleport>

        <!-- Desktop sidebar -->
        <aside
            class="relative hidden h-screen flex-shrink-0 overflow-hidden border-r border-transparent bg-white/80 md:block"
            :class="isSidebarCollapsed ? 'w-24' : 'w-72'"
        >
            <PremiumSidebar
                :product-name="'TravelRocket'"
                :workspace-name="page.props.workspace?.name ?? page.props.tenant?.name ?? 'Workspace'"
                :logo-url="page.props.tenant?.logo_url"
                :user-name="userName"
                :user-role="userRole"
                :role-for-scheme="roleForScheme"
                :schemes="roleSchemes"
                :sections="navigationSections"
                :collapsed="isSidebarCollapsed"
                @navigate="handleNavigate"
                @logout="logout"
            />

            <button
                type="button"
                class="workspace-sidebar-toggle absolute -right-3 top-1/2 z-50 cursor-pointer rounded-full border border-white/80 bg-gradient-to-b from-slate-700 to-slate-900 p-2 text-white shadow-[0_10px_24px_rgba(17,24,39,0.38)] transition-all duration-200 ease-in-out hover:scale-105 hover:from-slate-600 hover:to-slate-800 active:scale-95"
                :aria-label="isSidebarCollapsed ? 'Expand sidebar' : 'Collapse sidebar'"
                @click="toggleSidebar"
            >
                <svg v-if="isSidebarCollapsed" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4">
                    <path d="M8 5l5 5-5 5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
                <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="none" stroke="currentColor" class="h-4 w-4">
                    <path d="M12 5l-5 5 5 5" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" />
                </svg>
            </button>
        </aside>

        <div class="flex flex-1 flex-col overflow-hidden">
            <header class="workspace-header sticky top-0 z-50 w-full flex-shrink-0 border-b border-slate-200 bg-white/85">
                <PremiumMainHeader
                    :title="pageTitle"
                    :subtitle="'How can I help your operations today?'"
                    :user-name="userName"
                    :user-role="userRole"
                    :schemes="roleSchemes"
                    :profile-href="workspacePath('/settings?tab=company-profile')"
                    :settings-href="workspacePath('/settings')"
                    @logout="logout"
                    @toggle-mobile-sidebar="isMobileSidebarOpen = !isMobileSidebarOpen"
                />
            </header>

            <main class="workspace-content workspace-main-panel m-2 flex-1 overflow-y-auto rounded-2xl p-3 sm:m-3 sm:rounded-[1.9rem] sm:p-4 md:m-4 md:p-5">
                <div class="flex min-h-0 flex-1 flex-col gap-4">
                    <div
                        v-if="page.props.flash?.success"
                        class="rounded-[20px] border px-5 py-4 text-sm"
                        :style="{ borderColor: '#79b8f155', backgroundColor: '#79b8f118', color: '#1e4b86' }"
                    >
                        {{ page.props.flash.success }}
                    </div>

                    <slot />
                </div>
            </main>
        </div>
    </div>
</template>

<style scoped>
.mobile-sidebar-enter-active,
.mobile-sidebar-leave-active {
    transition: opacity 0.2s ease;
}
.mobile-sidebar-enter-active aside,
.mobile-sidebar-leave-active aside {
    transition: transform 0.2s ease;
}
.mobile-sidebar-enter-from,
.mobile-sidebar-leave-to {
    opacity: 0;
}
.mobile-sidebar-enter-from aside,
.mobile-sidebar-leave-to aside {
    transform: translateX(-100%);
}
</style>
