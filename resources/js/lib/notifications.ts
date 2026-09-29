import {
    Banknote,
    Bell,
    CreditCard,
    MessagesSquare,
    Presentation,
    ReceiptText,
    TrendingUp,
} from '@lucide/vue';
import type { Component } from 'vue';
import type { NotificationKind, NotificationTone } from '@/types';

export const NOTIFICATION_ICONS: Record<NotificationKind, Component> = {
    payment: CreditCard,
    order: ReceiptText,
    sale: TrendingUp,
    application: Presentation,
    withdrawal: Banknote,
    discussion: MessagesSquare,
    info: Bell,
};

export const NOTIFICATION_TONES: Record<NotificationTone, string> = {
    success:
        'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300',
    warning:
        'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300',
    info: 'bg-primary/10 text-primary',
};

const UNITS: Array<[Intl.RelativeTimeFormatUnit, number]> = [
    ['year', 60 * 60 * 24 * 365],
    ['month', 60 * 60 * 24 * 30],
    ['week', 60 * 60 * 24 * 7],
    ['day', 60 * 60 * 24],
    ['hour', 60 * 60],
    ['minute', 60],
];

/** "5 menit yang lalu", "kemarin", ... */
export function timeAgo(date: string | null): string {
    if (!date) {
        return '';
    }

    const seconds = Math.round((new Date(date).getTime() - Date.now()) / 1000);
    const format = new Intl.RelativeTimeFormat('id', { numeric: 'auto' });

    for (const [unit, size] of UNITS) {
        if (Math.abs(seconds) >= size) {
            return format.format(Math.round(seconds / size), unit);
        }
    }

    return 'baru saja';
}
