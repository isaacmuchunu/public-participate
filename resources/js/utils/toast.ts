import type { FlashMessageContent, FlashMessages } from '@/types';
import { AlertTriangle, CheckCircle2, Info, XCircle } from 'lucide-vue-next';
import { h } from 'vue';
import { toast, type ToastOptions } from 'vue3-toastify';

type ToastVariant = 'success' | 'error' | 'info' | 'warning';

const iconByVariant: Record<ToastVariant, typeof CheckCircle2> = {
    success: CheckCircle2,
    error: XCircle,
    info: Info,
    warning: AlertTriangle,
};

const baseOptions: ToastOptions = {
    autoClose: 4500,
    hideProgressBar: false,
    closeOnClick: true,
    pauseOnHover: true,
    icon: false,
    position: 'top-right',
    toastClassName: 'portal-toast',
    bodyClassName: 'portal-toast__body',
};

const progressClassByVariant: Record<ToastVariant, string> = {
    success: 'portal-toast__progress portal-toast__progress--success',
    error: 'portal-toast__progress portal-toast__progress--error',
    info: 'portal-toast__progress portal-toast__progress--info',
    warning: 'portal-toast__progress portal-toast__progress--warning',
};

const iconClassByVariant: Record<ToastVariant, string> = {
    success: 'portal-toast__icon portal-toast__icon--success',
    error: 'portal-toast__icon portal-toast__icon--error',
    info: 'portal-toast__icon portal-toast__icon--info',
    warning: 'portal-toast__icon portal-toast__icon--warning',
};

const resolveVariant = (value?: string | null): ToastVariant => {
    switch (value) {
        case 'success':
        case 'ok':
            return 'success';
        case 'error':
        case 'danger':
        case 'failed':
            return 'error';
        case 'warning':
        case 'caution':
            return 'warning';
        default:
            return 'info';
    }
};

const emitToast = (variant: ToastVariant, title: string, description?: string | null) => {
    const Icon = iconByVariant[variant];

    toast(
        () =>
            h('div', { class: 'portal-toast__content' }, [
                h('span', { class: iconClassByVariant[variant] }, [h(Icon, { class: 'portal-toast__icon-mark' })]),
                h('div', { class: 'portal-toast__copy' }, [
                    h('p', { class: 'portal-toast__title' }, title),
                    description ? h('p', { class: 'portal-toast__description' }, description) : null,
                ]),
            ]),
        {
            ...baseOptions,
            progressClassName: progressClassByVariant[variant],
            type: variant,
        },
    );
};

const notifyFromFlashBag = (bag?: FlashMessageContent | null) => {
    if (!bag?.message) {
        return;
    }

    const variant = resolveVariant(bag.status ?? bag.type);
    const title = bag.title ?? bag.message;
    const description = bag.description ?? (bag.title ? bag.message : null);

    emitToast(variant, title, description);
};

const notifyFromFlash = (flash?: FlashMessages) => {
    if (!flash) {
        return;
    }

    if (flash.success) {
        emitToast('success', flash.success);
    }

    if (flash.error) {
        emitToast('error', flash.error);
    }

    if (flash.status) {
        emitToast('success', flash.status);
    }

    if (flash.message && !flash.success && !flash.error) {
        emitToast('info', flash.message);
    }

    notifyFromFlashBag(flash.bag);
};

const notifyFromErrors = (errors?: Record<string, unknown>) => {
    if (!errors) {
        return;
    }

    const [firstKey] = Object.keys(errors);

    if (!firstKey) {
        return;
    }

    const value = errors[firstKey];

    if (!value) {
        return;
    }

    const message = Array.isArray(value) ? value[0] : String(value);

    if (message) {
        emitToast('error', message);
    }
};

export const flashToastsFromPage = (page: any): void => {
    if (!page) {
        return;
    }

    notifyFromFlash(page.props.flash);
    notifyFromErrors(page.props.errors);
};

export const showNetworkErrorToast = (): void => {
    emitToast('error', 'Connection issue', 'We could not reach the server. Please try again.');
};
