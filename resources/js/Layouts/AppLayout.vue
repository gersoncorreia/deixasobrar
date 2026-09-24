<script setup>
import { ref, provide } from 'vue';
import { usePage, Link, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Building2, 
    FileUp, 
    ArrowLeftRight, 
    ShieldCheck, 
    Flame,
    LogOut, 
    TrendingUp,
    Camera,
    CreditCard,
    PieChart,
    Eye,
    EyeOff,
    Menu,
    SlidersHorizontal,
    Sparkles
} from 'lucide-vue-next';
import { usePrivacyMode } from '@/Composables/usePrivacyMode';
import MobileMenuDrawer from '@/Components/Mobile/MobileMenuDrawer.vue';

defineProps({
    title: {
        type: String,
        default: 'Dashboard',
    },
    user: {
        type: Object,
        default: () => ({ name: 'Usuário', email: '' }),
    },
});

const page = usePage();
const { isPrivate, togglePrivacy } = usePrivacyMode();
const isMobileMenuOpen = ref(false);

provide('openMobileMenu', () => {
    isMobileMenuOpen.value = true;
});

const navItems = [
    { name: 'Visão Geral', href: '/dashboard', icon: LayoutDashboard },
    { name: 'Inteligência & Gráficos', href: '/analises', icon: PieChart },
    { name: 'Scanner OCR', href: '/scanner', icon: Camera },
    { name: 'Contas & Bancos', href: '/contas', icon: Building2 },
    { name: 'Extratos', href: '/extratos', icon: FileUp },
    { name: 'Lançamentos', href: '/transacoes', icon: ArrowLeftRight },
    { name: 'Contas Blindadas', href: '/blindagem', icon: ShieldCheck },
    { name: 'Raio-X Vazamentos', href: '/vazamentos', icon: Flame },
    { name: 'Meu Plano Pro', href: '/assinatura', icon: CreditCard },
];

const isActive = (href) => {
    return page.url === href || (href !== '/dashboard' && page.url.startsWith(href));
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row font-sans selection:bg-emerald-500 selection:text-slate-950">
        
        <!-- Desktop Sidebar -->
        <aside class="hidden md:flex flex-col w-64 bg-slate-900/60 border-r border-slate-800/80 p-5 shrink-0 justify-between">
            <div>
                <!-- Brand -->
                <Link href="/dashboard" class="flex items-center gap-3 mb-8">
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 p-0.5 shadow-md shadow-emerald-500/20">
                        <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                            <TrendingUp class="w-5 h-5 text-emerald-400" />
                        </div>
                    </div>
                    <span class="text-lg font-extrabold text-white tracking-tight">
                        Deixa<span class="text-emerald-400">Sobrar</span>
                    </span>
                </Link>

                <!-- Navigation -->
                <nav class="space-y-1.5">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name" 
                        :href="item.href"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                        :class="isActive(item.href) 
                            ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30 shadow-sm shadow-emerald-500/10' 
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                    >
                        <component :is="item.icon" class="w-4 h-4" :class="isActive(item.href) ? 'text-emerald-400' : 'text-slate-400'" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>

            <!-- User Footer in Sidebar -->
            <div class="pt-4 border-t border-slate-800/80 space-y-2">
                <div v-if="page.props.auth?.user?.is_admin" class="px-1">
                    <Link 
                        href="/admin/dashboard" 
                        class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-300 hover:bg-purple-500/25 hover:text-white text-xs font-bold transition-all shadow-sm"
                    >
                        <span>Painel Master Admin ⚡</span>
                    </Link>
                </div>

                <div class="flex items-center justify-between">
                    <div class="truncate pr-2">
                        <p class="text-xs font-bold text-white truncate">{{ user.name }}</p>
                        <p class="text-[10px] text-slate-400 truncate">{{ user.email }}</p>
                    </div>
                    <button 
                        @click="logout" 
                        title="Sair"
                        class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                    >
                        <LogOut class="w-4 h-4" />
                    </button>
                </div>
            </div>
        </aside>

        <!-- Mobile Topbar (Padrão Banco do Brasil: Avatar, Saudação, Olho de Privacidade, Menu) -->
        <header class="md:hidden flex items-center justify-between px-5 pt-4 pb-3.5 bg-slate-900/95 border-b border-slate-800/80 pt-safe sticky top-0 z-40 backdrop-blur-xl shadow-sm">
            <div class="flex items-center gap-3">
                <!-- User Avatar Button (Abre Gaveta de Perfil/Menu) -->
                <button 
                    @click="isMobileMenuOpen = true"
                    class="relative w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-md shadow-emerald-500/20 active:scale-95 transition-transform"
                >
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center font-black text-emerald-400 text-xs">
                        {{ user.name?.charAt(0) || 'U' }}
                    </div>
                    <span class="absolute -bottom-0.5 -right-0.5 w-2.5 h-2.5 rounded-full bg-emerald-400 ring-2 ring-slate-900"></span>
                </button>

                <!-- Greeting / Context -->
                <div @click="isMobileMenuOpen = true" class="cursor-pointer">
                    <div class="flex items-center gap-1.5">
                        <span class="text-sm font-extrabold text-white leading-none">
                            Olá, {{ user.name?.split(' ')[0] || 'Usuário' }}
                        </span>
                        <span class="px-1.5 py-0.5 rounded-md text-[9px] font-bold bg-emerald-500/15 text-emerald-400 border border-emerald-500/30">
                            PRO
                        </span>
                    </div>
                    <span class="text-[10px] text-slate-400 font-medium block mt-1">
                        DeixaSobrar • Gestão Ativa
                    </span>
                </div>
            </div>

            <!-- Topbar Actions: Privacy Eye & Drawer Trigger -->
            <div class="flex items-center gap-2">
                <!-- Privacy Mode Button (Olho de Privacidade) -->
                <button 
                    @click="togglePrivacy" 
                    type="button"
                    class="p-2 rounded-xl text-slate-300 hover:text-white bg-slate-800/80 border border-slate-700/80 active:scale-95 transition-all shadow-sm"
                    :title="isPrivate ? 'Mostrar valores monetários' : 'Ocultar valores monetários'"
                >
                    <EyeOff v-if="isPrivate" class="w-4 h-4 text-emerald-400" />
                    <Eye v-else class="w-4 h-4 text-slate-300" />
                </button>

                <!-- Full Menu Trigger -->
                <button 
                    @click="isMobileMenuOpen = true"
                    type="button"
                    class="p-2 rounded-xl text-slate-300 hover:text-white bg-slate-800/80 border border-slate-700/80 active:scale-95 transition-all shadow-sm"
                    title="Menu de Módulos"
                >
                    <Menu class="w-4 h-4" />
                </button>
            </div>
        </header>

        <!-- Main Content View Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Impersonation Alert Banner -->
            <div 
                v-if="page.props.auth?.is_impersonating" 
                class="bg-amber-500/20 border-b border-amber-500/40 px-4 py-2.5 flex items-center justify-between text-xs text-amber-200"
            >
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-amber-400 animate-ping"></span>
                    <span><strong>Modo de Suporte Ativo:</strong> Você está visualizando o app como o cliente <strong>{{ user.name }}</strong>.</span>
                </div>
                <a 
                    href="/admin/stop-impersonation" 
                    class="px-3 py-1 rounded-lg bg-amber-500 text-slate-950 font-bold hover:bg-amber-400 transition-colors"
                >
                    Retornar ao Painel Master ⚡
                </a>
            </div>

            <main class="flex-1 px-4 sm:px-8 lg:px-10 pt-6 pb-8 sm:py-8 w-full max-w-[1700px] mx-auto">
                <slot />
            </main>
        </div>

        <!-- Mobile PWA Bottom Navigation Bar (Dock Ergonômico de 5 botões) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/95 backdrop-blur-xl border-t border-slate-800/80 px-2 py-1.5 flex items-center justify-around pb-safe shadow-[0_-10px_30px_rgba(0,0,0,0.6)]">
            <!-- 1. Visão Geral / Início -->
            <Link 
                href="/dashboard" 
                class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-[10px] font-semibold transition-all active:scale-95"
                :class="isActive('/dashboard') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <div 
                    class="p-1 rounded-lg transition-all"
                    :class="isActive('/dashboard') ? 'bg-emerald-500/15' : ''"
                >
                    <LayoutDashboard class="w-4 h-4" />
                </div>
                <span>Início</span>
            </Link>

            <!-- 2. Extrato / Lançamentos -->
            <Link 
                href="/transacoes" 
                class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-[10px] font-semibold transition-all active:scale-95"
                :class="isActive('/transacoes') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <div 
                    class="p-1 rounded-lg transition-all"
                    :class="isActive('/transacoes') ? 'bg-emerald-500/15' : ''"
                >
                    <ArrowLeftRight class="w-4 h-4" />
                </div>
                <span>Extrato</span>
            </Link>

            <!-- 3. Scanner OCR (Botão Central em Destaque Fluorescente) -->
            <Link 
                href="/scanner" 
                class="flex flex-col items-center -mt-5 group"
            >
                <div class="w-12 h-12 rounded-xl bg-gradient-to-tr from-emerald-600 via-emerald-500 to-teal-400 p-0.5 shadow-xl shadow-emerald-500/35 group-active:scale-90 transition-transform">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center group-hover:bg-transparent transition-colors p-2">
                        <Camera class="w-5 h-5 text-emerald-400 group-hover:text-slate-950 transition-colors" />
                    </div>
                </div>
                <span class="text-[9px] font-extrabold text-emerald-400 mt-1">Scanner</span>
            </Link>

            <!-- 4. Análises & Gráficos (Agora com acesso direto no Dock!) -->
            <Link 
                href="/analises" 
                class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-[10px] font-semibold transition-all active:scale-95"
                :class="isActive('/analises') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <div 
                    class="p-1 rounded-lg transition-all"
                    :class="isActive('/analises') ? 'bg-emerald-500/15' : ''"
                >
                    <PieChart class="w-4 h-4" />
                </div>
                <span>Análises</span>
            </Link>

            <!-- 5. Menu Completo (Abre Drawer com todos os links da Sidebar) -->
            <button 
                type="button"
                @click="isMobileMenuOpen = true"
                class="flex flex-col items-center gap-1 py-1 px-2.5 rounded-xl text-[10px] font-semibold text-slate-400 hover:text-white transition-all active:scale-95"
            >
                <div class="p-1 rounded-lg hover:bg-slate-800/60">
                    <SlidersHorizontal class="w-4 h-4" />
                </div>
                <span>Menu</span>
            </button>
        </nav>

        <!-- Mobile Full Menu Drawer Teleport -->
        <MobileMenuDrawer 
            :is-open="isMobileMenuOpen" 
            :user="user" 
            @close="isMobileMenuOpen = false" 
        />

    </div>
</template>
