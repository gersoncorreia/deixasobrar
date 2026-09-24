<script setup>
import { Link } from '@inertiajs/vue3';
import {
    FileUp,
    Camera,
    PieChart,
    Flame,
    Building2,
    ShieldCheck,
    Sparkles,
    MoreHorizontal
} from 'lucide-vue-next';
import { useMobileMenu } from '@/Composables/useMobileMenu';

const emit = defineEmits(['open-menu', 'open-fixed-bills', 'open-simulator']);
const { openMobileMenu } = useMobileMenu();

const handleAction = (item) => {
    if (item.action === 'menu') {
        openMobileMenu();
        emit('open-menu');
    } else if (item.action === 'fixed-bills') {
        emit('open-fixed-bills');
    } else if (item.action === 'simulator') {
        emit('open-simulator');
    }
};

const actions = [
    { label: 'Extratos', href: '/extratos', icon: FileUp, color: 'text-emerald-400', bg: 'bg-emerald-500/10 border-emerald-500/20' },
    { label: 'Scanner', href: '/scanner', icon: Camera, color: 'text-teal-400', bg: 'bg-teal-500/10 border-teal-500/20' },
    { label: 'Análises', href: '/analises', icon: PieChart, color: 'text-indigo-400', bg: 'bg-indigo-500/10 border-indigo-500/20' },
    { label: 'Raio-X', href: '/vazamentos', icon: Flame, color: 'text-rose-400', bg: 'bg-rose-500/10 border-rose-500/20' },
    { label: 'Contas', href: '/contas', icon: Building2, color: 'text-sky-400', bg: 'bg-sky-500/10 border-sky-500/20' },
    { label: 'Blindar', action: 'fixed-bills', icon: ShieldCheck, color: 'text-amber-400', bg: 'bg-amber-500/10 border-amber-500/20' },
    { label: 'Simulador', action: 'simulator', icon: Sparkles, color: 'text-purple-400', bg: 'bg-purple-500/10 border-purple-500/20' },
    { label: 'Ver mais', action: 'menu', icon: MoreHorizontal, color: 'text-slate-300', bg: 'bg-slate-800/80 border-slate-700/80' },
];
</script>

<template>
    <div class="rounded-2xl bg-slate-900/70 border border-slate-800/80 p-4 shadow-xl backdrop-blur-md">
        <div class="grid grid-cols-4 gap-2.5 sm:gap-3">
            <template v-for="(item, idx) in actions" :key="idx">
                <!-- If link -->
                <Link
                    v-if="item.href"
                    :href="item.href"
                    class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-slate-950/40 hover:bg-slate-800/60 border border-slate-800/60 active:scale-95 transition-all text-center group"
                >
                    <div 
                        class="w-11 h-11 rounded-xl flex items-center justify-center mb-1.5 border shadow-sm transition-transform group-hover:scale-105"
                        :class="item.bg"
                    >
                        <component :is="item.icon" class="w-5 h-5" :class="item.color" />
                    </div>
                    <span class="text-[11px] font-semibold text-slate-200 group-hover:text-white leading-tight">
                        {{ item.label }}
                    </span>
                </Link>

                <!-- If interactive action -->
                <button
                    v-else
                    type="button"
                    @click="handleAction(item)"
                    class="flex flex-col items-center justify-center p-2.5 rounded-xl bg-slate-950/40 hover:bg-slate-800/60 border border-slate-800/60 active:scale-95 transition-all text-center group"
                >
                    <div 
                        class="w-11 h-11 rounded-xl flex items-center justify-center mb-1.5 border shadow-sm transition-transform group-hover:scale-105"
                        :class="item.bg"
                    >
                        <component :is="item.icon" class="w-5 h-5" :class="item.color" />
                    </div>
                    <span class="text-[11px] font-semibold text-slate-200 group-hover:text-white leading-tight">
                        {{ item.label }}
                    </span>
                </button>
            </template>
        </div>
    </div>
</template>
