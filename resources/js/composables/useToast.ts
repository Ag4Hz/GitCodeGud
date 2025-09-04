import { reactive } from 'vue';

export interface Toast {
    id: string;
    type: 'success' | 'error' | 'info' | 'warning';
    message: string;
    duration?: number;
    sticky?: boolean;
}
interface ToastState {
    toasts: Toast[];
}

const state = reactive<ToastState>({
    toasts: [],
});

let toastIdCounter = 0;

export function useToast() {
    const generatedId = (): string => {
        return `toast-${++toastIdCounter}-${Date.now()}`;
    };

    const addToast = (toast: Omit<Toast, 'id'>): string => {
        const id = generatedId();
        const newToast: Toast = {
            id,
            duration: 5000,
            sticky: false,
            ...toast,
        };

        state.toasts.push(newToast);

        if (!newToast.sticky && newToast.duration && newToast.duration > 0) {
            setTimeout(() => removeToast(id), newToast.duration);
        }

        return id;
    };

    const removeToast = (id: string) => {
        const index = state.toasts.findIndex((t) => t.id === id);
        if (index !== -1) {
            state.toasts.splice(index, 1);
        }
    };

    const clearToasts = () => {
        state.toasts.splice(0, state.toasts.length);
    };

    // 👇 Add shorthand helpers
    const success = (message: string, duration = 5000, sticky = false) =>
        addToast({ type: 'success', message, duration, sticky });

    const error = (message: string, duration = 5000, sticky = false) =>
        addToast({ type: 'error', message, duration, sticky });

    const info = (message: string, duration = 5000, sticky = false) =>
        addToast({ type: 'info', message, duration, sticky });

    const warning = (message: string, duration = 5000, sticky = false) =>
        addToast({ type: 'warning', message, duration, sticky });

    return {
        toasts: state.toasts,
        addToast,
        removeToast,
        clearToasts,
        success,
        error,
        info,
        warning,
    };
}
