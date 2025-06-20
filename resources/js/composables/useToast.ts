import { ref, readonly } from 'vue';
import type { ToastProps } from '@/components/ui/toast';

// Global state for toasts
const toasts = ref<ToastProps[]>([]);

// Generate unique ID for each toast
const generateId = () => `toast-${Date.now()}-${Math.random().toString(36).substr(2, 9)}`;

export const useToast = () => {
  const addToast = (toast: Omit<ToastProps, 'id'>) => {
    const id = generateId();
    const newToast: ToastProps = {
      id,
      ...toast,
    };
    
    toasts.value.push(newToast);
    return id;
  };

  const removeToast = (id: string) => {
    const index = toasts.value.findIndex(toast => toast.id === id);
    if (index > -1) {
      toasts.value.splice(index, 1);
    }
  };

  const clearAllToasts = () => {
    toasts.value = [];
  };

  // Convenience methods for common toast types
  const showSuccess = (title: string, message?: string, options?: Partial<ToastProps>) => {
    return addToast({
      type: 'success',
      title,
      message,
      ...options,
    });
  };

  const showError = (title: string, message?: string, options?: Partial<ToastProps>) => {
    return addToast({
      type: 'error',
      title,
      message,
      ...options,
    });
  };

  return {
    toasts: readonly(toasts),
    addToast,
    removeToast,
    clearAllToasts,
    showSuccess,
    showError,
  };
}; 