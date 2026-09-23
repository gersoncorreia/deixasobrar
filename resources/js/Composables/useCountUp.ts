import { ref, watch } from 'vue';

export function useCountUp() {
    /**
     * Animates a reactive ref from an initial value to a target value using easeOutCubic
     */
    const animateNumber = (
        currentRef: any,
        targetValue: number,
        duration: number = 1000
    ) => {
        const start = Number(currentRef.value) || 0;
        const end = Number(targetValue);
        const startTime = performance.now();

        if (start === end) return;

        const updateNumber = (currentTime: number) => {
            const elapsed = currentTime - startTime;
            const progress = Math.min(elapsed / duration, 1);

            // easeOutCubic: 1 - Math.pow(1 - progress, 3)
            const easeProgress = 1 - Math.pow(1 - progress, 3);
            const current = start + (end - start) * easeProgress;

            currentRef.value = current;

            if (progress < 1) {
                requestAnimationFrame(updateNumber);
            } else {
                currentRef.value = end;
            }
        };

        requestAnimationFrame(updateNumber);
    };

    return {
        animateNumber,
    };
}
