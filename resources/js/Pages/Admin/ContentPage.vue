<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    content: {
        type: Object,
        default: () => ({
            home: {},
            features: {},
            pricing: {},
        }),
    },
    revisions: {
        type: Array,
        default: () => [],
    },
    media: {
        type: Array,
        default: () => [],
    },
});

const pageData = useForm({
    home: {
        hero_badge: '',
        hero_title: '',
        hero_subtitle: '',
        primary_cta_label: '',
        primary_cta_url: '',
        secondary_cta_label: '',
        secondary_cta_url: '',
        highlights: [],
    },
    features: {
        hero_title: '',
        hero_subtitle: '',
        partner_logos: [],
        testimonials: [],
    },
    pricing: {
        hero_title: '',
        hero_subtitle: '',
        partner_logos: [],
        testimonials: [],
    },
    note: '',
});

const mediaForm = useForm({
    file: null,
});

const parseError = ref('');
const homeHighlightsText = ref('[]');
const featureLogosText = ref('[]');
const pricingLogosText = ref('[]');
const featureTestimonialsText = ref('[]');
const pricingTestimonialsText = ref('[]');

const bootFromProps = (payload) => {
    pageData.home = {
        ...pageData.home,
        ...(payload.home ?? {}),
    };

    pageData.features = {
        ...pageData.features,
        ...(payload.features ?? {}),
    };

    pageData.pricing = {
        ...pageData.pricing,
        ...(payload.pricing ?? {}),
    };

    homeHighlightsText.value = JSON.stringify(pageData.home.highlights ?? [], null, 2);
    featureLogosText.value = JSON.stringify(pageData.features.partner_logos ?? [], null, 2);
    pricingLogosText.value = JSON.stringify(pageData.pricing.partner_logos ?? [], null, 2);
    featureTestimonialsText.value = JSON.stringify(pageData.features.testimonials ?? [], null, 2);
    pricingTestimonialsText.value = JSON.stringify(pageData.pricing.testimonials ?? [], null, 2);
};

bootFromProps(props.content ?? {});

const parseJsonArray = (value, label) => {
    try {
        const parsed = JSON.parse(value);

        if (!Array.isArray(parsed)) {
            throw new Error(`${label} must be a JSON array.`);
        }

        return parsed;
    } catch (error) {
        throw new Error(error?.message ?? `${label} contains invalid JSON.`);
    }
};

const hydratePayload = () => {
    pageData.home.highlights = parseJsonArray(homeHighlightsText.value, 'Home highlights');
    pageData.features.partner_logos = parseJsonArray(featureLogosText.value, 'Feature partner logos');
    pageData.pricing.partner_logos = parseJsonArray(pricingLogosText.value, 'Pricing partner logos');
    pageData.features.testimonials = parseJsonArray(featureTestimonialsText.value, 'Feature testimonials');
    pageData.pricing.testimonials = parseJsonArray(pricingTestimonialsText.value, 'Pricing testimonials');
};

const saveDraft = () => {
    parseError.value = '';

    try {
        hydratePayload();
    } catch (error) {
        parseError.value = error.message;
        return;
    }

    pageData.post('/admin/content/draft', {
        preserveScroll: true,
    });
};

const publish = () => {
    parseError.value = '';

    try {
        hydratePayload();
    } catch (error) {
        parseError.value = error.message;
        return;
    }

    pageData.put('/admin/content', {
        preserveScroll: true,
    });
};

const rollback = (revisionId) => {
    if (!confirm('Publish this revision as current content?')) {
        return;
    }

    router.post(`/admin/content/rollback/${revisionId}`);
};

const onMediaFileChange = (event) => {
    mediaForm.file = event.target.files[0] ?? null;
};

const uploadMedia = () => {
    if (!mediaForm.file) {
        parseError.value = 'Please choose a media file before uploading.';
        return;
    }

    parseError.value = '';

    mediaForm.post('/admin/content/media', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => mediaForm.reset(),
    });
};

const removeMedia = (mediaId) => {
    if (!confirm('Delete this media file?')) {
        return;
    }

    router.delete(`/admin/content/media/${mediaId}`);
};

const copyText = async (value) => {
    try {
        await navigator.clipboard.writeText(value);
    } catch {
        parseError.value = 'Unable to copy URL automatically. Please copy manually.';
    }
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <Head title="Admin Content" />

    <main class="min-h-screen bg-[linear-gradient(180deg,_#020617_0%,_#0f172a_52%,_#111827_100%)] text-slate-100">
        <header class="border-b border-white/10 bg-slate-950/55 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4 lg:px-8">
                <div>
                    <p class="text-xs uppercase tracking-[0.22em] text-cyan-100">Platform Admin</p>
                    <h1 class="mt-1 text-xl font-semibold text-white">Marketing Content Manager</h1>
                </div>
                <div class="flex items-center gap-2">
                    <Link href="/" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20">
                        View Site
                    </Link>
                    <button type="button" class="rounded-full border border-white/20 bg-white/10 px-4 py-2 text-sm font-semibold text-white transition hover:bg-white/20" @click="logout">
                        Sign Out
                    </button>
                </div>
            </div>
        </header>

        <section class="mx-auto max-w-6xl px-6 py-8 lg:px-8">
            <div v-if="parseError" class="mb-4 rounded-2xl border border-rose-300/40 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
                {{ parseError }}
            </div>

            <div v-if="pageData.hasErrors" class="mb-4 rounded-2xl border border-rose-300/40 bg-rose-500/15 px-4 py-3 text-sm text-rose-100">
                Please review the form fields and try again.
            </div>

            <label class="mb-6 block text-sm">
                <span class="mb-1 block text-slate-300">Revision Note (optional)</span>
                <input v-model="pageData.note" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
            </label>

            <div class="grid gap-6">
                <article class="rounded-3xl border border-white/12 bg-slate-900/65 p-6">
                    <h2 class="text-lg font-semibold text-white">Home Page</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Hero Badge</span>
                            <input v-model="pageData.home.hero_badge" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Hero Title</span>
                            <input v-model="pageData.home.hero_title" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Hero Subtitle</span>
                            <textarea v-model="pageData.home.hero_subtitle" rows="3" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Primary CTA Label</span>
                            <input v-model="pageData.home.primary_cta_label" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Primary CTA URL</span>
                            <input v-model="pageData.home.primary_cta_url" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Secondary CTA Label</span>
                            <input v-model="pageData.home.secondary_cta_label" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Secondary CTA URL</span>
                            <input v-model="pageData.home.secondary_cta_url" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Highlights JSON (array of strings)</span>
                            <textarea v-model="homeHighlightsText" rows="6" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-200/50" />
                        </label>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/12 bg-slate-900/65 p-6">
                    <h2 class="text-lg font-semibold text-white">Features Page</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Hero Title</span>
                            <input v-model="pageData.features.hero_title" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Hero Subtitle</span>
                            <textarea v-model="pageData.features.hero_subtitle" rows="3" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Partner Logos JSON (array of strings)</span>
                            <textarea v-model="featureLogosText" rows="6" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Testimonials JSON</span>
                            <textarea v-model="featureTestimonialsText" rows="6" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-200/50" />
                        </label>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/12 bg-slate-900/65 p-6">
                    <h2 class="text-lg font-semibold text-white">Pricing Page</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Hero Title</span>
                            <input v-model="pageData.pricing.hero_title" type="text" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm md:col-span-2">
                            <span class="mb-1 block text-slate-300">Hero Subtitle</span>
                            <textarea v-model="pageData.pricing.hero_subtitle" rows="3" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Partner Logos JSON (array of strings)</span>
                            <textarea v-model="pricingLogosText" rows="6" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-200/50" />
                        </label>
                        <label class="text-sm">
                            <span class="mb-1 block text-slate-300">Testimonials JSON</span>
                            <textarea v-model="pricingTestimonialsText" rows="6" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 font-mono text-xs text-white outline-none focus:border-cyan-200/50" />
                        </label>
                    </div>
                </article>
            </div>

            <div class="mt-6 flex flex-col justify-end gap-3 sm:flex-row">
                <button type="button" class="rounded-full border border-white/20 bg-white/10 px-6 py-3 text-sm font-semibold text-white transition hover:bg-white/20 disabled:cursor-not-allowed disabled:opacity-60" :disabled="pageData.processing" @click="saveDraft">
                    {{ pageData.processing ? 'Saving...' : 'Save Draft' }}
                </button>
                <button type="button" class="rounded-full bg-cyan-300 px-6 py-3 text-sm font-bold text-slate-950 transition hover:bg-cyan-200 disabled:cursor-not-allowed disabled:opacity-60" :disabled="pageData.processing" @click="publish">
                    {{ pageData.processing ? 'Saving...' : 'Publish Content Updates' }}
                </button>
            </div>

            <section class="mt-8 grid gap-6 lg:grid-cols-2">
                <article class="rounded-3xl border border-white/12 bg-slate-900/65 p-6">
                    <h2 class="text-lg font-semibold text-white">Revision History</h2>
                    <div class="mt-4 space-y-2">
                        <article v-for="revision in revisions" :key="revision.id" class="flex items-center justify-between rounded-xl border border-white/12 bg-white/10 px-3 py-2">
                            <div>
                                <p class="text-sm font-semibold text-white">#{{ revision.id }} · {{ revision.status }}</p>
                                <p class="text-xs text-slate-300">{{ revision.note || 'No note' }}</p>
                            </div>
                            <button type="button" class="rounded-full border border-white/20 bg-white/10 px-3 py-1.5 text-xs font-semibold text-white transition hover:bg-white/20" @click="rollback(revision.id)">
                                Rollback
                            </button>
                        </article>
                        <p v-if="revisions.length === 0" class="text-sm text-slate-300">No revisions yet.</p>
                    </div>
                </article>

                <article class="rounded-3xl border border-white/12 bg-slate-900/65 p-6">
                    <h2 class="text-lg font-semibold text-white">Media Manager</h2>
                    <div class="mt-4 flex flex-col gap-3 sm:flex-row">
                        <input type="file" accept="image/*" class="w-full rounded-xl border border-white/15 bg-white/10 px-3 py-2 text-sm text-white" @change="onMediaFileChange" />
                        <button type="button" class="rounded-full bg-cyan-300 px-5 py-2 text-sm font-bold text-slate-950 transition hover:bg-cyan-200 disabled:cursor-not-allowed disabled:opacity-60" :disabled="mediaForm.processing" @click="uploadMedia">
                            Upload
                        </button>
                    </div>

                    <div class="mt-4 space-y-2">
                        <article v-for="item in media" :key="item.id" class="rounded-xl border border-white/12 bg-white/10 p-3">
                            <p class="truncate text-sm font-semibold text-white">{{ item.original_name }}</p>
                            <a :href="item.url" target="_blank" class="mt-1 block truncate text-xs text-cyan-200 hover:text-cyan-100">{{ item.url }}</a>
                            <div class="mt-2 flex gap-2">
                                <button type="button" class="rounded-full border border-white/20 bg-white/10 px-3 py-1 text-xs font-semibold text-white" @click="copyText(item.url)">
                                    Copy URL
                                </button>
                                <button type="button" class="rounded-full border border-rose-300/40 bg-rose-500/20 px-3 py-1 text-xs font-semibold text-rose-100" @click="removeMedia(item.id)">
                                    Delete
                                </button>
                            </div>
                        </article>
                        <p v-if="media.length === 0" class="text-sm text-slate-300">No media uploaded yet.</p>
                    </div>
                </article>
            </section>
        </section>
    </main>
</template>
