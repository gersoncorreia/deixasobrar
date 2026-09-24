<script setup>
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Building2 } from 'lucide-vue-next';

defineProps({
    accounts: {
        type: Array,
        required: true,
        default: () => [],
    },
});

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-slate-800">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-xs font-bold text-slate-400 uppercase tracking-wider flex items-center gap-2">
                <Building2 class="w-4 h-4 text-emerald-400" />
                <span>Instituições & Contas</span>
            </h3>
            <Link 
                href="/contas" 
                class="text-[11px] text-emerald-400 hover:underline font-semibold"
            >
                Gerenciar tudo →
            </Link>
        </div>
        <div class="space-y-3">
            <Link 
                v-for="acc in accounts" 
                :key="acc.id"
                href="/contas"
                class="flex items-center justify-between p-3 rounded-xl bg-slate-900/60 border border-slate-800 hover:border-slate-700 text-xs transition-colors block"
            >
                <div>
                    <h4 class="font-bold text-white">{{ acc.name }}</h4>
                    <span class="text-slate-400 text-[10px]">{{ acc.bank_name || 'Banco' }}</span>
                </div>
                <span class="font-bold text-emerald-400 font-display">
                    {{ formatCurrency(acc.current_balance) }}
                </span>
            </Link>
        </div>
    </div>
</template>
