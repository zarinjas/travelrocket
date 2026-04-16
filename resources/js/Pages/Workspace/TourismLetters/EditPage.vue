<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    letter: {
        type: Object,
        required: true,
    },
    formAction: {
        type: String,
        required: true,
    },
});

const form = useForm({
    departure_date: props.letter?.departure_date ?? '',
    return_date: props.letter?.return_date ?? '',
    flight_name: props.letter?.flight_name ?? '',
    flight_number: props.letter?.flight_number ?? '',
});

const submit = () => {
    form.put(props.formAction);
};
</script>

<template>
    <Head :title="`${workspace.name} Edit Surat Melancong`" />

    <WorkspaceLayout>
        <main class="mx-auto max-w-4xl py-8 text-slate-100">
            <section class="rounded-[1.7rem] border border-white/10 bg-[#0b1626] p-8 shadow-[0_24px_80px_rgba(0,0,0,0.25)]">
                <div class="border-b border-white/10 pb-6">
                    <p class="text-sm font-semibold uppercase tracking-[0.25em] text-brand-300">Documents</p>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-white">Edit Surat Melancong</h2>
                    <p class="mt-2 text-slate-400">Kemaskini maklumat perjalanan untuk surat rasmi.</p>
                </div>

                <div class="mt-6 grid gap-4 rounded-2xl border border-slate-200 bg-white/90 shadow-[0_20px_48px_-36px_rgba(15,23,42,0.7)] p-4 text-sm text-slate-600 md:grid-cols-2">
                    <p>Booking #: <span class="text-white">{{ letter.booking_number || `BK-${letter.id}` }}</span></p>
                    <p>Lead Email: <span class="text-white">{{ letter.lead_email || '-' }}</span></p>
                    <p>Peserta: <span class="text-white">{{ letter.passenger_name || '-' }}</span></p>
                    <p>No Passport: <span class="text-white">{{ letter.passport_number || '-' }}</span></p>
                    <p class="md:col-span-2">Destinasi: <span class="text-white">{{ letter.destination || '-' }}</span></p>
                </div>

                <form class="mt-6 grid gap-4" @submit.prevent="submit">
                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Tarikh Berlepas</label>
                            <input v-model="form.departure_date" type="date" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition focus:border-white/20" />
                            <p v-if="form.errors.departure_date" class="mt-2 text-sm text-rose-300">{{ form.errors.departure_date }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Tarikh Pulang</label>
                            <input v-model="form.return_date" type="date" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition focus:border-white/20" />
                            <p v-if="form.errors.return_date" class="mt-2 text-sm text-rose-300">{{ form.errors.return_date }}</p>
                        </div>
                    </div>

                    <div class="grid gap-4 md:grid-cols-2">
                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">Penerbangan</label>
                            <input v-model="form.flight_name" type="text" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition focus:border-white/20" placeholder="Contoh: Qatar Airways" />
                            <p v-if="form.errors.flight_name" class="mt-2 text-sm text-rose-300">{{ form.errors.flight_name }}</p>
                        </div>

                        <div>
                            <label class="mb-2 block text-sm font-medium text-slate-200">No Flight</label>
                            <input v-model="form.flight_number" type="text" class="w-full rounded-2xl border border-slate-200 bg-white/90 px-4 py-3 text-slate-700 outline-none transition focus:border-white/20" placeholder="Contoh: QR852" />
                            <p v-if="form.errors.flight_number" class="mt-2 text-sm text-rose-300">{{ form.errors.flight_number }}</p>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-3 pt-2">
                        <button type="submit" class="rounded-full bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition hover:bg-brand-300" :disabled="form.processing">
                            Save Surat Details
                        </button>
                        <a :href="`/workspace/${workspace.slug}/tourism-letters`" class="rounded-full border border-slate-200 bg-white/90 px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-white/20 hover:bg-white/10">
                            Back To Surat Melancong
                        </a>
                    </div>
                </form>
            </section>
        </main>
    </WorkspaceLayout>
</template>
