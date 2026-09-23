<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link } from '@inertiajs/vue3';
import { 
    Menu, 
    X, 
    TrendingUp, 
    ArrowRight 
} from 'lucide-vue-next';

const isMobileMenuOpen = ref(false);
const isScrolled = ref(false);

const navLinks = [
    { name: 'Como Funciona', href: '#como-funciona' },
    { name: 'Diferenciais', href: '#diferenciais' },
    { name: 'Simulador', href: '#simulador' },
    { name: 'Multi-Bancos', href: '#extratos' },
    { name: 'Planos', href: '#planos' },
    { name: 'FAQ', href: '#faq' },
];

const handleScroll = () => {
    isScrolled.value = window.scrollY > 20;
};

onMounted(() => {
    window.addEventListener('scroll', handleScroll, { passive: true });
    handleScroll();
});

onUnmounted(() => {
    window.removeEventListener('scroll', handleScroll);
});

const closeMenu = () => {
    isMobileMenuOpen.value = false;
};
</script>

<template>
    <header 
        class="fixed top-0 left-0 right-0 z-50 transition-all duration-500"
        :class="[
            isScrolled 
                ? 'glass-panel border-b border-slate-800/90 py-3 shadow-2xl shadow-slate-950/60' 
                : 'bg-transparent border-b border-transparent py-5'
        ]"
    >
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between transition-all duration-300">
                <!-- Brand Logo with animated hover -->
                <Link href="/" class="flex items-center gap-3 group">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 p-0.5 shadow-lg shadow-emerald-500/20 group-hover:shadow-emerald-400/50 group-hover:rotate-6 transition-all duration-300">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <TrendingUp class="w-5 h-5 text-emerald-400 group-hover:scale-125 transition-transform duration-300" />
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <span class="text-xl font-extrabold tracking-tight text-white flex items-center gap-1">
                            Deixa<span class="text-emerald-400 group-hover:text-emerald-300 transition-colors">Sobrar</span>
                        </span>
                        <span class="text-[10px] font-medium text-slate-400 -mt-1 hidden sm:inline tracking-wide">
                            Gestão sem economês
                        </span>
                    </div>
                </Link>

                <!-- Desktop Navigation Links with Magnetic Underline -->
                <nav class="hidden md:flex items-center gap-7">
                    <a 
                        v-for="link in navLinks" 
                        :key="link.name" 
                        :href="link.href"
                        class="text-sm font-medium text-slate-300 hover:text-emerald-400 transition-all duration-200 py-1 relative group"
                    >
                        {{ link.name }}
                        <span class="absolute bottom-0 left-0 w-0 h-0.5 bg-gradient-to-r from-emerald-400 to-teal-300 group-hover:w-full transition-all duration-300 rounded-full"></span>
                    </a>
                </nav>

                <!-- Actions / CTAs -->
                <div class="hidden md:flex items-center gap-4">
                    <Link 
                        href="/login" 
                        class="text-sm font-semibold text-slate-300 hover:text-white px-4 py-2 rounded-xl hover:bg-slate-800/60 transition-all hover:scale-105 active:scale-95"
                    >
                        Entrar
                    </Link>
                    <Link 
                        href="/register" 
                        class="btn-shimmer relative inline-flex items-center justify-center px-5 py-2.5 text-sm font-bold text-slate-950 bg-gradient-to-r from-emerald-400 to-teal-300 hover:from-emerald-300 hover:to-teal-200 rounded-xl shadow-lg shadow-emerald-500/25 hover:shadow-emerald-500/40 hover:-translate-y-0.5 active:translate-y-0 active:scale-95 transition-all duration-200 group"
                    >
                        <span class="relative z-10 flex items-center gap-2">
                            Começar Grátis
                            <ArrowRight class="w-4 h-4 group-hover:translate-x-1 transition-transform duration-200" />
                        </span>
                        <!-- Pulse ripple ring -->
                        <span class="absolute inset-0 rounded-xl bg-emerald-400/40 animate-ping opacity-25 pointer-events-none"></span>
                    </Link>
                </div>

                <!-- Mobile Menu Hamburger Button -->
                <div class="flex md:hidden items-center gap-2">
                    <Link 
                        href="/register"
                        class="btn-shimmer text-xs font-bold text-slate-950 bg-emerald-400 px-3 py-1.5 rounded-lg active:scale-95 transition-transform"
                    >
                        Grátis
                    </Link>
                    <button 
                        @click="isMobileMenuOpen = !isMobileMenuOpen"
                        type="button" 
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/70 focus:outline-none transition-colors"
                        aria-label="Abrir menu"
                    >
                        <Menu v-if="!isMobileMenuOpen" class="w-6 h-6" />
                        <X v-else class="w-6 h-6 text-emerald-400" />
                    </button>
                </div>
            </div>
        </div>

        <!-- Mobile Drawer Menu with Elastic Animation -->
        <transition
            enter-active-class="transition-all duration-300 ease-out"
            enter-from-class="opacity-0 -translate-y-6 scale-95"
            enter-to-class="opacity-100 translate-y-0 scale-100"
            leave-active-class="transition-all duration-200 ease-in"
            leave-from-class="opacity-100 translate-y-0 scale-100"
            leave-to-class="opacity-0 -translate-y-6 scale-95"
        >
            <div 
                v-if="isMobileMenuOpen" 
                class="md:hidden glass-panel border-b border-slate-800 px-5 pt-4 pb-7 space-y-4 bg-slate-950/95 shadow-2xl backdrop-blur-2xl"
            >
                <div class="flex flex-col space-y-2">
                    <a 
                        v-for="link in navLinks" 
                        :key="link.name" 
                        :href="link.href"
                        @click="closeMenu"
                        class="px-3 py-2.5 rounded-xl text-base font-medium text-slate-200 hover:text-emerald-400 hover:bg-slate-900/80 transition-colors"
                    >
                        {{ link.name }}
                    </a>
                </div>
                <div class="pt-4 border-t border-slate-800 flex flex-col gap-3">
                    <Link 
                        href="/login"
                        @click="closeMenu"
                        class="w-full text-center py-2.5 rounded-xl border border-slate-700 text-slate-200 font-semibold text-sm hover:bg-slate-800"
                    >
                        Entrar na Minha Conta
                    </Link>
                    <Link 
                        href="/register"
                        @click="closeMenu"
                        class="btn-shimmer w-full text-center py-3.5 rounded-xl bg-emerald-400 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/30"
                    >
                        Criar Conta Gratuita
                    </Link>
                </div>
            </div>
        </transition>
    </header>
</template>
