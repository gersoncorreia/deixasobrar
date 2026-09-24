<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import NativeScannerModal from '@/Components/Scanner/NativeScannerModal.vue';
import ReceiptReviewCard from '@/Components/Scanner/ReceiptReviewCard.vue';
import ReconciliationMatchCard from '@/Components/Scanner/ReconciliationMatchCard.vue';
import ReceiptDetailModal from '@/Components/Scanner/ReceiptDetailModal.vue';
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
    Receipt
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    scans: Object, // paginated
    accounts: Array,
    categories: Array,
    quota: Object,
});

const { formatCurrency } = useCurrencyFormat();

const isScannerModalOpen = ref(false);
const activeScan = ref(null);
const matchCandidates = ref([]);
const isDetailModalOpen = ref(false);
const selectedScan = ref(null);

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

const deleteScan = (scan) => {
    if (confirm('Tem certeza que deseja excluir este registro de comprovante?')) {
        router.delete(`/scanner/${scan.id}`, { preserveScroll: true });
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

            <!-- History of Scanned Receipts Table -->
            <div class="glass-panel rounded-2xl border border-slate-800 overflow-hidden">
                <div class="p-5 border-b border-slate-800 flex items-center justify-between">
                    <div>
                        <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                            <FileText class="w-4 h-4 text-emerald-400" />
                            <span>Comprovantes & Notas Capturadas</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Histórico auditável com foto original e conferência</p>
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
    </AppLayout>
</template>
