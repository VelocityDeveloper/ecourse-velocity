import { formatRupiah } from '@/lib/course';

/** A number in the chosen period next to the same-length period before it. */
export type Comparison = { current: number; previous: number };

export type DashboardPeriod = {
    key: string;
    label: string;
    monthly: boolean;
    start: string;
    previous_start: string;
    options: { key: string; label: string }[];
};

export type SeriesPoint = {
    bucket: string;
    revenue: number;
    enrollments: number;
};

export type Person = { id: number; name: string; avatar: string | null };
export type CourseRef = { slug: string; title: string };

export type UnansweredQuestion = {
    id: number;
    body: string;
    created_at: string | null;
    author: Person;
    lesson: CourseRef;
    course: CourseRef;
};

export type RecentPayment = {
    id: number;
    order_number: string;
    course_title: string;
    amount: number;
    paid_at: string;
    student: Person | null;
};

/**
 * The change from the previous period, or null when there is nothing to compare with.
 */
export function change(value: Comparison): number | null {
    if (value.previous === 0) {
        return null;
    }

    return Math.round(
        ((value.current - value.previous) / value.previous) * 100,
    );
}

/** Rupiah in a few characters for chart axes: Rp1,2 jt, Rp350 rb. */
export function compactRupiah(amount: number): string {
    if (amount >= 1_000_000_000) {
        return `Rp${(amount / 1_000_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} M`;
    }

    if (amount >= 1_000_000) {
        return `Rp${(amount / 1_000_000).toLocaleString('id-ID', { maximumFractionDigits: 1 })} jt`;
    }

    if (amount >= 1_000) {
        return `Rp${Math.round(amount / 1_000)} rb`;
    }

    return formatRupiah(amount);
}

/** A chart bucket (2026-09-30 or 2026-09) as a short label. */
export function bucketLabel(
    bucket: string,
    monthly: boolean,
    long = false,
): string {
    const date = new Date(
        monthly ? `${bucket}-01T00:00:00` : `${bucket}T00:00:00`,
    );

    return date.toLocaleDateString(
        'id-ID',
        monthly
            ? {
                  month: long ? 'long' : 'short',
                  year: long ? 'numeric' : '2-digit',
              }
            : {
                  day: 'numeric',
                  month: 'short',
                  ...(long ? { year: 'numeric' } : {}),
              },
    );
}

/** "3 hari lalu" style relative time. */
export function timeAgo(date: string | null): string {
    if (date === null) {
        return '';
    }

    const minutes = Math.round((Date.now() - new Date(date).getTime()) / 60000);

    if (minutes < 60) {
        return minutes <= 1 ? 'baru saja' : `${minutes} menit lalu`;
    }

    const hours = Math.round(minutes / 60);

    if (hours < 24) {
        return `${hours} jam lalu`;
    }

    const days = Math.round(hours / 24);

    return days < 30
        ? `${days} hari lalu`
        : new Date(date).toLocaleDateString('id-ID', {
              day: 'numeric',
              month: 'short',
          });
}

/** Good morning/afternoon/evening by the viewer's clock. */
export function greeting(): string {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    return hour < 18 ? 'Selamat sore' : 'Selamat malam';
}
