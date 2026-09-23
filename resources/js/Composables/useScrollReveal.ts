import { onMounted, onUnmounted } from 'vue';

export function useScrollReveal() {
    let observer: IntersectionObserver | null = null;

    const initScrollReveal = () => {
        const elements = document.querySelectorAll('[data-reveal]');

        if (!('IntersectionObserver' in window)) {
            // Fallback for older browsers
            elements.forEach(el => el.classList.add('is-revealed'));
            return;
        }

        observer = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        entry.target.classList.add('is-revealed');
                        // Unobserve once revealed for peak performance
                        observer?.unobserve(entry.target);
                    }
                });
            },
            {
                threshold: 0.12,
                rootMargin: '0px 0px -50px 0px',
            }
        );

        elements.forEach((el) => observer?.observe(el));
    };

    onMounted(() => {
        // Run on next tick after DOM updates
        setTimeout(initScrollReveal, 100);
    });

    onUnmounted(() => {
        if (observer) {
            observer.disconnect();
            observer = null;
        }
    });

    return {
        initScrollReveal,
    };
}
