<script setup>
import { Link } from '@inertiajs/vue3';
import { TrendingUp, ShieldCheck, Lock, Heart } from 'lucide-vue-next';

const currentYear = new Date().getFullYear();
</script>

<template>
    <footer class="bg-slate-950 border-t border-slate-800/80 pt-16 pb-12 relative overflow-hidden">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 relative z-10">
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-5 gap-10 pb-12 border-b border-slate-800/60">
                
                <!-- Col 1 & 2: Brand & Slogan -->
                <div v-reveal="{ delay: 100, direction: 'up' }" class="lg:col-span-2 space-y-4">
                    <Link href="/" class="flex items-center gap-3 group">
                        <div class="w-9 h-9 rounded-xl bg-gradient-to-tr from-emerald-600 to-emerald-400 p-0.5 shadow-md shadow-emerald-500/20 group-hover:scale-110 group-hover:rotate-6 transition-all duration-300">
                            <div class="w-full h-full bg-slate-950 rounded-[10px] flex items-center justify-center">
                                <TrendingUp class="w-5 h-5 text-emerald-400 group-hover:scale-110 transition-transform" />
                            </div>
                        </div>
                        <span class="text-xl font-extrabold text-white tracking-tight">
                            Deixa<span class="text-emerald-400 group-hover:text-emerald-300 transition-colors">Sobrar</span>
                        </span>
                    </Link>

                    <p class="text-xs sm:text-sm text-slate-400 max-w-sm leading-relaxed">
                        <em>"Cuide dos gastos e deixe sobrar para o que importa."</em>
                        <br>
                        A plataforma financeira desenhada para a vida real, sem economês e com previsão de teto diário seguro.
                    </p>

                    <div class="flex items-center gap-4 text-slate-400 text-xs pt-2">
                        <div class="flex items-center gap-1.5 hover:text-emerald-400 transition-colors">
                            <ShieldCheck class="w-4 h-4 text-emerald-400" />
                            <span>Compatível com a LGPD</span>
                        </div>
                        <div class="flex items-center gap-1.5 hover:text-emerald-400 transition-colors">
                            <Lock class="w-4 h-4 text-emerald-400" />
                            <span>Criptografia 256-bit</span>
                        </div>
                    </div>
                </div>

                <!-- Col 3: Navegação -->
                <div v-reveal="{ delay: 200, direction: 'up' }">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-4">Navegação</h4>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-400">
                        <li><a href="#como-funciona" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Como Funciona</a></li>
                        <li><a href="#diferenciais" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Diferenciais</a></li>
                        <li><a href="#simulador" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Simulador de Folga</a></li>
                        <li><a href="#extratos" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Importador Multi-Bancos</a></li>
                        <li><a href="#planos" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Planos & Preços</a></li>
                    </ul>
                </div>

                <!-- Col 4: Suporte e Metodologia -->
                <div v-reveal="{ delay: 300, direction: 'up' }">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-4">Metodologia</h4>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-400">
                        <li><span class="hover:text-emerald-400 cursor-pointer hover:translate-x-1 inline-block transition-all">Dinheiro Livre Real</span></li>
                        <li><span class="hover:text-emerald-400 cursor-pointer hover:translate-x-1 inline-block transition-all">Fórmula do Teto Diário</span></li>
                        <li><span class="hover:text-emerald-400 cursor-pointer hover:translate-x-1 inline-block transition-all">Raio-X de Micro-Pix</span></li>
                        <li><span class="hover:text-emerald-400 cursor-pointer hover:translate-x-1 inline-block transition-all">Gerenciador Adeus Carnês</span></li>
                        <li><a href="#faq" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Perguntas Frequentes</a></li>
                    </ul>
                </div>

                <!-- Col 5: Conta & Segurança -->
                <div v-reveal="{ delay: 400, direction: 'up' }">
                    <h4 class="text-xs font-bold uppercase tracking-wider text-slate-300 mb-4">Acesso</h4>
                    <ul class="space-y-2.5 text-xs sm:text-sm text-slate-400">
                        <li><Link href="/login" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Entrar na Conta</Link></li>
                        <li><Link href="/register" class="hover:text-emerald-400 hover:translate-x-1 inline-block transition-all">Cadastrar Grátis</Link></li>
                        <li><span class="text-slate-500 hover:text-slate-400 cursor-pointer transition-colors">Termos de Serviço</span></li>
                        <li><span class="text-slate-500 hover:text-slate-400 cursor-pointer transition-colors">Política de Privacidade</span></li>
                    </ul>
                </div>

            </div>

            <!-- Bottom Copyright & Safe Area -->
            <div v-reveal="{ delay: 500, direction: 'up' }" class="pt-8 flex flex-col sm:flex-row items-center justify-between text-xs text-slate-500 gap-4 pb-safe">
                <p>© {{ currentYear }} DeixaSobrar. Todos os direitos reservados.</p>
                <p class="flex items-center gap-1.5">
                    Feito com <Heart class="w-3.5 h-3.5 text-rose-500 fill-rose-500 animate-bounce" style="animation-duration: 1.5s;" /> para você cuidar do seu dinheiro.
                </p>
            </div>

        </div>
    </footer>
</template>
