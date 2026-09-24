<script setup>
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    X,
    LayoutDashboard,
    PieChart,
    Camera,
    Building2,
    FileUp,
    ArrowLeftRight,
    ShieldCheck,
    Flame,
    CreditCard,
    Sliders,
    Sparkles,
    ShieldAlert,
    LogOut,
    ChevronRight,
    UserCheck,
    CheckCircle2
} from 'lucide-vue-next';

defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    user: {
        type: Object,
        default: () => ({ name: 'Usuário', email: '' }),
    },
});

const emit = defineEmits(['close', 'open-preferences']);

const page = usePage();

const menuLinks = [
    { name: 'Visão Geral', desc: 'Resumo e Teto Diário', href: '/dashboard', icon: LayoutDashboard, color: 'text-emerald-400', bg: 'bg-emerald-500/10' },
    { name: 'Extratos Bancários', desc: 'Upload e histórico de arquivos', href: '/extratos', icon: FileUp, color: 'text-blue-400', bg: 'bg-blue-500/10' },
    { name: 'Scanner OCR', desc: 'Leitura de cupons e comprovantes', href: '/scanner', icon: Camera, color: 'text-teal-400', bg: 'bg-teal-500/10' },
    { name: 'Inteligência & Gráficos', desc: 'Score, tendências e análises', href: '/analises', icon: PieChart, color: 'text-indigo-400', bg: 'bg-indigo-500/10' },
    { name: 'Lançamentos', desc: 'Extrato completo de transações', href: '/transacoes', icon: ArrowLeftRight, color: 'text-cyan-400', bg: 'bg-cyan-500/10' },
    { name: 'Contas & Bancos', desc: 'Saldos das suas instituições', href: '/contas', icon: Building2, color: 'text-emerald-400', bg: 'bg-emerald-500/10' },
    { name: 'Contas Blindadas', desc: 'Despesas fixas e tetos', href: '/blindagem', icon: ShieldCheck, color: 'text-amber-400', bg: 'bg-amber-500/10' },
    { name: 'Raio-X Vazamentos', desc: 'Radar de gastos invisíveis', href: '/vazamentos', icon: Flame, color: 'text-rose-400', bg: 'bg-rose-500/10' },
    { name: 'Previsibilidade & Simulador', desc: 'Simule compras e veja o impacto', href: '/dashboard?tab=simulator', icon: Sparkles, color: 'text-purple-400', bg: 'bg-purple-500/10' },
    { name: 'Meu Plano Pro', desc: 'Benefícios e cobrança', href: '/assinatura', icon: CreditCard, color: 'text-emerald-400', bg: 'bg-emerald-500/10' },
];

const logout = () => {
    emit('close');
    router.post('/logout');
};

const handleOpenPreferences = () => {
    emit('close');
    emit('open-preferences');
};
</script>

<template>
    <!-- Modal Backdrop -->
    <Teleport to="body">
        <div 
            v-if="isOpen" 
            class="fixed inset-0 z-50 flex flex-col justify-end bg-slate-950/80 backdrop-blur-md transition-opacity duration-300"
            @click.self="$emit('close')"
        >
            <!-- Drawer Sheet -->
            <div 
                class="w-full max-h-[88vh] bg-slate-900 border-t border-slate-800 rounded-t-[32px] shadow-2xl flex flex-col overflow-hidden animate-in slide-in-from-bottom duration-300"
            >
                <!-- Pull Indicator / Handle -->
                <div class="pt-3 pb-1 flex justify-center">
                    <div class="w-12 h-1.5 bg-slate-700/80 rounded-full"></div>
                </div>

                <!-- Drawer Header -->
                <div class="px-5 py-3 flex items-center justify-between border-b border-slate-800/80">
                    <div class="flex items-center gap-3">
                        <div class="w-11 h-11 rounded-2xl bg-gradient-to-tr from-emerald-500 to-teal-400 p-0.5 shadow-md shadow-emerald-500/20">
                            <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center font-black text-emerald-400 text-sm">
                                {{ user.name?.charAt(0) || 'U' }}
                            </div>
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h3 class="font-extrabold text-white text-base leading-tight">{{ user.name }}</h3>
                                <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-400 border border-emerald-500/30">
                                    PRO
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 truncate max-w-[200px]">{{ user.email }}</p>
                        </div>
                    </div>

                    <button 
                        @click="$emit('close')" 
                        class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                    >
                        <X class="w-5 h-5" />
                    </button>
                </div>

                <!-- Admin Banner (se for admin) -->
                <div v-if="page.props.auth?.user?.is_admin" class="px-5 pt-3">
                    <Link 
                        href="/admin/dashboard" 
                        @click="$emit('close')"
                        class="w-full flex items-center justify-between p-3 rounded-2xl bg-gradient-to-r from-purple-900/40 via-purple-800/30 to-slate-900 border border-purple-500/40 text-purple-200 hover:border-purple-400 transition-all shadow-lg shadow-purple-950/40"
                    >
                        <div class="flex items-center gap-3">
                            <div class="p-2 rounded-xl bg-purple-500/20 text-purple-300">
                                <ShieldAlert class="w-5 h-5" />
                            </div>
                            <div>
                                <span class="font-bold text-sm text-white block">Painel Master Admin ⚡</span>
                                <span class="text-[11px] text-purple-300/80">Gestão global de usuários, métricas e IA</span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-purple-400" />
                    </Link>
                </div>

                <!-- Links List (Scrollable) -->
                <div class="flex-1 overflow-y-auto px-5 py-4 space-y-2">
                    <span class="text-[11px] font-bold text-slate-400 uppercase tracking-wider block px-1">
                        Todos os Módulos do Sistema
                    </span>

                    <div class="grid grid-cols-1 gap-1.5">
                        <Link 
                            v-for="item in menuLinks" 
                            :key="item.name"
                            :href="item.href"
                            @click="$emit('close')"
                            class="flex items-center justify-between p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800/80 hover:border-slate-700 transition-all group"
                        >
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0" :class="item.bg">
                                    <component :is="item.icon" class="w-5 h-5" :class="item.color" />
                                </div>
                                <div>
                                    <span class="text-sm font-bold text-slate-100 group-hover:text-white block">
                                        {{ item.name }}
                                    </span>
                                    <span class="text-[11px] text-slate-400 block">
                                        {{ item.desc }}
                                    </span>
                                </div>
                            </div>
                            <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-300 transition-colors" />
                        </Link>
                    </div>

                    <!-- Configurar Ciclo Salarial Action -->
                    <button 
                        @click="handleOpenPreferences"
                        class="w-full flex items-center justify-between p-2.5 rounded-2xl bg-slate-900/60 hover:bg-slate-800/80 border border-slate-800/80 text-left transition-all group"
                    >
                        <div class="flex items-center gap-3">
                            <div class="w-10 h-10 rounded-xl bg-teal-500/10 flex items-center justify-center shrink-0">
                                <Sliders class="w-5 h-5 text-teal-400" />
                            </div>
                            <div>
                                <span class="text-sm font-bold text-slate-100 group-hover:text-white block">
                                    Ajustar Ciclo & Salário
                                </span>
                                <span class="text-[11px] text-slate-400 block">
                                    Dia de pagamento, reserva e alertas
                                </span>
                            </div>
                        </div>
                        <ChevronRight class="w-4 h-4 text-slate-600 group-hover:text-slate-300 transition-colors" />
                    </button>
                </div>

                <!-- Drawer Footer -->
                <div class="p-4 border-t border-slate-800 bg-slate-950/60 flex items-center justify-between pb-safe">
                    <div class="text-[11px] text-slate-500">
                        DeixaSobrar PWA v2.0
                    </div>

                    <button 
                        @click="logout"
                        class="flex items-center gap-2 px-4 py-2 rounded-xl text-xs font-bold text-rose-400 bg-rose-500/10 hover:bg-rose-500/20 border border-rose-500/20 transition-all"
                    >
                        <LogOut class="w-4 h-4" />
                        <span>Sair da Conta</span>
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
