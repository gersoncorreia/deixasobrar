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
    ExternalLink
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

const form = ref({
    account_id: props.accounts?.[0]?.id || '',
    category_id: props.categories?.[0]?.id || '',
    description: props.scan.merchant_name || 'Compra Registrada',
    amount: props.scan.total_amount || 0.00,
    transaction_date: props.scan.purchased_at ? props.scan.purchased_at.substring(0, 10) : new Date().toISOString().substring(0, 10),
});

const handleConfirm = async () => {
    isSubmitting.value = true;
    try {
        const response = await window.axios.post(`/scanner/${props.scan.id}/confirm`, form.value);
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
    <div class="glass-panel p-6 rounded-3xl border border-slate-800 bg-slate-900/90 space-y-6">
        
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
                    <span class="inline-block px-2.5 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 mb-1">
                        OCR Concluído com Sucesso
                    </span>
                    <h3 class="text-base font-extrabold text-white">Revisão do Comprovante</h3>
                    <p class="text-xs text-slate-400">Confira os valores detectados antes de salvar</p>
                </div>
            </div>

            <div class="text-right">
                <span class="text-xs text-slate-400 block">Total Identificado</span>
                <span class="text-2xl font-black text-emerald-400 font-display">
                    {{ formatCurrency(form.amount) }}
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

        <!-- Items Breakdown Drawer (Raio-X de Carrinho) -->
        <div v-if="scan.items && scan.items.length > 0" class="pt-2">
            <button 
                type="button" 
                @click="showItems = !showItems"
                class="flex items-center justify-between w-full p-3 rounded-2xl bg-slate-950/70 border border-slate-800 text-xs font-bold text-slate-300 hover:text-white"
            >
                <div class="flex items-center gap-2">
                    <ShoppingBag class="w-4 h-4 text-emerald-400" />
                    <span>Raio-X dos Itens do Cupom ({{ scan.items.length }} produtos encontrados)</span>
                </div>
                <component :is="showItems ? ChevronUp : ChevronDown" class="w-4 h-4 text-slate-500" />
            </button>

            <div v-show="showItems" class="mt-2 rounded-2xl border border-slate-800 bg-slate-950/40 overflow-hidden divide-y divide-slate-800/60 text-xs">
                <div v-for="item in scan.items" :key="item.id" class="p-3 flex items-center justify-between">
                    <div>
                        <div class="font-bold text-white">{{ item.item_name }}</div>
                        <div class="text-[10px] text-slate-400">
                            {{ item.quantity }} {{ item.unit }} x {{ formatCurrency(item.unit_price) }}
                        </div>
                    </div>
                    <div class="flex items-center gap-3">
                        <span class="px-2 py-0.5 rounded text-[10px] font-bold"
                            :class="item.item_category === 'superfluo' ? 'bg-amber-500/15 text-amber-300' : 'bg-slate-800 text-slate-300'"
                        >
                            {{ item.item_category === 'superfluo' ? 'Supérfluo' : 'Essencial' }}
                        </span>
                        <span class="font-bold text-white font-display">
                            {{ formatCurrency(item.total_price) }}
                        </span>
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
