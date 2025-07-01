import type { LucideIcon } from 'lucide-vue-next';
import type { Config } from 'ziggy-js';

// Import comprehensive API types
export * from './api'

// Existing interface definitions for backward compatibility
export interface Auth {
    user: User;
}

export interface BreadcrumbItem {
    title: string;
    href: string;
}

export interface NavItem {
    title: string;
    href: string;
    icon?: LucideIcon;
    isActive?: boolean;
}

export type AppPageProps<T extends Record<string, unknown> = Record<string, unknown>> = T & {
    name: string;
    quote: { message: string; author: string };
    auth: Auth;
    ziggy: Config & { location: string };
    sidebarOpen: boolean;
};

// Enhanced User interface (extends the API User type)
export interface User {
    id: number;
    name: string;
    email: string;
    avatar?: string;
    email_verified_at: string | null;
    role: 'admin' | 'user';
    created_at: string;
    updated_at: string;
    last_activity?: string;
    failed_login_attempts?: number;
    account_locked_until?: string | null;
}

export type BreadcrumbItemType = BreadcrumbItem;
