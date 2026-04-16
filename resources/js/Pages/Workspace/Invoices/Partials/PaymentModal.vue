<script setup>
import { ref, watch } from 'vue';
import { useForm } from '@inertiajs/vue3';

const props = defineProps({
    show: Boolean,
    invoice: Object,
    workspace: Object,
});

const emit = defineEmits(['close']);

const form = useForm({
    amount: 0,
    payment_method: 'Bank Transfer',
    payment_date: new Date().toISOString().substr(0, 10),
    proof: null,
    notes: '',
});

watch(() => props.invoice, (newVal) => {
    if (newVal) {
        form.amount = newVal.total - (newVal.paid_amount || 0);
    }
}, { immediate: true });

const submit = () => {
    form.post(route('invoices.record-payment', { 
        tenant: props.workspace.slug, 
        invoice: props.invoice.id 
    }), {
        onSuccess: () => {
            emit('close');
            form.reset();
        },
    });
};
</script>

<template>
    <div v-if="show" class="fixed inset-0 z-[100] flex items-center justify-center p-6 bg-gray-900/60 backdrop-blur-sm animate-in fade-in duration-300">
        <div class="bg-white w-full max-w-lg rounded-[2.5rem] shadow-2xl shadow-emerald-900/40 border border-gray-100 overflow-hidden transform animate-in slide-in-from-bottom-8 duration-500">
            <!-- Header -->
            <div class="px-10 pt-10 pb-6 flex justify-between items-start">
                <div class="space-y-1">
                    <h3 class="text-2xl font-black text-gray-900 tracking-tight flex items-center gap-3">
                        <span class="p-2 bg-emerald-500 text-white rounded-xl shadow-lg shadow-emerald-500/20 text-xs">RM</span>
                        Record Payment
                    </h3>
                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-[0.2em] px-1 italic">Update Invoice Status & Revenue</p>
                </div>
                <button @click="emit('close')" class="text-gray-300 hover:text-gray-600 transition p-2 hover:bg-gray-50 rounded-full">✕</button>
            </div>

            <!-- Form Body -->
            <form @submit.prevent="submit" class="px-10 pb-10 space-y-6">
                <!-- Balance Info -->
                <div class="bg-gray-50 p-6 rounded-3xl border border-gray-100 flex justify-between items-center group">
                    <div class="space-y-0.5">
                        <span class="text-[9px] font-black text-gray-400 uppercase tracking-widest">Balance Due</span>
                        <p class="text-lg font-black text-gray-900">RM {{ Number(invoice.total - (invoice.paid_amount || 0)).toLocaleString('en-MY', { minimumFractionDigits: 2 }) }}</p>
                    </div>
                    <div class="h-10 w-10 bg-white rounded-2xl flex items-center justify-center shadow-sm text-emerald-500 font-black">
                        ↓
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Amount Paid (RM)</label>
                        <input 
                            v-model="form.amount" 
                            type="number" 
                            step="0.01"
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl py-3 px-4 font-bold text-gray-900 focus:ring-emerald-500 focus:border-emerald-500 transition shadow-sm"
                            required
                        >
                    </div>
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Date</label>
                        <input 
                            v-model="form.payment_date" 
                            type="date" 
                            class="w-full bg-gray-50 border-gray-100 rounded-2xl py-3 px-4 font-bold text-gray-700 focus:ring-emerald-500 focus:border-emerald-500 text-sm shadow-sm"
                            required
                        >
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Payment Method</label>
                    <div class="grid grid-cols-3 gap-2">
                        <button 
                            v-for="method in ['Bank Transfer', 'Cash', 'Other']" 
                            :key="method"
                            type="button"
                            @click="form.payment_method = method"
                            class="py-3 px-2 rounded-2xl text-[10px] font-black uppercase tracking-wider transition-all"
                            :class="form.payment_method === method ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-500/20' : 'bg-gray-50 text-gray-400 hover:bg-gray-100'"
                        >
                            {{ method }}
                        </button>
                    </div>
                </div>

                <div class="space-y-2">
                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest px-1">Proof of Payment</label>
                    <div class="relative group cursor-pointer">
                        <input 
                            type="file" 
                            @input="form.proof = $event.target.files[0]"
                            class="absolute inset-0 opacity-0 cursor-pointer z-10"
                        >
                        <div class="w-full border-2 border-dashed border-gray-100 rounded-3xl py-8 flex flex-col items-center justify-center gap-2 group-hover:border-emerald-200 group-hover:bg-emerald-50/10 transition">
                            <span class="text-2xl grayscale group-hover:grayscale-0 transition">📸</span>
                            <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                {{ form.proof ? form.proof.name : 'Upload Receipt / Screen' }}
                            </p>
                        </div>
                    </div>
                </div>

                <div class="pt-4">
                    <button 
                        type="submit" 
                        class="w-full py-4 bg-gray-900 text-white rounded-3xl font-black text-xs uppercase tracking-[0.25em] shadow-2xl hover:bg-emerald-600 transition-all flex items-center justify-center gap-3 active:scale-[0.98]"
                        :disabled="form.processing"
                    >
                        <span v-if="form.processing" class="animate-spin italic italic italic italic">◌</span>
                        Confirm Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</template>
