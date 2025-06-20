import { onMounted, ref } from 'vue';

type Appearance = 'light' | 'system';

export function updateTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always use light theme, even for system preference
    document.documentElement.classList.remove('dark');
}

const setCookie = (name: string, value: string, days = 365) => {
    if (typeof document === 'undefined') {
        return;
    }

    const maxAge = days * 24 * 60 * 60;
    document.cookie = `${name}=${value};path=/;max-age=${maxAge};SameSite=Lax`;
};



export function initializeTheme() {
    if (typeof window === 'undefined') {
        return;
    }

    // Always initialize with light theme
    document.documentElement.classList.remove('dark');
}

const appearance = ref<Appearance>('system');

export function useAppearance() {
    onMounted(() => {
        const savedAppearance = localStorage.getItem('appearance') as Appearance | null;

        if (savedAppearance && (savedAppearance === 'light' || savedAppearance === 'system')) {
            appearance.value = savedAppearance;
        }
    });

    function updateAppearance(value: Appearance) {
        appearance.value = value;

        // Store in localStorage for client-side persistence
        localStorage.setItem('appearance', value);

        // Store in cookie for SSR
        setCookie('appearance', value);

        // Always use light theme
        updateTheme();
    }

    return {
        appearance,
        updateAppearance,
    };
}
