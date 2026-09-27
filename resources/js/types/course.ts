export type CourseStatus = 'draft' | 'pending' | 'published' | 'archived';

export type CourseLevel = 'beginner' | 'intermediate' | 'advanced';

export type CourseOption = {
    id: number;
    name: string;
};

export type CoursePermissions = {
    update: boolean;
    delete: boolean;
};

export type CourseSummary = {
    id: number;
    title: string;
    slug: string;
    status: CourseStatus;
    level: CourseLevel;
    price: string;
    thumbnail_url: string | null;
    category: CourseOption | null;
    instructor: CourseOption | null;
    created_at: string | null;
    can: CoursePermissions;
};

export type CourseDetail = CourseSummary & {
    description: string | null;
    instructor_email: string | null;
    students_count: number;
    updated_at: string | null;
};

export type CourseFormValues = {
    id: number;
    title: string;
    slug: string;
    description: string | null;
    category_id: number | null;
    instructor_id: number;
    price: string;
    level: CourseLevel;
    status: CourseStatus;
    thumbnail_url: string | null;
};

export type Category = {
    id: number;
    name: string;
    slug: string;
    description: string | null;
    image_url?: string | null;
    courses_count?: number;
};

export type Paginated<T> = {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
};

export type LessonContentType = 'video' | 'article';

export type Lesson = {
    id: number;
    title: string;
    content_type: LessonContentType;
    content_url: string | null;
    duration_minutes: number | null;
    position: number;
};

export type Section = {
    id: number;
    title: string;
    description: string | null;
    position: number;
    duration_minutes: number;
    lessons: Lesson[];
    quizzes: QuizSummary[];
};

export type MoveDirection = 'up' | 'down';

export type AnswerMode = 'single' | 'multiple' | 'true_false' | 'short_answer';

export type QuizOption = {
    id?: number;
    text: string;
    is_correct: boolean;
};

export type QuizQuestion = {
    id: number;
    question: string;
    answer_mode: AnswerMode;
    points: number;
    max_points: number;
    position: number;
    options: QuizOption[];
    scores: number[];
};

export type QuizSummary = {
    id: number;
    title: string;
    description: string | null;
    time_limit_minutes: number | null;
    position: number;
    questions_count: number;
    total_points: number;
};

export type QuizDetail = {
    id: number;
    title: string;
    description: string | null;
    time_limit_minutes: number | null;
    passing_score: number | null;
    weight: number;
    total_points: number;
    questions: QuizQuestion[];
};

export type MovableItem = {
    id: number;
    title: string;
    section_id: number;
    section_title: string;
    course_title: string;
};

export type GradebookQuiz = {
    id: number;
    title: string;
    section_title: string;
    weight: number;
    passing_score: number | null;
    max_score: number;
};

export type GradebookQuizGrade = {
    percent: number | null;
    score: number | null;
    max_score: number | null;
    attempts: number;
    passed: boolean | null;
};

export type GradebookRow = {
    enrollment_id: number;
    student: { id: number; name: string; email: string; avatar: string | null };
    grades: Record<number, GradebookQuizGrade>;
    quizzes_taken: number;
    final_percent: number | null;
    letter: string | null;
    passed: boolean | null;
    certificate_code: string | null;
};
