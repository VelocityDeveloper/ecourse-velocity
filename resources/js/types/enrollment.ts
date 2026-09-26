import type { CourseLevel, CourseOption, CourseStatus } from './course';
import type { UserRole } from './auth';

export type EnrollmentStatus = 'active' | 'cancelled';

export type PersonSummary = {
    id: number;
    name: string;
    avatar?: string | null;
};

export type CatalogCourse = {
    id: number;
    title: string;
    level: CourseLevel;
    price: string;
    thumbnail_url: string | null;
    category: CourseOption | null;
    instructor: PersonSummary | null;
    students_count: number;
    reviews_count: number;
    rating_average: number | null;
    is_enrolled: boolean;
};

export type CatalogCourseDetail = {
    id: number;
    title: string;
    description: string | null;
    level: CourseLevel;
    price: string;
    status: CourseStatus;
    thumbnail_url: string | null;
    category: CourseOption | null;
    instructor: (PersonSummary & { headline: string | null }) | null;
    students_count: number;
};

export type CatalogSection = {
    id: number;
    title: string;
    lessons: Array<{
        id: number;
        title: string;
        content_type: 'video' | 'article';
        duration_minutes: number | null;
    }>;
    quizzes: Array<{
        id: number;
        title: string;
        time_limit_minutes: number | null;
        questions_count: number;
    }>;
};

export type OwnEnrollment = {
    id: number;
    status: EnrollmentStatus;
    enrolled_at: string;
    cancelled_at: string | null;
};

export type MyCourseEnrollment = {
    id: number;
    enrolled_at: string;
    can_cancel: boolean;
    progress: { completed: number; total: number; percent: number };
    course: {
        id: number;
        title: string;
        level: CourseLevel;
        thumbnail_url: string | null;
        category: CourseOption | null;
        instructor: PersonSummary | null;
    };
};

export type EnrollmentRow = {
    id: number;
    status: EnrollmentStatus;
    enrolled_at: string;
    cancelled_at: string | null;
    is_self_enrolled: boolean;
    enrolled_by: { id: number; name: string } | null;
    student: PersonSummary & { email: string };
    course: { id: number; title: string };
    can_cancel: boolean;
};

export type EnrollmentActor = { id: number; name: string; role: UserRole };

export type EnrollmentDetail = {
    id: number;
    status: EnrollmentStatus;
    enrolled_at: string;
    cancelled_at: string | null;
    cancellation_reason: string | null;
    is_self_enrolled: boolean;
    enrolled_by: EnrollmentActor | null;
    cancelled_by: EnrollmentActor | null;
    student: PersonSummary & {
        email: string;
        headline: string | null;
        joined_at: string | null;
    };
    course: {
        id: number;
        title: string;
        status: CourseStatus;
        level: CourseLevel;
        price: string;
        thumbnail_url: string | null;
        category: CourseOption | null;
        instructor: CourseOption | null;
    };
};

export type StudentOption = { id: number; name: string; email: string };
