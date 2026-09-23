<script setup>
import { usePage, Link, router } from '@inertiajs/vue3';
import { 
    LayoutDashboard, 
    Users, 
    Sliders, 
    ArrowLeft, 
    LogOut, 
    ShieldAlert,
    TrendingUp
} from 'lucide-vue-next';

defineProps({
    title: {
        type: String,
        default: 'Master Admin',
    },
    adminUser: {
        type: Object,
        default: () => ({ name: 'Administrador', email: '' }),
    },
});

const page = usePage();

const navItems = [
    { name: 'Visão Geral & MRR', href: '/admin/dashboard', icon: LayoutDashboard },
    { name: 'Usuários & Assinantes', href: '/admin/usuarios', icon: Users },
    { name: 'Configurações Globais', href: '/admin/configuracoes', icon: Sliders },
];

const isActive = (href) => {
    return page.url === href || (href !== '/admin/dashboard' && page.url.startsWith(href));
};

const logout = () => {
    router.post('/logout');
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col md:flex-row font-sans selection:bg-purple-500 selection:text-white">
        
        <!-- Desktop Admin Sidebar -->
        <aside class="hidden md:flex flex-col w-64 bg-slate-900/90 border-r border-slate-800 p-5 shrink-0 justify-between">
            <div>
                <!-- Brand & Master Admin Badge -->
                <div class="mb-6">
                    <Link href="/admin/dashboard" class="flex items-center gap-3">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-purple-600 to-indigo-500 p-0.5">
                            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                <ShieldAlert class="w-5 h-5 text-purple-400" />
                            </div>
                        </div>
                        <div>
                            <span class="text-base font-extrabold text-white tracking-tight">
                                Deixa<span class="text-emerald-400">Sobrar</span>
                            </span>
                            <span class="block text-[10px] font-bold tracking-widest uppercase text-purple-400">
                                Master Admin
                            </span>
                        </div>
                    </Link>
                </div>



                <!-- Admin Navigation -->
                <nav class="space-y-1.5">
                    <Link 
                        v-for="item in navItems" 
                        :key="item.name" 
                        :href="item.href"
                        class="flex items-center gap-3 px-3.5 py-2.5 rounded-xl text-sm font-semibold transition-all"
                        :class="isActive(item.href) 
                            ? 'bg-purple-500/15 text-purple-300 border border-purple-500/30 shadow-sm shadow-purple-500/10' 
                            : 'text-slate-400 hover:text-white hover:bg-slate-800/60'"
                    >
                        <component :is="item.icon" class="w-4 h-4" :class="isActive(item.href) ? 'text-purple-400' : 'text-slate-400'" />
                        {{ item.name }}
                    </Link>
                </nav>
            </div>

            <!-- Admin User Footer -->
            <div class="pt-4 border-t border-slate-800 flex items-center justify-between">
                <div class="truncate pr-2">
                    <div class="flex items-center gap-1.5">
                        <span class="w-2 h-2 rounded-full bg-emerald-400"></span>
                        <p class="text-xs font-bold text-white truncate">{{ adminUser.name }}</p>
                    </div>
                    <p class="text-[10px] text-purple-400 font-semibold truncate uppercase mt-0.5">Super Administrador</p>
                </div>
                <button 
                    @click="logout" 
                    title="Sair"
                    class="p-2 rounded-lg text-slate-400 hover:text-rose-400 hover:bg-rose-500/10 transition-colors"
                >
                    <LogOut class="w-4 h-4" />
                </button>
            </div>
        </aside>

        <!-- Mobile Topbar -->
        <header class="md:hidden flex items-center justify-between px-4 py-3 bg-slate-900/90 border-b border-slate-800 sticky top-0 z-40">
            <Link href="/admin/dashboard" class="flex items-center gap-2">
                <div class="w-8 h-8 rounded-lg bg-purple-600 flex items-center justify-center text-white font-black text-xs">
                    MA
                </div>
                <div>
                    <span class="text-sm font-extrabold text-white">DeixaSobrar</span>
                    <span class="block text-[9px] text-purple-400 font-bold uppercase">Master Admin</span>
                </div>
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

        <!-- Main Admin Content Area -->
        <div class="flex-1 flex flex-col min-w-0 overflow-y-auto">
            <!-- Flash notification -->
            <div v-if="page.props.flash?.success" class="bg-emerald-500/20 border-b border-emerald-500/30 px-6 py-2.5 text-xs text-emerald-300 font-semibold">
                ✓ {{ page.props.flash.success }}
            </div>
            <div v-if="page.props.flash?.error" class="bg-rose-500/20 border-b border-rose-500/30 px-6 py-2.5 text-xs text-rose-300 font-semibold">
                ✕ {{ page.props.flash.error }}
            </div>

            <main class="flex-1 px-4 sm:px-8 lg:px-10 py-6 sm:py-8 w-full max-w-[1700px] mx-auto">
                <slot />
            </main>
        </div>

    </div>
</template>
