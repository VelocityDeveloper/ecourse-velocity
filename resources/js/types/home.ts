export type HomeStats = {
    courses: number;
    lessons: number;
    students: number;
    instructors: number;
};

export type HomeCategory = {
    id: number;
    name: string;
    description: string | null;
    courses_count: number;
};

export type HomeInstructor = {
    id: number;
    name: string;
    avatar: string | null;
    headline: string | null;
    courses_count: number;
};
