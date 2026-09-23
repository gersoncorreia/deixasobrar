<script setup>
import { ref, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import SafeToSpendGauge from '@/Components/Dashboard/SafeToSpendGauge.vue';
import LeakRadarCard from '@/Components/Dashboard/LeakRadarCard.vue';
import AccountsQuickList from '@/Components/Dashboard/AccountsQuickList.vue';
import StatementDropzone from '@/Components/Dashboard/StatementDropzone.vue';
import PurchaseSimulatorCard from '@/Components/Dashboard/PurchaseSimulatorCard.vue';
import UpcomingBillsTimeline from '@/Components/Dashboard/UpcomingBillsTimeline.vue';
import TransactionModal from '@/Components/Financial/TransactionModal.vue';
import PreferencesModal from '@/Components/Financial/PreferencesModal.vue';
import FixedBillsModal from '@/Components/Financial/FixedBillsModal.vue';
import PwaInstallPrompt from '@/Components/PwaInstallPrompt.vue';
import { 
    LayoutDashboard, 
    Sparkles, 
    UploadCloud 
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    safeToSpend: Object,
    accounts: Array,
    transactions: [Object, Array],
    filters: Object,
    availableMonths: Array,
    leaksSummary: Object,
    categories: Array,
    upcomingBills: Array,
    flash: Object,
});

// Active Tab State ('overview' | 'simulator' | 'import')
const activeTab = ref('overview');

onMounted(() => {
    const params = new URLSearchParams(window.location.search);
    const tabParam = params.get('tab');
    if (tabParam && ['overview', 'simulator', 'import'].includes(tabParam)) {
        activeTab.value = tabParam;
    }
});

const switchTab = (tab) => {
    activeTab.value = tab;
};

// Modal States
const isTransactionModalOpen = ref(false);
const isPreferencesModalOpen = ref(false);
const isFixedBillsModalOpen = ref(false);

const handleStatementUploaded = () => {
    router.reload({ only: ['safeToSpend', 'accounts', 'transactions', 'leaksSummary', 'availableMonths'] });
};
</script>

<template>
    <Head title="Painel Financeiro" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-16">
            
            <!-- PWA Mobile Install Banner -->
            <PwaInstallPrompt />

            <!-- Top Welcome Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Olá, {{ user.name.split(' ')[0] }} 👋
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Aqui está a previsão real do seu dinheiro e seu teto diário seguro.
                    </p>
                </div>
            </div>

            <!-- Tab Selector Bar -->
            <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 backdrop-blur-md overflow-x-auto scrollbar-none">
                <button
                    @click="switchTab('overview')"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'overview' 
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm shadow-emerald-500/10' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent'"
                >
                    <LayoutDashboard class="w-4 h-4" />
                    <span>Visão Rápida</span>
                </button>

                <button
                    @click="switchTab('simulator')"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'simulator' 
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm shadow-emerald-500/10' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent'"
                >
                    <Sparkles class="w-4 h-4" />
                    <span>Previsibilidade & Simulador</span>
                    <span 
                        v-if="upcomingBills?.length" 
                        class="px-1.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300 border border-slate-700"
                    >
                        {{ upcomingBills.length }}
                    </span>
                </button>

                <button
                    @click="switchTab('import')"
                    class="flex items-center gap-2 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap"
                    :class="activeTab === 'import' 
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm shadow-emerald-500/10' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent'"
                >
                    <UploadCloud class="w-4 h-4" />
                    <span>Importar Extratos</span>
                </button>
            </div>

            <!-- Tab Content Panels -->
            <div>
                <!-- ABA 1: Visão Rápida -->
                <div v-show="activeTab === 'overview'" class="space-y-6">
                    <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                        <!-- Safe to Spend Gauge (8 cols) -->
                        <div class="lg:col-span-8">
                            <SafeToSpendGauge 
                                :safe-to-spend="safeToSpend"
                                @open-fixed-bills="isFixedBillsModalOpen = true"
                                @open-preferences="isPreferencesModalOpen = true"
                            />
                        </div>

                        <!-- Leak Radar & Accounts Quick List (4 cols) -->
                        <div class="lg:col-span-4 space-y-6">
                            <LeakRadarCard :leaks-summary="leaksSummary" />
                            <AccountsQuickList :accounts="accounts" />
                        </div>
                    </div>
                </div>

                <!-- ABA 2: Previsibilidade & Simulador -->
                <div v-show="activeTab === 'simulator'" class="space-y-6">
                    <!-- Simulador de Compra Imediata ("Posso Comprar Isso Hoje?") -->
                    <PurchaseSimulatorCard :safe-to-spend="safeToSpend" />

                    <!-- Linha do Tempo de Vencimentos das Contas Fixas -->
                    <UpcomingBillsTimeline :upcoming-bills="upcomingBills" />
                </div>

                <!-- ABA 3: Importar Extratos -->
                <div v-show="activeTab === 'import'" class="space-y-6">
                    <StatementDropzone 
                        :accounts="accounts" 
                        @uploaded="handleStatementUploaded"
                    />
                </div>
            </div>

        </div>

        <!-- Modals -->
        <TransactionModal 
            :is-open="isTransactionModalOpen"
            :accounts="accounts"
            :categories="categories"
            @close="isTransactionModalOpen = false"
        />

        <PreferencesModal 
            :is-open="isPreferencesModalOpen"
            :user="user"
            @close="isPreferencesModalOpen = false"
        />

        <FixedBillsModal 
            :is-open="isFixedBillsModalOpen"
            @close="isFixedBillsModalOpen = false"
        />
    </AppLayout>
</template>
