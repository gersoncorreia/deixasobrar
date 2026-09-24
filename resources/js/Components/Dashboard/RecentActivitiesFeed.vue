<script setup>
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { usePrivacyMode } from '@/Composables/usePrivacyMode';
import {
    ArrowUpRight,
    ArrowDownRight,
    Flame,
    FileText,
    ArrowRight,
    Clock,
    UploadCloud,
    Camera
} from 'lucide-vue-next';

const props = defineProps({
    transactions: {
        type: [Object, Array],
        default: () => [],
    },
});

defineEmits(['open-import', 'open-scanner']);

const { formatCurrency } = useCurrencyFormat();
const { isPrivate, maskValue } = usePrivacyMode();

const transactionList = computed(() => {
    if (!props.transactions) return [];
    if (Array.isArray(props.transactions)) return props.transactions.slice(0, 6);
    if (props.transactions.data && Array.isArray(props.transactions.data)) {
        return props.transactions.data.slice(0, 6);
    }
    return [];
});

const formatDate = (dateStr) => {
    if (!dateStr) return '';
    const date = new Date(dateStr + 'T00:00:00');
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const diffDays = Math.round((today - date) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Hoje';
    if (diffDays === 1) return 'Ontem';

    return new Intl.DateTimeFormat('pt-BR', { day: '2-digit', month: 'short' }).format(date);
};
</script>

<template>
    <div class="glass-panel rounded-3xl p-5 sm:p-6 border border-slate-800/80 bg-slate-900/60 shadow-xl">
        <!-- Header -->
        <div class="flex items-center justify-between pb-4 border-b border-slate-800/80">
            <div class="flex items-center gap-2">
                <div class="w-7 h-7 rounded-xl bg-emerald-500/10 flex items-center justify-center">
                    <Clock class="w-4 h-4 text-emerald-400" />
                </div>
                <h3 class="font-extrabold text-white text-sm sm:text-base">
                    Atividades Recentes
                </h3>
            </div>

            <Link 
                href="/transacoes"
                class="inline-flex items-center gap-1 text-xs font-bold text-emerald-400 hover:text-emerald-300 transition-colors"
            >
                <span>Ver tudo</span>
                <ArrowRight class="w-3.5 h-3.5" />
            </Link>
        </div>

        <!-- Transactions List -->
        <div v-if="transactionList.length > 0" class="divide-y divide-slate-800/60">
            <div 
                v-for="tx in transactionList" 
                :key="tx.id"
                class="py-3.5 flex items-center justify-between gap-3 group"
            >
                <!-- Icon & Details -->
                <div class="flex items-center gap-3 min-w-0">
                    <div 
                        class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border"
                        :class="tx.amount < 0 
                            ? 'bg-rose-500/10 border-rose-500/20 text-rose-400' 
                            : 'bg-emerald-500/10 border-emerald-500/20 text-emerald-400'"
                    >
                        <ArrowDownRight v-if="tx.amount < 0" class="w-5 h-5" />
                        <ArrowUpRight v-else class="w-5 h-5" />
                    </div>

                    <div class="min-w-0">
                        <div class="flex items-center gap-1.5">
                            <span class="text-xs sm:text-sm font-bold text-white truncate block">
                                {{ tx.description }}
                            </span>
                            <span 
                                v-if="tx.is_leak" 
                                class="inline-flex items-center gap-0.5 px-1.5 py-0.2 rounded-full text-[9px] font-extrabold bg-rose-500/20 text-rose-400 border border-rose-500/30 shrink-0"
                                title="Gasto supérfluo detectado pelo Raio-X"
                            >
                                <Flame class="w-2.5 h-2.5" />
                                Vazamento
                            </span>
                        </div>
                        <div class="flex items-center gap-2 text-[10px] sm:text-xs text-slate-400 mt-0.5">
                            <span>{{ formatDate(tx.transaction_date) }}</span>
                            <span>•</span>
                            <span class="truncate">{{ tx.category?.name || 'Sem Categoria' }}</span>
                        </div>
                    </div>
                </div>

                <!-- Amount -->
                <div class="text-right shrink-0">
                    <span 
                        class="text-xs sm:text-sm font-black font-display"
                        :class="tx.amount < 0 ? 'text-slate-100' : 'text-emerald-400'"
                    >
                        {{ tx.amount < 0 ? '-' : '+' }} {{ maskValue(formatCurrency(Math.abs(tx.amount))) }}
                    </span>
                    <span v-if="tx.account?.name" class="text-[10px] text-slate-400 block truncate max-w-[90px]">
                        {{ tx.account.name.split(' ')[0] }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Empty State (Base limpa para início dos testes) -->
        <div v-else class="py-8 text-center px-4">
            <div class="w-14 h-14 rounded-3xl bg-slate-800/80 border border-slate-700/80 flex items-center justify-center mx-auto mb-3 text-slate-400 shadow-inner">
                <FileText class="w-7 h-7 text-emerald-400" />
            </div>
            <h4 class="text-sm font-bold text-white mb-1">
                Nenhum lançamento no momento
            </h4>
            <p class="text-xs text-slate-400 max-w-xs mx-auto mb-5">
                Envie seus novos extratos ou escaneie um comprovante para testar o processamento e a inteligência do sistema.
            </p>
            <div class="flex flex-wrap items-center justify-center gap-2">
                <Link
                    href="/extratos"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-950 bg-emerald-400 hover:bg-emerald-300 transition-colors shadow-md shadow-emerald-500/20 active:scale-95"
                >
                    <UploadCloud class="w-3.5 h-3.5" />
                    <span>Importar Extrato</span>
                </Link>
                <Link
                    href="/scanner"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl text-xs font-bold text-slate-200 bg-slate-800 hover:bg-slate-700 border border-slate-700 transition-colors active:scale-95"
                >
                    <Camera class="w-3.5 h-3.5 text-teal-400" />
                    <span>Escanear Cupom</span>
                </Link>
            </div>
        </div>
    </div>
</template>
