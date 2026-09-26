<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, Clock, Send } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, reactive, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { answerModeLabel } from '@/lib/course';
import learn from '@/routes/learn';
import type { AttemptContext, AttemptQuestion } from '@/types';

const props = defineProps<
    AttemptContext & {
        questions: AttemptQuestion[];
    }
>();

const storageKey = `quiz-attempt-${props.attempt.id}`;

/**
 * Answers are kept in the browser as well, so a refresh does not lose them.
 */
function restoreAnswers(): Record<number, number[]> {
    if (typeof window === 'undefined') {
        return {};
    }

    try {
        const saved = window.localStorage.getItem(storageKey);

        return saved ? (JSON.parse(saved) as Record<number, number[]>) : {};
    } catch {
        return {};
    }
}

const answers = reactive<Record<number, number[]>>(restoreAnswers());
const submitting = ref(false);
const confirmOpen = ref(false);

function persistAnswers(): void {
    try {
        window.localStorage.setItem(storageKey, JSON.stringify(answers));
    } catch {
        // Storage is a convenience only; the attempt still works without it.
    }
}

function forgetAnswers(): void {
    try {
        window.localStorage.removeItem(storageKey);
    } catch {
        // Nothing to clean up when storage is unavailable.
    }
}

function isSelected(questionId: number, optionId: number): boolean {
    return (answers[questionId] ?? []).includes(optionId);
}

function chooseSingle(questionId: number, optionId: number): void {
    answers[questionId] = [optionId];
    persistAnswers();
}

function toggleMultiple(
    questionId: number,
    optionId: number,
    checked: boolean,
): void {
    const current = new Set(answers[questionId] ?? []);

    if (checked) {
        current.add(optionId);
    } else {
        current.delete(optionId);
    }

    answers[questionId] = [...current];
    persistAnswers();
}

const answeredCount = computed(
    () =>
        props.questions.filter(
            (question) => (answers[question.id] ?? []).length > 0,
        ).length,
);

const unansweredCount = computed(
    () => props.questions.length - answeredCount.value,
);

// Countdown, measured from the server's remaining time to avoid clock drift.
const secondsLeft = ref<number | null>(props.attempt.seconds_remaining);
let deadline: number | null = null;
let timer: ReturnType<typeof setInterval> | null = null;

const timeLabel = computed(() => {
    if (secondsLeft.value === null) {
        return null;
    }

    const minutes = Math.floor(secondsLeft.value / 60);
    const seconds = secondsLeft.value % 60;

    return `${String(minutes).padStart(2, '0')}:${String(seconds).padStart(2, '0')}`;
});

const isRunningOut = computed(
    () => secondsLeft.value !== null && secondsLeft.value <= 60,
);

function tick(): void {
    if (deadline === null) {
        return;
    }

    secondsLeft.value = Math.max(0, Math.round((deadline - Date.now()) / 1000));

    if (secondsLeft.value === 0) {
        stopTimer();
        submit();
    }
}

function stopTimer(): void {
    if (timer !== null) {
        clearInterval(timer);
        timer = null;
    }
}

onMounted(() => {
    if (props.attempt.seconds_remaining !== null) {
        deadline = Date.now() + props.attempt.seconds_remaining * 1000;
        tick();
        timer = setInterval(tick, 1000);
    }
});

onBeforeUnmount(stopTimer);

function requestSubmit(): void {
    if (unansweredCount.value > 0) {
        confirmOpen.value = true;

        return;
    }

    submit();
}

function submit(): void {
    if (submitting.value) {
        return;
    }

    submitting.value = true;
    confirmOpen.value = false;
    stopTimer();

    router.post(
        learn.attempts.submit(props.attempt.id).url,
        { answers: { ...answers } },
        {
            onSuccess: () => forgetAnswers(),
            onFinish: () => {
                submitting.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="mx-auto w-full max-w-3xl px-4 py-8 sm:px-6">
        <Head :title="`${quiz.title} · Attempt`" />

        <div
            class="sticky top-16 z-30 -mx-4 mb-6 flex items-center justify-between gap-4 border-b bg-background/90 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6"
        >
            <div class="min-w-0">
                <Link
                    :href="
                        learn.quizzes.show({ course: course.id, quiz: quiz.id })
                    "
                    class="flex items-center gap-1 text-xs text-muted-foreground hover:text-foreground"
                >
                    <ArrowLeft class="h-3 w-3" />
                    {{ course.title }}
                </Link>
                <p class="truncate font-semibold">{{ quiz.title }}</p>
            </div>
            <div class="flex shrink-0 items-center gap-3">
                <span class="hidden text-sm text-muted-foreground sm:inline">
                    {{ answeredCount }}/{{ questions.length }} answered
                </span>
                <span
                    v-if="timeLabel"
                    class="flex items-center gap-1.5 rounded-md border px-2.5 py-1 font-mono text-sm tabular-nums"
                    :class="
                        isRunningOut
                            ? 'border-destructive text-destructive'
                            : 'text-foreground'
                    "
                    role="timer"
                    :aria-label="`Time remaining ${timeLabel}`"
                >
                    <Clock class="h-4 w-4" />
                    {{ timeLabel }}
                </span>
            </div>
        </div>

        <form class="space-y-6" @submit.prevent="requestSubmit">
            <fieldset
                v-for="(question, index) in questions"
                :key="question.id"
                class="space-y-4 rounded-lg border bg-card p-5 text-card-foreground"
            >
                <legend class="sr-only">Question {{ index + 1 }}</legend>
                <div class="flex items-start justify-between gap-4">
                    <p class="font-medium">
                        <span class="text-muted-foreground"
                            >{{ index + 1 }}.</span
                        >
                        {{ question.question }}
                    </p>
                    <Badge variant="secondary" class="shrink-0">
                        {{ question.max_points }} pts
                    </Badge>
                </div>
                <p class="text-xs text-muted-foreground">
                    {{
                        question.answer_mode === 'multiple'
                            ? 'Select every correct answer. Wrong picks cancel out right ones.'
                            : answerModeLabel(question.answer_mode)
                    }}
                </p>

                <div
                    :class="
                        question.answer_mode === 'true_false'
                            ? 'grid grid-cols-2 gap-2'
                            : 'space-y-2'
                    "
                >
                    <label
                        v-for="option in question.options"
                        :key="option.id"
                        class="flex cursor-pointer items-center gap-3 rounded-md border p-3 text-sm transition-colors"
                        :class="
                            isSelected(question.id, option.id)
                                ? 'border-primary bg-primary/5'
                                : 'hover:bg-muted/50'
                        "
                    >
                        <Checkbox
                            v-if="question.answer_mode === 'multiple'"
                            :model-value="isSelected(question.id, option.id)"
                            @update:model-value="
                                (checked) =>
                                    toggleMultiple(
                                        question.id,
                                        option.id,
                                        checked === true,
                                    )
                            "
                        />
                        <input
                            v-else
                            type="radio"
                            :name="`question-${question.id}`"
                            class="h-4 w-4 shrink-0 accent-primary"
                            :checked="isSelected(question.id, option.id)"
                            @change="chooseSingle(question.id, option.id)"
                        />
                        <span>{{ option.text }}</span>
                    </label>
                </div>
            </fieldset>

            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 p-4"
            >
                <p class="text-sm text-muted-foreground">
                    {{ answeredCount }} of {{ questions.length }} questions
                    answered.
                </p>
                <Button type="submit" size="lg" :disabled="submitting">
                    <Send class="mr-2 h-4 w-4" />
                    {{ submitting ? 'Submitting...' : 'Submit answers' }}
                </Button>
            </div>
        </form>

        <Dialog v-model:open="confirmOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Submit with unanswered questions?</DialogTitle>
                    <DialogDescription>
                        {{ unansweredCount }} question(s) have no answer and
                        will score zero. You cannot change your answers after
                        submitting.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="confirmOpen = false">
                        Keep answering
                    </Button>
                    <Button :disabled="submitting" @click="submit">
                        Submit anyway
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
