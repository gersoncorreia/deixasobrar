<script setup>
import { 
    X, 
    Compass, 
    Target, 
    ShieldCheck, 
    Flame, 
    Camera, 
    FileUp, 
    PieChart, 
    Sparkles, 
    CheckCircle2, 
    ArrowRight 
} from 'lucide-vue-next';

defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['close']);

const features = [
    {
        title: 'Teto Diário Seguro',
        subtitle: 'Seu limite diário livre de culpa',
        icon: Target,
        color: 'emerald',
        badge: 'Cálculo Reverso',
        summary: 'Quanto você pode gastar hoje sem faltar dinheiro para o aluguel do dia 10.',
        description: 'Diferente de planilhas tradicionais que só mostram o passado, o DeixaSobrar calcula seu saldo real, subtrai todas as contas que ainda vão vencer até o seu próximo salário e divide o que sobrou pelos dias restantes. Se você gastar menos que o Teto, o dinheiro sobra automaticamente!'
    },
    {
        title: 'Contas Blindadas',
        subtitle: 'Dinheiro carimbado antes do mês começar',
        icon: ShieldCheck,
        color: 'blue',
        badge: 'Proteção Essencial',
        summary: 'Aluguel, luz, condomínio e parcelas já ficam mentalmente separados da sua conta.',
        description: 'Você cadastra o valor médio e o dia de vencimento de cada conta fixa. Conforme o extrato bancário identifica o pagamento, a blindagem é baixada e seu Teto Diário recalcula em tempo real.'
    },
    {
        title: 'Raio-X de Vazamentos & IA',
        subtitle: 'O fim dos pequenos drenos invisíveis',
        icon: Flame,
        color: 'amber',
        badge: 'Inteligência Artificial',
        summary: 'Pequenos gastos de R$ 15 a R$ 35 que parecem inofensivos, mas no fim do mês somam um salário.',
        description: 'A IA do DeixaSobrar monitora seus extratos e cupons procurando tarifas bancárias abusivas, apostas/bets online, assinaturas esquecidas e delivery impulsivo repetitivo. Com um clique você descobre exatamente quanto acumularia em 1 ano se cortasse esses vazamentos.'
    },
    {
        title: 'Scanner OCR Inteligente (Sem Lixo no Disco)',
        subtitle: 'Foto do cupom direto para dados estruturados',
        icon: Camera,
        color: 'teal',
        badge: 'Visão Computacional & Privacidade',
        summary: 'Aponte a câmera pro cupom do mercado e a IA extrai itens, valores e forma de pagamento.',
        description: 'Nossa IA processa a foto instantaneamente e apaga o arquivo de imagem do servidor logo após a leitura, economizando espaço em disco e garantindo sua privacidade total. Os dados dos produtos e da compra ficam salvos digitalmente e são reconciliados com seu extrato bancário.'
    },
    {
        title: 'Micro-Análise da Cesta de Compras',
        subtitle: 'Raio-X de produtos do supermercado',
        icon: PieChart,
        color: 'emerald',
        badge: 'Inteligência de Mercado',
        summary: 'Descubra quais produtos levam mais do seu dinheiro e diferencie o essencial dos supérfluos.',
        description: 'Ao escanear notas fiscais, o sistema agrupa os produtos em Alimentação Básica, Limpeza/Higiene, Supérfluos e Bebidas. Você descobre exatamente onde estão os gastos ocultos da feira e do mercado.'
    },
    {
        title: 'Importador Universal de Extratos',
        subtitle: 'Suba o arquivo do seu banco em segundos',
        icon: FileUp,
        color: 'purple',
        badge: 'OFX & CSV',
        summary: 'Adeus anotações manuais: o extrato do seu banco alimenta todo o sistema sozinho.',
        description: 'Compatível com Nubank, Banco do Brasil, Itaú, Bradesco, Santander, Caixa, Inter e qualquer outro banco brasileiro. O sistema normaliza caracteres, limpa duplicidades e aprende os nomes das suas lojas favoritas.'
    },
    {
        title: 'Central de Análises 360º',
        subtitle: 'Seu diagnóstico financeiro em português claro',
        icon: PieChart,
        color: 'cyan',
        badge: 'Score de Saúde',
        summary: 'Gráficos descomplicados e um Score de 0 a 100 que mostra se sua vida financeira está respirando.',
        description: 'Transformamos extratos complicados em conselhos práticos e humanos. Navegue pelas abas para ver o termômetro de saúde, histórico semestral, grupos de gastos, vilões do orçamento e sua cesta de compras escaneada.'
    }
];
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-6 overflow-y-auto">
        <div class="glass-panel w-full max-w-4xl rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl relative overflow-hidden flex flex-col my-auto max-h-[90vh]">
            
            <!-- Header -->
            <div class="p-6 border-b border-slate-800 flex items-center justify-between bg-slate-950/60 sticky top-0 z-10 backdrop-blur-md">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-2xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <Compass class="w-5 h-5" />
                    </div>
                    <div>
                        <h3 class="text-base sm:text-lg font-extrabold text-white flex items-center gap-2">
                            <span>Como o DeixaSobrar Cuida de Você</span>
                            <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">Guia Didático</span>
                        </h3>
                        <p class="text-xs text-slate-400">Entenda cada ferramenta criada para fazer seu dinheiro sobrar sem complicação</p>
                    </div>
                </div>

                <button 
                    @click="emit('close')"
                    class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    title="Fechar guia"
                >
                    <X class="w-5 h-5" />
                </button>
            </div>

            <!-- Content Area (Cards Grid) -->
            <div class="p-6 sm:p-8 overflow-y-auto space-y-6">
                <div class="p-4 rounded-2xl bg-gradient-to-r from-emerald-950/40 via-slate-900 to-slate-900/60 border border-emerald-500/30 text-xs text-slate-300 flex items-start gap-3">
                    <Sparkles class="w-5 h-5 text-emerald-400 shrink-0 mt-0.5" />
                    <p class="leading-relaxed">
                        <strong class="text-white">Filosofia DeixaSobrar:</strong> Você não precisa de planilhas complexas com 50 colunas. Apenas mantenha suas contas blindadas cadastradas e siga o seu <strong>Teto Diário</strong>. O resto o sistema calcula para você!
                    </p>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-6">
                    <div 
                        v-for="feat in features" 
                        :key="feat.title"
                        class="p-5 rounded-2xl bg-slate-950/70 border border-slate-800 hover:border-slate-700 transition-all flex flex-col justify-between group"
                    >
                        <div>
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2.5">
                                    <div class="w-8 h-8 rounded-xl bg-slate-900 border border-slate-700 flex items-center justify-center text-emerald-400 group-hover:scale-110 transition-transform">
                                        <component :is="feat.icon" class="w-4 h-4" />
                                    </div>
                                    <h4 class="text-sm font-extrabold text-white">{{ feat.title }}</h4>
                                </div>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-slate-900 text-slate-400 border border-slate-800">
                                    {{ feat.badge }}
                                </span>
                            </div>

                            <p class="text-xs font-bold text-emerald-400 mb-1.5">
                                {{ feat.subtitle }}
                            </p>

                            <p class="text-xs text-slate-300 leading-relaxed mb-3">
                                {{ feat.summary }}
                            </p>
                        </div>

                        <div class="pt-3 border-t border-slate-800/80 text-[11px] text-slate-400 leading-relaxed">
                            {{ feat.description }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer Action -->
            <div class="p-4 bg-slate-950 border-t border-slate-800 flex items-center justify-between px-6">
                <span class="text-xs text-slate-400">💡 Dica: Acesse este guia sempre que tiver dúvidas na Central de Análises.</span>
                <button 
                    @click="emit('close')"
                    class="px-5 py-2 rounded-xl bg-emerald-500 hover:bg-emerald-400 text-slate-950 font-extrabold text-xs transition-colors cursor-pointer"
                >
                    Entendi, Vamos Lá!
                </button>
            </div>

        </div>
    </div>
</template>
