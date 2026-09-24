<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    FileUp, 
    UploadCloud, 
    Loader2, 
    CheckCircle2, 
    AlertTriangle, 
    ChevronDown, 
    ChevronUp, 
    FileText, 
    Files,
    Clock,
    Building2
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    accounts: Array,
    imports: Array,
    quota: Object,
});

const fileInput = ref(null);
const selectedAccount = ref(props.accounts?.[0]?.id || null);
const isUploading = ref(false);
const uploadMessage = ref(null);
const isSuccess = ref(false);
const isDragging = ref(false);
const batchReport = ref(null);
const showBatchDetails = ref(false);

const triggerFileInput = () => {
    fileInput.value?.click();
};

const handleFileInputChange = (event) => {
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

const processFiles = async (files) => {
    if (!files || files.length === 0) return;

    isUploading.value = true;
    batchReport.value = null;
    showBatchDetails.value = false;

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

        if (response.data.is_batch) {
            batchReport.value = response.data;
            showBatchDetails.value = response.data.failed_files > 0;
        }

        router.reload();
    } catch (err) {
        isSuccess.value = false;
        uploadMessage.value = err.response?.data?.message || 'Erro ao importar arquivo(s). Verifique o formato do extrato.';
    } finally {
        isUploading.value = false;
        if (fileInput.value) fileInput.value.value = '';
    }
};
</script>

<template>
    <Head title="Extratos & Bancos" />

    <AppLayout :user="user">
        <div class="space-y-8 pb-16">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                        <FileUp class="w-7 h-7 text-emerald-400" />
                        Importador Universal de Extratos
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Suba um ou múltiplos extratos (.CSV, .OFX, .TXT) com detecção inteligente de bancos e motor anti-duplicidade.
                    </p>
                </div>

                <!-- Quota Badge -->
                <div v-if="quota" class="px-4 py-2.5 rounded-2xl bg-slate-900 border border-slate-800 flex items-center gap-3">
                    <div class="space-y-0.5">
                        <div class="text-[10px] uppercase font-bold text-slate-400">
                            {{ quota.is_unlimited ? 'Plano Pro ⚡' : 'Cota Gratuita' }}
                        </div>
                        <div class="text-xs font-black text-white">
                            <span v-if="quota.is_unlimited" class="text-emerald-400">Envios Ilimitados</span>
                            <span v-else :class="quota.remaining === 0 ? 'text-rose-400' : 'text-emerald-400'">
                                {{ quota.remaining }} restantes / {{ quota.limit }} mês
                            </span>
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
            </div>

            <!-- Upload Area Panel -->
            <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 mb-6">
                    <div>
                        <h2 class="text-base font-bold text-white flex items-center gap-2">
                            <UploadCloud class="w-5 h-5 text-emerald-400" />
                            Área de Envio de Extratos Bancários
                        </h2>
                        <p class="text-xs text-slate-400 mt-0.5">
                            Reconhece Banco do Brasil, Nubank, Itaú, Bradesco, Inter, Caixa, OFX ou qualquer planilha CSV.
                        </p>
                    </div>

                    <!-- Destination Account Selector -->
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
                    @change="handleFileInputChange" 
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
                    class="border-2 border-dashed rounded-2xl p-10 text-center cursor-pointer transition-all duration-200 group"
                >
                    <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 flex items-center justify-center mx-auto mb-3 group-hover:scale-110 transition-transform">
                        <Loader2 v-if="isUploading" class="w-7 h-7 text-emerald-400 animate-spin" />
                        <Files v-else-if="isDragging" class="w-7 h-7 text-emerald-400" />
                        <FileUp v-else class="w-7 h-7 text-emerald-400" />
                    </div>

                    <h3 class="text-base font-bold text-white">
                        {{ isUploading ? 'Lendo, convertendo e classificando extrato(s)...' : (isDragging ? 'Solte os arquivos aqui para importar em lote!' : 'Clique ou arraste um ou vários extratos bancários aqui') }}
                    </h3>
                    <p class="text-xs text-slate-400 mt-1 max-w-md mx-auto">
                        Suporta múltiplos arquivos .CSV, .OFX ou .TXT (importação simultânea com descarte automático de duplicados).
                    </p>
                </div>

                <!-- Upload message alert -->
                <div 
                    v-if="uploadMessage" 
                    class="mt-4 p-4 rounded-2xl text-xs flex items-center justify-between border"
                    :class="isSuccess ? 'bg-emerald-500/15 border-emerald-500/30 text-emerald-200' : 'bg-rose-500/15 border-rose-500/30 text-rose-200'"
                >
                    <div class="flex items-center gap-2.5">
                        <CheckCircle2 v-if="isSuccess" class="w-4 h-4 text-emerald-400 shrink-0" />
                        <AlertTriangle v-else class="w-4 h-4 text-rose-400 shrink-0" />
                        <span>{{ uploadMessage }}</span>
                    </div>

                    <button 
                        v-if="batchReport?.files_detail?.length > 1" 
                        @click="showBatchDetails = !showBatchDetails" 
                        type="button" 
                        class="inline-flex items-center gap-1 font-semibold underline text-xs ml-3 hover:text-white shrink-0"
                    >
                        {{ showBatchDetails ? 'Ocultar detalhes' : 'Ver detalhes por arquivo' }}
                        <ChevronDown v-if="!showBatchDetails" class="w-3.5 h-3.5" />
                        <ChevronUp v-else class="w-3.5 h-3.5" />
                    </button>
                </div>

                <!-- Collapsible Batch Details List -->
                <div 
                    v-if="batchReport && showBatchDetails && batchReport.files_detail?.length" 
                    class="mt-3 p-4 rounded-2xl bg-slate-900/80 border border-slate-800 text-xs space-y-2.5 animate-fadeIn"
                >
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800 text-slate-400 font-semibold text-[11px] uppercase tracking-wider">
                        <span>Arquivo</span>
                        <span>Resultado da Leitura</span>
                    </div>
                    <div 
                        v-for="(detail, idx) in batchReport.files_detail" 
                        :key="idx" 
                        class="flex items-center justify-between py-2 border-b border-slate-800/50 last:border-0"
                    >
                        <div class="flex items-center gap-2.5 min-w-0 pr-4">
                            <FileText class="w-4 h-4 text-slate-400 shrink-0" />
                            <div class="min-w-0">
                                <p class="truncate font-medium text-slate-200">{{ detail.file_name }}</p>
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

            <!-- Import History Section -->
            <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800">
                <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                    <Clock class="w-4 h-4 text-emerald-400" />
                    <span>Histórico Recente de Extratos Importados</span>
                </h3>

                <div v-if="imports && imports.length > 0" class="divide-y divide-slate-800/60">
                    <div 
                        v-for="imp in imports" 
                        :key="imp.id"
                        class="py-3.5 flex flex-col sm:flex-row sm:items-center justify-between gap-3 text-xs"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 rounded-xl bg-slate-800 flex items-center justify-center text-slate-300">
                                <FileText class="w-4 h-4" />
                            </div>
                            <div>
                                <p class="font-bold text-white text-sm">{{ imp.file_name }}</p>
                                <div class="flex items-center gap-2 text-slate-400 text-[11px] mt-0.5">
                                    <span>{{ imp.detected_bank }}</span>
                                    <span>•</span>
                                    <span>{{ imp.account?.name || 'Conta' }}</span>
                                </div>
                            </div>
                        </div>

                        <div class="flex items-center gap-3 self-end sm:self-center">
                            <span class="text-emerald-400 font-semibold bg-emerald-500/10 border border-emerald-500/20 px-2.5 py-1 rounded-lg">
                                +{{ imp.imported_records }} lançamentos
                            </span>
                            <span v-if="imp.skipped_records > 0" class="text-slate-400 bg-slate-800/60 px-2 py-1 rounded-lg">
                                {{ imp.skipped_records }} ignorados
                            </span>
                        </div>
                    </div>
                </div>

                <div v-else class="text-center py-8 text-slate-500 text-xs">
                    Nenhum extrato importado até o momento. Utilize a área de envio acima.
                </div>
            </div>

        </div>
    </AppLayout>
</template>
