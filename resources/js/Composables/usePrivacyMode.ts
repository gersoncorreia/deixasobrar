import { ref } from 'vue';

const isPrivate = ref(typeof window !== 'undefined' && localStorage.getItem('ds_privacy_mode') === 'true');

export function usePrivacyMode() {
    const togglePrivacy = () => {
        isPrivate.value = !isPrivate.value;
        if (typeof window !== 'undefined') {
            localStorage.setItem('ds_privacy_mode', isPrivate.value ? 'true' : 'false');
        }
    };

    const maskValue = (formattedValue: string, mask: string = '••••••') => {
        if (isPrivate.value) {
            return mask;
        }
        return formattedValue;
    };

    return {
        isPrivate,
        togglePrivacy,
        maskValue,
    };
}
