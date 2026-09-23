<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Sliders, 
    Save, 
    Globe, 
    Settings, 
    ShieldCheck, 
    Check,
    CreditCard,
    Sparkles,
    Cpu,
    Key,
    Eye,
    EyeOff,
    CheckCircle2,
    XCircle,
    Loader2,
    Zap
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    adminUser: Object,
    settings: Object,
});

const form = ref({ 
    system_gemini_model: 'gemini-3.6-flash',
    system_gemini_api_key: '',
    asaas_environment: 'sandbox',
    asaas_api_key: '',
    asaas_webhook_token: '',
    ...props.settings 
});
const isSaving = ref(false);
const showApiKey = ref(false);
const showAsaasKey = ref(false);
const showWebhookToken = ref(false);

const isTestingAi = ref(false);
const aiTestResult = ref(null);

const isTestingAsaas = ref(false);
const asaasTestResult = ref(null);

const testAiConnection = async () => {
    isTestingAi.value = true;
    aiTestResult.value = null;
    try {
        const response = await axios.post('/admin/configuracoes/test-ai', {
            api_key: form.value.system_gemini_api_key,
            model: form.value.system_gemini_model
        });
        aiTestResult.value = response.data;
    } catch (err) {
        aiTestResult.value = {
            success: false,
            message: err.response?.data?.message || err.message || 'Falha ao conectar com o serviço de IA.',
            model: form.value.system_gemini_model
        };
    } finally {
        isTestingAi.value = false;
    }
};

const testAsaasConnection = async () => {
    isTestingAsaas.value = true;
    asaasTestResult.value = null;
    try {
        const response = await axios.post('/admin/configuracoes/test-asaas', {
            api_key: form.value.asaas_api_key,
            environment: form.value.asaas_environment
        });
        asaasTestResult.value = response.data;
    } catch (err) {
        asaasTestResult.value = {
            success: false,
            message: err.response?.data?.message || err.message || 'Falha ao conectar com a API do Asaas.',
            environment: form.value.asaas_environment
        };
    } finally {
        isTestingAsaas.value = false;
    }
};

const availableGeminiModels = [
    { value: 'gemini-3.6-flash', label: 'Gemini 3.6 Flash (Recomendado - Ultra-rápido, Atual & Suportado)', badge: 'Oficial' },
    { value: 'gemini-3.7-flash', label: 'Gemini 3.7 Flash (Nova Geração - Alta Precisão Visual)', badge: 'Nova Geração' },
    { value: 'gemini-3.8-flash', label: 'Gemini 3.8 Flash (Última Versão Google 2026)', badge: 'Mais Recente' },
    { value: 'gemini-3.1-pro-preview', label: 'Gemini 3.1 Pro Preview (Raciocínio Profundo & OCR Complexo)', badge: 'Avançado' },
];

// Dynamic Tabs definition for easy scaling
const tabs = [
    { id: 'site', label: 'Landing Page & Prova Social', icon: Globe, badge: 'Home' },
    { id: 'system', label: 'Parâmetros Padrão do SaaS & IA', icon: Settings, badge: 'Core' },
    { id: 'quotas', label: 'Limites & Cotas de Planos', icon: Sliders, badge: 'Cotas' },
    { id: 'security', label: 'Modos Operacionais & Segurança', icon: ShieldCheck, badge: 'Segurança' },
    { id: 'gateway', label: 'Gateway de Pagamentos (Asaas)', icon: CreditCard, badge: 'Checkout' },
];

const activeTab = ref('site');

const submitSettings = () => {
    isSaving.value = true;
    router.post('/admin/configuracoes', {
        settings: form.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSaving.value = false;
        }
    });
};
</script>

<template>
    <Head title="Master Admin - Configurações Globais" />

    <AdminLayout :admin-user="adminUser">
        <div class="space-y-6 pb-16 w-full">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Configurações Globais do Sistema & Site
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Gerencie textos da Landing Page, parâmetros padrão, integrações e segurança operacional da plataforma.
                    </p>
                </div>

                <button 
                    @click="submitSettings"
                    :disabled="isSaving"
                    class="btn-shimmer inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-purple-600/30 transition-all self-start sm:self-auto cursor-pointer"
                >
                    <Save class="w-4 h-4" />
                    <span>{{ isSaving ? 'Salvando...' : 'Salvar Alterações' }}</span>
                </button>
            </div>

            <!-- Tabs Navigation Bar Dinâmica e Flexível -->
            <div class="flex items-center gap-2 p-1.5 rounded-2xl bg-slate-900/90 border border-slate-800 backdrop-blur-md overflow-x-auto scrollbar-none w-full">
                <button
                    v-for="tab in tabs"
                    :key="tab.id"
                    type="button"
                    @click="activeTab = tab.id"
                    class="flex items-center gap-2.5 px-4 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all whitespace-nowrap cursor-pointer shrink-0"
                    :class="activeTab === tab.id 
                        ? 'bg-purple-600/20 text-purple-300 border border-purple-500/40 shadow-sm shadow-purple-500/10' 
                        : 'text-slate-400 hover:text-slate-200 hover:bg-slate-800/60 border border-transparent'"
                >
                    <component :is="tab.icon" class="w-4 h-4 shrink-0" />
                    <span>{{ tab.label }}</span>
                </button>
            </div>

            <!-- Form Panels -->
            <form @submit.prevent="submitSettings" class="space-y-6">
                
                <!-- ABA 1: Landing Page & Prova Social -->
                <div v-show="activeTab === 'site'" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <Globe class="w-4 h-4 text-purple-400" />
                                <span>Apresentação & Marketing na Home</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Esses dados alteram em tempo real a página inicial pública do DeixaSobrar.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Headline Principal da Home</label>
                            <input 
                                v-model="form.landing_headline"
                                type="text"
                                placeholder="Não deixe o mês engolir o seu dinheiro..."
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500 transition-colors"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Subtítulo Explicativo</label>
                            <textarea 
                                v-model="form.landing_subheadline"
                                rows="2"
                                placeholder="O primeiro SaaS com cálculo reverso de ciclo salarial..."
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500 transition-colors"
                            ></textarea>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 pt-2">
                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1.5">Estatística: Usuários Ativos</label>
                            <input 
                                v-model="form.landing_stat_users"
                                type="text"
                                placeholder="14.200+"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1.5">Estatística: Total Economizado</label>
                            <input 
                                v-model="form.landing_stat_saved"
                                type="text"
                                placeholder="R$ 4.8M+"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                        </div>

                        <div>
                            <label class="block text-[11px] font-bold text-slate-300 mb-1.5">Estatística: Teto Médio Diário</label>
                            <input 
                                v-model="form.landing_stat_ceiling"
                                type="text"
                                placeholder="R$ 52,40"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                        </div>
                    </div>
                </div>

                <!-- ABA 2: Parâmetros Padrão do SaaS -->
                <div v-show="activeTab === 'system'" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <Settings class="w-4 h-4 text-emerald-400" />
                                <span>Parâmetros Financeiros Iniciais</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Configurações atribuídas por padrão a novas contas de clientes cadastradas.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Dia Padrão de Pagamento</label>
                            <input 
                                v-model="form.system_default_payday"
                                type="number"
                                min="1"
                                max="31"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5">Dia de fechamento do ciclo (ex: dia 5).</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Reserva Padrão (R$)</label>
                            <input 
                                v-model="form.system_default_reserve"
                                type="number"
                                step="0.01"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5">Colchão financeiro emergencial inicial.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">E-mail Oficial de Suporte</label>
                            <input 
                                v-model="form.system_support_email"
                                type="email"
                                placeholder="suporte@deixasobrar.com.br"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                            <p class="text-[11px] text-slate-400 mt-1.5">Exibido aos assinantes para contato.</p>
                        </div>
                    </div>

                    <!-- AI Vision Model & API Key Configuration -->
                    <div class="pt-5 border-t border-slate-800 space-y-4">
                        <div class="flex items-center justify-between">
                            <div class="flex items-center gap-2">
                                <Sparkles class="w-4 h-4 text-purple-400" />
                                <label class="text-xs font-bold text-white uppercase tracking-wider">Inteligência Artificial & Leitura OCR (Google Gemini)</label>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-semibold bg-purple-500/10 text-purple-300 border border-purple-500/20">
                                <Zap class="w-3 h-3 text-purple-400" />
                                OCR Nativo
                            </span>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1.5">Modelo Google Gemini</label>
                                <select 
                                    v-model="form.system_gemini_model"
                                    class="w-full px-3.5 py-2.5 bg-slate-900 border border-purple-500/50 rounded-xl text-xs text-white focus:outline-none focus:border-purple-400 font-semibold cursor-pointer"
                                >
                                    <option v-for="m in availableGeminiModels" :key="m.value" :value="m.value">
                                        {{ m.label }}
                                    </option>
                                </select>
                                <p class="text-[11px] text-slate-400 mt-1.5">
                                    Versão que processará as notas fiscais e comprovantes no app.
                                </p>
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1.5">
                                    <label class="block text-xs font-bold text-slate-300">Google Gemini API Key</label>
                                    <button 
                                        type="button" 
                                        @click="showApiKey = !showApiKey" 
                                        class="text-[11px] text-purple-400 hover:text-purple-300 flex items-center gap-1 cursor-pointer"
                                    >
                                        <component :is="showApiKey ? EyeOff : Eye" class="w-3 h-3" />
                                        <span>{{ showApiKey ? 'Ocultar' : 'Exibir' }}</span>
                                    </button>
                                </div>
                                <div class="relative">
                                    <input 
                                        v-model="form.system_gemini_api_key"
                                        :type="showApiKey ? 'text' : 'password'"
                                        placeholder="AIzaSy... (ou deixe vazio para usar o .env)"
                                        class="w-full pl-9 pr-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
                                    />
                                    <Key class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none" />
                                </div>
                                <p class="text-[11px] text-slate-400 mt-1.5">
                                    Pode ser gravada aqui no banco ou configurada via <code class="text-purple-300 bg-purple-950/40 px-1 py-0.5 rounded">GEMINI_API_KEY</code> no seu arquivo <code class="text-slate-300">.env</code>.
                                </p>
                            </div>
                        </div>

                        <!-- Botão de Teste de Conexão com IA & Feedback em Tempo Real -->
                        <div class="p-4 rounded-2xl bg-purple-950/20 border border-purple-900/40 space-y-3">
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                                <div>
                                    <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                        <Cpu class="w-3.5 h-3.5 text-purple-400" />
                                        Diagnóstico de Conectividade com a IA
                                    </h4>
                                    <p class="text-[11px] text-slate-400 mt-0.5">
                                        Dispara um ping leve de validação diretamente contra a API do Google para certificar a chave e o modelo.
                                    </p>
                                </div>
                                <button
                                    type="button"
                                    @click="testAiConnection"
                                    :disabled="isTestingAi"
                                    class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-purple-600/20 transition-all whitespace-nowrap cursor-pointer"
                                >
                                    <Loader2 v-if="isTestingAi" class="w-3.5 h-3.5 animate-spin" />
                                    <Zap v-else class="w-3.5 h-3.5" />
                                    <span>{{ isTestingAi ? 'Testando Conexão...' : 'Testar Conexão com IA ⚡' }}</span>
                                </button>
                            </div>

                            <!-- Alert de Resultado do Teste -->
                            <div 
                                v-if="aiTestResult"
                                class="p-3.5 rounded-xl text-xs flex items-start gap-2.5 transition-all animate-in fade-in"
                                :class="aiTestResult.success 
                                    ? 'bg-emerald-950/40 border border-emerald-500/40 text-emerald-200' 
                                    : 'bg-rose-950/40 border border-rose-500/40 text-rose-200'"
                            >
                                <CheckCircle2 v-if="aiTestResult.success" class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                                <XCircle v-else class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" />
                                <div class="space-y-1">
                                    <div class="font-bold flex items-center gap-2">
                                        <span>{{ aiTestResult.success ? 'Conexão Bem-Sucedida!' : 'Falha na Conexão' }}</span>
                                        <span class="text-[10px] px-2 py-0.2 rounded-full font-mono bg-black/40 border border-current">
                                            {{ aiTestResult.model }}
                                        </span>
                                    </div>
                                    <p class="text-[11px] opacity-90 leading-relaxed">{{ aiTestResult.message }}</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA: Limites & Cotas de Planos -->
                <div v-show="activeTab === 'quotas'" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <Sliders class="w-4 h-4 text-purple-400" />
                                <span>Cotas de Uso & Controle de Custos de IA</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Configure o limite mensal de consumo de inteligência artificial e extratos para cada modalidade de plano.</p>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Limite Free OCR -->
                        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-300">Plano Gratuito: Scanner IA</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Mensal</span>
                            </div>
                            <input 
                                v-model="form.plan_free_ocr_limit"
                                type="number"
                                min="0"
                                max="50"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-bold text-white focus:outline-none focus:border-purple-500"
                            />
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Leituras gratuitas por mês por usuário para degustação antes de exigir upgrade.
                            </p>
                        </div>

                        <!-- Limite Pro OCR -->
                        <div class="p-5 rounded-2xl bg-purple-950/20 border border-purple-500/30 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-purple-300">Plano Pro: Scanner IA</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-purple-500/20 text-purple-300">Mensal</span>
                            </div>
                            <input 
                                v-model="form.plan_pro_ocr_limit"
                                type="number"
                                min="10"
                                max="1000"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-purple-500/50 rounded-xl text-sm font-bold text-white focus:outline-none focus:border-purple-400"
                            />
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Teto mensal de notas fiscais lidas por assinantes Pro (protege seus custos de API).
                            </p>
                        </div>

                        <!-- Limite Free Extratos -->
                        <div class="p-5 rounded-2xl bg-slate-900/60 border border-slate-800 space-y-3">
                            <div class="flex items-center justify-between">
                                <span class="text-xs font-bold text-slate-300">Plano Gratuito: Extratos</span>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 text-slate-400">Mensal</span>
                            </div>
                            <input 
                                v-model="form.plan_free_statement_limit"
                                type="number"
                                min="0"
                                max="20"
                                class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-sm font-bold text-white focus:outline-none focus:border-purple-500"
                            />
                            <p class="text-[11px] text-slate-400 leading-relaxed">
                                Importações de extrato (OFX/CSV) permitidas para contas Free. (No Pro é ilimitado).
                            </p>
                        </div>
                    </div>

                    <!-- Enriquecimento de Extratos com IA (Classificação & Vazamentos) -->
                    <div class="p-5 rounded-2xl bg-emerald-950/20 border border-emerald-500/30 space-y-4">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-emerald-300 flex items-center gap-2">
                                    <Sparkles class="w-4 h-4 text-emerald-400" />
                                    <span>Classificação Inteligente de Extratos com IA & Detecção de Vazamentos</span>
                                </h4>
                                <p class="text-[11px] text-slate-300 mt-1">
                                    Decifra descrições bancárias crípticas e aponta automaticamente se é tarifa oculta, aposta/bet ou gasto impulsivo via Gemini Flash com cache inteligente de custo zero.
                                </p>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer shrink-0">
                                <input 
                                    type="checkbox" 
                                    v-model="form.enable_ai_statement_classification" 
                                    true-value="1" 
                                    false-value="0" 
                                    class="sr-only peer"
                                />
                                <div class="w-11 h-6 bg-slate-800 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                            </label>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-3 border-t border-emerald-500/20">
                            <div>
                                <label class="text-[11px] font-bold text-slate-300 block mb-1">Cota Free (Extratos com IA/mês):</label>
                                <input 
                                    v-model="form.plan_free_ai_statement_limit"
                                    type="number"
                                    min="0"
                                    max="10"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-emerald-500"
                                />
                                <span class="text-[10px] text-slate-400 mt-1 block">Degustação para converter usuários gratuitos.</span>
                            </div>

                            <div>
                                <label class="text-[11px] font-bold text-emerald-300 block mb-1">Cota Pro (Extratos com IA/mês):</label>
                                <input 
                                    v-model="form.plan_pro_ai_statement_limit"
                                    type="number"
                                    min="5"
                                    max="200"
                                    class="w-full px-3 py-2 bg-slate-950 border border-emerald-500/40 rounded-xl text-xs font-bold text-white focus:outline-none focus:border-emerald-400"
                                />
                                <span class="text-[10px] text-slate-400 mt-1 block">Teto mensal seguro para assinantes Pro com lote máximo por importação.</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ABA 3: Modos Operacionais & Segurança -->
                <div v-show="activeTab === 'security'" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-5 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <ShieldCheck class="w-4 h-4 text-amber-400" />
                                <span>Operação, Cadastros & Manutenção</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Controles globais de acesso ao SaaS e comportamento do sistema.</p>
                        </div>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between p-4 rounded-2xl bg-slate-900/60 border border-slate-800 gap-4">
                        <div>
                            <h4 class="text-xs font-bold text-white">Permitir Novos Cadastros Públicos</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Se desativado, o formulário de registro público bloqueará novas inscrições.</p>
                        </div>
                        <select 
                            v-model="form.system_allow_new_registrations"
                            class="px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                        >
                            <option value="1">Ativado (Cadastros Abertos)</option>
                            <option value="0">Desativado (Fechado para Novos)</option>
                        </select>
                    </div>

                    <div class="flex flex-col sm:flex-row items-center justify-between p-4 rounded-2xl bg-slate-900/60 border border-slate-800 gap-4">
                        <div>
                            <h4 class="text-xs font-bold text-white">Modo de Manutenção Geral</h4>
                            <p class="text-[11px] text-slate-400 mt-0.5">Exibe aviso de manutenção aos clientes enquanto administradores continuam operando.</p>
                        </div>
                        <select 
                            v-model="form.system_maintenance_mode"
                            class="px-3.5 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                        >
                            <option value="0">Normal (Plataforma Online)</option>
                            <option value="1">Em Manutenção Preventiva</option>
                        </select>
                    </div>
                </div>

                <!-- ABA 4: Gateway de Pagamentos & Asaas -->
                <div v-show="activeTab === 'gateway'" class="glass-panel p-6 sm:p-8 rounded-3xl border border-slate-800 space-y-6 animate-in fade-in duration-200">
                    <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                        <div>
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <CreditCard class="w-4 h-4 text-emerald-400" />
                                <span>Integração Gateway de Cobrança (Asaas)</span>
                            </h3>
                            <p class="text-xs text-slate-400 mt-1">Gerencie chaves de API, ambiente (sandbox ou produção) e webhook de retorno de pagamentos.</p>
                        </div>
                        <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold"
                            :class="form.asaas_environment === 'production' ? 'bg-emerald-500/10 text-emerald-300 border border-emerald-500/30' : 'bg-amber-500/10 text-amber-300 border border-amber-500/30'">
                            <span class="w-2 h-2 rounded-full" :class="form.asaas_environment === 'production' ? 'bg-emerald-400' : 'bg-amber-400'"></span>
                            {{ form.asaas_environment === 'production' ? 'Modo Produção' : 'Modo Sandbox (Testes)' }}
                        </span>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1.5">Ambiente Asaas</label>
                            <select 
                                v-model="form.asaas_environment"
                                class="w-full px-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500 font-semibold cursor-pointer"
                            >
                                <option value="sandbox">Sandbox (Ambiente de Testes / Desenvolvimento)</option>
                                <option value="production">Produção (Cobranças Reais)</option>
                            </select>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                Em Sandbox as cobranças são fictícias e usam <code class="text-amber-300">sandbox.asaas.com</code>.
                            </p>
                        </div>

                        <div>
                            <div class="flex items-center justify-between mb-1.5">
                                <label class="block text-xs font-bold text-slate-300">Chave de API (Access Token Asaas)</label>
                                <button 
                                    type="button" 
                                    @click="showAsaasKey = !showAsaasKey" 
                                    class="text-[11px] text-purple-400 hover:text-purple-300 flex items-center gap-1 cursor-pointer"
                                >
                                    <component :is="showAsaasKey ? EyeOff : Eye" class="w-3 h-3" />
                                    <span>{{ showAsaasKey ? 'Ocultar' : 'Exibir' }}</span>
                                </button>
                            </div>
                            <div class="relative">
                                <input 
                                    v-model="form.asaas_api_key"
                                    :type="showAsaasKey ? 'text' : 'password'"
                                    placeholder="$aact_... (ou deixe vazio para usar o .env)"
                                    class="w-full pl-9 pr-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
                                />
                                <Key class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none" />
                            </div>
                            <p class="text-[11px] text-slate-400 mt-1.5">
                                Gerada no menu "Integrações > Chaves de API" no painel do Asaas.
                            </p>
                        </div>
                    </div>

                    <div>
                        <div class="flex items-center justify-between mb-1.5">
                            <label class="block text-xs font-bold text-slate-300">Token de Autenticação do Webhook (Opcional)</label>
                            <button 
                                type="button" 
                                @click="showWebhookToken = !showWebhookToken" 
                                class="text-[11px] text-purple-400 hover:text-purple-300 flex items-center gap-1 cursor-pointer"
                            >
                                <component :is="showWebhookToken ? EyeOff : Eye" class="w-3 h-3" />
                                <span>{{ showWebhookToken ? 'Ocultar' : 'Exibir' }}</span>
                            </button>
                        </div>
                        <div class="relative">
                            <input 
                                v-model="form.asaas_webhook_token"
                                :type="showWebhookToken ? 'text' : 'password'"
                                placeholder="Token customizado configurado no webhook do Asaas..."
                                class="w-full pl-9 pr-3.5 py-2.5 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
                            />
                            <ShieldCheck class="w-4 h-4 text-slate-500 absolute left-3 top-3 pointer-events-none" />
                        </div>
                        <p class="text-[11px] text-slate-400 mt-1.5">
                            URL do Webhook para colar no Asaas: <code class="text-emerald-300 bg-emerald-950/40 px-1.5 py-0.5 rounded font-mono text-[10px]">https://seu-dominio.com/api/webhooks/asaas</code>
                        </p>
                    </div>

                    <!-- Diagnóstico de Conectividade com Asaas -->
                    <div class="p-4 rounded-2xl bg-emerald-950/20 border border-emerald-900/40 space-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                            <div>
                                <h4 class="text-xs font-bold text-white flex items-center gap-1.5">
                                    <Zap class="w-3.5 h-3.5 text-emerald-400" />
                                    Diagnóstico de Conectividade com Asaas
                                </h4>
                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    Verifica em tempo real se a chave de API é válida e consulta o saldo da conta no Asaas.
                                </p>
                            </div>
                            <button
                                type="button"
                                @click="testAsaasConnection"
                                :disabled="isTestingAsaas"
                                class="inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-emerald-600 hover:bg-emerald-500 disabled:opacity-50 text-white font-bold text-xs shadow-md shadow-emerald-600/20 transition-all whitespace-nowrap cursor-pointer"
                            >
                                <Loader2 v-if="isTestingAsaas" class="w-3.5 h-3.5 animate-spin" />
                                <CreditCard v-else class="w-3.5 h-3.5" />
                                <span>{{ isTestingAsaas ? 'Testando Conexão...' : 'Testar Conexão com Asaas ⚡' }}</span>
                            </button>
                        </div>

                        <!-- Alert de Resultado do Teste Asaas -->
                        <div 
                            v-if="asaasTestResult"
                            class="p-3.5 rounded-xl text-xs flex items-start gap-2.5 transition-all animate-in fade-in"
                            :class="asaasTestResult.success 
                                ? 'bg-emerald-950/40 border border-emerald-500/40 text-emerald-200' 
                                : 'bg-rose-950/40 border border-rose-500/40 text-rose-200'"
                        >
                            <CheckCircle2 v-if="asaasTestResult.success" class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                            <XCircle v-else class="w-4 h-4 text-rose-400 shrink-0 mt-0.5" />
                            <div class="space-y-1">
                                <div class="font-bold flex items-center gap-2">
                                    <span>{{ asaasTestResult.success ? 'Conexão Estabelecida com Asaas!' : 'Falha na Conexão' }}</span>
                                    <span class="text-[10px] px-2 py-0.2 rounded-full font-mono bg-black/40 border border-current">
                                        {{ asaasTestResult.environment }}
                                    </span>
                                </div>
                                <p class="text-[11px] opacity-90 leading-relaxed">{{ asaasTestResult.message }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end pt-2">
                    <button 
                        @click="submitSettings"
                        :disabled="isSaving"
                        class="btn-shimmer inline-flex items-center gap-2 px-6 py-3 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs sm:text-sm shadow-lg shadow-purple-600/30 transition-all cursor-pointer"
                    >
                        <Check class="w-4 h-4" />
                        <span>{{ isSaving ? 'Salvando...' : 'Salvar Todas as Configurações' }}</span>
                    </button>
                </div>
            </form>

        </div>
    </AdminLayout>
</template>
