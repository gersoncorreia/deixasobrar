<script setup>
import { useCurrencyFormat } from '@/Composables/useCurrencyFormat';
import { Tag, Flame } from 'lucide-vue-next';

defineProps({
    categories: {
        type: Array,
        default: () => [],
    },
});

defineEmits(['select-category']);

const { formatCurrency } = useCurrencyFormat();
</script>

<template>
    <div class="glass-panel rounded-2xl p-6 sm:p-8 border border-slate-800">
        <div class="flex items-center justify-between mb-6">
            <div>
                <h3 class="text-base sm:text-lg font-bold text-white flex items-center gap-2">
                    <Tag class="w-5 h-5 text-amber-400" />
                    <span>Ranking dos Maiores Ralos</span>
                </h3>
                <p class="text-xs text-slate-400 mt-0.5">
                    Categorias com maior concentração de pequenas saídas repetitivas.
                </p>
            </div>
        </div>

        <div v-if="categories.length === 0" class="p-8 text-center text-slate-500 text-xs">
            Nenhum ralo registrado ainda para este período.
        </div>

        <div v-else class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
                v-for="cat in categories" 
                :key="cat.category_id || cat.name"
                @click="$emit('select-category', cat.category_id)"
                class="p-4 rounded-2xl bg-slate-900/60 border border-slate-800/80 hover:border-amber-500/40 cursor-pointer transition-all group flex flex-col justify-between"
            >
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center gap-2.5">
                        <div class="w-8 h-8 rounded-xl bg-amber-500/15 border border-amber-500/25 flex items-center justify-center text-amber-400 group-hover:scale-105 transition-transform">
                            <Flame class="w-4 h-4" />
                        </div>
                        <div>
                            <span class="font-bold text-sm text-white group-hover:text-amber-300 transition-colors">
                                {{ cat.name }}
                            </span>
                            <span class="text-[11px] text-slate-400 block">
                                {{ cat.count }} saídas registradas
                            </span>
                        </div>
                    </div>

                    <div class="text-right">
                        <span class="font-bold text-sm text-white font-display">
                            {{ formatCurrency(cat.total_amount) }}
                        </span>
                        <span class="text-[11px] font-semibold text-amber-400 block font-mono">
                            {{ cat.percentage }}% do ralo
                        </span>
                    </div>
                </div>

                <!-- Horizontal Progress Bar -->
                <div class="w-full bg-slate-800/80 h-2 rounded-full overflow-hidden">
                    <div 
                        class="h-full bg-gradient-to-r from-amber-500 to-rose-500 rounded-full transition-all duration-500"
                        :style="{ width: `${Math.min(100, cat.percentage)}%` }"
                    ></div>
                </div>
            </div>
        </div>
    </div>
</template>
