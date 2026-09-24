<script setup>
import { reactive, computed, watch } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Flame, CheckCircle, ChevronLeft, ChevronRight, Filter, Calendar, X, RotateCcw, ShieldAlert } from 'lucide-vue-next';

const props = defineProps({
    transactions: {
        type: Object,
        required: true,
    },
    categories: {
        type: Array,
        default: () => [],
    },
    availableMonths: {
        type: Array,
        default: () => [],
    },
    filters: {
        type: Object,
        default: () => ({}),
    },
});

const { formatCurrency } = useCurrencyFormat();

// Local Filter State
const filterForm = reactive({
    status: props.filters.status || 'active',
    category_id: props.filters.category_id || '',
    month: props.filters.month || '',
    start_date: props.filters.start_date || '',
    end_date: props.filters.end_date || '',
});

watch(() => props.filters, (newFilters) => {
    filterForm.status = newFilters.status || 'active';
    filterForm.category_id = newFilters.category_id || '';
    filterForm.month = newFilters.month || '';
    filterForm.start_date = newFilters.start_date || '';
    filterForm.end_date = newFilters.end_date || '';
}, { deep: true });

const hasActiveFilters = computed(() => {
    return Boolean(
        filterForm.category_id || 
        filterForm.start_date || 
        filterForm.end_date || 
        (filterForm.status && filterForm.status !== 'active')
    );
});

const applyFilters = () => {
    router.get('/vazamentos', {
        status: filterForm.status || undefined,
        category_id: filterForm.category_id || undefined,
        month: filterForm.month || undefined,
        start_date: filterForm.start_date || undefined,
        end_date: filterForm.end_date || undefined,
    }, { preserveState: true, preserveScroll: true });
};

const resetFilters = () => {
    filterForm.status = 'active';
    filterForm.category_id = '';
    filterForm.start_date = '';
    filterForm.end_date = '';
    applyFilters();
};

const toggleLeak = async (trx) => {
    try {
        await window.axios.put(`/transacoes/${trx.id}`, {
            is_leak: !trx.is_leak,
        });
        router.reload({ only: ['transactions', 'summary', 'categoriesBreakdown'] });
    } catch (e) {
        console.error('Falha ao alternar vazamento', e);
    }
};

const formatMonthLabel = (mStr) => {
    if (!mStr) return '';
    const [year, month] = mStr.split('-');
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return `${months[parseInt(month, 10) - 1]} / ${year}`;
};

const safeFormatDate = (dateStr) => {
    if (!dateStr) return '';
    try {
        const cleanStr = String(dateStr).trim();
        if (/^\d{2}[\/\-]\d{2}[\/\-]\d{4}/.test(cleanStr)) {
            const parts = cleanStr.substring(0, 10).split(/[\/\-]/);
            return `${parts[0]}/${parts[1]}/${parts[2]}`;
        }
        if (/^\d{4}-\d{2}-\d{2}/.test(cleanStr)) {
            const parts = cleanStr.substring(0, 10).split('-');
            return `${parts[2]}/${parts[1]}/${parts[0]}`;
        }
        const d = new Date(cleanStr);
        return isNaN(d.getTime()) ? cleanStr : d.toLocaleDateString('pt-BR');
    } catch {
        return String(dateStr);
    }
};
</script>

<template>
    <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800">
        <!-- Header -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <Flame class="w-5 h-5 text-amber-400" />
                    <span>Lançamentos Rastreados no Radar</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Filtre por categoria, status ou intervalo de datas para auditar suas despesas.
                </p>
            </div>

            <!-- Clear filters button if active -->
            <button 
                v-if="hasActiveFilters"
                @click="resetFilters"
                type="button"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 font-semibold text-xs border border-slate-700 transition-all self-start sm:self-auto"
            >
                <RotateCcw class="w-3.5 h-3.5" />
                <span>Limpar Filtros</span>
            </button>
        </div>

        <!-- Filter Controls Bar -->
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-6 p-4 rounded-2xl bg-slate-900/60 border border-slate-800">
            <!-- 1. Status Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    Status do Ralo
                </label>
                <select 
                    v-model="filterForm.status"
                    @change="applyFilters"
                    class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400"
                >
                    <option value="active">🔥 Ralos Ativos (Vazamentos)</option>
                    <option value="inactive">✅ Gastos Comuns (Desmarcados)</option>
                    <option value="all">📋 Todos os Lançamentos</option>
                </select>
            </div>

            <!-- 2. Category Filter -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    Categoria
                </label>
                <select 
                    v-model="filterForm.category_id"
                    @change="applyFilters"
                    class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400"
                >
                    <option value="">Todas as Categorias</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
            </div>

            <!-- 3. Start Date -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    Data Inicial
                </label>
                <input 
                    type="date"
                    v-model="filterForm.start_date"
                    @change="applyFilters"
                    class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400 [color-scheme:dark]"
                />
            </div>

            <!-- 4. End Date -->
            <div>
                <label class="block text-[11px] font-semibold text-slate-400 uppercase tracking-wider mb-1">
                    Data Final
                </label>
                <input 
                    type="date"
                    v-model="filterForm.end_date"
                    @change="applyFilters"
                    class="w-full bg-slate-900 border border-slate-700/80 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-amber-400 [color-scheme:dark]"
                />
            </div>
        </div>

        <!-- Empty state -->
        <div v-if="!transactions.data || transactions.data.length === 0" class="p-12 text-center text-slate-500 text-xs">
            Nenhum lançamento encontrado para os filtros selecionados.
        </div>

        <!-- Table List -->
        <div v-else class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="border-b border-slate-800 text-slate-400 uppercase tracking-wider text-[11px]">
                        <th class="py-3.5 px-4 whitespace-nowrap">Data</th>
                        <th class="py-3.5 px-4 min-w-[280px]">Descrição do Lançamento</th>
                        <th class="py-3.5 px-4 min-w-[150px]">Categoria</th>
                        <th class="py-3.5 px-4 min-w-[140px]">Conta</th>
                        <th class="py-3.5 px-4 text-right min-w-[120px]">Valor</th>
                        <th class="py-3.5 px-4 text-center min-w-[130px]">Ação / Status</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-800/60">
                    <tr 
                        v-for="trx in transactions.data" 
                        :key="trx.id"
                        class="hover:bg-slate-900/40 transition-colors"
                    >
                        <td class="py-4 px-4 text-slate-400 font-mono text-[11px] whitespace-nowrap">
                            {{ safeFormatDate(trx.transaction_date) }}
                        </td>
                        <td class="py-4 px-4 font-semibold text-white">
                            <span class="block truncate max-w-md" :title="trx.description">
                                {{ trx.description }}
                            </span>
                        </td>
                        <td class="py-4 px-4">
                            <span 
                                class="inline-flex items-center gap-1 px-3 py-1 rounded-lg text-xs font-semibold whitespace-nowrap border"
                                :class="trx.is_leak 
                                    ? 'bg-amber-500/10 text-amber-300 border-amber-500/20' 
                                    : 'bg-slate-800 text-slate-300 border-slate-700'"
                            >
                                {{ trx.category?.name || 'Sem Categoria' }}
                            </span>
                        </td>
                        <td class="py-4 px-4 text-slate-300 text-xs whitespace-nowrap">
                            {{ trx.account?.name || '-' }}
                        </td>
                        <td class="py-4 px-4 text-right font-bold font-display text-sm whitespace-nowrap" :class="trx.amount < 0 ? 'text-rose-400' : 'text-emerald-400'">
                            {{ trx.amount < 0 ? '- ' : '+ ' }}{{ formatCurrency(Math.abs(trx.amount)) }}
                        </td>
                        <td class="py-4 px-4 text-center whitespace-nowrap">
                            <button 
                                @click="toggleLeak(trx)"
                                type="button"
                                :title="trx.is_leak ? 'Clique para desmarcar como vazamento' : 'Clique para marcar como vazamento no radar'"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-bold transition-all shadow-sm border"
                                :class="trx.is_leak
                                    ? 'bg-amber-500/15 text-amber-300 hover:bg-emerald-500/20 hover:text-emerald-300 hover:border-emerald-500/40 border-amber-500/30'
                                    : 'bg-slate-800/80 text-slate-400 hover:bg-amber-500/20 hover:text-amber-300 hover:border-amber-500/40 border-slate-700'"
                            >
                                <Flame v-if="trx.is_leak" class="w-3.5 h-3.5 text-amber-400" />
                                <CheckCircle v-else class="w-3.5 h-3.5 text-slate-500" />
                                <span>{{ trx.is_leak ? 'Ralo Ativo' : 'Gasto Comum' }}</span>
                            </button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Pagination -->
        <div v-if="transactions.links && transactions.links.length > 3" class="flex items-center justify-between pt-6 border-t border-slate-800 mt-4 text-xs">
            <span class="text-slate-400 text-[11px]">
                Mostrando {{ transactions.from || 0 }} até {{ transactions.to || 0 }} de {{ transactions.total }} lançamentos
            </span>
            <div class="flex items-center gap-1.5">
                <Link
                    v-for="(link, i) in transactions.links"
                    :key="i"
                    :href="link.url || '#'"
                    preserve-scroll
                    preserve-state
                    :class="[
                        'px-3 py-1.5 rounded-xl text-xs font-semibold transition-colors',
                        link.active ? 'bg-amber-500 text-slate-950 font-bold' : 'bg-slate-900 text-slate-300 hover:text-white border border-slate-800',
                        !link.url ? 'opacity-40 cursor-not-allowed' : ''
                    ]"
                    v-html="link.label"
                />
            </div>
        </div>
    </div>
</template>
