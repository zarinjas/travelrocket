<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';
import { computed, ref } from 'vue';

const props = defineProps({
    workspace: { type: Object, required: true },
    package: { type: Object, default: null },
    statuses: { type: Array, default: () => [] },
    packageTypes: { type: Array, default: () => [] },
    defaultCategory: { type: String, default: 'Umrah' },
    formAction: { type: String, required: true },
    formMethod: { type: String, required: true },
});

const form = useForm({
    category: props.package?.category ?? props.defaultCategory ?? props.packageTypes[0] ?? 'Umrah',
    name: props.package?.name ?? '',
    destination: props.package?.destination ?? '',
    start_date: props.package?.start_date ?? '',
    end_date: props.package?.end_date ?? '',
    itinerary: props.package?.itinerary ?? '',
    itinerary_days: props.package?.itinerary_days?.length ? props.package.itinerary_days : [{ day: 1, title: '', description: '', activities: [''] }],
    inclusions: props.package?.inclusions?.length ? props.package.inclusions : [''],
    exclusions: props.package?.exclusions?.length ? props.package.exclusions : [''],
    pricing_tiers: props.package?.pricing_tiers ?? { adult: null, child: null, infant: null },
    terms_conditions: props.package?.terms_conditions ?? '',
    booking_capacity: props.package?.booking_capacity ?? 0,
    current_bookings: props.package?.current_bookings ?? 0,
    price: props.package?.price ?? 0,
    brochure: null,
    cover_image: null,
    status: props.package?.status ?? props.statuses[0] ?? 'draft',
    description: props.package?.description ?? '',
});

const availableSeats = () => Math.max(0, Number(form.booking_capacity || 0) - Number(form.current_bookings || 0));

const coverImagePreview = ref(props.package?.cover_image_url ?? null);
const handleCoverImageFile = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.cover_image = file;
    coverImagePreview.value = URL.createObjectURL(file);
};
const removeCoverImage = () => {
    form.cover_image = null;
    coverImagePreview.value = null;
};

const handleBrochureFile = (event) => {
    form.brochure = event.target.files?.[0] ?? null;
};

// Health score
const healthScore = computed(() => {
    let score = 0;
    const total = 8;
    if (form.name) score++;
    if (form.destination) score++;
    if (form.start_date && form.end_date) score++;
    if (Number(form.price) > 0) score++;
    if (form.itinerary_days.some(d => d.title)) score++;
    if (form.inclusions.some(i => i.trim())) score++;
    if (form.pricing_tiers?.adult > 0) score++;
    if (form.status === 'published') score++;
    return Math.round((score / total) * 100);
});

const healthColor = computed(() => {
    if (healthScore.value >= 80) return 'bg-emerald-500';
    if (healthScore.value >= 50) return 'bg-amber-500';
    return 'bg-red-500';
});

// Itinerary days management
const addDay = () => {
    form.itinerary_days.push({ day: form.itinerary_days.length + 1, title: '', description: '', activities: [''] });
};
const removeDay = (index) => {
    form.itinerary_days.splice(index, 1);
    form.itinerary_days.forEach((d, i) => d.day = i + 1);
};
const addActivity = (dayIndex) => {
    form.itinerary_days[dayIndex].activities.push('');
};
const removeActivity = (dayIndex, actIndex) => {
    form.itinerary_days[dayIndex].activities.splice(actIndex, 1);
};

// Inclusions/Exclusions management
const addInclusion = () => form.inclusions.push('');
const removeInclusion = (i) => form.inclusions.splice(i, 1);
const addExclusion = () => form.exclusions.push('');
const removeExclusion = (i) => form.exclusions.splice(i, 1);

const submit = () => {
    const cleanedData = {
        ...form.data(),
        itinerary_days: form.itinerary_days.filter(d => d.title.trim()).map(d => ({
            ...d,
            activities: d.activities.filter(a => a.trim()),
        })),
        inclusions: form.inclusions.filter(i => i.trim()),
        exclusions: form.exclusions.filter(i => i.trim()),
    };

    if (props.formMethod === 'put') {
        form.transform(() => ({
            ...cleanedData,
            _method: 'put',
        })).post(props.formAction, { forceFormData: true });
        return;
    }
    form.transform(() => cleanedData).post(props.formAction, { forceFormData: true });
};
</script>

<template>
    <Head :title="package ? `Edit ${package.name}` : 'Create Package'" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">
                        {{ package ? 'Edit Package' : 'Create Package' }}
                    </h1>
                    <p class="mt-1 text-sm text-gray-500">{{ package ? 'Update details for this travel product.' : 'Add a new travel product to your catalog.' }}</p>
                </div>
                <a
                    :href="`/workspace/${workspace.slug}/packages`"
                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                    Back
                </a>
            </div>

            <div class="grid gap-6 xl:grid-cols-[1fr_320px]">

                <!-- Main Form -->
                <div class="space-y-6">
                    <form @submit.prevent="submit">

                        <!-- Basic Details -->
                        <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Package Details</h2>
                            </div>
                            <div class="space-y-5 p-6">
                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Category</label>
                                        <select v-model="form.category" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                            <option v-for="type in packageTypes" :key="type" :value="type">{{ type }}</option>
                                        </select>
                                        <p v-if="form.errors.category" class="mt-1.5 text-sm text-red-600">{{ form.errors.category }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Package Name</label>
                                        <input v-model="form.name" type="text" placeholder="e.g. Umrah Ekonomi 12 Hari" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                        <p v-if="form.errors.name" class="mt-1.5 text-sm text-red-600">{{ form.errors.name }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Destination</label>
                                    <input v-model="form.destination" type="text" placeholder="Makkah, Istanbul, Tokyo..." class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                    <p v-if="form.errors.destination" class="mt-1.5 text-sm text-red-600">{{ form.errors.destination }}</p>
                                </div>

                                <div class="grid gap-5 sm:grid-cols-2">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Start Date</label>
                                        <input v-model="form.start_date" type="date" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                        <p v-if="form.errors.start_date" class="mt-1.5 text-sm text-red-600">{{ form.errors.start_date }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">End Date</label>
                                        <input v-model="form.end_date" type="date" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                        <p v-if="form.errors.end_date" class="mt-1.5 text-sm text-red-600">{{ form.errors.end_date }}</p>
                                    </div>
                                </div>

                                <div class="grid gap-5 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Base Price (RM)</label>
                                        <input v-model="form.price" type="number" min="0" step="0.01" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                        <p v-if="form.errors.price" class="mt-1.5 text-sm text-red-600">{{ form.errors.price }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Booking Capacity</label>
                                        <input v-model="form.booking_capacity" type="number" min="0" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900" />
                                        <p v-if="form.errors.booking_capacity" class="mt-1.5 text-sm text-red-600">{{ form.errors.booking_capacity }}</p>
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Status</label>
                                        <select v-model="form.status" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 focus:ring-2 focus:ring-gray-900">
                                            <option v-for="status in statuses" :key="status" :value="status">{{ status.charAt(0).toUpperCase() + status.slice(1) }}</option>
                                        </select>
                                        <p v-if="form.errors.status" class="mt-1.5 text-sm text-red-600">{{ form.errors.status }}</p>
                                    </div>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Brochure (PDF / JPG / PNG)</label>
                                    <input
                                        type="file"
                                        accept=".pdf,.jpg,.jpeg,.png"
                                        class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 file:mr-3 file:rounded-md file:border-0 file:bg-gray-900 file:px-3 file:py-1 file:text-xs file:font-semibold file:text-white"
                                        @change="handleBrochureFile"
                                    />
                                    <p v-if="package?.brochure_url" class="mt-1.5 text-xs text-gray-500">
                                        Current: <a :href="package.brochure_url" target="_blank" class="text-gray-900 underline">View file</a>
                                    </p>
                                    <p v-if="form.errors.brochure" class="mt-1.5 text-sm text-red-600">{{ form.errors.brochure }}</p>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Cover Image</label>
                                    <p class="mb-2 text-xs text-gray-500">Displayed on the public catalog and package cards. Recommended: 1200×800px landscape.</p>
                                    <div class="flex items-start gap-4">
                                        <div class="flex h-28 w-44 shrink-0 items-center justify-center overflow-hidden rounded-xl bg-gray-100 ring-1 ring-gray-200">
                                            <img v-if="coverImagePreview" :src="coverImagePreview" alt="Cover" class="h-full w-full object-cover" />
                                            <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="h-8 w-8 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                                        </div>
                                        <div class="flex flex-col gap-2">
                                            <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                                                Upload Image
                                                <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="handleCoverImageFile" />
                                            </label>
                                            <button v-if="coverImagePreview" type="button" class="text-xs font-medium text-red-600 hover:text-red-700" @click="removeCoverImage">Remove</button>
                                        </div>
                                    </div>
                                    <p v-if="form.errors.cover_image" class="mt-1.5 text-sm text-red-600">{{ form.errors.cover_image }}</p>
                                </div>

                                <div>
                                    <label class="mb-1.5 block text-sm font-medium text-gray-700">Description</label>
                                    <p class="mb-2 text-xs text-gray-500">Public-facing description shown to customers on the catalog page. Include highlights, unique selling points, and what makes this package special.</p>
                                    <textarea v-model="form.description" rows="5" placeholder="e.g. Experience the spiritual journey of a lifetime with our 12-day Umrah package. Includes 5-star hotel in Makkah, guided ziarah, and seamless transport between holy cities..." class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900"></textarea>
                                    <p v-if="form.errors.description" class="mt-1.5 text-sm text-red-600">{{ form.errors.description }}</p>
                                </div>
                            </div>
                        </div>

                        <!-- Pricing Tiers -->
                        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Pricing Tiers</h2>
                                <p class="mt-0.5 text-xs text-gray-500">Set per-person rates by age group. Leave blank to use base price for all.</p>
                            </div>
                            <div class="p-6">
                                <div class="grid gap-5 sm:grid-cols-3">
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Adult (RM)</label>
                                        <input v-model="form.pricing_tiers.adult" type="number" min="0" step="0.01" placeholder="e.g. 4999" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Child (RM)</label>
                                        <input v-model="form.pricing_tiers.child" type="number" min="0" step="0.01" placeholder="e.g. 3999" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                    </div>
                                    <div>
                                        <label class="mb-1.5 block text-sm font-medium text-gray-700">Infant (RM)</label>
                                        <input v-model="form.pricing_tiers.infant" type="number" min="0" step="0.01" placeholder="e.g. 999" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Itinerary Builder -->
                        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                <div>
                                    <h2 class="text-sm font-semibold text-gray-900">Itinerary Builder</h2>
                                    <p class="mt-0.5 text-xs text-gray-500">Build a day-by-day structured itinerary.</p>
                                </div>
                                <button type="button" @click="addDay" class="inline-flex items-center gap-1.5 rounded-lg bg-gray-900 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5"><path d="M8.75 3.75a.75.75 0 00-1.5 0v3.5h-3.5a.75.75 0 000 1.5h3.5v3.5a.75.75 0 001.5 0v-3.5h3.5a.75.75 0 000-1.5h-3.5v-3.5z" /></svg>
                                    Add Day
                                </button>
                            </div>
                            <div class="divide-y divide-gray-100">
                                <div v-for="(day, dayIdx) in form.itinerary_days" :key="dayIdx" class="p-6">
                                    <div class="mb-4 flex items-center justify-between">
                                        <span class="inline-flex items-center gap-2 rounded-full bg-gray-900 px-3 py-1 text-xs font-bold text-white">Day {{ day.day }}</span>
                                        <button v-if="form.itinerary_days.length > 1" type="button" @click="removeDay(dayIdx)" class="text-xs font-medium text-red-600 hover:text-red-800">Remove</button>
                                    </div>
                                    <div class="space-y-3">
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Title</label>
                                            <input v-model="day.title" type="text" :placeholder="`e.g. Arrival in ${form.destination || 'Destination'}`" class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Description</label>
                                            <textarea v-model="day.description" rows="2" placeholder="Brief overview of the day..." class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900"></textarea>
                                        </div>
                                        <div>
                                            <label class="mb-1 block text-xs font-medium text-gray-600">Activities</label>
                                            <div class="space-y-2">
                                                <div v-for="(act, actIdx) in day.activities" :key="actIdx" class="flex items-center gap-2">
                                                    <input v-model="day.activities[actIdx]" type="text" placeholder="e.g. Hotel check-in, City tour..." class="flex-1 rounded-lg border-0 bg-gray-50 px-3.5 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                                    <button v-if="day.activities.length > 1" type="button" @click="removeActivity(dayIdx, actIdx)" class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5"><path d="M3.75 7.25a.75.75 0 000 1.5h8.5a.75.75 0 000-1.5h-8.5z" /></svg>
                                                    </button>
                                                </div>
                                            </div>
                                            <button type="button" @click="addActivity(dayIdx)" class="mt-2 text-xs font-medium text-gray-500 hover:text-gray-900">+ Add activity</button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Inclusions & Exclusions -->
                        <div class="mt-6 grid gap-6 sm:grid-cols-2">
                            <!-- Inclusions -->
                            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                    <div>
                                        <h2 class="text-sm font-semibold text-gray-900">Inclusions</h2>
                                        <p class="mt-0.5 text-xs text-gray-500">What's included in the package.</p>
                                    </div>
                                    <button type="button" @click="addInclusion" class="inline-flex items-center gap-1 rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M8.75 3.75a.75.75 0 00-1.5 0v3.5h-3.5a.75.75 0 000 1.5h3.5v3.5a.75.75 0 001.5 0v-3.5h3.5a.75.75 0 000-1.5h-3.5v-3.5z" /></svg>
                                        Add
                                    </button>
                                </div>
                                <div class="space-y-2 p-5">
                                    <div v-for="(item, i) in form.inclusions" :key="i" class="flex items-center gap-2">
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-emerald-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 text-emerald-600"><path fill-rule="evenodd" d="M12.416 3.376a.75.75 0 01.208 1.04l-5 7.5a.75.75 0 01-1.154.114l-3-3a.75.75 0 011.06-1.06l2.353 2.353 4.493-6.74a.75.75 0 011.04-.207z" clip-rule="evenodd" /></svg>
                                        </div>
                                        <input v-model="form.inclusions[i]" type="text" placeholder="e.g. Return flight tickets" class="flex-1 rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                        <button v-if="form.inclusions.length > 1" type="button" @click="removeInclusion(i)" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M3.75 7.25a.75.75 0 000 1.5h8.5a.75.75 0 000-1.5h-8.5z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Exclusions -->
                            <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                                <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4">
                                    <div>
                                        <h2 class="text-sm font-semibold text-gray-900">Exclusions</h2>
                                        <p class="mt-0.5 text-xs text-gray-500">What's NOT included.</p>
                                    </div>
                                    <button type="button" @click="addExclusion" class="inline-flex items-center gap-1 rounded-lg bg-gray-900 px-2.5 py-1.5 text-xs font-semibold text-white transition hover:bg-gray-800">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M8.75 3.75a.75.75 0 00-1.5 0v3.5h-3.5a.75.75 0 000 1.5h3.5v3.5a.75.75 0 001.5 0v-3.5h3.5a.75.75 0 000-1.5h-3.5v-3.5z" /></svg>
                                        Add
                                    </button>
                                </div>
                                <div class="space-y-2 p-5">
                                    <div v-for="(item, i) in form.exclusions" :key="i" class="flex items-center gap-2">
                                        <div class="flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-red-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3 text-red-600"><path d="M5.28 4.22a.75.75 0 00-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 101.06 1.06L8 9.06l2.72 2.72a.75.75 0 101.06-1.06L9.06 8l2.72-2.72a.75.75 0 00-1.06-1.06L8 6.94 5.28 4.22z" /></svg>
                                        </div>
                                        <input v-model="form.exclusions[i]" type="text" placeholder="e.g. Travel insurance" class="flex-1 rounded-lg border-0 bg-gray-50 px-3 py-2 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" />
                                        <button v-if="form.exclusions.length > 1" type="button" @click="removeExclusion(i)" class="flex h-7 w-7 shrink-0 items-center justify-center rounded-lg text-gray-400 transition hover:bg-red-50 hover:text-red-600">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3 w-3"><path d="M3.75 7.25a.75.75 0 000 1.5h8.5a.75.75 0 000-1.5h-8.5z" /></svg>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Terms & Conditions -->
                        <div class="mt-6 overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                            <div class="border-b border-gray-100 px-6 py-4">
                                <h2 class="text-sm font-semibold text-gray-900">Terms & Conditions</h2>
                                <p class="mt-0.5 text-xs text-gray-500">Cancellation policy, payment terms, refund policy, etc.</p>
                            </div>
                            <div class="p-6">
                                <textarea v-model="form.terms_conditions" rows="5" placeholder="e.g. Full payment required 30 days before departure. 50% refund for cancellation 14 days prior..." class="w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-200 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900"></textarea>
                                <p v-if="form.errors.terms_conditions" class="mt-1.5 text-sm text-red-600">{{ form.errors.terms_conditions }}</p>
                            </div>
                        </div>

                        <!-- Submit -->
                        <div class="mt-6 flex gap-3">
                            <button
                                type="submit"
                                class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:cursor-not-allowed disabled:opacity-50"
                                :disabled="form.processing"
                            >
                                {{ package ? 'Update Package' : 'Save Package' }}
                            </button>
                            <a
                                :href="`/workspace/${workspace.slug}/packages`"
                                class="inline-flex items-center rounded-lg border border-gray-200 bg-white px-5 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50"
                            >Cancel</a>
                        </div>
                    </form>
                </div>

                <!-- Sidebar -->
                <div class="space-y-4">

                    <!-- Health Score -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Package Health</h3>
                        </div>
                        <div class="p-5">
                            <div class="mb-2 flex items-center justify-between">
                                <span class="text-xs font-medium text-gray-500">Completeness</span>
                                <span class="text-sm font-bold tabular-nums" :class="healthScore >= 80 ? 'text-emerald-600' : healthScore >= 50 ? 'text-amber-600' : 'text-red-600'">{{ healthScore }}%</span>
                            </div>
                            <div class="h-2 w-full overflow-hidden rounded-full bg-gray-100">
                                <div :class="healthColor" class="h-full rounded-full transition-all duration-300" :style="{ width: healthScore + '%' }"></div>
                            </div>
                            <div class="mt-3 space-y-1.5 text-xs text-gray-500">
                                <div class="flex items-center gap-2">
                                    <span :class="form.name ? 'text-emerald-500' : 'text-gray-300'">{{ form.name ? '✓' : '○' }}</span>
                                    <span>Package name</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.destination ? 'text-emerald-500' : 'text-gray-300'">{{ form.destination ? '✓' : '○' }}</span>
                                    <span>Destination</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.start_date && form.end_date ? 'text-emerald-500' : 'text-gray-300'">{{ form.start_date && form.end_date ? '✓' : '○' }}</span>
                                    <span>Travel dates</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="Number(form.price) > 0 ? 'text-emerald-500' : 'text-gray-300'">{{ Number(form.price) > 0 ? '✓' : '○' }}</span>
                                    <span>Base price</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.itinerary_days.some(d => d.title) ? 'text-emerald-500' : 'text-gray-300'">{{ form.itinerary_days.some(d => d.title) ? '✓' : '○' }}</span>
                                    <span>Itinerary</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.inclusions.some(i => i.trim()) ? 'text-emerald-500' : 'text-gray-300'">{{ form.inclusions.some(i => i.trim()) ? '✓' : '○' }}</span>
                                    <span>Inclusions</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.pricing_tiers?.adult > 0 ? 'text-emerald-500' : 'text-gray-300'">{{ form.pricing_tiers?.adult > 0 ? '✓' : '○' }}</span>
                                    <span>Pricing tiers</span>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span :class="form.status === 'published' ? 'text-emerald-500' : 'text-gray-300'">{{ form.status === 'published' ? '✓' : '○' }}</span>
                                    <span>Published</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Inventory Preview -->
                    <div class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Inventory Preview</h3>
                        </div>
                        <div class="grid grid-cols-3 divide-x divide-gray-100 text-center">
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Capacity</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">{{ form.booking_capacity }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Booked</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums text-gray-900">{{ form.current_bookings }}</p>
                            </div>
                            <div class="p-4">
                                <p class="text-xs font-medium text-gray-500">Available</p>
                                <p class="mt-1.5 text-lg font-bold tabular-nums" :class="availableSeats() > 0 ? 'text-emerald-600' : 'text-red-600'">{{ availableSeats() }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Pricing Summary -->
                    <div v-if="form.pricing_tiers?.adult || form.pricing_tiers?.child || form.pricing_tiers?.infant" class="overflow-hidden rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="border-b border-gray-100 px-5 py-4">
                            <h3 class="text-sm font-semibold text-gray-900">Pricing Summary</h3>
                        </div>
                        <div class="divide-y divide-gray-100">
                            <div v-if="form.pricing_tiers?.adult" class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Adult</span>
                                <span class="text-sm font-medium tabular-nums text-gray-900">RM{{ Number(form.pricing_tiers.adult).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div v-if="form.pricing_tiers?.child" class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Child</span>
                                <span class="text-sm font-medium tabular-nums text-gray-900">RM{{ Number(form.pricing_tiers.child).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                            <div v-if="form.pricing_tiers?.infant" class="flex items-center justify-between px-5 py-3">
                                <span class="text-xs text-gray-500">Infant</span>
                                <span class="text-sm font-medium tabular-nums text-gray-900">RM{{ Number(form.pricing_tiers.infant).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
