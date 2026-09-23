<script setup>
import { 
    XCircle, 
    CheckCircle2, 
    TrendingDown, 
    TrendingUp, 
    Sparkles 
} from 'lucide-vue-next';

const traditionalPain = [
    { title: 'Economês e jargões chatos', desc: 'Termos contábeis como "passivo circulante" e gráficos confusos que mais complicam do que ajudam.' },
    { title: 'Conexões bancárias que caem', desc: 'Pedem senha do seu banco e vivem quebrando ou cobrando mensalidades abusivas.' },
    { title: 'Sensação constante de culpa', desc: 'Só mostram relatórios do passado dizendo onde você já errou, quando o dinheiro já acabou.' },
    { title: 'Planilhas manuais infinitas', desc: 'Passar o fim de semana digitando cada cafezinho numa tabela do Excel que ninguém abre.' },
];

const deixaSobrarSolution = [
    { title: 'Teto Diário sem complicação', desc: 'Saiba exatamente quantos reais você pode gastar no dia de hoje com tranquilidade.' },
    { title: 'Importação universal e segura', desc: 'Suba o extrato em CSV ou OFX de qualquer banco em 3 segundos sem dar senha a ninguém.' },
    { title: 'Previsibilidade antifrágil', desc: 'Foco no presente e no futuro: suas contas fixas ficam blindadas antes de você gastar.' },
    { title: 'Adeus Carnês e Margem Livre', desc: 'Acompanhe a liquidação de parcelas e sinta o alívio da sua renda mensal sendo destravada.' },
];
</script>

<template>
    <section id="como-funciona" class="py-24 bg-slate-900/50 border-y border-slate-800/80 relative overflow-hidden">
        <!-- Ambient background soft gradient -->
        <div class="absolute left-0 top-1/2 -translate-y-1/2 w-72 h-72 bg-rose-500/5 rounded-full blur-[100px] pointer-events-none"></div>
        <div class="absolute right-0 top-1/2 -translate-y-1/2 w-80 h-80 bg-emerald-500/10 rounded-full blur-[120px] pointer-events-none"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <!-- Section Header with blur-fade -->
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    O Fim da Frustração Financeira
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-4 mb-4"
                >
                    Por que o DeixaSobrar é diferente de tudo o que você já testou?
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-base sm:text-lg"
                >
                    A maioria dos aplicativos foi feita para contadores, não para quem quer apenas respirar aliviado no fim do mês.
                </p>
            </div>

            <!-- Comparison Cards Grid -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 lg:gap-10">
                
                <!-- Left: Traditional Apps (Pain) -->
                <div 
                    v-reveal="{ delay: 180, direction: 'left' }"
                    class="rounded-3xl p-6 sm:p-8 bg-slate-950/80 border border-rose-500/20 hover:border-rose-500/40 transition-all duration-300 relative overflow-hidden group hover:-translate-y-1 shadow-xl shadow-rose-950/10"
                >
                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-slate-800">
                        <div class="w-10 h-10 rounded-xl bg-rose-500/10 border border-rose-500/30 flex items-center justify-center group-hover:scale-110 transition-transform">
                            <TrendingDown class="w-5 h-5 text-rose-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">Aplicativos Tradicionais</h3>
                            <p class="text-xs text-rose-400 font-medium">Complicados, burocráticos e estressantes</p>
                        </div>
                    </div>

                    <ul class="space-y-5">
                        <li 
                            v-for="(item, i) in traditionalPain" 
                            :key="i" 
                            class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-rose-500/5 transition-colors"
                        >
                            <XCircle class="w-5 h-5 text-rose-500 shrink-0 mt-0.5 group-hover:rotate-45 transition-transform" />
                            <div>
                                <h4 class="text-sm font-semibold text-slate-200">{{ item.title }}</h4>
                                <p class="text-xs sm:text-sm text-slate-400 mt-0.5 leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

                <!-- Right: DeixaSobrar (Solution) -->
                <div 
                    v-reveal="{ delay: 280, direction: 'right' }"
                    class="rounded-3xl p-6 sm:p-8 bg-gradient-to-b from-emerald-950/40 via-slate-950/80 to-slate-950/90 border border-emerald-500/30 hover:border-emerald-400/60 transition-all duration-300 relative overflow-hidden shadow-2xl shadow-emerald-950/30 group hover:-translate-y-1"
                >
                    <div class="absolute -right-8 -top-8 w-36 h-36 bg-emerald-500/15 rounded-full blur-2xl group-hover:scale-125 transition-transform duration-500"></div>

                    <div class="flex items-center gap-3 mb-6 pb-4 border-b border-emerald-500/20">
                        <div class="w-10 h-10 rounded-xl bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center group-hover:scale-110 group-hover:rotate-6 transition-transform">
                            <TrendingUp class="w-5 h-5 text-emerald-400" />
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-white">DeixaSobrar</h3>
                            <p class="text-xs text-emerald-400 font-medium">Simples, acolhedor e focado no seu teto diário</p>
                        </div>
                    </div>

                    <ul class="space-y-5">
                        <li 
                            v-for="(item, i) in deixaSobrarSolution" 
                            :key="i" 
                            class="flex items-start gap-3.5 p-2 rounded-xl hover:bg-emerald-500/5 transition-colors"
                        >
                            <CheckCircle2 class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5 group-hover:scale-110 transition-transform" />
                            <div>
                                <h4 class="text-sm font-semibold text-white">{{ item.title }}</h4>
                                <p class="text-xs sm:text-sm text-slate-300 mt-0.5 leading-relaxed">{{ item.desc }}</p>
                            </div>
                        </li>
                    </ul>
                </div>

            </div>

        </div>
    </section>
</template>
