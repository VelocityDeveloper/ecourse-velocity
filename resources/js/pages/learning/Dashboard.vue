<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Bookmark,
    BookOpen,
    ArrowRight,
    CircleCheck,
    Compass,
    GraduationCap,
    ListChecks,
    NotebookPen,
    PlayCircle,
    Trophy,
} from '@lucide/vue';
import { computed } from 'vue';
import LearningHeader from '@/components/LearningHeader.vue';
import { Button } from '@/components/ui/button';
import { formatDate, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import learn from '@/routes/learn';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';
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
    () => page.props.auth.user?.name.split(' ')[0] ?? '',
);

const statItems = computed(() => [
    {
        label: 'Kursus aktif',
        value: String(props.stats.active_courses),
        Icon: BookOpen,
        accent: 'bg-chart-2/10 text-chart-2',
    },
    {
        label: 'Kursus selesai',
        value: String(props.stats.completed_courses),
        Icon: GraduationCap,
        accent: 'bg-chart-1/10 text-chart-1',
    },
    {
        label: 'Materi selesai',
        value: String(props.stats.lessons_completed),
        Icon: CircleCheck,
        accent: 'bg-chart-3/10 text-chart-3',
    },
    {
        label: 'Rata-rata nilai kuis',
        value:
            props.stats.average_quiz_score === null
                ? '-'
                : `${props.stats.average_quiz_score}%`,
        Icon: Trophy,
        accent: 'bg-rating/15 text-rating',
    },
]);

function resumeHref(card: LearningCourseCard) {
    return card.last_lesson
        ? learn.lessons.show({
              course: card.course.slug,
              lesson: card.last_lesson.slug,
          })
        : learn.show(card.course.slug);
}

function activityHref(item: LearningActivity) {
    return item.type === 'quiz'
        ? learn.attempts.show(item.target_id)
        : learn.lessons.show({
              course: item.course.slug,
              lesson: item.target_slug,
          });
}
</script>

<template>
    <div>
        <Head title="Belajar Saya" />

        <LearningHeader
            :title="`Selamat datang kembali${firstName ? `, ${firstName}` : ''}`"
            description="Lanjutkan dari bagian terakhir dan pertahankan semangat belajar Anda."
        >
            <template #actions>
                <Link :href="catalogRoutes.index()">
                    <Button variant="secondary" class="rounded-xl font-bold">
                        <Compass class="mr-2 h-4 w-4" />
                        Jelajahi kursus
                    </Button>
                </Link>
            </template>
        </LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-8 px-4 py-10 sm:px-6"
        >
            <!-- Resume -->
            <section
                v-if="resume"
                class="grid overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm md:grid-cols-[340px_1fr]"
                aria-labelledby="resume-heading"
            >
                <Link :href="resumeHref(resume)" class="block bg-muted">
                    <img
                        v-if="resume.course.thumbnail_url"
                        :src="resume.course.thumbnail_url"
                        :alt="resume.course.title"
                        class="aspect-video size-full object-cover object-left"
                    />
                    <div
                        v-else
                        class="flex aspect-video size-full items-center justify-center text-muted-foreground"
                    >
                        <BookOpen class="size-10" />
                    </div>
                </Link>
                <div class="flex flex-col justify-center gap-5 p-6 sm:p-8">
                    <div class="space-y-1.5">
                        <p
                            class="text-xs font-bold tracking-wider text-primary uppercase"
                        >
                            Lanjutkan belajar
                        </p>
                        <h2
                            id="resume-heading"
                            class="text-2xl font-extrabold tracking-tight"
                        >
                            {{ resume.course.title }}
                        </h2>
                        <p
                            v-if="resume.last_lesson"
                            class="text-sm text-muted-foreground"
                        >
                            Terakhir dibuka:
                            <span class="font-semibold text-foreground">{{
                                resume.last_lesson.title
                            }}</span>
                        </p>
                    </div>
                    <div class="max-w-lg space-y-2">
                        <div class="flex items-center justify-between text-sm">
                            <span class="text-muted-foreground">
                                {{ resume.progress.completed }} dari
                                {{ resume.progress.total }} selesai
                            </span>
                            <span class="font-bold text-primary tabular-nums"
                                >{{ resume.progress.percent }}%</span
                            >
                        </div>
                        <div
                            class="h-2.5 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            :aria-valuenow="resume.progress.percent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            aria-label="Progres kursus"
                        >
                            <div
                                class="h-full rounded-full bg-gradient-to-r from-primary to-brand"
                                :style="{
                                    width: `${resume.progress.percent}%`,
                                }"
                            />
                        </div>
                    </div>
                    <Link :href="resumeHref(resume)" class="w-fit">
                        <Button
                            size="lg"
                            class="rounded-xl font-bold shadow-lg shadow-primary/25"
                        >
                            <PlayCircle class="mr-2 h-4 w-4" />
                            Lanjutkan
                        </Button>
                    </Link>
                </div>
            </section>

            <section
                v-else
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card p-10 text-center"
            >
                <Compass class="size-10 text-muted-foreground" />
                <p class="text-lg font-bold">
                    {{
                        stats.active_courses === 0
                            ? 'Anda belum terdaftar di kursus mana pun'
                            : 'Anda telah menyelesaikan semua kursus Anda'
                    }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Temukan hal baru untuk dipelajari di katalog.
                </p>
                <Link :href="catalogRoutes.index()">
                    <Button class="rounded-xl font-bold"
                        >Jelajahi kursus</Button
                    >
                </Link>
            </section>

            <!-- Stats -->
            <section
                class="grid grid-cols-2 gap-4 lg:grid-cols-4"
                aria-label="Statistik belajar"
            >
                <div
                    v-for="item in statItems"
                    :key="item.label"
                    class="flex items-center gap-4 rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
                >
                    <span
                        class="flex size-12 shrink-0 items-center justify-center rounded-xl"
                        :class="item.accent"
                    >
                        <component :is="item.Icon" class="h-6 w-6" />
                    </span>
                    <div class="min-w-0">
                        <p class="text-3xl font-extrabold tracking-tight">
                            {{ item.value }}
                        </p>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ item.label }}
                        </p>
                    </div>
                </div>
            </section>

            <div class="grid items-start gap-8 lg:grid-cols-[1fr_360px]">
                <!-- Courses -->
                <section class="space-y-4" aria-labelledby="courses-heading">
                    <div class="flex items-center justify-between gap-4">
                        <h2
                            id="courses-heading"
                            class="flex items-center gap-3 text-xl font-bold tracking-tight"
                        >
                            <span
                                class="h-6 w-1 rounded-full bg-primary"
                                aria-hidden="true"
                            />
                            Kursus Anda
                        </h2>
                        <Link
                            :href="myCourses.index()"
                            class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline"
                        >
                            Lihat semua
                            <ArrowRight class="h-4 w-4" />
                        </Link>
                    </div>
                    <p
                        v-if="courses.length === 0"
                        class="rounded-2xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        Kursus yang Anda ikuti akan muncul di sini.
                    </p>
                    <Link
                        v-for="card in courses"
                        :key="card.course.id"
                        :href="resumeHref(card)"
                        class="group flex items-center gap-4 rounded-2xl border bg-card p-4 text-card-foreground shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary/30 hover:shadow-md"
                    >
                        <img
                            v-if="card.course.thumbnail_url"
                            :src="card.course.thumbnail_url"
                            :alt="card.course.title"
                            class="hidden aspect-video w-32 shrink-0 rounded-xl object-cover sm:block"
                        />
                        <div class="min-w-0 flex-1 space-y-2.5">
                            <div>
                                <p
                                    class="truncate font-bold transition-colors group-hover:text-primary"
                                >
                                    {{ card.course.title }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ levelLabel(card.course.level) }}
                                    <template v-if="card.course.instructor">
                                        · {{ card.course.instructor }}
                                    </template>
                                    <template v-if="card.last_accessed_at">
                                        · Terakhir aktif
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
                                    class="w-10 text-right text-sm font-bold tabular-nums"
                                >
                                    {{ card.progress.percent }}%
                                </span>
                            </div>
                        </div>
                        <PlayCircle
                            class="hidden h-8 w-8 shrink-0 text-muted-foreground/40 transition-colors group-hover:text-primary sm:block"
                        />
                    </Link>
                </section>

                <!-- Side column -->
                <div class="space-y-4">
                    <section
                        class="rounded-2xl border bg-card text-card-foreground shadow-sm"
                        aria-labelledby="activity-heading"
                    >
                        <h2
                            id="activity-heading"
                            class="flex items-center gap-3 border-b px-5 py-4 text-base font-bold"
                        >
                            <span
                                class="h-5 w-1 rounded-full bg-primary"
                                aria-hidden="true"
                            />
                            Aktivitas terbaru
                        </h2>
                        <p
                            v-if="activity.length === 0"
                            class="p-5 text-sm text-muted-foreground"
                        >
                            Selesaikan materi atau kuis untuk melihatnya di
                            sini.
                        </p>
                        <ol v-else class="divide-y">
                            <li
                                v-for="item in activity"
                                :key="`${item.type}-${item.target_id}`"
                            >
                                <Link
                                    :href="activityHref(item)"
                                    class="flex items-start gap-3 px-5 py-3 text-sm transition-colors hover:bg-muted/50"
                                >
                                    <span
                                        class="mt-0.5 flex size-7 shrink-0 items-center justify-center rounded-lg"
                                        :class="
                                            item.type === 'lesson'
                                                ? 'bg-primary/10 text-primary'
                                                : 'bg-chart-2/10 text-chart-2'
                                        "
                                    >
                                        <CircleCheck
                                            v-if="item.type === 'lesson'"
                                            class="h-4 w-4"
                                        />
                                        <ListChecks v-else class="h-4 w-4" />
                                    </span>
                                    <span class="min-w-0 flex-1">
                                        <span
                                            class="line-clamp-1 font-semibold"
                                        >
                                            {{ item.title }}
                                        </span>
                                        <span
                                            class="line-clamp-1 text-xs text-muted-foreground"
                                        >
                                            {{
                                                item.type === 'quiz'
                                                    ? `Nilai ${item.score ?? 0}/${item.max_score}`
                                                    : 'Materi selesai'
                                            }}
                                            · {{ item.course.title }}
                                        </span>
                                    </span>
                                    <span
                                        class="shrink-0 pt-0.5 text-xs text-muted-foreground"
                                    >
                                        {{ formatDate(item.at) }}
                                    </span>
                                </Link>
                            </li>
                        </ol>
                    </section>

                    <section class="grid grid-cols-2 gap-4">
                        <Link
                            v-for="shortcut in [
                                {
                                    label: 'Catatan',
                                    value: stats.notes,
                                    href: learning.notes(),
                                    Icon: NotebookPen,
                                },
                                {
                                    label: 'Markah',
                                    value: stats.bookmarks,
                                    href: learning.bookmarks(),
                                    Icon: Bookmark,
                                },
                            ]"
                            :key="shortcut.label"
                            :href="shortcut.href"
                            class="group flex flex-col gap-3 rounded-2xl border bg-card p-5 text-card-foreground shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary/30"
                        >
                            <span
                                class="flex size-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                            >
                                <component
                                    :is="shortcut.Icon"
                                    class="h-5 w-5"
                                />
                            </span>
                            <span>
                                <span
                                    class="block text-2xl font-extrabold tracking-tight"
                                    >{{ shortcut.value }}</span
                                >
                                <span
                                    class="flex items-center gap-1 text-sm text-muted-foreground group-hover:text-foreground"
                                    >{{ shortcut.label }}
                                    <ArrowRight class="h-3.5 w-3.5"
                                /></span>
                            </span>
                        </Link>
                    </section>
                </div>
            </div>
        </div>
    </div>
</template>
