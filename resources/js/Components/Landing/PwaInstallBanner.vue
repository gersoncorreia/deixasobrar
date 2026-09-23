<script setup>
import { ref, onMounted } from 'vue';
import { Smartphone, Download, X } from 'lucide-vue-next';

const deferredPrompt = ref(null);
const showBanner = ref(false);

onMounted(() => {
    window.addEventListener('beforeinstallprompt', (e) => {
        // Prevent default browser mini-infobar
        e.preventDefault();
        deferredPrompt.value = e;
        
        // Show banner after 3 seconds if not previously dismissed
        if (!localStorage.getItem('pwa_dismissed')) {
            setTimeout(() => {
                showBanner.value = true;
            }, 3000);
        }
    });

    window.addEventListener('appinstalled', () => {
        showBanner.value = false;
        deferredPrompt.value = null;
    });
});

const installApp = async () => {
    if (!deferredPrompt.value) return;
    deferredPrompt.value.prompt();
    const { outcome } = await deferredPrompt.value.userChoice;
    if (outcome === 'accepted') {
        showBanner.value = false;
    }
    deferredPrompt.value = null;
};

const dismiss = () => {
    showBanner.value = false;
    localStorage.setItem('pwa_dismissed', 'true');
};
</script>

<template>
    <transition
        enter-active-class="transition duration-300 ease-out"
        enter-from-class="opacity-0 translate-y-8"
        enter-to-class="opacity-100 translate-y-0"
        leave-active-class="transition duration-200 ease-in"
        leave-from-class="opacity-100 translate-y-0"
        leave-to-class="opacity-0 translate-y-8"
    >
        <div 
            v-if="showBanner"
            class="fixed bottom-4 left-4 right-4 sm:left-auto sm:right-6 sm:w-96 z-50 glass-card-glow rounded-2xl p-4 border border-emerald-500/40 shadow-2xl pb-safe"
        >
            <div class="flex items-start gap-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-500/20 flex items-center justify-center shrink-0">
                    <Smartphone class="w-5 h-5 text-emerald-400" />
                </div>
                <div class="flex-1 pr-2">
                    <h4 class="text-sm font-bold text-white">Instalar DeixaSobrar no Celular</h4>
                    <p class="text-xs text-slate-300 mt-0.5">
                        Acesso com 1 toque na tela inicial. Mais rápido e sem baixar da loja.
                    </p>
                    <div class="flex items-center gap-3 mt-3">
                        <button 
                            @click="installApp"
                            class="px-3.5 py-1.5 rounded-lg bg-emerald-400 text-slate-950 font-bold text-xs flex items-center gap-1.5 shadow"
                        >
                            <Download class="w-3.5 h-3.5" />
                            Instalar Agora
                        </button>
                        <button 
                            @click="dismiss"
                            class="text-xs text-slate-400 hover:text-white font-medium"
                        >
                            Depois
                        </button>
                    </div>
                </div>
                <button 
                    @click="dismiss" 
                    class="text-slate-400 hover:text-white p-1"
                    aria-label="Fechar aviso"
                >
                    <X class="w-4 h-4" />
                </button>
            </div>
        </div>
    </transition>
</template>
