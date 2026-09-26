import type { AnswerMode, CourseLevel, LessonContentType } from './course';

export type OutlineLessonItem = {
    type: 'lesson';
    id: number;
    title: string;
    content_type: LessonContentType;
    duration_minutes: number | null;
    is_done: boolean;
    is_bookmarked: boolean;
};

export type OutlineQuizItem = {
    type: 'quiz';
    id: number;
    title: string;
    time_limit_minutes: number | null;
    questions_count: number;
    is_done: boolean;
};

export type OutlineItem = OutlineLessonItem | OutlineQuizItem;

export type OutlineReference = {
    type: 'lesson' | 'quiz';
    id: number;
    title: string;
};

export type LearningOutline = {
    course: { id: number; title: string };
    sections: Array<{ id: number; title: string; items: OutlineItem[] }>;
    items: OutlineReference[];
    progress: { completed: number; total: number; percent: number };
};

export type OutlineNeighbours = {
    previous: OutlineReference | null;
    next: OutlineReference | null;
};

export type LearnLesson = {
    id: number;
    title: string;
    content_type: LessonContentType;
    content: string | null;
    content_url: string | null;
    embed_url: string | null;
    duration_minutes: number | null;
    section_title: string;
    is_completed: boolean;
    is_bookmarked: boolean;
    note: string | null;
    attachments: Array<{
        id: number;
        name: string;
        size: number;
        mime_type: string | null;
    }>;
};

export type LearnQuiz = {
    id: number;
    title: string;
    description: string | null;
    time_limit_minutes: number | null;
    questions_count: number;
    max_score: number;
    section_title: string;
};

export type QuizAttemptSummary = {
    id: number;
    score: number;
    max_score: number;
    is_late: boolean;
    submitted_at: string | null;
};

export type AttemptContext = {
    attempt: {
        id: number;
        started_at: string;
        expires_at: string | null;
        submitted_at: string | null;
        seconds_remaining: number | null;
        score: number | null;
        max_score: number;
        is_late: boolean;
    };
    quiz: { id: number; title: string; time_limit_minutes: number | null };
    course: { id: number; title: string };
};

export type AttemptQuestion = {
    id: number;
    question: string;
    answer_mode: AnswerMode;
    max_points: number;
    options: Array<{ id: number; text: string }>;
};

export type ResultQuestion = {
    id: number;
    question: string;
    answer_mode: AnswerMode;
    points: number;
    max_points: number;
    is_correct: boolean;
    options: Array<{
        id: number;
        text: string;
        is_correct: boolean;
        was_selected: boolean;
    }>;
};

export type DiscussionAuthor = {
    id: number;
    name: string;
    avatar: string | null;
    is_staff: boolean;
};

export type DiscussionReply = {
    id: number;
    body: string;
    created_at: string | null;
    author: DiscussionAuthor;
    can_delete: boolean;
};

export type DiscussionQuestion = DiscussionReply & {
    replies: DiscussionReply[];
};

export type LearningCourseCard = {
    course: {
        id: number;
        title: string;
        thumbnail_url: string | null;
        level: CourseLevel;
        instructor: string | null;
    };
    last_lesson: { id: number; title: string } | null;
    last_accessed_at: string | null;
    progress: { completed: number; total: number; percent: number };
};

export type LearningActivity = {
    type: 'lesson' | 'quiz';
    title: string;
    course: { id: number; title: string };
    target_id: number;
    score?: number | null;
    max_score?: number;
    at: string | null;
};

export type LearningStats = {
    active_courses: number;
    completed_courses: number;
    lessons_completed: number;
    average_quiz_score: number | null;
    notes: number;
    bookmarks: number;
};

export type NoteEntry = {
    id: number;
    body: string;
    updated_at: string | null;
    lesson: { id: number; title: string };
    section: { id: number; title: string };
    course: { id: number; title: string };
};

export type BookmarkEntry = {
    id: number;
    title: string;
    content_type: LessonContentType;
    duration_minutes: number | null;
    bookmarked_at: string | null;
    section: { id: number; title: string };
    course: { id: number; title: string };
};

export type RatingSummary = {
    average: number | null;
    count: number;
    distribution: Record<number, number>;
};

export type CourseReviewEntry = {
    id: number;
    rating: number;
    comment: string | null;
    created_at: string | null;
    author: { id: number; name: string; avatar: string | null };
    can_delete: boolean;
};

export type StudentProgressRow = {
    enrollment_id: number;
    student: { id: number; name: string; email: string; avatar: string | null };
    enrolled_at: string;
    last_accessed_at: string | null;
    lessons_completed: number;
    lessons_total: number;
    quizzes_attempted: number;
    quizzes_total: number;
    quiz_score_percent: number | null;
    percent: number;
};

export type UnansweredQuestion = {
    id: number;
    body: string;
    created_at: string | null;
    author: { id: number; name: string; avatar: string | null };
    lesson: { id: number; title: string };
    course_id: number;
};
