<script setup>
import { 
    Zap, 
    Radar, 
    FileUp, 
    TrendingUp, 
    Building2,
    CheckCircle
} from 'lucide-vue-next';

const features = [
    {
        icon: Zap,
        badge: 'Metodologia Central',
        title: 'Teto Diário Seguro',
        description: 'Nunca mais passe aperto na semana anterior ao pagamento. Suas contas fixas ficam 100% blindadas e você descobre exatamente quantos reais tem livres para o dia a dia.',
        delay: 'delay-100',
    },
    {
        icon: Radar,
        badge: 'Fim dos Desperdícios',
        title: 'Radar de Micro-Pix & Apostas',
        description: 'Pequenos saques e Pix de R$ 10 ou R$ 20 parecem inofensivos, mas somam R$ 400 no mês. O DeixaSobrar alerta imediatamente quando os vazamentos começam a crescer.',
        delay: 'delay-200',
    },
    {
        icon: FileUp,
        badge: 'Universal & Seguro',
        title: 'Importador Universal Multi-Bancos',
        description: 'Compatível com Banco do Brasil, Nubank, Itaú, Bradesco, Santander, Inter, Caixa, OFX padrão FEBRABAN e qualquer arquivo CSV. Sem expor suas senhas bancárias.',
        delay: 'delay-300',
    },
    {
        icon: TrendingUp,
        badge: 'Liberdade Financeira',
        title: 'Gerenciador "Adeus Carnês"',
        description: 'Acompanhe a contagem regressiva para a liquidação de compras parceladas e carnês. A cada carnê pago, o sistema calcula na hora o aumento da sua renda livre.',
        delay: 'delay-400',
    },
];

const supportedBanks = [
    'Banco do Brasil', 'Nubank', 'Itaú', 'Bradesco', 'Santander', 'Banco Inter', 'Caixa Econômica', 'C6 Bank', 'Sicoob / Sicredi', 'Extratos OFX & CSV'
];
</script>

<template>
    <section id="diferenciais" class="py-24 bg-slate-950 relative overflow-hidden">
        <!-- Ambient background lighting -->
        <div class="absolute right-1/4 top-1/3 w-80 h-80 bg-teal-500/10 rounded-full blur-[130px] pointer-events-none animate-float-reverse"></div>

        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center max-w-3xl mx-auto mb-16">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    Recursos Desenhados para a Vida Real
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-3 mb-4"
                >
                    Tudo o que você precisa para fazer sobrar dinheiro no final do mês
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-base sm:text-lg"
                >
                    Ferramentas práticas e acolhedoras para quem não tem tempo a perder com telas complicadas.
                </p>
            </div>

            <!-- Features 4-grid with staggered reveal -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-8 mb-16">
                <div 
                    v-for="(item, i) in features" 
                    :key="i"
                    v-reveal="{ delay: 150 + i * 100, direction: 'up' }"
                    class="glass-panel rounded-3xl p-8 border border-slate-800 hover:border-emerald-500/40 transition-all duration-300 group hover:-translate-y-2 hover:shadow-2xl hover:shadow-emerald-950/30 relative overflow-hidden"
                >
                    <!-- Corner ambient glow -->
                    <div class="absolute -right-10 -top-10 w-28 h-28 bg-emerald-500/10 rounded-full blur-2xl group-hover:bg-emerald-500/25 group-hover:scale-150 transition-all duration-500"></div>

                    <div class="flex items-center justify-between mb-5">
                        <div class="w-12 h-12 rounded-2xl bg-slate-900 border border-slate-700/80 flex items-center justify-center group-hover:border-emerald-500/60 group-hover:bg-emerald-500/10 transition-all duration-300">
                            <component :is="item.icon" class="w-6 h-6 text-emerald-400 group-hover:scale-110 group-hover:rotate-6 transition-transform duration-300" />
                        </div>
                        <span class="text-[11px] font-bold uppercase tracking-wider text-slate-400 bg-slate-900/80 px-2.5 py-1 rounded-full border border-slate-800 group-hover:border-emerald-500/30 transition-colors">
                            {{ item.badge }}
                        </span>
                    </div>

                    <h3 class="text-xl font-bold text-white mb-2.5 group-hover:text-emerald-300 transition-colors">
                        {{ item.title }}
                    </h3>

                    <p class="text-sm text-slate-400 leading-relaxed group-hover:text-slate-300 transition-colors">
                        {{ item.description }}
                    </p>
                </div>
            </div>

            <!-- Bank Support Showcase Banner with animated badge borders -->
            <div 
                id="extratos" 
                v-reveal="{ delay: 200, direction: 'scale' }"
                class="glass-card rounded-3xl p-8 sm:p-10 border border-slate-800/80 text-center hover:border-emerald-500/30 transition-colors shadow-2xl"
            >
                <div class="inline-flex items-center gap-2 text-xs font-semibold text-teal-400 mb-3 px-3 py-1 rounded-full bg-teal-500/10 border border-teal-500/20">
                    <Building2 class="w-4 h-4 animate-pulse" />
                    <span>Conexão Segura & Sem Senha</span>
                </div>
                <h3 class="text-xl sm:text-3xl font-bold text-white mb-2">
                    Importe o extrato do seu banco em poucos segundos
                </h3>
                <p class="text-xs sm:text-sm text-slate-400 max-w-xl mx-auto mb-8">
                    Você baixa o extrato oficial no app do seu banco (CSV ou OFX) e arrasta para cá. Nenhum dado de acesso bancário ou senha é solicitado.
                </p>

                <!-- Bank tags with subtle sequential hover effects -->
                <div class="flex flex-wrap items-center justify-center gap-3 max-w-3xl mx-auto">
                    <span 
                        v-for="bank in supportedBanks" 
                        :key="bank"
                        class="px-3.5 py-2 rounded-xl bg-slate-900/90 text-xs font-medium text-slate-300 border border-slate-800 hover:border-emerald-400/60 hover:text-white hover:scale-105 transition-all duration-200 flex items-center gap-2 shadow-sm"
                    >
                        <CheckCircle class="w-3.5 h-3.5 text-emerald-400" />
                        {{ bank }}
                    </span>
                </div>
            </div>

        </div>
    </section>
</template>
