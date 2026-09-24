<script setup>
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { AlertTriangle } from 'lucide-vue-next';

defineProps({
    leaksSummary: {
        type: Object,
        required: true,
        default: () => ({ totalAmount: 0, count: 0 }),
    },
});

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-amber-500/25 bg-amber-950/10">
        <div class="flex items-center justify-between mb-2">
            <div class="flex items-center gap-2 text-xs font-bold text-amber-400 uppercase tracking-wider">
                <AlertTriangle class="w-4 h-4 text-amber-400" />
                <span>Raio-X de Vazamentos</span>
            </div>
            <Link 
                href="/vazamentos"
                class="text-[11px] text-amber-400 hover:underline font-semibold"
            >
                Abrir Raio-X →
            </Link>
        </div>
        
        <div class="text-2xl font-extrabold text-white mt-1 font-display">
            {{ formatCurrency(leaksSummary.totalAmount) }}
        </div>
        <p class="text-xs text-slate-400 mt-1">
            Pequenas saídas acumuladas (apostas/bets, micro-pix frequentes e tarifas invisíveis).
        </p>
        <div class="mt-3 flex items-center justify-between">
            <span class="inline-block text-[11px] font-semibold text-amber-300 px-2.5 py-1 rounded-lg bg-amber-500/15 border border-amber-500/30">
                {{ leaksSummary.count }} micro-saídas localizadas
            </span>
            <Link 
                href="/vazamentos" 
                class="text-[11px] font-semibold text-emerald-400 hover:underline"
            >
                Simular Economia ⚡
            </Link>
        </div>
    </div>
</template>
