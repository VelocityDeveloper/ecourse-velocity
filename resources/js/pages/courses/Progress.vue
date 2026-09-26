<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, MessageCircleQuestion, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/course';
import courses from '@/routes/courses';
import learn from '@/routes/learn';
import type { StudentProgressRow, UnansweredQuestion } from '@/types';

const props = defineProps<{
    course: { id: number; title: string };
    summary: {
        students: number;
        average_percent: number;
        completed: number;
        inactive: number;
        average_quiz_score: number | null;
    };
    students: StudentProgressRow[];
    unansweredQuestions: UnansweredQuestion[];
    filters: { search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Courses', href: '/courses' },
            { title: 'Student Progress', href: '#' },
        ],
    },
});

const search = ref(props.filters.search ?? '');

const summaryItems = computed(() => [
    { label: 'Enrolled students', value: String(props.summary.students) },
    { label: 'Average progress', value: `${props.summary.average_percent}%` },
    { label: 'Completed the course', value: String(props.summary.completed) },
    {
        label: 'Average quiz score',
        value:
            props.summary.average_quiz_score === null
                ? '-'
                : `${props.summary.average_quiz_score}%`,
    },
]);

function applySearch(): void {
    router.get(
        courses.progress.index(props.course.id).url,
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head :title="`Progress · ${course.title}`" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Student Progress"
                :description="course.title"
            />
            <Link :href="courses.show(course.id)">
                <Button variant="outline" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Back to course
                </Button>
            </Link>
        </div>

        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            <div
                v-for="item in summaryItems"
                :key="item.label"
                class="rounded-lg border p-4"
            >
                <p class="text-2xl font-semibold tracking-tight">
                    {{ item.value }}
                </p>
                <p class="text-sm text-muted-foreground">{{ item.label }}</p>
            </div>
        </div>

        <p v-if="summary.inactive > 0" class="text-sm text-muted-foreground">
            {{ summary.inactive }} student(s) have not opened the course yet.
        </p>

        <form
            class="flex max-w-sm gap-2"
            role="search"
            @submit.prevent="applySearch"
        >
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    aria-label="Search students"
                    placeholder="Search by name or email..."
                    class="pl-9"
                />
            </div>
            <Button variant="outline" type="submit">Search</Button>
        </form>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Student
                            </th>
                            <th
                                class="h-12 min-w-40 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Progress
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Lessons
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Quizzes
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Quiz score
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Last active
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="students.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No enrolled students found.
                            </td>
                        </tr>
                        <tr
                            v-for="row in students"
                            :key="row.enrollment_id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="
                                        courses.progress.show({
                                            course: course.id,
                                            student: row.student.id,
                                        })
                                    "
                                    class="flex items-center gap-3"
                                >
                                    <Avatar
                                        class="size-9 overflow-hidden rounded-full"
                                    >
                                        <AvatarImage
                                            v-if="row.student.avatar"
                                            :src="row.student.avatar"
                                            :alt="row.student.name"
                                        />
                                        <AvatarFallback class="text-xs">
                                            {{ getInitials(row.student.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium hover:underline"
                                        >
                                            {{ row.student.name }}
                                        </span>
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                        >
                                            {{ row.student.email }}
                                        </span>
                                    </span>
                                </Link>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-2">
                                    <div
                                        class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                                        role="progressbar"
                                        :aria-valuenow="row.percent"
                                        aria-valuemin="0"
                                        aria-valuemax="100"
                                        :aria-label="`${row.student.name} progress`"
                                    >
                                        <div
                                            class="h-full rounded-full bg-primary"
                                            :style="{
                                                width: `${row.percent}%`,
                                            }"
                                        />
                                    </div>
                                    <span class="w-10 text-right tabular-nums">
                                        {{ row.percent }}%
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 align-middle tabular-nums">
                                {{ row.lessons_completed }}/{{
                                    row.lessons_total
                                }}
                            </td>
                            <td class="p-4 align-middle tabular-nums">
                                {{ row.quizzes_attempted }}/{{
                                    row.quizzes_total
                                }}
                            </td>
                            <td class="p-4 align-middle tabular-nums">
                                {{
                                    row.quiz_score_percent === null
                                        ? '-'
                                        : `${row.quiz_score_percent}%`
                                }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge
                                    v-if="row.last_accessed_at === null"
                                    variant="outline"
                                >
                                    Not started
                                </Badge>
                                <span v-else>{{
                                    formatDate(row.last_accessed_at)
                                }}</span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <section class="space-y-3">
            <h3 class="flex items-center gap-2 text-sm font-medium">
                <MessageCircleQuestion class="h-4 w-4" />
                Unanswered questions
            </h3>
            <p
                v-if="unansweredQuestions.length === 0"
                class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
            >
                Every question has a reply. Nice work!
            </p>
            <ul v-else class="divide-y rounded-lg border">
                <li v-for="question in unansweredQuestions" :key="question.id">
                    <Link
                        :href="
                            learn.lessons.show({
                                course: question.course_id,
                                lesson: question.lesson.id,
                            })
                        "
                        class="flex items-start gap-3 p-4 transition-colors hover:bg-muted/40"
                    >
                        <Avatar
                            class="size-8 shrink-0 overflow-hidden rounded-full"
                        >
                            <AvatarImage
                                v-if="question.author.avatar"
                                :src="question.author.avatar"
                                :alt="question.author.name"
                            />
                            <AvatarFallback class="text-xs">
                                {{ getInitials(question.author.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="min-w-0 flex-1 space-y-1">
                            <span class="block text-sm">
                                <span class="font-medium">{{
                                    question.author.name
                                }}</span>
                                <span class="text-muted-foreground">
                                    in {{ question.lesson.title }} ·
                                    {{ formatDate(question.created_at) }}
                                </span>
                            </span>
                            <span
                                class="line-clamp-2 block text-sm text-muted-foreground"
                            >
                                {{ question.body }}
                            </span>
                        </span>
                    </Link>
                </li>
            </ul>
        </section>
    </div>
</template>
