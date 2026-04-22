<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

defineProps({
    workspace: {
        type: Object,
        required: true,
    },
    packages: {
        type: Array,
        required: true,
    },
});

defineOptions({
    layout: WorkspaceLayout,
});

const formatDate = (date) => {
    if (!date) return '-';
    return new Date(date).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: '2-digit',
    });
};
</script>

<template>
    <div class="min-h-screen bg-slate-50">
        <div class="mx-auto max-w-6xl space-y-6 px-6 py-8">
            <div class="flex flex-col gap-2">
                <h1 class="text-3xl font-bold text-slate-900">Rooming List Management</h1>
                <p class="text-slate-600">Select a package to manage rooming list arrangements</p>
            </div>

            <div class="overflow-hidden rounded-lg border border-slate-200 bg-white shadow-sm">
                <table class="w-full">
                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Package ID</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Package Name</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Destination</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">Start Date</th>
                            <th class="px-6 py-3 text-left text-sm font-semibold text-slate-900">End Date</th>
                            <th class="px-6 py-3 text-right text-sm font-semibold text-slate-900">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200">
                        <tr v-for="pkg in packages" :key="pkg.id" class="transition hover:bg-slate-50">
                            <td class="px-6 py-4 text-sm text-slate-600">{{ pkg.id }}</td>
                            <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ pkg.name }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ pkg.destination }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ formatDate(pkg.start_date) }}</td>
                            <td class="px-6 py-4 text-sm text-slate-600">{{ formatDate(pkg.end_date) }}</td>
                            <td class="px-6 py-4 text-right">
                                <Link
                                    :href="`/workspace/${workspace.slug}/packages/${pkg.id}/rooming-list`"
                                    class="inline-flex items-center rounded-full bg-slate-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-slate-800"
                                >
                                    Manage Rooming List
                                </Link>
                            </td>
                        </tr>
                        <tr v-if="packages.length === 0">
                            <td colspan="6" class="px-6 py-8 text-center text-slate-500">
                                No packages found. Create a package to manage rooming lists.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>
