<script setup>
import { ref } from 'vue';
import { Head, router } from '@inertiajs/vue3';
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { 
    Users, 
    Search, 
    ShieldAlert, 
    UserCheck, 
    UserX, 
    LogIn, 
    CreditCard, 
    Check, 
    X,
    Filter
} from 'lucide-vue-next';

const props = defineProps({
    adminUser: Object,
    users: Object, // paginated
    filters: Object,
    availablePlans: Array,
});

const searchTerm = ref(props.filters?.search || '');
const selectedStatus = ref(props.filters?.status || 'all');

// Plan Editing Modal State
const editingUser = ref(null);
const selectedPlan = ref('free');
const selectedPlanStatus = ref('active');
const isSubmitting = ref(false);

const handleSearch = () => {
    router.get('/admin/usuarios', {
        search: searchTerm.value || undefined,
        status: selectedStatus.value !== 'all' ? selectedStatus.value : undefined,
    }, { preserveState: true, replace: true });
};

const toggleUserStatus = (user) => {
    if (confirm(`Tem certeza que deseja ${user.is_active ? 'suspender' : 'ativar'} o usuário ${user.name}?`)) {
        router.post(`/admin/usuarios/${user.id}/toggle-status`, {}, { preserveScroll: true });
    }
};

const openPlanModal = (user) => {
    editingUser.value = user;
    selectedPlan.value = user.subscription?.plan || 'free';
    selectedPlanStatus.value = user.subscription?.status || 'active';
};

const closePlanModal = () => {
    editingUser.value = null;
};

const savePlan = () => {
    if (!editingUser.value) return;
    isSubmitting.value = true;
    router.post(`/admin/usuarios/${editingUser.value.id}/update-plan`, {
        plan_tier: selectedPlan.value,
        status: selectedPlanStatus.value,
    }, {
        preserveScroll: true,
        onFinish: () => {
            isSubmitting.value = false;
            closePlanModal();
        }
    });
};

const impersonate = (user) => {
    if (confirm(`Entrar no aplicativo como "${user.name}"? Você verá a tela exatamente como este cliente vê.`)) {
        router.post(`/admin/usuarios/${user.id}/impersonate`);
    }
};
</script>

<template>
    <Head title="Master Admin - Gestão de Usuários & Assinantes" />

    <AdminLayout :admin-user="adminUser">
        <div class="space-y-6 pb-16">
            
            <!-- Page Header -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-extrabold text-white tracking-tight">
                        Gestão de Assinantes & Usuários
                    </h1>
                    <p class="text-xs sm:text-sm text-slate-400 mt-1">
                        Gerencie planos, status de contas e faça suporte via acesso assistido (impersonate).
                    </p>
                </div>
            </div>

            <!-- Filters & Search Toolbar -->
            <div class="glass-panel p-4 rounded-2xl border border-slate-800 flex flex-col sm:flex-row items-center justify-between gap-4">
                <form @submit.prevent="handleSearch" class="relative w-full sm:w-96">
                    <Search class="w-4 h-4 text-slate-400 absolute left-3.5 top-1/2 -translate-y-1/2" />
                    <input 
                        v-model="searchTerm"
                        type="text" 
                        placeholder="Buscar por nome ou e-mail..."
                        class="w-full pl-10 pr-4 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-white placeholder-slate-500 focus:outline-none focus:border-purple-500 transition-colors"
                    />
                </form>

                <div class="flex items-center gap-3 w-full sm:w-auto justify-end">
                    <select 
                        v-model="selectedStatus" 
                        @change="handleSearch"
                        class="px-3 py-2 bg-slate-900 border border-slate-700/80 rounded-xl text-xs text-slate-200 focus:outline-none focus:border-purple-500"
                    >
                        <option value="all">Todos os Status</option>
                        <option value="active">Somente Ativos</option>
                        <option value="inactive">Somente Suspensos</option>
                    </select>
                </div>
            </div>

            <!-- Users Table -->
            <div class="glass-panel rounded-3xl border border-slate-800 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-900/80 text-slate-400 border-b border-slate-800">
                            <tr>
                                <th class="p-4 font-bold">Usuário</th>
                                <th class="p-4 font-bold">Plano Atual</th>
                                <th class="p-4 font-bold">Status</th>
                                <th class="p-4 font-bold">Dados Operacionais</th>
                                <th class="p-4 font-bold">Cadastrado em</th>
                                <th class="p-4 font-bold text-right">Ações Administrativas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-800/60">
                            <tr v-for="user in users.data" :key="user.id" class="hover:bg-slate-900/30 transition-colors">
                                <!-- User Name/Email -->
                                <td class="p-4">
                                    <div class="flex items-center gap-3">
                                        <div class="w-8 h-8 rounded-full bg-slate-800 flex items-center justify-center font-bold text-purple-300">
                                            {{ user.name.charAt(0) }}
                                        </div>
                                        <div>
                                            <div class="font-bold text-white flex items-center gap-2">
                                                <span>{{ user.name }}</span>
                                                <span v-if="user.is_admin" class="px-1.5 py-0.5 rounded text-[9px] font-black uppercase bg-purple-500/20 text-purple-300 border border-purple-500/30">
                                                    Master
                                                </span>
                                            </div>
                                            <div class="text-[11px] text-slate-400">{{ user.email }}</div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Subscription Plan -->
                                <td class="p-4">
                                    <div class="flex items-center gap-2">
                                        <span 
                                            class="px-2.5 py-1 rounded-lg text-xs font-bold"
                                            :class="user.subscription.plan !== 'free' 
                                                ? 'bg-purple-500/15 border border-purple-500/30 text-purple-300' 
                                                : 'bg-slate-800 text-slate-300'"
                                        >
                                            {{ user.subscription.plan_name }}
                                        </span>
                                        <button 
                                            @click="openPlanModal(user)"
                                            title="Alterar Plano"
                                            class="p-1 rounded-lg hover:bg-slate-800 text-slate-400 hover:text-white"
                                        >
                                            <CreditCard class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>

                                <!-- Status Badge -->
                                <td class="p-4">
                                    <span 
                                        class="inline-flex items-center gap-1.5 px-2.5 py-0.5 rounded-full text-[10px] font-bold"
                                        :class="user.is_active ? 'bg-emerald-500/15 text-emerald-400 border border-emerald-500/30' : 'bg-rose-500/15 text-rose-400 border border-rose-500/30'"
                                    >
                                        <span class="w-1.5 h-1.5 rounded-full" :class="user.is_active ? 'bg-emerald-400' : 'bg-rose-400'"></span>
                                        {{ user.is_active ? 'Ativo' : 'Suspenso' }}
                                    </span>
                                </td>

                                <!-- Operational Counts -->
                                <td class="p-4 text-slate-400 text-[11px]">
                                    <div><strong>{{ user.accounts_count }}</strong> conta(s) bancária(s)</div>
                                    <div><strong>{{ user.transactions_count }}</strong> lançamentos</div>
                                </td>

                                <!-- Registration Date -->
                                <td class="p-4 text-slate-400 text-[11px]">
                                    {{ user.created_at }}
                                </td>

                                <!-- Action Buttons -->
                                <td class="p-4 text-right">
                                    <div class="flex items-center justify-end gap-1.5">
                                        <!-- Impersonate Button (Only for regular subscribers, not on admins) -->
                                        <button 
                                            v-if="!user.is_admin"
                                            @click="impersonate(user)"
                                            class="inline-flex items-center gap-1 px-2.5 py-1 rounded-lg bg-slate-900 border border-slate-700/80 hover:border-emerald-500/50 text-slate-300 hover:text-white text-xs font-semibold transition-all"
                                            title="Acessar como o cliente"
                                        >
                                            <LogIn class="w-3.5 h-3.5 text-emerald-400" />
                                            <span>Acessar</span>
                                        </button>

                                        <!-- Toggle Active/Suspended -->
                                        <button 
                                            @click="toggleUserStatus(user)"
                                            class="p-1.5 rounded-lg border text-xs font-semibold transition-colors"
                                            :class="user.is_active 
                                                ? 'bg-rose-500/10 border-rose-500/30 text-rose-400 hover:bg-rose-500/20' 
                                                : 'bg-emerald-500/10 border-emerald-500/30 text-emerald-400 hover:bg-emerald-500/20'"
                                            :title="user.is_active ? 'Suspender usuário' : 'Ativar usuário'"
                                        >
                                            <UserX v-if="user.is_active" class="w-3.5 h-3.5" />
                                            <UserCheck v-else class="w-3.5 h-3.5" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination if multiple pages -->
                <div v-if="users.links?.length > 3" class="p-4 border-t border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-400">Total: {{ users.total }} usuários</span>
                    <div class="flex items-center gap-1">
                        <Link 
                            v-for="(link, idx) in users.links" 
                            :key="idx"
                            :href="link.url || '#'"
                            v-html="link.label"
                            class="px-3 py-1.5 rounded-lg border text-xs font-medium transition-all"
                            :class="link.active 
                                ? 'bg-purple-600 text-white border-purple-500' 
                                : 'bg-slate-900 text-slate-400 border-slate-800 hover:text-white hover:bg-slate-800'"
                        />
                    </div>
                </div>
            </div>

        </div>

        <!-- Edit Plan Modal -->
        <div v-if="editingUser" class="fixed inset-0 z-50 bg-black/80 backdrop-blur-sm flex items-center justify-center p-4">
            <div class="glass-panel w-full max-w-md p-6 rounded-3xl border border-slate-700 bg-slate-900 shadow-2xl relative">
                <button @click="closePlanModal" class="absolute right-4 top-4 text-slate-400 hover:text-white">
                    <X class="w-5 h-5" />
                </button>

                <h3 class="text-lg font-extrabold text-white mb-1">
                    Alterar Plano & Assinatura
                </h3>
                <p class="text-xs text-slate-400 mb-6">
                    Usuário: <strong class="text-purple-300">{{ editingUser.name }}</strong> ({{ editingUser.email }})
                </p>

                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Plano Escolhido</label>
                        <select 
                            v-model="selectedPlan"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                        >
                            <option v-for="p in availablePlans" :key="p.value" :value="p.value">
                                {{ p.label }}
                            </option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1">Status da Assinatura</label>
                        <select 
                            v-model="selectedPlanStatus"
                            class="w-full px-3.5 py-2.5 bg-slate-950 border border-slate-700 rounded-xl text-xs text-white focus:outline-none focus:border-purple-500"
                        >
                            <option value="active">Ativa (Active)</option>
                            <option value="trialing">Período de Teste (Trial)</option>
                            <option value="past_due">Em Atraso (Past Due)</option>
                            <option value="canceled">Cancelada (Canceled)</option>
                        </select>
                    </div>

                    <div class="pt-4 flex items-center justify-end gap-2.5">
                        <button 
                            @click="closePlanModal"
                            type="button" 
                            class="px-4 py-2 rounded-xl text-xs font-semibold text-slate-400 hover:text-white"
                        >
                            Cancelar
                        </button>
                        <button 
                            @click="savePlan"
                            :disabled="isSubmitting"
                            class="px-5 py-2 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-bold text-xs shadow-lg shadow-purple-600/30 transition-all flex items-center gap-1.5"
                        >
                            <Check class="w-3.5 h-3.5" />
                            <span>{{ isSubmitting ? 'Salvando...' : 'Salvar Alteração' }}</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AdminLayout>
</template>
