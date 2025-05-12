import { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from 'lucide-vue-next';

export interface Auth {
    user: User | null;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
}

export interface FlashMessageContent {
    status?: 'success' | 'error' | 'info' | 'warning' | string;
    message?: string | null;
    description?: string | null;
    title?: string | null;
    type?: 'success' | 'error' | 'info' | 'warning' | string;
}

export interface FlashMessages {
    success?: string | null;
    error?: string | null;
    status?: string | null;
    message?: string | null;
    bag?: FlashMessageContent | null;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    sidebarOpen: boolean;
    flash?: FlashMessages;
};

export interface User {
    id: number;
    name: string;
    email: string;
    phone?: string | null;
    avatar?: string;
    email_verified_at: string | null;
    role: 'citizen' | 'mp' | 'senator' | 'clerk' | 'admin';
    legislative_house?: 'national_assembly' | 'senate' | null;
    county?: string | null;
    constituency?: string | null;
    is_verified?: boolean;
    created_at: string;
    updated_at: string;
}

export type BreadcrumbItemType = BreadcrumbItem;
