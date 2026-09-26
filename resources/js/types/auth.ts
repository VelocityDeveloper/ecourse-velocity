import type { CourseLevel } from './course';

export type UserRole = 'admin' | 'instructor' | 'student';

export type User = {
    id: number;
    name: string;
    email: string;
    role: UserRole;
    avatar?: string | null;
    headline?: string | null;
    bio?: string | null;
    email_verified_at: string | null;
    created_at: string;
    updated_at: string;
    [key: string]: unknown;
};

export type PublicProfile = {
    id: number;
    name: string;
    role: UserRole;
    avatar: string | null;
    headline: string | null;
    bio: string | null;
    joined_at: string | null;
};

export type ProfileCourse = {
    id: number;
    title: string;
    level: CourseLevel;
    thumbnail_url: string | null;
};

export type Auth = {
    user: User;
};
