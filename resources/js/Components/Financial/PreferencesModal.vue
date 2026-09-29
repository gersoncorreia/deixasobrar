<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    X, 
    Settings, 
    Calendar, 
    ShieldCheck, 
    Zap, 
    Loader2,
    AlertTriangle,
    Trash2,
    RotateCcw,
    ChevronDown,
    ChevronUp
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    user: Object,
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({
    payday_day: 5,
    safety_reserve: 200,
});

const isSubmitting = ref(false);
const errorMessage = ref('');

// Reset Data State
const showResetSection = ref(false);
const resetMode = ref('transactions_only'); // 'transactions_only' | 'full_reset'
const resetConfirmationText = ref('');
const isResetting = ref(false);
const resetErrorMessage = ref('');
const resetSuccessMessage = ref('');

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        form.payday_day = props.user?.payday_day || 5;
        form.safety_reserve = props.user?.safety_reserve || 200;
        errorMessage.value = '';
        showResetSection.value = false;
        resetConfirmationText.value = '';
        resetErrorMessage.value = '';
        resetSuccessMessage.value = '';
    }
});

const submit = async () => {
    if (form.payday_day < 1 || form.payday_day > 31) {
        errorMessage.value = 'O dia do pagamento deve ser entre 1 e 31.';
        return;
    }

    if (form.safety_reserve < 0) {
        errorMessage.value = 'A reserva de segurança não pode ser negativa.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const res = await window.axios.post('/configuracoes/ciclo', {
            payday_day: parseInt(form.payday_day, 10),
            safety_reserve: parseFloat(form.safety_reserve),
        });

        if (res.data.success) {
            emit('saved');
            emit('close');
            router.reload({ only: ['safeToSpend', 'user'] });
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Erro ao atualizar preferências.';
    } finally {
        isSubmitting.value = false;
    }
};

const executeReset = async () => {
    if (resetConfirmationText.value.trim().toUpperCase() !== 'ZERAR') {
        resetErrorMessage.value = 'Por favor, digite a palavra ZERAR para confirmar.';
        return;
    }

    isResetting.value = true;
    resetErrorMessage.value = '';

    try {
        const res = await window.axios.post('/configuracoes/reset-dados', {
            mode: resetMode.value,
            confirmation: 'ZERAR',
        });

        if (res.data?.success) {
            resetSuccessMessage.value = res.data.message;
            setTimeout(() => {
                window.location.href = '/dashboard';
            }, 1200);
        }
    } catch (err) {
        resetErrorMessage.value = err.response?.data?.message || 'Erro ao zerar dados da conta.';
    } finally {
        isResetting.value = false;
    }
};
</script>

<template>
    <transition
        enter-active-class="transition duration-200 ease-out"
        enter-from-class="opacity-0"
        enter-to-class="opacity-100"
        leave-active-class="transition duration-150 ease-in"
        leave-from-class="opacity-100"
        leave-to-class="opacity-0"
    >
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/80 backdrop-blur-md">
            
            <div 
                class="glass-panel w-full max-w-md rounded-2xl p-6 sm:p-7 border border-slate-700/80 shadow-2xl relative overflow-hidden bg-slate-900/95 max-h-[90vh] overflow-y-auto"
                @click.stop
            >
                <button 
                    @click="emit('close')"
                    class="absolute top-5 right-5 p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>

                <div class="mb-5">
                    <h3 class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
                        <Settings class="w-5 h-5 text-emerald-400" />
                        Ciclo de Renda & Teto Diário
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Personalize suas datas e valores para o cálculo preciso do seu <strong>Teto Diário Seguro</strong>.
                    </p>
                </div>

                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Dia do Pagamento -->
                    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-200 mb-1.5">
                            <Calendar class="w-4 h-4 text-emerald-400" />
                            <span>Dia do seu Salário / Pagamento Principal</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-2.5 leading-relaxed">
                            Dia do mês em que você recebe. O DeixaSobrar calcula a contagem regressiva exata de dias até esta data.
                        </p>
                        <div class="flex items-center gap-3">
                            <span class="text-xs font-semibold text-slate-400">Todo dia</span>
                            <input 
                                type="number" 
                                min="1" 
                                max="31"
                                v-model="form.payday_day"
                                class="w-20 px-3 py-2 bg-slate-900 rounded-xl border border-slate-700 text-center font-bold text-base text-white focus:outline-none focus:border-emerald-500 font-display"
                            />
                            <span class="text-xs font-semibold text-slate-400">de cada mês</span>
                        </div>
                    </div>

                    <!-- Reserva Mínima Intocável -->
                    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
                        <div class="flex items-center gap-2 text-xs font-bold text-slate-200 mb-1.5">
                            <ShieldCheck class="w-4 h-4 text-emerald-400" />
                            <span>Reserva Mínima Blindada</span>
                        </div>
                        <p class="text-[11px] text-slate-400 mb-2.5 leading-relaxed">
                            Valor mínimo que você quer manter na conta como margem de segurança. O sistema <strong>desconta este valor</strong> antes de calcular o teto livre diário.
                        </p>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-xs">
                                R$
                            </span>
                            <input 
                                type="number" 
                                step="10"
                                min="0"
                                v-model="form.safety_reserve"
                                placeholder="200,00"
                                class="w-full pl-10 pr-4 py-2 bg-slate-900 rounded-xl border border-slate-700 text-white font-bold text-sm focus:outline-none focus:border-emerald-500 font-display"
                            />
                        </div>
                    </div>

                    <!-- Informative Tip -->
                    <div class="flex items-start gap-2.5 p-3 rounded-xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-300 text-xs leading-relaxed">
                        <Zap class="w-4 h-4 text-emerald-400 shrink-0 mt-0.5" />
                        <span>
                            Seu teto diário será recalculado instantaneamente assim que você salvar.
                        </span>
                    </div>

                    <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                        {{ errorMessage }}
                    </div>

                    <div class="pt-2 flex items-center justify-end gap-3">
                        <button 
                            type="button" 
                            @click="emit('close')"
                            class="px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold text-slate-400 hover:text-white transition-colors"
                        >
                            Cancelar
                        </button>
                        <button 
                            type="submit"
                            :disabled="isSubmitting"
                            class="btn-shimmer px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/20 disabled:opacity-50 flex items-center gap-2"
                        >
                            <Loader2 v-if="isSubmitting" class="w-4 h-4 animate-spin" />
                            <span>{{ isSubmitting ? 'Salvando...' : 'Salvar Preferências' }}</span>
                        </button>
                    </div>

                </form>

                <!-- Zona de Limpeza / Recomeçar do Zero -->
                <div class="mt-6 pt-5 border-t border-slate-800">
                    <button 
                        type="button"
                        @click="showResetSection = !showResetSection"
                        class="w-full flex items-center justify-between text-left py-2 px-3 rounded-xl hover:bg-rose-500/10 transition-colors border border-transparent hover:border-rose-500/20 group"
                    >
                        <div class="flex items-center gap-2.5">
                            <AlertTriangle class="w-4 h-4 text-rose-400 shrink-0 group-hover:scale-110 transition-transform" />
                            <div>
                                <h4 class="text-xs font-bold text-rose-300">Zona de Limpeza (Zerar Dados)</h4>
                                <p class="text-[11px] text-slate-400">Importou errado ou quer recomeçar do zero?</p>
                            </div>
                        </div>
                        <component :is="showResetSection ? ChevronUp : ChevronDown" class="w-4 h-4 text-slate-400 group-hover:text-rose-300" />
                    </button>

                    <div v-if="showResetSection" class="mt-4 p-4 rounded-xl bg-rose-950/20 border border-rose-500/30 space-y-4">
                        <p class="text-xs text-rose-200/90 leading-relaxed">
                            Aqui você pode apagar extratos, comprovantes e movimentações que foram enviados por engano, sem precisar criar uma conta nova.
                        </p>

                        <div class="space-y-2">
                            <label class="flex items-start gap-2.5 cursor-pointer p-2.5 rounded-lg bg-slate-900/60 border border-slate-800 hover:border-rose-500/40 transition-colors">
                                <input 
                                    type="radio" 
                                    name="reset_mode" 
                                    value="transactions_only" 
                                    v-model="resetMode"
                                    class="mt-0.5 text-rose-500 focus:ring-rose-500 bg-slate-950 border-slate-700"
                                />
                                <div>
                                    <div class="text-xs font-bold text-white">Limpar apenas Movimentações & Extratos</div>
                                    <div class="text-[11px] text-slate-400 leading-tight">Apaga todos os extratos, notas escaneadas e lançamentos. Mantém suas contas bancárias e contas fixas cadastradas.</div>
                                </div>
                            </label>

                            <label class="flex items-start gap-2.5 cursor-pointer p-2.5 rounded-lg bg-slate-900/60 border border-slate-800 hover:border-rose-500/40 transition-colors">
                                <input 
                                    type="radio" 
                                    name="reset_mode" 
                                    value="full_reset" 
                                    v-model="resetMode"
                                    class="mt-0.5 text-rose-500 focus:ring-rose-500 bg-slate-950 border-slate-700"
                                />
                                <div>
                                    <div class="text-xs font-bold text-rose-300">Zerar Tudo (Reinício Completo)</div>
                                    <div class="text-[11px] text-slate-400 leading-tight">Apaga movimentações, extratos, categorias personalizadas e redefine suas contas para R$ 0,00.</div>
                                </div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-rose-200 mb-1">
                                Digite <strong class="text-white underline">ZERAR</strong> para confirmar:
                            </label>
                            <input 
                                v-model="resetConfirmationText"
                                type="text"
                                placeholder="ZERAR"
                                class="w-full bg-slate-950/80 border border-rose-500/30 rounded-xl px-3 py-2 text-xs text-white uppercase placeholder-slate-600 focus:outline-none focus:border-rose-500 transition-colors tracking-widest font-mono"
                            />
                        </div>

                        <div v-if="resetErrorMessage" class="p-2.5 rounded-lg bg-rose-500/20 border border-rose-500/40 text-rose-300 text-xs">
                            {{ resetErrorMessage }}
                        </div>

                        <div v-if="resetSuccessMessage" class="p-2.5 rounded-lg bg-emerald-500/20 border border-emerald-500/40 text-emerald-300 text-xs">
                            {{ resetSuccessMessage }}
                        </div>

                        <button 
                            type="button"
                            @click="executeReset"
                            :disabled="isResetting || resetConfirmationText.trim().toUpperCase() !== 'ZERAR'"
                            class="w-full py-2.5 px-4 rounded-xl bg-rose-600 hover:bg-rose-500 text-white font-bold text-xs shadow-lg shadow-rose-600/20 disabled:opacity-40 disabled:cursor-not-allowed flex items-center justify-center gap-2 transition-all"
                        >
                            <Loader2 v-if="isResetting" class="w-4 h-4 animate-spin" />
                            <Trash2 v-else class="w-4 h-4" />
                            <span>{{ isResetting ? 'Limpando dados...' : 'Confirmar e Zerar Dados' }}</span>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </transition>
</template>
