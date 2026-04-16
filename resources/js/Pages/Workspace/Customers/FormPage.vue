<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    customer: {
        type: Object,
        default: null,
    },
    formAction: {
        type: String,
        required: true,
    },
    formMethod: {
        type: String,
        required: true,
    },
});

const form = useForm({
    name: props.customer?.name ?? '',
    passport_number: props.customer?.passport_number ?? '',
    passport_copy: null,
    address: props.customer?.address ?? '',
    email: props.customer?.email ?? '',
    phone: props.customer?.phone ?? '',
    emergency_name: props.customer?.emergency_name ?? '',
    emergency_phone: props.customer?.emergency_phone ?? '',
    emergency_relation: props.customer?.emergency_relation ?? '',
    emergency_address: props.customer?.emergency_address ?? '',
    tags: props.customer?.tags ?? [],
    allow_marketing: props.customer?.allow_marketing ?? true,
});

const tagInput = ref('');

const handlePassportCopy = (event) => {
    form.passport_copy = event.target.files?.[0] ?? null;
};

const addTag = () => {
    const tag = tagInput.value.trim();
    if (!tag) {
        return;
    }

    if (!form.tags.includes(tag)) {
        form.tags.push(tag);
    }

    tagInput.value = '';
};

const removeTag = (tag) => {
    form.tags = form.tags.filter((item) => item !== tag);
};

const submit = () => {
    if (props.formMethod === 'put') {
        form.put(props.formAction, { forceFormData: true });
        return;
    }

    form.post(props.formAction, { forceFormData: true });
};
</script>

<template>
    <Head :title="customer ? `Edit ${customer.name}` : 'Create Customer'" />

    <WorkspaceLayout>
        <div class="w-full">
            <section class="tr-bento tr-bento-main">
                <div class="tr-surface rounded-[1.7rem] border border-slate-200 p-7 md:p-8">
                    <div class="border-b border-slate-200 pb-6">
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Traveler CRM Form</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">
                            {{ customer ? 'Edit Traveler Profile' : 'Add Traveler Profile' }}
                        </h2>
                        <p class="mt-2 text-sm text-slate-700">
                            Save traveler identity and emergency contact details in this tenant workspace.
                        </p>
                    </div>

                    <form class="mt-8 grid gap-4" :method="formMethod" :action="formAction" @submit.prevent="submit">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Traveler Name</label>
                            <input v-model="form.name" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200" />
                            <p v-if="form.errors.name" class="mt-2 text-sm text-rose-300">{{ form.errors.name }}</p>
                        </div>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Passport Number</label>
                                <input v-model="form.passport_number" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200" />
                                <p v-if="form.errors.passport_number" class="mt-2 text-sm text-rose-300">{{ form.errors.passport_number }}</p>
                            </div>

                            <div>
                                <label class="mb-2 block text-sm font-medium text-slate-700">Phone</label>
                                <input v-model="form.phone" type="text" class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-900 outline-none transition placeholder:text-slate-500 focus:border-emerald-300 focus:ring-2 focus:ring-emerald-200" />
                                <p v-if="form.errors.phone" class="mt-2 text-sm text-rose-300">{{ form.errors.phone }}</p>
                            </div>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-700">Passport Copy (JPG/PNG)</label>
                            <input
                                type="file"
                                accept=".jpg,.jpeg,.png"
                                class="w-full rounded-2xl border border-slate-200 bg-white px-4 py-3 text-slate-700 file:mr-4 file:rounded-full file:border-0 file:bg-brand-500 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-white"
                                @change="handlePassportCopy"
                            />
                            <p v-if="customer?.passport_copy_url" class="mt-2 text-xs text-slate-500">
                                Current copy:
                                <a :href="customer.passport_copy_url" target="_blank" class="text-brand-300 underline">View image</a>
                            </p>
                            <p v-if="form.errors.passport_copy" class="mt-2 text-sm text-rose-300">{{ form.errors.passport_copy }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Address</label>
                            <textarea v-model="form.address" rows="3" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-white/20"></textarea>
                            <p v-if="form.errors.address" class="mt-2 text-sm text-rose-300">{{ form.errors.address }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Email (Optional)</label>
                            <input v-model="form.email" type="email" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-white/20" />
                            <p v-if="form.errors.email" class="mt-2 text-sm text-rose-300">{{ form.errors.email }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Interest Tags</label>
                            <div class="flex gap-2">
                                <input
                                    v-model="tagInput"
                                    type="text"
                                    placeholder="Umrah, Domestic, Europe..."
                                    class="flex-1 rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition placeholder:text-slate-500 focus:border-white/20"
                                    @keydown.enter.prevent="addTag"
                                />
                                <button
                                    type="button"
                                    class="rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-xs font-semibold text-slate-700 transition hover:border-white/20 hover:bg-white/10"
                                    @click="addTag"
                                >
                                    Add
                                </button>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                <button
                                    v-for="tag in form.tags"
                                    :key="tag"
                                    type="button"
                                    class="inline-flex items-center gap-1 rounded-full border border-slate-200 bg-white/90 px-3 py-1 text-xs text-slate-600"
                                    @click="removeTag(tag)"
                                >
                                    <span>{{ tag }}</span>
                                    <span class="text-slate-400">×</span>
                                </button>
                            </div>
                            <p v-if="form.errors.tags" class="mt-2 text-sm text-rose-300">{{ form.errors.tags }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Marketing Consent</label>
                            <label class="inline-flex items-center gap-2 rounded-full border border-slate-200 bg-white/90 px-4 py-2 text-xs text-slate-600">
                                <input v-model="form.allow_marketing" type="checkbox" class="h-4 w-4" />
                                Allow marketing outreach (Email / WhatsApp)
                            </label>
                            <p v-if="form.errors.allow_marketing" class="mt-2 text-sm text-rose-300">{{ form.errors.allow_marketing }}</p>
                        </div>

                        <div class="rounded-[1.15rem] border border-slate-200 bg-white/90 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.7)] p-4">
                            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-slate-400">Emergency Contact (Waris)</p>

                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-200">Emergency Name</label>
                                    <input v-model="form.emergency_name" type="text" class="w-full rounded-2xl border border-white/10 bg-[#0b1626] px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-white/20" />
                                    <p v-if="form.errors.emergency_name" class="mt-2 text-sm text-rose-300">{{ form.errors.emergency_name }}</p>
                                </div>

                                <div>
                                    <label class="mb-2 block text-sm font-medium text-slate-200">Emergency Phone</label>
                                    <input v-model="form.emergency_phone" type="text" class="w-full rounded-2xl border border-white/10 bg-[#0b1626] px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-white/20" />
                                    <p v-if="form.errors.emergency_phone" class="mt-2 text-sm text-rose-300">{{ form.errors.emergency_phone }}</p>
                                </div>
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-medium text-slate-200">Emergency Relation</label>
                                <input v-model="form.emergency_relation" type="text" class="w-full rounded-2xl border border-white/10 bg-[#0b1626] px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-white/20" />
                                <p v-if="form.errors.emergency_relation" class="mt-2 text-sm text-rose-300">{{ form.errors.emergency_relation }}</p>
                            </div>

                            <div class="mt-4">
                                <label class="mb-2 block text-sm font-medium text-slate-200">Emergency Address</label>
                                <textarea v-model="form.emergency_address" rows="3" class="w-full rounded-2xl border border-white/10 bg-[#0b1626] px-4 py-3 text-slate-100 outline-none transition placeholder:text-slate-500 focus:border-white/20"></textarea>
                                <p v-if="form.errors.emergency_address" class="mt-2 text-sm text-rose-300">{{ form.errors.emergency_address }}</p>
                            </div>
                        </div>

                        <div class="flex flex-col gap-3 pt-4 sm:flex-row">
                            <button
                                type="submit"
                                class="tr-btn-primary rounded-full px-5 py-3 text-sm font-semibold transition disabled:cursor-not-allowed disabled:opacity-60"
                                :disabled="form.processing"
                            >
                                {{ customer ? 'Update Traveler' : 'Save Traveler' }}
                            </button>

                            <a
                                :href="`/workspace/${workspace.slug}/customers`"
                                class="tr-btn-secondary rounded-full px-5 py-3 text-center text-sm font-semibold transition"
                            >
                                Back To Customers
                            </a>
                        </div>
                    </form>
                </div>

                <aside class="tr-surface space-y-4 rounded-[1.7rem] border border-slate-200 p-6">
                    <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">CRM Notes</p>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Storage isolation</p>
                        <p class="mt-2 text-base font-semibold text-slate-900">Passport files are isolated per tenant</p>
                    </div>
                    <div class="rounded-[1rem] border border-slate-200 bg-white/75 p-5">
                        <p class="text-sm text-slate-700">Data quality</p>
                        <p class="mt-2 text-sm leading-6 text-slate-700">Complete emergency data to speed up handling during travel operations.</p>
                    </div>
                </aside>
            </section>
        </div>
    </WorkspaceLayout>
</template>
