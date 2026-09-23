<script setup>
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { AlertTriangle, Flame, TrendingDown, Calendar } from 'lucide-vue-next';

defineProps({
    summary: {
        type: Object,
        required: true,
    },
});

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
        <!-- Card 1: Total Drenado -->
        <div class="glass-panel rounded-3xl p-6 border border-amber-500/25 bg-amber-950/10 relative overflow-hidden">
            <div class="absolute -right-6 -top-6 w-28 h-28 bg-amber-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs font-bold text-amber-400 uppercase tracking-wider mb-2">
                <span>Ralo Total Identificado</span>
                <Flame class="w-4 h-4 text-amber-400" />
            </div>
            <div class="text-3xl sm:text-4xl font-black text-white font-display">
                {{ formatCurrency(summary.totalAmount) }}
            </div>
            <p class="text-xs text-amber-200/80 mt-1">
                {{ summary.count }} lançamentos classificados como micro-desperdício
            </p>
        </div>

        <!-- Card 2: Impacto Diário no Teto -->
        <div class="glass-panel rounded-3xl p-6 border border-rose-500/25 bg-rose-950/10 relative overflow-hidden">
            <div class="absolute -right-6 -top-6 w-28 h-28 bg-rose-500/10 rounded-full blur-2xl pointer-events-none"></div>
            <div class="flex items-center justify-between text-xs font-bold text-rose-400 uppercase tracking-wider mb-2">
                <span>Dreno Médio Diário</span>
                <TrendingDown class="w-4 h-4 text-rose-400" />
            </div>
            <div class="text-3xl sm:text-4xl font-black text-white font-display">
                - {{ formatCurrency(summary.dailyImpact) }}
                <span class="text-xs text-slate-400 font-normal">/ dia</span>
            </div>
            <p class="text-xs text-rose-200/80 mt-1">
                Valor que está sendo subtraído do seu teto livre diariamente
            </p>
        </div>

        <!-- Card 3: Janela do Ciclo -->
        <div class="glass-panel rounded-3xl p-6 border border-slate-800 bg-slate-900/60 relative overflow-hidden">
            <div class="flex items-center justify-between text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">
                <span>Dias Até o Pagamento</span>
                <Calendar class="w-4 h-4 text-emerald-400" />
            </div>
            <div class="text-3xl sm:text-4xl font-black text-white font-display">
                {{ summary.daysRemaining }}
                <span class="text-xs text-slate-400 font-normal">dias restantes</span>
            </div>
            <p class="text-xs text-slate-400 mt-1">
                Janela de tempo onde pequenas economias geram alívio imediato
            </p>
        </div>
    </div>
</template>
