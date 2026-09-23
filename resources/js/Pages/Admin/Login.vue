<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { ShieldAlert, Lock, Mail, Loader2, ArrowRight } from 'lucide-vue-next';

const form = useForm({
    email: '',
    password: '',
    remember: false,
});

const submit = () => {
    form.post('/admin/login', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Master Control - Acesso Restrito" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans selection:bg-purple-500 selection:text-white px-4">
        
        <!-- Background Glow FX -->
        <div class="fixed inset-0 pointer-events-none overflow-hidden">
            <div class="absolute -top-40 left-1/2 -translate-x-1/2 w-[600px] h-[600px] bg-purple-600/10 rounded-full blur-[140px]"></div>
            <div class="absolute -bottom-40 left-1/2 -translate-x-1/2 w-[500px] h-[500px] bg-indigo-600/10 rounded-full blur-[140px]"></div>
        </div>

        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center relative z-10">
            <!-- Master Admin Shield Badge -->
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-gradient-to-tr from-purple-700 to-indigo-600 p-0.5 shadow-xl shadow-purple-600/30 mb-6">
                <div class="w-full h-full bg-slate-950 rounded-[14px] flex items-center justify-center">
                    <ShieldAlert class="w-8 h-8 text-purple-400" />
                </div>
            </div>

            <h1 class="text-2xl font-black text-white tracking-tight">
                Deixa<span class="text-emerald-400">Sobrar</span>
            </h1>
            <div class="inline-block mt-1 px-3 py-1 rounded-full bg-purple-500/15 border border-purple-500/30 text-purple-300 text-[10px] font-black uppercase tracking-widest">
                Painel Master Admin • Acesso Restrito
            </div>
            <p class="text-xs text-slate-400 mt-2">
                Área reservada para a governança e administração geral da plataforma.
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md relative z-10">
            <div class="glass-panel py-8 px-6 shadow-2xl rounded-3xl sm:px-10 border border-slate-800/90 bg-slate-900/90 backdrop-blur-xl">
                
                <!-- Security Warning Banner -->
                <div class="mb-6 p-3 rounded-xl bg-purple-950/40 border border-purple-800/50 text-[11px] text-purple-300 leading-relaxed flex items-center gap-2">
                    <span class="w-1.5 h-1.5 rounded-full bg-purple-400 animate-pulse"></span>
                    <span>Conexão criptografada de alta segurança. Todas as tentativas de autenticação são monitoradas por IP.</span>
                </div>

                <form @submit.prevent="submit" class="space-y-5">
                    <!-- Email Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">E-mail do Administrador</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Mail class="w-4 h-4" />
                            </div>
                            <input 
                                type="email" 
                                v-model="form.email" 
                                required 
                                autofocus
                                placeholder="admin@deixasobrar.com.br"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-purple-500 transition-colors"
                            />
                        </div>
                        <p v-if="form.errors.email" class="mt-1.5 text-xs text-rose-400 font-semibold">
                            {{ form.errors.email }}
                        </p>
                    </div>

                    <!-- Password Input -->
                    <div>
                        <label class="block text-xs font-bold text-slate-300 mb-1.5">Chave / Senha Master</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input 
                                type="password" 
                                v-model="form.password" 
                                required 
                                placeholder="••••••••••••"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-950 border border-slate-800 rounded-xl text-sm text-white placeholder-slate-600 focus:outline-none focus:border-purple-500 transition-colors"
                            />
                        </div>
                        <p v-if="form.errors.password" class="mt-1.5 text-xs text-rose-400 font-semibold">
                            {{ form.errors.password }}
                        </p>
                    </div>

                    <!-- Remember Me Option -->
                    <div class="flex items-center justify-between">
                        <label class="flex items-center text-xs text-slate-400 cursor-pointer">
                            <input 
                                type="checkbox" 
                                v-model="form.remember" 
                                class="rounded bg-slate-950 border-slate-800 text-purple-600 focus:ring-purple-500 w-4 h-4"
                            />
                            <span class="ml-2 font-medium">Manter sessão segura</span>
                        </label>
                    </div>

                    <!-- Submit Button -->
                    <div>
                        <button 
                            type="submit" 
                            :disabled="form.processing"
                            class="w-full flex items-center justify-center gap-2 py-3 px-4 rounded-xl text-sm font-bold text-white bg-gradient-to-r from-purple-600 to-indigo-600 hover:from-purple-500 hover:to-indigo-500 shadow-lg shadow-purple-600/30 transition-all hover:-translate-y-0.5 active:translate-y-0 disabled:opacity-60 cursor-pointer"
                        >
                            <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                            <span v-else>Autenticar no Master Control</span>
                            <ArrowRight v-if="!form.processing" class="w-4 h-4" />
                        </button>
                    </div>
                </form>
            </div>
            
            <div class="mt-6 text-center">
                <a href="/" class="text-xs text-slate-500 hover:text-slate-400 transition-colors">
                    ← Voltar para a página inicial pública
                </a>
            </div>
        </div>

    </div>
</template>
