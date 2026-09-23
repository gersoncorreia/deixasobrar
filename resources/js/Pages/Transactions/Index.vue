<script setup>
import { ref, reactive } from 'vue';
import { Head, router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import TransactionModal from '@/Components/Financial/TransactionModal.vue';
import { 
    ArrowLeftRight, 
    PlusCircle, 
    FileUp,
    Search, 
    X, 
    Flame, 
    Trash2, 
    ChevronLeft, 
    ChevronRight, 
    Tag, 
    Filter,
    ArrowDownRight,
    ArrowUpRight,
    Building2,
    Calendar,
    AlertTriangle,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    accounts: Array,
    categories: Array,
    transactions: Object,
    filters: Object,
    availableMonths: Array,
    leaksSummary: Object,
});

const { formatCurrency } = useCurrencyFormat();

const isTransactionModalOpen = ref(false);
const isUpdating = ref(null);

const filterState = reactive({
    search: props.filters?.search || '',
    type: props.filters?.type || 'all',
    category_id: props.filters?.category_id || '',
    account_id: props.filters?.account_id || '',
    month: props.filters?.month || '',
});

let searchTimeout = null;
const applyFilters = () => {
    const query = {};
    if (filterState.search) query.search = filterState.search;
    if (filterState.type && filterState.type !== 'all') query.type = filterState.type;
    if (filterState.category_id) query.category_id = filterState.category_id;
    if (filterState.account_id) query.account_id = filterState.account_id;
    if (filterState.month) query.month = filterState.month;

    router.get('/transacoes', query, {
        preserveState: true,
        preserveScroll: true,
    });
};

const onSearchInput = () => {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        applyFilters();
    }, 350);
};

const setTypeFilter = (type) => {
    filterState.type = type;
    applyFilters();
};

const clearSearch = () => {
    filterState.search = '';
    applyFilters();
};

const updateCategory = async (tx, newCategoryId) => {
    try {
        isUpdating.value = tx.id;
        await window.axios.put(`/transacoes/${tx.id}`, { 
            category_id: newCategoryId ? parseInt(newCategoryId, 10) : null 
        });
        tx.category_id = newCategoryId ? parseInt(newCategoryId, 10) : null;
        tx.category = props.categories.find(c => c.id == newCategoryId) || null;
    } catch (err) {
        alert('Erro ao atualizar categoria.');
    } finally {
        isUpdating.value = null;
    }
};

const toggleLeak = async (tx) => {
    try {
        const nextVal = !tx.is_leak;
        await window.axios.put(`/transacoes/${tx.id}`, { is_leak: nextVal });
        tx.is_leak = nextVal;
        router.reload({ only: ['leaksSummary'] });
    } catch (err) {
        alert('Erro ao atualizar status de vazamento.');
    }
};

const deleteTransaction = async (tx) => {
    if (!confirm(`Deseja realmente excluir o lançamento "${tx.description}"? O saldo da conta será recalculado.`)) {
        return;
    }

    try {
        await window.axios.delete(`/transacoes/${tx.id}`);
        router.reload();
    } catch (err) {
        alert('Erro ao excluir lançamento.');
    }
};

const formatMonthLabel = (mStr) => {
    if (!mStr) return '';
    const [year, month] = mStr.split('-');
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return `${months[parseInt(month, 10) - 1]} / ${year}`;
};
</script>

<template>
    <Head title="Lançamentos & Transações" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-16">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                        <ArrowLeftRight class="w-7 h-7 text-emerald-400" />
                        Lançamentos & Transações
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Visualize, filtre por categoria ou conta, e identifique pequenos vazamentos invisíveis no seu orçamento.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2.5">
                    <Link 
                        href="/extratos"
                        class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-slate-900 hover:bg-slate-800 text-slate-200 hover:text-white border border-slate-700/80 font-semibold text-xs sm:text-sm transition-all"
                    >
                        <FileUp class="w-4 h-4 text-emerald-400" />
                        <span>Subir Extrato</span>
                    </Link>

                    <button 
                        @click="isTransactionModalOpen = true"
                        class="btn-shimmer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all"
                    >
                        <PlusCircle class="w-4 h-4" />
                        <span>Novo Lançamento</span>
                    </button>
                </div>
            </div>

            <!-- Complete Transactions & Filtering Section -->
            <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-800">
                
                <!-- Section Header -->
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-lg font-bold text-white flex items-center gap-2">
                            Extrato Detalhado
                            <span v-if="transactions" class="px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-300 text-xs font-normal">
                                {{ transactions.total }} registros
                            </span>
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Filtre por tipo, pesquise por nome ou recategorize com 1 clique.
                        </p>
                    </div>

                    <!-- Type Filter Tabs -->
                    <div class="flex items-center p-1 bg-slate-900 border border-slate-800 rounded-2xl">
                        <button 
                            @click="setTypeFilter('all')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                            :class="filterState.type === 'all' ? 'bg-slate-800 text-white shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            Todos
                        </button>
                        <button 
                            @click="setTypeFilter('expense')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                            :class="filterState.type === 'expense' ? 'bg-rose-500/20 text-rose-300 shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            Saídas
                        </button>
                        <button 
                            @click="setTypeFilter('income')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all"
                            :class="filterState.type === 'income' ? 'bg-emerald-500/20 text-emerald-300 shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            Entradas
                        </button>
                        <button 
                            @click="setTypeFilter('leak')"
                            class="px-3 py-1.5 rounded-xl text-xs font-semibold transition-all flex items-center gap-1"
                            :class="filterState.type === 'leak' ? 'bg-amber-500/20 text-amber-300 shadow-sm' : 'text-slate-400 hover:text-white'"
                        >
                            <Flame class="w-3.5 h-3.5 text-amber-400" />
                            Vazamentos
                        </button>
                    </div>
                </div>

                <!-- Secondary Filters Bar -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-12 gap-3 mb-6">
                    <!-- Search Input -->
                    <div class="lg:col-span-5 relative">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input 
                            v-model="filterState.search"
                            @input="onSearchInput"
                            type="text" 
                            placeholder="Buscar descrição ou documento..." 
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl pl-10 pr-9 py-2 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500 transition-colors"
                        />
                        <button 
                            v-if="filterState.search" 
                            @click="clearSearch"
                            class="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-white"
                        >
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Category Selector -->
                    <div class="lg:col-span-3">
                        <select 
                            v-model="filterState.category_id"
                            @change="applyFilters"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Todas as Categorias</option>
                            <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                {{ cat.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Account Selector -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterState.account_id"
                            @change="applyFilters"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Todas as Contas</option>
                            <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                {{ acc.name }}
                            </option>
                        </select>
                    </div>

                    <!-- Month Selector -->
                    <div class="lg:col-span-2">
                        <select 
                            v-model="filterState.month"
                            @change="applyFilters"
                            class="w-full bg-slate-900 border border-slate-800 rounded-xl px-3 py-2 text-xs text-slate-200 focus:outline-none focus:border-emerald-500"
                        >
                            <option value="">Todos os Meses</option>
                            <option v-for="m in availableMonths" :key="m" :value="m">
                                {{ formatMonthLabel(m) }}
                            </option>
                        </select>
                    </div>
                </div>

                <!-- Transactions Table -->
                <div class="overflow-x-auto -mx-6 sm:mx-0">
                    <table class="w-full text-left text-xs">
                        <thead>
                            <tr class="border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                <th class="pb-3 px-4 sm:px-0">Data</th>
                                <th class="pb-3 px-4 sm:px-0">Descrição & Documento</th>
                                <th class="pb-3 px-4 sm:px-0">Categoria</th>
                                <th class="pb-3 px-4 sm:px-0 text-center">Vazamento</th>
                                <th class="pb-3 px-4 sm:px-0 text-right">Valor</th>
                                <th class="pb-3 px-4 sm:px-0 text-right">Ação</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr 
                                v-for="tx in transactions.data" 
                                :key="tx.id"
                                class="hover:bg-slate-900/40 transition-colors group"
                            >
                                <!-- Data -->
                                <td class="py-3 px-4 sm:px-0 text-slate-400 whitespace-nowrap">
                                    {{ new Date(tx.transaction_date + 'T00:00:00').toLocaleDateString('pt-BR') }}
                                </td>

                                <!-- Descrição & Conta -->
                                <td class="py-3 px-4 sm:px-0">
                                    <div class="font-bold text-white max-w-xs sm:max-w-md truncate">
                                        {{ tx.description }}
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-[10px] text-slate-400 mt-0.5">
                                        <span v-if="tx.account" class="text-slate-400">
                                            {{ tx.account.name }}
                                        </span>
                                        <span v-if="tx.document_number">
                                            • Doc: {{ tx.document_number }}
                                        </span>
                                        <!-- IA Leak Badge -->
                                        <span 
                                            v-if="tx.leak_reason" 
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded-md bg-amber-500/10 text-amber-300 border border-amber-500/20 text-[9px] font-bold"
                                            :title="tx.leak_reason"
                                        >
                                            <Sparkles class="w-2.5 h-2.5 text-amber-400" />
                                            <span>IA: {{ tx.leak_reason }}</span>
                                        </span>
                                    </div>
                                </td>

                                <!-- Categoria (Dropdown Inline) -->
                                <td class="py-3 px-4 sm:px-0">
                                    <select 
                                        :value="tx.category_id || ''"
                                        @change="updateCategory(tx, $event.target.value)"
                                        :disabled="isUpdating === tx.id"
                                        class="bg-slate-900/90 border border-slate-800 rounded-lg px-2.5 py-1 text-[11px] text-slate-300 focus:outline-none focus:border-emerald-500 hover:border-slate-700 transition-colors"
                                    >
                                        <option value="">Sem categoria</option>
                                        <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                            {{ cat.name }}
                                        </option>
                                    </select>
                                </td>

                                <!-- Vazamento Toggle -->
                                <td class="py-3 px-4 sm:px-0 text-center">
                                    <button 
                                        @click="toggleLeak(tx)"
                                        :title="tx.is_leak ? 'Desmarcar vazamento' : 'Marcar como micro-gasto / vazamento'"
                                        class="p-1 rounded-lg transition-colors"
                                        :class="tx.is_leak ? 'text-amber-400 bg-amber-500/15 border border-amber-500/30' : 'text-slate-600 hover:text-slate-400 hover:bg-slate-800'"
                                    >
                                        <Flame class="w-4 h-4" />
                                    </button>
                                </td>

                                <!-- Valor -->
                                <td class="py-3 px-4 sm:px-0 text-right whitespace-nowrap font-bold font-display"
                                    :class="tx.amount > 0 ? 'text-emerald-400' : 'text-slate-200'"
                                >
                                    {{ formatCurrency(tx.amount) }}
                                </td>

                                <!-- Excluir -->
                                <td class="py-3 px-4 sm:px-0 text-right">
                                    <button 
                                        @click="deleteTransaction(tx)"
                                        class="p-1.5 rounded-lg text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                                        title="Excluir lançamento"
                                    >
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </td>
                            </tr>

                            <tr v-if="transactions.data.length === 0">
                                <td colspan="6" class="py-12 text-center text-slate-500">
                                    Nenhum lançamento encontrado para os filtros selecionados.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="transactions.last_page > 1" class="flex items-center justify-between pt-6 border-t border-slate-800 mt-4">
                    <span class="text-xs text-slate-400">
                        Mostrando {{ transactions.from }} a {{ transactions.to }} de {{ transactions.total }} lançamentos
                    </span>

                    <div class="flex items-center gap-1.5">
                        <Link 
                            v-if="transactions.prev_page_url" 
                            :href="transactions.prev_page_url"
                            preserve-scroll
                            preserve-state
                            class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 text-xs flex items-center gap-1"
                        >
                            <ChevronLeft class="w-4 h-4" />
                            <span>Anterior</span>
                        </Link>
                        
                        <span class="px-3 py-1.5 rounded-xl bg-slate-800 text-xs font-bold text-emerald-400">
                            {{ transactions.current_page }} / {{ transactions.last_page }}
                        </span>

                        <Link 
                            v-if="transactions.next_page_url" 
                            :href="transactions.next_page_url"
                            preserve-scroll
                            preserve-state
                            class="p-2 rounded-xl bg-slate-900 border border-slate-800 text-slate-300 hover:text-white hover:border-slate-700 text-xs flex items-center gap-1"
                        >
                            <span>Próxima</span>
                            <ChevronRight class="w-4 h-4" />
                        </Link>
                    </div>
                </div>

            </div>

        </div>

        <TransactionModal 
            :is-open="isTransactionModalOpen"
            :accounts="accounts"
            :categories="categories"
            @close="isTransactionModalOpen = false"
        />
    </AppLayout>
</template>
