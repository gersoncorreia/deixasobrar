<script setup>
import { ref, watch } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import FeatureExplainerModal from '@/Components/Analytics/FeatureExplainerModal.vue';
import { 
    PieChart as PieChartIcon, 
    TrendingUp, 
    TrendingDown, 
    Flame, 
    Sparkles, 
    Compass, 
    Calendar, 
    AlertTriangle, 
    ShieldCheck, 
    ArrowUpRight, 
    ArrowDownRight,
    HelpCircle,
    Info,
    Wallet,
    Target,
    Activity,
    BarChart3,
    Layers,
    SlidersHorizontal,
    Lightbulb,
    ShoppingBag,
    Receipt
} from 'lucide-vue-next';

// Chart.js imports
import {
    Chart as ChartJS,
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    PointElement,
    LineElement
} from 'chart.js';
import { Bar, Doughnut } from 'vue-chartjs';

ChartJS.register(
    Title,
    Tooltip,
    Legend,
    BarElement,
    CategoryScale,
    LinearScale,
    ArcElement,
    PointElement,
    LineElement
);

const props = defineProps({
    user: Object,
    analytics: Object,
});

const { formatCurrency } = useCurrencyFormat();

const isGuideModalOpen = ref(false);
const selectedMonth = ref(props.analytics.selected_month);

// Dynamic Reactive Tabs definition for easy scaling
const activeTab = ref('health'); // 'health' | 'evolution' | 'distribution' | 'villains' | 'basket' | 'guide'

const tabs = [
    { id: 'health', label: 'Saúde & Diagnóstico', icon: Activity, badge: 'Score' },
    { id: 'evolution', label: 'Evolução Semestral', icon: BarChart3, badge: 'Histórico' },
    { id: 'distribution', label: 'Destino do Dinheiro', icon: PieChartIcon, badge: 'Grupos' },
    { id: 'villains', label: 'Vilões do Orçamento', icon: AlertTriangle, badge: 'Vazamentos' },
    { id: 'basket', label: 'Cesta de Compras (OCR)', icon: ShoppingBag, badge: 'Mercado' },
    { id: 'guide', label: 'Guia do DeixaSobrar', icon: Compass, badge: 'Ajuda' },
];

const changeMonth = () => {
    router.get('/analises', { month: selectedMonth.value }, { preserveState: true });
};

// 1. Bar Chart Data (History 6 Months)
const historyChartData = ref({
    labels: props.analytics.six_months_history.labels,
    datasets: [
        {
            label: 'Entradas (Ganhos)',
            backgroundColor: '#10b981',
            borderRadius: 8,
            data: props.analytics.six_months_history.income,
        },
        {
            label: 'Saídas (Gastos)',
            backgroundColor: '#f43f5e',
            borderRadius: 8,
            data: props.analytics.six_months_history.expense,
        },
        {
            label: 'Sobra Real',
            backgroundColor: '#06b6d4',
            borderRadius: 8,
            data: props.analytics.six_months_history.surplus,
        }
    ]
});

const historyChartOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            position: 'top',
            labels: {
                color: '#cbd5e1',
                font: { family: 'Plus Jakarta Sans', size: 12, weight: 'bold' }
            }
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#cbd5e1',
            borderColor: '#334155',
            borderWidth: 1,
            callbacks: {
                label: function(context) {
                    return ` ${context.dataset.label}: ${new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw)}`;
                }
            }
        }
    },
    scales: {
        x: {
            grid: { display: false },
            ticks: { color: '#94a3b8', font: { family: 'Plus Jakarta Sans', size: 11 } }
        },
        y: {
            grid: { color: '#1e293b' },
            ticks: {
                color: '#94a3b8',
                callback: function(value) {
                    return 'R$ ' + value;
                },
                font: { family: 'Plus Jakarta Sans', size: 11 }
            }
        }
    }
};

// 2. Doughnut Chart Data (Distribution by Group)
const doughnutData = ref({
    labels: props.analytics.group_distribution.map(g => g.label),
    datasets: [
        {
            backgroundColor: props.analytics.group_distribution.map(g => g.color),
            borderColor: '#0f172a',
            borderWidth: 3,
            data: props.analytics.group_distribution.map(g => g.total),
            hoverOffset: 6
        }
    ]
});

const doughnutOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: {
        legend: {
            display: false,
        },
        tooltip: {
            backgroundColor: '#0f172a',
            titleColor: '#f8fafc',
            bodyColor: '#cbd5e1',
            borderColor: '#334155',
            borderWidth: 1,
            callbacks: {
                label: function(context) {
                    const val = new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(context.raw);
                    return ` ${context.label}: ${val}`;
                }
            }
        }
    },
    cutout: '70%'
};

watch(() => props.analytics, (newVal) => {
    selectedMonth.value = newVal.selected_month;
    historyChartData.value = {
        labels: newVal.six_months_history.labels,
        datasets: [
            {
                label: 'Entradas (Ganhos)',
                backgroundColor: '#10b981',
                borderRadius: 8,
                data: newVal.six_months_history.income,
            },
            {
                label: 'Saídas (Gastos)',
                backgroundColor: '#f43f5e',
                borderRadius: 8,
                data: newVal.six_months_history.expense,
            },
            {
                label: 'Sobra Real',
                backgroundColor: '#06b6d4',
                borderRadius: 8,
                data: newVal.six_months_history.surplus,
            }
        ]
    };

    doughnutData.value = {
        labels: newVal.group_distribution.map(g => g.label),
        datasets: [
            {
                backgroundColor: newVal.group_distribution.map(g => g.color),
                borderColor: '#0f172a',
                borderWidth: 3,
                data: newVal.group_distribution.map(g => g.total),
                hoverOffset: 6
            }
        ]
    };
}, { deep: true });
</script>

<template>
    <Head title="Inteligência Financeira 360º" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-20">
            
            <!-- Top Header & Period Filter -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                        <PieChartIcon class="w-8 h-8 text-emerald-400" />
                        <span>Central de Inteligência 360º</span>
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Diagnóstico completo e descomplicado. Navegue pelas abas para entender cada dimensão da sua vida financeira.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <!-- Feature Explainer Guide Quick Trigger -->
                    <button 
                        @click="isGuideModalOpen = true"
                        class="inline-flex items-center gap-2 px-3.5 py-2 rounded-xl bg-slate-900 hover:bg-slate-800 text-emerald-400 border border-emerald-500/30 font-bold text-xs sm:text-sm shadow-md transition-all active:scale-95 cursor-pointer"
                    >
                        <Compass class="w-4 h-4 text-emerald-400" />
                        <span>Manual Rápido</span>
                    </button>

                    <!-- Month Filter Select -->
                    <div class="flex items-center gap-2 bg-slate-900 border border-slate-800 rounded-xl px-3 py-1.5">
                        <Calendar class="w-4 h-4 text-slate-400" />
                        <select 
                            v-model="selectedMonth" 
                            @change="changeMonth"
                            class="bg-transparent text-xs sm:text-sm font-bold text-white focus:outline-none cursor-pointer"
                        >
                            <option v-for="m in analytics.available_months" :key="m" :value="m" class="bg-slate-900 text-white">
                                {{ m }}
                            </option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Dynamic Navigation Tabs (Visíveis, Fluidas e Sem Cortar) -->
            <div class="p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 backdrop-blur-md flex flex-wrap items-center gap-1.5 sm:gap-2">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    @click="activeTab = tab.id"
                    class="flex items-center gap-2 px-3.5 py-2 sm:px-4 sm:py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all cursor-pointer select-none"
                    :class="activeTab === tab.id 
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/40 shadow-sm shadow-emerald-500/10' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent'"
                >
                    <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ tab.label }}</span>
                    <span 
                        v-if="tab.badge"
                        class="px-1.5 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider"
                        :class="activeTab === tab.id ? 'bg-emerald-500/20 text-emerald-300' : 'bg-slate-800 text-slate-400'"
                    >
                        {{ tab.badge }}
                    </span>
                </button>
            </div>

            <!-- ========================================== -->
            <!-- ABA 1: Saúde & Diagnóstico em Linguagem Humana -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'health'" class="space-y-6 animate-in fade-in duration-200">
                
                <!-- Health Score Card -->
                <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 relative overflow-hidden bg-gradient-to-r from-slate-900/90 via-slate-900/60 to-slate-950">
                    <div class="flex flex-col lg:flex-row items-start lg:items-center justify-between gap-6">
                        
                        <div class="flex items-center gap-5 sm:gap-6">
                            <!-- Score Circular / Pill Indicator -->
                            <div class="relative w-24 h-24 sm:w-28 sm:h-28 rounded-2xl bg-slate-950/80 border-2 flex flex-col items-center justify-center shrink-0 shadow-2xl"
                                :style="{ borderColor: analytics.health_score.color }"
                            >
                                <span class="text-3xl sm:text-4xl font-black font-display text-white">
                                    {{ analytics.health_score.score }}
                                </span>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">Pontos</span>
                            </div>

                            <!-- Score Interpretation -->
                            <div class="space-y-1.5">
                                <div class="flex items-center gap-2">
                                    <span 
                                        class="px-2.5 py-0.5 rounded-full text-xs font-extrabold uppercase tracking-wider"
                                        :style="{ backgroundColor: `${analytics.health_score.color}20`, color: analytics.health_score.color, border: `1px solid ${analytics.health_score.color}40` }"
                                    >
                                        {{ analytics.health_score.badge }}
                                    </span>
                                    <span class="text-xs text-slate-400">Período: {{ analytics.selected_month_label }}</span>
                                </div>

                                <h3 class="text-lg sm:text-xl font-extrabold text-white">
                                    {{ analytics.health_score.title }}
                                </h3>

                                <p class="text-xs sm:text-sm text-slate-300 max-w-2xl leading-relaxed">
                                    {{ analytics.health_score.advice }}
                                </p>
                            </div>
                        </div>

                        <!-- Fast Quick Stats in Score Card -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 w-full lg:w-auto shrink-0 border-t lg:border-t-0 lg:border-l border-slate-800 pt-4 lg:pt-0 lg:pl-6">
                            <div class="p-3 rounded-2xl bg-slate-950/50 border border-slate-800">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Taxa de Poupança</span>
                                <div class="text-base font-extrabold text-white mt-1 flex items-center gap-1">
                                    <span :class="analytics.metrics.savings_rate >= 0 ? 'text-emerald-400' : 'text-rose-400'">
                                        {{ analytics.metrics.savings_rate }}%
                                    </span>
                                </div>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-950/50 border border-slate-800">
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">Média Gasta / Dia</span>
                                <div class="text-base font-extrabold text-white mt-1">
                                    {{ formatCurrency(analytics.metrics.daily_average_spent) }}
                                </div>
                            </div>

                            <div class="p-3 rounded-2xl bg-slate-950/50 border border-slate-800 col-span-2 sm:col-span-1">
                                <span class="text-[10px] font-bold text-amber-400 uppercase tracking-wider">Vazamentos</span>
                                <div class="text-base font-extrabold text-amber-300 mt-1">
                                    {{ formatCurrency(analytics.metrics.leaks) }}
                                </div>
                            </div>
                        </div>

                    </div>
                </div>

                <!-- 4 Key Balance Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 sm:gap-6">
                    <!-- Entradas -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-400">Total Entrou (Ganhos)</span>
                            <div class="text-xl sm:text-2xl font-black text-emerald-400 font-display mt-1">
                                {{ formatCurrency(analytics.metrics.income) }}
                            </div>
                            <span class="text-[10px] text-slate-400">Salários, transferências e Pix</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center">
                            <ArrowDownRight class="w-6 h-6" />
                        </div>
                    </div>

                    <!-- Saídas -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-400">Total Saiu (Gastos)</span>
                            <div class="text-xl sm:text-2xl font-black text-rose-400 font-display mt-1">
                                {{ formatCurrency(analytics.metrics.expenses) }}
                            </div>
                            <span class="text-[10px] text-slate-400">Contas fixas, compras e taxas</span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-rose-500/10 border border-rose-500/20 text-rose-400 flex items-center justify-center">
                            <ArrowUpRight class="w-6 h-6" />
                        </div>
                    </div>

                    <!-- Sobra Real -->
                    <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-slate-400">O Que Sobrou no Mês</span>
                            <div 
                                class="text-xl sm:text-2xl font-black font-display mt-1"
                                :class="analytics.metrics.net_savings >= 0 ? 'text-emerald-400' : 'text-rose-400'"
                            >
                                {{ formatCurrency(analytics.metrics.net_savings) }}
                            </div>
                            <span class="text-[10px] text-slate-400">
                                {{ analytics.metrics.net_savings >= 0 ? 'Disponível para sua reserva' : 'Déficit no período' }}
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-cyan-500/10 border border-cyan-500/20 text-cyan-400 flex items-center justify-center">
                            <Wallet class="w-6 h-6" />
                        </div>
                    </div>

                    <!-- Vazamentos Detectados -->
                    <div class="p-5 rounded-2xl bg-amber-950/20 border border-amber-500/30 flex items-center justify-between">
                        <div>
                            <span class="text-xs font-bold text-amber-300">Vazamentos & Drenos</span>
                            <div class="text-xl sm:text-2xl font-black text-amber-400 font-display mt-1">
                                {{ formatCurrency(analytics.metrics.leaks) }}
                            </div>
                            <span class="text-[10px] text-amber-200/80">
                                {{ analytics.metrics.leaks_percentage }}% de todos os seus gastos
                            </span>
                        </div>
                        <div class="w-12 h-12 rounded-2xl bg-amber-500/20 border border-amber-500/40 text-amber-400 flex items-center justify-center">
                            <Flame class="w-6 h-6" />
                        </div>
                    </div>
                </div>

                <!-- Dica Rápida de Ação -->
                <div class="p-4 rounded-2xl bg-slate-900/50 border border-slate-800 flex items-center gap-3">
                    <Lightbulb class="w-5 h-5 text-amber-400 shrink-0" />
                    <p class="text-xs text-slate-300 leading-relaxed">
                        <strong>Como interpretar seu score:</strong> Acima de <strong>80 pontos</strong> significa que você está fazendo o dinheiro sobrar com folga e blindando seu futuro. Se estiver abaixo de <strong>60</strong>, explore a aba <em>"Vilões do Orçamento"</em> para descobrir onde cortar gastos.
                    </p>
                </div>

            </div>

            <!-- ========================================== -->
            <!-- ABA 2: Evolução Semestral (Histórico) -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'evolution'" class="space-y-6 animate-in fade-in duration-200">
                <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 flex flex-col justify-between">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-800 gap-2">
                        <div>
                            <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                                <TrendingUp class="w-4 h-4 text-emerald-400" />
                                <span>Fluxo Financeiro Semestral (Últimos 6 Meses)</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Veja mês a mês como o dinheiro entrou, quanto saiu e o resultado líquido que ficou no seu bolso.</p>
                        </div>
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-800 text-slate-300 border border-slate-700 w-fit">
                            Histórico Contínuo
                        </span>
                    </div>

                    <div class="h-80 sm:h-96 w-full mt-6">
                        <Bar :data="historyChartData" :options="historyChartOptions" />
                    </div>

                    <div class="mt-6 pt-4 border-t border-slate-800 flex flex-col sm:flex-row sm:items-center justify-between text-xs text-slate-400 gap-2">
                        <span>💡 <strong>Verde:</strong> Ganhos | <strong>Rosa:</strong> Gastos | <strong>Azul Claro:</strong> Sobra Líquida.</span>
                        <span>Se a barra azul for positiva, você terminou o mês deixando sobrar!</span>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ABA 3: Destino do Dinheiro (Categorias & Grupos) -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'distribution'" class="space-y-6 animate-in fade-in duration-200">
                <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                    
                    <!-- Left: Doughnut Visual Chart -->
                    <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 flex flex-col justify-between">
                        <div class="pb-4 border-b border-slate-800">
                            <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                                <PieChartIcon class="w-4 h-4 text-cyan-400" />
                                <span>Distribuição Metodológica</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Como suas saídas se dividem no mês selecionado.</p>
                        </div>

                        <div class="h-64 relative flex items-center justify-center my-4">
                            <Doughnut :data="doughnutData" :options="doughnutOptions" />
                            <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none text-center">
                                <span class="text-xs text-slate-400 font-bold">Total Gasto</span>
                                <span class="text-base font-black text-white font-display">
                                    {{ formatCurrency(analytics.metrics.expenses) }}
                                </span>
                            </div>
                        </div>

                        <p class="text-[11px] text-slate-400 text-center">
                            Calculado sobre o total de saídas do mês: {{ analytics.selected_month_label }}
                        </p>
                    </div>

                    <!-- Right 2 Cols: Group Cards Breakdown -->
                    <div class="lg:col-span-2 space-y-4">
                        <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-4">
                            <h3 class="text-sm font-extrabold text-white pb-3 border-b border-slate-800">
                                Detalhamento por Grupo de Gasto
                            </h3>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div 
                                    v-for="group in analytics.group_distribution" 
                                    :key="group.key"
                                    class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-start justify-between gap-3"
                                >
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2">
                                            <span class="w-3 h-3 rounded-full shrink-0" :style="{ backgroundColor: group.color }"></span>
                                            <h4 class="text-xs font-bold text-white">{{ group.label }}</h4>
                                        </div>
                                        <div class="text-lg font-black text-white font-display">
                                            {{ formatCurrency(group.total) }}
                                        </div>
                                    </div>
                                    <span class="px-2.5 py-1 rounded-xl text-xs font-black bg-slate-900 text-slate-300 border border-slate-700">
                                        {{ group.percentage }}%
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Rule of Thumb (50-30-20 Education) -->
                        <div class="p-5 rounded-2xl bg-gradient-to-r from-blue-950/30 to-slate-900 border border-blue-500/30 text-xs text-slate-300 space-y-2">
                            <h4 class="text-xs font-extrabold text-blue-300 flex items-center gap-1.5">
                                <Sparkles class="w-4 h-4 text-blue-400" />
                                <span>A Regra de Ouro do DeixaSobrar:</span>
                            </h4>
                            <p class="leading-relaxed">
                                Idealmente, suas <strong>Contas Blindadas e Rotina</strong> não devem ultrapassar <strong>70%</strong> da sua renda. O <strong>Lazer</strong> deve caber em até <strong>15%</strong> e os <strong>Vazamentos</strong> devem ser reduzidos a zero para que pelo menos <strong>15%</strong> sobre para investir e construir sua tranquilidade.
                            </p>
                        </div>
                    </div>

                </div>
            </div>

            <!-- ========================================== -->
            <!-- ABA 4: Vilões do Orçamento (Vazamentos & Drenos) -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'villains'" class="space-y-6 animate-in fade-in duration-200">
                <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center justify-between pb-4 border-b border-slate-800 gap-2">
                        <div>
                            <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                                <AlertTriangle class="w-4 h-4 text-amber-400" />
                                <span>Top 5 Maiores Drenos do Mês</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Os estabelecimentos ou descrições onde o seu dinheiro mais foi gasto neste período.</p>
                        </div>

                        <div class="flex items-center gap-2">
                            <span class="text-xs text-slate-400 font-bold">Total Vazamentos:</span>
                            <span class="px-3 py-1 rounded-xl text-xs font-black bg-amber-500/20 text-amber-300 border border-amber-500/40">
                                {{ formatCurrency(analytics.metrics.leaks) }}
                            </span>
                        </div>
                    </div>

                    <div v-if="analytics.top_villains.length === 0" class="py-12 text-center text-slate-400 text-xs">
                        Nenhum lançamento de gasto encontrado para o mês selecionado.
                    </div>

                    <div v-else class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead>
                                <tr class="border-b border-slate-800 text-slate-400 font-bold uppercase tracking-wider text-[10px]">
                                    <th class="pb-3">Posição & Descrição</th>
                                    <th class="pb-3 text-center">Frequência</th>
                                    <th class="pb-3 text-center">Classificação</th>
                                    <th class="pb-3 text-right">Total Gasto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="(v, index) in analytics.top_villains" :key="v.description" class="hover:bg-slate-900/40 transition-colors">
                                    <td class="py-3 font-bold text-white flex items-center gap-3">
                                        <span class="w-6 h-6 rounded-lg bg-slate-800 flex items-center justify-center text-xs font-black text-slate-300">
                                            #{{ index + 1 }}
                                        </span>
                                        <span>{{ v.description }}</span>
                                    </td>
                                    <td class="py-3 text-center text-slate-400">
                                        {{ v.count }}x no mês
                                    </td>
                                    <td class="py-3 text-center">
                                        <span 
                                            v-if="v.is_leak"
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-amber-500/20 text-amber-300 border border-amber-500/30 text-[10px] font-bold"
                                        >
                                            <Flame class="w-3 h-3 text-amber-400" />
                                            <span>{{ v.leak_reason || 'Vazamento Oculto' }}</span>
                                        </span>
                                        <span 
                                            v-else
                                            class="inline-flex items-center gap-1 px-2.5 py-0.5 rounded-full bg-slate-800 text-slate-400 text-[10px]"
                                        >
                                            Gasto Rotineiro
                                        </span>
                                    </td>
                                    <td class="py-3 text-right font-black text-rose-400 font-display text-sm">
                                        {{ formatCurrency(v.total_spent) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <div class="pt-4 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                        <span>💡 Se você identificar um gasto que não deveria ter ocorrido, marque-o como vazamento na aba Lançamentos.</span>
                        <a href="/vazamentos" class="text-emerald-400 font-bold hover:underline">Abrir Raio-X de Vazamentos Completo ⚡</a>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ABA 5: Cesta de Compras (Micro-Análise OCR) -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'basket'" class="space-y-6 animate-in fade-in duration-200">
                <!-- Summary Cards Row -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-400 border border-teal-500/30 flex items-center justify-center">
                                <Receipt class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-bold block">Cupons Escaneados</span>
                                <span class="text-xl font-extrabold text-white">
                                    {{ analytics.basket_analytics?.total_scans || 0 }} {{ (analytics.basket_analytics?.total_scans || 0) === 1 ? 'notinha' : 'notinhas' }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                                <ShoppingBag class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-bold block">Total Gasto em Cupons</span>
                                <span class="text-xl font-extrabold text-emerald-400 font-display">
                                    {{ formatCurrency(analytics.basket_analytics?.total_spent || 0) }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center justify-center">
                                <Layers class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="text-xs text-slate-400 font-bold block">Itens Individuais Lidos</span>
                                <span class="text-xl font-extrabold text-purple-300">
                                    {{ analytics.basket_analytics?.total_items_count || 0 }} produtos
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Empty State (No Scans) -->
                <div v-if="!analytics.basket_analytics || analytics.basket_analytics.total_scans === 0" class="glass-panel p-10 rounded-2xl border border-slate-800 text-center space-y-4">
                    <div class="w-16 h-16 rounded-2xl bg-slate-800/80 text-slate-500 border border-slate-700 mx-auto flex items-center justify-center">
                        <ShoppingBag class="w-8 h-8" />
                    </div>
                    <div class="max-w-md mx-auto space-y-2">
                        <h3 class="text-base font-extrabold text-white">Nenhum cupom fiscal escaneado neste mês</h3>
                        <p class="text-xs text-slate-400 leading-relaxed">
                            Aponte a câmera do seu celular para cupons de supermercado no <strong>Scanner OCR</strong>. A inteligência extrai cada produto, apaga a foto do servidor para não gastar espaço em disco e gera o raio-x da sua cesta de compras aqui.
                        </p>
                    </div>
                    <div>
                        <a 
                            href="/scanner" 
                            class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition-all shadow-lg shadow-emerald-500/20 cursor-pointer"
                        >
                            <Sparkles class="w-4 h-4" />
                            <span>Escanear Primeiro Cupom</span>
                        </a>
                    </div>
                </div>

                <!-- Content when scans exist -->
                <div v-else class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                    <!-- Col 1: Top 10 Produtos Mais Gastos (7 cols) -->
                    <div class="lg:col-span-7 glass-panel p-6 rounded-2xl border border-slate-800 space-y-4">
                        <div class="flex items-center justify-between pb-3 border-b border-slate-800">
                            <div class="flex items-center gap-2.5">
                                <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center">
                                    <ShoppingBag class="w-4 h-4" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-extrabold text-white">Top 10 Produtos de Maior Custo</h3>
                                    <p class="text-[11px] text-slate-400">Os itens da feira/mercado onde seu dinheiro mais se concentrou</p>
                                </div>
                            </div>
                            <span class="text-[11px] font-bold text-slate-500 bg-slate-900 px-2.5 py-1 rounded-lg border border-slate-800">
                                Ranking Mensal
                            </span>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-800/80">
                                        <th class="py-2.5 font-bold w-12">#</th>
                                        <th class="py-2.5 font-bold">Produto</th>
                                        <th class="py-2.5 font-bold text-center">Qtd Total</th>
                                        <th class="py-2.5 font-bold text-right">Gasto Acumulado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/50">
                                    <tr 
                                        v-for="(prod, idx) in analytics.basket_analytics.top_products" 
                                        :key="prod.product_name"
                                        class="hover:bg-slate-800/30 transition-colors"
                                    >
                                        <td class="py-2.5 font-bold">
                                            <span 
                                                class="w-6 h-6 rounded-lg text-[10px] flex items-center justify-center font-black"
                                                :class="idx === 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : idx < 3 ? 'bg-slate-800 text-emerald-400' : 'text-slate-500'"
                                            >
                                                {{ idx + 1 }}º
                                            </span>
                                        </td>
                                        <td class="py-2.5 font-bold text-white max-w-[200px] truncate" :title="prod.product_name">
                                            {{ prod.product_name }}
                                        </td>
                                        <td class="py-2.5 text-center text-slate-300">
                                            {{ prod.total_qty }} un
                                        </td>
                                        <td class="py-2.5 text-right font-black text-rose-400 font-display">
                                            {{ formatCurrency(prod.total_spent) }}
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="pt-3 border-t border-slate-800 flex items-center justify-between text-[11px] text-slate-400">
                            <span>💡 Os itens são extraídos digitalmente sem guardar fotos no servidor.</span>
                            <a href="/scanner" class="text-teal-400 font-bold hover:underline">Ver Cupons no Scanner 📑</a>
                        </div>
                    </div>

                    <!-- Col 2: Distribuição por Categoria da Cesta (5 cols) -->
                    <div class="lg:col-span-5 glass-panel p-6 rounded-2xl border border-slate-800 space-y-5 flex flex-col justify-between">
                        <div class="space-y-4">
                            <div class="pb-3 border-b border-slate-800">
                                <h3 class="text-sm font-extrabold text-white flex items-center gap-2">
                                    <span>Divisão da Cesta por Categoria</span>
                                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-teal-500/20 text-teal-300 border border-teal-500/30">Micro-IA</span>
                                </h3>
                                <p class="text-[11px] text-slate-400">Como as despesas de supermercado se distribuíram</p>
                            </div>

                            <!-- Category Bars -->
                            <div class="space-y-3.5">
                                <div 
                                    v-for="(cat, catKey) in analytics.basket_analytics.category_split" 
                                    :key="catKey"
                                    class="p-3 rounded-xl bg-slate-950/60 border border-slate-800/80 space-y-1.5"
                                >
                                    <div class="flex items-center justify-between text-xs">
                                        <span class="font-bold text-slate-300 flex items-center gap-2">
                                            <span class="w-2.5 h-2.5 rounded-full" :style="{ backgroundColor: cat.color }"></span>
                                            {{ cat.label }}
                                        </span>
                                        <span class="font-extrabold text-white font-display">
                                            {{ formatCurrency(cat.total) }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between text-[10px] text-slate-400">
                                        <span>{{ cat.count }} {{ cat.count === 1 ? 'item' : 'itens' }}</span>
                                        <span class="font-bold">
                                            {{ (analytics.basket_analytics.total_spent > 0 ? (cat.total / analytics.basket_analytics.total_spent * 100) : 0).toFixed(1) }}% da cesta
                                        </span>
                                    </div>
                                    <div class="w-full bg-slate-800/80 rounded-full h-1.5 overflow-hidden">
                                        <div 
                                            class="h-full rounded-full transition-all duration-500"
                                            :style="{ 
                                                width: `${(analytics.basket_analytics.total_spent > 0 ? (cat.total / analytics.basket_analytics.total_spent * 100) : 0)}%`,
                                                backgroundColor: cat.color
                                            }"
                                        ></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Tip callout -->
                        <div class="p-3.5 rounded-2xl bg-teal-950/30 border border-teal-500/20 text-xs text-slate-300 flex items-start gap-2.5">
                            <Lightbulb class="w-4 h-4 text-teal-400 shrink-0 mt-0.5" />
                            <p class="text-[11px] leading-relaxed">
                                <strong class="text-teal-300">Dica Prática:</strong> Se os <em>Doces & Supérfluos</em> ultrapassarem 15% da cesta, tente substituí-los por marcas genéricas ou frutas da época para turbinar o que sobra no mês!
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ========================================== -->
            <!-- ABA 6: Guia do DeixaSobrar (Didático Embutido) -->
            <!-- ========================================== -->
            <div v-show="activeTab === 'guide'" class="space-y-6 animate-in fade-in duration-200">
                <div class="glass-panel p-6 sm:p-8 rounded-2xl border border-slate-800 space-y-6">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                                <Compass class="w-5 h-5" />
                            </div>
                            <div>
                                <h3 class="text-sm sm:text-base font-extrabold text-white">Manual Completo & Didático das Ferramentas</h3>
                                <p class="text-xs text-slate-400">Tudo o que você precisa saber para transformar sua vida financeira sem precisar ser contador.</p>
                            </div>
                        </div>
                    </div>

                    <!-- Cards Didáticos 6 pilares -->
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                        <!-- 1. Teto Diário -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-emerald-500/20 text-emerald-400 flex items-center justify-center mb-3">
                                    <Target class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">1. Teto Diário Seguro</h4>
                                <p class="text-xs font-bold text-emerald-400">Seu limite diário livre de culpa</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Calcula exatamente quanto você pode gastar hoje sem deixar faltar para as contas fixas do fim do mês. Se você gastar menos que o Teto, o dinheiro sobra automaticamente na sua conta!
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                O Coração do SaaS 🎯
                            </span>
                        </div>

                        <!-- 2. Contas Blindadas -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-blue-500/20 text-blue-400 flex items-center justify-center mb-3">
                                    <ShieldCheck class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">2. Contas Blindadas</h4>
                                <p class="text-xs font-bold text-blue-400">Dinheiro carimbado com antecedência</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Aluguel, condomínio, luz e carnês ficam mentalmente bloqueados do seu saldo. Conforme você paga pelo extrato, a blindagem é baixada e seu saldo livre é recalculado.
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                Proteção Essencial 🛡️
                            </span>
                        </div>

                        <!-- 3. Raio-X de Vazamentos -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-amber-500/20 text-amber-400 flex items-center justify-center mb-3">
                                    <Flame class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">3. Raio-X de Vazamentos</h4>
                                <p class="text-xs font-bold text-amber-400">Fim dos drenos invisíveis</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Identifica taxas de conta desnecessárias, apostas/bets online, assinaturas esquecidas e delivery frequente. Mostra quanto você juntaria em 1 ano se cortasse esses drenos.
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                Alívio Imediato 🔥
                            </span>
                        </div>

                        <!-- 4. Scanner OCR Zero-Lixo & Cesta -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-teal-500/20 text-teal-400 flex items-center justify-center mb-3">
                                    <Sparkles class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">4. Scanner OCR & Cesta de Compras</h4>
                                <p class="text-xs font-bold text-teal-400">Sem fotos no servidor & itens detalhados</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Tire uma foto do cupom fiscal. A IA extrai produtos, loja e valor total, apagando o arquivo de imagem imediatamente para poupar espaço em disco e garantir sigilo total. Veja todos os itens na aba Cesta de Compras!
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                Zero Disco & Máximo Detalhe 📷
                            </span>
                        </div>

                        <!-- 5. Extratos Bancários -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-purple-500/20 text-purple-400 flex items-center justify-center mb-3">
                                    <Wallet class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">5. Extratos Universais</h4>
                                <p class="text-xs font-bold text-purple-400">OFX e CSV de qualquer banco</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Basta baixar o arquivo de extrato no aplicativo do seu banco (BB, Nubank, Itaú, etc.) e arrastar para o DeixaSobrar. Tudo é classificado e organizado sozinho.
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                Sem Digitação Manual 📄
                            </span>
                        </div>

                        <!-- 6. Central 360 -->
                        <div class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 flex flex-col justify-between">
                            <div class="space-y-2">
                                <div class="w-9 h-9 rounded-xl bg-cyan-500/20 text-cyan-400 flex items-center justify-center mb-3">
                                    <PieChartIcon class="w-5 h-5" />
                                </div>
                                <h4 class="text-sm font-extrabold text-white">6. Inteligência 360º</h4>
                                <p class="text-xs font-bold text-cyan-400">Score de Saúde Financeira</p>
                                <p class="text-xs text-slate-300 leading-relaxed">
                                    Transforma números brutos em um termômetro de 0 a 100 com diagnósticos práticos para você saber exatamente se está economizando e para onde seu dinheiro foi.
                                </p>
                            </div>
                            <span class="mt-4 pt-3 border-t border-slate-800 text-[10px] text-slate-400 font-bold uppercase tracking-wider block">
                                Clareza & Controle 📈
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Feature Explainer Modal (Se acionado pelo botão rápido) -->
            <FeatureExplainerModal 
                :is-open="isGuideModalOpen"
                @close="isGuideModalOpen = false"
            />

        </div>
    </AppLayout>
</template>
