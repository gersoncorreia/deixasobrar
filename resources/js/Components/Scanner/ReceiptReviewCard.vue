<script setup>
import { ref } from 'vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    Check, 
    FileText, 
    Calendar, 
    DollarSign, 
    Building2, 
    ShoppingBag, 
    Tag, 
    ChevronDown, 
    ChevronUp,
    ExternalLink,
    Trash2
} from 'lucide-vue-next';

const props = defineProps({
    scan: Object,
    accounts: Array,
    categories: Array,
});

const emit = defineEmits(['confirmed']);

const { formatCurrency } = useCurrencyFormat();

const showItems = ref(true);
const isSubmitting = ref(false);

const editableItems = ref(
    (props.scan.items || []).map(i => ({
        id: i.id,
        item_name: i.item_name,
        quantity: i.quantity,
        unit_price: i.unit_price,
        total_price: i.total_price,
        item_category: i.item_category || 'alimentacao_essencial'
    }))
);

const form = ref({
    account_id: props.accounts?.[0]?.id || '',
    category_id: props.categories?.[0]?.id || '',
    description: props.scan.merchant_name || 'Compra Registrada',
    amount: props.scan.total_amount || 0.00,
    transaction_date: props.scan.purchased_at ? props.scan.purchased_at.substring(0, 10) : new Date().toISOString().substring(0, 10),
});

const updateItemTotal = (item) => {
    item.total_price = Math.round((item.quantity * item.unit_price) * 100) / 100;
    recalculateFromItems();
};

const recalculateFromItems = () => {
    if (editableItems.value.length > 0) {
        const sum = editableItems.value.reduce((acc, curr) => acc + (parseFloat(curr.total_price) || 0), 0);
        form.value.amount = Math.round(sum * 100) / 100;
    }
};

const removeItem = (index) => {
    editableItems.value.splice(index, 1);
    recalculateFromItems();
};

const handleConfirm = async () => {
    isSubmitting.value = true;
    try {
        const payload = {
            ...form.value,
            items: editableItems.value,
        };
        const response = await window.axios.post(`/scanner/${props.scan.id}/confirm`, payload);
        if (response.data?.success) {
            emit('confirmed', response.data);
        }
    } catch (err) {
        alert(err.response?.data?.message || 'Falha ao confirmar o lançamento.');
    } finally {
        isSubmitting.value = false;
    }
};
</script>

<template>
    <div class="glass-panel p-6 rounded-2xl border border-slate-800 bg-slate-900/90 space-y-6">
        
        <!-- Header & Image Preview -->
        <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 pb-4 border-b border-slate-800">
            <div class="flex items-center gap-3">
                <div v-if="scan.image_path" class="w-14 h-14 rounded-2xl bg-slate-950 overflow-hidden border border-slate-700 shrink-0 relative group">
                    <img :src="scan.image_path" alt="Cupom" class="w-full h-full object-cover" />
                    <a 
                        :href="scan.image_path" 
                        target="_blank" 
                        class="absolute inset-0 bg-black/60 flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity"
                        title="Ver foto inteira"
                    >
                        <ExternalLink class="w-4 h-4 text-white" />
                    </a>
                </div>

                <div>
                    <div class="flex items-center gap-2 mb-1">
                        <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 border border-emerald-500/30 text-emerald-400">
                            OCR Concluído com Sucesso
                        </span>
                        <span v-if="scan.payment_method_detected" class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-slate-800 border border-slate-700 text-slate-300 capitalize">
                            💳 {{ scan.payment_method_detected === 'debit' ? 'Cartão Débito' : (scan.payment_method_detected === 'credit' ? 'Cartão Crédito' : scan.payment_method_detected) }}
                            <span v-if="scan.card_last_digits">(Final {{ scan.card_last_digits }})</span>
                        </span>
                    </div>
                    <h3 class="text-base font-extrabold text-white">Revisão do Comprovante</h3>
                    <p class="text-xs text-slate-400">Confira os valores detectados antes de salvar</p>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs text-slate-400 block">Total Identificado</span>
                <span class="text-2xl font-black text-emerald-400 font-display">
                    {{ formatCurrency(form.amount) }}
                </span>
                <span v-if="scan.raw_ocr_payload?.discount > 0" class="block text-[10px] font-semibold text-teal-400">
                    Desconto: -{{ formatCurrency(scan.raw_ocr_payload.discount) }}
                </span>
            </div>
        </div>

        <!-- Editable Form Grid -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Estabelecimento / Descrição</label>
                <input 
                    v-model="form.description"
                    type="text"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                />
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Valor Total (R$)</label>
                <input 
                    v-model="form.amount"
                    type="number"
                    step="0.01"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                />
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Debitar da Conta</label>
                <select 
                    v-model="form.account_id"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                >
                    <option v-for="acc in accounts" :key="acc.id" :value="acc.id">
                        {{ acc.name }} (Saldo: {{ formatCurrency(acc.current_balance) }})
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-300 mb-1">Categoria</label>
                <select 
                    v-model="form.category_id"
                    class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700/80 rounded-xl text-xs text-white focus:outline-none focus:border-emerald-500"
                >
                    <option value="">Sem categoria definida</option>
                    <option v-for="cat in categories" :key="cat.id" :value="cat.id">
                        {{ cat.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- Items Breakdown Drawer (Raio-X de Carrinho & Edição) -->
        <div v-if="editableItems.length > 0" class="pt-2">
            <button 
                type="button" 
                @click="showItems = !showItems"
                class="flex items-center justify-between w-full p-3 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white"
            >
                <div class="flex items-center gap-2">
                    <ShoppingBag class="w-4 h-4 text-emerald-400" />
                    <span>Produtos Encontrados na Nota ({{ editableItems.length }}) - Clique para conferir ou ajustar</span>
                </div>
                <component :is="showItems ? ChevronUp : ChevronDown" class="w-4 h-4 text-slate-500" />
            </button>

            <div v-show="showItems" class="mt-2 rounded-2xl border border-slate-800 bg-slate-950/40 overflow-hidden divide-y divide-slate-800/60 text-xs">
                <div v-for="(item, idx) in editableItems" :key="idx" class="p-3 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div class="flex-1 min-w-0">
                        <input 
                            v-model="item.item_name"
                            type="text"
                            placeholder="Nome do produto"
                            class="w-full bg-slate-900 border border-slate-700/60 rounded-lg px-2.5 py-1 text-xs text-white font-semibold focus:outline-none focus:border-emerald-500"
                        />
                        <div class="flex items-center gap-2 mt-1.5 flex-wrap">
                            <label class="text-[10px] text-slate-400 flex items-center gap-1">
                                Qtd:
                                <input 
                                    v-model.number="item.quantity"
                                    type="number"
                                    step="0.001"
                                    min="0.001"
                                    @input="updateItemTotal(item)"
                                    class="w-16 bg-slate-900 border border-slate-700/60 rounded px-1.5 py-0.5 text-xs text-white text-center"
                                />
                            </label>

                            <label class="text-[10px] text-slate-400 flex items-center gap-1">
                                Unit (R$):
                                <input 
                                    v-model.number="item.unit_price"
                                    type="number"
                                    step="0.01"
                                    @input="updateItemTotal(item)"
                                    class="w-20 bg-slate-900 border border-slate-700/60 rounded px-1.5 py-0.5 text-xs text-white text-center"
                                />
                            </label>

                            <select
                                v-model="item.item_category"
                                class="bg-slate-900 border border-slate-700/60 rounded px-2 py-0.5 text-[10px] text-slate-300"
                            >
                                <option value="alimentacao_essencial">Essencial</option>
                                <option value="superfluo">Supérfluo / Impulso</option>
                                <option value="limpeza">Limpeza</option>
                                <option value="bebidas">Bebidas</option>
                                <option value="outros">Outros</option>
                            </select>
                        </div>
                    </div>

                    <div class="flex items-center justify-between sm:justify-end gap-3 shrink-0">
                        <div class="text-right">
                            <span class="text-[10px] text-slate-400 block sm:hidden">Total Item:</span>
                            <div class="flex items-center gap-1">
                                <span class="text-xs text-slate-400 font-bold">R$</span>
                                <input 
                                    v-model.number="item.total_price"
                                    type="number"
                                    step="0.01"
                                    @input="recalculateFromItems"
                                    class="w-20 bg-slate-900 border border-emerald-500/40 rounded-lg px-2 py-1 text-xs text-emerald-300 font-bold font-display text-right focus:border-emerald-400"
                                />
                            </div>
                        </div>

                        <button 
                            type="button"
                            @click="removeItem(idx)"
                            class="p-1.5 text-slate-500 hover:text-rose-400 hover:bg-rose-500/10 rounded-lg transition-colors"
                            title="Remover este item da nota"
                        >
                            <Trash2 class="w-3.5 h-3.5" />
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Action Button -->
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-800">
            <button 
                @click="handleConfirm"
                :disabled="isSubmitting"
                class="btn-shimmer inline-flex items-center gap-2 px-6 py-2.5 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold text-xs sm:text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all cursor-pointer"
            >
                <Check class="w-4 h-4" />
                <span>{{ isSubmitting ? 'Salvando...' : 'Confirmar e Salvar Lançamento' }}</span>
            </button>
        </div>

    </div>
</template>
