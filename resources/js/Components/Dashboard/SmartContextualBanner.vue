<script setup>
import { ref, computed } from 'vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { usePrivacyMode } from '@/Composables/usePrivacyMode';
import { 
    ShieldCheck, 
    Bell, 
    Sparkles, 
    ChevronRight, 
    X,
    Calendar,
    AlertCircle
} from 'lucide-vue-next';

const props = defineProps({
    upcomingBills: {
        type: Array,
        default: () => [],
    },
    safeToSpend: {
        type: Object,
        default: () => ({}),
    },
});

defineEmits(['open-fixed-bills', 'open-simulator']);

const { formatCurrency } = useCurrencyFormat();
const { isPrivate, maskValue } = usePrivacyMode();
const isDismissed = ref(false);

const pendingBills = computed(() => {
    return props.upcomingBills.filter(b => !b.is_paid && b.pending_amount > 0);
});

const totalPending = computed(() => {
    return pendingBills.value.reduce((acc, b) => acc + (b.pending_amount || 0), 0);
});
</script>

<template>
    <div v-if="!isDismissed" class="space-y-3">
        <!-- 1. Alerta de Contas Fixas Próximas (se houver contas pendentes) -->
        <div 
            v-if="pendingBills.length > 0"
            class="rounded-2xl p-4 sm:p-5 bg-gradient-to-r from-amber-950/40 via-slate-900 to-slate-900 border border-amber-500/30 shadow-lg relative overflow-hidden flex items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-amber-500/15 border border-amber-500/30 flex items-center justify-center shrink-0 text-amber-400">
                    <Calendar class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-xs sm:text-sm font-extrabold text-white">
                            {{ pendingBills.length }} {{ pendingBills.length === 1 ? 'conta fixa a vencer' : 'contas fixas a vencer' }}
                        </span>
                        <span class="px-1.5 py-0.2 rounded-full text-[9px] font-bold bg-amber-500/20 text-amber-400 border border-amber-500/30">
                            Blindada
                        </span>
                    </div>
                    <p class="text-[11px] text-slate-400 truncate mt-0.5">
                        Total de <strong class="text-amber-300">{{ maskValue(formatCurrency(totalPending)) }}</strong> protegido no cálculo.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <button
                    @click="$emit('open-fixed-bills')"
                    type="button"
                    class="px-3 py-1.5 rounded-xl bg-amber-500 text-slate-950 text-xs font-bold hover:bg-amber-400 transition-colors shadow-sm active:scale-95"
                >
                    Ver contas
                </button>
                <button 
                    @click="isDismissed = true"
                    class="p-1 rounded-lg text-slate-500 hover:text-slate-300 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- 2. Banner de Dica Inteligente de Economia (quando não há contas atrasadas) -->
        <div 
            v-else
            class="rounded-2xl p-4 sm:p-5 bg-gradient-to-r from-emerald-950/40 via-slate-900 to-slate-900 border border-emerald-500/30 shadow-lg relative overflow-hidden flex items-center justify-between gap-3"
        >
            <div class="flex items-center gap-3 min-w-0">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/15 border border-emerald-500/30 flex items-center justify-center shrink-0 text-emerald-400">
                    <Sparkles class="w-5 h-5" />
                </div>
                <div class="min-w-0">
                    <span class="text-xs sm:text-sm font-extrabold text-white block">
                        Blindagem Total Ativada 🛡️
                    </span>
                    <p class="text-[11px] text-slate-400 truncate mt-0.5">
                        Gaste até seu teto diário de hoje com tranquilidade sem comprometer o mês.
                    </p>
                </div>
            </div>

            <div class="flex items-center gap-1.5 shrink-0">
                <button
                    @click="$emit('open-simulator')"
                    type="button"
                    class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-200 border border-slate-700 text-xs font-bold transition-colors active:scale-95 flex items-center gap-1"
                >
                    <span>Simular Compra</span>
                    <ChevronRight class="w-3.5 h-3.5" />
                </button>
                <button 
                    @click="isDismissed = true"
                    class="p-1 rounded-lg text-slate-500 hover:text-slate-300 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </div>
</template>
