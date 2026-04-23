<script setup>
import { ref, computed } from 'vue';
import { Head, useForm, router } from '@inertiajs/vue3';
import WorkspaceLayout from '@/Layouts/WorkspaceLayout.vue';

const props = defineProps({
    workspace: Object,
    customers: Array,
    packages: Array,
    nextId: String,
    fromQuote: Object,
});

const initializeForm = () => {
    const quoteId = props.fromQuote ? (props.fromQuote.id || null) : null;
    return {
        quote_id: quoteId,
        customer_id: props.fromQuote?.customer_id || null,
        manual_customer_data: props.fromQuote?.manual_customer_data || {
            name: '',
            address: '',
            phone: '',
            email: '',
        },
        subject: props.fromQuote?.subject || '',
        items: props.fromQuote?.items?.map(item => ({
            ...item,
            package_id: item.package_id || null,
            amount: item.amount ?? (Number(item.qty || 0) * Number(item.rate || 0)),
        })) || [
            { package_id: null, description: '', qty: 1, rate: 0, amount: 0 }
        ],
        sub_total: props.fromQuote?.sub_total || 0,
        total: props.fromQuote?.total || 0,
        deposit_amount: 0,
        notes: props.fromQuote?.notes || '',
        terms: props.fromQuote?.terms || '',
    };
};

const form = useForm(initializeForm());

const isManualCustomer = ref(!!props.fromQuote?.manual_customer_data?.name);

const updateItemAmount = (index) => {
    const item = form.items[index];
    item.amount = (item.qty || 0) * (item.rate || 0);
    calculateTotals();
};

const calculateTotals = () => {
    form.sub_total = form.items.reduce((sum, item) => sum + (item.amount || 0), 0);
    form.total = form.sub_total;
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
    form.post(route('invoices.store', { tenant: props.workspace.slug }));
};
</script>

<template>
    <Head title="Create Invoice" />

    <WorkspaceLayout>
        <div class="mx-auto max-w-6xl bg-white p-8 shadow-sm rounded-xl border border-gray-100">
            <!-- Zoho Style Header -->
            <div class="flex justify-between items-start border-b pb-8 mb-8">
                <div class="space-y-1">
                    <div class="h-12 w-12 bg-emerald-500 rounded-lg flex items-center justify-center text-white font-bold mb-4">TR</div>
                    <h2 class="text-xl font-bold text-gray-900">{{ workspace.name }}</h2>
                    <p class="text-sm text-gray-500">Kuala Lumpur, Malaysia</p>
                </div>
                <div class="text-right">
                    <h1 class="text-4xl font-light text-gray-400 uppercase tracking-widest">Invoice</h1>
                    <p class="text-lg font-semibold text-gray-700 mt-2"># {{ nextId }}</p>
                    <div v-if="fromQuote" class="mt-2 inline-flex items-center rounded-full bg-blue-50 px-3 py-1 text-xs font-semibold text-blue-700 border border-blue-100">
                        From # {{ fromQuote.public_id }}
                    </div>
                </div>
            </div>

            <form @submit.prevent="submit">
                <!-- Info Section -->
                <div class="grid grid-cols-2 gap-12 mb-10">
                    <div class="space-y-4">
                        <label class="block text-xs font-bold uppercase tracking-wider text-gray-400">Bill To</label>
                        
                        <div v-if="!isManualCustomer" class="space-y-3">
                            <select v-model="form.customer_id" class="w-full rounded-lg border-gray-200 text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                <option :value="null">Select a customer</option>
                                <option v-for="c in customers" :key="c.id" :value="c.id">{{ c.name }}</option>
                            </select>
                            <button type="button" @click="isManualCustomer = true" class="text-xs text-emerald-600 hover:underline">+ Or enter details manually</button>
                        </div>

                        <div v-else class="space-y-3">
                            <input v-model="form.manual_customer_data.name" placeholder="Customer Name" class="w-full rounded-lg border-gray-200 text-sm" />
                            <textarea v-model="form.manual_customer_data.address" placeholder="Address" rows="2" class="w-full rounded-lg border-gray-200 text-sm"></textarea>
                            <button type="button" @click="isManualCustomer = false" class="text-xs text-gray-400 hover:underline">← Back to selection</button>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Invoice Date</span>
                            <span class="font-medium">{{ new Date().toLocaleDateString() }}</span>
                        </div>
                        <div class="flex justify-between items-center text-sm">
                            <span class="text-gray-500">Subject</span>
                            <input v-model="form.subject" placeholder="What is this for?" class="w-1/2 border-0 border-b border-gray-100 focus:border-emerald-500 focus:ring-0 p-0 text-sm font-medium text-right" />
                        </div>
                    </div>
                </div>

                <!-- Item Table -->
                <div class="mb-8 overflow-hidden">
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
                                        <option v-for="p in packages" :key="p.id" :value="p.id">{{ p.name }} ({{ p.available_seats }} left)</option>
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
                    <button type="button" @click="addRow" class="mt-4 inline-flex items-center gap-2 text-xs font-bold text-emerald-600 hover:text-emerald-700">
                        <span class="flex h-5 w-5 items-center justify-center rounded-full bg-emerald-100">+</span>
                        Add Another Line
                    </button>
                </div>

                <!-- Footer Summary -->
                <div class="flex justify-between items-start gap-12 mt-12 pb-12">
                    <div class="flex-1 space-y-6">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Deposit Paid (RM)</label>
                            <input type="number" v-model.number="form.deposit_amount" class="w-full rounded-lg border-gray-100 text-sm focus:ring-emerald-500 focus:border-emerald-500" placeholder="0.00" />
                            <p class="text-[10px] text-gray-400 mt-1 italic">Enter initial deposit if already received.</p>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 mb-2 uppercase tracking-wider">Notes</label>
                            <textarea v-model="form.notes" rows="2" class="w-full rounded-lg border-gray-100 text-sm focus:ring-emerald-500 focus:border-emerald-500"></textarea>
                        </div>
                    </div>

                    <div class="w-80 space-y-3">
                        <div class="flex justify-between text-sm text-gray-600">
                            <span>Sub Total</span>
                            <span>RM {{ form.sub_total.toFixed(2) }}</span>
                        </div>
                        <div class="flex justify-between items-center bg-gray-100 p-4 rounded-lg mt-4 shadow-inner">
                            <span class="text-sm font-bold text-gray-700 uppercase">Total Invoice (RM)</span>
                            <span class="text-xl font-black text-gray-900">RM {{ form.total.toFixed(2) }}</span>
                        </div>
                    </div>
                </div>

                <div class="fixed bottom-0 left-0 right-0 bg-white border-t p-4 flex justify-end gap-3 z-50">
                    <button type="button" class="px-6 py-2 rounded-full border border-gray-200 text-sm font-bold text-gray-600 hover:bg-gray-50">Cancel</button>
                    <button type="submit" :disabled="form.processing" class="px-8 py-2 rounded-full bg-emerald-500 text-white text-sm font-bold shadow-lg hover:bg-emerald-600 transition disabled:opacity-50">
                        Finalize & Issue Invoice
                    </button>
                </div>
            </form>
        </div>
    </WorkspaceLayout>
</template>
