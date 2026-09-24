<script setup>
import { ref, reactive } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    Building2, 
    PlusCircle, 
    Wallet, 
    CreditCard, 
    Banknote, 
    Pencil, 
    Trash2, 
    Check, 
    X, 
    Loader2, 
    TrendingUp,
    ShieldAlert
} from 'lucide-vue-next';

const props = defineProps({
    user: Object,
    accounts: Array,
    totalBalance: Number,
    accountTypes: Array,
});

const { formatCurrency } = useCurrencyFormat();

const showAddForm = ref(false);
const isSubmitting = ref(false);

const newAccount = reactive({
    name: '',
    type: 'checking',
    bank_name: 'Nubank',
    current_balance: '',
});

const editingAccountId = ref(null);
const editForm = reactive({
    name: '',
    type: '',
    bank_name: '',
    current_balance: '',
});

const popularBanks = [
    'Nubank',
    'Banco do Brasil',
    'Itaú',
    'Bradesco',
    'Inter',
    'Caixa',
    'Santander',
    'C6 Bank',
    'Carteira Física',
    'Outro'
];

const startEditing = (acc) => {
    editingAccountId.value = acc.id;
    editForm.name = acc.name;
    editForm.type = acc.type;
    editForm.bank_name = acc.bank_name || '';
    editForm.current_balance = acc.current_balance;
};

const cancelEditing = () => {
    editingAccountId.value = null;
};

const saveEditing = async (acc) => {
    isSubmitting.value = true;
    try {
        await window.axios.put(`/contas/${acc.id}`, {
            name: editForm.name,
            type: editForm.type,
            bank_name: editForm.bank_name,
            current_balance: parseFloat(editForm.current_balance),
        });

        editingAccountId.value = null;
        router.reload();
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao atualizar conta.');
    } finally {
        isSubmitting.value = false;
    }
};

const createAccount = async () => {
    if (!newAccount.name || newAccount.current_balance === '') {
        alert('Informe o nome da conta e o saldo atual.');
        return;
    }

    isSubmitting.value = true;
    try {
        await window.axios.post('/contas', {
            name: newAccount.name,
            type: newAccount.type,
            bank_name: newAccount.bank_name,
            current_balance: parseFloat(newAccount.current_balance),
        });

        newAccount.name = '';
        newAccount.current_balance = '';
        showAddForm.value = false;
        router.reload();
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao criar conta.');
    } finally {
        isSubmitting.value = false;
    }
};

const deleteAccount = async (acc) => {
    if (!confirm(`Deseja realmente excluir a conta "${acc.name}"? Todos os lançamentos e extratos desta conta também serão excluídos!`)) {
        return;
    }

    try {
        await window.axios.delete(`/contas/${acc.id}`);
        router.reload();
    } catch (err) {
        alert(err.response?.data?.message || 'Erro ao excluir conta.');
    }
};

const getAccountIcon = (type) => {
    if (type === 'credit_card') return CreditCard;
    if (type === 'cash') return Banknote;
    if (type === 'savings') return Wallet;
    return Building2;
};
</script>

<template>
    <Head title="Contas & Bancos" />

    <AppLayout :user="user">
        <div class="space-y-6 pb-16">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight flex items-center gap-2.5">
                        <Building2 class="w-7 h-7 text-emerald-400" />
                        Instituições & Contas Bancárias
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Gerencie seus bancos, contas correntes, cartões e carteira física com reconciliação de saldo.
                    </p>
                </div>

                <button 
                    @click="showAddForm = !showAddForm"
                    class="inline-flex items-center gap-2 px-4 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 transition-all self-start sm:self-auto"
                >
                    <PlusCircle class="w-4 h-4" />
                    <span>{{ showAddForm ? 'Fechar Formulário' : 'Nova Conta Bancária' }}</span>
                </button>
            </div>

            <!-- Consolidated Balance Banner -->
            <div class="glass-panel rounded-2xl p-6 border border-slate-800 bg-slate-900/40 relative overflow-hidden">
                <div class="absolute -right-8 -bottom-8 w-40 h-40 bg-emerald-500/10 rounded-full blur-3xl pointer-events-none"></div>

                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                    <div>
                        <span class="text-xs font-bold uppercase tracking-wider text-slate-400 block mb-1">
                            Saldo Consolidado Total (Patrimônio Líquido)
                        </span>
                        <div class="text-3xl sm:text-4xl font-black text-white font-display">
                            {{ formatCurrency(totalBalance) }}
                        </div>
                        <p class="text-xs text-emerald-400/90 mt-1">
                            Soma líquida de todas as suas contas ativas no momento.
                        </p>
                    </div>

                    <div class="flex items-center gap-3">
                        <div class="px-3.5 py-2 rounded-xl bg-slate-950/60 border border-slate-800 text-xs text-slate-300">
                            <strong>{{ accounts.length }}</strong> contas cadastradas
                        </div>
                    </div>
                </div>
            </div>

            <!-- Add Account Form Collapsible -->
            <div 
                v-if="showAddForm"
                class="glass-panel rounded-2xl p-6 border border-emerald-500/30 bg-slate-900/90 animate-fadeIn space-y-4"
            >
                <h3 class="text-sm font-bold text-white flex items-center gap-2">
                    <PlusCircle class="w-4 h-4 text-emerald-400" />
                    Cadastrar Nova Conta ou Instituição
                </h3>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Nome de Identificação</label>
                        <input 
                            v-model="newAccount.name" 
                            type="text" 
                            placeholder="Ex: Nubank Principal, Itaú Salário" 
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
                        />
                    </div>

                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Instituição / Banco</label>
                        <select 
                            v-model="newAccount.bank_name"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
                        >
                            <option v-for="b in popularBanks" :key="b" :value="b">{{ b }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Tipo de Conta</label>
                        <select 
                            v-model="newAccount.type"
                            class="w-full bg-slate-950 border border-slate-700 rounded-xl px-3 py-2 text-xs text-white focus:outline-none focus:border-emerald-400"
                        >
                            <option v-for="t in accountTypes" :key="t.value" :value="t.value">{{ t.label }}</option>
                        </select>
                    </div>

                    <div>
                        <label class="text-[11px] text-slate-400 block mb-1">Saldo Atual (R$)</label>
                        <input 
                            v-model="newAccount.current_balance" 
                            type="number" 
                            step="0.01" 
                            placeholder="0,00" 
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
                        @click="createAccount"
                        :disabled="isSubmitting"
                        type="button"
                        class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 disabled:opacity-50 transition-all"
                    >
                        <Loader2 v-if="isSubmitting" class="w-3.5 h-3.5 animate-spin" />
                        <span>Salvar Conta</span>
                    </button>
                </div>
            </div>

            <!-- Accounts Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                <div 
                    v-for="acc in accounts" 
                    :key="acc.id"
                    class="glass-panel rounded-2xl p-5 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between group"
                >
                    <!-- View Mode -->
                    <div v-if="editingAccountId !== acc.id">
                        <div class="flex items-start justify-between gap-3 mb-4">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 flex items-center justify-center text-emerald-400 shrink-0">
                                    <component :is="getAccountIcon(acc.type)" class="w-5 h-5" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="font-bold text-white text-sm truncate">{{ acc.name }}</h3>
                                    <span class="text-slate-400 text-[11px] block">{{ acc.bank_name || 'Instituição Bancária' }}</span>
                                </div>
                            </div>

                            <span class="text-[10px] uppercase font-semibold px-2 py-0.5 rounded-md bg-slate-800 text-slate-300">
                                {{ acc.type }}
                            </span>
                        </div>

                        <div class="my-4 pt-3 border-t border-slate-800/80 flex items-baseline justify-between">
                            <div>
                                <span class="text-[10px] uppercase tracking-wider text-slate-400 block">Saldo Atual</span>
                                <span class="text-xl sm:text-2xl font-black text-white font-display">
                                    {{ formatCurrency(acc.current_balance) }}
                                </span>
                            </div>

                            <span class="text-[11px] text-slate-500">
                                {{ acc.transactions_count }} lançamentos
                            </span>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2 border-t border-slate-800/60">
                            <button 
                                @click="startEditing(acc)"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl text-xs font-semibold text-slate-300 bg-slate-900 border border-slate-800 hover:border-emerald-500/40 hover:text-white transition-all"
                            >
                                <Pencil class="w-3.5 h-3.5 text-emerald-400" />
                                <span>Reconciliar / Editar</span>
                            </button>

                            <button 
                                @click="deleteAccount(acc)"
                                class="p-1.5 rounded-xl text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                                title="Excluir conta"
                            >
                                <Trash2 class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Edit Mode -->
                    <div v-else class="space-y-3">
                        <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                            <h4 class="text-xs font-bold text-emerald-400">Reconciliação / Edição</h4>
                            <button @click="cancelEditing" class="text-slate-400 hover:text-white">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-400 block mb-1">Nome da Conta</label>
                            <input 
                                v-model="editForm.name" 
                                type="text" 
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white"
                            />
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-400 block mb-1">Instituição / Banco</label>
                            <select 
                                v-model="editForm.bank_name"
                                class="w-full bg-slate-950 border border-slate-700 rounded-xl px-2.5 py-1.5 text-xs text-white"
                            >
                                <option v-for="b in popularBanks" :key="b" :value="b">{{ b }}</option>
                            </select>
                        </div>

                        <div>
                            <label class="text-[10px] text-slate-400 block mb-1">Saldo Atual Reconciliado (R$)</label>
                            <input 
                                v-model="editForm.current_balance" 
                                type="number" 
                                step="0.01"
                                class="w-full bg-slate-950 border border-emerald-500/60 rounded-xl px-2.5 py-1.5 text-xs text-white font-bold"
                            />
                            <p class="text-[10px] text-slate-500 mt-1">
                                Digite o saldo que consta exatamente hoje no app do seu banco.
                            </p>
                        </div>

                        <div class="flex items-center justify-end gap-2 pt-2">
                            <button 
                                @click="cancelEditing"
                                class="px-3 py-1.5 text-xs text-slate-400 hover:text-white"
                            >
                                Cancelar
                            </button>
                            <button 
                                @click="saveEditing(acc)"
                                :disabled="isSubmitting"
                                class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-emerald-500 text-slate-950 font-bold text-xs hover:bg-emerald-400 disabled:opacity-50 transition-all"
                            >
                                <Check class="w-3.5 h-3.5" />
                                <span>Salvar</span>
                            </button>
                        </div>
                    </div>

                </div>
            </div>

        </div>
    </AppLayout>
</template>
