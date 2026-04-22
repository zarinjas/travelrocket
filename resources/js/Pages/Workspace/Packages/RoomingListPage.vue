<script setup>
import { ref, computed } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace:  { type: Object, required: true },
    package:    { type: Object, required: true },
    passengers: { type: Array,  default: () => [] },
    savedRooms: { type: Array,  default: () => [] },
});

// ─── Constants ──────────────────────────────────────────────────────────────

const CAPACITY = { single: 1, twin: 2, double: 2, triple: 3, quad: 4 };

const ROOM_STYLE = {
    single: { border: 'border-violet-200', bg: 'bg-violet-50',  badge: 'bg-violet-100 text-violet-700',  bar: 'bg-violet-400' },
    twin:   { border: 'border-blue-200',   bg: 'bg-blue-50',    badge: 'bg-blue-100 text-blue-700',      bar: 'bg-blue-400'   },
    double: { border: 'border-emerald-200',bg: 'bg-emerald-50', badge: 'bg-emerald-100 text-emerald-700',bar: 'bg-emerald-400'},
    triple: { border: 'border-amber-200',  bg: 'bg-amber-50',   badge: 'bg-amber-100 text-amber-700',    bar: 'bg-amber-400'  },
    quad:   { border: 'border-rose-200',   bg: 'bg-rose-50',    badge: 'bg-rose-100 text-rose-700',      bar: 'bg-rose-400'   },
};

const TYPE_LABELS = { single: 'Single', twin: 'Twin', double: 'Double', triple: 'Triple', quad: 'Quad' };

// ─── State ───────────────────────────────────────────────────────────────────

let uid = Date.now();

const rooms = ref(
    props.savedRooms.length
        ? props.savedRooms.map(r => ({ ...r, passengers: [...(r.passengers ?? [])] }))
        : []
);

let roomCounter = ref(rooms.value.length);

// ─── Room helpers ────────────────────────────────────────────────────────────

const addRoom = (type) => {
    roomCounter.value++;
    rooms.value.push({
        id:         `r-${uid++}`,
        type,
        label:      `Bilik ${roomCounter.value}`,
        passengers: [],
    });
};

const removeRoom = (id) => {
    rooms.value = rooms.value.filter(r => r.id !== id);
};

const isFull = (room) => room.passengers.length >= CAPACITY[room.type];

const capacityPct = (room) =>
    Math.min(100, Math.round((room.passengers.length / CAPACITY[room.type]) * 100));

// ─── Passenger helpers ───────────────────────────────────────────────────────

const assignedIds = computed(() => {
    const s = new Set();
    rooms.value.forEach(r => r.passengers.forEach(id => s.add(id)));
    return s;
});

const unassigned = computed(() =>
    props.passengers.filter(p => !assignedIds.value.has(p.id))
);

const roomOfPassenger = (pid) =>
    rooms.value.find(r => r.passengers.includes(pid)) ?? null;

const getPassenger = (pid) =>
    props.passengers.find(p => p.id === pid);

const assignToRoom = (room, pid) => {
    if (isFull(room)) return;
    // Remove from current room if any
    rooms.value.forEach(r => {
        r.passengers = r.passengers.filter(id => id !== pid);
    });
    room.passengers.push(pid);
};

const removeFromRoom = (room, pid) => {
    room.passengers = room.passengers.filter(id => id !== pid);
};

const onSelectPassenger = (room, event) => {
    const pid = parseInt(event.target.value);
    if (!pid) return;
    assignToRoom(room, pid);
    event.target.value = '';
};

// ─── Stats ────────────────────────────────────────────────────────────────────

const stats = computed(() => ({
    total:      props.passengers.length,
    rooms:      rooms.value.length,
    assigned:   assignedIds.value.size,
    unassigned: props.passengers.length - assignedIds.value.size,
}));

// ─── Save ─────────────────────────────────────────────────────────────────────

const saving  = ref(false);
const generating = ref(false);
const flash   = ref('');

const save = () => {
    saving.value = true;
    router.post(
        `/workspace/${props.workspace.slug}/packages/${props.package.id}/rooming-list/save`,
        { rooms: rooms.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                flash.value = 'Rooming list berjaya disimpan!';
                setTimeout(() => (flash.value = ''), 3500);
            },
            onFinish: () => { saving.value = false; },
        }
    );
};

const generatePdf = () => {
    generating.value = true;
    window.location.href = `/workspace/${props.workspace.slug}/packages/${props.package.id}/rooming-list/pdf`;
    setTimeout(() => { generating.value = false; }, 2000);
};

// ─── Inline label edit ───────────────────────────────────────────────────────

const editingLabel = ref(null);
const startEditLabel = (room) => { editingLabel.value = room.id; };
const stopEditLabel  = ()     => { editingLabel.value = null; };

// ─── Format date ─────────────────────────────────────────────────────────────

const formatDate = (d) => {
    if (!d) return '–';
    const [y, m, day] = d.split('-');
    return `${day}/${m}/${y}`;
};
</script>

<template>
    <Head :title="`Rooming List – ${package.name}`" />

    <WorkspaceLayout>
        <div class="w-full py-6 flex flex-col gap-4" style="height: calc(100vh - 72px);">

            <!-- ── Header ──────────────────────────────────────────────────── -->
            <div class="flex flex-wrap items-start justify-between gap-3 flex-shrink-0">
                <div>
                    <a
                        :href="`/workspace/${workspace.slug}/packages/${package.id}`"
                        class="inline-flex items-center gap-1 text-sm text-gray-500 hover:text-gray-800 mb-1"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path fill-rule="evenodd" d="M17 10a.75.75 0 01-.75.75H5.612l4.158 3.96a.75.75 0 11-1.04 1.08l-5.5-5.25a.75.75 0 010-1.08l5.5-5.25a.75.75 0 111.04 1.08L5.612 9.25H16.25A.75.75 0 0117 10z" clip-rule="evenodd" />
                        </svg>
                        Kembali ke Pakej
                    </a>
                    <h1 class="text-xl font-bold text-gray-900 tracking-tight">Rooming List Builder</h1>
                    <p class="text-sm text-gray-500 mt-0.5">
                        {{ package.name }}
                        <span v-if="package.destination"> · {{ package.destination }}</span>
                        <span v-if="package.start_date"> · {{ formatDate(package.start_date) }}</span>
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <transition
                        enter-active-class="transition ease-out duration-200"
                        enter-from-class="opacity-0 translate-y-1"
                        enter-to-class="opacity-100 translate-y-0"
                        leave-active-class="transition ease-in duration-150"
                        leave-from-class="opacity-100"
                        leave-to-class="opacity-0"
                    >
                        <span v-if="flash" class="text-sm font-medium text-emerald-600 flex items-center gap-1">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.857-9.809a.75.75 0 00-1.214-.882l-3.483 4.79-1.88-1.88a.75.75 0 10-1.06 1.061l2.5 2.5a.75.75 0 001.137-.089l4-5.5z" clip-rule="evenodd" />
                            </svg>
                            {{ flash }}
                        </span>
                    </transition>

                    <button
                        @click="generatePdf"
                        :disabled="generating"
                        class="inline-flex items-center gap-2 rounded-lg bg-slate-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-slate-700 disabled:opacity-60"
                        title="Generate Tour Confirmation PDF"
                    >
                        <svg v-if="generating" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path d="M5.5 13a3.5 3.5 0 01-.369-6.98 4 4 0 117.753-1.3A4.5 4.5 0 1113.5 13H11V9.413l1.293 1.293a1 1 0 001.414-1.414l-3-3a1 1 0 00-1.414 0l-3 3a1 1 0 001.414 1.414L9 9.414V13H5.5z" />
                        </svg>
                        {{ generating ? 'Menjana...' : 'Generate PDF' }}
                    </button>

                    <button
                        @click="save"
                        :disabled="saving"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-indigo-700 disabled:opacity-60"
                    >
                        <svg v-if="saving" class="animate-spin h-4 w-4" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                            <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                            <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8z"></path>
                        </svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" class="h-4 w-4">
                            <path d="M3 3.5A1.5 1.5 0 014.5 2h6.879a1.5 1.5 0 011.06.44l4.122 4.12A1.5 1.5 0 0117 7.622V16.5a1.5 1.5 0 01-1.5 1.5h-11A1.5 1.5 0 013 16.5v-13zM13.25 9a.75.75 0 00-.75.75v4.5c0 .414.336.75.75.75h.5a.75.75 0 00.75-.75v-4.5a.75.75 0 00-.75-.75h-.5zM6 12.25a.75.75 0 01.75-.75h2.5a.75.75 0 010 1.5h-2.5a.75.75 0 01-.75-.75z" />
                        </svg>
                        {{ saving ? 'Menyimpan...' : 'Simpan Rooming List' }}
                    </button>
                </div>
            </div>

            <!-- ── Stats bar ───────────────────────────────────────────────── -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 flex-shrink-0">
                <div class="rounded-xl bg-white border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500 font-medium">Jumlah Peserta</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.total }}</p>
                </div>
                <div class="rounded-xl bg-white border border-gray-200 px-4 py-3">
                    <p class="text-xs text-gray-500 font-medium">Jumlah Bilik</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ stats.rooms }}</p>
                </div>
                <div class="rounded-xl bg-white border border-emerald-200 px-4 py-3">
                    <p class="text-xs text-emerald-600 font-medium">Telah Ditempatkan</p>
                    <p class="text-2xl font-bold text-emerald-700 mt-1">{{ stats.assigned }}</p>
                </div>
                <div class="rounded-xl bg-white border border-amber-200 px-4 py-3">
                    <p class="text-xs text-amber-600 font-medium">Belum Ditempatkan</p>
                    <p class="text-2xl font-bold text-amber-700 mt-1">{{ stats.unassigned }}</p>
                </div>
            </div>

            <!-- ── Add Room toolbar ────────────────────────────────────────── -->
            <div class="flex flex-wrap items-center gap-2 flex-shrink-0">
                <span class="text-sm font-semibold text-gray-600 mr-1">Tambah Bilik:</span>
                <button
                    v-for="type in ['single', 'twin', 'double', 'triple', 'quad']"
                    :key="type"
                    @click="addRoom(type)"
                    :class="['inline-flex items-center gap-1.5 rounded-lg border px-3 py-1.5 text-xs font-semibold transition', ROOM_STYLE[type].border, ROOM_STYLE[type].bg, 'hover:opacity-80']"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-3.5 w-3.5">
                        <path d="M8.75 3.75a.75.75 0 00-1.5 0v3.5h-3.5a.75.75 0 000 1.5h3.5v3.5a.75.75 0 001.5 0v-3.5h3.5a.75.75 0 000-1.5h-3.5v-3.5z" />
                    </svg>
                    {{ TYPE_LABELS[type] }}
                </button>
            </div>

            <!-- ── Main 2-column body ──────────────────────────────────────── -->
            <div class="flex gap-4 flex-1 overflow-hidden min-h-0">

                <!-- Left: Manifest table -->
                <div class="w-[38%] flex flex-col bg-white rounded-xl border border-gray-200 overflow-hidden flex-shrink-0">
                    <div class="flex items-center justify-between px-4 py-3 bg-gray-50 border-b border-gray-200 flex-shrink-0">
                        <span class="text-sm font-semibold text-gray-700">Manifest Peserta</span>
                        <span class="rounded-full bg-gray-200 px-2 py-0.5 text-xs font-medium text-gray-600">{{ passengers.length }}</span>
                    </div>

                    <div v-if="!passengers.length" class="flex-1 flex flex-col items-center justify-center text-gray-400 gap-2 p-6">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-10 w-10 text-gray-200">
                            <path fill-rule="evenodd" d="M8.25 6.75a3.75 3.75 0 117.5 0 3.75 3.75 0 01-7.5 0zM15.75 9.75a3 3 0 116 0 3 3 0 01-6 0zM2.25 9.75a3 3 0 116 0 3 3 0 01-6 0zM6.31 15.117A6.745 6.745 0 0112 12a6.745 6.745 0 016.709 7.498.75.75 0 01-.372.568A12.696 12.696 0 0112 21.75c-2.305 0-4.47-.612-6.337-1.684a.75.75 0 01-.372-.568 6.787 6.787 0 011.019-4.38z" clip-rule="evenodd" />
                        </svg>
                        <p class="text-sm">Tiada peserta dengan bayaran selesai.</p>
                    </div>

                    <div v-else class="overflow-y-auto flex-1">
                        <table class="w-full text-xs">
                            <thead class="sticky top-0 bg-gray-50 z-10">
                                <tr class="border-b border-gray-200">
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500 w-8">#</th>
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500">Nama</th>
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500">Pasport</th>
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500">T/Lahir</th>
                                    <th class="px-3 py-2.5 text-left font-semibold text-gray-500">Bilik</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                <tr
                                    v-for="(p, i) in passengers"
                                    :key="p.id"
                                    :class="[
                                        'transition-colors',
                                        assignedIds.has(p.id) ? 'bg-white hover:bg-gray-50' : 'bg-amber-50 hover:bg-amber-100'
                                    ]"
                                >
                                    <td class="px-3 py-2 text-gray-400 tabular-nums">{{ i + 1 }}</td>
                                    <td class="px-3 py-2 font-medium text-gray-800 max-w-[100px] truncate">{{ p.name || '–' }}</td>
                                    <td class="px-3 py-2 font-mono text-gray-600">{{ p.passport_number || '–' }}</td>
                                    <td class="px-3 py-2 text-gray-500 whitespace-nowrap">{{ formatDate(p.date_of_birth) }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            v-if="roomOfPassenger(p.id)"
                                            :class="['inline-block rounded-full px-2 py-0.5 text-[10px] font-semibold', ROOM_STYLE[roomOfPassenger(p.id).type].badge]"
                                        >
                                            {{ roomOfPassenger(p.id).label }}
                                        </span>
                                        <span v-else class="text-amber-500 font-medium text-[10px]">Belum</span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Right: Room grid -->
                <div class="flex-1 overflow-y-auto min-h-0">

                    <div v-if="!rooms.length" class="h-full flex flex-col items-center justify-center text-gray-400 gap-3">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" class="h-12 w-12 text-gray-200">
                            <path d="M19.006 3.705a.75.75 0 00-.512-1.41L6 6.838V3a.75.75 0 00-1.5 0v4.93l-1.006.365a.75.75 0 00.512 1.41l15-5.453zM6 11.055V13.5a1 1 0 001 1h9a1 1 0 001-1V9.978L6 11.055zM3 15.051V18a1 1 0 001 1h16a1 1 0 001-1v-5.507L3 15.051z" />
                        </svg>
                        <p class="text-sm font-medium">Belum ada bilik.</p>
                        <p class="text-xs text-gray-400">Klik butang "Tambah Bilik" di atas untuk mula.</p>
                    </div>

                    <div v-else class="grid grid-cols-2 xl:grid-cols-3 gap-3 pb-4">
                        <div
                            v-for="room in rooms"
                            :key="room.id"
                            :class="['rounded-xl border-2 p-4 flex flex-col gap-3 transition-shadow hover:shadow-sm', ROOM_STYLE[room.type].border, ROOM_STYLE[room.type].bg]"
                        >
                            <!-- Room header -->
                            <div class="flex items-start justify-between gap-2">
                                <div class="flex items-center gap-2 flex-1 min-w-0">
                                    <span :class="['text-[10px] font-bold uppercase tracking-wider px-2 py-0.5 rounded-full flex-shrink-0', ROOM_STYLE[room.type].badge]">
                                        {{ TYPE_LABELS[room.type] }}
                                    </span>
                                    <input
                                        v-if="editingLabel === room.id"
                                        v-model="room.label"
                                        @blur="stopEditLabel"
                                        @keyup.enter="stopEditLabel"
                                        class="flex-1 min-w-0 rounded border border-gray-300 bg-white px-2 py-0.5 text-sm font-semibold text-gray-800 focus:outline-none focus:ring-1 focus:ring-indigo-400"
                                        autofocus
                                    />
                                    <button
                                        v-else
                                        @click="startEditLabel(room)"
                                        class="flex-1 min-w-0 text-left text-sm font-semibold text-gray-800 hover:text-indigo-600 truncate transition-colors"
                                        title="Klik untuk edit nama bilik"
                                    >
                                        {{ room.label }}
                                    </button>
                                </div>
                                <button
                                    @click="removeRoom(room.id)"
                                    class="flex-shrink-0 text-gray-400 hover:text-red-500 transition-colors p-0.5 rounded"
                                    title="Padam bilik"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4">
                                        <path d="M5.28 4.22a.75.75 0 00-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 101.06 1.06L8 9.06l2.72 2.72a.75.75 0 101.06-1.06L9.06 8l2.72-2.72a.75.75 0 00-1.06-1.06L8 6.94 5.28 4.22z" />
                                    </svg>
                                </button>
                            </div>

                            <!-- Capacity bar -->
                            <div>
                                <div class="flex justify-between text-[11px] text-gray-500 mb-1">
                                    <span class="font-medium">{{ room.passengers.length }} / {{ CAPACITY[room.type] }} peserta</span>
                                    <span v-if="isFull(room)" class="font-semibold text-red-500">Penuh</span>
                                </div>
                                <div class="h-1.5 rounded-full bg-black/10 overflow-hidden">
                                    <div
                                        :class="['h-full rounded-full transition-all duration-300', isFull(room) ? 'bg-red-400' : ROOM_STYLE[room.type].bar]"
                                        :style="`width: ${capacityPct(room)}%`"
                                    ></div>
                                </div>
                            </div>

                            <!-- Assigned passengers -->
                            <div class="flex flex-col gap-1.5 min-h-[40px]">
                                <div
                                    v-for="pid in room.passengers"
                                    :key="pid"
                                    class="flex items-center justify-between rounded-lg bg-white/80 px-3 py-2 shadow-sm"
                                >
                                    <div class="min-w-0 flex-1">
                                        <p class="text-xs font-semibold text-gray-800 truncate">{{ getPassenger(pid)?.name || `ID ${pid}` }}</p>
                                        <p class="text-[10px] font-mono text-gray-400 truncate">{{ getPassenger(pid)?.passport_number || '–' }}</p>
                                    </div>
                                    <button
                                        @click="removeFromRoom(room, pid)"
                                        class="ml-2 flex-shrink-0 text-gray-300 hover:text-red-400 transition-colors"
                                        title="Keluarkan dari bilik"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 16 16" fill="currentColor" class="h-4 w-4">
                                            <path d="M5.28 4.22a.75.75 0 00-1.06 1.06L6.94 8l-2.72 2.72a.75.75 0 101.06 1.06L8 9.06l2.72 2.72a.75.75 0 101.06-1.06L9.06 8l2.72-2.72a.75.75 0 00-1.06-1.06L8 6.94 5.28 4.22z" />
                                        </svg>
                                    </button>
                                </div>

                                <div
                                    v-if="!room.passengers.length"
                                    class="flex items-center justify-center py-3 text-[11px] text-gray-400 border border-dashed border-gray-200 rounded-lg"
                                >
                                    Tiada peserta
                                </div>
                            </div>

                            <!-- Add passenger dropdown -->
                            <div v-if="!isFull(room)">
                                <select
                                    @change="onSelectPassenger(room, $event)"
                                    class="w-full rounded-lg border border-gray-200 bg-white/80 px-3 py-2 text-xs text-gray-700 focus:border-indigo-400 focus:outline-none focus:ring-1 focus:ring-indigo-300 cursor-pointer"
                                >
                                    <option value="">
                                        + Tambah peserta
                                        <template v-if="unassigned.length"> ({{ unassigned.length }} tersedia)</template>
                                        <template v-else>...</template>
                                    </option>
                                    <option
                                        v-for="p in unassigned"
                                        :key="p.id"
                                        :value="p.id"
                                    >
                                        {{ p.name }} {{ p.passport_number ? `(${p.passport_number})` : '' }}
                                    </option>
                                </select>
                                <p v-if="!unassigned.length" class="mt-1 text-center text-[10px] text-gray-400">
                                    Semua peserta telah ditempatkan
                                </p>
                            </div>
                            <div v-else class="rounded-lg bg-red-50 border border-red-200 py-1.5 text-center text-[11px] font-semibold text-red-500">
                                Bilik penuh — kapasiti {{ CAPACITY[room.type] }} peserta
                            </div>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </WorkspaceLayout>
</template>
