<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowDownRight,
    ArrowRight,
    ArrowUpRight,
    BookOpen,
    CircleCheck,
    MessageCircleQuestion,
    Newspaper,
    Plus,
    Receipt,
    Star,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import DashboardActivityChart from '@/components/DashboardActivityChart.vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import {
    enrollmentStatusLabel,
    enrollmentStatusVariant,
    formatDate,
    formatRupiah,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import { dashboard } from '@/routes';
import adminOrders from '@/routes/admin/orders';
import posts from '@/routes/admin/posts';
import courses from '@/routes/courses';
import enrollments from '@/routes/enrollments';
import learn from '@/routes/learn';
import type { CourseStatus, EnrollmentStatus, RatingSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dasbor', href: dashboard() }],
    },
});

type Person = { id: number; name: string; avatar: string | null };
type CourseRef = { slug: string; title: string };

const props = defineProps<{
    isAdmin: boolean;
    stats: {
        revenue: { month: number; previous: number; total: number };
        enrollments: { month: number; previous: number };
        students: number;
        courses: { published: number; draft: number; pending: number };
        rating: RatingSummary;
        users: {
            students: number;
            instructors: number;
            new_this_month: number;
        } | null;
    };
    chart: Array<{ date: string; enrollments: number; revenue: number }>;
    awaitingOrders: Array<{
        number: string;
        course_title: string;
        total: number;
        proof_uploaded_at: string | null;
        student: Person;
    }>;
    awaitingOrdersCount: number;
    unansweredQuestions: Array<{
        id: number;
        body: string;
        created_at: string | null;
        author: Person;
        lesson: CourseRef;
        course: CourseRef;
    }>;
    pendingCourses: Array<{
        slug: string;
        title: string;
        instructor: string | null;
        updated_at: string | null;
    }>;
    topCourses: Array<{
        slug: string;
        title: string;
        status: CourseStatus;
        thumbnail_url: string | null;
        students: number;
        rating: number | null;
        reviews: number;
    }>;
    recentEnrollments: Array<{
        id: number;
        status: EnrollmentStatus;
        enrolled_at: string;
        student: Person;
        course: CourseRef;
    }>;
    recentReviews: Array<{
        id: number;
        rating: number;
        comment: string | null;
        created_at: string | null;
        author: Person;
        course: CourseRef;
    }>;
}>();

const page = usePage();
const firstName = computed(
    () => page.props.auth.user?.name.split(' ')[0] ?? '',
);

const greeting = computed(() => {
    const hour = new Date().getHours();

    if (hour < 11) {
        return 'Selamat pagi';
    }

    if (hour < 15) {
        return 'Selamat siang';
    }

    return hour < 18 ? 'Selamat sore' : 'Selamat malam';
});

// Percentage change against last month; null when last month had nothing to compare with.
function trend(current: number, previous: number): number | null {
    if (previous === 0) {
        return null;
    }

    return Math.round(((current - previous) / previous) * 100);
}

const cards = computed(() => [
    {
        label: 'Pendapatan bulan ini',
        value: formatRupiah(props.stats.revenue.month),
        note: `Total ${formatRupiah(props.stats.revenue.total)}`,
        trend: trend(props.stats.revenue.month, props.stats.revenue.previous),
        icon: Wallet,
    },
    {
        label: 'Pendaftar baru bulan ini',
        value: `${props.stats.enrollments.month}`,
        note: `${props.stats.enrollments.previous} bulan lalu`,
        trend: trend(
            props.stats.enrollments.month,
            props.stats.enrollments.previous,
        ),
        icon: Users,
    },
    {
        label: 'Siswa aktif',
        value: `${props.stats.students}`,
        note: props.stats.users
            ? `${props.stats.users.students} siswa · ${props.stats.users.instructors} instruktur`
            : 'Di semua kursus Anda',
        trend: null,
        icon: CircleCheck,
    },
    {
        label: 'Rating rata-rata',
        value: props.stats.rating.average?.toFixed(1) ?? '-',
        note: `${props.stats.rating.count} ulasan`,
        trend: null,
        icon: Star,
    },
]);

const nothingToDo = computed(
    () =>
        props.awaitingOrdersCount === 0 &&
        props.unansweredQuestions.length === 0 &&
        props.pendingCourses.length === 0,
);
</script>

<template>
    <Head title="Dasbor" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ greeting }}, {{ firstName }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{
                        isAdmin
                            ? 'Ringkasan seluruh platform hari ini.'
                            : 'Ringkasan kursus yang Anda ajar hari ini.'
                    }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <Button v-if="isAdmin" as-child variant="outline">
                    <Link :href="posts.create()">
                        <Newspaper class="h-4 w-4" />
                        Tulis Artikel
                    </Link>
                </Button>
                <Button as-child>
                    <Link :href="courses.create()">
                        <Plus class="h-4 w-4" />
                        Buat Kursus
                    </Link>
                </Button>
            </div>
        </div>

        <!-- Key numbers -->
        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <div
                v-for="card in cards"
                :key="card.label"
                class="min-w-0 rounded-xl border bg-card p-4 text-card-foreground sm:p-5"
            >
                <div class="flex items-start justify-between gap-2">
                    <p class="text-xs text-muted-foreground sm:text-sm">
                        {{ card.label }}
                    </p>
                    <span
                        class="hidden size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary sm:flex"
                    >
                        <component :is="card.icon" class="size-4" />
                    </span>
                </div>
                <p
                    class="mt-1 truncate text-xl font-bold tracking-tight sm:text-2xl"
                >
                    {{ card.value }}
                </p>
                <div
                    class="mt-1 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs"
                >
                    <span
                        v-if="card.trend !== null"
                        class="inline-flex items-center gap-0.5 rounded-full px-1.5 py-0.5 font-semibold"
                        :class="
                            card.trend >= 0
                                ? 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400'
                                : 'bg-destructive/10 text-destructive'
                        "
                    >
                        <component
                            :is="
                                card.trend >= 0 ? ArrowUpRight : ArrowDownRight
                            "
                            class="size-3"
                        />
                        {{ Math.abs(card.trend) }}%
                    </span>
                    <span class="text-muted-foreground">{{ card.note }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-3">
            <DashboardActivityChart :days="chart" class="xl:col-span-2" />

            <!-- What needs attention -->
            <div class="rounded-xl border bg-card p-5 text-card-foreground">
                <h2 class="font-semibold">Perlu Tindakan</h2>

                <div
                    v-if="nothingToDo"
                    class="flex flex-col items-center gap-2 py-10 text-center text-sm text-muted-foreground"
                >
                    <CircleCheck class="size-8 text-emerald-500" />
                    Semua beres, tidak ada yang menunggu.
                </div>

                <div v-else class="mt-3 space-y-5">
                    <section v-if="awaitingOrdersCount > 0">
                        <Link
                            :href="
                                adminOrders.index({
                                    query: { status: 'awaiting_confirmation' },
                                })
                            "
                            class="flex items-center justify-between gap-2 text-sm font-medium hover:text-primary"
                        >
                            <span class="flex items-center gap-2">
                                <Receipt class="size-4 text-amber-500" />
                                Pembayaran menunggu konfirmasi
                            </span>
                            <Badge variant="secondary">{{
                                awaitingOrdersCount
                            }}</Badge>
                        </Link>
                        <ul class="mt-2 space-y-1">
                            <li
                                v-for="order in awaitingOrders"
                                :key="order.number"
                            >
                                <Link
                                    :href="adminOrders.show(order.number)"
                                    class="flex items-center justify-between gap-3 rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                >
                                    <span class="min-w-0">
                                        <span class="block truncate">{{
                                            order.student.name
                                        }}</span>
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                            >{{ order.course_title }}</span
                                        >
                                    </span>
                                    <span
                                        class="shrink-0 text-xs font-semibold"
                                        >{{ formatRupiah(order.total) }}</span
                                    >
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <section v-if="pendingCourses.length > 0">
                        <p class="flex items-center gap-2 text-sm font-medium">
                            <BookOpen class="size-4 text-sky-500" />
                            {{
                                isAdmin
                                    ? 'Kursus menunggu tinjauan'
                                    : 'Kursus Anda sedang ditinjau'
                            }}
                        </p>
                        <ul class="mt-2 space-y-1">
                            <li
                                v-for="course in pendingCourses"
                                :key="course.slug"
                            >
                                <Link
                                    :href="
                                        courses.show(course.slug, {
                                            query: { tab: 'status' },
                                        })
                                    "
                                    class="block rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                >
                                    <span class="block truncate">{{
                                        course.title
                                    }}</span>
                                    <span
                                        v-if="isAdmin && course.instructor"
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ course.instructor }}</span
                                    >
                                </Link>
                            </li>
                        </ul>
                    </section>

                    <section v-if="unansweredQuestions.length > 0">
                        <p class="flex items-center gap-2 text-sm font-medium">
                            <MessageCircleQuestion
                                class="size-4 text-violet-500"
                            />
                            Pertanyaan belum dijawab
                        </p>
                        <ul class="mt-2 space-y-1">
                            <li
                                v-for="question in unansweredQuestions"
                                :key="question.id"
                            >
                                <Link
                                    :href="
                                        learn.lessons.show({
                                            course: question.course.slug,
                                            lesson: question.lesson.slug,
                                        })
                                    "
                                    class="block rounded-md px-2 py-1.5 text-sm hover:bg-muted"
                                >
                                    <span class="line-clamp-1"
                                        >"{{ question.body }}"</span
                                    >
                                    <span
                                        class="block truncate text-xs text-muted-foreground"
                                        >{{ question.author.name }} ·
                                        {{ question.lesson.title }}</span
                                    >
                                </Link>
                            </li>
                        </ul>
                    </section>
                </div>
            </div>
        </div>

        <div class="grid gap-6 xl:grid-cols-2">
            <!-- Most popular courses -->
            <div class="rounded-xl border bg-card text-card-foreground">
                <div class="flex items-center justify-between gap-2 p-5 pb-3">
                    <h2 class="font-semibold">Kursus Terpopuler</h2>
                    <Link
                        :href="courses.index()"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        Semua kursus
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
                <p
                    v-if="topCourses.length === 0"
                    class="px-5 pb-5 text-sm text-muted-foreground"
                >
                    Belum ada kursus.
                </p>
                <ul v-else class="divide-y border-t">
                    <li v-for="course in topCourses" :key="course.slug">
                        <Link
                            :href="courses.show(course.slug)"
                            class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-muted/50"
                        >
                            <img
                                v-if="course.thumbnail_url"
                                :src="course.thumbnail_url"
                                alt=""
                                class="aspect-video w-16 shrink-0 rounded-md border object-cover"
                            />
                            <span
                                v-else
                                class="flex aspect-video w-16 shrink-0 items-center justify-center rounded-md border bg-muted"
                            >
                                <BookOpen
                                    class="size-4 text-muted-foreground"
                                />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                    >{{ course.title }}</span
                                >
                                <span
                                    class="mt-0.5 flex items-center gap-2 text-xs text-muted-foreground"
                                >
                                    <Badge
                                        :variant="
                                            statusBadgeVariant(course.status)
                                        "
                                        class="px-1.5 py-0 text-[10px]"
                                        >{{ statusLabel(course.status) }}</Badge
                                    >
                                    <span
                                        v-if="course.rating !== null"
                                        class="inline-flex items-center gap-0.5"
                                    >
                                        <Star
                                            class="size-3 fill-current text-rating"
                                        />
                                        {{ course.rating.toFixed(1) }} ({{
                                            course.reviews
                                        }})
                                    </span>
                                </span>
                            </span>
                            <span class="shrink-0 text-right">
                                <span class="block text-sm font-semibold">{{
                                    course.students
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >siswa</span
                                >
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>

            <!-- Latest enrollments -->
            <div class="rounded-xl border bg-card text-card-foreground">
                <div class="flex items-center justify-between gap-2 p-5 pb-3">
                    <h2 class="font-semibold">Pendaftar Terbaru</h2>
                    <Link
                        :href="enrollments.index()"
                        class="inline-flex items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
                    >
                        User terdaftar
                        <ArrowRight class="size-3.5" />
                    </Link>
                </div>
                <p
                    v-if="recentEnrollments.length === 0"
                    class="px-5 pb-5 text-sm text-muted-foreground"
                >
                    Belum ada pendaftar.
                </p>
                <ul v-else class="divide-y border-t">
                    <li
                        v-for="enrollment in recentEnrollments"
                        :key="enrollment.id"
                    >
                        <Link
                            :href="enrollments.show(enrollment.id)"
                            class="flex items-center gap-3 px-5 py-3 transition-colors hover:bg-muted/50"
                        >
                            <Avatar class="size-9 shrink-0">
                                <AvatarImage
                                    v-if="enrollment.student.avatar"
                                    :src="enrollment.student.avatar"
                                    :alt="enrollment.student.name"
                                />
                                <AvatarFallback class="text-xs">{{
                                    getInitials(enrollment.student.name)
                                }}</AvatarFallback>
                            </Avatar>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                    >{{ enrollment.student.name }}</span
                                >
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ enrollment.course.title }}</span
                                >
                            </span>
                            <span class="shrink-0 text-right">
                                <Badge
                                    :variant="
                                        enrollmentStatusVariant(
                                            enrollment.status,
                                        )
                                    "
                                    class="text-[10px]"
                                    >{{
                                        enrollmentStatusLabel(enrollment.status)
                                    }}</Badge
                                >
                                <span
                                    class="mt-0.5 block text-xs text-muted-foreground"
                                    >{{
                                        formatDate(enrollment.enrolled_at)
                                    }}</span
                                >
                            </span>
                        </Link>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Latest reviews -->
        <div class="rounded-xl border bg-card text-card-foreground">
            <div class="p-5 pb-3">
                <h2 class="font-semibold">Ulasan Terbaru</h2>
            </div>
            <p
                v-if="recentReviews.length === 0"
                class="px-5 pb-5 text-sm text-muted-foreground"
            >
                Belum ada ulasan.
            </p>
            <ul
                v-else
                class="grid gap-3 px-5 pb-5 sm:grid-cols-2 xl:grid-cols-3"
            >
                <li v-for="review in recentReviews" :key="review.id">
                    <Link
                        :href="courses.reviews.index(review.course.slug)"
                        class="block h-full rounded-lg border p-4 transition-colors hover:bg-muted/50"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="flex min-w-0 items-center gap-2">
                                <Avatar class="size-7 shrink-0">
                                    <AvatarImage
                                        v-if="review.author.avatar"
                                        :src="review.author.avatar"
                                        :alt="review.author.name"
                                    />
                                    <AvatarFallback class="text-[10px]">{{
                                        getInitials(review.author.name)
                                    }}</AvatarFallback>
                                </Avatar>
                                <span class="truncate text-sm font-medium">{{
                                    review.author.name
                                }}</span>
                            </span>
                            <StarRating :rating="review.rating" />
                        </div>
                        <p
                            class="mt-2 line-clamp-2 text-sm"
                            :class="
                                review.comment
                                    ? ''
                                    : 'text-muted-foreground italic'
                            "
                        >
                            {{ review.comment ?? 'Hanya memberi rating.' }}
                        </p>
                        <p class="mt-2 truncate text-xs text-muted-foreground">
                            {{ review.course.title }} ·
                            {{ formatDate(review.created_at) }}
                        </p>
                    </Link>
                </li>
            </ul>
        </div>
    </div>
</template>
