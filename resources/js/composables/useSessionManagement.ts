import { ref, onMounted, onUnmounted } from 'vue';
import { router } from '@inertiajs/vue3';

export function useSessionManagement() {
    const sessionTimeoutId = ref<number | null>(null);
    const warningTimeoutId = ref<number | null>(null);
    const showSessionWarning = ref(false);
    
    // 30 minutes = 1800000 milliseconds
    const SESSION_TIMEOUT = 30 * 60 * 1000;
    // Show warning 5 minutes before expiration
    const WARNING_TIME = 25 * 60 * 1000;

    const resetSessionTimer = () => {
        // Clear existing timers
        if (sessionTimeoutId.value) {
            clearTimeout(sessionTimeoutId.value);
        }
        if (warningTimeoutId.value) {
            clearTimeout(warningTimeoutId.value);
        }
        
        showSessionWarning.value = false;

        // Set warning timer (25 minutes)
        warningTimeoutId.value = setTimeout(() => {
            showSessionWarning.value = true;
        }, WARNING_TIME);

        // Set session timeout (30 minutes)
        sessionTimeoutId.value = setTimeout(() => {
            handleSessionExpired();
        }, SESSION_TIMEOUT);
    };

    const handleSessionExpired = () => {
        showSessionWarning.value = false;
        router.post('/logout', {}, {
            onFinish: () => {
                router.visit('/login', {
                    data: { 
                        message: 'Your session has expired due to inactivity. Please log in again.' 
                    }
                });
            }
        });
    };

    const extendSession = () => {
        // Make a simple request to extend the session
        router.reload({
            only: [],
            onSuccess: () => {
                resetSessionTimer();
            }
        });
    };

    const dismissWarning = () => {
        showSessionWarning.value = false;
        extendSession();
    };

    const setupActivityListeners = () => {
        const events = ['mousedown', 'mousemove', 'keypress', 'scroll', 'touchstart', 'click'];
        
        const resetTimer = () => {
            resetSessionTimer();
        };

        events.forEach(event => {
            document.addEventListener(event, resetTimer, { passive: true });
        });

        // Cleanup function
        return () => {
            events.forEach(event => {
                document.removeEventListener(event, resetTimer);
            });
        };
    };

    onMounted(() => {
        const cleanup = setupActivityListeners();
        resetSessionTimer();
        
        onUnmounted(() => {
            cleanup();
            if (sessionTimeoutId.value) {
                clearTimeout(sessionTimeoutId.value);
            }
            if (warningTimeoutId.value) {
                clearTimeout(warningTimeoutId.value);
            }
        });
    });

    return {
        showSessionWarning,
        extendSession,
        dismissWarning,
        handleSessionExpired
    };
} 