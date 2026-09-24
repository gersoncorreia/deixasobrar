<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import LeakMetricsOverview from '@/Components/Leaks/LeakMetricsOverview.vue';
import LeakSavingsSimulator from '@/Components/Leaks/LeakSavingsSimulator.vue';
import LeakCategoriesBreakdown from '@/Components/Leaks/LeakCategoriesBreakdown.vue';
import LeakTransactionsTable from '@/Components/Leaks/LeakTransactionsTable.vue';
import { Flame, Sparkles, Tag } from 'lucide-vue-next';

const props = defineProps({
    safeToSpend: Object,
    summary: Object,
    categoriesBreakdown: Array,
    categories: Array,
    transactions: Object,
    availableMonths: Array,
    filters: Object,
});

// Active Tab state
const activeTab = ref('transactions'); // 'transactions' | 'simulator' | 'categories'

const handleCategoryFilter = (categoryId) => {
    activeTab.value = 'transactions';
    router.get('/vazamentos', { 
        ...props.filters, 
        category_id: categoryId || undefined 
    }, { preserveState: true, preserveScroll: true });
};

const formatMonthLabel = (mStr) => {
    if (!mStr) return '';
    if (mStr === 'all') return 'Todo o Período';
    const [year, month] = mStr.split('-');
    const months = ['Jan', 'Fev', 'Mar', 'Abr', 'Mai', 'Jun', 'Jul', 'Ago', 'Set', 'Out', 'Nov', 'Dez'];
    return `${months[parseInt(month, 10) - 1]} / ${year}`;
};

const handleMonthChange = (e) => {
    const selected = e.target.value;
    router.get('/vazamentos', {
        ...props.filters,
        month: selected,
        start_date: undefined,
        end_date: undefined,
    }, { preserveState: true, preserveScroll: true });
};
</script>

<template>
    <Head title="Raio-X de Vazamentos & Simulador" />

    <AppLayout title="Raio-X de Vazamentos">
        <div class="space-y-8 pb-16">
            <!-- Header with Title and Period Picker -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Raio-X de Vazamentos & Economia 🎯
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Identifique e estanque as micro-saídas invisíveis que drenam o seu teto diário seguro.
                    </p>
                </div>

                <!-- Global Period Selector -->
                <div class="flex items-center gap-2 self-start md:self-auto p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 shadow-lg backdrop-blur-md">
                    <span class="text-xs font-bold text-slate-400 px-2 flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400 animate-pulse"></span>
                        Período:
                    </span>
                    <select
                        :value="filters.month || summary.selectedMonth"
                        @change="handleMonthChange"
                        class="bg-slate-950 border border-slate-700/80 rounded-xl px-3 py-1.5 text-xs font-semibold text-white focus:outline-none focus:border-amber-400 cursor-pointer"
                    >
                        <option 
                            v-for="m in availableMonths" 
                            :key="m" 
                            :value="m"
                        >
                            {{ formatMonthLabel(m) }}
                        </option>
                        <option value="all">🌐 Todo o Histórico (Geral)</option>
                    </select>
                </div>
            </div>

            <!-- 1. Visão Geral das Métricas de Impacto (Sempre visível no topo, contextual ao período) -->
            <LeakMetricsOverview :summary="summary" />

            <!-- 2. Abas de Navegação (Tabs) -->
            <div class="flex items-center gap-2 border-b border-slate-800 pb-3 overflow-x-auto scrollbar-none">
                <button
                    @click="activeTab = 'transactions'"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shrink-0"
                    :class="activeTab === 'transactions' 
                        ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' 
                        : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-850 border border-slate-800'"
                >
                    <Flame class="w-4 h-4" />
                    <span>Lançamentos Rastreados</span>
                    <span 
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="activeTab === 'transactions' ? 'bg-slate-950/25 text-slate-950' : 'bg-slate-800 text-amber-300'"
                    >
                        {{ summary.count }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'simulator'"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shrink-0"
                    :class="activeTab === 'simulator' 
                        ? 'bg-emerald-500 text-slate-950 shadow-lg shadow-emerald-500/20' 
                        : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-850 border border-slate-800'"
                >
                    <Sparkles class="w-4 h-4" />
                    <span>Simulador de Economia</span>
                </button>

                <button
                    @click="activeTab = 'categories'"
                    type="button"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-2xl text-xs sm:text-sm font-bold transition-all shrink-0"
                    :class="activeTab === 'categories' 
                        ? 'bg-amber-500 text-slate-950 shadow-lg shadow-amber-500/20' 
                        : 'bg-slate-900/80 text-slate-400 hover:text-white hover:bg-slate-850 border border-slate-800'"
                >
                    <Tag class="w-4 h-4" />
                    <span>Ranking por Categorias</span>
                    <span 
                        class="px-2 py-0.5 rounded-full text-[10px] font-black"
                        :class="activeTab === 'categories' ? 'bg-slate-950/25 text-slate-950' : 'bg-slate-800 text-amber-300'"
                    >
                        {{ categoriesBreakdown.length }}
                    </span>
                </button>
            </div>

            <!-- 3. Conteúdo das Abas -->
            <div class="transition-all duration-200">
                <!-- Aba 1: Tabela de Lançamentos Rastreados -->
                <div v-show="activeTab === 'transactions'">
                    <LeakTransactionsTable 
                        :transactions="transactions"
                        :categories="categories"
                        :available-months="availableMonths"
                        :filters="filters"
                    />
                </div>

                <!-- Aba 2: Simulador de Economia -->
                <div v-show="activeTab === 'simulator'">
                    <LeakSavingsSimulator 
                        :total-leaks-amount="summary.totalAmount"
                        :days-remaining="summary.daysRemaining"
                        :current-ceiling="safeToSpend.daily_ceiling"
                    />
                </div>

                <!-- Aba 3: Ranking de Categorias -->
                <div v-show="activeTab === 'categories'">
                    <LeakCategoriesBreakdown 
                        :categories="categoriesBreakdown"
                        @select-category="handleCategoryFilter"
                    />
                </div>
            </div>

        </div>
    </AppLayout>
</template>
