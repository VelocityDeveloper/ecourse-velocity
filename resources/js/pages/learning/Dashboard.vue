<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    BookOpen,
    CircleCheck,
    Compass,
    GraduationCap,
    ListChecks,
    NotebookPen,
    PlayCircle,
    Trophy,
} from '@lucide/vue';
import { computed } from 'vue';
import LearningNav from '@/components/LearningNav.vue';
import { Button } from '@/components/ui/button';
import { formatDate, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import learn from '@/routes/learn';
import learning from '@/routes/learning';
import type {
    LearningActivity,
    LearningCourseCard,
    LearningStats,
} from '@/types';

const props = defineProps<{
    resume: LearningCourseCard | null;
    stats: LearningStats;
    courses: LearningCourseCard[];
    activity: LearningActivity[];
}>();

const page = usePage();
const firstName = computed(
    () => page.props.auth.user?.name.split(' ')[0] ?? 'there',
);

const statItems = computed(() => [
    {
        label: 'Active courses',
        value: String(props.stats.active_courses),
        Icon: BookOpen,
        accent: 'bg-chart-2/10 text-chart-2',
    },
    {
        label: 'Courses completed',
        value: String(props.stats.completed_courses),
        Icon: GraduationCap,
        accent: 'bg-chart-1/10 text-chart-1',
    },
    {
        label: 'Lessons finished',
        value: String(props.stats.lessons_completed),
        Icon: CircleCheck,
        accent: 'bg-chart-3/10 text-chart-3',
    },
    {
        label: 'Average quiz score',
        value:
            props.stats.average_quiz_score === null
                ? '-'
                : `${props.stats.average_quiz_score}%`,
        Icon: Trophy,
        accent: 'bg-chart-4/15 text-chart-4',
    },
]);

function resumeHref(card: LearningCourseCard) {
    return card.last_lesson
        ? learn.lessons.show({
              course: card.course.id,
              lesson: card.last_lesson.id,
          })
        : learn.show(card.course.id);
}

function activityHref(item: LearningActivity) {
    return item.type === 'quiz'
        ? learn.attempts.show(item.target_id)
        : learn.lessons.show({
              course: item.course.id,
              lesson: item.target_id,
          });
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6"
    >
        <Head title="My Learning" />

        <div class="space-y-6">
            <div class="max-w-2xl space-y-2">
                <p class="text-sm font-semibold text-primary">My learning</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    Welcome back, {{ firstName }}
                </h1>
                <p class="text-muted-foreground">
                    Pick up where you left off and keep your streak going.
                </p>
            </div>
            <LearningNav />
        </div>

        <!-- Resume -->
        <section
            v-if="resume"
            class="grid overflow-hidden rounded-xl border bg-card text-card-foreground md:grid-cols-[280px_1fr]"
        >
            <img
                v-if="resume.course.thumbnail_url"
                :src="resume.course.thumbnail_url"
                :alt="resume.course.title"
                class="aspect-video h-full w-full object-cover"
            />
            <div
                v-else
                class="flex aspect-video items-center justify-center bg-muted text-muted-foreground"
            >
                <BookOpen class="size-10" />
            </div>
            <div class="flex flex-col justify-center gap-4 p-6">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-primary">
                        Continue learning
                    </p>
                    <h2 class="text-xl font-semibold tracking-tight">
                        {{ resume.course.title }}
                    </h2>
                    <p
                        v-if="resume.last_lesson"
                        class="text-sm text-muted-foreground"
                    >
                        Last opened: {{ resume.last_lesson.title }}
                    </p>
                </div>
                <div class="max-w-md space-y-1.5">
                    <div
                        class="h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="resume.progress.percent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Course progress"
                    >
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${resume.progress.percent}%` }"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ resume.progress.completed }} of
                        {{ resume.progress.total }} complete ·
                        {{ resume.progress.percent }}%
                    </p>
                </div>
                <Link :href="resumeHref(resume)" class="w-fit">
                    <Button>
                        <PlayCircle class="mr-2 h-4 w-4" />
                        Resume
                    </Button>
                </Link>
            </div>
        </section>

        <section
            v-else
            class="flex flex-col items-center gap-3 rounded-lg border border-dashed p-10 text-center"
        >
            <Compass class="size-10 text-muted-foreground" />
            <p class="font-medium">
                {{
                    stats.active_courses === 0
                        ? 'You are not enrolled in any course yet'
                        : 'You have finished all of your courses'
                }}
            </p>
            <p class="text-sm text-muted-foreground">
                Find something new to learn in the catalog.
            </p>
            <Link :href="catalogRoutes.index()">
                <Button>Browse courses</Button>
            </Link>
        </section>

        <!-- Stats -->
        <section class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="item in statItems"
                :key="item.label"
                class="flex items-center gap-3 rounded-lg border p-4"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-lg"
                    :class="item.accent"
                >
                    <component :is="item.Icon" class="h-5 w-5" />
                </span>
                <div class="min-w-0">
                    <p class="text-2xl font-semibold tracking-tight">
                        {{ item.value }}
                    </p>
                    <p class="truncate text-sm text-muted-foreground">
                        {{ item.label }}
                    </p>
                </div>
            </div>
        </section>

        <div class="grid gap-8 lg:grid-cols-[1fr_340px]">
            <!-- Courses -->
            <section class="space-y-4">
                <div class="flex items-center justify-between gap-4">
                    <h2 class="text-lg font-semibold">Your courses</h2>
                    <Link
                        :href="catalogRoutes.index()"
                        class="text-sm text-muted-foreground hover:text-foreground"
                    >
                        Find more
                    </Link>
                </div>
                <p
                    v-if="courses.length === 0"
                    class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    Courses you enroll in will show up here.
                </p>
                <Link
                    v-for="card in courses"
                    :key="card.course.id"
                    :href="resumeHref(card)"
                    class="flex items-center gap-4 rounded-lg border bg-card p-4 text-card-foreground transition-colors hover:bg-muted/40"
                >
                    <img
                        v-if="card.course.thumbnail_url"
                        :src="card.course.thumbnail_url"
                        :alt="card.course.title"
                        class="hidden h-16 w-28 shrink-0 rounded object-cover sm:block"
                    />
                    <div class="min-w-0 flex-1 space-y-2">
                        <div>
                            <p class="truncate font-medium">
                                {{ card.course.title }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ levelLabel(card.course.level) }}
                                <template v-if="card.course.instructor">
                                    · {{ card.course.instructor }}
                                </template>
                                <template v-if="card.last_accessed_at">
                                    · Last active
                                    {{ formatDate(card.last_accessed_at) }}
                                </template>
                            </p>
                        </div>
                        <div class="flex items-center gap-3">
                            <div
                                class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{
                                        width: `${card.progress.percent}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="w-10 text-right text-xs text-muted-foreground tabular-nums"
                            >
                                {{ card.progress.percent }}%
                            </span>
                        </div>
                    </div>
                </Link>
            </section>

            <!-- Side column -->
            <div class="space-y-8">
                <section class="space-y-4">
                    <h2 class="text-lg font-semibold">Recent activity</h2>
                    <p
                        v-if="activity.length === 0"
                        class="text-sm text-muted-foreground"
                    >
                        Finish a lesson or a quiz to see it here.
                    </p>
                    <ol v-else class="space-y-1">
                        <li
                            v-for="item in activity"
                            :key="`${item.type}-${item.target_id}`"
                        >
                            <Link
                                :href="activityHref(item)"
                                class="flex items-start gap-3 rounded-md p-2 text-sm transition-colors hover:bg-muted/50"
                            >
                                <CircleCheck
                                    v-if="item.type === 'lesson'"
                                    class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                />
                                <ListChecks
                                    v-else
                                    class="mt-0.5 h-4 w-4 shrink-0 text-chart-2"
                                />
                                <span class="min-w-0 flex-1">
                                    <span class="line-clamp-1 font-medium">
                                        {{ item.title }}
                                    </span>
                                    <span
                                        class="line-clamp-1 text-xs text-muted-foreground"
                                    >
                                        {{
                                            item.type === 'quiz'
                                                ? `Scored ${item.score ?? 0}/${item.max_score}`
                                                : 'Lesson completed'
                                        }}
                                        · {{ item.course.title }}
                                    </span>
                                </span>
                                <span
                                    class="shrink-0 text-xs text-muted-foreground"
                                >
                                    {{ formatDate(item.at) }}
                                </span>
                            </Link>
                        </li>
                    </ol>
                </section>

                <section class="grid grid-cols-2 gap-3">
                    <Link
                        :href="learning.notes()"
                        class="flex flex-col gap-2 rounded-lg border p-4 transition-colors hover:bg-muted/40"
                    >
                        <NotebookPen class="h-5 w-5 text-muted-foreground" />
                        <span class="text-2xl font-semibold tracking-tight">
                            {{ stats.notes }}
                        </span>
                        <span class="text-sm text-muted-foreground">Notes</span>
                    </Link>
                    <Link
                        :href="learning.bookmarks()"
                        class="flex flex-col gap-2 rounded-lg border p-4 transition-colors hover:bg-muted/40"
                    >
                        <Bookmark class="h-5 w-5 text-muted-foreground" />
                        <span class="text-2xl font-semibold tracking-tight">
                            {{ stats.bookmarks }}
                        </span>
                        <span class="text-sm text-muted-foreground">
                            Bookmarks
                        </span>
                    </Link>
                </section>
            </div>
        </div>
    </div>
</template>
