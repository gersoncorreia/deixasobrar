<script setup>
import { ref, onMounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { useCardTilt } from '@/Composables/useCardTilt';
import { useCountUp } from '@/Composables/useCountUp';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    ShieldCheck, 
    ArrowRight, 
    Play, 
    Sparkles, 
    CheckCircle2, 
    TrendingUp, 
    AlertTriangle,
    Wallet,
    Calendar,
    Zap,
    Flame
} from 'lucide-vue-next';

const { tiltStyle, glareStyle, onMouseMove, onMouseLeave } = useCardTilt(12);
const { animateNumber } = useCountUp();
const { formatCurrency } = useCurrencyFormat();

const cardRef = ref(null);
const animatedDailyCeiling = ref(0);
const progressBarWidth = ref('0%');

onMounted(() => {
    // Smooth number count-up animation for the Daily Ceiling after card entrance
    setTimeout(() => {
        animateNumber(animatedDailyCeiling, 42.50, 1800);
    }, 1100);

    setTimeout(() => {
        progressBarWidth.value = '28%';
    }, 1350);
});
</script>

<template>
    <section class="relative pt-32 pb-20 md:pt-44 md:pb-32 overflow-hidden">
        <!-- Ambient Breathing Lighting Spheres -->
        <div class="absolute top-1/4 left-1/2 -translate-x-1/2 -translate-y-1/2 w-[600px] h-[350px] md:w-[950px] md:h-[520px] bg-emerald-500/15 rounded-full blur-[140px] pointer-events-none animate-breathe-glow"></div>
        <div class="absolute top-1/3 left-1/4 w-[350px] h-[350px] bg-teal-500/10 rounded-full blur-[110px] pointer-events-none animate-float-reverse"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-12 lg:gap-8 items-center">
                
                <!-- Left Column: Copy & Value Proposition -->
                <div class="lg:col-span-7 text-center lg:text-left">
                    
                    <!-- Value badge with gentle float -->
                    <div 
                        v-reveal="{ delay: 200, direction: 'scale' }"
                        class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-emerald-500/10 border border-emerald-500/30 text-emerald-400 text-xs sm:text-sm font-semibold mb-6 animate-float-slow hover:border-emerald-400 transition-colors shadow-lg shadow-emerald-950/40"
                    >
                        <Sparkles class="w-4 h-4 text-emerald-400 animate-spin" style="animation-duration: 8s;" />
                        <span>Metodologia Safe-to-Spend: Zero Economês</span>
                    </div>

                    <!-- Main Headline with blur-to-clear fade -->
                    <h1 
                        v-reveal="{ delay: 450, direction: 'up' }"
                        class="text-3xl sm:text-5xl lg:text-6xl font-extrabold text-white tracking-tight leading-[1.12] mb-6"
                    >
                        O controle financeiro que <span class="bg-gradient-to-r from-emerald-400 via-teal-300 to-emerald-200 bg-clip-text text-transparent">não te dá dor de cabeça</span>.
                    </h1>

                    <!-- Sub-headline with fade -->
                    <p 
                        v-reveal="{ delay: 700, direction: 'up' }"
                        class="text-base sm:text-lg lg:text-xl text-slate-300 leading-relaxed max-w-2xl mx-auto lg:mx-0 mb-8 font-normal"
                    >
                        Cuide dos seus gastos e <strong class="text-white font-semibold underline decoration-emerald-400/50 decoration-2 underline-offset-4">deixe sobrar para o que importa</strong>. Chega de planilhas complexas com nomes difíceis. Saiba exatamente quantos reais você pode gastar por dia sem faltar dinheiro para pagar suas contas.
                    </p>

                    <!-- Dual CTAs with fade -->
                    <div 
                        v-reveal="{ delay: 950, direction: 'up' }"
                        class="flex flex-col sm:flex-row items-center justify-center lg:justify-start gap-4 mb-10"
                    >
                        <Link 
                            href="/register"
                            class="btn-shimmer w-full sm:w-auto inline-flex items-center justify-center gap-3 px-8 py-4 text-base font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-teal-300 hover:from-emerald-300 hover:to-teal-200 rounded-2xl shadow-xl shadow-emerald-500/25 hover:shadow-emerald-400/40 hover:-translate-y-1 active:translate-y-0 active:scale-95 transition-all duration-200 group"
                        >
                            Comece Grátis em 1 Minuto
                            <ArrowRight class="w-5 h-5 group-hover:translate-x-1.5 transition-transform duration-200" />
                        </Link>
                        
                        <a 
                            href="#simulador"
                            class="w-full sm:w-auto inline-flex items-center justify-center gap-2.5 px-6 py-4 text-base font-semibold text-slate-200 hover:text-white rounded-2xl border border-slate-700/80 hover:border-emerald-500/50 hover:bg-slate-800/60 hover:-translate-y-0.5 active:translate-y-0 transition-all duration-200 group"
                        >
                            <Play class="w-4 h-4 text-emerald-400 fill-emerald-400 group-hover:scale-125 transition-transform duration-200" />
                            Simular Minha Folga
                        </a>
                    </div>

                    <!-- Trust indicators with staggered delay -->
                    <div 
                        v-reveal="{ delay: 1150, direction: 'up' }"
                        class="flex flex-wrap items-center justify-center lg:justify-start gap-6 text-xs sm:text-sm text-slate-400"
                    >
                        <div class="flex items-center gap-2 hover:text-slate-200 transition-colors">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                            <span>100% Gratuito para começar</span>
                        </div>
                        <div class="flex items-center gap-2 hover:text-slate-200 transition-colors">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                            <span>Importe extratos de qualquer banco</span>
                        </div>
                        <div class="flex items-center gap-2 hover:text-slate-200 transition-colors">
                            <CheckCircle2 class="w-4 h-4 text-emerald-400" />
                            <span>Funciona no Celular (PWA)</span>
                        </div>
                    </div>
                </div>

                <!-- Right Column: Interactive 3D Mockup / Dashboard Preview Card with Parallax Tilt -->
                <div 
                    v-reveal="{ delay: 550, direction: 'scale' }"
                    class="lg:col-span-5 relative perspective-[1200px]"
                >
                    <!-- Floating Indicator Badge: Micro-Pix Leak Warning -->
                    <div class="absolute -top-5 -left-4 sm:-left-6 z-20 animate-float-slow pointer-events-none">
                        <div class="glass-card rounded-2xl px-3.5 py-2 border border-amber-500/40 shadow-xl flex items-center gap-2 text-xs text-amber-300 backdrop-blur-xl">
                            <Flame class="w-4 h-4 text-amber-400 animate-pulse" />
                            <span class="font-bold">Radar Ativo:</span> 3 micro-saídas hoje
                        </div>
                    </div>

                    <!-- Floating Indicator Badge: 100% Blindado -->
                    <div class="absolute -bottom-5 -right-3 sm:-right-5 z-20 animate-float-reverse pointer-events-none">
                        <div class="glass-card rounded-2xl px-3.5 py-2 border border-emerald-500/40 shadow-xl flex items-center gap-2 text-xs text-emerald-300 backdrop-blur-xl">
                            <ShieldCheck class="w-4 h-4 text-emerald-400" />
                            <span class="font-bold">Contas Fixas Blindadas</span>
                        </div>
                    </div>

                    <!-- Glow behind card -->
                    <div class="absolute -inset-2 bg-gradient-to-tr from-emerald-500/30 via-teal-500/20 to-emerald-400/25 rounded-3xl blur-2xl opacity-80 animate-breathe-glow pointer-events-none"></div>

                    <!-- The 3D Tilt Card Component -->
                    <div 
                        ref="cardRef"
                        @mousemove="(e) => onMouseMove(e, cardRef)"
                        @mouseleave="onMouseLeave"
                        :style="tiltStyle"
                        class="relative glass-panel rounded-3xl p-6 sm:p-7 border border-slate-700/60 shadow-2xl overflow-hidden cursor-pointer group will-change-transform select-none"
                    >
                        <!-- Dynamic Specular Glare Layer -->
                        <div 
                            :style="glareStyle" 
                            class="absolute inset-0 pointer-events-none transition-opacity duration-300 rounded-3xl z-30"
                        ></div>
                        
                        <!-- Header of card -->
                        <div class="flex items-center justify-between pb-5 border-b border-slate-800">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/30 flex items-center justify-center group-hover:rotate-6 transition-transform">
                                    <Wallet class="w-5 h-5 text-emerald-400" />
                                </div>
                                <div>
                                    <h3 class="text-sm font-bold text-white">Painel Hoje</h3>
                                    <p class="text-xs text-slate-400">Previsão em tempo real</p>
                                </div>
                            </div>
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-semibold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                                <span class="w-2 h-2 rounded-full bg-emerald-400 animate-ping"></span>
                                Folga Garantida
                            </span>
                        </div>

                        <!-- Central Highlight: Teto Diário Seguro -->
                        <div class="my-6 p-5 rounded-2xl bg-gradient-to-br from-emerald-950/60 to-slate-900/90 border border-emerald-500/30 relative overflow-hidden group-hover:border-emerald-400/50 transition-colors">
                            <div class="flex items-center justify-between text-xs text-slate-300 font-medium mb-1">
                                <span class="uppercase tracking-wider flex items-center gap-1.5 text-emerald-400 font-bold">
                                    <Zap class="w-3.5 h-3.5 animate-pulse" />
                                    Teto Diário Seguro
                                </span>
                                <span class="text-slate-400 flex items-center gap-1">
                                    <Calendar class="w-3.5 h-3.5" />
                                    Até o dia 28 (14 dias)
                                </span>
                            </div>

                            <!-- Animated Count-Up Value -->
                            <div class="flex items-baseline gap-2 mt-2">
                                <span class="text-3xl sm:text-4xl font-extrabold text-white tracking-tight font-display">
                                    {{ formatCurrency(animatedDailyCeiling) }}
                                </span>
                                <span class="text-slate-400 text-sm font-medium">/ dia</span>
                            </div>

                            <p class="text-xs text-emerald-300/90 mt-2 leading-relaxed">
                                Você pode gastar isso hoje com total paz de espírito. As contas fixas já estão blindadas.
                            </p>

                            <!-- Animated Progress Bar -->
                            <div class="mt-4 pt-3 border-t border-emerald-500/20">
                                <div class="flex justify-between text-[11px] text-slate-400 mb-1.5">
                                    <span>Gasto de hoje: R$ 12,00</span>
                                    <span class="text-emerald-400 font-medium">R$ 30,50 livres</span>
                                </div>
                                <div class="w-full h-2.5 rounded-full bg-slate-800 overflow-hidden">
                                    <div 
                                        class="h-full bg-gradient-to-r from-emerald-500 via-teal-400 to-emerald-300 rounded-full transition-all duration-1000 ease-out shadow-sm shadow-emerald-400"
                                        :style="{ width: progressBarWidth }"
                                    ></div>
                                </div>
                            </div>
                        </div>

                        <!-- Secondary Insights Grid -->
                        <div class="grid grid-cols-2 gap-3 mb-4">
                            <!-- Saldo em Conta -->
                            <div class="p-3.5 rounded-xl bg-slate-900/70 border border-slate-800 group-hover:border-slate-700 transition-colors">
                                <span class="text-xs text-slate-400 block mb-1">Saldo em Contas</span>
                                <span class="text-base font-bold text-white">R$ 1.845,00</span>
                                <span class="text-[10px] text-emerald-400 block mt-0.5 font-medium">BB + Nubank</span>
                            </div>
                            
                            <!-- Contas a Vencer -->
                            <div class="p-3.5 rounded-xl bg-slate-900/70 border border-slate-800 group-hover:border-slate-700 transition-colors">
                                <span class="text-xs text-slate-400 block mb-1">Fixas Reservadas</span>
                                <span class="text-base font-bold text-rose-400">R$ 1.250,00</span>
                                <span class="text-[10px] text-slate-400 block mt-0.5">Aluguel, Luz, Wi-Fi</span>
                            </div>
                        </div>

                        <!-- Live Leak Radar Notification -->
                        <div class="flex items-center gap-3 p-3 rounded-xl bg-amber-500/10 border border-amber-500/25 text-amber-300 text-xs transition-transform group-hover:scale-[1.01]">
                            <AlertTriangle class="w-4 h-4 text-amber-400 shrink-0" />
                            <span>
                                <strong>Raio-X:</strong> 3 micro-pix de delivery & apostas somaram R$ 43,00 esta semana.
                            </span>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </section>
</template>
