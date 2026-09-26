import type { BadgeVariants } from '@/components/ui/badge';
import type {
    AnswerMode,
    CourseLevel,
    CourseStatus,
    EnrollmentStatus,
    LessonContentType,
} from '@/types';

const STATUS_LABELS: Record<CourseStatus, string> = {
    draft: 'Draf',
    pending: 'Menunggu Tinjauan',
    published: 'Terbit',
    archived: 'Diarsipkan',
};

const STATUS_VARIANTS: Record<CourseStatus, BadgeVariants['variant']> = {
    draft: 'outline',
    pending: 'secondary',
    published: 'default',
    archived: 'destructive',
};

const LEVEL_LABELS: Record<CourseLevel, string> = {
    beginner: 'Pemula',
    intermediate: 'Menengah',
    advanced: 'Lanjutan',
};

export function statusLabel(status: CourseStatus): string {
    return STATUS_LABELS[status] ?? status;
}

export function statusBadgeVariant(
    status: CourseStatus,
): BadgeVariants['variant'] {
    return STATUS_VARIANTS[status] ?? 'outline';
}

export function levelLabel(level: CourseLevel): string {
    return LEVEL_LABELS[level] ?? level;
}

export function formatPrice(price: string | number): string {
    const amount = typeof price === 'number' ? price : Number(price);

    if (Number.isNaN(amount)) {
        return String(price);
    }

    if (amount === 0) {
        return 'Gratis';
    }

    return new Intl.NumberFormat('id-ID', {
        style: 'currency',
        currency: 'IDR',
        maximumFractionDigits: 0,
    }).format(amount);
}

export function formatDate(date: string | null): string {
    if (date === null) {
        return '-';
    }

    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}

const CONTENT_TYPE_LABELS: Record<LessonContentType, string> = {
    video: 'Video',
    article: 'Artikel',
};

export function contentTypeLabel(type: LessonContentType): string {
    return CONTENT_TYPE_LABELS[type] ?? type;
}

export function formatDuration(minutes: number | null): string {
    if (minutes === null || minutes <= 0) {
        return '-';
    }

    if (minutes < 60) {
        return `${minutes} mnt`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest === 0 ? `${hours} j` : `${hours} j ${rest} mnt`;
}

const ANSWER_MODE_LABELS: Record<AnswerMode, string> = {
    single: 'Satu jawaban benar',
    multiple: 'Beberapa jawaban benar',
    true_false: 'Benar atau salah',
};

// True/false questions store their fixed options in English (QuizQuestion::TRUE_FALSE_OPTIONS),
// so they are translated only when shown.
const OPTION_LABELS: Record<string, string> = {
    True: 'Benar',
    False: 'Salah',
};

export function optionLabel(text: string): string {
    return OPTION_LABELS[text] ?? text;
}

export function answerModeLabel(mode: AnswerMode): string {
    return ANSWER_MODE_LABELS[mode] ?? mode;
}

export function formatTimeLimit(minutes: number | null): string {
    if (minutes === null) {
        return 'Tanpa batas waktu';
    }

    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;

    if (hours === 0) {
        return `${remainder} mnt`;
    }

    return remainder === 0 ? `${hours} j` : `${hours} j ${remainder} mnt`;
}

const ENROLLMENT_STATUS_LABELS: Record<EnrollmentStatus, string> = {
    active: 'Aktif',
    cancelled: 'Dibatalkan',
};

const ENROLLMENT_STATUS_VARIANTS: Record<
    EnrollmentStatus,
    BadgeVariants['variant']
> = {
    active: 'default',
    cancelled: 'outline',
};

export function enrollmentStatusLabel(status: EnrollmentStatus): string {
    return ENROLLMENT_STATUS_LABELS[status] ?? status;
}

export function enrollmentStatusVariant(
    status: EnrollmentStatus,
): BadgeVariants['variant'] {
    return ENROLLMENT_STATUS_VARIANTS[status] ?? 'outline';
}

export function formatBytes(size: number): string {
    if (size < 1024) {
        return `${size} B`;
    }

    if (size < 1024 * 1024) {
        return `${Math.round(size / 1024)} KB`;
    }

    return `${(size / (1024 * 1024)).toFixed(1)} MB`;
}
