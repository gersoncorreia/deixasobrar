<script setup>
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { usePrivacyMode } from '@/Composables/usePrivacyMode';
import { Zap, ShieldCheck, Sliders, Sparkles, TrendingUp } from 'lucide-vue-next';

defineProps({
    safeToSpend: {
        type: Object,
        required: true,
    },
});

defineEmits(['open-fixed-bills', 'open-preferences', 'open-simulator']);

const { formatCurrency } = useCurrencyFormat();
const { isPrivate, maskValue } = usePrivacyMode();
</script>

<template>
    <div class="glass-panel rounded-[28px] sm:rounded-3xl p-5 sm:p-8 border border-slate-800/90 relative overflow-hidden bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-slate-950/90 shadow-2xl">
        <!-- Ambient Glow -->
        <div class="absolute -right-10 -top-10 w-56 h-56 bg-emerald-500/15 rounded-full blur-3xl pointer-events-none"></div>
        <div class="absolute -left-10 -bottom-10 w-44 h-44 bg-teal-500/10 rounded-full blur-3xl pointer-events-none"></div>

        <!-- Top Header inside Hero Card -->
        <div class="flex items-center justify-between gap-2 pb-4 sm:pb-6 border-b border-slate-800/80">
            <div class="flex items-center gap-2">
                <div class="w-6 h-6 rounded-lg bg-emerald-500/10 flex items-center justify-center">
                    <Zap class="w-3.5 h-3.5 text-emerald-400" />
                </div>
                <span class="text-[11px] sm:text-xs font-extrabold uppercase tracking-wider text-emerald-400">
                    Safe-to-Spend
                </span>
            </div>
            
            <span 
                class="inline-flex items-center gap-1.5 px-2.5 py-1 rounded-full text-[11px] sm:text-xs font-bold"
                :class="safeToSpend.status === 'healthy' 
                    ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' 
                    : 'bg-amber-500/15 text-amber-400 border border-amber-500/30'"
            >
                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                {{ safeToSpend.status === 'healthy' ? 'Folga Garantida' : 'Atenção ao Teto' }}
            </span>
        </div>

        <!-- Main Ceiling Display -->
        <div class="my-5 sm:my-6">
            <span class="text-xs sm:text-sm text-slate-400 font-medium block">
                Seu Teto Diário Seguro Hoje
            </span>
            <div class="text-3xl sm:text-6xl font-black text-white tracking-tight my-2 font-display flex items-baseline gap-2">
                <span>{{ maskValue(formatCurrency(safeToSpend.daily_ceiling)) }}</span>
                <span v-if="!isPrivate" class="text-xs sm:text-lg text-slate-400 font-normal">/ dia</span>
            </div>
            <p class="text-xs sm:text-sm text-emerald-300/90 mt-1 max-w-xl">
                Você pode gastar até este valor hoje sem culpa. Suas contas fixas já foram separadas e blindadas.
            </p>
        </div>

        <!-- Quick Action Pills inside Card (Mobile & Desktop) -->
        <div class="flex flex-wrap items-center gap-2 mb-6">
            <!-- Blindar Contas Button -->
            <button 
                @click="$emit('open-fixed-bills')"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-200 bg-slate-800/80 border border-slate-700 hover:border-rose-500/50 hover:bg-slate-800 hover:text-white transition-all active:scale-95 shadow-sm"
                title="Configurar contas fixas blindadas"
            >
                <ShieldCheck class="w-3.5 h-3.5 text-rose-400" />
                <span>Blindar Contas</span>
            </button>

            <!-- Configurar Ciclo Button -->
            <button 
                @click="$emit('open-preferences')"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-200 bg-slate-800/80 border border-slate-700 hover:border-emerald-500/50 hover:bg-slate-800 hover:text-white transition-all active:scale-95 shadow-sm"
                title="Configurar dia do pagamento e reserva"
            >
                <Sliders class="w-3.5 h-3.5 text-emerald-400" />
                <span>Ajustar Ciclo</span>
            </button>

            <!-- Simular Compra Button -->
            <button 
                @click="$emit('open-simulator')"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-200 bg-slate-800/80 border border-slate-700 hover:border-purple-500/50 hover:bg-slate-800 hover:text-white transition-all active:scale-95 shadow-sm"
                title="Simular impacto de uma compra hoje"
            >
                <Sparkles class="w-3.5 h-3.5 text-purple-400" />
                <span>Simular Compra</span>
            </button>
        </div>

        <!-- Metrics Breakdown Grid -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4 sm:pt-6 border-t border-slate-800/80">
            <div class="p-2 sm:p-0 rounded-2xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">Saldo em Contas</span>
                <span class="text-sm sm:text-base font-bold text-white font-display">
                    {{ maskValue(formatCurrency(safeToSpend.current_balance)) }}
                </span>
            </div>

            <div 
                @click="$emit('open-fixed-bills')"
                class="cursor-pointer group p-2 sm:p-1.5 sm:-m-1.5 rounded-2xl bg-slate-950/30 sm:bg-transparent hover:bg-slate-800/60 transition-all"
                title="Clique para gerenciar suas contas fixas blindadas"
            >
                <span class="text-[11px] text-slate-400 group-hover:text-rose-300 flex items-center gap-1 mb-0.5">
                    Contas Blindadas
                    <ShieldCheck class="w-3 h-3 text-rose-400" />
                </span>
                <span class="text-sm sm:text-base font-bold text-rose-400 font-display group-hover:underline block">
                    - {{ maskValue(formatCurrency(safeToSpend.pending_fixed_bills)) }}
                </span>
                <span class="text-[10px] text-slate-500 group-hover:text-slate-400 underline block mt-0.5">
                    Gerenciar
                </span>
            </div>

            <div class="p-2 sm:p-0 rounded-2xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">Dinheiro Livre Real</span>
                <span class="text-sm sm:text-base font-bold text-emerald-400 font-display">
                    {{ maskValue(formatCurrency(safeToSpend.available_capital)) }}
                </span>
            </div>

            <div class="p-2 sm:p-0 rounded-2xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">Ciclo Salarial</span>
                <span class="text-sm sm:text-base font-bold text-teal-300 font-display">
                    {{ safeToSpend.days_remaining }} dias (dia {{ safeToSpend.next_payday_day }})
                </span>
            </div>
        </div>
    </div>
</template>
