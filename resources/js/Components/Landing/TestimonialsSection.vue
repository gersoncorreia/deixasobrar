<script setup>
import { Star, Quote } from 'lucide-vue-next';

const testimonials = [
    {
        name: 'Carlos Mendes',
        role: 'Autônomo & Designer',
        quote: 'Eu nunca conseguia guardar nada porque não sabia quanto podia gastar na semana. O Teto Diário do DeixaSobrar mudou meu jogo: se hoje posso gastar R$ 45, eu respeito e durmo tranquilo.',
        stars: 5,
        saved: 'R$ 520 economizados/mês',
        delay: 'delay-100',
    },
    {
        name: 'Juliana e Renato',
        role: 'Casal (Plano Família)',
        quote: 'A gente brigava todo fim de mês por causa da fatura do cartão. Subimos os extratos do Nubank e do BB, e vimos quase R$ 400 em pequenas saidinhas e apostas que nem lembrávamos.',
        stars: 5,
        saved: 'Fim das dívidas de carnê',
        delay: 'delay-200',
    },
    {
        name: 'Patrícia Duarte',
        role: 'Servidora Pública',
        quote: 'O que mais amei é que não tem economês. O visual no celular é leve, rápido e parece um app instalado da Apple Store. Não fico mais perdida com planilhas.',
        stars: 5,
        saved: 'Reserva de emergência montada',
        delay: 'delay-300',
    },
];
</script>

<template>
    <section class="py-24 bg-slate-950 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    Histórias Reais
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-3 mb-3"
                >
                    Quem usa, não volta para as planilhas
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-sm sm:text-base"
                >
                    Pessoas comuns que recuperaram o controle e agora veem o dinheiro sobrar.
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <div 
                    v-for="(t, i) in testimonials" 
                    :key="i"
                    v-reveal="{ delay: 200 + i * 120, direction: 'up' }"
                    class="glass-panel rounded-3xl p-8 border border-slate-800 flex flex-col justify-between hover:border-emerald-500/40 hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-950/30 transition-all duration-300 group"
                >
                    <div>
                        <!-- Stars with twinkling animation -->
                        <div class="flex items-center gap-1.5 mb-5 text-amber-400">
                            <Star 
                                v-for="s in t.stars" 
                                :key="s" 
                                class="w-4 h-4 fill-amber-400 animate-twinkle" 
                                :style="{ animationDelay: `${s * 200}ms` }"
                            />
                        </div>

                        <!-- Quote -->
                        <p class="text-sm text-slate-300 italic leading-relaxed mb-6 group-hover:text-white transition-colors">
                            "{{ t.quote }}"
                        </p>
                    </div>

                    <div class="pt-5 border-t border-slate-800/80 flex items-center justify-between">
                        <div>
                            <h4 class="text-sm font-bold text-white group-hover:text-emerald-300 transition-colors">{{ t.name }}</h4>
                            <p class="text-xs text-slate-400">{{ t.role }}</p>
                        </div>
                        <span class="text-[11px] font-semibold text-emerald-400 bg-emerald-500/10 px-3 py-1 rounded-xl border border-emerald-500/20">
                            {{ t.saved }}
                        </span>
                    </div>
                </div>
            </div>

        </div>
    </section>
</template>
