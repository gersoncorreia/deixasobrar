<script setup>
import { ref } from 'vue';
import { ChevronDown } from 'lucide-vue-next';

const faqs = [
    {
        question: 'Preciso fornecer a senha ou conectar meu banco diretamente?',
        answer: 'Absolutamente NÃO! Nós prezamos 100% pela sua segurança e privacidade. Você nunca insere senhas nem códigos bancários. Você simplesmente baixa o extrato oficial no aplicativo do seu banco (em formato CSV ou OFX) e arrasta para cá em 2 segundos.',
    },
    {
        question: 'Funciona com quais bancos?',
        answer: 'Com QUALQUER banco! O DeixaSobrar possui um motor universal multi-bancos otimizado para Banco do Brasil, Nubank, Itaú, Bradesco, Santander, Inter, Caixa, C6 Bank, Cooperativas (Sicoob/Sicredi) e arquivos padrão OFX ou planilhas CSV.',
    },
    {
        question: 'Como funciona o aplicativo no celular (PWA)?',
        answer: 'O DeixaSobrar foi construído com tecnologia PWA (Progressive Web App). Ao acessar pelo navegador do seu celular (Chrome ou Safari), basta tocar no aviso "Adicionar à Tela de Início". Ele será instalado como um app nativo, rápido, leve e sem ocupar a memória do seu telefone.',
    },
    {
        question: 'O que é a metodologia "Teto Diário Seguro"?',
        answer: 'Em vez de te mostrar gráficos difíceis depois que o mês acabou, o DeixaSobrar protege primeiro todas as suas contas fixas e carnês. O dinheiro que realmente sobra é dividido pelos dias que faltam até seu próximo salário. Você sabe exatamente quanto pode gastar hoje com tranquilidade.',
    },
    {
        question: 'Posso cancelar a assinatura se não me adaptar?',
        answer: 'Sim, a qualquer momento com apenas 1 clique dentro do painel, sem telefonemas nem pegadinhas. Além disso, você conta com nossa garantia de 7 dias com reembolso integral.',
    },
];

const openIndex = ref(0);

const toggleFaq = (index) => {
    openIndex.value = openIndex.value === index ? -1 : index;
};
</script>

<template>
    <section id="faq" class="py-24 bg-slate-900/40 border-t border-slate-800 relative overflow-hidden">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="text-center max-w-2xl mx-auto mb-16">
                <span 
                    v-reveal="{ delay: 50, direction: 'scale' }"
                    class="inline-block text-xs font-bold uppercase tracking-wider text-emerald-400 bg-emerald-500/10 px-3.5 py-1.5 rounded-full border border-emerald-500/20 shadow-sm"
                >
                    Tire Suas Dúvidas
                </span>
                <h2 
                    v-reveal="{ delay: 150, direction: 'up' }"
                    class="text-3xl sm:text-4xl lg:text-5xl font-extrabold text-white tracking-tight mt-3 mb-3"
                >
                    Perguntas Frequentes
                </h2>
                <p 
                    v-reveal="{ delay: 250, direction: 'up' }"
                    class="text-slate-400 text-sm sm:text-base"
                >
                    Tudo o que você precisa saber para começar a usar sem medo.
                </p>
            </div>

            <!-- Accordion -->
            <div class="space-y-4">
                <div 
                    v-for="(faq, i) in faqs" 
                    :key="i"
                    v-reveal="{ delay: 150 + i * 80, direction: 'up' }"
                    :class="[
                        'rounded-2xl border transition-all duration-300 overflow-hidden',
                        openIndex === i 
                            ? 'bg-slate-900/90 border-emerald-500/50 shadow-xl shadow-emerald-950/20' 
                            : 'bg-slate-950/60 border-slate-800 hover:border-slate-700'
                    ]"
                >
                    <button 
                        @click="toggleFaq(i)"
                        type="button"
                        class="w-full px-6 py-5 flex items-center justify-between text-left focus:outline-none transition-colors"
                    >
                        <span class="text-sm sm:text-base font-bold text-white pr-4">
                            {{ faq.question }}
                        </span>
                        <div class="w-8 h-8 rounded-lg bg-slate-800/80 flex items-center justify-center shrink-0 border border-slate-700/60 transition-colors">
                            <ChevronDown 
                                class="w-4 h-4 text-emerald-400 transition-transform duration-300 ease-out"
                                :class="{ 'rotate-180': openIndex === i }"
                            />
                        </div>
                    </button>

                    <transition
                        enter-active-class="transition-all duration-300 ease-out"
                        enter-from-class="opacity-0 -translate-y-2 max-h-0"
                        enter-to-class="opacity-100 translate-y-0 max-h-96"
                        leave-active-class="transition-all duration-200 ease-in"
                        leave-from-class="opacity-100 translate-y-0 max-h-96"
                        leave-to-class="opacity-0 -translate-y-2 max-h-0"
                    >
                        <div 
                            v-show="openIndex === i"
                            class="px-6 pb-6 text-xs sm:text-sm text-slate-300 leading-relaxed border-t border-slate-800/60 pt-4"
                        >
                            {{ faq.answer }}
                        </div>
                    </transition>
                </div>
            </div>

        </div>
    </section>
</template>
