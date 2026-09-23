<script setup>
import { ref, reactive, onMounted } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    ShieldCheck, 
    PlusCircle, 
    Trash2, 
    CheckCircle2, 
    AlertCircle, 
    Clock, 
    Loader2, 
    Calendar, 
    Pencil, 
    X, 
    Check, 
    Sparkles 
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
});

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

onMounted(() => {
    fetchFixedBills();
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
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao remover conta.');
    }
};
</script>

<template>
    <Head title="Contas Blindadas & Orçamentos" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-16">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                        <ShieldCheck class="w-7 h-7 text-emerald-400" />
                        Blindagem de Contas Fixas & Orçamentos
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Proteja o valor das suas contas obrigatórias. O saldo das contas pendentes é blindado e deduzido do Teto Diário Seguro.
                    </p>
                </div>

                <button 
                    @click="showAddForm = !showAddForm"
                    class="btn-shimmer inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all self-start sm:self-auto"
                >
                    <PlusCircle class="w-4 h-4" />
                    <span>{{ showAddForm ? 'Fechar Formulário' : 'Nova Conta Fixa' }}</span>
                </button>
            </div>

            <!-- Summary Cards & Progress Bar Banner -->
            <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-800 bg-slate-900/60 relative overflow-hidden">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            Total Previsto no Mês
                        </span>
                        <div class="text-2xl sm:text-3xl font-black text-white font-display">
                            {{ formatCurrency(summary.total_planned) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Soma dos tetos de todas as contas fixas cadastradas.
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-emerald-400 block mb-1">
                            Já Pagas / Debitadas
                        </span>
                        <div class="text-2xl sm:text-3xl font-black text-emerald-400 font-display">
                            {{ formatCurrency(summary.total_paid) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            {{ summary.count_paid }} contas já totalmente cobertas este mês.
                        </p>
                    </div>

                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-rose-400 block mb-1">
                            Ainda Blindado no Teto
                        </span>
                        <div class="text-2xl sm:text-3xl font-black text-rose-400 font-display">
                            {{ formatCurrency(summary.total_pending) }}
                        </div>
                        <p class="text-xs text-slate-500 mt-1">
                            Valor retido para impedir que você gaste o dinheiro das contas.
                        </p>
                    </div>
                </div>

                <!-- Progress Bar -->
                <div class="space-y-1.5 pt-4 border-t border-slate-800/80">
                    <div class="flex items-center justify-between text-xs text-slate-400">
                        <span>Progresso de Quitação de Contas no Mês</span>
                        <span class="font-bold text-emerald-400">{{ summary.percent_shielded }}% pago</span>
                    </div>
                    <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
                        <div 
                            class="h-full bg-gradient-to-r from-teal-500 to-emerald-400 transition-all duration-500 rounded-full"
                            :style="{ width: `${summary.percent_shielded}%` }"
                        ></div>
                    </div>
                </div>
            </div>

            <!-- Add Bill Collapsible Form -->
            <div 
                v-if="showAddForm"
                class="glass-panel rounded-3xl p-6 border border-emerald-500/30 bg-slate-900/90 animate-fadeIn space-y-4"
            >
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <Sparkles class="w-4 h-4 text-emerald-400" />
                    Cadastrar Nova Despesa Fixa Recorrente
                </h3>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Nome da Obrigação</label>
                        <input 
                            v-model="newBill.name" 
                            type="text" 
                            placeholder="Ex: Aluguel Ap, Plano Unimed" 
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
                        />
                    </div>

                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Tipo de Obrigação</label>
                        <select 
                            v-model="newBill.group_type"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
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
                            placeholder="0,00" 
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
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
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
                        />
                    </div>
                </div>

                <div class="flex items-center justify-end gap-2.5 pt-2">
                    <button 
                        @click="showAddForm = false"
                        type="button"
                        class="px-4 py-2 text-xs font-semibold text-slate-400 hover:text-white"
                    >
                        Cancelar
                    </button>
                    <button 
                        @click="createNewBill"
                        :disabled="isSaving"
                        type="button"
                        class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 disabled:opacity-50 transition-all"
                    >
                        <Loader2 v-if="isSaving" class="w-3.5 h-3.5 animate-spin" />
                        <span>Salvar Obrigação</span>
                    </button>
                </div>
            </div>

            <!-- Bills List Panel -->
            <div class="glass-panel rounded-3xl p-6 sm:p-8 border border-slate-800 space-y-3">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-400 mb-4">
                    Contas Fixas Cadastradas ({{ fixedBills.length }})
                </h3>

                <div v-if="isLoading" class="flex flex-col items-center justify-center py-10 text-slate-400">
                    <Loader2 class="w-6 h-6 animate-spin text-emerald-400 mb-2" />
                    <span class="text-xs">Carregando contas blindadas...</span>
                </div>

                <div 
                    v-else-if="fixedBills.length === 0" 
                    class="text-center py-10 text-slate-500 text-xs"
                >
                    Nenhuma conta fixa cadastrada. Clique em "+ Nova Conta Fixa" para adicionar.
                </div>

                <div 
                    v-for="bill in fixedBills" 
                    :key="bill.id"
                    class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800/80 hover:border-slate-700 transition-all flex flex-col sm:flex-row sm:items-center justify-between gap-4"
                >
                    <!-- Left Info -->
                    <div class="min-w-0 flex items-center gap-3.5">
                        <div 
                            class="w-10 h-10 rounded-2xl flex items-center justify-center shrink-0 border"
                            :class="bill.is_paid ? 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400' : (bill.budget_ceiling ? 'bg-amber-500/10 border-amber-500/30 text-amber-400' : 'bg-slate-800/50 border-slate-700 text-slate-400')"
                        >
                            <CheckCircle2 v-if="bill.is_paid" class="w-5 h-5" />
                            <Clock v-else class="w-5 h-5" />
                        </div>

                        <div class="min-w-0">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-white text-sm sm:text-base truncate">{{ bill.name }}</h4>
                                <span 
                                    v-if="bill.due_day" 
                                    class="inline-flex items-center gap-1 text-[10px] font-semibold text-slate-400 bg-slate-900 border border-slate-800 px-2 py-0.5 rounded-md"
                                >
                                    <Calendar class="w-2.5 h-2.5 text-teal-400" />
                                    Vence dia {{ bill.due_day }}
                                </span>
                            </div>

                            <p class="text-xs text-slate-400 mt-0.5">
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

                    <!-- Right Status / Actions -->
                    <div class="flex items-center gap-2 shrink-0 self-end sm:self-center">
                        
                        <!-- Inline Edit Mode -->
                        <div v-if="editingId === bill.id" class="flex items-center gap-1.5">
                            <input 
                                v-model="editForm.budget_ceiling"
                                type="number"
                                step="0.01"
                                placeholder="Valor R$"
                                class="w-24 bg-slate-900 border border-emerald-500/50 rounded-xl px-2.5 py-1 text-xs text-white focus:outline-none"
                            />
                            <input 
                                v-model="editForm.due_day"
                                type="number"
                                min="1"
                                max="31"
                                placeholder="Dia"
                                class="w-16 bg-slate-900 border border-emerald-500/50 rounded-xl px-2.5 py-1 text-xs text-white focus:outline-none"
                            />
                            <button 
                                @click="saveEditing(bill)"
                                :disabled="isSaving"
                                class="px-3 py-1 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 disabled:opacity-50"
                            >
                                Ok
                            </button>
                            <button 
                                @click="cancelEditing"
                                class="p-1 rounded-xl text-slate-400 hover:text-white"
                            >
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- View Mode Badges & Buttons -->
                        <div v-else class="flex items-center gap-2">
                            <span 
                                v-if="bill.status === 'paid'"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-emerald-400 bg-emerald-500/15 border border-emerald-500/30 px-2.5 py-1 rounded-xl"
                            >
                                <CheckCircle2 class="w-3.5 h-3.5" />
                                Quitada
                            </span>
                            <span 
                                v-else-if="bill.status === 'partial'"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-amber-400 bg-amber-500/15 border border-amber-500/30 px-2.5 py-1 rounded-xl"
                            >
                                <AlertCircle class="w-3.5 h-3.5" />
                                Falta {{ formatCurrency(bill.pending_amount) }}
                            </span>
                            <span 
                                v-else-if="bill.status === 'pending'"
                                class="inline-flex items-center gap-1 text-[11px] font-semibold text-rose-300 bg-rose-500/15 border border-rose-500/30 px-2.5 py-1 rounded-xl"
                            >
                                Blindado ({{ formatCurrency(bill.pending_amount) }})
                            </span>

                            <button 
                                @click="startEditing(bill)"
                                class="px-3 py-1.5 rounded-xl bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white text-xs font-semibold transition-colors"
                            >
                                {{ bill.budget_ceiling ? 'Ajustar' : '+ Definir Valor' }}
                            </button>

                            <button 
                                v-if="bill.is_custom"
                                @click="deleteCustomBill(bill)"
                                class="p-1.5 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                                title="Excluir conta personalizada"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>

                    </div>
                </div>
            </div>

        </div>
    </AppLayout>
</template>
