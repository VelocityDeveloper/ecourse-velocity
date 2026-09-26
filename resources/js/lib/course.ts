import type { BadgeVariants } from '@/components/ui/badge';
import type {
    AnswerMode,
    CourseLevel,
    CourseStatus,
    EnrollmentStatus,
    LessonContentType,
} from '@/types';

const STATUS_LABELS: Record<CourseStatus, string> = {
    draft: 'Draft',
    pending: 'Pending Review',
    published: 'Published',
    archived: 'Archived',
};

const STATUS_VARIANTS: Record<CourseStatus, BadgeVariants['variant']> = {
    draft: 'outline',
    pending: 'secondary',
    published: 'default',
    archived: 'destructive',
};

const LEVEL_LABELS: Record<CourseLevel, string> = {
    beginner: 'Beginner',
    intermediate: 'Intermediate',
    advanced: 'Advanced',
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
        return 'Free';
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
    article: 'Article',
};

export function contentTypeLabel(type: LessonContentType): string {
    return CONTENT_TYPE_LABELS[type] ?? type;
}

export function formatDuration(minutes: number | null): string {
    if (minutes === null || minutes <= 0) {
        return '-';
    }

    if (minutes < 60) {
        return `${minutes} min`;
    }

    const hours = Math.floor(minutes / 60);
    const rest = minutes % 60;

    return rest === 0 ? `${hours} j` : `${hours} j ${rest} min`;
}

const ANSWER_MODE_LABELS: Record<AnswerMode, string> = {
    single: 'One correct answer',
    multiple: 'Several correct answers',
    true_false: 'True or false',
};

export function answerModeLabel(mode: AnswerMode): string {
    return ANSWER_MODE_LABELS[mode] ?? mode;
}

export function formatTimeLimit(minutes: number | null): string {
    if (minutes === null) {
        return 'No time limit';
    }

    const hours = Math.floor(minutes / 60);
    const remainder = minutes % 60;

    if (hours === 0) {
        return `${remainder} min`;
    }

    return remainder === 0 ? `${hours} h` : `${hours} h ${remainder} min`;
}

const ENROLLMENT_STATUS_LABELS: Record<EnrollmentStatus, string> = {
    active: 'Active',
    cancelled: 'Cancelled',
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
