<script setup>
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Zap, ShieldCheck, Sliders } from 'lucide-vue-next';

defineProps({
    safeToSpend: {
        type: Object,
        required: true,
    },
});

defineEmits(['open-fixed-bills', 'open-preferences']);

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-800 relative overflow-hidden">
        <div class="absolute -right-10 -top-10 w-48 h-48 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <div class="flex flex-wrap items-center justify-between gap-3 pb-6 border-b border-slate-800">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-400">
                <Zap class="w-4 h-4" />
                <span>Metodologia Safe-to-Spend</span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Blindar Contas Button -->
                <button 
                    @click="$emit('open-fixed-bills')"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-700/80 hover:border-rose-500/50 hover:text-white transition-colors"
                    title="Configurar contas fixas blindadas"
                >
                    <ShieldCheck class="w-3.5 h-3.5 text-rose-400" />
                    <span>Blindar Contas</span>
                </button>

                <!-- Configurar Ciclo Button -->
                <button 
                    @click="$emit('open-preferences')"
                    type="button"
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-700/80 hover:border-emerald-500/50 hover:text-white transition-colors"
                    title="Configurar dia do pagamento e reserva"
                >
                    <Sliders class="w-3.5 h-3.5 text-emerald-400" />
                    <span>Ajustar Ciclo</span>
                </button>

                <span 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                    :class="safeToSpend.status === 'healthy' ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'"
                >
                    <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                    {{ safeToSpend.status === 'healthy' ? 'Folga Garantida' : 'Atenção ao Teto' }}
                </span>
            </div>
        </div>

        <div class="my-6">
            <span class="text-xs sm:text-sm text-slate-400 font-medium">Seu Teto Diário Seguro Hoje</span>
            <div class="text-4xl sm:text-6xl font-black text-white tracking-tight my-2 font-display">
                {{ formatCurrency(safeToSpend.daily_ceiling) }}
                <span class="text-sm sm:text-lg text-slate-400 font-normal">/ dia</span>
            </div>
            <p class="text-xs sm:text-sm text-emerald-300/90 mt-1">
                Você pode gastar até este valor hoje com total tranquilidade. Suas contas fixas já estão blindadas.
            </p>
        </div>

        <!-- Metrics Breakdown -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 pt-6 border-t border-slate-800">
            <div>
                <span class="text-[11px] text-slate-400 block">Saldo em Contas</span>
                <span class="text-sm sm:text-base font-bold text-white font-display">{{ formatCurrency(safeToSpend.current_balance) }}</span>
            </div>
            <div 
                @click="$emit('open-fixed-bills')"
                class="cursor-pointer group hover:bg-slate-900/60 p-1.5 -m-1.5 rounded-xl transition-all"
                title="Clique para gerenciar suas contas fixas blindadas"
            >
                <span class="text-[11px] text-slate-400 group-hover:text-rose-300 flex items-center gap-1">
                    Contas Fixas Blindadas
                    <ShieldCheck class="w-3 h-3 text-rose-400" />
                </span>
                <span class="text-sm sm:text-base font-bold text-rose-400 font-display group-hover:underline block">
                    - {{ formatCurrency(safeToSpend.pending_fixed_bills) }}
                </span>
                <span class="text-[10px] text-slate-500 group-hover:text-slate-400 underline block mt-0.5">
                    Gerenciar contas
                </span>
            </div>
            <div>
                <span class="text-[11px] text-slate-400 block">Dinheiro Livre Real</span>
                <span class="text-sm sm:text-base font-bold text-emerald-400 font-display">{{ formatCurrency(safeToSpend.available_capital) }}</span>
            </div>
            <div>
                <span class="text-[11px] text-slate-400 block">Até dia {{ safeToSpend.next_payday_day }}</span>
                <span class="text-sm sm:text-base font-bold text-teal-300 font-display">{{ safeToSpend.days_remaining }} dias restantes</span>
            </div>
        </div>
    </div>
</template>
