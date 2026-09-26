<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    Check,
    Clock,
    Pencil,
    Plus,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, reactive, ref, watch } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
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
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { answerModeLabel, formatTimeLimit } from '@/lib/course';
import courseRoutes from '@/routes/courses';
import questionRoutes from '@/routes/quiz-questions';
import quizRoutes from '@/routes/quizzes';
import type {
    AnswerMode,
    MoveDirection,
    QuizDetail,
    QuizQuestion,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Quizzes', href: '/quizzes' },
            { title: 'Edit Quiz', href: '/quizzes' },
        ],
    },
});

const props = defineProps<{
    quiz: QuizDetail;
    section: { id: number; title: string };
    course: { id: number; title: string };
    answerModes: AnswerMode[];
    maxOptions: number;
    maxTimeLimitMinutes: number;
}>();

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const quizForm = reactive({
    title: props.quiz.title,
    description: props.quiz.description ?? '',
    time_limit_minutes: props.quiz.time_limit_minutes?.toString() ?? '',
});

const questionDialogOpen = ref(false);
const editingQuestionId = ref<number | null>(null);
const questionForm = reactive({
    question: '',
    answer_mode: 'single' as AnswerMode,
    points: '10',
    options: [] as Array<{ text: string; is_correct: boolean }>,
    scores: [] as string[],
});

const deleteTarget = ref<QuizQuestion | null>(null);

const correctCount = computed(
    () => questionForm.options.filter((option) => option.is_correct).length,
);

watch(correctCount, (count) => {
    if (questionForm.answer_mode !== 'multiple') {
        return;
    }

    questionForm.scores = Array.from(
        { length: count },
        (_, index) => questionForm.scores[index] ?? '',
    );
});

function saveQuiz() {
    processing.value = true;
    errors.value = {};

    router.put(
        quizRoutes.update(props.quiz.id).url,
        { ...quizForm },
        {
            preserveScroll: true,
            onError: (err) => {
                errors.value = err as Record<string, string>;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function openQuestionDialog(question?: QuizQuestion) {
    errors.value = {};
    editingQuestionId.value = question?.id ?? null;
    questionForm.question = question?.question ?? '';
    questionForm.answer_mode = question?.answer_mode ?? 'single';
    questionForm.points = (question?.points ?? 10).toString();
    questionForm.options = question
        ? question.options.map((option) => ({
              text: option.text,
              is_correct: option.is_correct,
          }))
        : [
              { text: '', is_correct: true },
              { text: '', is_correct: false },
          ];
    questionForm.scores = (question?.scores ?? []).map((score) =>
        score.toString(),
    );
    questionDialogOpen.value = true;
}

function addOption() {
    if (questionForm.options.length >= props.maxOptions) {
        return;
    }

    questionForm.options.push({ text: '', is_correct: false });
}

function removeOption(index: number) {
    if (questionForm.options.length <= 2) {
        return;
    }

    questionForm.options.splice(index, 1);
}

function markCorrect(index: number) {
    questionForm.options.forEach((option, current) => {
        option.is_correct = current === index;
    });
}

function toggleCorrect(index: number, value: boolean) {
    questionForm.options[index].is_correct = value;
}

const TRUE_FALSE_OPTIONS = ['True', 'False'];

const isTrueFalse = computed(() => questionForm.answer_mode === 'true_false');

function onModeChange() {
    if (questionForm.answer_mode === 'multiple') {
        return;
    }

    if (questionForm.answer_mode === 'true_false') {
        const isFalseCorrect =
            questionForm.options[1]?.text === 'False' &&
            questionForm.options[1].is_correct;

        questionForm.options = TRUE_FALSE_OPTIONS.map((text, index) => ({
            text,
            is_correct: isFalseCorrect ? index === 1 : index === 0,
        }));
        questionForm.scores = [];

        return;
    }

    let kept = false;

    questionForm.options.forEach((option) => {
        if (option.is_correct && !kept) {
            kept = true;

            return;
        }

        option.is_correct = false;
    });

    if (!kept && questionForm.options.length > 0) {
        questionForm.options[0].is_correct = true;
    }

    questionForm.scores = [];
}

function submitQuestion() {
    processing.value = true;
    errors.value = {};

    const isMultiple = questionForm.answer_mode === 'multiple';

    const payload = {
        question: questionForm.question,
        answer_mode: questionForm.answer_mode,
        points: isMultiple ? 0 : Number(questionForm.points || 0),
        options: questionForm.options.map((option) => ({
            text: option.text,
            is_correct: option.is_correct,
        })),
        scores: isMultiple
            ? questionForm.scores.map((score) => Number(score || 0))
            : [],
    };

    const options = {
        preserveScroll: true,
        onSuccess: () => {
            questionDialogOpen.value = false;
        },
        onError: (err: Record<string, string>) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    };

    if (editingQuestionId.value === null) {
        router.post(questionRoutes.store(props.quiz.id).url, payload, options);

        return;
    }

    router.put(
        questionRoutes.update(editingQuestionId.value).url,
        payload,
        options,
    );
}

function moveQuestion(question: QuizQuestion, direction: MoveDirection) {
    router.patch(
        questionRoutes.move(question.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function confirmDelete() {
    const question = deleteTarget.value;

    if (question === null) {
        return;
    }

    router.delete(questionRoutes.destroy(question.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head :title="`Edit ${quiz.title}`" />

        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Quiz Builder"
                :description="`${course.title} › ${section.title}`"
            />
            <div class="flex items-center gap-2">
                <Badge variant="outline">
                    <Clock class="h-3 w-3" />
                    {{ formatTimeLimit(quiz.time_limit_minutes) }}
                </Badge>
                <Badge variant="secondary"
                    >{{ quiz.total_points }} pts total</Badge
                >
                <Link :href="courseRoutes.show(course.id)">
                    <Button variant="outline" size="sm">Back to course</Button>
                </Link>
            </div>
        </div>

        <form
            @submit.prevent="saveQuiz"
            class="space-y-4 rounded-lg border p-4"
        >
            <div class="grid gap-2">
                <Label for="quiz-title">Quiz title</Label>
                <Input id="quiz-title" v-model="quizForm.title" required />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="quiz-description">Description</Label>
                <Textarea
                    id="quiz-description"
                    v-model="quizForm.description"
                    rows="2"
                    placeholder="What does this quiz assess?"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="quiz-time-limit">Time limit (minutes)</Label>
                <Input
                    id="quiz-time-limit"
                    v-model="quizForm.time_limit_minutes"
                    type="number"
                    min="1"
                    :max="maxTimeLimitMinutes"
                    class="max-w-48"
                    placeholder="No limit"
                />
                <p class="text-xs text-muted-foreground">
                    Leave empty for no time limit.
                </p>
                <InputError :message="errors.time_limit_minutes" />
            </div>

            <Button size="sm" :disabled="processing">
                {{ processing ? 'Saving...' : 'Save Quiz' }}
            </Button>
        </form>

        <div class="rounded-lg border">
            <div class="flex items-center justify-between gap-4 border-b p-4">
                <div>
                    <h3 class="text-sm font-medium">Questions</h3>
                    <p class="text-xs text-muted-foreground">
                        {{ quiz.questions.length }} question(s) ·
                        {{ quiz.total_points }} points in total
                    </p>
                </div>
                <Button size="sm" @click="openQuestionDialog()">
                    <Plus class="mr-2 h-4 w-4" />
                    Add Question
                </Button>
            </div>

            <p
                v-if="quiz.questions.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                No questions yet. Add the first one to start scoring.
            </p>

            <div
                v-for="(question, questionIndex) in quiz.questions"
                :key="question.id"
                class="border-b p-4 last:border-b-0"
            >
                <div class="flex items-start justify-between gap-4">
                    <div class="min-w-0">
                        <p class="font-medium">
                            {{ questionIndex + 1 }}. {{ question.question }}
                        </p>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <Badge variant="outline">
                                {{ answerModeLabel(question.answer_mode) }}
                            </Badge>
                            <Badge variant="secondary">
                                max {{ question.max_points }} pts
                            </Badge>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="questionIndex === 0"
                            aria-label="Move question up"
                            @click="moveQuestion(question, 'up')"
                        >
                            <ChevronUp class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="
                                questionIndex === quiz.questions.length - 1
                            "
                            aria-label="Move question down"
                            @click="moveQuestion(question, 'down')"
                        >
                            <ChevronDown class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openQuestionDialog(question)"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="deleteTarget = question"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <ul class="mt-3 space-y-1">
                    <li
                        v-for="(option, optionIndex) in question.options"
                        :key="optionIndex"
                        class="flex items-center gap-2 text-sm"
                        :class="
                            option.is_correct
                                ? 'font-medium'
                                : 'text-muted-foreground'
                        "
                    >
                        <Check
                            v-if="option.is_correct"
                            class="h-4 w-4 shrink-0 text-green-600"
                        />
                        <X v-else class="h-4 w-4 shrink-0 opacity-40" />
                        <span>{{ option.text }}</span>
                    </li>
                </ul>

                <div
                    v-if="question.answer_mode === 'multiple'"
                    class="mt-3 flex flex-wrap gap-2"
                >
                    <Badge
                        v-for="(points, tier) in question.scores"
                        :key="tier"
                        variant="outline"
                    >
                        {{ tier + 1 }} correct → {{ points }} pts
                    </Badge>
                </div>
            </div>
        </div>

        <Dialog v-model:open="questionDialogOpen">
            <DialogContent class="max-h-[85vh] overflow-y-auto sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingQuestionId === null
                                ? 'Add Question'
                                : 'Edit Question'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Write the question, mark the answer key, and set how it
                        scores.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitQuestion" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="question-text">Question</Label>
                        <Textarea
                            id="question-text"
                            v-model="questionForm.question"
                            rows="2"
                            required
                            placeholder="Apa itu MVC?"
                        />
                        <InputError :message="errors.question" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="answer-mode">Answer key</Label>
                        <Select
                            v-model="questionForm.answer_mode"
                            @update:model-value="onModeChange"
                        >
                            <SelectTrigger id="answer-mode">
                                <SelectValue placeholder="Select mode" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="mode in answerModes"
                                    :key="mode"
                                    :value="mode"
                                >
                                    {{ answerModeLabel(mode) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.answer_mode" />
                    </div>

                    <div
                        v-if="questionForm.answer_mode !== 'multiple'"
                        class="grid gap-2"
                    >
                        <Label for="question-points">Points</Label>
                        <Input
                            id="question-points"
                            v-model="questionForm.points"
                            type="number"
                            min="0"
                            required
                        />
                        <InputError :message="errors.points" />
                    </div>

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between">
                            <Label>{{
                                isTrueFalse ? 'Correct answer' : 'Options'
                            }}</Label>
                            <Button
                                v-if="!isTrueFalse"
                                type="button"
                                variant="ghost"
                                size="sm"
                                :disabled="
                                    questionForm.options.length >= maxOptions
                                "
                                @click="addOption"
                            >
                                <Plus class="mr-2 h-4 w-4" />
                                Add option
                            </Button>
                        </div>

                        <div
                            v-if="isTrueFalse"
                            class="grid grid-cols-2 gap-2"
                            role="radiogroup"
                            aria-label="Correct answer"
                        >
                            <label
                                v-for="(option, index) in questionForm.options"
                                :key="option.text"
                                class="flex cursor-pointer items-center gap-2 rounded-md border p-3 text-sm transition-colors"
                                :class="
                                    option.is_correct
                                        ? 'border-primary bg-primary/5 font-medium'
                                        : 'hover:bg-muted/50'
                                "
                            >
                                <input
                                    type="radio"
                                    name="correct-option"
                                    class="h-4 w-4 shrink-0 accent-primary"
                                    :checked="option.is_correct"
                                    @change="markCorrect(index)"
                                />
                                {{ option.text }}
                            </label>
                        </div>

                        <template v-else>
                            <div
                                v-for="(option, index) in questionForm.options"
                                :key="index"
                                class="flex items-center gap-2"
                            >
                                <input
                                    v-if="questionForm.answer_mode === 'single'"
                                    type="radio"
                                    name="correct-option"
                                    class="h-4 w-4 shrink-0 accent-primary"
                                    :checked="option.is_correct"
                                    :aria-label="`Mark option ${index + 1} as correct`"
                                    @change="markCorrect(index)"
                                />
                                <Checkbox
                                    v-else
                                    :model-value="option.is_correct"
                                    :aria-label="`Mark option ${index + 1} as correct`"
                                    @update:model-value="
                                        (value) =>
                                            toggleCorrect(index, value === true)
                                    "
                                />
                                <Input
                                    v-model="option.text"
                                    required
                                    :placeholder="`Option ${index + 1}`"
                                />
                                <Button
                                    type="button"
                                    variant="ghost"
                                    size="sm"
                                    :disabled="questionForm.options.length <= 2"
                                    aria-label="Remove option"
                                    @click="removeOption(index)"
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </div>
                        </template>
                        <InputError :message="errors.options" />
                    </div>

                    <div
                        v-if="questionForm.answer_mode === 'multiple'"
                        class="grid gap-2 rounded-md border bg-muted/30 p-3"
                    >
                        <Label>Scoring</Label>
                        <p class="text-xs text-muted-foreground">
                            {{ correctCount }} correct option(s) marked. Set the
                            points awarded for each number the student gets
                            right.
                        </p>

                        <p
                            v-if="correctCount < 2"
                            class="text-sm text-destructive"
                        >
                            Mark at least two correct options to configure
                            scoring.
                        </p>

                        <div
                            v-for="(score, tier) in questionForm.scores"
                            :key="tier"
                            class="flex items-center gap-3"
                        >
                            <span class="w-40 shrink-0 text-sm">
                                {{ tier + 1 }} correct{{
                                    tier + 1 === correctCount ? ' (all)' : ''
                                }}
                            </span>
                            <Input
                                v-model="questionForm.scores[tier]"
                                type="number"
                                min="0"
                                required
                                placeholder="0"
                            />
                            <span class="text-sm text-muted-foreground"
                                >pts</span
                            >
                        </div>
                        <InputError :message="errors.scores" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="questionDialogOpen = false"
                        >
                            Cancel
                        </Button>
                        <Button :disabled="processing">
                            {{ processing ? 'Saving...' : 'Save Question' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="deleteTarget !== null"
            @update:open="
                (open) => {
                    if (!open) deleteTarget = null;
                }
            "
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Delete Question</DialogTitle>
                    <DialogDescription>
                        Delete <strong>{{ deleteTarget?.question }}</strong
                        >? Its options and answer key will be removed too. This
                        action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="deleteTarget = null"
                        >Cancel</Button
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Delete</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
