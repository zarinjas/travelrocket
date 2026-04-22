<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref, computed } from 'vue';

const props = defineProps({
    package: { type: Object, required: true },
    depositPercentage: { type: Number, default: 30 },
});

const pkg = props.package;

const activeTab = ref('itinerary');

// Gallery lightbox
const lightboxOpen = ref(false);
const lightboxIndex = ref(0);
const galleryImages = computed(() => pkg.gallery_images ?? []);
const openLightbox = (index) => { lightboxIndex.value = index; lightboxOpen.value = true; };
const closeLightbox = () => { lightboxOpen.value = false; };
const prevImage = () => { lightboxIndex.value = (lightboxIndex.value - 1 + galleryImages.value.length) % galleryImages.value.length; };
const nextImage = () => { lightboxIndex.value = (lightboxIndex.value + 1) % galleryImages.value.length; };

const minPax = pkg.min_pax ?? 1;
const maxPax = pkg.max_pax ?? pkg.available_seats;

const form = useForm({
    name: '',
    email: '',
    phone: '',
    pax: minPax,
    payment_type: 'full',
    card_number: '4242 4242 4242 4242',
    card_expiry: '12/28',
    card_cvc: '123',
});

const totalPrice = computed(() => pkg.price * form.pax);
const depositAmount = computed(() => Math.round(totalPrice.value * props.depositPercentage / 100));
const payableAmount = computed(() => form.payment_type === 'deposit' ? depositAmount.value : totalPrice.value);

const formatPrice = (price) => 'RM ' + Number(price).toLocaleString('en-MY', { minimumFractionDigits: 0 });

const mealPlanLabel = {
    none: null,
    breakfast: 'Breakfast Only',
    half_board: 'Half Board',
    full_board: 'Full Board',
    all_inclusive: 'All Inclusive',
};

const visaIncludedLabel = {
    yes: 'Included',
    no: 'Not Included',
    own: 'Handle on own',
};

const starCount = (n) => '★'.repeat(n) + '☆'.repeat(5 - n);

const submit = () => {
    form.post(`/packages/${pkg.id}/book`);
};
</script>

<template>
    <Head :title="pkg.name + ' — TravelRocket'" />

    <div class="min-h-screen bg-gray-50">

        <!-- ═══════════ NAVBAR ═══════════ -->
        <header class="sticky top-0 z-50 bg-white/95 backdrop-blur-lg border-b border-gray-100">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div class="flex h-14 items-center justify-between">
                    <a href="/" class="flex items-center gap-2.5">
                        <span class="flex h-8 w-8 items-center justify-center rounded-lg bg-blue-600 text-xs font-bold text-white shadow-sm">TR</span>
                        <span class="text-base font-bold tracking-tight text-gray-900">TravelRocket</span>
                    </a>
                    <div class="flex items-center gap-3">
                        <a href="/" class="text-sm font-medium text-gray-600 hover:text-gray-900 transition">← Back to Packages</a>
                    </div>
                </div>
            </div>
        </header>

        <!-- ═══════════ HERO IMAGE ═══════════ -->
        <div class="relative h-64 sm:h-80 lg:h-96 overflow-hidden bg-gray-900">
            <img
                v-if="pkg.cover_image_url"
                :src="pkg.cover_image_url"
                :alt="pkg.name"
                class="h-full w-full object-cover opacity-80"
            />
            <div v-else class="flex h-full w-full items-center justify-center bg-gradient-to-br from-blue-900 to-indigo-900">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="h-24 w-24 text-blue-300/40"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
            </div>
            <div class="absolute inset-0 bg-gradient-to-t from-gray-900/70 via-transparent to-transparent"></div>
            <div class="absolute bottom-0 left-0 right-0 p-6 lg:p-8">
                <div class="mx-auto max-w-7xl">
                    <div class="flex flex-wrap items-center gap-2 mb-3">
                        <span class="rounded-full bg-blue-600/90 px-3 py-1 text-xs font-bold text-white backdrop-blur">{{ pkg.category }}</span>
                        <span v-if="pkg.destination" class="rounded-full bg-white/20 px-3 py-1 text-xs font-semibold text-white backdrop-blur">{{ pkg.destination }}</span>
                    </div>
                    <h1 class="text-2xl font-extrabold text-white sm:text-3xl lg:text-4xl">{{ pkg.name }}</h1>
                </div>
            </div>
        </div>

        <!-- ═══════════ GALLERY ═══════════ -->
        <div v-if="galleryImages.length" class="mx-auto max-w-7xl px-4 pt-6 sm:px-6 lg:px-8">
            <div class="grid gap-2" :class="{
                'grid-cols-1': galleryImages.length === 1,
                'grid-cols-2': galleryImages.length === 2,
                'grid-cols-3': galleryImages.length >= 3,
            }">
                <div
                    v-for="(url, idx) in galleryImages.slice(0, 5)"
                    :key="idx"
                    class="group relative cursor-pointer overflow-hidden rounded-xl bg-gray-200"
                    :class="[
                        galleryImages.length >= 3 && idx === 0 ? 'col-span-2 row-span-2' : '',
                        galleryImages.length === 1 ? 'aspect-video' : 'aspect-square',
                    ]"
                    @click="openLightbox(idx)"
                >
                    <img :src="url" class="h-full w-full object-cover transition duration-300 group-hover:scale-105" />
                    <div class="absolute inset-0 bg-black/0 transition group-hover:bg-black/20"></div>
                    <!-- "View all" overlay on the last visible tile when there are more -->
                    <div v-if="idx === 4 && galleryImages.length > 5" class="absolute inset-0 flex items-center justify-center bg-black/50">
                        <p class="text-xl font-bold text-white">+{{ galleryImages.length - 5 }} more</p>
                    </div>
                </div>
            </div>
            <button
                type="button"
                class="mt-2 text-xs font-semibold text-blue-600 hover:text-blue-700"
                @click="openLightbox(0)"
            >
                View all {{ galleryImages.length }} photos
            </button>
        </div>

        <!-- Lightbox -->
        <Teleport to="body">
            <div
                v-if="lightboxOpen"
                class="fixed inset-0 z-50 flex items-center justify-center bg-black/90 p-4"
                @click.self="closeLightbox"
            >
                <button class="absolute left-4 top-4 flex h-10 w-10 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" @click="closeLightbox">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5"><path d="M6.28 5.22a.75.75 0 00-1.06 1.06L8.94 10l-3.72 3.72a.75.75 0 101.06 1.06L10 11.06l3.72 3.72a.75.75 0 101.06-1.06L11.06 10l3.72-3.72a.75.75 0 00-1.06-1.06L10 8.94 6.28 5.22z" /></svg>
                </button>
                <button v-if="galleryImages.length > 1" class="absolute left-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" @click="prevImage">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M11.78 5.22a.75.75 0 010 1.06L8.06 10l3.72 3.72a.75.75 0 11-1.06 1.06l-4.25-4.25a.75.75 0 010-1.06l4.25-4.25a.75.75 0 011.06 0z" clip-rule="evenodd" /></svg>
                </button>
                <img :src="galleryImages[lightboxIndex]" class="max-h-[85vh] max-w-full rounded-xl object-contain shadow-2xl" />
                <button v-if="galleryImages.length > 1" class="absolute right-4 top-1/2 -translate-y-1/2 flex h-12 w-12 items-center justify-center rounded-full bg-white/10 text-white transition hover:bg-white/20" @click="nextImage">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-6 w-6"><path fill-rule="evenodd" d="M8.22 5.22a.75.75 0 011.06 0l4.25 4.25a.75.75 0 010 1.06l-4.25 4.25a.75.75 0 01-1.06-1.06L11.94 10 8.22 6.28a.75.75 0 010-1.06z" clip-rule="evenodd" /></svg>
                </button>
                <div class="absolute bottom-4 left-1/2 -translate-x-1/2 rounded-full bg-black/50 px-3 py-1 text-xs font-semibold text-white">
                    {{ lightboxIndex + 1 }} / {{ galleryImages.length }}
                </div>
            </div>
        </Teleport>

        <!-- ═══════════ MAIN CONTENT ═══════════ -->
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="grid gap-8 lg:grid-cols-[1fr_380px]">

                <!-- LEFT: Package Details -->
                <div class="space-y-8">

                    <!-- Quick Info Bar -->
                    <div class="flex flex-wrap gap-4 rounded-2xl bg-white p-5 shadow-sm ring-1 ring-gray-100">
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M5.75 2a.75.75 0 01.75.75V4h7V2.75a.75.75 0 011.5 0V4h.25A2.75 2.75 0 0118 6.75v8.5A2.75 2.75 0 0115.25 18H4.75A2.75 2.75 0 012 15.25v-8.5A2.75 2.75 0 014.75 4H5V2.75A.75.75 0 015.75 2z" clip-rule="evenodd" /></svg>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Dates</p>
                                <p class="text-sm font-semibold text-gray-900">{{ pkg.start_date }} — {{ pkg.end_date }}</p>
                            </div>
                        </div>
                        <div v-if="pkg.duration" class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm.75-13a.75.75 0 00-1.5 0v5c0 .414.336.75.75.75h4a.75.75 0 000-1.5h-3.25V5z" clip-rule="evenodd" /></svg>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Duration</p>
                                <p class="text-sm font-semibold text-gray-900">{{ pkg.duration }} Days {{ pkg.duration - 1 }} Nights</p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path d="M7 8a3 3 0 100-6 3 3 0 000 6zM14.5 9a2.5 2.5 0 100-5 2.5 2.5 0 000 5zM1.615 16.428a1.224 1.224 0 01-.569-1.175 6.002 6.002 0 0111.908 0c.058.467-.172.92-.57 1.174A9.953 9.953 0 017 18a9.953 9.953 0 01-5.385-1.572zM14.5 16h-.106c.07-.297.088-.611.048-.933a7.47 7.47 0 00-1.588-3.755 4.502 4.502 0 015.874 2.636.818.818 0 01-.36.98A7.465 7.465 0 0114.5 16z" /></svg>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Availability</p>
                                <p class="text-sm font-semibold" :class="pkg.available_seats <= 5 ? 'text-amber-600' : 'text-gray-900'">
                                    {{ pkg.is_sold_out ? 'Sold Out' : pkg.available_seats + ' seats left' }}
                                </p>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-blue-50">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-blue-600"><path fill-rule="evenodd" d="M5.5 3A2.5 2.5 0 003 5.5v2.879a2.5 2.5 0 00.732 1.767l6.5 6.5a2.5 2.5 0 003.536 0l2.878-2.878a2.5 2.5 0 000-3.536l-6.5-6.5A2.5 2.5 0 008.38 3H5.5zM6 7a1 1 0 100-2 1 1 0 000 2z" clip-rule="evenodd" /></svg>
                            </div>
                            <div>
                                <p class="text-[10px] uppercase tracking-wider text-gray-400">Price / pax</p>
                                <p class="text-sm font-extrabold text-blue-600">{{ formatPrice(pkg.price) }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Highlights -->
                    <div v-if="pkg.highlights?.length" class="rounded-2xl bg-gradient-to-br from-blue-50 to-indigo-50 p-6 ring-1 ring-blue-100">
                        <h2 class="mb-4 flex items-center gap-2 text-sm font-bold uppercase tracking-wider text-blue-700">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M10.868 2.884c-.321-.772-1.415-.772-1.736 0l-1.83 4.401-4.753.381c-.833.067-1.171 1.107-.536 1.651l3.62 3.102-1.106 4.637c-.194.813.691 1.456 1.405 1.02L10 15.591l4.069 2.485c.713.436 1.598-.207 1.404-1.02l-1.106-4.637 3.62-3.102c.635-.544.297-1.584-.536-1.65l-4.752-.382-1.831-4.401z" clip-rule="evenodd" /></svg>
                            Why Choose This Package
                        </h2>
                        <ul class="grid gap-2 sm:grid-cols-2">
                            <li v-for="(h, i) in pkg.highlights" :key="i" class="flex items-start gap-2.5">
                                <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-blue-600">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 text-white"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                                </div>
                                <span class="text-sm font-medium text-gray-800">{{ h }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Description -->
                    <div v-if="pkg.description" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h2 class="text-lg font-bold text-gray-900 mb-3">About This Package</h2>
                        <p class="text-sm leading-relaxed text-gray-600 whitespace-pre-line">{{ pkg.description }}</p>
                    </div>

                    <!-- Tabs: Itinerary / Inclusions / T&C -->
                    <div class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                        <div class="flex border-b border-gray-100">
                            <button
                                v-for="tab in [
                                    { key: 'itinerary', label: 'Itinerary' },
                                    { key: 'inclusions', label: 'Inclusions' },
                                    { key: 'pricing', label: 'Pricing Tiers' },
                                    { key: 'terms', label: 'Terms' },
                                ]"
                                :key="tab.key"
                                class="flex-1 py-3.5 text-center text-sm font-semibold transition"
                                :class="activeTab === tab.key ? 'text-blue-600 border-b-2 border-blue-600 bg-blue-50/50' : 'text-gray-500 hover:text-gray-700'"
                                @click="activeTab = tab.key"
                            >
                                {{ tab.label }}
                            </button>
                        </div>

                        <div class="p-6">
                            <!-- Itinerary Tab -->
                            <div v-if="activeTab === 'itinerary'">
                                <div v-if="pkg.itinerary_days.length" class="space-y-4">
                                    <div v-for="(day, idx) in pkg.itinerary_days" :key="idx" class="flex gap-4">
                                        <div class="flex flex-col items-center">
                                            <div class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-blue-600 text-xs font-bold text-white">{{ idx + 1 }}</div>
                                            <div v-if="idx < pkg.itinerary_days.length - 1" class="mt-1 w-px flex-1 bg-blue-100"></div>
                                        </div>
                                        <div class="pb-4">
                                            <p class="text-sm font-bold text-gray-900">{{ day.title || 'Day ' + (idx + 1) }}</p>
                                            <p v-if="day.description" class="mt-1 text-sm text-gray-600 leading-relaxed">{{ day.description }}</p>
                                        </div>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-400 text-center py-8">Itinerary details coming soon.</p>
                            </div>

                            <!-- Inclusions Tab -->
                            <div v-if="activeTab === 'inclusions'" class="grid gap-6 sm:grid-cols-2">
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-emerald-500"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                                        What's Included
                                    </h3>
                                    <ul v-if="pkg.inclusions.length" class="space-y-2">
                                        <li v-for="(item, idx) in pkg.inclusions" :key="idx" class="flex items-start gap-2 text-sm text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-emerald-500"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                                            {{ typeof item === 'string' ? item : item.item || item.name }}
                                        </li>
                                    </ul>
                                    <p v-else class="text-sm text-gray-400">No inclusions specified.</p>
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-gray-900 mb-3 flex items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-red-400"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.28 7.22a.75.75 0 00-1.06 1.06L8.94 10l-1.72 1.72a.75.75 0 101.06 1.06L10 11.06l1.72 1.72a.75.75 0 101.06-1.06L11.06 10l1.72-1.72a.75.75 0 00-1.06-1.06L10 8.94 8.28 7.22z" clip-rule="evenodd" /></svg>
                                        Not Included
                                    </h3>
                                    <ul v-if="pkg.exclusions.length" class="space-y-2">
                                        <li v-for="(item, idx) in pkg.exclusions" :key="idx" class="flex items-start gap-2 text-sm text-gray-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="mt-0.5 h-3.5 w-3.5 shrink-0 text-red-400"><path d="M5.28 4.22a.75.75 0 00-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 101.06 1.06L8 9.06l2.72 2.72a.75.75 0 101.06-1.06L9.06 8l2.72-2.72a.75.75 0 00-1.06-1.06L8 6.94 5.28 4.22z" /></svg>
                                            {{ typeof item === 'string' ? item : item.item || item.name }}
                                        </li>
                                    </ul>
                                    <p v-else class="text-sm text-gray-400">No exclusions specified.</p>
                                </div>
                            </div>

                            <!-- Pricing Tiers Tab -->
                            <div v-if="activeTab === 'pricing'">
                                <div v-if="pkg.pricing_tiers.length" class="space-y-3">
                                    <div v-for="(tier, idx) in pkg.pricing_tiers" :key="idx" class="flex items-center justify-between rounded-xl bg-gray-50 px-4 py-3">
                                        <div>
                                            <p class="text-sm font-semibold text-gray-900">{{ tier.label || tier.name || 'Tier ' + (idx + 1) }}</p>
                                            <p v-if="tier.description" class="text-xs text-gray-500">{{ tier.description }}</p>
                                        </div>
                                        <p class="text-sm font-bold text-blue-600">{{ formatPrice(tier.price || tier.amount || 0) }}</p>
                                    </div>
                                </div>
                                <p v-else class="text-sm text-gray-400 text-center py-8">Standard pricing applies.</p>
                            </div>

                            <!-- Terms Tab -->
                            <div v-if="activeTab === 'terms'">
                                <div v-if="pkg.terms_conditions" class="prose prose-sm max-w-none text-gray-600">
                                    <p class="whitespace-pre-line">{{ pkg.terms_conditions }}</p>
                                </div>
                                <p v-else class="text-sm text-gray-400 text-center py-8">No terms and conditions specified.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Hotel / Flight / Meal / Visa Info -->
                    <div v-if="pkg.hotel_details?.length || pkg.flight_info?.airline || pkg.meal_plan || pkg.visa_info?.included" class="rounded-2xl bg-white shadow-sm ring-1 ring-gray-100 overflow-hidden">
                        <div class="border-b border-gray-100 px-6 py-4">
                            <h2 class="text-lg font-bold text-gray-900">Package Details</h2>
                        </div>

                        <!-- Hotels -->
                        <div v-if="pkg.hotel_details?.length" class="border-b border-gray-100 px-6 py-5">
                            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path fill-rule="evenodd" d="M1 2.75A.75.75 0 011.75 2h10.5a.75.75 0 010 1.5H12v13.75a.75.75 0 01-.75.75h-1.5a.75.75 0 01-.75-.75v-2.5a.75.75 0 00-.75-.75h-2.5a.75.75 0 00-.75.75v2.5a.75.75 0 01-.75.75H3a.75.75 0 010-1.5h.25V3.5H1.75A.75.75 0 011 2.75zM4 3.5v13H6v-2.25c0-.69.56-1.25 1.25-1.25h2.5c.69 0 1.25.56 1.25 1.25V16.5h2V3.5H4z" clip-rule="evenodd" /><path d="M17 7.25a.75.75 0 00-1.5 0V19h-1.5V7.25a.75.75 0 00-1.5 0v12.5a.75.75 0 00.75.75h4.5a.75.75 0 00.75-.75V7.25z" /></svg>
                                Accommodation
                            </h3>
                            <div class="space-y-3">
                                <div v-for="(hotel, i) in pkg.hotel_details" :key="i" class="flex items-start justify-between gap-4 rounded-xl bg-gray-50 px-4 py-3">
                                    <div>
                                        <p class="text-sm font-semibold text-gray-900">{{ hotel.hotel_name }}</p>
                                        <p class="text-xs text-gray-500">{{ hotel.city }}</p>
                                    </div>
                                    <div class="text-right shrink-0">
                                        <p v-if="hotel.stars" class="text-xs font-medium text-amber-500">{{ starCount(hotel.stars) }}</p>
                                        <p v-if="hotel.nights" class="text-xs text-gray-500">{{ hotel.nights }} nights</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Flight -->
                        <div v-if="pkg.flight_info?.airline || pkg.flight_info?.departure_city" class="border-b border-gray-100 px-6 py-5">
                            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path d="M3.105 2.289a.75.75 0 00-.826.95l1.414 4.925A1.5 1.5 0 005.135 9.25h6.115a.25.25 0 01.245.296l-.972 5.393A.75.75 0 0011.5 15.5h.003a.75.75 0 00.744-.657l.97-8.03A.75.75 0 0114 6h2.5a.75.75 0 000-1.5H14a2.25 2.25 0 00-2.228 1.92l-.116.966A1.5 1.5 0 0110.183 6H5.135a.25.25 0 01-.241-.18L3.48 1.99a.75.75 0 00-.375-.701z" /></svg>
                                Flight
                            </h3>
                            <div class="flex flex-wrap gap-3">
                                <div v-if="pkg.flight_info.departure_city" class="rounded-lg bg-gray-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">From</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ pkg.flight_info.departure_city }}</p>
                                </div>
                                <div v-if="pkg.flight_info.airline" class="rounded-lg bg-gray-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Airline</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ pkg.flight_info.airline }}</p>
                                </div>
                                <div v-if="pkg.flight_info.flight_class" class="rounded-lg bg-gray-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Class</p>
                                    <p class="text-sm font-semibold text-gray-900 capitalize">{{ pkg.flight_info.flight_class }}</p>
                                </div>
                                <div class="rounded-lg px-3 py-2" :class="pkg.flight_info.is_direct ? 'bg-emerald-50' : 'bg-gray-50'">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Route</p>
                                    <p class="text-sm font-semibold" :class="pkg.flight_info.is_direct ? 'text-emerald-700' : 'text-gray-900'">{{ pkg.flight_info.is_direct ? 'Direct' : 'With Transit' }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Meal Plan -->
                        <div v-if="pkg.meal_plan && pkg.meal_plan !== 'none'" class="border-b border-gray-100 px-6 py-5">
                            <h3 class="mb-2 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path d="M18.375 2.25c-1.035 0-1.875.84-1.875 1.875v15.75c0 1.035.84 1.875 1.875 1.875h.375a.75.75 0 00.75-.75V3a.75.75 0 00-.75-.75h-.375zM9.75 8.875c0-1.036.84-1.875 1.875-1.875h.375a.75.75 0 00.75-.75v-.375a.75.75 0 00-.75-.75H11.25C9.18 5.125 7.5 6.805 7.5 8.875v1.2a4.5 4.5 0 01-1.884 3.668l-.1.067a.75.75 0 000 1.27l.1.066A4.5 4.5 0 017.5 18.875v1.2c0 2.07 1.68 3.75 3.75 3.75h.375a.75.75 0 00.75-.75v-.375a.75.75 0 00-.75-.75H11.25a2.25 2.25 0 01-2.25-2.25v-1.2a6 6 0 00-2.512-4.89l-.1-.067A6 6 0 009 11.075v-1.2z" /></svg>
                                Meals
                            </h3>
                            <span class="inline-flex items-center rounded-full bg-emerald-50 px-3 py-1 text-sm font-medium text-emerald-700 ring-1 ring-emerald-200">
                                {{ mealPlanLabel[pkg.meal_plan] }}
                            </span>
                        </div>

                        <!-- Visa -->
                        <div v-if="pkg.visa_info" class="px-6 py-5">
                            <h3 class="mb-3 flex items-center gap-2 text-sm font-semibold text-gray-700">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-gray-400"><path fill-rule="evenodd" d="M1 4.75C1 3.784 1.784 3 2.75 3h14.5c.966 0 1.75.784 1.75 1.75v10.515a1.75 1.75 0 01-1.75 1.75h-1.5c-.078 0-.155-.005-.23-.015H4.48c-.075.01-.152.015-.23.015h-1.5A1.75 1.75 0 011 15.265V4.75zm16.5 7.385V11.01a.25.25 0 00-.25-.25h-1.5a.25.25 0 00-.25.25v1.125c0 .138.112.25.25.25h1.5a.25.25 0 00.25-.25zm0 2.005a.25.25 0 00-.25-.25h-1.5a.25.25 0 00-.25.25v1.125c0 .414.336.75.75.75H17a.75.75 0 00.5-.193v-.932zm-15 1.682V4.75a.25.25 0 01.25-.25h14.5a.25.25 0 01.25.25v1.5H2.5v-.25zm0 1.75H7v1H2.5v-1zm0 2H7v1H2.5v-1zm0 2H7v1H2.5v-1zm5-5h4.75v1H7.5v-1zm0 2h4.75v1H7.5v-1zm0 2h4.75v1H7.5v-1z" clip-rule="evenodd" /></svg>
                                Visa
                            </h3>
                            <div class="flex flex-wrap gap-3">
                                <div class="rounded-lg px-3 py-2" :class="pkg.visa_info.included === 'yes' ? 'bg-emerald-50' : 'bg-gray-50'">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Status</p>
                                    <p class="text-sm font-semibold" :class="pkg.visa_info.included === 'yes' ? 'text-emerald-700' : 'text-gray-900'">{{ visaIncludedLabel[pkg.visa_info.included] ?? '—' }}</p>
                                </div>
                                <div v-if="pkg.visa_info.type" class="rounded-lg bg-gray-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Type</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ pkg.visa_info.type }}</p>
                                </div>
                                <div v-if="pkg.visa_info.processing_days" class="rounded-lg bg-gray-50 px-3 py-2">
                                    <p class="text-[10px] uppercase tracking-wider text-gray-400">Processing</p>
                                    <p class="text-sm font-semibold text-gray-900">{{ pkg.visa_info.processing_days }} days</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Agency Info -->
                    <div v-if="pkg.tenant" class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <h2 class="text-sm font-bold text-gray-900 mb-4">Operated By</h2>
                        <div class="flex items-center gap-4">
                            <div v-if="pkg.tenant.logo_url" class="h-12 w-12 rounded-xl overflow-hidden bg-gray-100">
                                <img :src="pkg.tenant.logo_url" :alt="pkg.tenant.name" class="h-full w-full object-cover" />
                            </div>
                            <div v-else class="flex h-12 w-12 items-center justify-center rounded-xl bg-blue-50 text-lg font-bold text-blue-600">
                                {{ pkg.tenant.name?.charAt(0) }}
                            </div>
                            <div>
                                <p class="text-sm font-semibold text-gray-900">{{ pkg.tenant.name }}</p>
                                <p v-if="pkg.tenant.phone" class="text-xs text-gray-500">{{ pkg.tenant.phone }}</p>
                                <p v-if="pkg.tenant.email" class="text-xs text-gray-500">{{ pkg.tenant.email }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- RIGHT: Booking Sidebar -->
                <div class="lg:sticky lg:top-20 space-y-6 self-start">  

                    <!-- Price Summary Card -->
                    <div class="rounded-2xl bg-white p-6 shadow-sm ring-1 ring-gray-100">
                        <div class="flex items-end justify-between mb-6">
                            <div>
                                <p class="text-xs uppercase tracking-wider text-gray-400">From</p>
                                <p class="text-3xl font-extrabold text-gray-900">{{ formatPrice(pkg.price) }}</p>
                                <p class="text-xs text-gray-500 mt-0.5">per person</p>
                            </div>
                            <span v-if="pkg.is_sold_out" class="rounded-full bg-red-100 px-3 py-1 text-xs font-bold text-red-600">Sold Out</span>
                        </div>

                        <form v-if="!pkg.is_sold_out" @submit.prevent="submit" class="space-y-4">
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Full Name *</label>
                                <input v-model="form.name" type="text" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="Ahmad bin Ali" />
                                <p v-if="form.errors.name" class="mt-1 text-xs text-red-500">{{ form.errors.name }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Email *</label>
                                <input v-model="form.email" type="email" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="ahmad@email.com" />
                                <p v-if="form.errors.email" class="mt-1 text-xs text-red-500">{{ form.errors.email }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Phone *</label>
                                <input v-model="form.phone" type="tel" required class="w-full rounded-xl border-0 bg-gray-50 px-4 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:bg-white focus:ring-2 focus:ring-blue-500" placeholder="+60 12-345 6789" />
                                <p v-if="form.errors.phone" class="mt-1 text-xs text-red-500">{{ form.errors.phone }}</p>
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-1.5">Number of Pax *</label>
                                <div class="flex items-center gap-3">
                                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition" @click="form.pax = Math.max(minPax, form.pax - 1)">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M4 10a.75.75 0 01.75-.75h10.5a.75.75 0 010 1.5H4.75A.75.75 0 014 10z" clip-rule="evenodd" /></svg>
                                    </button>
                                    <span class="text-lg font-bold text-gray-900 w-8 text-center">{{ form.pax }}</span>
                                    <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-600 hover:bg-gray-200 transition" @click="form.pax = Math.min(maxPax, form.pax + 1)">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M10.75 4.75a.75.75 0 00-1.5 0v4.5h-4.5a.75.75 0 000 1.5h4.5v4.5a.75.75 0 001.5 0v-4.5h4.5a.75.75 0 000-1.5h-4.5v-4.5z" /></svg>
                                    </button>
                                </div>
                                <p v-if="minPax > 1 || pkg.max_pax" class="mt-1 text-xs text-gray-400">
                                    <span v-if="minPax > 1">Min {{ minPax }} pax</span>
                                    <span v-if="minPax > 1 && pkg.max_pax"> · </span>
                                    <span v-if="pkg.max_pax">Max {{ pkg.max_pax }} pax</span>
                                </p>
                                <p v-if="form.errors.pax" class="mt-1 text-xs text-red-500">{{ form.errors.pax }}</p>
                            </div>

                            <!-- Payment Type -->
                            <div>
                                <label class="block text-xs font-semibold text-gray-700 mb-2">Payment Option *</label>
                                <div class="grid grid-cols-2 gap-2">
                                    <label
                                        class="relative flex cursor-pointer flex-col items-center rounded-xl border-2 p-3 transition"
                                        :class="form.payment_type === 'full' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <input v-model="form.payment_type" type="radio" value="full" class="sr-only" />
                                        <p class="text-xs font-bold text-gray-900">Full Payment</p>
                                        <p class="text-lg font-extrabold text-blue-600 mt-1">{{ formatPrice(totalPrice) }}</p>
                                    </label>
                                    <label
                                        class="relative flex cursor-pointer flex-col items-center rounded-xl border-2 p-3 transition"
                                        :class="form.payment_type === 'deposit' ? 'border-blue-600 bg-blue-50' : 'border-gray-200 hover:border-gray-300'"
                                    >
                                        <input v-model="form.payment_type" type="radio" value="deposit" class="sr-only" />
                                        <p class="text-xs font-bold text-gray-900">Deposit {{ depositPercentage }}%</p>
                                        <p class="text-lg font-extrabold text-blue-600 mt-1">{{ formatPrice(depositAmount) }}</p>
                                    </label>
                                </div>
                            </div>

                            <!-- Dummy Card Fields -->
                            <div class="rounded-xl bg-gray-50 p-4 ring-1 ring-gray-200">
                                <p class="text-[10px] font-bold uppercase tracking-widest text-gray-400 mb-3">Payment Details (Demo)</p>
                                <div class="space-y-3">
                                    <div>
                                        <label class="block text-[10px] font-semibold text-gray-500 mb-1">Card Number</label>
                                        <input v-model="form.card_number" type="text" class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500" />
                                    </div>
                                    <div class="grid grid-cols-2 gap-3">
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-500 mb-1">Expiry</label>
                                            <input v-model="form.card_expiry" type="text" class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                        <div>
                                            <label class="block text-[10px] font-semibold text-gray-500 mb-1">CVC</label>
                                            <input v-model="form.card_cvc" type="text" class="w-full rounded-lg border-0 bg-white px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-blue-500" />
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Summary -->
                            <div class="rounded-xl bg-blue-50 p-4 space-y-2">
                                <div class="flex justify-between text-sm">
                                    <span class="text-gray-600">{{ formatPrice(pkg.price) }} × {{ form.pax }} pax</span>
                                    <span class="font-semibold text-gray-900">{{ formatPrice(totalPrice) }}</span>
                                </div>
                                <div v-if="form.payment_type === 'deposit'" class="flex justify-between text-sm">
                                    <span class="text-gray-600">Deposit ({{ depositPercentage }}%)</span>
                                    <span class="font-semibold text-gray-900">{{ formatPrice(depositAmount) }}</span>
                                </div>
                                <div class="border-t border-blue-200 pt-2 flex justify-between">
                                    <span class="text-sm font-bold text-gray-900">Pay Now</span>
                                    <span class="text-lg font-extrabold text-blue-600">{{ formatPrice(payableAmount) }}</span>
                                </div>
                                <div v-if="form.payment_type === 'deposit'" class="flex justify-between text-xs text-gray-500">
                                    <span>Balance due later</span>
                                    <span>{{ formatPrice(totalPrice - depositAmount) }}</span>
                                </div>
                            </div>

                            <button
                                type="submit"
                                :disabled="form.processing"
                                class="w-full rounded-xl bg-blue-600 px-6 py-3.5 text-sm font-bold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed"
                            >
                                <span v-if="form.processing">Processing...</span>
                                <span v-else>Pay {{ formatPrice(payableAmount) }} & Confirm Booking</span>
                            </button>

                            <p class="text-center text-[10px] text-gray-400">
                                By proceeding you agree to the package terms and conditions.
                            </p>
                        </form>

                        <div v-else class="text-center py-4">
                            <p class="text-sm text-gray-500">This package is currently sold out.</p>
                            <a href="/" class="mt-3 inline-flex text-sm font-semibold text-blue-600 hover:text-blue-700">Browse other packages →</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
