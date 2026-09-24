<script setup>
import { ref, onBeforeUnmount } from 'vue';
import { 
    Camera, 
    UploadCloud, 
    X, 
    Loader2, 
    ScanLine, 
    Sparkles, 
    AlertCircle, 
    SwitchCamera,
    Flashlight
} from 'lucide-vue-next';
import { compressImageFile } from '@/Utils/imageCompressor';

const props = defineProps({
    isOpen: Boolean,
});

const emit = defineEmits(['close', 'captured']);

const activeMode = ref('camera'); // 'camera' | 'upload'
const videoRef = ref(null);
const canvasRef = ref(null);
const fileInput = ref(null);
const isStreamActive = ref(false);
const streamError = ref(null);
const isProcessing = ref(false);
const facingMode = ref('environment'); // environment (traseira) | user (frontal)
const quotaError = ref(null);
const isTorchSupported = ref(false);
const isTorchOn = ref(false);
let mediaStream = null;

const startCamera = async () => {
    streamError.value = null;
    isStreamActive.value = false;
    isTorchSupported.value = false;
    isTorchOn.value = false;

    if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
        streamError.value = 'Seu navegador não suporta acesso nativo à câmera. Utilize o modo de upload.';
        activeMode.value = 'upload';
        return;
    }

    try {
        if (mediaStream) {
            stopCamera();
        }

        // Tenta iniciar com foco contínuo ideal para leitura de pequenos textos
        const constraints = {
            video: {
                facingMode: facingMode.value,
                width: { ideal: 1920 },
                height: { ideal: 1080 },
                focusMode: { ideal: 'continuous' },
            },
            audio: false,
        };

        mediaStream = await navigator.mediaDevices.getUserMedia(constraints);

        if (videoRef.value) {
            videoRef.value.srcObject = mediaStream;
            videoRef.value.play();
            isStreamActive.value = true;
        }

        // Verifica suporte a Lanterna (Torch) no dispositivo
        const track = mediaStream.getVideoTracks()[0];
        if (track && typeof track.getCapabilities === 'function') {
            const capabilities = track.getCapabilities();
            if (capabilities.torch) {
                isTorchSupported.value = true;
            }
        }
    } catch (err) {
        console.error('Camera access error:', err);
        streamError.value = 'Permissão para usar a câmera foi negada ou não encontrada. Você pode enviar a foto pela galeria.';
        activeMode.value = 'upload';
    }
};

const stopCamera = () => {
    if (mediaStream) {
        mediaStream.getTracks().forEach((track) => track.stop());
        mediaStream = null;
    }
    isStreamActive.value = false;
    isTorchOn.value = false;
    isTorchSupported.value = false;
};

const toggleTorch = async () => {
    if (!mediaStream) return;
    const track = mediaStream.getVideoTracks()[0];
    if (track) {
        try {
            isTorchOn.value = !isTorchOn.value;
            await track.applyConstraints({
                advanced: [{ torch: isTorchOn.value }],
            });
        } catch (err) {
            console.warn('Erro ao alternar lanterna:', err);
            isTorchOn.value = false;
        }
    }
};

const toggleFacingMode = () => {
    facingMode.value = facingMode.value === 'environment' ? 'user' : 'environment';
    startCamera();
};

const switchMode = (mode) => {
    activeMode.value = mode;
    if (mode === 'camera') {
        startCamera();
    } else {
        stopCamera();
    }
};

const captureFromVideo = () => {
    if (!videoRef.value || !isStreamActive.value) return;

    const video = videoRef.value;
    const canvas = document.createElement('canvas');
    let width = video.videoWidth || 1280;
    let height = video.videoHeight || 720;

    // Redimensionamento inteligente no cliente para max 1600px
    const maxDim = 1600;
    if (width > maxDim || height > maxDim) {
        if (width > height) {
            height = Math.round((height * maxDim) / width);
            width = maxDim;
        } else {
            width = Math.round((width * maxDim) / height);
            height = maxDim;
        }
    }

    canvas.width = width;
    canvas.height = height;

    const ctx = canvas.getContext('2d');
    ctx.imageSmoothingEnabled = true;
    ctx.imageSmoothingQuality = 'high';
    ctx.drawImage(video, 0, 0, width, height);

    const base64Data = canvas.toDataURL('image/jpeg', 0.82);
    stopCamera();
    uploadPayload({ camera_data: base64Data, scan_type: 'paper_ocr' });
};

const handleFileSelect = async (e) => {
    const file = e.target.files?.[0];
    if (file) {
        // Comprime no cliente imagens pesadas da galeria (12MB -> 300KB)
        const optimizedFile = await compressImageFile(file, 1600, 0.82);
        uploadPayload({ image: optimizedFile, scan_type: 'paper_ocr' });
    }
};

const uploadPayload = async (payload) => {
    isProcessing.value = true;
    quotaError.value = null;
    try {
        const formData = new FormData();
        if (payload.image) {
            formData.append('image', payload.image);
        }
        if (payload.camera_data) {
            formData.append('camera_data', payload.camera_data);
        }
        formData.append('scan_type', payload.scan_type || 'paper_ocr');

        const response = await window.axios.post('/scanner/capture', formData, {
            headers: { 'Content-Type': 'multipart/form-data' },
        });

        if (response.data?.success) {
            emit('captured', response.data);
            handleClose();
        }
    } catch (err) {
        if (err.response?.status === 403 && err.response?.data?.quota_exceeded) {
            quotaError.value = err.response.data.message;
        } else {
            alert(err.response?.data?.message || 'Falha ao processar a leitura do cupom.');
        }
    } finally {
        isProcessing.value = false;
    }
};

const handleClose = () => {
    stopCamera();
    emit('close');
};

onBeforeUnmount(() => {
    stopCamera();
});
</script>

<template>
    <div v-if="isOpen" class="fixed inset-0 z-50 bg-black/85 backdrop-blur-md flex items-center justify-center p-3 sm:p-4">
        <div class="glass-panel w-full max-w-lg rounded-2xl border border-slate-700 bg-slate-900 shadow-2xl relative overflow-hidden flex flex-col max-h-[92vh]">
            
            <!-- Top Modal Bar -->
            <div class="p-4 sm:p-5 border-b border-slate-800 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <div class="w-8 h-8 rounded-xl bg-emerald-500/20 text-emerald-400 border border-emerald-500/30 flex items-center justify-center">
                        <ScanLine class="w-4 h-4" />
                    </div>
                    <div>
                        <h3 class="text-sm font-extrabold text-white">Scanner Nativo OCR</h3>
                        <p class="text-[10px] text-slate-400">Leitura direta via câmera ou galeria</p>
                    </div>
                </div>

                <button @click="handleClose" class="p-2 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition-colors">
                    <X class="w-4 h-4" />
                </button>
            </div>

            <!-- Mode Selector Switch -->
            <div class="p-3 bg-slate-950/60 border-b border-slate-800 flex items-center justify-center gap-2">
                <button 
                    @click="switchMode('camera')"
                    class="flex items-center gap-2 px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                    :class="activeMode === 'camera' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'text-slate-400 hover:text-white'"
                >
                    <Camera class="w-3.5 h-3.5" />
                    <span>Câmera ao Vivo</span>
                </button>

                <button 
                    @click="switchMode('upload')"
                    class="flex items-center gap-2 px-4 py-1.5 rounded-xl text-xs font-bold transition-all"
                    :class="activeMode === 'upload' ? 'bg-emerald-500/20 text-emerald-300 border border-emerald-500/40' : 'text-slate-400 hover:text-white'"
                >
                    <UploadCloud class="w-3.5 h-3.5" />
                    <span>Galeria / Arquivo</span>
                </button>
            </div>

            <!-- Quota Exceeded Alert Banner -->
            <div v-if="quotaError" class="p-4 mx-4 mt-3 rounded-2xl bg-purple-950/40 border border-purple-500/40 text-purple-200 text-xs flex items-start gap-3">
                <Sparkles class="w-5 h-5 text-purple-400 shrink-0 mt-0.5" />
                <div class="flex-1 space-y-2">
                    <p class="font-bold text-white leading-relaxed">{{ quotaError }}</p>
                    <a 
                        href="/assinatura" 
                        class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-purple-600 hover:bg-purple-500 text-white font-extrabold text-xs transition-colors shadow-md shadow-purple-600/30"
                    >
                        <span>Fazer Upgrade para o Plano Pro ⚡</span>
                    </a>
                </div>
            </div>

            <!-- Processing Overlay -->
            <div v-if="isProcessing" class="absolute inset-0 z-30 bg-slate-950/90 flex flex-col items-center justify-center p-6 text-center">
                <div class="w-16 h-16 rounded-full bg-emerald-500/20 border border-emerald-500/40 flex items-center justify-center mb-4">
                    <Loader2 class="w-8 h-8 text-emerald-400 animate-spin" />
                </div>
                <h4 class="text-base font-extrabold text-white">Lendo Cupom Fiscal & OCR</h4>
                <p class="text-xs text-slate-400 mt-1 max-w-xs">
                    Identificando estabelecimento, valor total, produtos do carrinho e forma de pagamento...
                </p>
            </div>

            <!-- Viewport Area -->
            <div class="p-4 flex-1 flex flex-col items-center justify-center min-h-[300px] relative bg-slate-950">
                <!-- CAMERA MODE -->
                <div v-if="activeMode === 'camera'" class="w-full h-full flex flex-col items-center justify-center relative">
                    <div class="w-full max-w-sm aspect-[3/4] bg-slate-900 rounded-2xl overflow-hidden relative border border-slate-800 flex items-center justify-center">
                        <video 
                            ref="videoRef" 
                            autoplay 
                            playsinline 
                            muted
                            class="w-full h-full object-cover"
                        ></video>

                        <!-- Scanner Laser Overlay -->
                        <div class="absolute inset-4 border-2 border-emerald-500/50 rounded-xl pointer-events-none flex flex-col justify-between p-2">
                            <div class="flex justify-between">
                                <span class="w-4 h-4 border-t-2 border-l-2 border-emerald-400"></span>
                                <span class="w-4 h-4 border-t-2 border-r-2 border-emerald-400"></span>
                            </div>
                            
                            <!-- Animated Laser Beam -->
                            <div class="w-full h-0.5 bg-gradient-to-r from-transparent via-emerald-400 to-transparent shadow-lg shadow-emerald-500 animate-pulse"></div>

                            <div class="flex justify-between">
                                <span class="w-4 h-4 border-b-2 border-l-2 border-emerald-400"></span>
                                <span class="w-4 h-4 border-b-2 border-r-2 border-emerald-400"></span>
                            </div>
                        </div>

                        <!-- Camera Controls Overlay (Top Right) -->
                        <div class="absolute top-3 right-3 flex items-center gap-2">
                            <!-- Torch Button -->
                            <button 
                                v-if="isTorchSupported"
                                @click="toggleTorch" 
                                type="button" 
                                class="p-2 rounded-xl backdrop-blur-md border transition-colors"
                                :class="isTorchOn ? 'bg-amber-500 text-slate-950 border-amber-400 font-bold shadow-lg shadow-amber-500/30' : 'bg-slate-900/80 text-slate-300 hover:text-white border-slate-700/80'"
                                :title="isTorchOn ? 'Desligar Lanterna' : 'Ligar Lanterna'"
                            >
                                <Flashlight class="w-4 h-4" />
                            </button>

                            <!-- Switch Camera Flip Button -->
                            <button 
                                @click="toggleFacingMode" 
                                type="button" 
                                class="p-2 rounded-xl bg-slate-900/80 backdrop-blur-md text-slate-300 hover:text-white border border-slate-700/80 transition-colors"
                                title="Alternar Câmera"
                            >
                                <SwitchCamera class="w-4 h-4" />
                            </button>
                        </div>
                    </div>

                    <!-- Trigger Capture Button -->
                    <div class="mt-4 flex items-center justify-center">
                        <button 
                            @click="captureFromVideo"
                            type="button"
                            class="w-16 h-16 rounded-full border-4 border-emerald-500 bg-emerald-500/20 hover:bg-emerald-500/40 p-1 flex items-center justify-center shadow-lg shadow-emerald-500/30 transition-all hover:scale-105 active:scale-95 cursor-pointer"
                            title="Tirar Foto"
                        >
                            <div class="w-11 h-11 rounded-full bg-emerald-400"></div>
                        </button>
                    </div>
                </div>

                <!-- UPLOAD MODE -->
                <div v-else class="w-full h-full flex flex-col items-center justify-center p-6 text-center">
                    <input 
                        type="file" 
                        ref="fileInput"
                        accept="image/*,.pdf" 
                        class="hidden" 
                        @change="handleFileSelect"
                    />

                    <div 
                        @click="fileInput?.click()"
                        class="w-full max-w-sm p-8 border-2 border-dashed border-slate-700 hover:border-emerald-500 rounded-2xl bg-slate-900/40 flex flex-col items-center justify-center cursor-pointer transition-all"
                    >
                        <div class="w-14 h-14 rounded-2xl bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 flex items-center justify-center mb-3">
                            <UploadCloud class="w-7 h-7" />
                        </div>
                        <h4 class="text-sm font-bold text-white">Selecione uma Imagem ou PDF</h4>
                        <p class="text-xs text-slate-400 mt-1">
                            Foto da galeria, print de Pix ou cupom fiscal digitalizado
                        </p>
                    </div>
                </div>
            </div>

            <!-- Modal Footer Note -->
            <div class="p-3 bg-slate-950 border-t border-slate-800 text-center">
                <p class="text-[11px] text-slate-400">
                    🔒 Processamento 100% nativo. O comprovante será confrontado com seus extratos bancários para evitar duplicações.
                </p>
            </div>

        </div>
    </div>
</template>
