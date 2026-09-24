<script setup>
import { ref, reactive, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import { 
    X, 
    PlusCircle, 
    ArrowDownRight, 
    ArrowUpRight, 
    Flame, 
    Repeat, 
    Loader2 
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    accounts: {
        type: Array,
        default: () => []
    },
    categories: {
        type: Array,
        default: () => []
    },
});

const emit = defineEmits(['close', 'saved']);

const form = reactive({
    type: 'expense',
    description: '',
    amount: '',
    account_id: '',
    category_id: '',
    transaction_date: new Date().toISOString().split('T')[0],
    is_leak: false,
    is_recurring: false,
});

const isSubmitting = ref(false);
const errorMessage = ref('');

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        form.type = 'expense';
        form.description = '';
        form.amount = '';
        form.account_id = props.accounts?.[0]?.id || '';
        form.category_id = '';
        form.transaction_date = new Date().toISOString().split('T')[0];
        form.is_leak = false;
        form.is_recurring = false;
        errorMessage.value = '';
    }
});

const submit = async () => {
    if (!form.description || !form.amount || !form.account_id) {
        errorMessage.value = 'Por favor, preencha a descrição, o valor e selecione a conta.';
        return;
    }

    const cleanAmount = parseFloat(String(form.amount).replace(',', '.'));
    if (isNaN(cleanAmount) || cleanAmount <= 0) {
        errorMessage.value = 'Informe um valor numérico válido maior que zero.';
        return;
    }

    isSubmitting.value = true;
    errorMessage.value = '';

    try {
        const payload = {
            ...form,
            amount: cleanAmount,
        };

        const res = await window.axios.post('/transacoes', payload);
        if (res.data.success) {
            emit('saved');
            emit('close');
            router.reload({ only: ['safeToSpend', 'accounts', 'transactions', 'leaksSummary'] });
        }
    } catch (err) {
        errorMessage.value = err.response?.data?.message || 'Erro ao registrar lançamento. Tente novamente.';
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
            
            <!-- Modal Body with zoom-in -->
            <div 
                class="glass-panel w-full max-w-lg rounded-2xl p-6 sm:p-7 border border-slate-700/80 shadow-2xl relative overflow-hidden bg-slate-900/95"
                @click.stop
            >
                <!-- Close Button -->
                <button 
                    @click="emit('close')"
                    class="absolute top-5 right-5 p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <X class="w-5 h-5" />
                </button>

                <!-- Title & Subtitle -->
                <div class="mb-5">
                    <h3 class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
                        <PlusCircle class="w-5 h-5 text-emerald-400" />
                        Novo Lançamento
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">
                        Adicione um gasto diário, Pix ou receita para atualizar seu Teto Diário.
                    </p>
                </div>

                <!-- Type Selector Tabs (Saída / Entrada) -->
                <div class="grid grid-cols-2 gap-2 p-1 rounded-2xl bg-slate-950 border border-slate-800 mb-5">
                    <button 
                        type="button"
                        @click="form.type = 'expense'"
                        :class="[
                            'flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all',
                            form.type === 'expense' 
                                ? 'bg-rose-500/20 text-rose-300 border border-rose-500/30 shadow-md' 
                                : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        <ArrowDownRight class="w-4 h-4 text-rose-400" />
                        Saída / Gasto
                    </button>
                    <button 
                        type="button"
                        @click="form.type = 'income'"
                        :class="[
                            'flex items-center justify-center gap-2 py-2.5 rounded-xl text-xs sm:text-sm font-bold transition-all',
                            form.type === 'income' 
                                ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/30 shadow-md' 
                                : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        <ArrowUpRight class="w-4 h-4 text-emerald-400" />
                        Entrada / Receita
                    </button>
                </div>

                <!-- Form Fields -->
                <form @submit.prevent="submit" class="space-y-4">
                    
                    <!-- Valor -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Valor em Reais (R$)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-500 font-bold text-sm">
                                R$
                            </span>
                            <input 
                                type="text"
                                inputmode="decimal"
                                v-model="form.amount"
                                placeholder="0,00"
                                autofocus
                                class="w-full pl-10 pr-4 py-3 bg-slate-950 rounded-xl border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 text-lg font-bold font-display"
                            />
                        </div>
                    </div>

                    <!-- Descrição -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Descrição / Nome do Estabelecimento
                        </label>
                        <input 
                            type="text"
                            v-model="form.description"
                            placeholder="Ex: Almoço Padaria, Uber, Farmácia, Pix João..."
                            class="w-full px-4 py-2.5 bg-slate-950 rounded-xl border border-slate-800 text-white placeholder-slate-600 focus:outline-none focus:border-emerald-500 text-sm"
                        />
                    </div>

                    <!-- Conta & Categoria (Grid 2 cols) -->
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Conta Bancária
                            </label>
                            <select 
                                v-model="form.account_id"
                                class="w-full px-3 py-2.5 bg-slate-950 rounded-xl border border-slate-800 text-slate-200 text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                            >
                                <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                                    {{ acc.name }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-semibold text-slate-300 mb-1">
                                Categoria
                            </label>
                            <select 
                                v-model="form.category_id"
                                class="w-full px-3 py-2.5 bg-slate-950 rounded-xl border border-slate-800 text-slate-200 text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                            >
                                <option value="">Sem categoria definida</option>
                                <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                                    {{ cat.name }}
                                </option>
                            </select>
                        </div>
                    </div>

                    <!-- Data do Lançamento -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1">
                            Data do Lançamento
                        </label>
                        <input 
                            type="date"
                            v-model="form.transaction_date"
                            class="w-full px-4 py-2 bg-slate-950 rounded-xl border border-slate-800 text-white text-xs sm:text-sm focus:outline-none focus:border-emerald-500"
                        />
                    </div>

                    <!-- Toggles: Vazamento e Fixo (apenas para despesas) -->
                    <div v-if="form.type === 'expense'" class="pt-2 border-t border-slate-800 flex flex-col sm:flex-row gap-3">
                        
                        <!-- É vazamento? -->
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer flex-1 hover:border-amber-500/40 transition-colors">
                            <input 
                                type="checkbox" 
                                v-model="form.is_leak"
                                class="rounded bg-slate-900 border-slate-700 text-amber-500 focus:ring-0 w-4 h-4 cursor-pointer"
                            />
                            <div class="flex items-center gap-1.5 text-xs text-amber-300 font-semibold">
                                <Flame class="w-3.5 h-3.5 text-amber-400" />
                                <span>Radar de Vazamento</span>
                            </div>
                        </label>

                        <!-- É fixo / recorrente? -->
                        <label class="flex items-center gap-2 p-2.5 rounded-xl bg-slate-950/60 border border-slate-800 cursor-pointer flex-1 hover:border-emerald-500/40 transition-colors">
                            <input 
                                type="checkbox" 
                                v-model="form.is_recurring"
                                class="rounded bg-slate-900 border-slate-700 text-emerald-500 focus:ring-0 w-4 h-4 cursor-pointer"
                            />
                            <div class="flex items-center gap-1.5 text-xs text-slate-300 font-semibold">
                                <Repeat class="w-3.5 h-3.5 text-emerald-400" />
                                <span>Conta Fixa / Carnê</span>
                            </div>
                        </label>

                    </div>

                    <!-- Error feedback -->
                    <div v-if="errorMessage" class="p-3 rounded-xl bg-rose-500/10 border border-rose-500/30 text-rose-300 text-xs">
                        {{ errorMessage }}
                    </div>

                    <!-- Action Buttons -->
                    <div class="pt-3 flex items-center justify-end gap-3">
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
                            <span>{{ isSubmitting ? 'Salvando...' : 'Salvar Lançamento' }}</span>
                        </button>
                    </div>

                </form>

            </div>
        </div>
    </transition>
</template>
