<script setup>
import { ref, computed } from 'vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Calculator, CheckCircle2, AlertTriangle, ShieldAlert, Sparkles, ArrowRight } from 'lucide-vue-next';

const props = defineProps({
    safeToSpend: {
        type: Object,
        required: true,
    },
});

const { formatCurrency } = useCurrencyFormat();

const simulatedAmount = ref('');
const quickAmounts = [50, 100, 200, 400];

const numericAmount = computed(() => {
    const val = parseFloat(simulatedAmount.value);
    return isNaN(val) || val < 0 ? 0 : val;
});

const currentCeiling = computed(() => props.safeToSpend.daily_ceiling || 0);
const availableCapital = computed(() => props.safeToSpend.available_capital || 0);
const daysRemaining = computed(() => Math.max(1, props.safeToSpend.days_remaining || 1));
const safetyReserve = computed(() => props.safeToSpend.safety_reserve || 0);

const newAvailableCapital = computed(() => availableCapital.value - numericAmount.value);
const newDailyCeiling = computed(() => {
    return newAvailableCapital.value > 0 ? (newAvailableCapital.value / daysRemaining.value) : 0;
});
const dailyCeilingReduction = computed(() => {
    return currentCeiling.value - newDailyCeiling.value;
});

const simulationStatus = computed(() => {
    if (numericAmount.value <= 0) return 'idle';
    if (newAvailableCapital.value < -safetyReserve.value) return 'danger';
    if (newAvailableCapital.value < 0) return 'reserve';
    if (newDailyCeiling.value < 25) return 'tight';
    return 'safe';
});
</script>

<template>
    <div class="glass-panel rounded-2xl p-5 sm:p-6 border border-slate-800 bg-slate-900/60 relative overflow-hidden">
        <div class="flex items-center justify-between pb-4 border-b border-slate-800 mb-5">
            <div class="flex items-center gap-2 text-xs font-bold uppercase tracking-wider text-emerald-400">
                <Calculator class="w-4 h-4" />
                <span>Simulador de Compra: "Posso Comprar Isso Hoje?"</span>
            </div>
            <span class="text-[11px] text-slate-400">Simulação instantânea</span>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-12 gap-5 items-center">
            <!-- Left Input & Quick Chips -->
            <div class="lg:col-span-6 space-y-3">
                <label class="block text-xs text-slate-300 font-medium">
                    Quanto custa o item ou desejo que você quer comprar?
                </label>
                
                <div class="relative">
                    <span class="absolute left-3.5 top-1/2 -translate-y-1/2 text-slate-400 font-bold text-sm">R$</span>
                    <input 
                        type="number" 
                        step="0.01"
                        min="0"
                        v-model="simulatedAmount"
                        placeholder="Ex: 180,00"
                        class="w-full bg-slate-950 border border-slate-700 rounded-2xl pl-10 pr-4 py-2.5 text-sm text-white font-bold focus:outline-none focus:border-emerald-400 transition-colors"
                    />
                </div>

                <!-- Quick amount chips -->
                <div class="flex items-center gap-1.5 flex-wrap">
                    <span class="text-[11px] text-slate-400 mr-1">Atalhos:</span>
                    <button 
                        v-for="amt in quickAmounts" 
                        :key="amt"
                        @click="simulatedAmount = amt"
                        type="button"
                        class="px-2.5 py-1 rounded-xl text-xs font-semibold bg-slate-800 hover:bg-slate-700 text-slate-300 hover:text-white border border-slate-700/60 transition-all"
                    >
                        + R$ {{ amt }}
                    </button>
                    <button 
                        v-if="simulatedAmount"
                        @click="simulatedAmount = ''"
                        type="button"
                        class="text-[11px] text-slate-400 hover:text-rose-400 underline ml-2"
                    >
                        Limpar
                    </button>
                </div>
            </div>

            <!-- Right: Impact Verdict Card -->
            <div class="lg:col-span-6">
                <!-- Idle State -->
                <div v-if="simulationStatus === 'idle'" class="p-4 rounded-2xl bg-slate-950/40 border border-slate-800 text-center text-xs text-slate-500">
                    Digite um valor acima para ver o impacto exato no seu teto diário e na sua sobra do mês.
                </div>

                <!-- Safe State -->
                <div v-else-if="simulationStatus === 'safe'" class="p-4 rounded-2xl bg-emerald-500/10 border border-emerald-500/30 text-xs space-y-2 animate-fadeIn">
                    <div class="flex items-center gap-2 text-emerald-400 font-bold text-sm">
                        <CheckCircle2 class="w-4 h-4 shrink-0" />
                        <span>Compra 100% Segura! Pode comprar.</span>
                    </div>
                    <p class="text-emerald-200/90 text-xs">
                        Suas contas fixas continuarão blindadas. Seu teto seguro cairá apenas 
                        <strong class="text-white">-{{ formatCurrency(dailyCeilingReduction) }}/dia</strong>, ficando em 
                        <strong class="text-emerald-300 font-display text-sm">{{ formatCurrency(newDailyCeiling) }}/dia</strong>.
                    </p>
                </div>

                <!-- Tight State -->
                <div v-else-if="simulationStatus === 'tight'" class="p-4 rounded-2xl bg-amber-500/10 border border-amber-500/30 text-xs space-y-2 animate-fadeIn">
                    <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
                        <AlertTriangle class="w-4 h-4 shrink-0" />
                        <span>Viável, mas o orçamento ficará apertado!</span>
                    </div>
                    <p class="text-amber-200/90 text-xs">
                        Você pode comprar, mas seu teto diário cairá para 
                        <strong class="text-amber-300 font-display text-sm">{{ formatCurrency(newDailyCeiling) }}/dia</strong> 
                        pelos próximos {{ daysRemaining }} dias até o pagamento.
                    </p>
                </div>

                <!-- Reserve State -->
                <div v-else-if="simulationStatus === 'reserve'" class="p-4 rounded-2xl bg-amber-950/30 border border-amber-500/40 text-xs space-y-2 animate-fadeIn">
                    <div class="flex items-center gap-2 text-amber-400 font-bold text-sm">
                        <ShieldAlert class="w-4 h-4 shrink-0" />
                        <span>Atenção: Invadirá sua Reserva de Segurança!</span>
                    </div>
                    <p class="text-amber-200/90 text-xs">
                        Esta compra zerará seu teto diário livre e consumirá parte dos R$ {{ formatCurrency(safetyReserve) }} da sua reserva de emergência.
                    </p>
                </div>

                <!-- Danger State -->
                <div v-else-if="simulationStatus === 'danger'" class="p-4 rounded-2xl bg-rose-500/10 border border-rose-500/30 text-xs space-y-2 animate-fadeIn">
                    <div class="flex items-center gap-2 text-rose-400 font-bold text-sm">
                        <ShieldAlert class="w-4 h-4 shrink-0" />
                        <span>Alerta Vermelho: Risco de Inadimplência!</span>
                    </div>
                    <p class="text-rose-200/90 text-xs">
                        Esta compra invade o dinheiro reservado para suas contas fixas blindadas deste mês. Não recomendamos realizar este gasto agora.
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>
