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
import { answerModeLabel, formatTimeLimit, optionLabel } from '@/lib/course';
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
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kuis', href: '/dasbor/kuis' },
            { title: 'Ubah Kuis', href: '/dasbor/kuis' },
        ],
    },
});

const props = defineProps<{
    quiz: QuizDetail;
    section: { id: number; title: string };
    course: { id: number; slug: string; title: string };
    answerModes: AnswerMode[];
    maxOptions: number;
    maxTimeLimitMinutes: number;
    maxWeight: number;
}>();

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const quizForm = reactive({
    title: props.quiz.title,
    description: props.quiz.description ?? '',
    time_limit_minutes: props.quiz.time_limit_minutes?.toString() ?? '',
    passing_score: props.quiz.passing_score?.toString() ?? '',
    weight: props.quiz.weight.toString(),
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

    questionForm.options.push({ text: '', is_correct: isShortAnswer.value });
}

function removeOption(index: number) {
    if (questionForm.options.length <= minOptions.value) {
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

// A short answer question lists the answers it accepts, all of them correct.
const isShortAnswer = computed(
    () => questionForm.answer_mode === 'short_answer',
);

const minOptions = computed(() => (isShortAnswer.value ? 1 : 2));

function padOptions() {
    while (questionForm.options.length < minOptions.value) {
        questionForm.options.push({ text: '', is_correct: false });
    }
}

function withoutTrueFalseOptions() {
    const isTrueFalseSet =
        questionForm.options.length === TRUE_FALSE_OPTIONS.length &&
        questionForm.options.every(
            (option, index) => option.text === TRUE_FALSE_OPTIONS[index],
        );

    if (isTrueFalseSet) {
        questionForm.options = [];
    }
}

function onModeChange() {
    if (questionForm.answer_mode === 'short_answer') {
        withoutTrueFalseOptions();
        questionForm.options = questionForm.options
            .filter((option) => option.is_correct)
            .map((option) => ({ text: option.text, is_correct: true }));
        padOptions();
        questionForm.options.forEach((option) => {
            option.is_correct = true;
        });
        questionForm.scores = [];

        return;
    }

    if (questionForm.answer_mode === 'multiple') {
        padOptions();

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

    padOptions();

    if (!kept) {
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
        <Head :title="`Ubah ${quiz.title}`" />

        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Penyusun Kuis"
                :description="`${course.title} › ${section.title}`"
            />
            <div class="flex items-center gap-2">
                <Badge variant="outline">
                    <Clock class="h-3 w-3" />
                    {{ formatTimeLimit(quiz.time_limit_minutes) }}
                </Badge>
                <Badge variant="secondary"
                    >Total {{ quiz.total_points }} poin</Badge
                >
                <Badge v-if="quiz.passing_score !== null" variant="outline">
                    KKM {{ quiz.passing_score }}%
                </Badge>
                <Badge variant="outline">Bobot {{ quiz.weight }}</Badge>
                <Link :href="courseRoutes.show(course.slug)">
                    <Button variant="outline" size="sm"
                        >Kembali ke kursus</Button
                    >
                </Link>
            </div>
        </div>

        <form
            @submit.prevent="saveQuiz"
            class="space-y-4 rounded-lg border p-4"
        >
            <div class="grid gap-2">
                <Label for="quiz-title">Judul kuis</Label>
                <Input id="quiz-title" v-model="quizForm.title" required />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="quiz-description">Deskripsi</Label>
                <Textarea
                    id="quiz-description"
                    v-model="quizForm.description"
                    rows="2"
                    placeholder="Apa yang dinilai kuis ini?"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="quiz-time-limit">Batas waktu (menit)</Label>
                <Input
                    id="quiz-time-limit"
                    v-model="quizForm.time_limit_minutes"
                    type="number"
                    min="1"
                    :max="maxTimeLimitMinutes"
                    class="max-w-48"
                    placeholder="Tanpa batas"
                />
                <p class="text-xs text-muted-foreground">
                    Kosongkan jika tanpa batas waktu.
                </p>
                <InputError :message="errors.time_limit_minutes" />
            </div>

            <div class="grid gap-4 sm:grid-cols-2">
                <div class="grid content-start gap-2">
                    <Label for="quiz-passing-score"
                        >Nilai minimum lulus / KKM (%)</Label
                    >
                    <Input
                        id="quiz-passing-score"
                        v-model="quizForm.passing_score"
                        type="number"
                        min="0"
                        max="100"
                        placeholder="Tanpa KKM"
                    />
                    <p class="text-xs text-muted-foreground">
                        Siswa lulus kuis bila nilai terbaiknya mencapai
                        persentase ini. Kosongkan jika tanpa KKM.
                    </p>
                    <InputError :message="errors.passing_score" />
                </div>
                <div class="grid content-start gap-2">
                    <Label for="quiz-weight">Bobot di nilai akhir</Label>
                    <Input
                        id="quiz-weight"
                        v-model="quizForm.weight"
                        type="number"
                        min="0"
                        :max="maxWeight"
                        required
                    />
                    <p class="text-xs text-muted-foreground">
                        Kuis berbobot 2 dihitung dua kali lipat kuis berbobot 1.
                        Isi 0 agar kuis tidak ikut nilai akhir.
                    </p>
                    <InputError :message="errors.weight" />
                </div>
            </div>

            <Button size="sm" :disabled="processing">
                {{ processing ? 'Menyimpan...' : 'Simpan Kuis' }}
            </Button>
        </form>

        <div class="rounded-lg border">
            <div class="flex items-center justify-between gap-4 border-b p-4">
                <div>
                    <h3 class="text-sm font-medium">Soal</h3>
                    <p class="text-xs text-muted-foreground">
                        {{ quiz.questions.length }} soal · total
                        {{ quiz.total_points }} poin
                    </p>
                </div>
                <Button size="sm" @click="openQuestionDialog()">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Soal
                </Button>
            </div>

            <p
                v-if="quiz.questions.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                Belum ada soal. Tambahkan soal pertama untuk mulai menilai.
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
                                maks. {{ question.max_points }} poin
                            </Badge>
                        </div>
                    </div>

                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="questionIndex === 0"
                            aria-label="Pindahkan soal ke atas"
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
                            aria-label="Pindahkan soal ke bawah"
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
                        <span>{{ optionLabel(option.text) }}</span>
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
                        {{ tier + 1 }} benar → {{ points }} poin
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
                                ? 'Tambah Soal'
                                : 'Ubah Soal'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Tulis soal, tandai kunci jawaban, dan atur cara
                        penilaiannya.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitQuestion" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="question-text">Soal</Label>
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
                        <Label for="answer-mode">Kunci jawaban</Label>
                        <Select
                            v-model="questionForm.answer_mode"
                            @update:model-value="onModeChange"
                        >
                            <SelectTrigger id="answer-mode">
                                <SelectValue placeholder="Pilih mode" />
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
                        <Label for="question-points">Poin</Label>
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
                                isTrueFalse
                                    ? 'Jawaban benar'
                                    : isShortAnswer
                                      ? 'Jawaban yang diterima'
                                      : 'Pilihan'
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
                                {{
                                    isShortAnswer
                                        ? 'Tambah jawaban'
                                        : 'Tambah pilihan'
                                }}
                            </Button>
                        </div>
                        <p
                            v-if="isShortAnswer"
                            class="text-xs text-muted-foreground"
                        >
                            Jawaban siswa dianggap benar bila sama dengan salah
                            satu jawaban ini. Huruf besar/kecil, spasi berlebih,
                            dan tanda baca di ujung diabaikan. Tambahkan variasi
                            ejaan yang juga benar.
                        </p>

                        <div
                            v-if="isTrueFalse"
                            class="grid grid-cols-2 gap-2"
                            role="radiogroup"
                            aria-label="Jawaban benar"
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
                                {{ optionLabel(option.text) }}
                            </label>
                        </div>

                        <template v-else>
                            <div
                                v-for="(option, index) in questionForm.options"
                                :key="index"
                                class="grid gap-1"
                            >
                                <div class="flex items-center gap-2">
                                    <input
                                        v-if="
                                            questionForm.answer_mode ===
                                            'single'
                                        "
                                        type="radio"
                                        name="correct-option"
                                        class="h-4 w-4 shrink-0 accent-primary"
                                        :checked="option.is_correct"
                                        :aria-label="`Tandai pilihan ${index + 1} sebagai benar`"
                                        @change="markCorrect(index)"
                                    />
                                    <Checkbox
                                        v-else-if="!isShortAnswer"
                                        :model-value="option.is_correct"
                                        :aria-label="`Tandai pilihan ${index + 1} sebagai benar`"
                                        @update:model-value="
                                            (value) =>
                                                toggleCorrect(
                                                    index,
                                                    value === true,
                                                )
                                        "
                                    />
                                    <Input
                                        v-model="option.text"
                                        required
                                        :maxlength="500"
                                        :placeholder="
                                            isShortAnswer
                                                ? `Jawaban ${index + 1}`
                                                : `Pilihan ${index + 1}`
                                        "
                                    />
                                    <Button
                                        type="button"
                                        variant="ghost"
                                        size="sm"
                                        :disabled="
                                            questionForm.options.length <=
                                            minOptions
                                        "
                                        :aria-label="
                                            isShortAnswer
                                                ? 'Hapus jawaban'
                                                : 'Hapus pilihan'
                                        "
                                        @click="removeOption(index)"
                                    >
                                        <Trash2
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                                <InputError
                                    :message="errors[`options.${index}.text`]"
                                />
                            </div>
                        </template>
                        <InputError :message="errors.options" />
                    </div>

                    <div
                        v-if="questionForm.answer_mode === 'multiple'"
                        class="grid gap-2 rounded-md border bg-muted/30 p-3"
                    >
                        <Label>Penilaian</Label>
                        <p class="text-xs text-muted-foreground">
                            {{ correctCount }} pilihan benar ditandai. Atur poin
                            yang diberikan untuk tiap jumlah jawaban benar
                            siswa.
                        </p>

                        <p
                            v-if="correctCount < 2"
                            class="text-sm text-destructive"
                        >
                            Tandai minimal dua pilihan benar untuk mengatur
                            penilaian.
                        </p>

                        <div
                            v-for="(score, tier) in questionForm.scores"
                            :key="tier"
                            class="flex items-center gap-3"
                        >
                            <span class="w-40 shrink-0 text-sm">
                                {{ tier + 1 }} benar{{
                                    tier + 1 === correctCount ? ' (semua)' : ''
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
                                >poin</span
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
                            Batal
                        </Button>
                        <Button :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Soal' }}
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
                    <DialogTitle>Hapus Soal</DialogTitle>
                    <DialogDescription>
                        Hapus <strong>{{ deleteTarget?.question }}</strong
                        >? Pilihan dan kunci jawabannya juga akan dihapus.
                        Tindakan ini tidak bisa dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="deleteTarget = null"
                        >Batal</Button
                    >
                    <Button variant="destructive" @click="confirmDelete"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
