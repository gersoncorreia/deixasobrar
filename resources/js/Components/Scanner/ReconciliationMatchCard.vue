<script setup>
import { ref } from 'vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    GitMerge, 
    ArrowRight, 
    Check, 
    Calendar, 
    Receipt, 
    CreditCard, 
    Building2 
} from 'lucide-vue-next';

const props = defineProps({
    scan: Object,
    candidates: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['reconciled']);
const { formatCurrency } = useCurrencyFormat();
const isSubmitting = ref(false);

const handleReconcile = async (transactionId) => {
    isSubmitting.value = true;
    try {
        const response = await window.axios.post(`/scanner/${props.scan.id}/reconcile`, {
            transaction_id: transactionId,
        });

        if (response.data?.success) {
            emit('reconciled', response.data);
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Falha ao conciliar comprovante.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div v-if="candidates && candidates.length > 0" class="glass-panel p-6 rounded-3xl border border-teal-500/30 bg-teal-950/10 space-y-4">
        
        <div class="flex items-center gap-2 text-xs font-bold text-teal-400 uppercase tracking-wider">
            <GitMerge class="w-4 h-4" />
            <span>Casamento com Extrato Detectado (Anti-Duplicação)</span>
        </div>

        <p class="text-xs text-slate-300">
            Localizamos débito(s) bancário(s) correspondente(s) no seu extrato. Você pode unificar para não lançar duas vezes:
        </p>

        <div class="space-y-3">
            <div 
                v-for="tx in candidates" 
                :key="tx.id"
                class="p-4 rounded-2xl bg-slate-900 border border-slate-700/80 flex flex-col sm:flex-row sm:items-center justify-between gap-4"
            >
                <div>
                    <div class="flex items-center gap-2">
                        <span class="font-bold text-white text-xs sm:text-sm">{{ tx.description }}</span>
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold bg-slate-800 text-slate-300">
                            Extrato Bancário
                        </span>
                    </div>
                    <div class="text-[11px] text-slate-400 mt-1 flex items-center gap-3">
                        <span>Data: {{ tx.transaction_date }}</span>
                        <span class="font-bold text-emerald-400 font-display">
                            {{ formatCurrency(Math.abs(tx.amount)) }}
                        </span>
                    </div>
                </div>

                <button 
                    @click="handleReconcile(tx.id)"
                    :disabled="isSubmitting"
                    class="inline-flex items-center gap-2 px-4 py-2 rounded-xl bg-teal-500 hover:bg-teal-400 text-slate-950 font-bold text-xs shadow-lg shadow-teal-500/20 transition-all cursor-pointer self-start sm:self-auto"
                >
                    <Check class="w-3.5 h-3.5" />
                    <span>Conciliar sem Duplicar</span>
                </button>
            </div>
        </div>

    </div>
</template>
