<script setup>
defineProps({
    title: {
        type: String,
        default: 'TravelRocket',
    },
});

const navigation = [
    { label: 'Home', href: '/' },
    { label: 'Packages', href: '/#packages' },
    { label: 'About', href: '/#about' },
    { label: 'Sign In', href: '/login' },
];
</script>

<template>
    <div class="public-shell min-h-screen">
        <header class="border-b border-slate-200 bg-white/85 backdrop-blur-xl">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 lg:px-8">
                <a href="/" class="flex items-center gap-3 text-xl font-bold tracking-tight text-slate-900">
                    <span class="flex h-10 w-10 items-center justify-center rounded-2xl bg-brand-500 text-sm text-white shadow-sm">TR</span>
                    <span>TravelRocket</span>
                </a>

                <nav class="hidden items-center gap-6 md:flex">
                    <a
                        v-for="item in navigation"
                        :key="item.href"
                        :href="item.href"
                        class="text-sm font-semibold text-slate-700 transition hover:text-slate-900"
                    >
                        {{ item.label }}
                    </a>
                </nav>

                <div class="hidden items-center gap-2 md:flex">
                    <a
                        href="/login"
                        class="rounded-full border border-brand-500 bg-brand-500 px-4 py-2 text-sm font-semibold text-white transition hover:bg-brand-700"
                    >
                        Sign In
                    </a>
                </div>

                <a
                    href="/login"
                    class="rounded-full border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 md:hidden"
                >
                    Sign In
                </a>
            </div>
            <div class="border-t border-slate-200 px-6 py-3 md:hidden">
                <nav class="mx-auto flex max-w-6xl flex-wrap gap-3 text-sm">
                    <a
                        v-for="item in navigation"
                        :key="`mobile-${item.href}`"
                        :href="item.href"
                        class="rounded-full border border-slate-300 bg-white px-3 py-1.5 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900"
                    >
                        {{ item.label }}
                    </a>
                </nav>
            </div>
        </header>

        <slot />

        <footer class="border-t border-slate-200 bg-white/80">
            <div class="mx-auto flex max-w-6xl flex-col gap-3 px-6 py-8 text-sm text-slate-700 lg:flex-row lg:items-center lg:justify-between lg:px-8">
                <p>{{ title }} for travel agencies that need a cleaner operating system.</p>
                <div class="flex gap-4">
                    <a href="/#packages" class="transition hover:text-slate-900">Packages</a>
                    <a href="/login" class="transition hover:text-slate-900">Sign In</a>
                </div>
            </div>
        </footer>
    </div>
</template>
