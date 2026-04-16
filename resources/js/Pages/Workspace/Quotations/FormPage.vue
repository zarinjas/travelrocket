<script setup>
import { ref, computed, watch } from 'vue';
import { Head, useForm } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    customers: Array,
    packages: Array,
    nextId: String,
});

const form = useForm({
    customer_id: null,
    manual_customer_data: {
        name: '',
        address: '',
        phone: '',
        email: '',
    },
    subject: '',
    items: [
        { package_id: null, description: '', qty: 1, rate: 0, amount: 0 }
    ],
    sub_total: 0,
    total: 0,
    notes: '',
    terms: '',
    expiry_date: new Date(Date.now() + 7 * 24 * 60 * 60 * 1000).toISOString().split('T')[0],
});

const isManualCustomer = ref(false);

const updateItemAmount = (index) => {
    const item = form.items[index];
    item.amount = (item.qty || 0) * (item.rate || 0);
    calculateTotals();
};

const calculateTotals = () => {
    form.sub_total = form.items.reduce((sum, item) => sum + (item.amount || 0), 0);
    form.total = form.sub_total; // Simplify for now, can add tax/discount later
};

const addRow = () => {
    form.items.push({ package_id: null, description: '', qty: 1, rate: 0, amount: 0 });
};

const removeRow = (index) => {
    if (form.items.length > 1) {
        form.items.splice(index, 1);
        calculateTotals();
    }
};

const onPackageSelect = (index) => {
    const item = form.items[index];
    const pkg = props.packages.find(p => p.id === item.package_id);
    if (pkg) {
        item.description = pkg.name;
        item.rate = pkg.price;
        updateItemAmount(index);
    }
};

const submit = () => {
    form.post(route('quotations.store', { tenant: props.workspace.slug }));
};
</script>

<template>
    <Head title="Create Quotation" />

    <WorkspaceLayout>
        <div class="mx-auto max-w-6xl bg-white p-8 shadow-sm rounded-xl border border-gray-100">
            <!-- Zoho Style Header -->
            <div class="flex justify-between items-start border-b pb-8 mb-8">
                <div class="space-y-1">
                    <div class="h-12 w-12 bg-brand-500 rounded-lg flex items-center justify-center text-white font-bold mb-4">TR</div>
                    <h2 class="text-xl font-bold text-gray-900">{{ workspace.name }}</h2>
                    <p class="text-sm text-gray-500">Kuala Lumpur, Malaysia</p>
                </div>
                <div class="text-right">
                    <h1 class="text-4xl font-light text-gray-400 uppercase tracking-widest">Quotation</h1>
                    <p class="text-lg font-semibold text-gray-700 mt-2"># {{ nextId }}</p>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Info Section -->
                <div class="grid grid-cols-2 gap-12 mb-10">
                    <div class="space-y-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Bill To</label>
                        
                        <div v-if="!isManualCustomer" class="space-y-3">
                            <select v-model="form.customer_id" class="w-full rounded-lg border-gray-200 text-sm focus:ring-brand-500 focus:border-brand-500">
                                <option :value="null">Select a customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <button type="button" @click="isManualCustomer = true" class="text-xs text-brand-600 hover:underline">+ Or enter details manually</button>
                        </div>

                        <div v-else class="space-y-3">
                            <input v-model="form.manual_customer_data.name" placeholder="Customer Name" class="w-full rounded-lg border-gray-200 text-sm" />
                            <textarea v-model="form.manual_customer_data.address" placeholder="Address" rows="2" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                            <button type="button" @click="isManualCustomer = false" class="text-xs text-gray-400 hover:underline">← Back to selection</button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Quote Date</span>
                            <span class="font-medium">{{ new Date().toLocaleDateString() }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Expiry Date</span>
                            <input type="date" v-model="form.expiry_date" class="border-0 p-0 text-right font-medium focus:ring-0 w-32" />
                        </div>
                        <div class="pt-4 border-t border-gray-50">
                            <label class="block text-xs font-bold text-gray-400 mb-1 uppercase tracking-wider">Subject</label>
                            <input v-model="form.subject" placeholder="e.g. Umrah Package November" class="w-full border-0 border-b border-gray-100 focus:border-brand-500 focus:ring-0 p-0 text-sm font-medium" />
                        </div>
                    </div>
                </div>

                <!-- Item Table -->
                <div class="mb-8">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-gray-800 text-white text-[11px] uppercase tracking-widest font-bold">
                                <th class="py-3 px-4 w-12 rounded-tl-lg">#</th>
                                <th class="py-3 px-4">Item & Description</th>
                                <th class="py-3 px-4 w-24 text-center">Qty</th>
                                <th class="py-3 px-4 w-32 text-right">Rate</th>
                                <th class="py-3 px-4 w-32 text-right rounded-tr-lg">Amount</th>
                            </tr>
                        </thead>
                        <tbody class="text-sm">
                            <tr v-for="(item, index) in form.items" :key="index" class="border-b border-gray-100 group">
                                <td class="py-4 px-4 text-gray-400 align-top">{{ index + 1 }}</td>
                                <td class="py-4 px-4 align-top">
                                    <select v-model="item.package_id" @change="onPackageSelect(index)" class="w-full border-0 p-0 focus:ring-0 font-semibold text-gray-800">
                                        <option :value="null">Select Package or custom</option>
                                        <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }}</option>
                                    </select>
                                    <textarea v-model="item.description" rows="1" class="w-full border-0 p-0 text-gray-500 text-xs mt-1 focus:ring-0" placeholder="Enter description..."></textarea>
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <input type="number" v-model.number="item.qty" @input="updateItemAmount(index)" class="w-full border-0 p-0 text-center focus:ring-0" />
                                </td>
                                <td class="py-4 px-4 align-top">
                                    <input type="number" v-model.number="item.rate" @input="updateItemAmount(index)" class="w-full border-0 p-0 text-right focus:ring-0" />
                                </td>
                                <td class="py-4 px-4 align-top text-right font-medium relative">
                                    RM {{ item.amount.toFixed(2) }}
                                    <button v-show="form.items.length > 1" @click="removeRow(index)" type="button" class="absolute -right-2 top-4 text-gray-300 hover:text-red-500 opacity-0 group-hover:opacity-100 transition">✕</button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" @click="addRow" class="mt-4 inline-flex items-center gap-2 text-xs font-bold text-brand-600 hover:text-brand-700">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-brand-100">+</span>
                        Add Another Line
                    </button>
                </div>

                <!-- Footer Summary -->
                <div class="flex justify-between items-start gap-12 mt-12 pb-12">
                    <div class="flex-1 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Notes</label>
                            <textarea v-model="form.notes" rows="3" class="w-full rounded-lg border-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500" placeholder="Thanks for your business."></textarea>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Terms & Conditions</label>
                            <textarea v-model="form.terms" rows="3" class="w-full rounded-lg border-gray-100 text-sm focus:ring-brand-500 focus:border-brand-500" placeholder="Standard travel terms apply."></textarea>
                        </div>
                    </div>

                    <div class="w-80 space-y-3">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Sub Total</span>
                            <span>RM {{ form.sub_total.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg mt-4">
                            <span class="text-sm font-bold text-gray-700 uppercase">Total (RM)</span>
                            <span class="text-xl font-black text-gray-900">RM {{ form.total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-end gap-3 z-50">
                    <button type="button" class="px-6 py-2 rounded-full border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50">Discard</button>
                    <button type="submit" :disabled="form.processing" class="px-8 py-2 rounded-full bg-brand-500 text-white text-sm font-bold shadow-lg hover:bg-brand-600 transition disabled:opacity-50">
                        Save as Draft
                    </button>
                </div>
            </form>
        </div>
    </WorkspaceLayout>
</template>
