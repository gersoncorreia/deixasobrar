<script setup>
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Calendar, CheckCircle2, Clock, ShieldCheck, ArrowRight } from 'lucide-vue-next';

defineProps({
    upcomingBills: {
        type: Array,
        default: () => [],
    },
});

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="glass-panel rounded-3xl p-6 sm:p-7 border border-slate-800">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
            <div>
                <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                    <Calendar class="w-4 h-4 text-emerald-400" />
                    <span>Linha do Tempo de Contas Fixas no Ciclo</span>
                </h3>
                <p class="text-[11px] text-slate-500 mt-0.5">
                    Previsão de vencimentos e status de quitação automática.
                </p>
            </div>
            
            <Link 
                href="/blindagem" 
                class="text-[11px] text-emerald-400 hover:underline font-semibold flex items-center gap-1"
            >
                <span>Ver todas</span>
                <ArrowRight class="w-3 h-3" />
            </Link>
        </div>

        <div v-if="upcomingBills.length === 0" class="py-6 text-center text-slate-500 text-xs">
            Nenhuma conta fixa cadastrada com vencimento neste ciclo.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3.5">
            <div 
                v-for="bill in upcomingBills.slice(0, 4)" 
                :key="bill.id"
                class="p-4 rounded-2xl border transition-all flex flex-col justify-between"
                :class="bill.is_paid 
                    ? 'bg-emerald-950/20 border-emerald-500/25' 
                    : (bill.days_until_due <= 3 ? 'bg-amber-950/20 border-amber-500/30' : 'bg-slate-900/60 border-slate-800')"
            >
                <div class="flex items-center justify-between mb-2">
                    <span class="text-xs font-bold text-white truncate max-w-[130px]" :title="bill.name">
                        {{ bill.name }}
                    </span>
                    <span 
                        class="text-[10px] font-bold px-2 py-0.5 rounded-full flex items-center gap-1 shrink-0"
                        :class="bill.is_paid 
                            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' 
                            : 'bg-slate-800 text-amber-300 border border-slate-700'"
                    >
                        <CheckCircle2 v-if="bill.is_paid" class="w-3 h-3" />
                        <Clock v-else class="w-3 h-3" />
                        {{ bill.is_paid ? 'Quitada' : (bill.days_until_due === 0 ? 'Vence Hoje' : `em ${bill.days_until_due}d`) }}
                    </span>
                </div>

                <div class="mt-2 flex items-baseline justify-between">
                    <div>
                        <span class="text-[10px] text-slate-400 block">Vencimento</span>
                        <span class="text-xs font-semibold text-slate-200">Dia {{ bill.due_day }}</span>
                    </div>
                    <div class="text-right">
                        <span class="text-[10px] text-slate-400 block">Valor</span>
                        <span class="text-sm font-bold font-display" :class="bill.is_paid ? 'text-emerald-400 line-through opacity-80' : 'text-white'">
                            {{ formatCurrency(bill.budget_ceiling) }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
