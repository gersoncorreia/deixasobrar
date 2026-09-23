<script setup>
import { ref, computed, watch } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { useCountUp } from '@/Composables/useCountUp';
import { 
    Zap, 
    Calendar, 
    ArrowRight, 
    PiggyBank,
    Sparkles,
    CheckCircle
} from 'lucide-vue-next';

const { formatCurrency } = useCurrencyFormat();
const { animateNumber } = useCountUp();

const salary = ref(3800);
const fixedExpenses = ref(2100);
const daysUntilPayday = ref(15);

// Animated values for smooth easing numbers
const animatedDailyCeiling = ref(113.33);
const animatedFreeMargin = ref(1700);
const animatedSavings = ref(374);

// Calculations
const freeMargin = computed(() => Math.max(0, salary.value - fixedExpenses.value));
const dailyCeiling = computed(() => {
    if (daysUntilPayday.value <= 0) return 0;
    return freeMargin.value / daysUntilPayday.value;
});

const estimatedSavings = computed(() => {
    const raw = Math.round(freeMargin.value * 0.22);
    return Math.max(250, Math.min(raw, 750));
});

// Watch inputs and trigger smooth easing animations
watch(dailyCeiling, (newVal) => {
    animateNumber(animatedDailyCeiling, newVal, 400);
}, { immediate: true });

watch(freeMargin, (newVal) => {
    animateNumber(animatedFreeMargin, newVal, 400);
}, { immediate: true });

watch(estimatedSavings, (newVal) => {
    animateNumber(animatedSavings, newVal, 400);
}, { immediate: true });

// Health status for dynamic card border
const isHealthy = computed(() => dailyCeiling.value >= 40);
</script>

<template>
    <section id="simulador" class="py-24 relative overflow-hidden">
        <!-- Ambient background soft lighting -->
        <div class="absolute left-1/2 top-1/2 -translate-x-1/2 -translate-y-1/2 w-[700px] h-[400px] bg-emerald-500/10 rounded-full blur-[140px] pointer-events-none animate-breathe-glow"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center max-w-2xl mx-auto mb-14">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    Simulador Interativo
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-3 mb-3"
                >
                    Descubra seu Teto Diário Seguro Agora
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-sm sm:text-base"
                >
                    Coloque seus números aproximados e veja quanto você pode gastar livremente por dia.
                </p>
            </div>

            <!-- Calculator Card Container -->
            <div 
                v-reveal="{ delay: 200, direction: 'scale' }"
                class="max-w-4xl mx-auto glass-panel rounded-3xl p-6 sm:p-10 border border-slate-700/60 shadow-2xl relative overflow-hidden group hover:border-emerald-500/30 transition-all duration-300"
            >
                <div class="grid grid-cols-1 lg:grid-cols-12 gap-8 items-center">
                    
                    <!-- Left: Inputs -->
                    <div class="lg:col-span-7 space-y-6">
                        
                        <!-- Input 1: Salário / Renda Líquida -->
                        <div class="p-3 rounded-2xl bg-slate-900/40 border border-slate-800/60 hover:border-emerald-500/30 transition-colors">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-xs sm:text-sm font-semibold text-slate-200">
                                    Sua Renda / Salário Líquido Mensal
                                </label>
                                <span class="text-base font-bold text-emerald-400 font-display">
                                    {{ formatCurrency(salary) }}
                                </span>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="salary" 
                                min="1412" 
                                max="25000" 
                                step="100"
                                class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-emerald-400 transition-all hover:accent-emerald-300"
                            />
                            <div class="flex justify-between text-[11px] text-slate-500 mt-1.5">
                                <span>R$ 1.412</span>
                                <span>R$ 10.000</span>
                                <span>R$ 25.000+</span>
                            </div>
                        </div>

                        <!-- Input 2: Contas Fixas & Parcelas -->
                        <div class="p-3 rounded-2xl bg-slate-900/40 border border-slate-800/60 hover:border-rose-500/30 transition-colors">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-xs sm:text-sm font-semibold text-slate-200">
                                    Contas Fixas & Carnês (Aluguel, Luz, etc.)
                                </label>
                                <span class="text-base font-bold text-rose-400 font-display">
                                    {{ formatCurrency(fixedExpenses) }}
                                </span>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="fixedExpenses" 
                                min="500" 
                                :max="Math.max(salary, 3000)" 
                                step="50"
                                class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-rose-400 transition-all hover:accent-rose-300"
                            />
                            <div class="flex justify-between text-[11px] text-slate-500 mt-1.5">
                                <span>R$ 500</span>
                                <span>R$ 5.000</span>
                                <span>{{ formatCurrency(salary) }}</span>
                            </div>
                        </div>

                        <!-- Input 3: Dias até o próximo pagamento -->
                        <div class="p-3 rounded-2xl bg-slate-900/40 border border-slate-800/60 hover:border-teal-400/30 transition-colors">
                            <div class="flex justify-between items-center mb-2">
                                <label class="text-xs sm:text-sm font-semibold text-slate-200">
                                    Dias restantes até seu próximo salário
                                </label>
                                <span class="text-base font-bold text-teal-300 font-display">
                                    {{ daysUntilPayday }} dias
                                </span>
                            </div>
                            <input 
                                type="range" 
                                v-model.number="daysUntilPayday" 
                                min="1" 
                                max="31" 
                                step="1"
                                class="w-full h-2.5 bg-slate-800 rounded-lg appearance-none cursor-pointer accent-teal-400 transition-all hover:accent-teal-300"
                            />
                            <div class="flex justify-between text-[11px] text-slate-500 mt-1.5">
                                <span>Amanhã (1 dia)</span>
                                <span>15 dias</span>
                                <span>1 mês inteiro (31 dias)</span>
                            </div>
                        </div>

                    </div>

                    <!-- Right: Live Result Display with Neon Border & Shimmer -->
                    <div 
                        class="lg:col-span-5 rounded-2xl p-6 text-center relative overflow-hidden shadow-2xl transition-all duration-500"
                        :class="[
                            isHealthy 
                                ? 'bg-gradient-to-br from-emerald-950/80 via-slate-900/90 to-slate-950 border border-emerald-500/40 shadow-emerald-950/30' 
                                : 'bg-gradient-to-br from-amber-950/80 via-slate-900/90 to-slate-950 border border-amber-500/40 shadow-amber-950/30'
                        ]"
                    >
                        <span class="text-xs uppercase tracking-wider font-bold text-emerald-400 flex items-center justify-center gap-1.5 mb-2">
                            <Zap class="w-4 h-4 animate-bounce" />
                            Seu Teto Diário Seguro
                        </span>

                        <!-- Animated Count-Up Daily Value -->
                        <div class="text-4xl sm:text-5xl font-black text-white tracking-tight my-2 font-display">
                            {{ formatCurrency(animatedDailyCeiling) }}
                        </div>
                        <span class="text-xs text-slate-400">por dia para gastar sem culpa</span>

                        <div class="mt-5 pt-4 border-t border-slate-800 text-left space-y-2 text-xs">
                            <div class="flex justify-between text-slate-300">
                                <span>Dinheiro Livre no Mês:</span>
                                <strong class="text-white font-display">{{ formatCurrency(animatedFreeMargin) }}</strong>
                            </div>
                            <div class="flex justify-between text-slate-300">
                                <span>Dias a percorrer:</span>
                                <strong class="text-white font-display">{{ daysUntilPayday }} dias</strong>
                            </div>
                        </div>

                        <!-- Savings highlight callout with animated bouncing piggy -->
                        <div class="mt-4 p-3 rounded-xl bg-emerald-500/15 border border-emerald-400/30 text-emerald-200 text-xs flex items-center gap-2.5 text-left">
                            <PiggyBank class="w-5 h-5 text-emerald-400 shrink-0 animate-bounce" style="animation-duration: 2s;" />
                            <span>
                                Com o DeixaSobrar, você elimina em média <strong class="font-display">{{ formatCurrency(animatedSavings) }}/mês</strong> em gastos invisíveis.
                            </span>
                        </div>

                        <!-- Shimmer CTA button -->
                        <Link 
                            href="/register" 
                            class="btn-shimmer mt-5 w-full inline-flex items-center justify-center gap-2 py-3.5 px-4 rounded-xl bg-gradient-to-r from-emerald-400 to-teal-300 hover:from-emerald-300 hover:to-teal-200 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/20 hover:shadow-emerald-500/40 transition-all hover:scale-[1.02] active:scale-95"
                        >
                            Quero Proteger Meu Teto Diário
                            <ArrowRight class="w-4 h-4" />
                        </Link>

                    </div>

                </div>

            </div>

        </div>
    </section>
</template>
