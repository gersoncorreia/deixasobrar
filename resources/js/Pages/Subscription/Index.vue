<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { 
    Zap, 
    Check, 
    ShieldCheck, 
    CreditCard, 
    QrCode, 
    Copy, 
    CheckCircle2, 
    XCircle, 
    AlertCircle, 
    Loader2, 
    ExternalLink,
    Sparkles,
    Calendar,
    ArrowRight
} from 'lucide-vue-next';
import axios from 'axios';

const props = defineProps({
    user: Object,
    subscription: Object,
    plans: Array,
});

// Checkout Modal state
const isModalOpen = ref(false);
const selectedPlan = ref(null);
const billingType = ref('PIX'); // 'PIX' | 'CREDIT_CARD'
const isSubmitting = ref(false);
const errorMessage = ref('');
const successData = ref(null);
const copiedPix = ref(false);

const checkoutForm = ref({
    cpf_cnpj: props.user?.cpf_cnpj || '',
    phone: props.user?.phone || '',
    credit_card: {
        holderName: '',
        number: '',
        expiryMonth: '',
        expiryYear: '',
        ccv: '',
    },
});

const openCheckout = (plan) => {
    selectedPlan.value = plan;
    errorMessage.value = '';
    successData.value = null;
    isModalOpen.value = true;
};

const copyPixCode = () => {
    if (successData.value?.pix_payload) {
        navigator.clipboard.writeText(successData.value.pix_payload);
        copiedPix.value = true;
        setTimeout(() => {
            copiedPix.value = false;
        }, 3000);
    }
};

// Polling for Pix payment confirmation
let pollingInterval = null;

const startPolling = () => {
    if (pollingInterval) clearInterval(pollingInterval);
    pollingInterval = setInterval(async () => {
        try {
            const res = await axios.get('/assinatura/status');
            if (res.data.is_active) {
                clearInterval(pollingInterval);
                window.location.reload();
            }
        } catch (e) {
            // Ignore polling errors
        }
    }, 4000);
};

onUnmounted(() => {
    if (pollingInterval) clearInterval(pollingInterval);
});

const submitCheckout = async () => {
    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            plan_tier: selectedPlan.value.tier,
            billing_type: billingType.value,
            cpf_cnpj: checkoutForm.value.cpf_cnpj,
            phone: checkoutForm.value.phone,
        };

        if (billingType.value === 'CREDIT_CARD') {
            payload.credit_card = checkoutForm.value.credit_card;
        }

        const response = await axios.post('/assinatura/checkout', payload);
        successData.value = response.data;

        if (billingType.value === 'PIX') {
            startPolling();
        } else {
            // Credit card activated
            setTimeout(() => {
                window.location.reload();
            }, 1500);
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || err.message || 'Erro ao processar assinatura.';
    } finally {
        isSubmitting.value = false;
    }
};

const cancelSubscription = () => {
    if (confirm('Tem certeza que deseja cancelar sua assinatura? Você perderá acesso aos recursos ilimitados no fim do ciclo.')) {
        router.post('/assinatura/cancelar');
    }
};
</script>

<template>
    <Head title="Meu Plano & Assinatura - DeixaSobrar" />

    <AppLayout :user="user" title="Meu Plano">
        <div class="max-w-6xl mx-auto space-y-8 pb-16">
            
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-2 px-3 py-1 rounded-full text-xs font-bold bg-purple-500/10 text-purple-400 border border-purple-500/20 mb-2">
                        <Sparkles class="w-3.5 h-3.5 text-purple-400" />
                        <span>Planos & Vantagens Exclusivas</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-black text-white tracking-tight">
                        Gestão da sua Assinatura
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Desbloqueie OCR ilimitado com IA, radar anti-ralos e suporte prioritário.
                    </p>
                </div>

                <!-- Current Plan Status Card -->
                <div class="p-4 rounded-2xl bg-slate-900/90 border border-slate-800 flex items-center gap-4">
                    <div class="w-10 h-10 rounded-xl flex items-center justify-center font-black text-sm"
                        :class="subscription?.is_active ? 'bg-emerald-500/20 text-emerald-400 border border-emerald-500/30' : 'bg-slate-800 text-slate-400'">
                        <Zap class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="text-[11px] uppercase tracking-wider text-slate-400 font-bold">Plano Atual</div>
                        <div class="text-sm font-extrabold text-white flex items-center gap-2">
                            <span>{{ subscription?.plan_name || 'Gratuito' }}</span>
                            <span class="text-[10px] px-2 py-0.5 rounded-full font-bold uppercase"
                                :class="subscription?.is_active ? 'bg-emerald-500/20 text-emerald-300' : 'bg-amber-500/20 text-amber-300'">
                                {{ subscription?.is_active ? 'Ativo' : (subscription?.status === 'trialing' ? 'Pendente' : 'Básico') }}
                            </span>
                        </div>
                        <div v-if="subscription?.current_period_end" class="text-[10px] text-slate-400 mt-0.5">
                            Renovação: {{ subscription.current_period_end }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Active Pix Pending Alert (se houver cobrança pendente) -->
            <div 
                v-if="subscription && subscription.status === 'trialing' && subscription.pix_payload" 
                class="p-5 rounded-2xl bg-purple-950/30 border border-purple-500/40 space-y-4"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <QrCode class="w-6 h-6 text-purple-400" />
                        <div>
                            <h3 class="text-sm font-bold text-white">Pagamento Pix Pendente</h3>
                            <p class="text-xs text-slate-300">Pague via Pix para ativar instantaneamente seu plano.</p>
                        </div>
                    </div>
                    <span class="text-xs font-mono text-purple-300 bg-purple-950/80 px-2.5 py-1 rounded-lg border border-purple-500/30">
                        Expira em: {{ subscription.pix_expiration || '24h' }}
                    </span>
                </div>

                <div class="flex flex-col sm:flex-row items-center gap-5 pt-2">
                    <div v-if="subscription.pix_qrcode" class="p-2 bg-white rounded-xl shadow-lg shrink-0">
                        <img :src="'data:image/png;base64,' + subscription.pix_qrcode" alt="QR Code Pix" class="w-36 h-36" />
                    </div>
                    <div class="flex-1 space-y-2 w-full">
                        <label class="block text-[11px] font-bold text-slate-300">Código Pix Copia e Cola:</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                readonly 
                                :value="subscription.pix_payload" 
                                class="w-full px-3 py-2 bg-slate-900 border border-slate-700 rounded-xl text-xs font-mono text-slate-300 select-all"
                            />
                            <button 
                                @click="navigator.clipboard.writeText(subscription.pix_payload)"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors shrink-0"
                            >
                                <Copy class="w-4 h-4" />
                                <span>Copiar</span>
                            </button>
                        </div>
                        <p class="text-[11px] text-emerald-400 flex items-center gap-1 mt-1">
                            <Loader2 class="w-3.5 h-3.5 animate-spin" />
                            <span>Aguardando confirmação do banco em tempo real...</span>
                        </p>
                    </div>
                </div>
            </div>

            <!-- Cards de Planos -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <div 
                    v-for="plan in plans" 
                    :key="plan.tier"
                    class="relative rounded-3xl p-6 sm:p-8 flex flex-col justify-between transition-all duration-300"
                    :class="plan.popular 
                        ? 'bg-gradient-to-b from-purple-950/50 to-slate-900/90 border-2 border-purple-500/50 shadow-2xl shadow-purple-950/40' 
                        : 'bg-slate-900/60 border border-slate-800/80 hover:border-slate-700'"
                >
                    <!-- Popular Badge -->
                    <div 
                        v-if="plan.popular" 
                        class="absolute -top-3.5 left-1/2 -translate-x-1/2 px-4 py-1 rounded-full text-[11px] font-black uppercase tracking-wider bg-purple-600 text-white shadow-lg shadow-purple-600/40 flex items-center gap-1.5"
                    >
                        <Zap class="w-3 h-3 fill-current" />
                        <span>Mais Escolhido</span>
                    </div>

                    <!-- Badge Custom -->
                    <div 
                        v-if="plan.badge" 
                        class="absolute -top-3.5 right-6 px-3 py-1 rounded-full text-[10px] font-black uppercase tracking-wider bg-emerald-500/20 text-emerald-300 border border-emerald-500/40"
                    >
                        {{ plan.badge }}
                    </div>

                    <div>
                        <h3 class="text-xl font-black text-white">{{ plan.name }}</h3>
                        
                        <div class="mt-4 flex items-baseline gap-1">
                            <span class="text-3xl sm:text-4xl font-black text-white tracking-tight">{{ plan.price }}</span>
                            <span class="text-xs text-slate-400 font-semibold">{{ plan.period }}</span>
                        </div>

                        <div class="my-6 border-t border-slate-800"></div>

                        <ul class="space-y-3">
                            <li 
                                v-for="feat in plan.features" 
                                :key="feat" 
                                class="flex items-start gap-3 text-xs text-slate-300"
                            >
                                <div class="w-4 h-4 rounded-full bg-emerald-500/20 text-emerald-400 flex items-center justify-center shrink-0 mt-0.5">
                                    <Check class="w-2.5 h-2.5 stroke-[3]" />
                                </div>
                                <span>{{ feat }}</span>
                            </li>
                        </ul>
                    </div>

                    <div class="mt-8 pt-4">
                        <button 
                            v-if="subscription?.plan_tier === plan.tier && subscription?.is_active"
                            disabled
                            class="w-full py-3 px-4 rounded-xl bg-slate-800 text-slate-400 text-xs font-bold cursor-not-allowed text-center"
                        >
                            Plano Atual Ativo ✓
                        </button>
                        <button 
                            v-else-if="plan.tier === 'free'"
                            disabled
                            class="w-full py-3 px-4 rounded-xl bg-slate-800/40 text-slate-500 text-xs font-bold cursor-not-allowed text-center"
                        >
                            Plano Básico
                        </button>
                        <button 
                            v-else
                            @click="openCheckout(plan)"
                            class="w-full py-3 px-4 rounded-xl font-bold text-xs sm:text-sm transition-all shadow-lg flex items-center justify-center gap-2 cursor-pointer"
                            :class="plan.popular 
                                ? 'bg-purple-600 hover:bg-purple-500 text-white shadow-purple-600/30' 
                                : 'bg-white hover:bg-slate-100 text-slate-950 shadow-white/10'"
                        >
                            <span>Assinar {{ plan.name }}</span>
                            <ArrowRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </div>

            <!-- Botão de Cancelamento (se for assinante ativo pago) -->
            <div v-if="subscription?.is_active && subscription?.plan_tier !== 'free'" class="pt-6 border-t border-slate-800 flex justify-end">
                <button 
                    @click="cancelSubscription"
                    class="text-xs font-semibold text-slate-500 hover:text-rose-400 transition-colors"
                >
                    Cancelar assinatura do plano pago
                </button>
            </div>

        </div>

        <!-- Modal de Checkout Asaas (Pix / Cartão) -->
        <div 
            v-if="isModalOpen" 
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-sm animate-in fade-in"
        >
            <div class="bg-slate-900 border border-slate-800 rounded-3xl p-6 sm:p-8 max-w-lg w-full space-y-5 shadow-2xl relative">
                
                <div class="flex items-center justify-between pb-4 border-b border-slate-800">
                    <div>
                        <h3 class="text-base font-extrabold text-white flex items-center gap-2">
                            <CreditCard class="w-5 h-5 text-purple-400" />
                            <span>Assinar {{ selectedPlan?.name }} ({{ selectedPlan?.price }})</span>
                        </h3>
                        <p class="text-xs text-slate-400 mt-0.5">Pagamento seguro processado via Asaas Gateway.</p>
                    </div>
                    <button 
                        @click="isModalOpen = false" 
                        class="text-slate-400 hover:text-white text-lg font-bold p-1 cursor-pointer"
                    >
                        ✕
                    </button>
                </div>

                <!-- Se o Pix já foi gerado no checkout -->
                <div v-if="successData && billingType === 'PIX'" class="space-y-4 py-2">
                    <div class="p-3 bg-emerald-950/40 border border-emerald-500/40 rounded-xl text-emerald-300 text-xs flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                        <span>QrCode Pix gerado! Faça o pagamento pelo seu app do banco.</span>
                    </div>

                    <div class="flex justify-center p-3 bg-white rounded-2xl w-48 h-48 mx-auto shadow-xl">
                        <img 
                            v-if="successData.pix_qrcode" 
                            :src="'data:image/png;base64,' + successData.pix_qrcode" 
                            alt="QR Code Pix" 
                            class="w-full h-full object-contain"
                        />
                    </div>

                    <div class="space-y-1.5">
                        <label class="block text-xs font-bold text-slate-300">Código Copia e Cola:</label>
                        <div class="flex gap-2">
                            <input 
                                type="text" 
                                readonly 
                                :value="successData.pix_payload" 
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs font-mono text-slate-300 select-all"
                            />
                            <button 
                                @click="copyPixCode"
                                class="px-4 py-2 bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs rounded-xl flex items-center gap-1.5 transition-colors cursor-pointer"
                            >
                                <Check v-if="copiedPix" class="w-4 h-4 text-emerald-300" />
                                <Copy v-else class="w-4 h-4" />
                                <span>{{ copiedPix ? 'Copiado!' : 'Copiar' }}</span>
                            </button>
                        </div>
                    </div>

                    <div class="p-3 rounded-xl bg-slate-950 border border-slate-800 text-center">
                        <p class="text-xs text-emerald-400 font-semibold flex items-center justify-center gap-2">
                            <Loader2 class="w-4 h-4 animate-spin text-emerald-400" />
                            <span>Confirmando pagamento em tempo real...</span>
                        </p>
                        <p class="text-[10px] text-slate-500 mt-1">Essa tela atualizará automaticamente assim que for aprovado.</p>
                    </div>
                </div>

                <!-- Formulário de Checkout Normal -->
                <form v-else @submit.prevent="submitCheckout" class="space-y-4">
                    
                    <!-- Seleção do Método: PIX ou Cartão -->
                    <div class="grid grid-cols-2 gap-3 p-1 rounded-2xl bg-slate-950 border border-slate-800">
                        <button 
                            type="button" 
                            @click="billingType = 'PIX'"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="billingType === 'PIX' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                        >
                            <QrCode class="w-4 h-4" />
                            <span>Pix Instantâneo</span>
                        </button>
                        <button 
                            type="button" 
                            @click="billingType = 'CREDIT_CARD'"
                            class="flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs font-bold transition-all cursor-pointer"
                            :class="billingType === 'CREDIT_CARD' ? 'bg-purple-600 text-white shadow-md' : 'text-slate-400 hover:text-white'"
                        >
                            <CreditCard class="w-4 h-4" />
                            <span>Cartão de Crédito</span>
                        </button>
                    </div>

                    <!-- Dados Cadastrais Básicos (obrigatórios pelo Banco Central/Asaas) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">CPF ou CNPJ</label>
                            <input 
                                v-model="checkoutForm.cpf_cnpj" 
                                type="text" 
                                required
                                placeholder="000.000.000-00"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Celular / WhatsApp</label>
                            <input 
                                v-model="checkoutForm.phone" 
                                type="text" 
                                required
                                placeholder="(11) 99999-9999"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                            />
                        </div>
                    </div>

                    <!-- Campos de Cartão de Crédito (se selecionado) -->
                    <div v-if="billingType === 'CREDIT_CARD'" class="space-y-3 pt-2 border-t border-slate-800">
                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Nome Impresso no Cartão</label>
                            <input 
                                v-model="checkoutForm.credit_card.holderName" 
                                type="text" 
                                required
                                placeholder="NOME SOBRENOME"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white uppercase focus:outline-none focus:border-purple-500"
                            />
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-300 mb-1">Número do Cartão</label>
                            <input 
                                v-model="checkoutForm.credit_card.number" 
                                type="text" 
                                required
                                placeholder="0000 0000 0000 0000"
                                class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white font-mono focus:outline-none focus:border-purple-500"
                            />
                        </div>

                        <div class="grid grid-cols-3 gap-2">
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Mês (MM)</label>
                                <input 
                                    v-model="checkoutForm.credit_card.expiryMonth" 
                                    type="text" 
                                    maxlength="2"
                                    required
                                    placeholder="12"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white text-center font-mono focus:outline-none focus:border-purple-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">Ano (AAAA)</label>
                                <input 
                                    v-model="checkoutForm.credit_card.expiryYear" 
                                    type="text" 
                                    maxlength="4"
                                    required
                                    placeholder="2028"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white text-center font-mono focus:outline-none focus:border-purple-500"
                                />
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-slate-300 mb-1">CVV</label>
                                <input 
                                    v-model="checkoutForm.credit_card.ccv" 
                                    type="text" 
                                    maxlength="4"
                                    required
                                    placeholder="123"
                                    class="w-full px-3 py-2 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white text-center font-mono focus:outline-none focus:border-purple-500"
                                />
                            </div>
                        </div>
                    </div>

                    <!-- Mensagem de Erro -->
                    <div v-if="errorMessage" class="p-3 bg-rose-950/40 border border-rose-500/40 rounded-xl text-rose-300 text-xs flex items-center gap-2">
                        <XCircle class="w-4 h-4 text-rose-400 shrink-0" />
                        <span>{{ errorMessage }}</span>
                    </div>

                    <button 
                        type="submit" 
                        :disabled="isSubmitting"
                        class="w-full py-3 rounded-xl bg-purple-600 hover:bg-purple-500 disabled:opacity-50 text-white font-extrabold text-sm shadow-lg shadow-purple-600/30 transition-all flex items-center justify-center gap-2 cursor-pointer"
                    >
                        <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                        <span v-else>{{ billingType === 'PIX' ? 'Gerar QrCode Pix ⚡' : 'Pagar e Ativar Plano 🔒' }}</span>
                    </button>
                </form>

            </div>
        </div>

    </AppLayout>
</template>
