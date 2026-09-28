<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Clock, ListChecks, Play, RotateCcw, Trophy } from '@lucide/vue';
import { computed, ref } from 'vue';
import LearnPager from '@/components/LearnPager.vue';
import LearnShell from '@/components/LearnShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    formatTimeLimit,
    passStatusLabel,
    passStatusVariant,
} from '@/lib/course';
import learn from '@/routes/learn';
import type {
    LearningOutline,
    LearnQuiz,
    OutlineNeighbours,
    QuizAttemptSummary,
} from '@/types';

const props = defineProps<{
    outline: LearningOutline;
    quiz: LearnQuiz;
    attempts: QuizAttemptSummary[];
    bestScore: number | null;
    bestPercent: number | null;
    openAttemptId: number | null;
    neighbours: OutlineNeighbours;
}>();

const starting = ref(false);
const courseSlug = computed(() => props.outline.course.slug);

function startQuiz(): void {
    if (props.openAttemptId !== null) {
        router.visit(learn.attempts.show(props.openAttemptId));

        return;
    }

    starting.value = true;

    router.post(
        learn.quizzes.start({ course: courseSlug.value, quiz: props.quiz.slug })
            .url,
        {},
        {
            onFinish: () => {
                starting.value = false;
            },
        },
    );
}

function formatDateTime(date: string | null): string {
    if (date === null) {
        return '-';
    }

    return new Date(date).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}
</script>

<template>
    <LearnShell :outline="outline" active-type="quiz" :active-id="quiz.id">
        <Head :title="`${quiz.title} · ${outline.course.title}`" />

        <header class="space-y-2">
            <p class="text-sm font-semibold text-primary">
                {{ quiz.section_title }}
            </p>
            <h1 class="text-3xl font-semibold tracking-tight text-balance">
                {{ quiz.title }}
            </h1>
            <p
                v-if="quiz.description"
                class="max-w-2xl whitespace-pre-line text-muted-foreground"
            >
                {{ quiz.description }}
            </p>
        </header>

        <div class="grid gap-4 sm:grid-cols-3">
            <div class="flex items-center gap-3 rounded-lg border p-4">
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-chart-2/10 text-chart-2"
                >
                    <ListChecks class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">
                        {{ quiz.questions_count }}
                    </p>
                    <p class="text-sm text-muted-foreground">Soal</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-lg border p-4">
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-chart-1/10 text-chart-1"
                >
                    <Clock class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">
                        {{
                            quiz.time_limit_minutes === null
                                ? '∞'
                                : formatTimeLimit(quiz.time_limit_minutes)
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">Batas waktu</p>
                </div>
            </div>
            <div class="flex items-center gap-3 rounded-lg border p-4">
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-chart-4/15 text-chart-4"
                >
                    <Trophy class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-2xl font-semibold tracking-tight">
                        {{ bestScore ?? '-' }}
                        <span
                            class="text-base font-normal text-muted-foreground"
                        >
                            / {{ quiz.max_score }}
                        </span>
                    </p>
                    <p class="text-sm text-muted-foreground">Nilai terbaik</p>
                </div>
            </div>
        </div>

        <p
            v-if="quiz.passing_score !== null"
            class="flex flex-wrap items-center gap-2 text-sm text-muted-foreground"
        >
            Nilai minimum lulus (KKM):
            <span class="font-medium text-foreground"
                >{{ quiz.passing_score }}%</span
            >
            <template v-if="bestPercent !== null">
                · nilai terbaik Anda {{ bestPercent }}%
                <Badge
                    :variant="
                        passStatusVariant(bestPercent >= quiz.passing_score)
                    "
                >
                    {{ passStatusLabel(bestPercent >= quiz.passing_score) }}
                </Badge>
            </template>
        </p>

        <div
            class="flex flex-col items-start gap-4 rounded-lg border bg-muted/30 p-6 sm:flex-row sm:items-center sm:justify-between"
        >
            <div class="space-y-1">
                <p class="font-medium">
                    {{
                        openAttemptId !== null
                            ? 'Anda memiliki percobaan yang sedang berjalan'
                            : attempts.length > 0
                              ? 'Ingin meningkatkan nilai Anda?'
                              : 'Mulai kapan pun Anda siap'
                    }}
                </p>
                <p class="text-sm text-muted-foreground">
                    <template v-if="quiz.time_limit_minutes !== null">
                        Waktu mulai berjalan begitu Anda memulai dan tetap
                        berjalan meskipun Anda meninggalkan halaman. Jawaban
                        dikirim otomatis saat waktu habis.
                    </template>
                    <template v-else>
                        Tidak ada batas waktu. Anda bisa mengulangi kuis ini
                        sesering yang Anda mau.
                    </template>
                </p>
            </div>
            <Button
                size="lg"
                :disabled="starting || quiz.questions_count === 0"
                @click="startQuiz"
            >
                <Play v-if="attempts.length === 0" class="mr-2 h-4 w-4" />
                <RotateCcw v-else class="mr-2 h-4 w-4" />
                {{
                    openAttemptId !== null
                        ? 'Lanjutkan percobaan'
                        : attempts.length > 0
                          ? 'Ulangi kuis'
                          : 'Mulai kuis'
                }}
            </Button>
        </div>

        <section v-if="attempts.length > 0" class="space-y-3">
            <h2 class="text-lg font-semibold">Percobaan Anda</h2>
            <ul class="divide-y rounded-lg border">
                <li v-for="(attempt, index) in attempts" :key="attempt.id">
                    <Link
                        :href="learn.attempts.show(attempt.id)"
                        class="flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-muted/40"
                    >
                        <span class="flex items-center gap-3">
                            <span class="text-muted-foreground">
                                #{{ attempts.length - index }}
                            </span>
                            <span>{{
                                formatDateTime(attempt.submitted_at)
                            }}</span>
                            <Badge v-if="attempt.is_late" variant="destructive">
                                Waktu habis
                            </Badge>
                        </span>
                        <span class="font-semibold">
                            {{ attempt.score }} / {{ attempt.max_score }}
                        </span>
                    </Link>
                </li>
            </ul>
        </section>

        <LearnPager :course-slug="courseSlug" :neighbours="neighbours" />
    </LearnShell>
</template>
