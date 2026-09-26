<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, Check, CircleAlert, X } from '@lucide/vue';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import learn from '@/routes/learn';
import type { AttemptContext, ResultQuestion } from '@/types';

const props = defineProps<
    AttemptContext & {
        questions: ResultQuestion[];
    }
>();

const percent = computed(() =>
    props.attempt.max_score === 0
        ? 0
        : Math.round(
              ((props.attempt.score ?? 0) / props.attempt.max_score) * 100,
          ),
);

const correctCount = computed(
    () => props.questions.filter((question) => question.is_correct).length,
);

const quizHref = computed(() =>
    learn.quizzes.show({ course: props.course.id, quiz: props.quiz.id }),
);
</script>

<template>
    <div class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-8 sm:px-6">
        <Head :title="`${quiz.title} · Result`" />

        <Link
            :href="quizHref"
            class="flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="h-4 w-4" />
            Back to {{ quiz.title }}
        </Link>

        <section
            class="flex flex-col items-center gap-4 rounded-xl border bg-card p-8 text-center text-card-foreground"
        >
            <p class="text-sm font-semibold text-primary">Your score</p>
            <p class="text-5xl font-semibold tracking-tight">
                {{ attempt.score ?? 0 }}
                <span class="text-2xl font-normal text-muted-foreground">
                    / {{ attempt.max_score }}
                </span>
            </p>
            <div class="w-full max-w-xs space-y-1.5">
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full rounded-full bg-primary"
                        :style="{ width: `${percent}%` }"
                    />
                </div>
                <p class="text-sm text-muted-foreground">
                    {{ percent }}% · {{ correctCount }} of
                    {{ questions.length }} fully correct
                </p>
            </div>
            <p
                v-if="attempt.is_late"
                class="flex items-center gap-2 rounded-md bg-destructive/10 px-3 py-2 text-sm text-destructive"
            >
                <CircleAlert class="h-4 w-4" />
                Time ran out before the answers were submitted, so they were not
                counted.
            </p>
            <div class="flex flex-wrap justify-center gap-3 pt-2">
                <Link :href="quizHref">
                    <Button variant="outline">Retake quiz</Button>
                </Link>
                <Link :href="learn.show(course.id)">
                    <Button>Continue course</Button>
                </Link>
            </div>
        </section>

        <section class="space-y-4">
            <h2 class="text-lg font-semibold">Review</h2>
            <article
                v-for="(question, index) in questions"
                :key="question.id"
                class="space-y-3 rounded-lg border bg-card p-5 text-card-foreground"
            >
                <div class="flex items-start justify-between gap-4">
                    <p class="font-medium">
                        <span class="text-muted-foreground"
                            >{{ index + 1 }}.</span
                        >
                        {{ question.question }}
                    </p>
                    <Badge
                        :variant="question.is_correct ? 'default' : 'outline'"
                        class="shrink-0"
                    >
                        {{ question.points }} / {{ question.max_points }} pts
                    </Badge>
                </div>
                <ul class="space-y-2">
                    <li
                        v-for="option in question.options"
                        :key="option.id"
                        class="flex items-center gap-3 rounded-md border p-3 text-sm"
                        :class="{
                            'border-primary bg-primary/5': option.is_correct,
                            'border-destructive bg-destructive/5':
                                option.was_selected && !option.is_correct,
                        }"
                    >
                        <Check
                            v-if="option.is_correct"
                            class="h-4 w-4 shrink-0 text-primary"
                            aria-label="Correct answer"
                        />
                        <X
                            v-else-if="option.was_selected"
                            class="h-4 w-4 shrink-0 text-destructive"
                            aria-label="Wrong answer"
                        />
                        <span v-else class="h-4 w-4 shrink-0" />
                        <span class="flex-1">{{ option.text }}</span>
                        <span
                            v-if="option.was_selected"
                            class="text-xs text-muted-foreground"
                        >
                            Your answer
                        </span>
                    </li>
                </ul>
            </article>
        </section>
    </div>
</template>
