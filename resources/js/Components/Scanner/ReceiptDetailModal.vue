<script setup>
import { 
    X, 
    Receipt, 
    Store, 
    Calendar, 
    CreditCard, 
    CheckCircle2, 
    ShoppingBag, 
    Sparkles, 
    Flame,
    Tag,
    Scale
} from 'lucide-vue-next';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';

const props = defineProps({
    isOpen: Boolean,
    scan: Object,
});

const emit = defineEmits(['close']);
const { formatCurrency } = useCurrencyFormat();

const getCategoryLabel = (category) => {
    const labels = {
        'alimentacao_essencial': { name: 'Alimentação Essencial', color: 'text-emerald-400 bg-emerald-500/15 border-emerald-500/30' },
        'limpeza': { name: 'Limpeza & Higiene', color: 'text-blue-400 bg-blue-500/15 border-blue-500/30' },
        'superfluo': { name: 'Supérfluo / Impulso', color: 'text-amber-400 bg-amber-500/15 border-amber-500/30' },
        'bebidas': { name: 'Bebidas & Lazer', color: 'text-purple-400 bg-purple-500/15 border-purple-500/30' },
        'outros': { name: 'Outros', color: 'text-slate-400 bg-slate-800 border-slate-700' },
    };
    return labels[category] || labels['outros'];
};
</script>

<template>
    <div v-if="isOpen && scan" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="glass-panel w-full max-w-2xl rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl relative overflow-hidden flex flex-col my-auto max-h-[92vh]">
            
            <!-- Modal Header (Estilo Cupom Fiscal Moderno) -->
            <div class="p-6 border-b border-slate-800 bg-slate-950/80 flex items-center justify-between sticky top-0 z-10 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <Receipt class="w-5 h-5" />
                    </div>
                    <div>
                        <div class="flex items-center gap-2">
                            <h3 class="text-base font-extrabold text-white">
                                {{ scan.merchant_name || 'Estabelecimento Comercial' }}
                            </h3>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">
                                Dados OCR
                            </span>
                        </div>
                        <p class="text-xs text-slate-400 mt-0.5">
                            CNPJ: {{ scan.merchant_tax_id || 'Não identificado' }} • {{ scan.purchased_at ? scan.purchased_at.substring(0, 16) : '' }}
                        </p>
                    </div>
                </div>

                <button 
                    @click="emit('close')"
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    title="Fechar comprovante"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Content Area (Scrollable Items List) -->
            <div class="p-6 overflow-y-auto space-y-6">
                
                <!-- Summary Metrics of the Receipt -->
                <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Valor Total</span>
                        <div class="text-lg font-black text-emerald-400 font-display mt-0.5">
                            {{ formatCurrency(scan.total_amount) }}
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Total de Itens</span>
                        <div class="text-lg font-black text-white font-display mt-0.5">
                            {{ scan.items ? scan.items.length : 0 }} produtos
                        </div>
                    </div>

                    <div class="p-3.5 rounded-2xl bg-slate-950/60 border border-slate-800 col-span-2 sm:col-span-1">
                        <span class="text-[10px] font-bold text-slate-400 uppercase tracking-wider block">Pagamento</span>
                        <div class="text-sm font-bold text-slate-200 mt-1 capitalize truncate">
                            {{ scan.payment_method_detected || 'Débito' }} 
                            <span v-if="scan.card_last_digits">({{ scan.card_last_digits }})</span>
                        </div>
                    </div>
                </div>

                <!-- Products Table / List -->
                <div class="space-y-3">
                    <div class="flex items-center justify-between pb-2 border-b border-slate-800">
                        <h4 class="text-xs font-bold text-slate-300 uppercase tracking-wider flex items-center gap-1.5">
                            <ShoppingBag class="w-4 h-4 text-emerald-400" />
                            <span>Itens Identificados pela IA</span>
                        </h4>
                        <span class="text-[11px] text-slate-400">Sem arquivos físicos pesados salvos</span>
                    </div>

                    <div v-if="!scan.items || scan.items.length === 0" class="p-8 text-center text-slate-400 text-xs">
                        Nenhum item individual detalhado neste cupom (apenas o valor total foi identificado).
                    </div>

                    <div v-else class="divide-y divide-slate-800/60 border border-slate-800/80 rounded-2xl bg-slate-950/40 overflow-hidden">
                        <div 
                            v-for="(item, idx) in scan.items" 
                            :key="item.id || idx"
                            class="p-3 sm:p-4 flex items-center justify-between gap-3 hover:bg-slate-900/40 transition-colors"
                        >
                            <div class="space-y-1 min-w-0">
                                <div class="text-xs font-bold text-white truncate">
                                    {{ item.item_name }}
                                </div>
                                <div class="flex items-center gap-2 text-[10px] text-slate-400">
                                    <span>Qtd: <strong>{{ Number(item.quantity) }} {{ item.unit || 'UN' }}</strong></span>
                                    <span>• Un: {{ formatCurrency(item.unit_price) }}</span>
                                    <span 
                                        class="px-2 py-0.5 rounded-full text-[9px] font-bold border"
                                        :class="getCategoryLabel(item.item_category).color"
                                    >
                                        {{ getCategoryLabel(item.item_category).name }}
                                    </span>
                                </div>
                            </div>

                            <div class="text-right shrink-0">
                                <span class="text-xs font-bold text-emerald-400 font-display">
                                    {{ formatCurrency(item.total_price) }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Reconciliation Status Box -->
                <div class="p-4 rounded-2xl bg-slate-950/60 border border-slate-800 flex items-center justify-between gap-3 text-xs">
                    <div class="flex items-center gap-2">
                        <CheckCircle2 class="w-4 h-4 text-emerald-400 shrink-0" />
                        <span class="text-slate-300">Status de Conciliação no Sistema:</span>
                    </div>
                    <span 
                        class="px-2.5 py-1 rounded-xl text-[11px] font-bold"
                        :class="scan.match_status === 'matched' 
                            ? 'bg-teal-500/15 text-teal-400 border border-teal-500/30' 
                            : (scan.match_status === 'manual_created' 
                                ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' 
                                : 'bg-amber-500/15 text-amber-400 border border-amber-500/30')"
                    >
                        {{ scan.match_status === 'matched' ? 'Conciliado com Extrato' : (scan.match_status === 'manual_created' ? 'Lançado no Saldo' : 'Pendente de Vínculo') }}
                    </span>
                </div>

            </div>

            <!-- Footer -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-between px-6">
                <span class="text-[11px] text-slate-400">🔒 Dados preservados na base. Imagens descartadas para máxima privacidade e economia de disco.</span>
                <button 
                    @click="emit('close')"
                    class="px-5 py-2 rounded-xl bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-colors cursor-pointer"
                >
                    Fechar
                </button>
            </div>

        </div>
    </div>
</template>
