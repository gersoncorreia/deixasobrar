<script setup>
import { ref, computed } from 'vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Sparkles, ArrowUpRight, PiggyBank, Zap } from 'lucide-vue-next';

const props = defineProps({
    totalLeaksAmount: {
        type: Number,
        default: 0,
    },
    daysRemaining: {
        type: Number,
        default: 1,
    },
    currentCeiling: {
        type: Number,
        default: 0,
    },
});

const { formatCurrency } = useCurrencyFormat();

// Percentage cut options
const cutPercentages = [10, 20, 30, 50];
const selectedCut = ref(20);

// Computed savings calculations
const monthlySavings = computed(() => {
    return (props.totalLeaksAmount * selectedCut.value) / 100;
});

const dailyCeilingBoost = computed(() => {
    const days = Math.max(1, props.daysRemaining);
    return monthlySavings.value / days;
});

const newEstimatedDailyCeiling = computed(() => {
    return props.currentCeiling + dailyCeilingBoost.value;
});

const yearlyAccumulated = computed(() => {
    return monthlySavings.value * 12;
});
</script>

<template>
    <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-emerald-500/30 bg-gradient-to-br from-emerald-950/20 via-slate-900/60 to-slate-950 relative overflow-hidden">
        <div class="absolute -right-10 -bottom-10 w-44 h-44 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 pb-6 border-b border-slate-800">
            <div>
                <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-400">
                    <Sparkles class="w-4 h-4" />
                    <span>Simulador de Economia Inteligente</span>
                </div>
                <h3 class="text-lg font-bold text-white mt-1">
                    E se você estancasse parte desses vazamentos?
                </h3>
            </div>

            <!-- Percentage Selector Chips -->
            <div class="flex items-center gap-2">
                <span class="text-xs text-slate-400 mr-1 hidden sm:inline">Economizar:</span>
                <button
                    v-for="pct in cutPercentages"
                    :key="pct"
                    @click="selectedCut = pct"
                    type="button"
                    class="px-3 py-1.5 rounded-xl text-xs font-bold transition-all"
                    :class="selectedCut === pct 
                        ? 'bg-emerald-500 text-slate-950 shadow-md shadow-emerald-500/25 scale-105' 
                        : 'bg-slate-900 border border-slate-700/80 text-slate-300 hover:text-white hover:border-emerald-500/40'"
                >
                    {{ pct }}%
                </button>
            </div>
        </div>

        <!-- Slider Bar -->
        <div class="mt-6 mb-8">
            <div class="flex justify-between text-xs text-slate-400 mb-2 font-medium">
                <span>Corte conservador (5%)</span>
                <span class="text-emerald-400 font-bold">Meta atual: Cortar {{ selectedCut }}% dos vazamentos</span>
                <span>Corte agressivo (70%)</span>
            </div>
            <input 
                type="range" 
                min="5" 
                max="70" 
                step="5"
                v-model.number="selectedCut" 
                class="w-full accent-emerald-400 h-2 bg-slate-800 rounded-lg cursor-pointer"
            />
        </div>

        <!-- Simulation Result Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-5">
            <!-- Dinheiro resgatado no mês -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
                <span class="text-[11px] font-semibold text-slate-400 uppercase tracking-wider block">
                    Dinheiro Resgatado no Mês
                </span>
                <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-display my-1">
                    + {{ formatCurrency(monthlySavings) }}
                </div>
                <p class="text-xs text-slate-400">
                    Sobra imediata que volta para a sua conta corrente.
                </p>
            </div>

            <!-- Aumento do Teto Diário -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-emerald-500/20 relative">
                <div class="flex items-center justify-between text-[11px] font-semibold text-teal-300 uppercase tracking-wider">
                    <span>Novo Teto Diário Seguro</span>
                    <Zap class="w-3.5 h-3.5 text-teal-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-black text-white font-display my-1">
                    {{ formatCurrency(newEstimatedDailyCeiling) }}
                    <span class="text-xs text-slate-400 font-normal">/ dia</span>
                </div>
                <p class="text-xs text-emerald-300/80">
                    Seu teto diário hoje sobe em <strong class="text-emerald-400">+{{ formatCurrency(dailyCeilingBoost) }}/dia</strong>.
                </p>
            </div>

            <!-- Projeção Anual em Reserva -->
            <div class="p-5 rounded-2xl bg-slate-900/80 border border-slate-800">
                <div class="flex items-center justify-between text-[11px] font-semibold text-amber-300 uppercase tracking-wider">
                    <span>Reserva em 1 Ano</span>
                    <PiggyBank class="w-3.5 h-3.5 text-amber-400" />
                </div>
                <div class="text-2xl sm:text-3xl font-black text-white font-display my-1">
                    {{ formatCurrency(yearlyAccumulated) }}
                </div>
                <p class="text-xs text-slate-400">
                    Acumulado livre para guardar, investir ou realizar metas.
                </p>
            </div>
        </div>
    </div>
</template>
