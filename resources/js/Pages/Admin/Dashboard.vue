<script setup>
import { Head, Link } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { 
    Users, 
    DollarSign, 
    TrendingUp, 
    ArrowUpRight, 
    Activity, 
    CreditCard, 
    FileText, 
    ShieldCheck, 
    Building2 
} from 'lucide-vue-next';

const props = defineProps({
    adminUser: Object,
    metrics: Object,
    recentUsers: Array,
});

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <Head title="Master Admin - Visão Geral" />

    <AdminLayout :admin-user="adminUser">
        <div class="space-y-8 pb-12">
            
            <!-- Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <div class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-purple-500/15 border border-purple-500/30 text-purple-300 text-xs font-bold mb-2">
                        <ShieldCheck class="w-3.5 h-3.5" />
                        <span>SaaS Master Control</span>
                    </div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Painel Executivo DeixaSobrar
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Métricas financeiras globais, assinaturas recorrentes e integridade da plataforma.
                    </p>
                </div>
            </div>

            <!-- Primary Metrics Cards -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                <!-- MRR Card -->
                <div class="glass-panel p-5 rounded-2xl border border-purple-500/25 bg-gradient-to-br from-purple-950/20 to-slate-900">
                    <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <span>MRR Recorrente</span>
                        <DollarSign class="w-4 h-4 text-purple-400" />
                    </div>
                    <div class="text-2xl font-black text-white font-display">
                        {{ formatCurrency(metrics.mrr) }}
                    </div>
                    <p class="text-[11px] text-purple-300/80 mt-1">
                        ARR projetado: <strong>{{ formatCurrency(metrics.arr) }}</strong>/ano
                    </p>
                </div>

                <!-- Total Users Card -->
                <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                    <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <span>Total de Usuários</span>
                        <Users class="w-4 h-4 text-emerald-400" />
                    </div>
                    <div class="text-2xl font-black text-white font-display">
                        {{ metrics.totalUsers }}
                    </div>
                    <p class="text-[11px] text-emerald-400 mt-1 flex items-center gap-1 font-semibold">
                        <ArrowUpRight class="w-3.5 h-3.5" />
                        +{{ metrics.newUsersThisMonth }} novos este mês
                    </p>
                </div>

                <!-- Active Subscribers / Health -->
                <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                    <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <span>Usuários Ativos</span>
                        <Activity class="w-4 h-4 text-teal-400" />
                    </div>
                    <div class="text-2xl font-black text-white font-display">
                        {{ metrics.activeUsers }}
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Status operacional da base: <strong>{{ metrics.totalUsers > 0 ? Math.round((metrics.activeUsers / metrics.totalUsers) * 100) : 0 }}% ativos</strong>
                    </p>
                </div>

                <!-- Volume of System Transactions -->
                <div class="glass-panel p-5 rounded-2xl border border-slate-800 bg-slate-900/60">
                    <div class="flex items-center justify-between text-slate-400 text-xs font-bold uppercase tracking-wider mb-2">
                        <span>Lançamentos Globais</span>
                        <FileText class="w-4 h-4 text-indigo-400" />
                    </div>
                    <div class="text-2xl font-black text-white font-display">
                        {{ metrics.totalTransactions }}
                    </div>
                    <p class="text-[11px] text-slate-400 mt-1">
                        Em <strong>{{ metrics.totalImports }}</strong> extratos e <strong>{{ metrics.totalAccounts }}</strong> contas
                    </p>
                </div>
            </div>

            <!-- Breakdown of Plans & Quick Actions -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Plans Breakdown (5 cols) -->
                <div class="lg:col-span-5 glass-panel p-6 rounded-3xl border border-slate-800">
                    <h3 class="text-sm font-bold text-white uppercase tracking-wider mb-4 flex items-center gap-2">
                        <CreditCard class="w-4 h-4 text-purple-400" />
                        <span>Distribuição de Planos</span>
                    </h3>
                    <div class="space-y-3">
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-xs font-semibold text-slate-300">Gratuito (Free)</span>
                            <span class="px-2.5 py-0.5 rounded-lg bg-slate-800 text-slate-300 text-xs font-bold">
                                {{ metrics.plansBreakdown.free }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-xs font-semibold text-emerald-300">Pro Mensal (R$ 19,90)</span>
                            <span class="px-2.5 py-0.5 rounded-lg bg-emerald-500/15 border border-emerald-500/30 text-emerald-400 text-xs font-bold">
                                {{ metrics.plansBreakdown.pro_mensal }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-xs font-semibold text-purple-300">Pro Anual (R$ 179,90)</span>
                            <span class="px-2.5 py-0.5 rounded-lg bg-purple-500/15 border border-purple-500/30 text-purple-300 text-xs font-bold">
                                {{ metrics.plansBreakdown.pro_anual }}
                            </span>
                        </div>
                        <div class="flex items-center justify-between p-3 rounded-xl bg-slate-900/80 border border-slate-800">
                            <span class="text-xs font-semibold text-indigo-300">Plano Família (R$ 29,90)</span>
                            <span class="px-2.5 py-0.5 rounded-lg bg-indigo-500/15 border border-indigo-500/30 text-indigo-300 text-xs font-bold">
                                {{ metrics.plansBreakdown.familia }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Recent Registered Users Table (7 cols) -->
                <div class="lg:col-span-7 glass-panel p-6 rounded-3xl border border-slate-800 flex flex-col justify-between">
                    <div>
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-white uppercase tracking-wider flex items-center gap-2">
                                <Users class="w-4 h-4 text-emerald-400" />
                                <span>Últimos Usuários Cadastrados</span>
                            </h3>
                            <Link 
                                href="/admin/usuarios"
                                class="text-xs text-purple-400 hover:text-purple-300 hover:underline font-semibold"
                            >
                                Ver todos ({{ metrics.totalUsers }}) →
                            </Link>
                        </div>

                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs">
                                <thead>
                                    <tr class="text-slate-400 border-b border-slate-800 pb-2">
                                        <th class="py-2 font-bold">Nome / Email</th>
                                        <th class="py-2 font-bold">Plano</th>
                                        <th class="py-2 font-bold">Data</th>
                                        <th class="py-2 font-bold text-right">Status</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-800/60">
                                    <tr v-for="user in recentUsers" :key="user.id" class="hover:bg-slate-900/40">
                                        <td class="py-2.5 pr-2">
                                            <div class="font-bold text-white">{{ user.name }}</div>
                                            <div class="text-[10px] text-slate-400">{{ user.email }}</div>
                                        </td>
                                        <td class="py-2.5">
                                            <span class="px-2 py-0.5 rounded-md text-[10px] font-bold bg-slate-800 text-slate-200">
                                                {{ user.plan }}
                                            </span>
                                        </td>
                                        <td class="py-2.5 text-slate-400 text-[11px]">
                                            {{ user.created_at }}
                                        </td>
                                        <td class="py-2.5 text-right">
                                            <span 
                                                class="px-2 py-0.5 rounded-full text-[10px] font-semibold"
                                                :class="user.is_active ? 'bg-emerald-500/15 text-emerald-400' : 'bg-rose-500/15 text-rose-400'"
                                            >
                                                {{ user.is_active ? 'Ativo' : 'Suspenso' }}
                                            </span>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

        </div>
    </AdminLayout>
</template>
