<script setup>
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
    PieChart
} from 'lucide-vue-next';

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
                    <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 p-0.5">
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
                        class="w-full flex items-center justify-center gap-2 py-2 px-3 rounded-xl bg-purple-500/15 border border-purple-500/30 text-purple-300 hover:bg-purple-500/25 hover:text-white text-xs font-bold transition-all"
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

        <!-- Mobile Topbar -->
        <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900/90 border-b border-slate-800 pt-safe sticky top-0 z-40">
            <Link href="/dashboard" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-emerald-500 flex items-center justify-center text-slate-950 font-black text-xs">
                    DS
                </div>
                <span class="text-base font-extrabold text-white">Deixa<span class="text-emerald-400">Sobrar</span></span>
            </Link>
            <div class="flex items-center gap-2">
                <button 
                    @click="logout"
                    class="text-xs font-semibold text-slate-400 hover:text-rose-400 px-2 py-1"
                >
                    Sair
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

            <main class="flex-1 px-4 sm:px-8 lg:px-10 py-6 sm:py-8 w-full max-w-[1700px] mx-auto">
                <slot />
            </main>
        </div>

        <!-- Mobile PWA Bottom Navigation Bar (Otimizada & Ergonômica) -->
        <nav class="md:hidden fixed bottom-0 left-0 right-0 z-40 bg-slate-950/90 backdrop-blur-xl border-t border-slate-800/80 px-2 py-1.5 flex items-center justify-around pb-safe shadow-[0_-10px_25px_rgba(0,0,0,0.5)]">
            <!-- 1. Visão Geral -->
            <Link 
                href="/dashboard" 
                class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-[10px] font-semibold transition-colors"
                :class="isActive('/dashboard') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <LayoutDashboard class="w-4 h-4" />
                <span>Início</span>
            </Link>

            <!-- 2. Lançamentos -->
            <Link 
                href="/transacoes" 
                class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-[10px] font-semibold transition-colors"
                :class="isActive('/transacoes') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <ArrowLeftRight class="w-4 h-4" />
                <span>Extrato</span>
            </Link>

            <!-- 3. Scanner OCR (Botão Central em Destaque) -->
            <Link 
                href="/scanner" 
                class="flex flex-col items-center -mt-5 group"
            >
                <div class="w-12 h-12 rounded-2xl bg-gradient-to-tr from-emerald-600 via-emerald-500 to-teal-400 p-0.5 shadow-lg shadow-emerald-500/30 group-active:scale-95 transition-transform">
                    <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center group-hover:bg-transparent transition-colors">
                        <Camera class="w-5 h-5 text-emerald-400 group-hover:text-slate-950 transition-colors" />
                    </div>
                </div>
                <span class="text-[9px] font-extrabold text-emerald-400 mt-1">Scanner</span>
            </Link>

            <!-- 4. Vazamentos -->
            <Link 
                href="/vazamentos" 
                class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-[10px] font-semibold transition-colors"
                :class="isActive('/vazamentos') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <Flame class="w-4 h-4" />
                <span>Raio-X</span>
            </Link>

            <!-- 5. Blindagem / Mais -->
            <Link 
                href="/blindagem" 
                class="flex flex-col items-center gap-1 py-1 px-2 rounded-xl text-[10px] font-semibold transition-colors"
                :class="isActive('/blindagem') ? 'text-emerald-400 font-bold' : 'text-slate-400 hover:text-white'"
            >
                <ShieldCheck class="w-4 h-4" />
                <span>Blindar</span>
            </Link>
        </nav>

    </div>
</template>
