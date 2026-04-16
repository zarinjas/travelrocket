<script setup>
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { ref, computed, watch } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: { type: Object, required: true },
});

const page = usePage();
const flash = computed(() => page.props.flash);

// Active tab
const activeTab = ref('company');
const tabs = [
    { key: 'company', label: 'Company Info' },
    { key: 'banking', label: 'Banking' },
    { key: 'social', label: 'Social & Branding' },
    { key: 'documents', label: 'Documents' },
];

// Form
const form = useForm({
    name: props.workspace.name ?? '',
    logo: null,
    company_name: props.workspace.company_name ?? '',
    company_address: props.workspace.company_address ?? '',
    company_phone: props.workspace.company_phone ?? '',
    company_email: props.workspace.company_email ?? '',
    company_website: props.workspace.company_website ?? '',
    bank_name: props.workspace.bank_name ?? '',
    bank_account_name: props.workspace.bank_account_name ?? '',
    bank_account_number: props.workspace.bank_account_number ?? '',
    bank_swift: props.workspace.bank_swift ?? '',
    quotation_terms: props.workspace.quotation_terms ?? '',
    social_links: {
        facebook: props.workspace.social_links?.facebook ?? '',
        instagram: props.workspace.social_links?.instagram ?? '',
        tiktok: props.workspace.social_links?.tiktok ?? '',
        x: props.workspace.social_links?.x ?? '',
        linkedin: props.workspace.social_links?.linkedin ?? '',
    },
});

const logoPreview = ref(props.workspace.logo_url);
const handleLogoFile = (event) => {
    const file = event.target.files?.[0];
    if (!file) return;
    form.logo = file;
    logoPreview.value = URL.createObjectURL(file);
};

const removeLogo = () => {
    form.logo = null;
    logoPreview.value = null;
};

const submitForm = () => {
    form.post(`/workspace/${props.workspace.slug}/settings/branding`, {
        forceFormData: true,
        preserveScroll: true,
    });
};

// Show success flash then auto-dismiss
const showSuccess = ref(false);
watch(flash, (val) => {
    if (val?.success) {
        showSuccess.value = true;
        setTimeout(() => (showSuccess.value = false), 4000);
    }
}, { immediate: true });

// Checklist
const checklist = computed(() => props.workspace.checklist || []);
const profileCompleteness = computed(() => props.workspace.profile_completeness || 0);
</script>

<template>
    <Head :title="`${workspace.name} Settings`" />

    <WorkspaceLayout>
        <div class="w-full py-6">

            <!-- Header -->
            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 tracking-tight">Settings</h1>
                    <p class="mt-1 text-sm text-gray-500">Manage your workspace profile, branding, and document defaults.</p>
                </div>
                <button
                    type="button"
                    class="inline-flex items-center gap-2 rounded-lg bg-gray-900 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:opacity-50"
                    :disabled="form.processing"
                    @click="submitForm"
                >
                    <svg v-if="form.processing" class="h-4 w-4 animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"/></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                    Save Settings
                </button>
            </div>

            <!-- Success flash -->
            <Transition enter-active-class="transition duration-200 ease-out" enter-from-class="opacity-0 -translate-y-1" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition duration-150 ease-in" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 -translate-y-1">
                <div v-if="showSuccess" class="mb-6 flex items-center gap-3 rounded-xl bg-emerald-50 px-5 py-3.5 ring-1 ring-emerald-200/50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 shrink-0 text-emerald-600"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" /></svg>
                    <p class="text-sm font-medium text-emerald-800">Settings updated successfully.</p>
                </div>
            </Transition>

            <!-- Main layout: Content + Sidebar -->
            <div class="grid gap-6 xl:grid-cols-[1fr_340px]">

                <!-- Left: Tabbed form -->
                <div>
                    <!-- Tab navigation -->
                    <div class="mb-6 flex gap-1 rounded-xl bg-gray-100/80 p-1">
                        <button
                            v-for="tab in tabs"
                            :key="tab.key"
                            type="button"
                            class="flex-1 rounded-lg px-4 py-2 text-sm font-medium transition"
                            :class="activeTab === tab.key ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            @click="activeTab = tab.key"
                        >
                            {{ tab.label }}
                        </button>
                    </div>

                    <form @submit.prevent="submitForm">

                        <!-- Company Info Tab -->
                        <div v-show="activeTab === 'company'" class="space-y-6">
                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Workspace Identity</h3>
                                <p class="mt-1 text-xs text-gray-500">This name appears across your workspace and navigation.</p>
                                <div class="mt-4">
                                    <label class="block text-xs font-medium text-gray-700">Workspace Name</label>
                                    <input v-model="form.name" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="My Travel Agency" />
                                </div>
                                <div class="mt-3">
                                    <label class="block text-xs font-medium text-gray-700">Workspace Slug</label>
                                    <div class="mt-1.5 flex items-center rounded-lg bg-gray-50 px-3.5 py-2.5 ring-1 ring-inset ring-gray-300">
                                        <span class="text-sm text-gray-400">/workspace/</span>
                                        <span class="text-sm font-medium text-gray-900">{{ workspace.slug }}</span>
                                    </div>
                                    <p class="mt-1 text-[11px] text-gray-400">Slug cannot be changed.</p>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Company Details</h3>
                                <p class="mt-1 text-xs text-gray-500">Used in quotations, invoices, and official documents.</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Company Name</label>
                                        <input v-model="form.company_name" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="TravelRocket Sdn. Bhd." />
                                        <p v-if="form.errors.company_name" class="mt-1 text-xs text-red-600">{{ form.errors.company_name }}</p>
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Phone</label>
                                        <input v-model="form.company_phone" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="+60 12-345 6789" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Email</label>
                                        <input v-model="form.company_email" type="email" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="hello@travelrocket.my" />
                                        <p v-if="form.errors.company_email" class="mt-1 text-xs text-red-600">{{ form.errors.company_email }}</p>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Website</label>
                                        <input v-model="form.company_website" type="url" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="https://www.travelrocket.my" />
                                    </div>
                                    <div class="sm:col-span-2">
                                        <label class="block text-xs font-medium text-gray-700">Address</label>
                                        <textarea v-model="form.company_address" rows="3" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="Level 8, Menara Travel, Kuala Lumpur"></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Banking Tab -->
                        <div v-show="activeTab === 'banking'" class="space-y-6">
                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Bank Account</h3>
                                <p class="mt-1 text-xs text-gray-500">Displayed on invoices and payment instructions.</p>
                                <div class="mt-4 grid gap-4 sm:grid-cols-2">
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Bank Name</label>
                                        <input v-model="form.bank_name" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="Maybank" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Account Name</label>
                                        <input v-model="form.bank_account_name" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="TravelRocket Sdn. Bhd." />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">Account Number</label>
                                        <input v-model="form.bank_account_number" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="5123 9921 0045" />
                                    </div>
                                    <div>
                                        <label class="block text-xs font-medium text-gray-700">SWIFT Code</label>
                                        <input v-model="form.bank_swift" type="text" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="MBBEMYKL" />
                                    </div>
                                </div>
                            </div>

                            <!-- Bank preview card -->
                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <p class="text-[10px] font-semibold uppercase tracking-widest text-gray-400">Payment Details Preview</p>
                                <div class="mt-3 flex items-center gap-3">
                                    <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-gray-100">
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-5 w-5 text-gray-400"><path d="M1 4.25a3.733 3.733 0 012.25-.75h13.5c.844 0 1.623.279 2.25.75A2.25 2.25 0 0016.75 2H3.25A2.25 2.25 0 001 4.25zM1 7.25a3.733 3.733 0 012.25-.75h13.5c.844 0 1.623.279 2.25.75A2.25 2.25 0 0016.75 5H3.25A2.25 2.25 0 001 7.25zM7 8a1 1 0 000 2h6a1 1 0 100-2H7zM2 11.25A2.25 2.25 0 014.25 9h11.5A2.25 2.25 0 0118 11.25v5.5A2.25 2.25 0 0115.75 19H4.25A2.25 2.25 0 012 16.75v-5.5z" /></svg>
                                    </div>
                                    <div class="min-w-0">
                                        <p class="text-sm font-bold text-gray-900 truncate">{{ form.bank_name || 'Bank Name' }}</p>
                                        <p class="text-xs text-gray-500 truncate">{{ form.bank_account_name || 'Account Holder Name' }}</p>
                                    </div>
                                </div>
                                <div class="mt-4 grid grid-cols-2 gap-3 rounded-lg bg-gray-50 p-3.5">
                                    <div>
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400">Account No.</p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900 tabular-nums tracking-wider">{{ form.bank_account_number || '•••• •••• ••••' }}</p>
                                    </div>
                                    <div v-if="form.bank_swift" class="text-right">
                                        <p class="text-[10px] uppercase tracking-wider text-gray-400">SWIFT</p>
                                        <p class="mt-0.5 text-sm font-semibold text-gray-900">{{ form.bank_swift }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Social & Branding Tab -->
                        <div v-show="activeTab === 'social'" class="space-y-6">
                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Company Logo</h3>
                                <p class="mt-1 text-xs text-gray-500">Used in documents, emails, and branding. Recommended size: 400×400px.</p>
                                <div class="mt-4 flex items-center gap-5">
                                    <div class="flex h-20 w-20 shrink-0 items-center justify-center overflow-hidden rounded-2xl bg-gray-100 ring-1 ring-gray-200">
                                        <img v-if="logoPreview" :src="logoPreview" alt="Logo" class="h-full w-full object-contain" />
                                        <svg v-else xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" class="h-8 w-8 text-gray-300"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 21h16.5A2.25 2.25 0 0 0 22.5 18.75V5.25A2.25 2.25 0 0 0 20.25 3H3.75A2.25 2.25 0 0 0 1.5 5.25v13.5A2.25 2.25 0 0 0 3.75 21Z" /></svg>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <label class="inline-flex cursor-pointer items-center gap-2 rounded-lg bg-white px-3.5 py-2 text-xs font-semibold text-gray-700 ring-1 ring-gray-300 transition hover:bg-gray-50">
                                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path d="M9.25 13.25a.75.75 0 001.5 0V4.636l2.955 3.129a.75.75 0 001.09-1.03l-4.25-4.5a.75.75 0 00-1.09 0l-4.25 4.5a.75.75 0 101.09 1.03L9.25 4.636v8.614z" /><path d="M3.5 12.75a.75.75 0 00-1.5 0v2.5A2.75 2.75 0 004.75 18h10.5A2.75 2.75 0 0018 15.25v-2.5a.75.75 0 00-1.5 0v2.5c0 .69-.56 1.25-1.25 1.25H4.75c-.69 0-1.25-.56-1.25-1.25v-2.5z" /></svg>
                                            Upload Logo
                                            <input type="file" accept=".jpg,.jpeg,.png,.webp" class="hidden" @change="handleLogoFile" />
                                        </label>
                                        <button v-if="logoPreview" type="button" class="text-xs font-medium text-red-600 hover:text-red-700" @click="removeLogo">Remove logo</button>
                                    </div>
                                </div>
                            </div>

                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Social Media Links</h3>
                                <p class="mt-1 text-xs text-gray-500">Links to your agency's social profiles.</p>
                                <div class="mt-4 space-y-3">
                                    <div v-for="(social, key) in { facebook: 'Facebook', instagram: 'Instagram', tiktok: 'TikTok', x: 'X (Twitter)', linkedin: 'LinkedIn' }" :key="key">
                                        <label class="block text-xs font-medium text-gray-700">{{ social }}</label>
                                        <input v-model="form.social_links[key]" type="url" class="mt-1.5 block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" :placeholder="`https://${key === 'x' ? 'x' : key}.com/...`" />
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Documents Tab -->
                        <div v-show="activeTab === 'documents'" class="space-y-6">
                            <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                                <h3 class="text-sm font-semibold text-gray-900">Default Quotation Terms &amp; Conditions</h3>
                                <p class="mt-1 text-xs text-gray-500">Pre-filled when generating new quotations.</p>
                                <div class="mt-4">
                                    <textarea v-model="form.quotation_terms" rows="8" class="block w-full rounded-lg border-0 bg-gray-50 px-3.5 py-2.5 text-sm text-gray-900 ring-1 ring-inset ring-gray-300 placeholder:text-gray-400 focus:ring-2 focus:ring-gray-900" placeholder="1) Deposit is required to confirm booking.&#10;2) Full payment must be made before departure.&#10;3) Cancellation charges may apply."></textarea>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>

                <!-- Right sidebar -->
                <div class="space-y-4">

                    <!-- Document Readiness -->
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                        <div class="flex items-center justify-between">
                            <h3 class="text-sm font-semibold text-gray-900">Profile Readiness</h3>
                            <span class="rounded-full px-2.5 py-0.5 text-xs font-bold tabular-nums" :class="profileCompleteness >= 100 ? 'bg-emerald-50 text-emerald-700' : profileCompleteness >= 60 ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700'">
                                {{ profileCompleteness }}%
                            </span>
                        </div>
                        <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-gray-100">
                            <div class="h-full rounded-full transition-all duration-500" :class="profileCompleteness >= 100 ? 'bg-emerald-500' : profileCompleteness >= 60 ? 'bg-amber-500' : 'bg-red-500'" :style="{ width: profileCompleteness + '%' }"></div>
                        </div>
                        <p class="mt-2 text-[11px] text-gray-500">Complete your profile for professional documents.</p>
                        <ul class="mt-3 space-y-1.5">
                            <li v-for="item in checklist" :key="item.key" class="flex items-center gap-2.5">
                                <div class="flex h-4 w-4 shrink-0 items-center justify-center rounded-full" :class="item.done ? 'bg-emerald-100' : 'bg-gray-100'">
                                    <svg v-if="item.done" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-3 w-3 text-emerald-600"><path fill-rule="evenodd" d="M16.704 4.153a.75.75 0 01.143 1.052l-8 10.5a.75.75 0 01-1.127.075l-4.5-4.5a.75.75 0 011.06-1.06l3.894 3.893 7.48-9.817a.75.75 0 011.05-.143z" clip-rule="evenodd" /></svg>
                                    <div v-else class="h-1.5 w-1.5 rounded-full bg-gray-300"></div>
                                </div>
                                <span class="text-xs" :class="item.done ? 'text-gray-900' : 'text-gray-400'">{{ item.label }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- Document Branding Preview -->
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-sm font-semibold text-gray-900">Document Preview</h3>
                        <p class="mt-1 text-[11px] text-gray-500">How your identity appears on quotations & invoices.</p>

                        <div class="mt-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                            <!-- Letterhead preview -->
                            <div class="flex items-start gap-3">
                                <div v-if="logoPreview" class="flex h-10 w-10 shrink-0 items-center justify-center overflow-hidden rounded-lg bg-white ring-1 ring-gray-200">
                                    <img :src="logoPreview" alt="Logo" class="h-full w-full object-contain" />
                                </div>
                                <div v-else class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-gray-200">
                                    <span class="text-xs font-bold text-gray-400">LOGO</span>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-sm font-bold text-gray-900 truncate">{{ form.company_name || 'Company Name' }}</p>
                                    <p class="mt-0.5 text-[11px] text-gray-500 truncate">{{ form.company_phone || 'Phone' }} · {{ form.company_email || 'Email' }}</p>
                                </div>
                            </div>
                            <div class="mt-3 border-t border-dashed border-gray-200 pt-3">
                                <p class="text-[10px] leading-relaxed text-gray-400 whitespace-pre-line">{{ form.company_address || 'Company address will appear here.' }}</p>
                            </div>
                            <div v-if="form.company_website" class="mt-2">
                                <p class="text-[10px] text-gray-400">{{ form.company_website }}</p>
                            </div>
                        </div>
                    </div>

                    <!-- Danger Zone -->
                    <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-red-200/60">
                        <h3 class="text-sm font-semibold text-red-900">Danger Zone</h3>
                        <p class="mt-1 text-[11px] text-gray-500">Irreversible actions for this workspace.</p>
                        <div class="mt-4 flex items-center justify-between rounded-lg border border-red-100 bg-red-50/50 px-4 py-3">
                            <div>
                                <p class="text-xs font-medium text-red-900">Export Workspace Data</p>
                                <p class="mt-0.5 text-[10px] text-red-700/70">Download all of your data as CSV.</p>
                            </div>
                            <button type="button" disabled class="rounded-lg border border-red-200 bg-white px-3 py-1.5 text-[11px] font-semibold text-red-700 opacity-50 cursor-not-allowed">
                                Coming Soon
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </WorkspaceLayout>
</template>
