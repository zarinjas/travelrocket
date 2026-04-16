<script setup>
import { Head } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    content: { type: Object, default: () => ({}) },
    packages: { type: Array, default: () => [] },
    categories: { type: Array, default: () => [] },
    destinations: { type: Array, default: () => [] },
    branding: { type: Object, default: () => ({}) },
});

const activeCategory = ref('all');

const filteredPackages = computed(() => {
    if (activeCategory.value === 'all') return props.packages;
    return props.packages.filter(p => p.category === activeCategory.value);
});

const searchQuery = ref('');

const formatPrice = (price) => {
    return 'RM ' + Number(price).toLocaleString('en-MY', { minimumFractionDigits: 0 });
};

const navigation = [
    { label: 'Destinations', href: '#destinations' },
    { label: 'Packages', href: '#packages' },
    { label: 'Gallery', href: '#gallery' },
    { label: 'About', href: '#about' },
];

const usps = [
    { icon: 'shield', title: 'Licensed & Trusted', text: 'MATTA & MOTAC certified travel operators' },
    { icon: 'price', title: 'Best Price Guarantee', text: 'Competitive rates with no hidden charges' },
    { icon: 'support', title: '24/7 Support', text: 'Dedicated support throughout your journey' },
    { icon: 'custom', title: 'Fully Customizable', text: 'Tailor-made packages to suit your needs' },
];

const destinations = computed(() => {
    if (props.destinations.length) return props.destinations;
    return [
        { name: 'Makkah & Madinah', tag: 'Umrah', image: 'https://images.unsplash.com/photo-1591604129939-f1efa4d9f7fa?w=800&q=80' },
        { name: 'Istanbul', tag: 'Turkey', image: 'https://images.unsplash.com/photo-1524231757912-21f4fe3a7200?w=800&q=80' },
        { name: 'Tokyo', tag: 'Japan', image: 'https://images.unsplash.com/photo-1540959733332-eab4deabeeaf?w=800&q=80' },
        { name: 'Seoul', tag: 'Korea', image: 'https://images.unsplash.com/photo-1534274988757-a28bf1a57c17?w=800&q=80' },
        { name: 'London', tag: 'UK', image: 'https://images.unsplash.com/photo-1513635269975-59663e0ac1ad?w=800&q=80' },
    ];
});

const galleryItems = [
    { title: 'Umrah Experience', image: 'https://images.unsplash.com/photo-1564769625905-50e93615e769?w=600&q=80' },
    { title: 'Cultural Tours', image: 'https://images.unsplash.com/photo-1528164344705-47542687000d?w=600&q=80' },
    { title: 'Beach Getaways', image: 'https://images.unsplash.com/photo-1507525428034-b723cf961d3e?w=600&q=80' },
    { title: 'Mountain Adventures', image: 'https://images.unsplash.com/photo-1464822759023-fed622ff2c3b?w=600&q=80' },
    { title: 'City Breaks', image: 'https://images.unsplash.com/photo-1480714378408-67cf0d13bc1b?w=600&q=80' },
    { title: 'Island Hopping', image: 'https://images.unsplash.com/photo-1559128010-7c1ad6e1b6a5?w=600&q=80' },
    { title: 'Desert Safari', image: 'https://images.unsplash.com/photo-1451337516015-6b6e9a44a8a3?w=600&q=80' },
    { title: 'Historical Sites', image: 'https://images.unsplash.com/photo-1539650116574-8efeb43e2750?w=600&q=80' },
];
</script>

<template>
    <Head title="TravelRocket — Discover Your Next Journey" />

    <div class="min-h-screen bg-white">

        <!-- ═══════════ NAVBAR ═══════════ -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-gray-100">
            <!-- Top bar -->
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-16 items-center justify-between">
                    <a href="/" class="flex items-center gap-2.5">
                        <img v-if="branding.logo_url" :src="branding.logo_url" alt="Logo" class="h-9 w-9 rounded-xl object-contain shadow-sm" />
                        <span v-else class="flex h-9 w-9 items-center justify-center rounded-xl bg-blue-600 text-sm font-bold text-white shadow-sm">TR</span>
                        <span class="text-lg font-bold tracking-tight text-gray-900">{{ branding.name || 'TravelRocket' }}</span>
                    </a>

                    <!-- Search -->
                    <div class="hidden md:flex flex-1 max-w-md mx-8">
                        <div class="relative w-full">
                            <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                            <input v-model="searchQuery" type="text" placeholder="Search destinations, packages..." class="w-full rounded-full border-0 bg-gray-50 py-2.5 pl-10 pr-4 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500" />
                        </div>
                    </div>

                    <div class="flex items-center gap-3">
                        <a href="/login" class="inline-flex items-center rounded-full bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700">
                            Sign In
                        </a>
                    </div>
                </div>
            </div>
            <!-- Bottom nav links -->
            <div class="border-t border-gray-100">
                <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    <nav class="flex items-center gap-8 overflow-x-auto scrollbar-none">
                        <a v-for="item in navigation" :key="item.href" :href="item.href" class="whitespace-nowrap border-b-2 border-transparent py-3 text-sm font-medium text-gray-600 transition hover:border-blue-500 hover:text-blue-600">
                            {{ item.label }}
                        </a>

                    </nav>
                </div>
            </div>
        </header>

        <!-- ═══════════ HERO ═══════════ -->
        <section class="relative overflow-hidden bg-gradient-to-br from-blue-950 via-blue-900 to-indigo-900">
            <div class="absolute inset-0 bg-[url('data:image/svg+xml;base64,PHN2ZyB3aWR0aD0iNjAiIGhlaWdodD0iNjAiIHhtbG5zPSJodHRwOi8vd3d3LnczLm9yZy8yMDAwL3N2ZyI+PGRlZnM+PHBhdHRlcm4gaWQ9ImdyaWQiIHdpZHRoPSI2MCIgaGVpZ2h0PSI2MCIgcGF0dGVyblVuaXRzPSJ1c2VyU3BhY2VPblVzZSI+PHBhdGggZD0iTSA2MCAwIEwgMCAwIDAgNjAiIGZpbGw9Im5vbmUiIHN0cm9rZT0icmdiYSgyNTUsMjU1LDI1NSwwLjA1KSIgc3Ryb2tlLXdpZHRoPSIxIi8+PC9wYXR0ZXJuPjwvZGVmcz48cmVjdCB3aWR0aD0iMTAwJSIgaGVpZ2h0PSIxMDAlIiBmaWxsPSJ1cmwoI2dyaWQpIi8+PC9zdmc+')] opacity-40"></div>
            <div class="relative mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8 lg:py-28">
                <div class="grid items-center gap-12 lg:grid-cols-2">
                    <div class="space-y-8">
                        <div class="inline-flex items-center gap-2 rounded-full border border-blue-400/30 bg-blue-500/10 px-4 py-1.5 text-xs font-semibold uppercase tracking-widest text-blue-200 backdrop-blur">
                            <span class="h-1.5 w-1.5 rounded-full bg-blue-400 animate-pulse"></span>
                            Trusted by 320+ Travel Agencies
                        </div>
                        <h1 class="text-4xl font-extrabold leading-[1.1] tracking-tight text-white sm:text-5xl lg:text-6xl">
                            Discover Your Next <span class="bg-gradient-to-r from-blue-300 to-cyan-300 bg-clip-text text-transparent">Dream Journey</span>
                        </h1>
                        <p class="max-w-lg text-lg leading-relaxed text-blue-100/80">
                            Browse curated travel packages from Malaysia's top agencies. Umrah, holiday tours, and custom trips — all in one place.
                        </p>
                        <div class="flex flex-wrap gap-3">
                            <a href="#packages" class="inline-flex items-center gap-2 rounded-full bg-white px-7 py-3.5 text-sm font-bold text-blue-900 shadow-lg shadow-blue-950/30 transition hover:bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.564 2 12.163 2 7c0-.538.035-1.069.104-1.589a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.75z" clip-rule="evenodd" /></svg>
                                Browse Packages
                            </a>
                            <a href="#about" class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-7 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                                Learn More
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                            </a>
                        </div>
                    </div>
                    <div class="hidden lg:block">
                        <div class="relative">
                            <div class="absolute -inset-4 rounded-3xl bg-gradient-to-br from-blue-500/20 to-cyan-500/20 blur-2xl"></div>
                            <img src="https://images.unsplash.com/photo-1488646953014-85cb44e25828?w=700&q=80" alt="Travel" class="relative rounded-3xl object-cover shadow-2xl ring-1 ring-white/10" style="aspect-ratio: 4/3;" />
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ USP FLOATING BAR ═══════════ -->
        <section class="relative z-10 -mt-8 mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-2 gap-3 rounded-2xl bg-white p-4 shadow-xl shadow-gray-900/5 ring-1 ring-gray-100 sm:p-6 lg:grid-cols-4 lg:gap-6">
                <div v-for="usp in usps" :key="usp.title" class="flex items-start gap-3 p-2">
                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-blue-50">
                        <svg v-if="usp.icon === 'shield'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M9.661 2.237a.531.531 0 01.678 0 11.947 11.947 0 007.078 2.749.5.5 0 01.479.425c.069.52.104 1.05.104 1.59 0 5.162-3.26 9.563-7.834 11.256a.48.48 0 01-.332 0C5.26 16.564 2 12.163 2 7c0-.538.035-1.069.104-1.589a.5.5 0 01.48-.425 11.947 11.947 0 007.077-2.75z" clip-rule="evenodd" /></svg>
                        <svg v-else-if="usp.icon === 'price'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 003 5.5v2.879a2.5 2.5 0 00.732 1.767l6.5 6.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-6.5-6.5A2.5 2.5 0 008.38 3H5.5zM6 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                        <svg v-else-if="usp.icon === 'support'" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-8-5a.75.75 0 01.75.75v4.5a.75.75 0 01-1.5 0v-4.5A.75.75 0 0110 5zm0 10a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path d="M15.98 1.804a1 1 0 00-1.96 0l-.24 1.192a1 1 0 01-.784.785l-1.192.238a1 1 0 000 1.962l1.192.238a1 1 0 01.785.785l.238 1.192a1 1 0 001.962 0l.238-1.192a1 1 0 01.785-.785l1.192-.238a1 1 0 000-1.962l-1.192-.238a1 1 0 01-.785-.785l-.238-1.192zM6.949 5.684a1 1 0 00-1.898 0l-.683 2.051a1 1 0 01-.633.633l-2.051.683a1 1 0 000 1.898l2.051.684a1 1 0 01.633.632l.683 2.051a1 1 0 001.898 0l.683-2.051a1 1 0 01.633-.633l2.051-.683a1 1 0 000-1.898l-2.051-.683a1 1 0 01-.633-.633L6.95 5.684z" /></svg>
                    </div>
                    <div class="min-w-0">
                        <p class="text-sm font-semibold text-gray-900">{{ usp.title }}</p>
                        <p class="mt-0.5 text-xs text-gray-500 leading-relaxed">{{ usp.text }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ BENTO: DESTINASI POPULAR ═══════════ -->
        <section id="destinations" class="mx-auto max-w-7xl px-4 pt-20 pb-10 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Explore</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">Popular Destinations</h2>
                </div>
                <a href="#packages" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 transition">
                    See All
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3 lg:grid-rows-2 lg:h-[480px]">
                <!-- Featured card (left, spans 2 rows + 1 col) -->
                <div class="group relative col-span-1 overflow-hidden rounded-2xl bg-gray-900 sm:col-span-2 sm:row-span-2 lg:col-span-1" style="min-height: 240px;">
                    <img :src="destinations[0].image" :alt="destinations[0].name" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950/80 via-gray-950/20 to-transparent"></div>
                    <div class="relative flex h-full flex-col justify-end p-6 lg:p-8">
                        <span class="mb-2 w-fit rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ destinations[0].tag }}</span>
                        <h3 class="text-2xl font-bold text-white lg:text-3xl">{{ destinations[0].name }}</h3>
                    </div>
                </div>
                <!-- 4 equal cards (right, 2 cols × 2 rows) -->
                <div v-for="dest in destinations.slice(1, 5)" :key="dest.name" class="group relative min-h-[140px] overflow-hidden rounded-2xl bg-gray-900">
                    <img :src="dest.image" :alt="dest.name" class="absolute inset-0 h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-gradient-to-t from-gray-950/70 to-transparent"></div>
                    <div class="relative flex h-full flex-col justify-end p-4">
                        <span class="mb-1 w-fit rounded-full bg-white/20 px-2.5 py-0.5 text-[10px] font-semibold text-white backdrop-blur">{{ dest.tag }}</span>
                        <h3 class="text-sm font-bold text-white">{{ dest.name }}</h3>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ PACKAGE CARDS ═══════════ -->
        <section id="packages" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-6">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Curated</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">Travel Packages</h2>
                </div>
                <a href="#" class="hidden sm:inline-flex items-center gap-1 text-sm font-semibold text-blue-600 hover:text-blue-700 transition">
                    See All
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                </a>
            </div>

            <!-- Filter tabs -->
            <div class="mb-8 flex gap-2 overflow-x-auto scrollbar-none pb-1">
                <button
                    class="whitespace-nowrap rounded-full px-5 py-2 text-sm font-semibold transition"
                    :class="activeCategory === 'all' ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    @click="activeCategory = 'all'"
                >
                    All Packages
                </button>
                <button
                    v-for="cat in categories"
                    :key="cat"
                    class="whitespace-nowrap rounded-full px-5 py-2 text-sm font-semibold transition"
                    :class="activeCategory === cat ? 'bg-blue-600 text-white shadow-sm' : 'bg-gray-100 text-gray-600 hover:bg-gray-200'"
                    @click="activeCategory = cat"
                >
                    {{ cat }}
                </button>
            </div>

            <!-- Cards grid -->
            <div v-if="filteredPackages.length" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <a
                    v-for="pkg in filteredPackages"
                    :key="pkg.id"
                    :href="'/packages/' + pkg.id"
                    class="group overflow-hidden rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 transition hover:shadow-lg hover:-translate-y-1"
                >
                    <div class="relative aspect-[4/3] overflow-hidden bg-gray-100">
                        <img
                            v-if="pkg.cover_image_url"
                            :src="pkg.cover_image_url"
                            :alt="pkg.name"
                            class="h-full w-full object-cover transition duration-500 group-hover:scale-105"
                        />
                        <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-50 to-indigo-100">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="h-12 w-12 text-blue-200"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                        </div>
                        <!-- Category badge -->
                        <span class="absolute left-3 top-3 rounded-full bg-blue-600/90 px-3 py-1 text-[11px] font-bold text-white backdrop-blur">{{ pkg.category }}</span>
                        <!-- Sold out overlay -->
                        <div v-if="pkg.is_sold_out" class="absolute inset-0 flex items-center justify-center bg-gray-900/60">
                            <span class="rounded-full bg-red-600 px-4 py-1.5 text-xs font-bold text-white">Sold Out</span>
                        </div>
                    </div>
                    <div class="p-4">
                        <h3 class="text-sm font-bold text-gray-900 line-clamp-2 leading-snug">{{ pkg.name }}</h3>
                        <p v-if="pkg.destination" class="mt-1 text-xs text-gray-500">{{ pkg.destination }}</p>
                        <div class="mt-3 flex items-center gap-2">
                            <span v-if="pkg.duration" class="inline-flex items-center gap-1 text-xs text-gray-500">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path fill-rule="evenodd" d="M4 1.75a.75.75 0 01.75.75V3h6.5V2.5a.75.75 0 011.5 0V3h.25A2.75 2.75 0 0115.75 5.75v7.5A2.75 2.75 0 0113 16H3A2.75 2.75 0 01.25 13.25v-7.5A2.75 2.75 0 013 3h.25V2.5A.75.75 0 014 1.75z" clip-rule="evenodd" /></svg>
                                {{ pkg.duration }}D{{ pkg.duration - 1 }}N
                            </span>
                            <span v-if="pkg.available_seats > 0 && pkg.available_seats <= 10" class="text-[10px] font-semibold text-amber-600">{{ pkg.available_seats }} seats left</span>
                        </div>
                        <div class="mt-3 flex items-end justify-between border-t border-gray-50 pt-3">
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">From</p>
                                <p class="text-lg font-extrabold text-blue-600">{{ formatPrice(pkg.price) }}</p>
                            </div>
                            <div v-if="pkg.tenant_name" class="flex items-center gap-1.5">
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-gray-100 text-[9px] font-bold text-gray-500">{{ pkg.tenant_name.charAt(0) }}</div>
                                <span class="text-[10px] text-gray-400 max-w-[80px] truncate">{{ pkg.tenant_name }}</span>
                            </div>
                        </div>
                    </div>
                </a>
            </div>

            <!-- Empty state -->
            <div v-else class="flex flex-col items-center justify-center rounded-2xl border-2 border-dashed border-gray-200 py-20">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="h-12 w-12 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="M20.25 14.15v4.25c0 1.094-.787 2.036-1.872 2.18-2.087.277-4.216.42-6.378.42s-4.291-.143-6.378-.42c-1.085-.144-1.872-1.086-1.872-2.18v-4.25m16.5 0a2.18 2.18 0 00.75-1.661V8.706c0-1.081-.768-2.015-1.837-2.175a48.114 48.114 0 00-3.413-.387m4.5 8.006c-.194.165-.42.295-.673.38A23.978 23.978 0 0112 15.75c-2.648 0-5.195-.429-7.577-1.22a2.016 2.016 0 01-.673-.38m0 0A2.18 2.18 0 013 12.489V8.706c0-1.081.768-2.015 1.837-2.175a48.111 48.111 0 013.413-.387m7.5 0V5.25A2.25 2.25 0 0013.5 3h-3a2.25 2.25 0 00-2.25 2.25v.894m7.5 0a48.667 48.667 0 00-7.5 0" /></svg>
                <p class="mt-3 text-sm font-medium text-gray-500">No packages available in this category yet.</p>
                <p class="mt-1 text-xs text-gray-400">Check back soon for exciting new travel deals!</p>
            </div>
        </section>

        <!-- ═══════════ IMAGE GRID (GALLERY) ═══════════ -->
        <section id="gallery" class="mx-auto max-w-7xl px-4 py-16 sm:px-6 lg:px-8">
            <div class="flex items-end justify-between mb-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">Gallery</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-gray-900">Travel Experiences</h2>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-3 sm:grid-cols-4">
                <div v-for="item in galleryItems" :key="item.title" class="group relative aspect-square overflow-hidden rounded-2xl bg-gray-100">
                    <img :src="item.image" :alt="item.title" class="h-full w-full object-cover transition duration-500 group-hover:scale-105" />
                    <div class="absolute inset-x-0 bottom-0 bg-gradient-to-t from-gray-950/70 to-transparent p-4 pt-12">
                        <p class="text-sm font-semibold text-white">{{ item.title }}</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ SPLIT LAYOUT (ABOUT) ═══════════ -->
        <section id="about" class="mx-auto max-w-7xl px-4 py-20 sm:px-6 lg:px-8">
            <div class="grid items-center gap-12 lg:grid-cols-2">
                <div class="space-y-6">
                    <p class="text-xs font-bold uppercase tracking-widest text-blue-600">About Us</p>
                    <h2 class="text-3xl font-extrabold tracking-tight text-gray-900 sm:text-4xl">
                        Your Trusted Travel <br/>Partner Since Day One
                    </h2>
                    <p class="text-base leading-relaxed text-gray-600">
                        TravelRocket connects you with Malaysia's most trusted travel agencies offering curated Umrah, holiday, and adventure packages. Every operator on our platform is MATTA and MOTAC certified, ensuring your journey is in safe hands.
                    </p>
                    <p class="text-base leading-relaxed text-gray-600">
                        From spiritual pilgrimages to family getaways, we bring you the widest selection of travel experiences with transparent pricing, verified operators, and dedicated customer support throughout your trip.
                    </p>
                    <div class="flex flex-wrap gap-6 pt-2">
                        <div>
                            <p class="text-3xl font-extrabold text-gray-900">320+</p>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Agencies</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-gray-900">18k+</p>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Bookings</p>
                        </div>
                        <div>
                            <p class="text-3xl font-extrabold text-gray-900">50+</p>
                            <p class="text-xs font-medium text-gray-500 uppercase tracking-wider">Destinations</p>
                        </div>
                    </div>
                    <a href="/login" class="inline-flex items-center gap-2 rounded-full bg-blue-600 px-7 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700">
                        Sign In
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M3 10a.75.75 0 01.75-.75h10.638L10.23 5.29a.75.75 0 111.04-1.08l5.5 5.25a.75.75 0 010 1.08l-5.5 5.25a.75.75 0 11-1.04-1.08l4.158-3.96H3.75A.75.75 0 013 10z" clip-rule="evenodd" /></svg>
                    </a>
                </div>
                <!-- Masonry collage -->
                <div class="hidden lg:grid grid-cols-3 gap-3">
                    <div class="space-y-3 pt-8">
                        <img src="https://images.unsplash.com/photo-1578662996442-48f60103fc96?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 3/4;" alt="" />
                        <img src="https://images.unsplash.com/photo-1504598318550-17eba1008a68?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 1/1;" alt="" />
                    </div>
                    <div class="space-y-3">
                        <img src="https://images.unsplash.com/photo-1526772662000-3f88f10405ff?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 1/1;" alt="" />
                        <img src="https://images.unsplash.com/photo-1533105079780-92b9be482077?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 3/4;" alt="" />
                    </div>
                    <div class="space-y-3 pt-12">
                        <img src="https://images.unsplash.com/photo-1469854523086-cc02fe5d8800?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 4/5;" alt="" />
                        <img src="https://images.unsplash.com/photo-1501785888041-af3ef285b470?w=400&q=80" class="rounded-2xl object-cover w-full" style="aspect-ratio: 3/4;" alt="" />
                    </div>
                </div>
            </div>
        </section>

        <!-- ═══════════ FOOTER ═══════════ -->
        <footer class="border-t border-gray-100 bg-gray-50">
            <div class="mx-auto max-w-7xl px-4 py-12 sm:px-6 lg:px-8">
                <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-4">
                    <div>
                        <div class="flex items-center gap-2.5 mb-4">
                            <img v-if="branding.logo_url" :src="branding.logo_url" alt="Logo" class="h-8 w-8 rounded-lg object-contain" />
                            <span v-else class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white">TR</span>
                            <span class="text-sm font-bold text-gray-900">{{ branding.name || 'TravelRocket' }}</span>
                        </div>
                        <p class="text-sm text-gray-500 leading-relaxed">Malaysia's trusted travel marketplace connecting you with certified agencies for Umrah, tours, and custom trips.</p>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-900 mb-3">Explore</p>
                        <ul class="space-y-2">
                            <li><a href="#destinations" class="text-sm text-gray-500 hover:text-gray-900 transition">Destinations</a></li>
                            <li><a href="#packages" class="text-sm text-gray-500 hover:text-gray-900 transition">Packages</a></li>
                            <li><a href="#gallery" class="text-sm text-gray-500 hover:text-gray-900 transition">Gallery</a></li>
                            <li><a href="#about" class="text-sm text-gray-500 hover:text-gray-900 transition">About Us</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-900 mb-3">Quick Links</p>
                        <ul class="space-y-2">
                            <li><a href="#destinations" class="text-sm text-gray-500 hover:text-gray-900 transition">Destinations</a></li>
                            <li><a href="#packages" class="text-sm text-gray-500 hover:text-gray-900 transition">Packages</a></li>
                            <li><a href="/login" class="text-sm text-gray-500 hover:text-gray-900 transition">Sign In</a></li>
                        </ul>
                    </div>
                    <div>
                        <p class="text-xs font-bold uppercase tracking-wider text-gray-900 mb-3">Support</p>
                        <ul class="space-y-2">
                            <li><span class="text-sm text-gray-500">hello@travelrocket.my</span></li>
                            <li><span class="text-sm text-gray-500">+60 12-345 6789</span></li>
                        </ul>
                    </div>
                </div>
                <div class="mt-10 flex flex-col items-center justify-between gap-4 border-t border-gray-200 pt-8 sm:flex-row">
                    <p class="text-xs text-gray-400">&copy; {{ new Date().getFullYear() }} TravelRocket. All rights reserved.</p>
                    <div class="flex gap-4">
                        <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition">Privacy</a>
                        <a href="#" class="text-xs text-gray-400 hover:text-gray-600 transition">Terms</a>
                    </div>
                </div>
            </div>
        </footer>
    </div>
</template>

