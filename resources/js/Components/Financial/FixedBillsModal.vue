<script setup>
import { ref, reactive, watch, computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    X, 
    ShieldCheck, 
    PlusCircle, 
    Trash2, 
    CheckCircle2, 
    AlertCircle, 
    Clock, 
    Loader2,
    Calendar,
    ChevronDown,
    ChevronUp,
    Sparkles
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['close', 'saved']);

const { formatCurrency } = useCurrencyFormat();

const isLoading = ref(false);
const isSaving = ref(false);
const fixedBills = ref([]);
const summary = ref({
    total_planned: 0,
    total_paid: 0,
    total_pending: 0,
    percent_shielded: 0,
    count_planned: 0,
    count_paid: 0,
});

const showAddForm = ref(false);
const newBill = reactive({
    name: '',
    group_type: 'fixed_expense',
    budget_ceiling: '',
    due_day: '',
});

const editingId = ref(null);
const editForm = reactive({
    budget_ceiling: '',
    due_day: '',
});

const fetchFixedBills = async () => {
    isLoading.value = true;
    try {
        const res = await window.axios.get('/categorias/contas-fixas');
        if (res.data.success) {
            fixedBills.value = res.data.fixed_bills;
            summary.value = res.data.summary;
        }
    } catch (err) {
        console.error('Erro ao carregar contas fixas:', err);
    } finally {
        isLoading.value = false;
    }
};

watch(() => props.isOpen, (newVal) => {
    if (newVal) {
        fetchFixedBills();
        showAddForm.value = false;
        editingId.value = null;
    }
});

const startEditing = (bill) => {
    editingId.value = bill.id;
    editForm.budget_ceiling = bill.budget_ceiling || '';
    editForm.due_day = bill.due_day || '';
};

const cancelEditing = () => {
    editingId.value = null;
};

const saveEditing = async (bill) => {
    isSaving.value = true;
    try {
        const payload = {
            budget_ceiling: editForm.budget_ceiling ? parseFloat(editForm.budget_ceiling) : null,
            due_day: editForm.due_day ? parseInt(editForm.due_day, 10) : null,
        };

        const res = await window.axios.put(`/categorias/${bill.id}`, payload);
        if (res.data.success) {
            editingId.value = null;
            await fetchFixedBills();
            emit('saved');
            router.reload({ only: ['safeToSpend', 'categories'] });
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao salvar alterações da conta.');
    } finally {
        isSaving.value = false;
    }
};

const createNewBill = async () => {
    if (!newBill.name || !newBill.budget_ceiling) {
        alert('Por favor, informe o nome e o valor previsto da conta.');
        return;
    }

    isSaving.value = true;
    try {
        const res = await window.axios.post('/categorias', {
            name: newBill.name,
            group_type: newBill.group_type,
            budget_ceiling: parseFloat(newBill.budget_ceiling),
            due_day: newBill.due_day ? parseInt(newBill.due_day, 10) : null,
        });

        if (res.data.success) {
            newBill.name = '';
            newBill.budget_ceiling = '';
            newBill.due_day = '';
            showAddForm.value = false;
            await fetchFixedBills();
            emit('saved');
            router.reload({ only: ['safeToSpend', 'categories'] });
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao criar conta fixa.');
    } finally {
        isSaving.value = false;
    }
};

const deleteCustomBill = async (bill) => {
    if (!confirm(`Deseja realmente remover a conta "${bill.name}"?`)) {
        return;
    }

    try {
        const res = await window.axios.delete(`/categorias/${bill.id}`);
        if (res.data.success) {
            await fetchFixedBills();
            emit('saved');
            router.reload({ only: ['safeToSpend', 'categories'] });
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao remover conta.');
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
        <div v-if="isOpen" class="fixed inset-0 z-50 flex items-center justify-center p-3 sm:p-4 bg-slate-950/80 backdrop-blur-md">
            
            <div 
                class="glass-panel w-full max-w-2xl max-h-[90vh] flex flex-col rounded-3xl p-5 sm:p-7 border border-slate-700/80 shadow-2xl relative overflow-hidden bg-slate-900/95"
                @click.stop
            >
                <!-- Close Button -->
                <button 
                    @click="emit('close')"
                    class="absolute top-5 right-5 p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors z-10"
                >
                    <X class="w-5 h-5" />
                </button>

                <!-- Header -->
                <div class="mb-5 shrink-0">
                    <h3 class="text-xl font-extrabold text-white tracking-tight flex items-center gap-2">
                        <ShieldCheck class="w-5 h-5 text-emerald-400" />
                        Blindagem de Contas Fixas
                    </h3>
                    <p class="text-xs text-slate-400 mt-1">
                        Defina o valor mensal das suas despesas obrigatórias. O saldo das contas que ainda não foram pagas fica <strong>blindado e protegido</strong>, garantindo que seu teto diário seja 100% seguro.
                    </p>
                </div>

                <!-- Summary Cards & Progress Bar -->
                <div class="bg-slate-950/60 border border-slate-800 rounded-2xl p-4 mb-5 shrink-0">
                    <div class="grid grid-cols-3 gap-3 text-center sm:text-left mb-3">
                        <div>
                            <span class="text-[10px] text-slate-400 block font-medium uppercase tracking-wider">Total Previsto</span>
                            <span class="text-sm sm:text-lg font-bold text-white font-display">
                                {{ formatCurrency(summary.total_planned) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-emerald-400 block font-medium uppercase tracking-wider">Já Pagas no Mês</span>
                            <span class="text-sm sm:text-lg font-bold text-emerald-400 font-display">
                                {{ formatCurrency(summary.total_paid) }}
                            </span>
                        </div>
                        <div>
                            <span class="text-[10px] text-rose-400 block font-medium uppercase tracking-wider">Ainda Blindado</span>
                            <span class="text-sm sm:text-lg font-bold text-rose-400 font-display">
                                {{ formatCurrency(summary.total_pending) }}
                            </span>
                        </div>
                    </div>

                    <!-- Progress Bar -->
                    <div class="space-y-1">
                        <div class="flex items-center justify-between text-[11px] text-slate-400">
                            <span>Progresso de Quitação no Mês</span>
                            <span class="font-bold text-emerald-400">{{ summary.percent_shielded }}% pago</span>
                        </div>
                        <div class="w-full h-2 rounded-full bg-slate-800 overflow-hidden">
                            <div 
                                class="h-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-500 rounded-full"
                                :style="{ width: `${summary.percent_shielded}%` }"
                            ></div>
                        </div>
                    </div>
                </div>

                <!-- Add New Bill Action Bar -->
                <div class="flex items-center justify-between mb-3 shrink-0">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-400">
                        Obrigações Cadastradas ({{ fixedBills.length }})
                    </span>

                    <button 
                        @click="showAddForm = !showAddForm"
                        class="inline-flex items-center gap-1.5 px-3 py-1 rounded-xl text-xs font-semibold text-emerald-400 bg-emerald-500/10 hover:bg-emerald-500/20 border border-emerald-500/30 transition-all"
                    >
                        <PlusCircle class="w-3.5 h-3.5" />
                        <span>Nova Conta Fixa</span>
                    </button>
                </div>

                <!-- Add Bill Collapsible Form -->
                <div 
                    v-if="showAddForm"
                    class="bg-slate-900 border border-emerald-500/30 rounded-2xl p-4 mb-4 shrink-0 animate-fadeIn"
                >
                    <h4 class="text-xs font-bold text-white mb-3 flex items-center gap-1.5">
                        <Sparkles class="w-3.5 h-3.5 text-emerald-400" />
                        Cadastrar Nova Despesa Fixa Recorrente
                    </h4>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Nome da Conta / Obrigação</label>
                            <input 
                                v-model="newBill.name" 
                                type="text" 
                                placeholder="Ex: Aluguel, Internet, Academia" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>

                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Tipo de Obrigação</label>
                            <select 
                                v-model="newBill.group_type"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                            >
                                <option value="fixed_expense">Conta Fixa & Essencial</option>
                                <option value="debt_installment">Carnê / Financiamento</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Valor Mensal Previsto (R$)</label>
                            <input 
                                v-model="newBill.budget_ceiling" 
                                type="number" 
                                step="0.01" 
                                min="0" 
                                placeholder="0,00" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>

                        <div>
                            <label class="text-[11px] text-slate-400 block mb-1">Dia do Vencimento (1 a 31)</label>
                            <input 
                                v-model="newBill.due_day" 
                                type="number" 
                                min="1" 
                                max="31" 
                                placeholder="Ex: 10" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-1.5 text-xs text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-2 mt-4">
                        <button 
                            @click="showAddForm = false"
                            type="button"
                            class="px-3 py-1.5 text-xs font-semibold text-slate-400 hover:text-white"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="createNewBill"
                            :disabled="isSaving"
                            type="button"
                            class="px-4 py-1.5 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs transition-all flex items-center gap-1.5 disabled:opacity-50"
                        >
                            <Loader2 v-if="isSaving" class="w-3.5 h-3.5 animate-spin" />
                            <span>Salvar Conta</span>
                        </button>
                    </div>
                </div>

                <!-- Scrollable Bills List -->
                <div class="overflow-y-auto flex-1 pr-1 space-y-2.5 custom-scrollbar min-h-[160px]">
                    <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 text-slate-400">
                        <Loader2 class="w-6 h-6 animate-spin text-emerald-400 mb-2" />
                        <span class="text-xs">Carregando suas contas fixas...</span>
                    </div>

                    <div 
                        v-else-if="fixedBills.length === 0" 
                        class="text-center py-10 text-slate-500 text-xs"
                    >
                        Nenhuma conta fixa cadastrada. Clique em "+ Nova Conta Fixa" acima para adicionar.
                    </div>

                    <div 
                        v-for="bill in fixedBills" 
                        :key="bill.id"
                        class="p-3.5 rounded-2xl bg-slate-950/40 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-3"
                    >
                        <!-- Left info -->
                        <div class="min-w-0 flex items-center gap-3">
                            <div 
                                class="w-9 h-9 rounded-xl flex items-center justify-center shrink-0 border"
                                :class="bill.is_paid ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (bill.budget_ceiling ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-slate-800/50 border-slate-700 text-slate-400')"
                            >
                                <CheckCircle2 v-if="bill.is_paid" class="w-4 h-4" />
                                <Clock v-else class="w-4 h-4" />
                            </div>

                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <h4 class="font-bold text-white text-xs sm:text-sm truncate">
                                        {{ bill.name }}
                                    </h4>
                                    <span 
                                        v-if="bill.due_day" 
                                        class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-400 bg-slate-900 border border-slate-800 px-2 py-0.5 rounded-md"
                                    >
                                        <Calendar class="w-2.5 h-2.5 text-teal-400" />
                                        Vence dia {{ bill.due_day }}
                                    </span>
                                </div>

                                <p class="text-[11px] text-slate-400 mt-0.5">
                                    <span v-if="bill.budget_ceiling">
                                        Previsto: <strong class="text-slate-200">{{ formatCurrency(bill.budget_ceiling) }}</strong>
                                        <span class="mx-1.5">•</span>
                                        Pago este mês: <strong :class="bill.paid_amount > 0 ? 'text-emerald-400' : 'text-slate-400'">{{ formatCurrency(bill.paid_amount) }}</strong>
                                    </span>
                                    <span v-else class="text-slate-500 italic">
                                        Sem valor previsto configurado
                                    </span>
                                </p>
                            </div>
                        </div>

                        <!-- Right Actions / Edit Mode -->
                        <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                            
                            <!-- Inline Edit Mode -->
                            <div v-if="editingId === bill.id" class="flex items-center gap-1.5">
                                <div class="w-24">
                                    <input 
                                        v-model="editForm.budget_ceiling"
                                        type="number"
                                        step="0.01"
                                        min="0"
                                        placeholder="Valor R$"
                                        class="w-full bg-slate-900 border border-emerald-500/50 rounded-lg px-2 py-1 text-xs text-white focus:outline-none"
                                    />
                                </div>
                                <div class="w-16">
                                    <input 
                                        v-model="editForm.due_day"
                                        type="number"
                                        min="1"
                                        max="31"
                                        placeholder="Dia"
                                        title="Dia do Vencimento"
                                        class="w-full bg-slate-900 border border-emerald-500/50 rounded-lg px-2 py-1 text-xs text-white focus:outline-none"
                                    />
                                </div>
                                <button 
                                    @click="saveEditing(bill)"
                                    :disabled="isSaving"
                                    class="px-2.5 py-1 rounded-lg bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 disabled:opacity-50"
                                >
                                    Ok
                                </button>
                                <button 
                                    @click="cancelEditing"
                                    class="p-1 rounded-lg text-slate-400 hover:text-white"
                                >
                                    <X class="w-3.5 h-3.5" />
                                </button>
                            </div>

                            <!-- Normal View Mode -->
                            <div v-else class="flex items-center gap-2">
                                <span 
                                    v-if="bill.status === 'paid'"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-400 bg-emerald-500/15 border border-emerald-500/30 px-2 py-0.5 rounded-lg"
                                >
                                    <CheckCircle2 class="w-3 h-3" />
                                    Quitada
                                </span>
                                <span 
                                    v-else-if="bill.status === 'partial'"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-amber-400 bg-amber-500/15 border border-amber-500/30 px-2 py-0.5 rounded-lg"
                                >
                                    <AlertCircle class="w-3 h-3" />
                                    Falta {{ formatCurrency(bill.pending_amount) }}
                                </span>
                                <span 
                                    v-else-if="bill.status === 'pending'"
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-rose-300 bg-rose-500/15 border border-rose-500/30 px-2 py-0.5 rounded-lg"
                                >
                                    Blindado ({{ formatCurrency(bill.pending_amount) }})
                                </span>

                                <button 
                                    @click="startEditing(bill)"
                                    class="px-2.5 py-1 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-medium transition-colors"
                                >
                                    {{ bill.budget_ceiling ? 'Ajustar' : '+ Definir Valor' }}
                                </button>

                                <button 
                                    v-if="bill.is_custom"
                                    @click="deleteCustomBill(bill)"
                                    class="p-1.5 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                                    title="Excluir conta personalizada"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

                <!-- Footer -->
                <div class="pt-4 border-t border-slate-800 mt-4 flex items-center justify-between shrink-0">
                    <span class="text-[11px] text-slate-400 flex items-center gap-1.5">
                        <ShieldCheck class="w-3.5 h-3.5 text-emerald-400" />
                        O valor pendente é descontado automaticamente do saldo.
                    </span>

                    <button 
                        @click="emit('close')"
                        class="px-4 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-semibold text-xs transition-colors"
                    >
                        Concluído
                    </button>
                </div>

            </div>

        </div>
    </transition>
</template>
