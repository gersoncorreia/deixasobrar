<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NativeScannerModal from '@/Components/Scanner/NativeScannerModal.vue';
import ReceiptReviewCard from '@/Components/Scanner/ReceiptReviewCard.vue';
import ReconciliationMatchCard from '@/Components/Scanner/ReconciliationMatchCard.vue';
import ReceiptDetailModal from '@/Components/Scanner/ReceiptDetailModal.vue';
import PremiumAlertModal from '@/Components/Common/PremiumAlertModal.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    Camera, 
    ScanLine, 
    FileText, 
    CheckCircle2, 
    Clock, 
    Trash2, 
    Sparkles, 
    Plus,
    ExternalLink,
    Eye,
    ShoppingBag,
    TrendingUp,
    Layers,
    Store,
    Scale,
    Search,
    Filter,
    X,
    ArrowDownRight,
    ArrowUpRight
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    scans: Object, // paginated
    accounts: Array,
    categories: Array,
    quota: Object,
    topProducts: Array,
    merchants: Array,
    priceComparison: Array,
    filters: Object,
    totalScansCount: Number,
    totalItemsCount: Number,
});

const { formatCurrency } = useCurrencyFormat();

const activeTab = ref('history'); // 'history' | 'top_products' | 'price_comparison' | 'merchants'
const isScannerModalOpen = ref(false);
const activeScan = ref(null);
const matchCandidates = ref([]);
const isDetailModalOpen = ref(false);
const selectedScan = ref(null);

const selectedMerchant = ref(props.filters?.merchant || '');
const searchQuery = ref(props.filters?.search || '');

const applyFilters = () => {
    router.get('/scanner', {
        merchant: selectedMerchant.value || undefined,
        search: searchQuery.value || undefined,
    }, {
        preserveState: true,
        preserveScroll: true,
    });
};

const clearFilters = () => {
    selectedMerchant.value = '';
    searchQuery.value = '';
    applyFilters();
};

const openDetailModal = (scan) => {
    selectedScan.value = scan;
    isDetailModalOpen.value = true;
};

const handleScanCaptured = (data) => {
    activeScan.value = data.scan;
    matchCandidates.value = data.match_candidates || [];
    if (data.quota && props.quota) {
        Object.assign(props.quota, data.quota);
    }
};

const handleConfirmed = () => {
    activeScan.value = null;
    matchCandidates.value = [];
    router.reload({ only: ['scans', 'quota'] });
};

const handleReconciled = () => {
    activeScan.value = null;
    matchCandidates.value = [];
    router.reload({ only: ['scans', 'quota'] });
};

const confirmDeleteModal = ref({
    isOpen: false,
    scanToDelete: null,
});

const deleteScan = (scan) => {
    confirmDeleteModal.value = {
        isOpen: true,
        scanToDelete: scan,
    };
};

const executeDeleteScan = () => {
    if (confirmDeleteModal.value.scanToDelete) {
        router.delete(`/scanner/${confirmDeleteModal.value.scanToDelete.id}`, { preserveScroll: true });
        confirmDeleteModal.value.isOpen = false;
        confirmDeleteModal.value.scanToDelete = null;
    }
};
</script>

<template>
    <Head title="Scanner Nativo OCR & Cupons" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-16">
            
            <!-- Header & Quota Card -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold mb-2">
                        <ScanLine class="w-3.5 h-3.5" />
                        <span>Captura & Inteligência Visual Nativa</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Scanner de Cupons & Comprovantes
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Aponte a câmera para cupons de supermercado, farmácia ou Pix físicos. Sem intermediários ou bots externos.
                    </p>
                </div>

                <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Quota Pill Badge -->
                    <div v-if="quota" class="px-4 py-2.5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center justify-between sm:justify-start gap-3">
                        <div class="space-y-0.5">
                            <div class="text-[10px] uppercase font-bold text-slate-400 flex items-center gap-1">
                                <Sparkles class="w-3 h-3 text-purple-400" />
                                <span>Cota de IA (Mês)</span>
                            </div>
                            <div class="text-xs font-black text-white">
                                <span :class="quota.remaining === 0 ? 'text-rose-400' : 'text-emerald-400'">
                                    {{ quota.remaining }} restantes
                                </span>
                                <span class="text-slate-500 font-normal"> / {{ quota.limit }}</span>
                            </div>
                        </div>

                        <a 
                            v-if="quota.upgrade_required"
                            href="/assinatura" 
                            class="px-2.5 py-1 rounded-lg bg-purple-600/30 text-purple-300 border border-purple-500/40 text-[10px] font-bold hover:bg-purple-600 hover:text-white transition-colors"
                        >
                            Assinar PRO ⚡
                        </a>
                    </div>

                    <button 
                        @click="isScannerModalOpen = true"
                        class="btn-shimmer inline-flex items-center justify-center gap-2 px-5 py-3 rounded-2xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-extrabold text-xs sm:text-sm shadow-xl shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all cursor-pointer shrink-0"
                    >
                        <Camera class="w-4 h-4" />
                        <span>Escanear Novo Comprovante</span>
                    </button>
                </div>
            </div>

            <!-- Review / Reconciliation Active Area (If user just captured something) -->
            <div v-if="activeScan" class="space-y-6">
                <!-- Reconciliation Match Suggestions -->
                <ReconciliationMatchCard 
                    :scan="activeScan"
                    :candidates="matchCandidates"
                    @reconciled="handleReconciled"
                />

                <!-- Review and Manual Confirm -->
                <ReceiptReviewCard 
                    :scan="activeScan"
                    :accounts="accounts"
                    :categories="categories"
                    @confirmed="handleConfirmed"
                />
            </div>

            <!-- Search and Merchant Filter Bar -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-800 bg-slate-900/90 flex flex-col md:flex-row items-stretch md:items-center justify-between gap-3">
                <div class="flex-1 flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                    <!-- Search Input -->
                    <div class="relative flex-1">
                        <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                        <input
                            v-model="searchQuery"
                            @keyup.enter="applyFilters"
                            type="text"
                            placeholder="Buscar por mercado, CNPJ ou produto..."
                            class="w-full bg-slate-950 border border-slate-700/80 rounded-xl pl-9 pr-3.5 py-2.5 text-xs text-white placeholder-slate-500 focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <!-- Merchant Selector -->
                    <div class="relative sm:w-64">
                        <Store class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2 pointer-events-none" />
                        <select
                            v-model="selectedMerchant"
                            @change="applyFilters"
                            class="w-full bg-slate-950 border border-slate-700/80 rounded-xl pl-9 pr-8 py-2.5 text-xs text-white focus:outline-none focus:border-emerald-500 cursor-pointer appearance-none"
                        >
                            <option value="">Todos os Estabelecimentos</option>
                            <option v-for="m in merchants" :key="m.merchant_name" :value="m.merchant_name">
                                {{ m.merchant_name }} ({{ m.total_scans }})
                            </option>
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <button
                        @click="applyFilters"
                        class="px-4 py-2.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-xs font-bold text-white transition-colors cursor-pointer flex items-center justify-center gap-2"
                    >
                        <Filter class="w-3.5 h-3.5 text-emerald-400" />
                        <span>Filtrar</span>
                    </button>

                    <!-- Clear Filter -->
                    <button
                        v-if="selectedMerchant || searchQuery"
                        @click="clearFilters"
                        class="px-3 py-2.5 rounded-xl text-xs font-semibold text-rose-400 hover:bg-rose-500/10 transition-colors cursor-pointer flex items-center justify-center gap-1"
                        title="Limpar filtros"
                    >
                        <X class="w-3.5 h-3.5" />
                        <span>Limpar</span>
                    </button>
                </div>
            </div>

            <!-- Tabs: Comprovantes, Mais Comprados, Comparador de Preços e Estabelecimentos -->
            <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 backdrop-blur-md overflow-x-auto">
                <button
                    @click="activeTab = 'history'"
                    type="button"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer shrink-0"
                    :class="activeTab === 'history'
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'"
                >
                    <FileText class="w-4 h-4" />
                    <span>Notas & Comprovantes</span>
                    <span v-if="scans.total > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-300">
                        {{ scans.total }}
                    </span>
                </button>

                <button
                    @click="activeTab = 'top_products'"
                    type="button"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer shrink-0"
                    :class="activeTab === 'top_products'
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'"
                >
                    <ShoppingBag class="w-4 h-4" />
                    <span>O Que Mais Compro</span>
                    <span v-if="topProducts?.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300 border border-purple-500/30">
                        {{ topProducts.length }} itens
                    </span>
                </button>

                <button
                    @click="activeTab = 'price_comparison'"
                    type="button"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer shrink-0"
                    :class="activeTab === 'price_comparison'
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'"
                >
                    <Scale class="w-4 h-4" />
                    <span>Comparador de Preços</span>
                    <span v-if="priceComparison?.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                        Radar Ativo
                    </span>
                </button>

                <button
                    @click="activeTab = 'merchants'"
                    type="button"
                    class="flex items-center gap-2 px-3.5 sm:px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all cursor-pointer shrink-0"
                    :class="activeTab === 'merchants'
                        ? 'bg-gradient-to-r from-emerald-500/20 to-teal-500/20 text-emerald-400 border border-emerald-500/30 shadow-sm'
                        : 'text-slate-400 hover:text-white hover:bg-slate-800/60 border border-transparent'"
                >
                    <Store class="w-4 h-4" />
                    <span>Estabelecimentos</span>
                    <span v-if="merchants?.length > 0" class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/20 text-blue-300 border border-blue-500/30">
                        {{ merchants.length }}
                    </span>
                </button>
            </div>

            <!-- TAB 1: History of Scanned Receipts Table -->
            <div v-show="activeTab === 'history'" class="glass-panel rounded-2xl border border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <FileText class="w-4 h-4 text-emerald-400" />
                            <span>Comprovantes & Notas Capturadas</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Histórico auditável com conferência de itens e valores</p>
                    </div>
                </div>

                <div v-if="scans.data?.length === 0" class="p-12 text-center">
                    <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <Camera class="w-7 h-7" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Nenhum comprovante capturado ainda</h4>
                    <p class="text-xs text-slate-400 mt-1 max-w-sm mx-auto">
                        Clique em "Escanear Novo Comprovante" para apontar a câmera do celular para uma notinha ou subir um print.
                    </p>
                </div>

                <div v-else class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4 font-bold">Comprovante</th>
                                <th class="p-4 font-bold">Data da Compra</th>
                                <th class="p-4 font-bold">Valor</th>
                                <th class="p-4 font-bold">Itens / Carrinho</th>
                                <th class="p-4 font-bold">Conciliação</th>
                                <th class="p-4 font-bold text-right">Ações</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="scan in scans.data" :key="scan.id" class="hover:bg-slate-900/30 transition-colors">
                                <!-- Merchant and Clean Icon -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div 
                                            @click="openDetailModal(scan)"
                                            class="w-10 h-10 rounded-xl bg-slate-950 border border-slate-700 text-emerald-400 flex items-center justify-center shrink-0 cursor-pointer hover:border-emerald-500 transition-colors"
                                            title="Ver dados do cupom"
                                        >
                                            <Receipt class="w-5 h-5" />
                                        </div>
                                        <div>
                                            <div 
                                                @click="openDetailModal(scan)"
                                                class="font-bold text-white hover:text-emerald-400 transition-colors cursor-pointer"
                                            >
                                                {{ scan.merchant_name || 'Estabelecimento' }}
                                            </div>
                                            <div class="text-[10px] text-slate-400">CNPJ: {{ scan.merchant_tax_id || 'Não informado' }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Date -->
                                <td class="p-4 text-slate-300 text-[11px]">
                                    {{ scan.purchased_at ? scan.purchased_at.substring(0, 16) : '-' }}
                                </td>

                                <!-- Amount -->
                                <td class="p-4 font-bold text-emerald-400 font-display text-sm">
                                    {{ formatCurrency(scan.total_amount) }}
                                </td>

                                <!-- Items Count (Clickable to open modal) -->
                                <td class="p-4 text-slate-300 text-[11px]">
                                    <button 
                                        @click="openDetailModal(scan)"
                                        type="button"
                                        class="px-2.5 py-1 rounded-lg bg-slate-800 hover:bg-slate-700 text-emerald-400 hover:text-white font-bold inline-flex items-center gap-1.5 transition-colors cursor-pointer"
                                        title="Clique para ver os itens"
                                    >
                                        <Eye class="w-3.5 h-3.5" />
                                        <span>{{ scan.items && scan.items.length > 0 ? `${scan.items.length} produtos` : 'Ver dados' }}</span>
                                    </button>
                                </td>

                                <!-- Match Status -->
                                <td class="p-4">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="scan.match_status === 'matched' 
                                            ? 'bg-teal-500/15 text-teal-400 border border-teal-500/30' 
                                            : (scan.match_status === 'manual_created' 
                                                ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' 
                                                : 'bg-amber-500/15 text-amber-400 border border-amber-500/30')"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="scan.match_status === 'matched' ? 'bg-teal-400' : (scan.match_status === 'manual_created' ? 'bg-emerald-400' : 'bg-amber-400')"></span>
                                        {{ scan.match_status === 'matched' ? 'Conciliado com Extrato' : (scan.match_status === 'manual_created' ? 'Lançado no Saldo' : 'Pendente') }}
                                    </span>
                                </td>

                                <!-- Actions -->
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <button 
                                            @click="openDetailModal(scan)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-emerald-400 hover:bg-emerald-500/10 transition-colors"
                                            title="Ver detalhes da notinha"
                                        >
                                            <Eye class="w-4 h-4" />
                                        </button>
                                        <button 
                                            @click="deleteScan(scan)"
                                            class="p-1.5 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                                            title="Excluir comprovante"
                                        >
                                            <Trash2 class="w-4 h-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB 2: O Que Mais Compro (Ranking dos Produtos Mais Comprados em Cupons) -->
            <div v-show="activeTab === 'top_products'" class="space-y-6">
                <!-- Summary KPI Cards -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                            <ShoppingBag class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-bold block">Produtos Diferentes</span>
                            <span class="text-xl font-extrabold text-white">
                                {{ topProducts?.length || 0 }} itens cadastrados
                            </span>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-500/20 text-purple-400 border border-purple-500/30 flex items-center justify-center shrink-0">
                            <Layers class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-bold block">Unidades Escaneadas</span>
                            <span class="text-xl font-extrabold text-purple-300">
                                {{ totalItemsCount }} itens lidos
                            </span>
                        </div>
                    </div>

                    <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60 flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-teal-500/20 text-teal-400 border border-teal-500/30 flex items-center justify-center shrink-0">
                            <TrendingUp class="w-5 h-5" />
                        </div>
                        <div>
                            <span class="text-xs text-slate-400 font-bold block">Gasto Total Acumulado</span>
                            <span class="text-xl font-extrabold text-emerald-400 font-display">
                                {{ formatCurrency(topProducts?.reduce((acc, p) => acc + (p.total_spent || 0), 0) || 0) }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Empty State for Top Products -->
                <div v-if="!topProducts || topProducts.length === 0" class="glass-panel p-12 text-center rounded-2xl border border-slate-800 space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <ShoppingBag class="w-7 h-7" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Nenhum produto analisado ainda</h4>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Quando você escanear cupons de supermercado, a inteligência extrai cada item (arroz, feijão, sabão, etc.) e monta aqui o ranking do que você mais compra.
                    </p>
                </div>

                <!-- Products Table -->
                <div v-else class="glass-panel rounded-2xl border border-slate-800 overflow-hidden">
                    <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <TrendingUp class="w-4 h-4 text-emerald-400" />
                                <span>Ranking dos Produtos Mais Comprados</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-0.5">Onde o dinheiro da sua feira e mercado mais se concentra</p>
                        </div>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800">
                                <tr>
                                    <th class="p-4 font-bold w-14">#</th>
                                    <th class="p-4 font-bold">Produto</th>
                                    <th class="p-4 font-bold text-center">Quantas Vezes Comprou</th>
                                    <th class="p-4 font-bold text-center">Qtd Total</th>
                                    <th class="p-4 font-bold text-right">Total Gasto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-800/60">
                                <tr v-for="(prod, idx) in topProducts" :key="idx" class="hover:bg-slate-900/30 transition-colors">
                                    <td class="p-4 font-bold">
                                        <span 
                                            class="w-7 h-7 rounded-lg text-xs flex items-center justify-center font-black"
                                            :class="idx === 0 ? 'bg-amber-500/20 text-amber-300 border border-amber-500/30' : idx < 3 ? 'bg-slate-800 text-emerald-400' : 'text-slate-500'"
                                        >
                                            {{ idx + 1 }}º
                                        </span>
                                    </td>
                                    <td class="p-4 font-bold text-white">
                                        {{ prod.name }}
                                        <span 
                                            class="ml-2 px-2 py-0.5 rounded text-[10px] font-semibold"
                                            :class="prod.category === 'superfluo' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-400'"
                                        >
                                            {{ prod.category === 'superfluo' ? 'Supérfluo' : 'Essencial' }}
                                        </span>
                                    </td>
                                    <td class="p-4 text-center text-slate-300 font-semibold">
                                        {{ prod.occurrences }}x
                                    </td>
                                    <td class="p-4 text-center text-slate-300 font-semibold">
                                        {{ prod.quantity }} un
                                    </td>
                                    <td class="p-4 text-right font-black text-rose-400 font-display text-sm">
                                        {{ formatCurrency(prod.total_spent) }}
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- TAB 3: Radar Comparador de Preços (Onde é mais barato?) -->
            <div v-show="activeTab === 'price_comparison'" class="space-y-6">
                <!-- Header Card -->
                <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60 flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div class="flex items-center gap-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0">
                            <Scale class="w-5 h-5" />
                        </div>
                        <div>
                            <h3 class="text-sm font-extrabold text-white">Radar Comparador de Preços por Mercado</h3>
                            <p class="text-xs text-slate-400">Identifique onde os produtos que você compra saem mais em conta</p>
                        </div>
                    </div>
                </div>

                <!-- Empty State -->
                <div v-if="!priceComparison || priceComparison.length === 0" class="glass-panel p-12 text-center rounded-2xl border border-slate-800 space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <Scale class="w-7 h-7" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Ainda não há dados suficientes para comparar</h4>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Conforme você escanear cupons de diferentes supermercados, padarias e feiras, o sistema comparará os preços dos mesmos produtos automaticamente aqui.
                    </p>
                </div>

                <!-- Comparison Cards Grid -->
                <div v-else class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div 
                        v-for="(item, idx) in priceComparison" 
                        :key="idx"
                        class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/70 space-y-4 hover:border-slate-700 transition-all"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Produto Analisado</span>
                                <h4 class="text-sm font-extrabold text-white mt-0.5">{{ item.item_name }}</h4>
                            </div>

                            <span 
                                v-if="item.difference > 0"
                                class="px-2.5 py-1 rounded-xl text-[10px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shrink-0"
                            >
                                Economia de até {{ item.savings_percentage }}%
                            </span>
                        </div>

                        <!-- Best Price vs Highest Price Cards -->
                        <div class="grid grid-cols-2 gap-3 pt-1">
                            <!-- Lowest Price (Winner) -->
                            <div class="p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/30">
                                <div class="flex items-center gap-1 text-[10px] font-bold text-emerald-400 uppercase">
                                    <ArrowDownRight class="w-3.5 h-3.5" />
                                    <span>Mais Barato</span>
                                </div>
                                <div class="text-base font-black text-white font-display mt-0.5">
                                    {{ formatCurrency(item.min_price) }} <span class="text-[10px] font-normal text-slate-400">/ {{ item.unit }}</span>
                                </div>
                                <div class="text-[10px] text-emerald-300 font-semibold truncate mt-1" :title="item.best_merchant">
                                    🏪 {{ item.best_merchant }}
                                </div>
                            </div>

                            <!-- Highest Price -->
                            <div class="p-3 rounded-xl bg-slate-950/60 border border-slate-800">
                                <div class="flex items-center gap-1 text-[10px] font-bold text-rose-400 uppercase">
                                    <ArrowUpRight class="w-3.5 h-3.5" />
                                    <span>Mais Caro</span>
                                </div>
                                <div class="text-base font-black text-slate-300 font-display mt-0.5">
                                    {{ formatCurrency(item.max_price) }} <span class="text-[10px] font-normal text-slate-400">/ {{ item.unit }}</span>
                                </div>
                                <div class="text-[10px] text-slate-400 font-semibold truncate mt-1" :title="item.highest_merchant">
                                    🏪 {{ item.highest_merchant }}
                                </div>
                            </div>
                        </div>

                        <!-- History Footnote -->
                        <div class="text-[11px] text-slate-400 pt-1 flex items-center justify-between border-t border-slate-800/80">
                            <span>Visto em {{ item.merchants_count }} estabelecimento(s)</span>
                            <span v-if="item.difference > 0" class="text-emerald-400 font-bold">
                                Diferença de {{ formatCurrency(item.difference) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- TAB 4: Estabelecimentos & Empresas Analisadas -->
            <div v-show="activeTab === 'merchants'" class="space-y-6">
                <!-- Empty State -->
                <div v-if="!merchants || merchants.length === 0" class="glass-panel p-12 text-center rounded-2xl border border-slate-800 space-y-3">
                    <div class="w-14 h-14 rounded-2xl bg-slate-900 border border-slate-800 text-slate-500 flex items-center justify-center mx-auto mb-3">
                        <Store class="w-7 h-7" />
                    </div>
                    <h4 class="text-sm font-bold text-white">Nenhum estabelecimento registrado</h4>
                    <p class="text-xs text-slate-400 max-w-sm mx-auto">
                        Ao fotografar cupons fiscais, os nomes das empresas e CNPJs são identificados automaticamente e agrupados aqui.
                    </p>
                </div>

                <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <div 
                        v-for="m in merchants" 
                        :key="m.merchant_name"
                        class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/70 space-y-4 hover:border-slate-700 transition-all flex flex-col justify-between"
                    >
                        <div>
                            <div class="flex items-center justify-between gap-2 mb-2">
                                <span class="px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-blue-500/15 border border-blue-500/30 text-blue-300">
                                    {{ m.total_scans }} {{ m.total_scans === 1 ? 'comprovante' : 'comprovantes' }}
                                </span>
                                <span class="text-[10px] text-slate-500">
                                    Última: {{ m.last_purchase_at ? m.last_purchase_at.substring(0, 10) : '-' }}
                                </span>
                            </div>

                            <h4 class="text-sm font-black text-white line-clamp-2">
                                {{ m.merchant_name }}
                            </h4>
                            <p class="text-[11px] text-slate-400 mt-1">
                                CNPJ: <strong class="text-slate-300">{{ m.cnpj || 'Não informado na nota' }}</strong>
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-800 flex items-center justify-between">
                            <div>
                                <span class="text-[10px] text-slate-400 block">Total Gasto</span>
                                <span class="text-base font-black text-emerald-400 font-display">
                                    {{ formatCurrency(m.total_spent) }}
                                </span>
                            </div>

                            <button
                                @click="selectedMerchant = m.merchant_name; applyFilters(); activeTab = 'history'"
                                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-emerald-500/20 text-xs font-bold text-slate-200 hover:text-emerald-300 transition-colors cursor-pointer"
                            >
                                Ver Notas →
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Receipt Detail Modal (Itens & Produtos sem reter imagem) -->
            <ReceiptDetailModal 
                :is-open="isDetailModalOpen"
                :scan="selectedScan"
                @close="isDetailModalOpen = false"
            />

        </div>

        <!-- Scanner Modal -->
        <NativeScannerModal 
            :is-open="isScannerModalOpen"
            @close="isScannerModalOpen = false"
            @captured="handleScanCaptured"
        />

        <!-- Confirm Delete Modal -->
        <PremiumAlertModal 
            :is-open="confirmDeleteModal.isOpen"
            title="Excluir Comprovante"
            message="Tem certeza que deseja remover este comprovante? O lançamento financeiro correspondente não será apagado caso já tenha sido conciliado."
            type="warning"
            confirm-text="Sim, Excluir"
            :show-cancel="true"
            cancel-text="Cancelar"
            @confirm="executeDeleteScan"
            @close="confirmDeleteModal.isOpen = false"
        />
    </AppLayout>
</template>
