<script setup>
import { ref } from 'vue';
import { Link } from '@inertiajs/vue3';
import { Check, ArrowRight, Zap, Sparkles } from 'lucide-vue-next';

const isAnnual = ref(true);

const plans = [
    {
        name: 'Gratuito',
        tagline: 'Ideal para conhecer e dar os primeiros passos.',
        monthlyPrice: 'R$ 0',
        annualPrice: 'R$ 0',
        period: 'para sempre',
        isPopular: false,
        ctaText: 'Começar Grátis',
        ctaHref: '/register?plan=free',
        delay: 'delay-100',
        features: [
            'Registro de contas manuais',
            'Cálculo do Teto Diário Seguro',
            'Até 3 importações de extrato/mês',
            'Categorias financeiras essenciais',
            'Acesso web e celular (PWA)',
        ],
    },
    {
        name: 'Plano Pro',
        tagline: 'O mais completo para fazer o salário sobrar de verdade.',
        monthlyPrice: 'R$ 19,90',
        annualPrice: 'R$ 14,99', // equivalent monthly when billed annually (R$ 179,90/ano)
        annualTotal: 'R$ 179,90 cobrados anualmente',
        period: '/mês',
        isPopular: true,
        ctaText: 'Assinar Plano Pro',
        ctaHref: '/register?plan=pro',
        delay: 'delay-200',
        features: [
            'Tudo do Plano Gratuito',
            'Importações de extratos ILIMITADAS',
            'Suporte a todos os bancos (BB, Nubank, Itaú, OFX, etc.)',
            'Radar de Vazamentos (apostas, micro-pix e tarifas)',
            'Gerenciador "Adeus Carnês" com liberação de margem',
            'Alertas diários de limites e segurança',
            'Pagamento flexível via PIX ou Cartão',
        ],
    },
    {
        name: 'Plano Família',
        tagline: 'Para casais ou famílias unirem forças no orçamento.',
        monthlyPrice: 'R$ 29,90',
        annualPrice: 'R$ 22,50',
        annualTotal: 'R$ 269,90 cobrados anualmente',
        period: '/mês',
        isPopular: false,
        ctaText: 'Escolher Família',
        ctaHref: '/register?plan=family',
        delay: 'delay-300',
        features: [
            'Tudo do Plano Pro',
            'Até 4 contas de acesso independentes',
            'Despesas da casa compartilhadas',
            'Teto diário individual e coletivo',
            'Histórico compartilhado de quitações',
            'Suporte prioritário via WhatsApp',
        ],
    },
];
</script>

<template>
    <section id="planos" class="py-24 relative bg-slate-900/40 border-t border-slate-800 overflow-hidden">
        <!-- Ambient lighting -->
        <div class="absolute left-1/2 -top-24 -translate-x-1/2 w-96 h-96 bg-emerald-500/10 rounded-full blur-[140px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center max-w-3xl mx-auto mb-14">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    Preços Transparentes
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-3 mb-4"
                >
                    Menos que um lanche para ter paz financeira o mês inteiro
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-base sm:text-lg mb-8"
                >
                    Cancele a qualquer momento com apenas 1 clique. Sem fidelidade nem letras miúdas.
                </p>

                <!-- Billing Cycle Toggle with Elastic Background Slider -->
                <div 
                    v-reveal="{ delay: 350, direction: 'up' }"
                    class="inline-flex items-center p-1.5 rounded-2xl bg-slate-950 border border-slate-800 relative shadow-inner"
                >
                    <button 
                        @click="isAnnual = false"
                        :class="[
                            'relative z-10 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-300',
                            !isAnnual ? 'bg-slate-800 text-white shadow-lg' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        Faturamento Mensal
                    </button>
                    <button 
                        @click="isAnnual = true"
                        :class="[
                            'relative z-10 px-5 py-2.5 rounded-xl text-xs sm:text-sm font-semibold transition-all duration-300 flex items-center gap-2',
                            isAnnual ? 'bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 font-bold shadow-lg shadow-emerald-500/30' : 'text-slate-400 hover:text-slate-200'
                        ]"
                    >
                        <span>Faturamento Anual</span>
                        <span class="px-2 py-0.5 rounded-full text-[10px] font-black uppercase tracking-wider bg-slate-950 text-emerald-300 shadow">
                            Economize 25%
                        </span>
                    </button>
                </div>
            </div>

            <!-- Pricing Cards Grid with Staggered Delays -->
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-8 items-stretch">
                <div 
                    v-for="(plan, idx) in plans" 
                    :key="plan.name"
                    v-reveal="{ delay: 200 + idx * 100, direction: 'up' }"
                    :class="[
                        'rounded-3xl p-8 flex flex-col justify-between transition-all duration-300 relative',
                        plan.isPopular 
                            ? 'glass-card-glow border-2 border-emerald-400 animate-pulse-border lg:-translate-y-3 shadow-2xl shadow-emerald-950/50 hover:scale-[1.02]' 
                            : 'glass-panel border border-slate-800 hover:border-slate-700 hover:-translate-y-1'
                    ]"
                >
                    <!-- Popular badge with pulse icon -->
                    <div 
                        v-if="plan.isPopular" 
                        class="absolute -top-3.5 left-1/2 -translate-x-1/2 inline-flex items-center gap-1.5 px-4 py-1 rounded-full bg-gradient-to-r from-emerald-400 to-teal-300 text-slate-950 text-xs font-black uppercase tracking-wider shadow-lg shadow-emerald-500/30"
                    >
                        <Zap class="w-3.5 h-3.5 animate-bounce" />
                        Mais Escolhido
                    </div>

                    <div>
                        <!-- Header -->
                        <div class="mb-6">
                            <h3 class="text-xl font-bold text-white">{{ plan.name }}</h3>
                            <p class="text-xs text-slate-400 mt-1">{{ plan.tagline }}</p>
                        </div>

                        <!-- Price with transition -->
                        <div class="mb-6 pb-6 border-b border-slate-800">
                            <div class="flex items-baseline gap-1">
                                <transition mode="out-in" enter-active-class="transition-all duration-200" enter-from-class="opacity-0 -translate-y-2" enter-to-class="opacity-100 translate-y-0" leave-active-class="transition-all duration-150" leave-from-class="opacity-100 translate-y-0" leave-to-class="opacity-0 translate-y-2">
                                    <span :key="isAnnual ? plan.annualPrice : plan.monthlyPrice" class="text-4xl sm:text-5xl font-extrabold text-white tracking-tight font-display">
                                        {{ isAnnual ? plan.annualPrice : plan.monthlyPrice }}
                                    </span>
                                </transition>
                                <span class="text-xs text-slate-400 font-medium">
                                    {{ plan.period }}
                                </span>
                            </div>
                            <span 
                                v-if="isAnnual && plan.annualTotal" 
                                class="text-[11px] text-emerald-400/90 block mt-1.5 font-medium"
                            >
                                {{ plan.annualTotal }}
                            </span>
                        </div>

                        <!-- Feature list -->
                        <ul class="space-y-3.5 mb-8">
                            <li 
                                v-for="(feat, idx) in plan.features" 
                                :key="idx"
                                class="flex items-start gap-3 text-xs sm:text-sm text-slate-300"
                            >
                                <div class="w-4 h-4 rounded-full bg-emerald-500/20 flex items-center justify-center shrink-0 mt-0.5">
                                    <Check class="w-3 h-3 text-emerald-400" />
                                </div>
                                <span>{{ feat }}</span>
                            </li>
                        </ul>
                    </div>

                    <!-- CTA Button -->
                    <Link 
                        :href="plan.ctaHref"
                        :class="[
                            'w-full py-4 rounded-xl font-bold text-sm text-center transition-all duration-200 flex items-center justify-center gap-2 group',
                            plan.isPopular 
                                ? 'btn-shimmer bg-gradient-to-r from-emerald-400 to-teal-300 hover:from-emerald-300 hover:to-teal-200 text-slate-950 shadow-xl shadow-emerald-500/30 hover:scale-[1.02] active:scale-95' 
                                : 'bg-slate-800 hover:bg-slate-700 text-white hover:scale-[1.02] active:scale-95'
                        ]"
                    >
                        <span>{{ plan.ctaText }}</span>
                        <ArrowRight class="w-4 h-4 group-hover:translate-x-1.5 transition-transform duration-200" />
                    </Link>
                </div>
            </div>

            <!-- Money-back guarantee badge -->
            <div data-reveal class="delay-400 text-center mt-14">
                <span class="inline-flex items-center gap-2 text-xs sm:text-sm text-slate-400 p-2 rounded-xl bg-slate-900/50 border border-slate-800">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 animate-ping"></span>
                    Garantia incondicional de 7 dias. Não gostou? Devolvemos 100% do seu dinheiro.
                </span>
            </div>

        </div>
    </section>
</template>
