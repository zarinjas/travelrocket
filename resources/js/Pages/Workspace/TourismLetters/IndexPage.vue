<script setup>
import { Head, router, usePage } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    letters: {
        type: Object,
        required: true,
    },
    destinationOptions: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({ search: '', status: 'all', destination: '', departure_from: '', departure_to: '', action_needed: false }),
    },
});

const filterForm = reactive({
    search: props.filters?.search ?? '',
    status: props.filters?.status ?? 'all',
    destination: props.filters?.destination ?? '',
    departure_from: props.filters?.departure_from ?? '',
    departure_to: props.filters?.departure_to ?? '',
    action_needed: Boolean(props.filters?.action_needed),
});

const page = usePage();
const actionError = ref('');
const actionSuccess = ref('');
const emailProcessingId = ref(null);
const flashSuccess = computed(() => page.props.flash?.success ?? '');

const applyFilters = () => {
    router.get(`/workspace/${props.workspace.slug}/tourism-letters`, {
        search: filterForm.search,
        status: filterForm.status,
        destination: filterForm.destination,
        departure_from: filterForm.departure_from,
        departure_to: filterForm.departure_to,
        action_needed: filterForm.action_needed ? 1 : 0,
    }, {
        preserveState: true,
        replace: true,
    });
};

const resetFilters = () => {
    filterForm.search = '';
    filterForm.status = 'all';
    filterForm.destination = '';
    filterForm.departure_from = '';
    filterForm.departure_to = '';
    filterForm.action_needed = false;
    applyFilters();
};

const toggleActionNeeded = () => {
    filterForm.action_needed = !filterForm.action_needed;
    applyFilters();
};

const sendLetterEmail = (bookingId) => {
    actionError.value = '';
    actionSuccess.value = '';
    emailProcessingId.value = bookingId;

    router.post(`/workspace/${props.workspace.slug}/bookings/${bookingId}/tourism-letter/email`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            actionSuccess.value = 'Surat melancong berjaya dihantar ke email lead customer.';
        },
        onError: (errors) => {
            actionError.value = errors.tourism_letter
                || errors.company_profile
                || 'Gagal hantar email surat melancong. Sila semak data dan cuba lagi.';
        },
        onFinish: () => {
            emailProcessingId.value = null;
        },
    });
};

const bookingViewUrl = (bookingId) => `/workspace/${props.workspace.slug}/bookings/${bookingId}/tourism-letter`;
const bookingEditUrl = (bookingId) => `/workspace/${props.workspace.slug}/bookings/${bookingId}/tourism-letter/edit`;
const bookingDownloadTourismLetterUrl = (bookingId) => `/workspace/${props.workspace.slug}/bookings/${bookingId}/tourism-letter/download`;

const goPage = (url) => {
    if (!url) {
        return;
    }

    router.visit(url, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head :title="`${workspace.name} Surat Melancong`" />

    <WorkspaceLayout>
        <div class="w-full">
            <section class="tr-surface space-y-4 rounded-[1.7rem] border border-slate-200 p-6 md:p-8">
                <div class="flex flex-col gap-4 border-b border-slate-200 pb-6 md:flex-row md:items-end md:justify-between">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-[0.24em] text-blue-700">Documents</p>
                        <h2 class="mt-2 text-2xl font-bold tracking-tight text-slate-900">Surat Melancong</h2>
                        <p class="mt-2 text-sm text-slate-700">Senarai surat rasmi untuk majikan, dengan tindakan pantas download dan email.</p>
                    </div>
                    <a
                        :href="`/workspace/${workspace.slug}/bookings/create`"
                        class="tr-btn-primary rounded-xl px-5 py-2.5 text-sm font-semibold transition"
                    >
                        Create New Booking
                    </a>
                </div>

                <div v-if="flashSuccess || actionSuccess || actionError" class="space-y-2">
                    <div v-if="flashSuccess || actionSuccess" class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">
                        {{ actionSuccess || flashSuccess }}
                    </div>
                    <div v-if="actionError" class="rounded-xl border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">
                        {{ actionError }}
                    </div>
                </div>

                <form class="grid gap-3 rounded-2xl border border-slate-200 bg-white/90 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.7)] p-4 md:grid-cols-6" @submit.prevent="applyFilters">
                    <input
                        v-model="filterForm.search"
                        type="text"
                        placeholder="Cari nama peserta, passport, no booking, destinasi, flight"
                        class="w-full rounded-xl border border-white/10 bg-[#0b1626] px-4 py-2.5 text-sm text-slate-100 outline-none transition focus:border-white/20 md:col-span-2"
                    />

                    <select
                        v-model="filterForm.status"
                        class="w-full rounded-xl border border-white/10 bg-[#0b1626] px-4 py-2.5 text-sm text-slate-100 outline-none transition focus:border-white/20"
                    >
                        <option value="all">Semua</option>
                        <option value="ready">Ready</option>
                        <option value="incomplete">Incomplete</option>
                    </select>

                    <select
                        v-model="filterForm.destination"
                        class="w-full rounded-xl border border-white/10 bg-[#0b1626] px-4 py-2.5 text-sm text-slate-100 outline-none transition focus:border-white/20"
                    >
                        <option value="">Semua Destinasi</option>
                        <option v-for="option in destinationOptions" :key="option" :value="option">{{ option }}</option>
                    </select>

                    <input
                        v-model="filterForm.departure_from"
                        type="date"
                        title="Departure from"
                        class="w-full rounded-xl border border-white/10 bg-[#0b1626] px-4 py-2.5 text-sm text-slate-100 outline-none transition focus:border-white/20"
                    />

                    <input
                        v-model="filterForm.departure_to"
                        type="date"
                        title="Departure to"
                        class="w-full rounded-xl border border-white/10 bg-[#0b1626] px-4 py-2.5 text-sm text-slate-100 outline-none transition focus:border-white/20"
                    />

                    <div class="flex flex-wrap gap-2 md:col-span-6">
                        <button
                            type="button"
                            class="rounded-xl border px-4 py-2.5 text-sm font-semibold transition"
                            :class="filterForm.action_needed ? 'border-amber-200 bg-amber-50 text-amber-700' : 'border-slate-200 bg-white text-slate-700 hover:border-slate-300 hover:bg-slate-50'"
                            @click="toggleActionNeeded"
                        >
                            Perlu Tindakan Segera
                        </button>
                        <button type="submit" class="rounded-xl bg-brand-500 px-4 py-2.5 text-sm font-semibold text-white transition hover:bg-brand-300">
                            Filter
                        </button>
                        <button type="button" class="rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50" @click="resetFilters">
                            Reset
                        </button>
                    </div>
                </form>

                <div class="overflow-hidden rounded-2xl border border-white/10">
                    <div class="grid grid-cols-[1.4fr_1fr_1fr_1fr_1fr_220px] bg-[#081423] px-4 py-3 text-[11px] font-semibold uppercase tracking-[0.16em] text-slate-400">
                        <p>Peserta / Passport</p>
                        <p>Perjalanan</p>
                        <p>Penerbangan</p>
                        <p>Destinasi</p>
                        <p>Status</p>
                        <p class="text-right">Tindakan</p>
                    </div>

                    <div v-if="letters.data?.length" class="divide-y divide-white/10 bg-[#0b1626]">
                        <div
                            v-for="letter in letters.data"
                            :key="letter.id"
                            class="grid grid-cols-[1.4fr_1fr_1fr_1fr_1fr_220px] items-center gap-3 px-4 py-4 text-sm"
                        >
                            <div>
                                <p class="font-semibold text-white">{{ letter.passenger_name || '-' }}</p>
                                <p class="mt-1 text-xs text-slate-400">{{ letter.passport_number || '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ letter.booking_number || `BK-${letter.id}` }}</p>
                            </div>

                            <div class="text-xs text-slate-300">
                                <p>{{ letter.departure_date || '-' }}</p>
                                <p class="mt-1 text-slate-500">to {{ letter.return_date || '-' }}</p>
                            </div>

                            <div class="text-xs text-slate-300">
                                <p>{{ letter.flight_name || '-' }}</p>
                                <p class="mt-1 text-slate-500">{{ letter.flight_number || '-' }}</p>
                            </div>

                            <div>
                                <p class="text-xs text-slate-300">{{ letter.destination || '-' }}</p>
                                <p class="mt-1 text-xs text-slate-500">{{ letter.package_name || '-' }}</p>
                            </div>

                            <div>
                                <span
                                    class="inline-flex rounded-full border px-2.5 py-1 text-[11px] font-semibold uppercase tracking-[0.12em]"
                                    :class="letter.letter_ready ? 'border-emerald-200 bg-emerald-50 text-emerald-700' : 'border-amber-200 bg-amber-50 text-amber-700'"
                                >
                                    {{ letter.letter_ready ? 'Ready' : 'Incomplete' }}
                                </span>
                            </div>

                            <div class="flex items-center justify-end gap-2">
                                <a
                                    :href="bookingDownloadTourismLetterUrl(letter.id)"
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-all duration-200 ease-in-out hover:border-slate-300 hover:bg-slate-50"
                                >
                                    Download
                                </a>
                                <button
                                    type="button"
                                    class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition-all duration-200 ease-in-out hover:border-slate-300 hover:bg-slate-50"
                                    :disabled="emailProcessingId === letter.id"
                                    @click="sendLetterEmail(letter.id)"
                                >
                                    {{ emailProcessingId === letter.id ? 'Sending...' : 'Email' }}
                                </button>
                                <a
                                    :href="bookingViewUrl(letter.id)"
                                    target="_blank"
                                    rel="noopener"
                                    class="rounded-full border border-slate-200 bg-white px-3 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-slate-300 hover:bg-slate-50"
                                >
                                    View
                                </a>
                                <a
                                    :href="bookingEditUrl(letter.id)"
                                    class="rounded-full border border-amber-200 bg-amber-50 px-3 py-1.5 text-xs font-semibold text-amber-700 transition hover:border-amber-300 hover:bg-amber-100"
                                >
                                    Edit
                                </a>
                            </div>
                        </div>
                    </div>

                    <div v-else class="bg-[#0b1626] px-4 py-10 text-center text-sm text-slate-400">
                        Tiada rekod dijumpai untuk filter semasa.
                    </div>
                </div>

                <div v-if="letters.links?.length" class="flex flex-wrap items-center justify-between gap-3 pt-2">
                    <p class="text-xs text-slate-500">
                        Paparan {{ letters.from || 0 }} - {{ letters.to || 0 }} daripada {{ letters.total || 0 }} rekod
                    </p>
                    <div class="flex flex-wrap gap-1">
                        <button
                            v-for="(link, idx) in letters.links"
                            :key="`page-${idx}`"
                            type="button"
                            class="rounded-lg border px-3 py-1.5 text-xs font-semibold transition"
                            :class="link.active ? 'border-slate-900 bg-slate-900 text-white' : 'border-slate-200 bg-white text-slate-600 hover:border-slate-300 hover:bg-slate-50'"
                            :disabled="!link.url"
                            @click="goPage(link.url)"
                        >
                            <span v-html="link.label" />
                        </button>
                    </div>
                </div>
            </section>
        </div>
    </WorkspaceLayout>
</template>
