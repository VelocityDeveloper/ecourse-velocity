<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Circle,
    CircleCheck,
    ListChecks,
    MessageCircleQuestion,
} from '@lucide/vue';
import { computed } from 'vue';
import CourseManageLayout from '@/components/CourseManageLayout.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import {
    enrollmentStatusLabel,
    enrollmentStatusVariant,
    formatDate,
} from '@/lib/course';
import courses from '@/routes/courses';
import learn from '@/routes/learn';
import type { EnrollmentStatus, LessonContentType } from '@/types';

const props = defineProps<{
    course: { id: number; slug: string; title: string };
    student: {
        id: number;
        name: string;
        email: string;
        avatar: string | null;
        headline: string | null;
    };
    enrollment: {
        id: number;
        status: EnrollmentStatus;
        enrolled_at: string;
        last_accessed_at: string | null;
        last_lesson: { id: number; title: string } | null;
    };
    sections: Array<{
        id: number;
        title: string;
        lessons: Array<{
            id: number;
            title: string;
            content_type: LessonContentType;
            completed_at: string | null;
        }>;
        quizzes: Array<{
            id: number;
            title: string;
            attempts: number;
            best_score: number | null;
            max_score: number;
            last_submitted_at: string | null;
        }>;
    }>;
    questions: Array<{
        id: number;
        body: string;
        created_at: string | null;
        replies_count: number;
        lesson: { id: number; slug: string; title: string };
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kursus', href: '/dasbor/kursus' },
            { title: 'Progres Siswa', href: '#' },
        ],
    },
});

const totals = computed(() => {
    const lessons = props.sections.flatMap((section) => section.lessons);
    const quizzes = props.sections.flatMap((section) => section.quizzes);
    const done =
        lessons.filter((lesson) => lesson.completed_at !== null).length +
        quizzes.filter((quiz) => quiz.attempts > 0).length;
    const total = lessons.length + quizzes.length;

    return {
        done,
        total,
        percent: total === 0 ? 0 : Math.floor((done / total) * 100),
    };
});
</script>

<template>
    <CourseManageLayout :course="course">
        <div class="flex flex-col space-y-6">
            <Head :title="`${student.name} · ${course.title}`" />

            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    variant="small"
                    :title="student.name"
                    :description="`Progres di ${course.title}`"
                />
                <Link :href="courses.progress.index(course.slug)">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Semua siswa
                    </Button>
                </Link>
            </div>

            <section class="grid gap-4 md:grid-cols-[1fr_1fr]">
                <div class="flex items-center gap-4 rounded-lg border p-4">
                    <Avatar class="size-14 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="student.avatar"
                            :src="student.avatar"
                            :alt="student.name"
                        />
                        <AvatarFallback>{{
                            getInitials(student.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 space-y-1 text-sm">
                        <p class="font-semibold">{{ student.name }}</p>
                        <p class="truncate text-muted-foreground">
                            {{ student.email }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2">
                            <Badge
                                :variant="
                                    enrollmentStatusVariant(enrollment.status)
                                "
                            >
                                {{ enrollmentStatusLabel(enrollment.status) }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                Terdaftar
                                {{ formatDate(enrollment.enrolled_at) }}
                            </span>
                        </div>
                    </div>
                </div>
                <div class="space-y-3 rounded-lg border p-4">
                    <div class="flex items-baseline justify-between">
                        <p class="text-sm font-medium">Progres keseluruhan</p>
                        <p class="text-2xl font-semibold tracking-tight">
                            {{ totals.percent }}%
                        </p>
                    </div>
                    <div
                        class="h-2 overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="totals.percent"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        aria-label="Progres keseluruhan"
                    >
                        <div
                            class="h-full rounded-full bg-primary"
                            :style="{ width: `${totals.percent}%` }"
                        />
                    </div>
                    <p class="text-xs text-muted-foreground">
                        {{ totals.done }} dari {{ totals.total }} item selesai ·
                        Terakhir aktif
                        {{
                            enrollment.last_accessed_at
                                ? formatDate(enrollment.last_accessed_at)
                                : 'belum pernah'
                        }}
                        <template v-if="enrollment.last_lesson">
                            · Terakhir dibuka “{{
                                enrollment.last_lesson.title
                            }}”
                        </template>
                    </p>
                </div>
            </section>

            <section
                v-for="(section, sectionIndex) in sections"
                :key="section.id"
                class="rounded-lg border"
            >
                <h3 class="border-b bg-muted/30 px-4 py-3 text-sm font-medium">
                    {{ sectionIndex + 1 }}. {{ section.title }}
                </h3>
                <ul class="divide-y">
                    <li
                        v-for="lesson in section.lessons"
                        :key="`lesson-${lesson.id}`"
                        class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <CircleCheck
                                v-if="lesson.completed_at"
                                class="h-4 w-4 shrink-0 text-primary"
                                aria-label="Selesai"
                            />
                            <Circle
                                v-else
                                class="h-4 w-4 shrink-0 text-muted-foreground/50"
                                aria-label="Belum selesai"
                            />
                            <span class="truncate">{{ lesson.title }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            {{
                                lesson.completed_at
                                    ? `Selesai ${formatDate(lesson.completed_at)}`
                                    : 'Belum selesai'
                            }}
                        </span>
                    </li>
                    <li
                        v-for="quiz in section.quizzes"
                        :key="`quiz-${quiz.id}`"
                        class="flex items-center justify-between gap-3 px-4 py-2.5 text-sm"
                    >
                        <span class="flex min-w-0 items-center gap-2">
                            <ListChecks
                                class="h-4 w-4 shrink-0"
                                :class="
                                    quiz.attempts > 0
                                        ? 'text-primary'
                                        : 'text-muted-foreground/50'
                                "
                            />
                            <span class="truncate">{{ quiz.title }}</span>
                        </span>
                        <span class="shrink-0 text-xs text-muted-foreground">
                            <template v-if="quiz.attempts > 0">
                                Terbaik {{ quiz.best_score }}/{{
                                    quiz.max_score
                                }}
                                · {{ quiz.attempts }} percobaan
                            </template>
                            <template v-else>Belum dikerjakan</template>
                        </span>
                    </li>
                </ul>
            </section>

            <section class="space-y-3">
                <h3 class="flex items-center gap-2 text-sm font-medium">
                    <MessageCircleQuestion class="h-4 w-4" />
                    Pertanyaan yang diajukan
                </h3>
                <p
                    v-if="questions.length === 0"
                    class="text-sm text-muted-foreground"
                >
                    {{ student.name }} belum mengajukan pertanyaan di kursus
                    ini.
                </p>
                <ul v-else class="divide-y rounded-lg border">
                    <li v-for="question in questions" :key="question.id">
                        <Link
                            :href="
                                learn.lessons.show({
                                    course: course.slug,
                                    lesson: question.lesson.slug,
                                })
                            "
                            class="flex items-start justify-between gap-3 p-4 transition-colors hover:bg-muted/40"
                        >
                            <span class="min-w-0 space-y-1">
                                <span
                                    class="block text-xs text-muted-foreground"
                                >
                                    {{ question.lesson.title }} ·
                                    {{ formatDate(question.created_at) }}
                                </span>
                                <span class="line-clamp-2 block text-sm">
                                    {{ question.body }}
                                </span>
                            </span>
                            <Badge
                                :variant="
                                    question.replies_count > 0
                                        ? 'secondary'
                                        : 'outline'
                                "
                                class="shrink-0"
                            >
                                {{
                                    question.replies_count > 0
                                        ? `${question.replies_count} balasan`
                                        : 'Belum dijawab'
                                }}
                            </Badge>
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </CourseManageLayout>
</template>
