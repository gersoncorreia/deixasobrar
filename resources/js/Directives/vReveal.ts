import type { Directive } from 'vue';

interface RevealBindingValue {
    delay?: number;
    direction?: 'up' | 'down' | 'left' | 'right' | 'scale';
    blur?: boolean;
    distance?: number;
}

let sharedObserver: IntersectionObserver | null = null;
const elementsMap = new WeakMap<HTMLElement, { delay: number }>();

// Initial page load stabilization flag to prevent rushed or simultaneous pop-ins
let isPageReady = false;
let queuedTriggers: Array<() => void> = [];

if (typeof window !== 'undefined') {
    const markPageReady = () => {
        // Wait 280ms graceful breathing window on initial load before starting triggers
        setTimeout(() => {
            isPageReady = true;
            queuedTriggers.forEach(fn => fn());
            queuedTriggers = [];
        }, 280);
    };

    if (document.readyState === 'complete') {
        markPageReady();
    } else {
        window.addEventListener('load', markPageReady, { once: true });
        // Fallback safety timeout if window.load already passed
        setTimeout(markPageReady, 500);
    }
}

function getObserver(): IntersectionObserver {
    if (!sharedObserver && typeof window !== 'undefined') {
        sharedObserver = new IntersectionObserver(
            (entries) => {
                entries.forEach((entry) => {
                    if (entry.isIntersecting) {
                        const el = entry.target as HTMLElement;
                        const config = elementsMap.get(el);
                        const delay = config?.delay || 0;

                        const triggerReveal = () => {
                            if (delay > 0) {
                                setTimeout(() => {
                                    el.classList.add('is-revealed');
                                }, delay);
                            } else {
                                el.classList.add('is-revealed');
                            }
                        };

                        if (!isPageReady) {
                            queuedTriggers.push(triggerReveal);
                        } else {
                            triggerReveal();
                        }

                        // Unobserve after scheduling trigger
                        sharedObserver?.unobserve(el);
                        elementsMap.delete(el);
                    }
                });
            },
            {
                threshold: 0.08,
                rootMargin: '0px 0px -30px 0px',
            }
        );
    }
    return sharedObserver!;
}

export const vReveal: Directive<HTMLElement, number | RevealBindingValue | undefined> = {
    beforeMount(el, binding) {
        let delay = 0;
        let direction: 'up' | 'down' | 'left' | 'right' | 'scale' = 'up';

        if (typeof binding.value === 'number') {
            delay = binding.value;
        } else if (typeof binding.value === 'object' && binding.value !== null) {
            delay = binding.value.delay || 0;
            if (binding.value.direction) {
                direction = binding.value.direction;
            }
        }

        // Add base classes for initial hidden state before element enters DOM
        el.classList.add('reveal-element');
        if (direction === 'scale') {
            el.classList.add('reveal-scale');
        } else if (direction === 'left') {
            el.classList.add('reveal-left');
        } else if (direction === 'right') {
            el.classList.add('reveal-right');
        } else {
            el.classList.add('reveal-up');
        }

        elementsMap.set(el, { delay });
    },
    mounted(el) {
        getObserver().observe(el);
    },
    unmounted(el) {
        sharedObserver?.unobserve(el);
        elementsMap.delete(el);
    },
};
