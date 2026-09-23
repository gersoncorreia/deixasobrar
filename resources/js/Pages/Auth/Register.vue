<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';
import { TrendingUp, ArrowRight, Lock, Mail, User, Loader2, Sparkles } from 'lucide-vue-next';

const props = defineProps({
    plan: {
        type: String,
        default: 'free',
    },
});

const form = useForm({
    name: '',
    email: '',
    password: '',
    plan: props.plan,
});

const submit = () => {
    form.post('/register', {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <Head title="Criar Conta Gratuita" />

    <div class="min-h-screen bg-slate-950 text-slate-100 flex flex-col justify-center py-12 sm:px-6 lg:px-8 font-sans selection:bg-emerald-500 selection:text-slate-950 px-4">
        <div class="sm:mx-auto sm:w-full sm:max-w-md text-center">
            <!-- Brand -->
            <Link href="/" class="inline-flex items-center gap-3 mb-6">
                <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 p-0.5">
                    <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                        <TrendingUp class="w-5 h-5 text-emerald-400" />
                    </div>
                </div>
                <span class="text-2xl font-extrabold text-white tracking-tight">
                    Deixa<span class="text-emerald-400">Sobrar</span>
                </span>
            </Link>

            <h2 class="text-xl sm:text-2xl font-extrabold text-white">
                Crie sua conta em 1 minuto
            </h2>
            <p class="text-xs sm:text-sm text-slate-400 mt-1">
                Comece grátis, sem cartão de crédito necessário
            </p>
        </div>

        <div class="mt-8 sm:mx-auto sm:w-full sm:max-w-md">
            <div class="glass-panel py-8 px-6 shadow-2xl rounded-3xl sm:px-10 border border-slate-800">
                <form @submit.prevent="submit" class="space-y-4">
                    <!-- Nome -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Seu Nome Completo</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <User class="w-4 h-4" />
                            </div>
                            <input 
                                type="text" 
                                v-model="form.name" 
                                required 
                                placeholder="João Silva"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>
                        <span v-if="form.errors.name" class="text-xs text-rose-400 mt-1 block">
                            {{ form.errors.name }}
                        </span>
                    </div>

                    <!-- Email -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Seu Melhor E-mail</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Mail class="w-4 h-4" />
                            </div>
                            <input 
                                type="email" 
                                v-model="form.email" 
                                required 
                                placeholder="joao@gmail.com"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>
                        <span v-if="form.errors.email" class="text-xs text-rose-400 mt-1 block">
                            {{ form.errors.email }}
                        </span>
                    </div>

                    <!-- Password -->
                    <div>
                        <label class="block text-xs font-semibold text-slate-300 mb-1.5">Crie uma Senha Segura</label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-500">
                                <Lock class="w-4 h-4" />
                            </div>
                            <input 
                                type="password" 
                                v-model="form.password" 
                                required 
                                placeholder="Mínimo 6 caracteres"
                                class="w-full pl-10 pr-4 py-2.5 bg-slate-900 border border-slate-700 rounded-xl text-sm text-white focus:outline-none focus:border-emerald-400"
                            />
                        </div>
                        <span v-if="form.errors.password" class="text-xs text-rose-400 mt-1 block">
                            {{ form.errors.password }}
                        </span>
                    </div>

                    <!-- Submit -->
                    <button 
                        type="submit" 
                        :disabled="form.processing"
                        class="w-full py-3 px-4 rounded-xl bg-emerald-400 hover:bg-emerald-300 text-slate-950 font-bold text-sm shadow-lg shadow-emerald-500/20 transition-all flex items-center justify-center gap-2 mt-4"
                    >
                        <Loader2 v-if="form.processing" class="w-4 h-4 animate-spin" />
                        <span>Criar Minha Conta Grátis</span>
                        <ArrowRight v-if="!form.processing" class="w-4 h-4" />
                    </button>
                </form>

                <div class="mt-6 text-center text-xs text-slate-400">
                    Já tem cadastro?
                    <Link href="/login" class="text-emerald-400 font-semibold hover:underline ml-1">
                        Entrar aqui
                    </Link>
                </div>
            </div>
        </div>
    </div>
</template>
