import type { InertiaLinkProps } from '@inertiajs/vue3';
import type { LucideIcon } from '@lucide/vue';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon;
    isActive?: boolean;
    items?: NavItem[];
    /** Other pages (or URL prefixes) that should also highlight this item. */
    activeFor?: NonNullable<InertiaLinkProps['href']>[];
    /** A count shown beside the title, e.g. requests waiting for review. */
    badge?: number;
};
