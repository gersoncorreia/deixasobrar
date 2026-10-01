<script setup>
import { computed } from 'vue';
import { 
    AlertCircle, 
    CheckCircle2, 
    Info, 
    AlertTriangle, 
    X 
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: Boolean,
    title: {
        type: String,
        default: 'Atenção',
    },
    message: {
        type: String,
        default: '',
    },
    type: {
        type: String,
        default: 'error', // 'error' | 'success' | 'warning' | 'info'
    },
    confirmText: {
        type: String,
        default: 'Entendido',
    },
    showCancel: {
        type: Boolean,
        default: false,
    },
    cancelText: {
        type: String,
        default: 'Cancelar',
    },
});

const emit = defineEmits(['close', 'confirm', 'cancel']);

const config = computed(() => {
    switch (props.type) {
        case 'success':
            return {
                icon: CheckCircle2,
                iconColor: 'text-emerald-400',
                badgeBg: 'bg-emerald-500/20 text-emerald-300 border-emerald-500/30',
                btnClass: 'bg-gradient-to-r from-emerald-500 to-teal-400 text-slate-950 shadow-emerald-500/25',
                borderColor: 'border-emerald-500/30',
            };
        case 'warning':
            return {
                icon: AlertTriangle,
                iconColor: 'text-amber-400',
                badgeBg: 'bg-amber-500/20 text-amber-300 border-amber-500/30',
                btnClass: 'bg-gradient-to-r from-amber-500 to-orange-400 text-slate-950 shadow-amber-500/25',
                borderColor: 'border-amber-500/30',
            };
        case 'info':
            return {
                icon: Info,
                iconColor: 'text-blue-400',
                badgeBg: 'bg-blue-500/20 text-blue-300 border-blue-500/30',
                btnClass: 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-blue-500/25',
                borderColor: 'border-blue-500/30',
            };
        default: // error
            return {
                icon: AlertCircle,
                iconColor: 'text-rose-400',
                badgeBg: 'bg-rose-500/20 text-rose-300 border-rose-500/30',
                btnClass: 'bg-gradient-to-r from-rose-500 to-red-600 text-white shadow-rose-500/25',
                borderColor: 'border-rose-500/30',
            };
    }
});

const handleClose = () => {
    emit('close');
};

const handleCancel = () => {
    emit('cancel');
    emit('close');
};

const handleConfirm = () => {
    emit('confirm');
    emit('close');
};
</script>

<template>
    <Teleport to="body">
        <div 
            v-if="isOpen" 
            class="fixed inset-0 z-[100] bg-black/85 backdrop-blur-md flex items-center justify-center p-4 animate-in fade-in duration-200"
            @click.self="handleClose"
        >
            <div 
                class="glass-panel w-full max-w-sm rounded-3xl border bg-slate-900/95 shadow-2xl p-6 text-center space-y-4 relative overflow-hidden transition-all scale-100"
                :class="config.borderColor"
            >
                <!-- Close Icon Button -->
                <button 
                    @click="handleClose"
                    class="absolute top-4 right-4 p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors"
                >
                    <X class="w-4 h-4" />
                </button>

                <!-- Icon Badge -->
                <div 
                    class="w-14 h-14 rounded-2xl mx-auto flex items-center justify-center border shadow-lg"
                    :class="[config.badgeBg]"
                >
                    <component :is="config.icon" class="w-7 h-7" :class="config.iconColor" />
                </div>

                <!-- Text Content -->
                <div class="space-y-1.5">
                    <h3 class="text-base font-extrabold text-white tracking-tight">
                        {{ title }}
                    </h3>
                    <p class="text-xs text-slate-300 leading-relaxed max-w-xs mx-auto">
                        {{ message }}
                    </p>
                </div>

                <!-- Action Button(s) -->
                <div class="pt-2 flex items-center gap-3">
                    <button 
                        v-if="showCancel"
                        type="button"
                        @click="handleCancel"
                        class="flex-1 py-3 px-4 rounded-2xl font-bold text-xs bg-slate-800 hover:bg-slate-700 text-slate-300 border border-slate-700 transition-all active:scale-[0.98] cursor-pointer"
                    >
                        {{ cancelText }}
                    </button>
                    <button 
                        type="button"
                        @click="handleConfirm"
                        class="flex-1 py-3 px-4 rounded-2xl font-bold text-xs shadow-lg transition-all active:scale-[0.98] cursor-pointer"
                        :class="config.btnClass"
                    >
                        {{ confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
