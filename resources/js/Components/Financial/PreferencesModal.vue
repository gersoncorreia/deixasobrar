<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    X, 
    Settings, 
    Calendar, 
    ShieldCheck, 
    Zap, 
    Loader2 
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

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        form.payday_day = props.user?.payday_day || 5;
        form.safety_reserve = props.user?.safety_reserve || 200;
        errorMessage.value = '';
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
                class="glass-panel w-full max-w-md rounded-3xl p-6 sm:p-7 border border-slate-700/80 shadow-2xl relative overflow-hidden bg-slate-900/95"
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

            </div>
        </div>
    </transition>
</template>
