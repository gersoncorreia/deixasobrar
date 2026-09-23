<script setup>
import { ref, onMounted } from 'vue';
import { Download, X, Smartphone, Sparkles } from 'lucide-vue-next';

const deferredPrompt = ref(null);
const showPrompt = ref(false);
const isInstalled = ref(false);

onMounted(() => {
    // Verifica se já está em modo standalone (PWA instalado)
    if (window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true) {
        isInstalled.value = true;
        return;
    }

    // Se o usuário dispensou recentemente (últimas 24h), não exibe
    const dismissedAt = localStorage.getItem('deixasobrar_pwa_dismissed');
    if (dismissedAt && Date.now() - parseInt(dismissedAt, 10) < 24 * 60 * 60 * 1000) {
        return;
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        // Previne o mini-infobar padrão do navegador
        e.preventDefault();
        deferredPrompt.value = e;
        showPrompt.value = true;
    });

    window.addEventListener('appinstalled', () => {
        showPrompt.value = false;
        deferredPrompt.value = null;
        isInstalled.value = true;
    });
});

const installApp = async () => {
    if (!deferredPrompt.value) return;

    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        showPrompt.value = false;
    }
    deferredPrompt.value = null;
};

const dismissPrompt = () => {
    showPrompt.value = false;
    localStorage.setItem('deixasobrar_pwa_dismissed', Date.now().toString());
};
</script>

<template>
    <div 
        v-if="showPrompt && !isInstalled"
        class="mb-6 p-4 rounded-2xl bg-gradient-to-r from-emerald-950/60 via-slate-900/80 to-slate-900/60 border border-emerald-500/30 shadow-xl backdrop-blur-md flex flex-col sm:flex-row items-start sm:items-center justify-between gap-4 transition-all"
    >
        <div class="flex items-center gap-3.5">
            <div class="w-12 h-12 rounded-2xl bg-emerald-500/20 border border-emerald-500/40 text-emerald-400 flex items-center justify-center shrink-0 shadow-lg shadow-emerald-500/10">
                <Smartphone class="w-6 h-6" />
            </div>
            <div>
                <div class="flex items-center gap-2">
                    <h4 class="text-sm font-extrabold text-white">Instalar o DeixaSobrar no Celular</h4>
                    <span class="px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-500/20 text-emerald-300 border border-emerald-500/30">PWA ⚡</span>
                </div>
                <p class="text-xs text-slate-300 mt-0.5">
                    Acesso direto sem abrir o navegador, scanner com 1 toque e velocidade máxima.
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2 w-full sm:w-auto justify-end">
            <button 
                @click="installApp"
                type="button"
                class="flex-1 sm:flex-none inline-flex items-center justify-center gap-2 px-4 py-2 rounded-xl bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 text-slate-950 font-extrabold text-xs shadow-md shadow-emerald-500/20 transition-all cursor-pointer active:scale-95"
            >
                <Download class="w-4 h-4" />
                <span>Instalar Agora</span>
            </button>
            <button 
                @click="dismissPrompt"
                type="button"
                class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800/80 transition-colors"
                title="Dispensar"
            >
                <X class="w-4 h-4" />
            </button>
        </div>
    </div>
</template>
