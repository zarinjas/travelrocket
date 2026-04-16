<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    availableTags: { type: Array, default: () => [] },
    eligibleCount: { type: Number, default: 0 },
    newsletter: { type: Object, default: null },
});

const isEditing = computed(() => !!props.newsletter?.id);

const form = useForm({
    subject: props.newsletter?.subject ?? '',
    body_html: props.newsletter?.body_html ?? '',
    recipient_filter: props.newsletter?.recipient_filter ?? { tags: [] },
});

const activeTab = ref('compose'); // compose | preview

const toggleTag = (tag) => {
    const tags = form.recipient_filter.tags ?? [];
    const idx = tags.indexOf(tag);
    if (idx >= 0) {
        tags.splice(idx, 1);
    } else {
        tags.push(tag);
    }
    form.recipient_filter = { ...form.recipient_filter, tags };
};

const isTagActive = (tag) => (form.recipient_filter.tags ?? []).includes(tag);

const submit = () => {
    if (isEditing.value) {
        form.put(route('newsletters.update', { tenant: props.workspace.slug, newsletter: props.newsletter.id }));
    } else {
        form.post(route('newsletters.store', { tenant: props.workspace.slug }));
    }
};
</script>

<template>
    <Head :title="isEditing ? 'Edit Newsletter' : 'New Newsletter'" />
    <WorkspaceLayout>
        <div class="w-full py-6">
            <!-- Header -->
            <div class="mb-6 flex items-center gap-4">
                <Link :href="route('newsletters.index', { tenant: workspace.slug })" class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm transition hover:bg-gray-50">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4"><path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" /></svg>
                    Back
                </Link>
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-gray-900">{{ isEditing ? 'Edit Newsletter' : 'New Newsletter' }}</h1>
                    <p class="mt-0.5 text-sm text-gray-500">{{ eligibleCount }} customers eligible for email marketing</p>
                </div>
            </div>

            <form @submit.prevent="submit" class="grid gap-6 lg:grid-cols-3">
                <!-- Main content (2 cols) -->
                <div class="lg:col-span-2 space-y-6">
                    <!-- Subject -->
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2">Email Subject</label>
                        <input
                            v-model="form.subject"
                            type="text"
                            placeholder="e.g. Tawaran Umrah Disember 2026 🕌"
                            class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm text-gray-900 placeholder:text-gray-400 focus:border-gray-900 focus:ring-gray-900"
                        />
                        <p v-if="form.errors.subject" class="mt-1.5 text-xs text-red-500">{{ form.errors.subject }}</p>
                    </div>

                    <!-- Content tabs -->
                    <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5">
                        <div class="flex border-b border-gray-100">
                            <button type="button" @click="activeTab = 'compose'" class="px-6 py-3 text-sm font-semibold transition" :class="activeTab === 'compose' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-400 hover:text-gray-600'">
                                Compose
                            </button>
                            <button type="button" @click="activeTab = 'preview'" class="px-6 py-3 text-sm font-semibold transition" :class="activeTab === 'preview' ? 'text-gray-900 border-b-2 border-gray-900' : 'text-gray-400 hover:text-gray-600'">
                                Preview
                            </button>
                        </div>
                        <div class="p-6">
                            <!-- Compose -->
                            <div v-show="activeTab === 'compose'">
                                <label class="block text-xs font-bold uppercase tracking-widest text-gray-400 mb-2">Email Body (HTML)</label>
                                <textarea
                                    v-model="form.body_html"
                                    rows="16"
                                    placeholder="<h2>Salam &amp; Greetings!</h2>&#10;<p>We have exciting travel deals for you...</p>"
                                    class="w-full rounded-lg border border-gray-200 px-4 py-3 text-sm font-mono text-gray-900 placeholder:text-gray-400 focus:border-gray-900 focus:ring-gray-900 leading-relaxed"
                                ></textarea>
                                <p v-if="form.errors.body_html" class="mt-1.5 text-xs text-red-500">{{ form.errors.body_html }}</p>
                            </div>
                            <!-- Preview -->
                            <div v-show="activeTab === 'preview'" class="min-h-[300px]">
                                <div class="mx-auto max-w-lg rounded-xl border border-gray-100 bg-gray-50 p-6">
                                    <p class="text-sm text-gray-600 mb-4">Hi <span class="font-semibold">Customer Name</span>,</p>
                                    <div class="prose prose-sm max-w-none text-gray-700" v-html="form.body_html || '<p class=\'text-gray-400 italic\'>Your email content will appear here...</p>'"></div>
                                    <div class="mt-6 pt-4 border-t border-gray-200 text-center">
                                        <p class="text-[10px] text-gray-400">Sent by {{ workspace.name }} via TravelRocket</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Sidebar (1 col) -->
                <div class="space-y-6">
                    <!-- Audience filter -->
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Target Audience</h3>
                        <p class="text-xs text-gray-500 mb-4">Filter by tags or send to all marketing-eligible customers.</p>

                        <div v-if="availableTags.length" class="flex flex-wrap gap-2">
                            <button
                                v-for="tag in availableTags"
                                :key="tag"
                                type="button"
                                @click="toggleTag(tag)"
                                class="inline-flex items-center rounded-full px-3 py-1.5 text-xs font-semibold transition ring-1 ring-inset"
                                :class="isTagActive(tag) ? 'bg-gray-900 text-white ring-gray-900' : 'bg-white text-gray-600 ring-gray-200 hover:bg-gray-50'"
                            >
                                {{ tag }}
                            </button>
                        </div>

                        <p v-else class="text-xs text-gray-400 italic">No tags found. All eligible customers will receive this.</p>

                        <div class="mt-4 flex items-center gap-2 rounded-lg bg-blue-50 px-3 py-2">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4 text-blue-500 shrink-0"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a.75.75 0 000 1.5h.253a.25.25 0 01.244.304l-.459 2.066A1.75 1.75 0 0010.747 15H11a.75.75 0 000-1.5h-.253a.25.25 0 01-.244-.304l.459-2.066A1.75 1.75 0 009.253 9H9z" clip-rule="evenodd" /></svg>
                            <p class="text-xs text-blue-700">Only customers with <span class="font-semibold">allow marketing = yes</span> and a valid email will receive this.</p>
                        </div>
                    </div>

                    <!-- Actions -->
                    <div class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-gray-950/5">
                        <h3 class="text-xs font-bold uppercase tracking-widest text-gray-400 mb-3">Actions</h3>
                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="w-full rounded-lg bg-gray-900 px-4 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-gray-800 disabled:opacity-50"
                        >
                            {{ form.processing ? 'Saving...' : (isEditing ? 'Update Draft' : 'Save as Draft') }}
                        </button>
                        <p class="mt-3 text-center text-[10px] text-gray-400">You can send the newsletter from the detail page after saving.</p>
                    </div>
                </div>
            </form>
        </div>
    </WorkspaceLayout>
</template>
