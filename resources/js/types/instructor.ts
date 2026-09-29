import type { CourseStatus } from './course';

/** An instructor in the admin list, with their numbers for the chosen period. */
/** An instructor on the Instruktur list: courses, students and rating, no money. */
export type InstructorPerson = {
    id: number;
    name: string;
    email: string;
    slug: string;
    headline: string | null;
    avatar: string | null;
    joined_at: string | null;
    suspended: boolean;
    courses_count: number;
    published_courses_count: number;
    students_count: number;
    rating: number | null;
};

export type InstructorRow = {
    id: number;
    name: string;
    email: string;
    slug: string;
    headline: string | null;
    avatar: string | null;
    joined_at: string | null;
    courses_count: number;
    published_courses_count: number;
    students_count: number;
    rating: number | null;
    sales_count: number;
    /** What students paid in the period. */
    gross: number;
    /** The platform's commission out of that. */
    commission: number;
    /** The instructor's share after commission, in the period. */
    revenue: number;
    revenue_today: number;
    revenue_this_month: number;
    revenue_all_time: number;
    /** Paid out to the instructor through withdrawals. */
    withdrawn: number;
    /** Withdrawals requested and still waiting for a transfer. */
    withdrawal_pending: number;
    /** What the instructor can still withdraw. */
    balance: number;
};

/** One of an instructor's courses with what it earned in the period. */
export type InstructorCourseRevenue = {
    id: number;
    title: string;
    slug: string;
    status: CourseStatus;
    price: number;
    students_count: number;
    rating: number | null;
    revenue: number;
    gross: number;
    commission: number;
    sales_count: number;
};

export type InstructorPayment = {
    id: number;
    amount: number;
    commission_rate: number;
    commission_amount: number;
    instructor_amount: number;
    paid_at: string;
    order_number: string;
    course_title: string;
    student: { name: string; email: string };
};

export type InstructorApplicationStatus = 'pending' | 'approved' | 'rejected';

/** A request to become an instructor, as the applicant sees it. */
export type InstructorApplication = {
    id: number;
    status: InstructorApplicationStatus;
    headline: string;
    expertise: string;
    experience: string;
    motivation: string;
    portfolio_url: string | null;
    phone: string | null;
    admin_note: string | null;
    created_at: string;
    reviewed_at: string | null;
};

/** A row of the admin review list. */
export type InstructorApplicationRow = InstructorApplication & {
    user: {
        id: number;
        name: string;
        slug: string;
        email: string;
        role: string;
        avatar: string | null;
        created_at: string;
    };
    reviewer: { id: number; name: string } | null;
};

export type WithdrawalStatus = 'pending' | 'paid' | 'rejected' | 'cancelled';

export type InstructorBalance = {
    earned: number;
    paid_out: number;
    pending: number;
    available: number;
};

/** A payout request, as the instructor sees it. */
export type Withdrawal = {
    id: number;
    amount: number;
    bank_name: string;
    account_number: string;
    account_name: string;
    note: string | null;
    status: WithdrawalStatus;
    admin_note: string | null;
    has_proof: boolean;
    processor: { id: number; name: string } | null;
    processed_at: string | null;
    created_at: string;
};

/** A payout request in the admin list, with the instructor's current balance. */
export type WithdrawalRow = Withdrawal & {
    instructor: {
        id: number;
        name: string;
        slug: string;
        email: string;
        avatar: string | null;
        balance: InstructorBalance;
    };
};
