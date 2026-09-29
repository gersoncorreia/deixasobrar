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
    <div class="glass-panel rounded-2xl p-5 sm:p-7 border border-slate-800/90 relative overflow-hidden bg-gradient-to-b from-slate-900/90 via-slate-900/70 to-slate-950/90 shadow-2xl">
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
                    Calculadora de Sobra
                </span>
            </div>
            
            <div class="flex items-center gap-2">
                <!-- Semáforo Amigável -->
                <span 
                    class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold"
                    :class="safeToSpend.status === 'healthy' 
                        ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' 
                        : (safeToSpend.status === 'critical' 
                            ? 'bg-rose-500/15 text-rose-400 border border-rose-500/30' 
                            : 'bg-amber-500/15 text-amber-400 border border-amber-500/30')"
                >
                    <span 
                        class="w-2 h-2 rounded-full animate-pulse"
                        :class="safeToSpend.status === 'healthy' ? 'bg-emerald-400' : (safeToSpend.status === 'critical' ? 'bg-rose-400' : 'bg-amber-400')"
                    ></span>
                    {{ safeToSpend.status === 'healthy' ? '🟢 Tudo Tranquilo' : (safeToSpend.status === 'critical' ? '🔴 Aperto no Mês' : '🟡 Cuidado com os Gastos') }}
                </span>

                <!-- Botão Discreto de Ajustes / Zerar -->
                <button
                    @click="$emit('open-preferences')"
                    type="button"
                    class="p-1.5 rounded-lg text-slate-400 hover:text-white hover:bg-slate-800 border border-transparent hover:border-slate-700 transition-colors"
                    title="Ajustar dia do pagamento ou zerar dados"
                >
                    <Sliders class="w-4 h-4" />
                </button>
            </div>
        </div>

        <!-- Main Display: Quanto posso gastar hoje -->
        <div class="my-5 sm:my-6">
            <span class="text-xs sm:text-sm text-slate-400 font-semibold block">
                Você pode gastar hoje até:
            </span>
            <div class="text-3xl sm:text-6xl font-black text-white tracking-tight my-2 font-display flex items-baseline gap-2">
                <span>{{ maskValue(formatCurrency(safeToSpend.daily_ceiling)) }}</span>
                <span v-if="!isPrivate" class="text-xs sm:text-lg text-slate-400 font-normal">/ por dia</span>
            </div>
            <p 
                class="text-xs sm:text-sm mt-1 max-w-xl font-medium"
                :class="safeToSpend.status === 'healthy' ? 'text-emerald-300' : (safeToSpend.status === 'critical' ? 'text-rose-300' : 'text-amber-300')"
            >
                {{ safeToSpend.status === 'healthy' 
                    ? 'Suas contas obrigatórias já estão reservadas. Você pode gastar esse valor por dia que o dinheiro vai dar até o fim do mês!' 
                    : (safeToSpend.status === 'critical'
                        ? 'Suas contas fixas superaram o dinheiro em conta. Evite qualquer compra não essencial até o próximo pagamento.'
                        : 'Gaste com bastante atenção hoje para garantir que nenhum boleto fique sem pagar no fim do mês.')
                }}
            </p>
        </div>

        <!-- Metrics Breakdown Grid (Linguagem Acessível) -->
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 sm:gap-4 pt-4 sm:pt-6 border-t border-slate-800/80">
            <div class="p-2 sm:p-0 rounded-xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">Saldo no Banco</span>
                <span class="text-sm sm:text-base font-bold text-white font-display">
                    {{ maskValue(formatCurrency(safeToSpend.current_balance)) }}
                </span>
            </div>

            <div 
                @click="$emit('open-fixed-bills')"
                class="cursor-pointer group p-2 sm:p-1.5 sm:-m-1.5 rounded-xl bg-slate-950/30 sm:bg-transparent hover:bg-slate-800/60 transition-all"
                title="Clique para ver suas contas fixas a pagar"
            >
                <span class="text-[11px] text-slate-400 group-hover:text-amber-300 flex items-center gap-1 mb-0.5">
                    Contas a Pagar
                    <ShieldCheck class="w-3 h-3 text-amber-400" />
                </span>
                <span class="text-sm sm:text-base font-bold text-rose-400 font-display group-hover:underline block">
                    - {{ maskValue(formatCurrency(safeToSpend.pending_fixed_bills)) }}
                </span>
                <span class="text-[10px] text-slate-500 group-hover:text-slate-400 underline block mt-0.5">
                    Ver contas
                </span>
            </div>

            <div class="p-2 sm:p-0 rounded-xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">O que Sobra Livre</span>
                <span class="text-sm sm:text-base font-bold text-emerald-400 font-display">
                    {{ maskValue(formatCurrency(safeToSpend.available_capital)) }}
                </span>
            </div>

            <div class="p-2 sm:p-0 rounded-xl bg-slate-950/30 sm:bg-transparent">
                <span class="text-[11px] text-slate-400 block mb-0.5">Próximo Salário</span>
                <span class="text-sm sm:text-base font-bold text-teal-300 font-display">
                    Em {{ safeToSpend.days_remaining }} dias (dia {{ safeToSpend.next_payday_day }})
                </span>
            </div>
        </div>
    </div>
</template>
