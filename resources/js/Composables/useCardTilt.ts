import { ref } from 'vue';

export function useCardTilt(maxRotation: number = 10) {
    const tiltStyle = ref({
        transform: 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)',
        transition: 'transform 0.4s cubic-bezier(0.25, 1, 0.5, 1)',
    });

    const glareStyle = ref({
        opacity: 0,
        background: 'radial-gradient(circle at 50% 50%, rgba(255,255,255,0.2), transparent 70%)',
    });

    const onMouseMove = (event: MouseEvent, targetElement?: HTMLElement | null) => {
        const el = targetElement || (event.currentTarget as HTMLElement);
        if (!el) return;

        const rect = el.getBoundingClientRect();
        const width = rect.width;
        const height = rect.height;

        const mouseX = event.clientX - rect.left;
        const mouseY = event.clientY - rect.top;

        // Calculate rotation (-maxRotation to +maxRotation)
        const rotateY = ((mouseX / width) - 0.5) * (maxRotation * 2);
        const rotateX = ((0.5 - (mouseY / height))) * (maxRotation * 2);

        tiltStyle.value = {
            transform: `perspective(1000px) rotateX(${rotateX.toFixed(2)}deg) rotateY(${rotateY.toFixed(2)}deg) scale3d(1.02, 1.02, 1.02)`,
            transition: 'transform 0.1s ease-out',
        };

        // Glare calculation
        const glareX = (mouseX / width) * 100;
        const glareY = (mouseY / height) * 100;

        glareStyle.value = {
            opacity: 0.8,
            background: `radial-gradient(circle at ${glareX.toFixed(1)}% ${glareY.toFixed(1)}%, rgba(52, 211, 153, 0.22), transparent 60%)`,
        };
    };

    const onMouseLeave = () => {
        tiltStyle.value = {
            transform: 'perspective(1000px) rotateX(0deg) rotateY(0deg) scale3d(1, 1, 1)',
            transition: 'transform 0.6s cubic-bezier(0.25, 1, 0.5, 1)',
        };
        glareStyle.value = {
            opacity: 0,
            background: 'radial-gradient(circle at 50% 50%, rgba(255,255,255,0.2), transparent 70%)',
        };
    };

    return {
        tiltStyle,
        glareStyle,
        onMouseMove,
        onMouseLeave,
    };
}
