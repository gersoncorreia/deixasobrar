<script setup>
import HeaderNav from '@/Components/Landing/HeaderNav.vue';
import FooterSection from '@/Components/Landing/FooterSection.vue';
import PwaInstallBanner from '@/Components/Landing/PwaInstallBanner.vue';
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col selection:bg-emerald-500 selection:text-slate-950 font-sans">
        <!-- Sticky / Fixed Navigation Bar -->
        <HeaderNav />

        <!-- Main Body Content -->
        <main class="flex-grow">
            <slot />
        </main>

        <!-- PWA Installation Prompt for Android / iOS -->
        <PwaInstallBanner />

        <!-- Global Institutional Footer -->
        <FooterSection />
    </div>
</template>
