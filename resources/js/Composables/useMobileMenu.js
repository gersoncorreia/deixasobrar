import { ref } from 'vue';

const isMobileMenuOpen = ref(false);

export function useMobileMenu() {
    const openMobileMenu = () => {
        isMobileMenuOpen.value = true;
    };

    const closeMobileMenu = () => {
        isMobileMenuOpen.value = false;
    };

    const toggleMobileMenu = () => {
        isMobileMenuOpen.value = !isMobileMenuOpen.value;
    };

    return {
        isMobileMenuOpen,
        openMobileMenu,
        closeMobileMenu,
        toggleMobileMenu,
    };
}
