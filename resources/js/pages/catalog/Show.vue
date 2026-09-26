<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    CircleCheck,
    Clock,
    FileText,
    ListChecks,
    PlayCircle,
    Users,
    Video,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import CourseReviews from '@/components/CourseReviews.vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import {
    formatDate,
    formatDuration,
    formatPrice,
    formatTimeLimit,
    levelLabel,
} from '@/lib/course';
import { login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import courses from '@/routes/courses';
import learn from '@/routes/learn';
import users from '@/routes/users';
import type {
    CatalogCourseDetail,
    CatalogSection,
    CourseReviewEntry,
    OwnEnrollment,
    RatingSummary,
} from '@/types';

const props = defineProps<{
    course: CatalogCourseDetail;
    sections: CatalogSection[];
    enrollment: OwnEnrollment | null;
    rating: RatingSummary;
    reviews: CourseReviewEntry[];
    myReview: { id: number; rating: number; comment: string | null } | null;
    can: {
        enroll: boolean;
        cancel: boolean;
        manage: boolean;
        learn: boolean;
        review: boolean;
    };
}>();

const enrolling = ref(false);
const cancelDialogOpen = ref(false);

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const canRegister = computed(() => page.props.canRegister);

const isEnrolled = computed(() => props.enrollment?.status === 'active');

const lessonCount = computed(() =>
    props.sections.reduce(
        (count, section) => count + section.lessons.length,
        0,
    ),
);

const quizCount = computed(() =>
    props.sections.reduce(
        (count, section) => count + section.quizzes.length,
        0,
    ),
);

function enroll(): void {
    enrolling.value = true;

    router.post(
        catalogRoutes.enroll(props.course.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                enrolling.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="mx-auto w-full max-w-6xl px-4 py-10 sm:px-6">
        <Head :title="course.title" />

        <div class="grid gap-6 lg:grid-cols-[1fr_320px]">
            <div class="flex min-w-0 flex-col gap-6">
                <div class="flex flex-col gap-3">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">{{
                            levelLabel(course.level)
                        }}</Badge>
                        <span
                            v-if="course.category"
                            class="text-sm text-muted-foreground"
                        >
                            {{ course.category.name }}
                        </span>
                    </div>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-balance"
                    >
                        {{ course.title }}
                    </h1>
                    <div
                        v-if="rating.count > 0"
                        class="flex items-center gap-2 text-sm"
                    >
                        <span class="font-semibold">{{
                            rating.average?.toFixed(1)
                        }}</span>
                        <StarRating :rating="rating.average" />
                        <a
                            href="#reviews-heading"
                            class="text-muted-foreground hover:underline"
                        >
                            ({{ rating.count }} rating(s))
                        </a>
                    </div>
                    <p
                        v-if="course.description"
                        class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
                    >
                        {{ course.description }}
                    </p>

                    <Link
                        v-if="course.instructor"
                        :href="users.show(course.instructor.id)"
                        class="flex w-fit items-center gap-3 rounded-md py-1"
                    >
                        <Avatar class="size-10 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="course.instructor.avatar"
                                :src="course.instructor.avatar"
                                :alt="course.instructor.name"
                            />
                            <AvatarFallback>
                                {{ getInitials(course.instructor.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="flex flex-col text-sm">
                            <span class="font-medium hover:underline">
                                {{ course.instructor.name }}
                            </span>
                            <span
                                v-if="course.instructor.headline"
                                class="text-xs text-muted-foreground"
                            >
                                {{ course.instructor.headline }}
                            </span>
                        </div>
                    </Link>
                </div>

                <section class="flex flex-col gap-3">
                    <div class="flex items-baseline justify-between gap-4">
                        <h2 class="text-lg font-semibold">Course content</h2>
                        <span class="text-sm text-muted-foreground">
                            {{ sections.length }} section(s) ·
                            {{ lessonCount }} lesson(s) ·
                            {{ quizCount }} quiz(zes)
                        </span>
                    </div>

                    <p
                        v-if="sections.length === 0"
                        class="rounded-lg border p-6 text-center text-sm text-muted-foreground"
                    >
                        The curriculum has not been published yet.
                    </p>

                    <div
                        v-for="(section, sectionIndex) in sections"
                        :key="section.id"
                        class="rounded-lg border"
                    >
                        <div class="border-b bg-muted/30 px-4 py-3">
                            <h3 class="text-sm font-medium">
                                {{ sectionIndex + 1 }}. {{ section.title }}
                            </h3>
                        </div>
                        <ul class="divide-y">
                            <li
                                v-for="lesson in section.lessons"
                                :key="`lesson-${lesson.id}`"
                                class="flex items-center justify-between gap-3 px-4 py-2 text-sm"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <Video
                                        v-if="lesson.content_type === 'video'"
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <FileText
                                        v-else
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <Link
                                        v-if="can.learn"
                                        :href="
                                            learn.lessons.show({
                                                course: course.id,
                                                lesson: lesson.id,
                                            })
                                        "
                                        class="truncate hover:underline"
                                    >
                                        {{ lesson.title }}
                                    </Link>
                                    <span v-else class="truncate">{{
                                        lesson.title
                                    }}</span>
                                </span>
                                <span
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    {{
                                        formatDuration(lesson.duration_minutes)
                                    }}
                                </span>
                            </li>
                            <li
                                v-for="quiz in section.quizzes"
                                :key="`quiz-${quiz.id}`"
                                class="flex items-center justify-between gap-3 px-4 py-2 text-sm"
                            >
                                <span class="flex min-w-0 items-center gap-2">
                                    <ListChecks
                                        class="h-4 w-4 shrink-0 text-muted-foreground"
                                    />
                                    <Link
                                        v-if="can.learn"
                                        :href="
                                            learn.quizzes.show({
                                                course: course.id,
                                                quiz: quiz.id,
                                            })
                                        "
                                        class="truncate hover:underline"
                                    >
                                        {{ quiz.title }}
                                    </Link>
                                    <span v-else class="truncate">{{
                                        quiz.title
                                    }}</span>
                                </span>
                                <span
                                    class="flex shrink-0 items-center gap-1 text-xs text-muted-foreground"
                                >
                                    {{ quiz.questions_count }} question(s)
                                    <template
                                        v-if="quiz.time_limit_minutes !== null"
                                    >
                                        ·
                                        <Clock class="h-3 w-3" />
                                        {{
                                            formatTimeLimit(
                                                quiz.time_limit_minutes,
                                            )
                                        }}
                                    </template>
                                </span>
                            </li>
                            <li
                                v-if="
                                    section.lessons.length === 0 &&
                                    section.quizzes.length === 0
                                "
                                class="px-4 py-2 text-sm text-muted-foreground"
                            >
                                No content yet.
                            </li>
                        </ul>
                    </div>
                </section>

                <CourseReviews
                    :course-id="course.id"
                    :rating="rating"
                    :reviews="reviews"
                    :my-review="myReview"
                    :can-review="can.review"
                />
            </div>

            <aside class="flex flex-col gap-4 lg:sticky lg:top-4 lg:self-start">
                <div class="overflow-hidden rounded-lg border">
                    <img
                        v-if="course.thumbnail_url"
                        :src="course.thumbnail_url"
                        :alt="course.title"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
                    >
                        <BookOpen class="size-10" />
                    </div>

                    <div class="flex flex-col gap-4 p-4">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xl font-semibold">
                                {{ formatPrice(course.price) }}
                            </span>
                            <span
                                class="flex items-center gap-1 text-sm text-muted-foreground"
                            >
                                <Users class="h-4 w-4" />
                                {{ course.students_count }} student(s)
                            </span>
                        </div>

                        <template v-if="isEnrolled && enrollment">
                            <div
                                class="flex items-center gap-2 rounded-md bg-primary/10 p-3 text-sm"
                            >
                                <CircleCheck
                                    class="h-4 w-4 shrink-0 text-primary"
                                />
                                <span>
                                    You enrolled on
                                    {{ formatDate(enrollment.enrolled_at) }}.
                                </span>
                            </div>
                            <Link :href="learn.show(course.id)">
                                <Button class="w-full">
                                    <PlayCircle class="mr-2 h-4 w-4" />
                                    Go to course
                                </Button>
                            </Link>
                            <Button
                                v-if="can.cancel"
                                variant="ghost"
                                class="text-destructive"
                                @click="cancelDialogOpen = true"
                            >
                                Cancel enrollment
                            </Button>
                        </template>

                        <template v-else>
                            <p
                                v-if="enrollment?.status === 'cancelled'"
                                class="text-xs text-muted-foreground"
                            >
                                Your previous enrollment was cancelled on
                                {{ formatDate(enrollment.cancelled_at) }}.
                            </p>
                            <Button
                                v-if="can.enroll"
                                :disabled="enrolling"
                                @click="enroll"
                            >
                                {{
                                    enrolling
                                        ? 'Enrolling...'
                                        : enrollment
                                          ? 'Enroll again'
                                          : 'Enroll now'
                                }}
                            </Button>
                            <template v-else-if="!isAuthenticated">
                                <Link :href="login()">
                                    <Button class="w-full">
                                        Log in to enroll
                                    </Button>
                                </Link>
                                <p
                                    v-if="canRegister"
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    New here?
                                    <Link
                                        :href="register()"
                                        class="font-medium text-foreground underline-offset-4 hover:underline"
                                    >
                                        Create a free account
                                    </Link>
                                </p>
                            </template>
                            <p v-else class="text-xs text-muted-foreground">
                                Only students can enroll in published courses.
                            </p>
                        </template>

                        <Link
                            v-if="can.manage && !isEnrolled"
                            :href="learn.show(course.id)"
                        >
                            <Button variant="outline" class="w-full">
                                <PlayCircle class="mr-2 h-4 w-4" />
                                Preview as student
                            </Button>
                        </Link>
                        <Link
                            v-if="can.manage"
                            :href="courses.show(course.id)"
                            class="text-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        >
                            Manage this course in the dashboard
                        </Link>
                    </div>
                </div>
            </aside>
        </div>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="enrollment?.id ?? null"
            :description="`You will lose access to ${course.title}. You can enroll again later while the course is published.`"
        />
    </div>
</template>
