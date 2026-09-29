<script setup>
import {
    PlusCircle,
    FileUp,
    Camera
} from 'lucide-vue-next';

const emit = defineEmits(['open-transaction', 'open-import']);
</script>

<template>
    <!-- Duas ações principais e diretas para qualquer usuário -->
    <div class="grid grid-cols-2 gap-3">
        <!-- Botão 1: Anotar Gasto -->
        <button
            type="button"
            @click="$emit('open-transaction')"
            class="flex items-center gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-emerald-500/20 to-teal-500/10 hover:from-emerald-500/30 hover:to-teal-500/20 border border-emerald-500/30 active:scale-98 transition-all text-left group shadow-lg shadow-emerald-950/20"
        >
            <div class="w-10 h-10 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                <PlusCircle class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <span class="text-xs font-black text-white block tracking-tight">
                    + Anotar Gasto
                </span>
                <span class="text-[10px] text-emerald-300/80 block truncate">
                    Registrar na hora
                </span>
            </div>
        </button>

        <!-- Botão 2: Subir Extrato -->
        <button
            type="button"
            @click="$emit('open-import')"
            class="flex items-center gap-3 p-3.5 rounded-2xl bg-gradient-to-r from-blue-500/20 to-sky-500/10 hover:from-blue-500/30 hover:to-sky-500/20 border border-blue-500/30 active:scale-98 transition-all text-left group shadow-lg shadow-blue-950/20"
        >
            <div class="w-10 h-10 rounded-xl bg-blue-500/20 text-blue-400 border border-blue-500/30 flex items-center justify-center shrink-0 group-hover:scale-110 transition-transform">
                <FileUp class="w-5 h-5" />
            </div>
            <div class="min-w-0">
                <span class="text-xs font-black text-white block tracking-tight">
                    Subir Extrato
                </span>
                <span class="text-[10px] text-blue-300/80 block truncate">
                    PDF, OFX ou CSV
                </span>
            </div>
        </button>
    </div>
</template>
