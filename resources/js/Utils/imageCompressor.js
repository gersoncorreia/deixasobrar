/**
 * Utilitário de compressão e otimização de imagens no cliente via Canvas.
 * Redimensiona e comprime imagens de câmeras de alta resolução (12MP-48MP)
 * para dimensões ideais de OCR (máximo 1600px) e qualidade JPEG 0.82.
 * Reduz arquivos de 5MB-15MB para ~200KB-400KB em milissegundos.
 */

export async function compressImageFile(file, maxDimension = 1600, quality = 0.82) {
    if (!file || !file.type.startsWith('image/')) {
        return file; // Retorna o arquivo intacto caso seja PDF ou outro formato
    }

    return new Promise((resolve) => {
        const reader = new FileReader();
        reader.onload = (e) => {
            const img = new Image();
            img.onload = () => {
                let width = img.width;
                let height = img.height;

                // Se já estiver dentro do tamanho ideal, comprime apenas a qualidade se for grande
                if (width > maxDimension || height > maxDimension) {
                    if (width > height) {
                        height = Math.round((height * maxDimension) / width);
                        width = maxDimension;
                    } else {
                        width = Math.round((width * maxDimension) / height);
                        height = maxDimension;
                    }
                }

                const canvas = document.createElement('canvas');
                canvas.width = width;
                canvas.height = height;

                const ctx = canvas.getContext('2d');
                // Suavização bilinear para preservar nitidez de texto pequeno de comprovantes
                ctx.imageSmoothingEnabled = true;
                ctx.imageSmoothingQuality = 'high';
                ctx.drawImage(img, 0, 0, width, height);

                canvas.toBlob(
                    (blob) => {
                        if (!blob) {
                            resolve(file);
                            return;
                        }
                        const optimizedFile = new File([blob], file.name.replace(/\.[^.]+$/, '.jpg'), {
                            type: 'image/jpeg',
                            lastModified: Date.now(),
                        });
                        resolve(optimizedFile);
                    },
                    'image/jpeg',
                    quality
                );
            };

            img.onerror = () => resolve(file);
            img.src = e.target.result;
        };

        reader.onerror = () => resolve(file);
        reader.readAsDataURL(file);
    });
}

export function compressCanvasToJpeg(canvas, quality = 0.82) {
    return canvas.toDataURL('image/jpeg', quality);
}
