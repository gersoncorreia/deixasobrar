<script setup>
import { ref } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    UploadCloud, 
    Loader2, 
    Files, 
    FileUp, 
    CheckCircle2, 
    AlertTriangle, 
    ChevronDown, 
    ChevronUp, 
    ArrowRight, 
    FileText,
    Calendar,
    Wallet,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    accounts: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['uploaded']);
const { formatCurrency } = useCurrencyFormat();

// State
const fileInput = ref(null);
const selectedAccount = ref(props.accounts?.[0]?.id || null);
const isUploading = ref(false);
const uploadMessage = ref(null);
const isSuccess = ref(false);
const isDragging = ref(false);
const batchReport = ref(null);
const showBatchDetails = ref(false);
const isReconciling = ref(false);
const reconciledSuccess = ref(false);

const handleFileUpload = (event) => {
    const files = event.target.files;
    if (files && files.length > 0) {
        processFiles(files);
    }
};

const handleFileDrop = (event) => {
    isDragging.value = false;
    const files = event.dataTransfer.files;
    if (files && files.length > 0) {
        processFiles(files);
    }
};

const triggerFileInput = () => {
    fileInput.value?.click();
};

const processFiles = async (files) => {
    if (!files || files.length === 0) return;

    isUploading.value = true;
    batchReport.value = null;
    showBatchDetails.value = false;
    reconciledSuccess.value = false;

    const fileCount = files.length;
    uploadMessage.value = fileCount > 1 
        ? `Processando ${fileCount} extratos em lote...` 
        : 'Processando e convertendo extrato...';

    const formData = new FormData();
    for (let i = 0; i < files.length; i++) {
        formData.append('statement_files[]', files[i]);
    }
    if (selectedAccount.value) {
        formData.append('account_id', selectedAccount.value);
    }

    try {
        const response = await window.axios.post('/extratos/upload', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        isSuccess.value = response.data.success;
        uploadMessage.value = response.data.message;
        batchReport.value = response.data;

        if (response.data.is_batch) {
            showBatchDetails.value = response.data.failed_files > 0;
        }

        emit('uploaded', response.data);
    } catch (err) {
        isSuccess.value = false;
        uploadMessage.value = err.response?.data?.message || 'Erro ao importar arquivo(s). Verifique o formato do extrato.';
    } finally {
        isUploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
};

// 1-Click Balance Reconciliation
const reconcileBalance = async (accountId, targetBalance) => {
    if (!accountId || targetBalance === null || targetBalance === undefined) return;

    isReconciling.value = true;
    try {
        await window.axios.put(`/contas/${accountId}`, {
            current_balance: targetBalance,
        });
        reconciledSuccess.value = true;
        router.reload({ only: ['safeToSpend', 'accounts'] });
    } catch (err) {
        console.error('Falha ao reconciliar saldo da conta', err);
    } finally {
        isReconciling.value = false;
    }
};

const formatDateBr = (dStr) => {
    if (!dStr) return '';
    try {
        const [year, month, day] = dStr.split('-');
        return `${day}/${month}/${year}`;
    } catch {
        return dStr;
    }
};
</script>

<template>
    <div id="importar" class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-800">
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
            <div>
                <h2 class="text-lg font-bold text-white flex items-center gap-2">
                    <UploadCloud class="w-5 h-5 text-emerald-400" />
                    Importador Universal de Extratos Bancários
                </h2>
                <p class="text-xs text-slate-400 mt-0.5">
                    Reconhece Banco do Brasil, Nubank, Itaú, Bradesco, Inter, Caixa, OFX ou qualquer planilha CSV.
                </p>
            </div>

            <!-- Account Selector -->
            <div class="flex items-center gap-2 text-xs">
                <span class="text-slate-400">Conta de Destino:</span>
                <select 
                    v-model="selectedAccount"
                    class="bg-slate-900 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                >
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                        {{ acc.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Hidden file input -->
        <input 
            type="file" 
            ref="fileInput" 
            @change="handleFileUpload" 
            accept=".csv, .ofx, .qfx, .txt" 
            multiple
            class="hidden" 
        />

        <!-- Dropzone Box -->
        <div 
            @click="triggerFileInput"
            @dragover.prevent="isDragging = true"
            @dragleave.prevent="isDragging = false"
            @drop.prevent="handleFileDrop"
            :class="isDragging ? 'border-emerald-400 bg-emerald-500/10 scale-[1.01]' : 'border-slate-700 hover:border-emerald-400/80 bg-slate-950/40 hover:bg-slate-900/40'"
            class="border-2 border-dashed rounded-2xl p-8 text-center cursor-pointer transition-all duration-200 group"
        >
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                <Loader2 v-if="isUploading" class="w-6 h-6 text-emerald-400 animate-spin" />
                <Files v-else-if="isDragging" class="w-6 h-6 text-emerald-400" />
                <FileUp v-else class="w-6 h-6 text-emerald-400" />
            </div>

            <h3 class="text-sm font-bold text-white">
                {{ isUploading ? 'Lendo, convertendo e classificando extrato(s)...' : (isDragging ? 'Solte os arquivos aqui para importar em lote!' : 'Clique ou arraste um ou vários extratos bancários aqui') }}
            </h3>
            <p class="text-xs text-slate-400 mt-1">
                Suporta múltiplos arquivos .CSV, .OFX ou .TXT (descarte automático de linhas de saldo e duplicadas)
            </p>
        </div>

        <!-- Upload message alert with action to view transactions -->
        <div 
            v-if="uploadMessage" 
            class="mt-4 p-4 rounded-2xl text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-3 border"
            :class="isSuccess ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-200' : 'bg-rose-500/15 border-rose-500/30 text-rose-200'"
        >
            <div class="flex items-center gap-2.5">
                <CheckCircle2 v-if="isSuccess" class="w-4 h-4 text-emerald-400 shrink-0" />
                <AlertTriangle v-else class="w-4 h-4 text-rose-400 shrink-0" />
                <span class="font-medium">{{ uploadMessage }}</span>
            </div>

            <div class="flex items-center gap-2 shrink-0">
                <button 
                    v-if="batchReport?.files_detail?.length > 1" 
                    @click="showBatchDetails = !showBatchDetails" 
                    type="button" 
                    class="inline-flex items-center gap-1 font-semibold underline text-xs hover:text-white"
                >
                    {{ showBatchDetails ? 'Ocultar detalhes' : 'Ver detalhes por arquivo' }}
                    <ChevronDown v-if="!showBatchDetails" class="w-3.5 h-3.5" />
                    <ChevronUp v-else class="w-3.5 h-3.5" />
                </button>

                <Link 
                    v-if="isSuccess"
                    href="/transacoes"
                    class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-all shadow-md shadow-emerald-500/20"
                >
                    <span>Ver Lançamentos Importados</span>
                    <ArrowRight class="w-3.5 h-3.5" />
                </Link>
            </div>
        </div>

        <!-- Period Analysis & Reconciliation Card -->
        <div 
            v-if="isSuccess && batchReport && (batchReport.period_start || batchReport.detected_balance !== null)" 
            class="mt-4 p-4 sm:p-5 rounded-2xl bg-gradient-to-r from-slate-900/90 via-slate-900/70 to-slate-950 border border-emerald-500/25 text-xs flex flex-col sm:flex-row sm:items-center justify-between gap-4 animate-fadeIn"
        >
            <div class="space-y-1">
                <div class="flex items-center gap-2 text-emerald-400 font-bold text-[11px] uppercase tracking-wider">
                    <Sparkles class="w-3.5 h-3.5" />
                    <span>Análise do Extrato & Reconciliação de Saldo</span>
                </div>
                
                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-slate-300">
                    <span v-if="batchReport.period_start && batchReport.period_end" class="inline-flex items-center gap-1">
                        <Calendar class="w-3.5 h-3.5 text-slate-400" />
                        Período: <strong class="text-white">{{ formatDateBr(batchReport.period_start) }}</strong> até <strong class="text-white">{{ formatDateBr(batchReport.period_end) }}</strong>
                    </span>

                    <span v-if="batchReport.detected_balance !== null" class="inline-flex items-center gap-1">
                        <Wallet class="w-3.5 h-3.5 text-emerald-400" />
                        Saldo final no extrato: <strong class="text-emerald-400 font-display">{{ formatCurrency(batchReport.detected_balance) }}</strong>
                    </span>
                </div>
            </div>

            <!-- Reconciliation Action -->
            <div v-if="batchReport.detected_balance !== null && batchReport.account_id" class="shrink-0 flex items-center gap-2">
                <span v-if="reconciledSuccess" class="inline-flex items-center gap-1 text-emerald-400 font-bold bg-emerald-500/10 border border-emerald-500/25 px-3 py-1.5 rounded-xl">
                    <CheckCircle2 class="w-4 h-4" />
                    Saldo Alinhado!
                </span>
                <button
                    v-else
                    @click="reconcileBalance(batchReport.account_id, batchReport.detected_balance)"
                    type="button"
                    :disabled="isReconciling"
                    class="inline-flex items-center gap-1.5 px-3.5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-amber-300 hover:text-white border border-slate-700 font-bold text-xs transition-all shadow-sm"
                >
                    <Loader2 v-if="isReconciling" class="w-3.5 h-3.5 animate-spin" />
                    <Wallet v-else class="w-3.5 h-3.5 text-amber-400" />
                    <span>Alinhar saldo da conta para {{ formatCurrency(batchReport.detected_balance) }}</span>
                </button>
            </div>
        </div>

        <!-- Collapsible Batch Details List -->
        <div 
            v-if="batchReport && showBatchDetails && batchReport.files_detail?.length" 
            class="mt-3 p-4 rounded-xl bg-slate-900/80 border border-slate-800 text-xs space-y-2.5 animate-fadeIn"
        >
            <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-400 font-semibold text-[11px] uppercase tracking-wider">
                <span>Arquivo</span>
                <span>Resultado da Leitura</span>
            </div>
            <div 
                v-for="(detail, idx) in batchReport.files_detail" 
                :key="idx" 
                class="flex items-center justify-between py-1.5 border-b border-slate-800/50 last:border-0"
            >
                <div class="flex items-center gap-2 min-w-0 pr-4">
                    <FileText class="w-4 h-4 text-slate-400 shrink-0" />
                    <div class="min-w-0">
                        <p class="truncate font-medium text-slate-200">{{ detail.file_name || detail.file }}</p>
                        <span v-if="detail.detected_bank" class="text-[10px] text-slate-500 font-mono">{{ detail.detected_bank }}</span>
                    </div>
                </div>
                <div class="flex items-center gap-2 shrink-0">
                    <span 
                        v-if="detail.success" 
                        class="inline-flex items-center gap-1 text-[11px] text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-2 py-0.5 rounded-lg"
                    >
                        <CheckCircle2 class="w-3 h-3" />
                        {{ detail.imported }} novos ({{ detail.skipped }} duplicados)
                    </span>
                    <span 
                        v-else 
                        class="inline-flex items-center gap-1 text-[11px] text-rose-400 bg-rose-500/10 border border-rose-500/20 px-2 py-0.5 rounded-lg"
                        :title="detail.message || detail.error"
                    >
                        <AlertTriangle class="w-3 h-3" />
                        {{ detail.message || detail.error || 'Erro na leitura' }}
                    </span>
                </div>
            </div>
        </div>
    </div>
</template>
