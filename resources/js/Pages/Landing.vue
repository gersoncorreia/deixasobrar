<script setup>
import { Head } from '@inertiajs/vue3';
import SiteLayout from '@/Layouts/SiteLayout.vue';
import HeroSection from '@/Components/Landing/HeroSection.vue';
import PainSolutionSection from '@/Components/Landing/PainSolutionSection.vue';
import CalculatorWidget from '@/Components/Landing/CalculatorWidget.vue';
import FeaturesGrid from '@/Components/Landing/FeaturesGrid.vue';
import PricingPlans from '@/Components/Landing/PricingPlans.vue';
import TestimonialsSection from '@/Components/Landing/TestimonialsSection.vue';
import FaqAccordion from '@/Components/Landing/FaqAccordion.vue';
import { useScrollReveal } from '@/Composables/useScrollReveal';

// Initialize the high-performance IntersectionObserver animation engine
useScrollReveal();

defineProps({
    stats: {
        type: Object,
        default: () => ({
            activeUsers: '14.200+',
            totalSaved: 'R$ 4.8M+',
            averageDailyCeiling: 'R$ 52,40',
        }),
    },
});
</script>

<template>
    <Head>
        <title>DeixaSobrar - Cuide dos gastos e deixe sobrar para o que importa</title>
        <meta 
            name="description" 
            content="Sistema simples, acolhedor e sem economês. Descubra seu Teto Diário Seguro, elimine vazamentos de Micro-Pix e apostas, e importe extratos de qualquer banco."
        />
        <meta property="og:title" content="DeixaSobrar - Gestão Financeira Sem Economês" />
        <meta property="og:description" content="Saiba exatamente quantos reais você pode gastar por dia sem faltar dinheiro para pagar suas contas." />
        <meta property="og:type" content="website" />
    </Head>

    <SiteLayout>
        <!-- 1. Hero Section com Headline, CTAs, 3D Tilt e Widget Central Animado -->
        <HeroSection />

        <!-- 2. Comparativo Dores vs Solução com Staggered Reveal -->
        <PainSolutionSection />

        <!-- 3. Simulador Interativo com Contadores Animados em Tempo Real -->
        <CalculatorWidget />

        <!-- 4. Apresentação dos 4 Recursos com 3D Hover & Vitrine Multi-Bancos -->
        <FeaturesGrid />

        <!-- 5. Tabela de Preços SaaS com Toggle Elástico e Destaque Neon -->
        <PricingPlans />

        <!-- 6. Depoimentos com Estrelas Cintilantes e Prova Social -->
        <TestimonialsSection />

        <!-- 7. FAQ com Acordeão Fluido e Rotação Suave -->
        <FaqAccordion />
    </SiteLayout>
</template>
