<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import {
    ChevronDown,
    ChevronUp,
    ClipboardList,
    Clock,
    FileText,
    ListChecks,
    Pencil,
    Plus,
    Search,
    Trash2,
    Video,
} from '@lucide/vue';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
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
import {
    contentTypeLabel,
    formatDuration,
    formatTimeLimit,
} from '@/lib/course';
import lessonRoutes from '@/routes/lessons';
import quizRoutes from '@/routes/quizzes';
import sectionRoutes from '@/routes/sections';
import type {
    Lesson,
    LessonContentType,
    QuizSummary,
    MoveDirection,
    MovableItem,
    Section,
} from '@/types';

const props = defineProps<{
    courseSlug: string;
    sections: Section[];
    contentTypes: LessonContentType[];
    canEdit: boolean;
    movableLessons?: MovableItem[];
    movableQuizzes?: MovableItem[];
}>();

const CONTENT_ICONS = {
    video: Video,
    article: FileText,
};

const totalLessons = computed(() =>
    props.sections.reduce(
        (count, section) => count + section.lessons.length,
        0,
    ),
);

const totalMinutes = computed(() =>
    props.sections.reduce(
        (total, section) => total + section.duration_minutes,
        0,
    ),
);

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const sectionDialogOpen = ref(false);
const editingSectionId = ref<number | null>(null);
const sectionForm = reactive({ title: '', description: '' });

const lessonDialogOpen = ref(false);
const editingLessonId = ref<number | null>(null);
const lessonSectionId = ref<number | null>(null);
const lessonForm = reactive({
    title: '',
    content_type: 'video' as LessonContentType,
    content_url: '',
    duration_minutes: '',
});

const deleteTarget = ref<{
    kind: 'section' | 'lesson' | 'quiz';
    id: number;
    title: string;
} | null>(null);

const deleteLabel = computed(() => {
    const labels = { section: 'Bab', lesson: 'Materi', quiz: 'Kuis' };

    return deleteTarget.value === null ? '' : labels[deleteTarget.value.kind];
});
function lessonIcon(type: LessonContentType) {
    return CONTENT_ICONS[type] ?? FileText;
}

function requestOptions(onDone?: () => void) {
    return {
        preserveScroll: true,
        onSuccess: () => {
            onDone?.();
        },
        onError: (err: Record<string, string>) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    };
}

function openSectionDialog(section?: Section) {
    errors.value = {};
    editingSectionId.value = section?.id ?? null;
    sectionForm.title = section?.title ?? '';
    sectionForm.description = section?.description ?? '';
    sectionDialogOpen.value = true;
}

function submitSection() {
    processing.value = true;
    errors.value = {};

    const options = requestOptions(() => {
        sectionDialogOpen.value = false;
    });

    if (editingSectionId.value === null) {
        router.post(
            sectionRoutes.store(props.courseSlug).url,
            { ...sectionForm },
            options,
        );

        return;
    }

    router.put(
        sectionRoutes.update(editingSectionId.value).url,
        { ...sectionForm },
        options,
    );
}

function moveSection(section: Section, direction: MoveDirection) {
    router.patch(
        sectionRoutes.move(section.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function openLessonDialog(sectionId: number, lesson?: Lesson) {
    errors.value = {};
    lessonSectionId.value = sectionId;
    editingLessonId.value = lesson?.id ?? null;
    lessonForm.title = lesson?.title ?? '';
    lessonForm.content_type = lesson?.content_type ?? 'video';
    lessonForm.content_url = lesson?.content_url ?? '';
    lessonForm.duration_minutes = lesson?.duration_minutes?.toString() ?? '';
    lessonDialogOpen.value = true;
}

function submitLesson() {
    if (lessonSectionId.value === null) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const options = requestOptions(() => {
        lessonDialogOpen.value = false;
    });

    if (editingLessonId.value === null) {
        router.post(
            lessonRoutes.store(lessonSectionId.value).url,
            { ...lessonForm },
            options,
        );

        return;
    }

    router.put(
        lessonRoutes.update(editingLessonId.value).url,
        { ...lessonForm },
        options,
    );
}

function moveLesson(lesson: Lesson, direction: MoveDirection) {
    router.patch(
        lessonRoutes.move(lesson.id).url,
        { direction },
        { preserveScroll: true },
    );
}

const quizDialogOpen = ref(false);
const editingQuizId = ref<number | null>(null);
const quizSectionId = ref<number | null>(null);
const quizForm = reactive({
    title: '',
    description: '',
    time_limit_minutes: '',
});

function openQuizDialog(sectionId: number, quiz?: QuizSummary) {
    errors.value = {};
    quizSectionId.value = sectionId;
    editingQuizId.value = quiz?.id ?? null;
    quizForm.title = quiz?.title ?? '';
    quizForm.description = quiz?.description ?? '';
    quizForm.time_limit_minutes = quiz?.time_limit_minutes?.toString() ?? '';
    quizDialogOpen.value = true;
}

function submitQuiz() {
    if (quizSectionId.value === null) {
        return;
    }

    processing.value = true;
    errors.value = {};

    const options = requestOptions(() => {
        quizDialogOpen.value = false;
    });

    if (editingQuizId.value === null) {
        router.post(
            quizRoutes.store(quizSectionId.value).url,
            { ...quizForm },
            options,
        );

        return;
    }

    router.put(
        quizRoutes.update(editingQuizId.value).url,
        { ...quizForm },
        options,
    );
}

function moveQuiz(quiz: QuizSummary, direction: MoveDirection) {
    router.patch(
        quizRoutes.move(quiz.id).url,
        { direction },
        { preserveScroll: true },
    );
}

const pickerOpen = ref(false);
const pickerKind = ref<'lesson' | 'quiz'>('lesson');
const pickerSectionId = ref<number | null>(null);
const pickerSearch = ref('');
const pickerLoading = ref(false);

const pickerItems = computed<MovableItem[]>(() => {
    const source =
        pickerKind.value === 'lesson'
            ? (props.movableLessons ?? [])
            : (props.movableQuizzes ?? []);

    const needle = pickerSearch.value.trim().toLowerCase();

    return source
        .filter((item) => item.section_id !== pickerSectionId.value)
        .filter(
            (item) =>
                needle === '' ||
                item.title.toLowerCase().includes(needle) ||
                item.course_title.toLowerCase().includes(needle) ||
                item.section_title.toLowerCase().includes(needle),
        );
});

function openPicker(kind: 'lesson' | 'quiz', sectionId: number) {
    pickerKind.value = kind;
    pickerSectionId.value = sectionId;
    pickerSearch.value = '';
    pickerOpen.value = true;
    pickerLoading.value = true;

    router.reload({
        only: [kind === 'lesson' ? 'movableLessons' : 'movableQuizzes'],
        onFinish: () => {
            pickerLoading.value = false;
        },
    });
}

function moveIntoSection(item: MovableItem) {
    const sectionId = pickerSectionId.value;

    if (sectionId === null) {
        return;
    }

    const url =
        pickerKind.value === 'lesson'
            ? lessonRoutes.section.update(item.id).url
            : quizRoutes.section.update(item.id).url;

    router.patch(
        url,
        { section_id: sectionId },
        {
            preserveScroll: true,
            onSuccess: () => {
                pickerOpen.value = false;
            },
        },
    );
}
function confirmDelete() {
    const target = deleteTarget.value;

    if (target === null) {
        return;
    }

    const routes = {
        section: sectionRoutes.destroy,
        lesson: lessonRoutes.destroy,
        quiz: quizRoutes.destroy,
    };

    router.delete(routes[target.kind](target.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleteTarget.value = null;
        },
    });
}
</script>

<template>
    <div>
        <div class="rounded-lg border">
            <div class="flex items-center justify-between gap-4 border-b p-4">
                <div>
                    <h3 class="text-sm font-medium">Kurikulum</h3>
                    <p class="text-xs text-muted-foreground">
                        {{ sections.length }} bab · {{ totalLessons }} materi ·
                        {{ formatDuration(totalMinutes) }}
                    </p>
                </div>
                <Button v-if="canEdit" size="sm" @click="openSectionDialog()">
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Bab
                </Button>
            </div>

            <p
                v-if="sections.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                Belum ada bab.
                <span v-if="canEdit"
                    >Tambahkan bab pertama untuk memulai kurikulum.</span
                >
            </p>

            <div
                v-for="(section, sectionIndex) in sections"
                :key="section.id"
                class="border-b last:border-b-0"
            >
                <div class="flex items-start justify-between gap-4 p-4">
                    <div class="min-w-0">
                        <p class="font-medium">
                            {{ sectionIndex + 1 }}. {{ section.title }}
                        </p>
                        <p
                            v-if="section.description"
                            class="mt-0.5 text-sm text-muted-foreground"
                        >
                            {{ section.description }}
                        </p>
                        <div class="mt-2 flex items-center gap-2">
                            <Badge variant="outline">
                                {{ section.lessons.length }} materi
                            </Badge>
                            <span
                                class="flex items-center gap-1 text-xs text-muted-foreground"
                            >
                                <Clock class="h-3 w-3" />
                                {{ formatDuration(section.duration_minutes) }}
                            </span>
                        </div>
                    </div>

                    <div
                        v-if="canEdit"
                        class="flex shrink-0 items-center gap-1"
                    >
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="sectionIndex === 0"
                            aria-label="Pindahkan bab ke atas"
                            @click="moveSection(section, 'up')"
                        >
                            <ChevronUp class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            :disabled="sectionIndex === sections.length - 1"
                            aria-label="Pindahkan bab ke bawah"
                            @click="moveSection(section, 'down')"
                        >
                            <ChevronDown class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openSectionDialog(section)"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="
                                deleteTarget = {
                                    kind: 'section',
                                    id: section.id,
                                    title: section.title,
                                }
                            "
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </div>

                <ul class="border-t bg-muted/30">
                    <li
                        v-for="(lesson, lessonIndex) in section.lessons"
                        :key="lesson.id"
                        class="flex items-center justify-between gap-4 border-b px-4 py-2.5 last:border-b-0"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <component
                                :is="lessonIcon(lesson.content_type)"
                                class="h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0">
                                <p class="truncate text-sm">
                                    {{ lessonIndex + 1 }}. {{ lesson.title }}
                                </p>
                                <a
                                    v-if="lesson.content_url"
                                    :href="lesson.content_url"
                                    target="_blank"
                                    rel="noopener"
                                    class="truncate text-xs text-muted-foreground hover:underline"
                                >
                                    {{ lesson.content_url }}
                                </a>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge variant="secondary">
                                {{ contentTypeLabel(lesson.content_type) }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ formatDuration(lesson.duration_minutes) }}
                            </span>

                            <template v-if="canEdit">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="lessonIndex === 0"
                                    aria-label="Pindahkan materi ke atas"
                                    @click="moveLesson(lesson, 'up')"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="
                                        lessonIndex ===
                                        section.lessons.length - 1
                                    "
                                    aria-label="Pindahkan materi ke bawah"
                                    @click="moveLesson(lesson, 'down')"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </Button>
                                <Link
                                    :href="
                                        lessonRoutes.edit({
                                            course: courseSlug,
                                            lesson: lesson.slug,
                                        })
                                    "
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Ubah isi materi"
                                    >
                                        <FileText class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="
                                        openLessonDialog(section.id, lesson)
                                    "
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="
                                        deleteTarget = {
                                            kind: 'lesson',
                                            id: lesson.id,
                                            title: lesson.title,
                                        }
                                    "
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </template>
                        </div>
                    </li>

                    <li v-if="canEdit" class="px-4 py-2.5">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openLessonDialog(section.id)"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Tambah Materi
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openPicker('lesson', section.id)"
                        >
                            Tambah materi yang sudah ada
                        </Button>
                    </li>

                    <li
                        v-else-if="section.lessons.length === 0"
                        class="px-4 py-3 text-sm text-muted-foreground"
                    >
                        Belum ada materi di bab ini.
                    </li>
                </ul>

                <ul class="border-t bg-muted/10">
                    <li
                        v-for="(quiz, quizIndex) in section.quizzes"
                        :key="quiz.id"
                        class="flex items-center justify-between gap-4 border-b px-4 py-2.5 last:border-b-0"
                    >
                        <div class="flex min-w-0 items-center gap-3">
                            <ClipboardList
                                class="h-4 w-4 shrink-0 text-muted-foreground"
                            />
                            <div class="min-w-0">
                                <Link
                                    :href="
                                        quizRoutes.edit({
                                            course: courseSlug,
                                            quiz: quiz.slug,
                                        })
                                    "
                                    class="truncate text-sm hover:underline"
                                >
                                    {{ quiz.title }}
                                </Link>
                                <p
                                    v-if="quiz.description"
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ quiz.description }}
                                </p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-2">
                            <Badge
                                v-if="quiz.time_limit_minutes !== null"
                                variant="outline"
                            >
                                <Clock class="h-3 w-3" />
                                {{ formatTimeLimit(quiz.time_limit_minutes) }}
                            </Badge>
                            <Badge variant="outline">
                                {{ quiz.questions_count }} soal
                            </Badge>
                            <Badge variant="secondary"
                                >{{ quiz.total_points }} poin</Badge
                            >

                            <template v-if="canEdit">
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="quizIndex === 0"
                                    aria-label="Pindahkan kuis ke atas"
                                    @click="moveQuiz(quiz, 'up')"
                                >
                                    <ChevronUp class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    :disabled="
                                        quizIndex === section.quizzes.length - 1
                                    "
                                    aria-label="Pindahkan kuis ke bawah"
                                    @click="moveQuiz(quiz, 'down')"
                                >
                                    <ChevronDown class="h-4 w-4" />
                                </Button>
                                <Link
                                    :href="
                                        quizRoutes.edit({
                                            course: courseSlug,
                                            quiz: quiz.slug,
                                        })
                                    "
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Ubah soal"
                                    >
                                        <ListChecks class="h-4 w-4" />
                                    </Button>
                                </Link>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    aria-label="Ganti nama kuis"
                                    @click="openQuizDialog(section.id, quiz)"
                                >
                                    <Pencil class="h-4 w-4" />
                                </Button>
                                <Button
                                    variant="ghost"
                                    size="sm"
                                    @click="
                                        deleteTarget = {
                                            kind: 'quiz',
                                            id: quiz.id,
                                            title: quiz.title,
                                        }
                                    "
                                >
                                    <Trash2 class="h-4 w-4 text-destructive" />
                                </Button>
                            </template>
                        </div>
                    </li>

                    <li v-if="canEdit" class="px-4 py-2.5">
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openQuizDialog(section.id)"
                        >
                            <Plus class="mr-2 h-4 w-4" />
                            Tambah Kuis
                        </Button>
                        <Button
                            variant="ghost"
                            size="sm"
                            @click="openPicker('quiz', section.id)"
                        >
                            Tambah kuis yang sudah ada
                        </Button>
                    </li>
                </ul>
            </div>
        </div>

        <Dialog v-model:open="sectionDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingSectionId === null
                                ? 'Tambah Bab'
                                : 'Ubah Bab'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Bab mengelompokkan materi yang saling berkaitan.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitSection" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="section-title">Judul</Label>
                        <Input
                            id="section-title"
                            v-model="sectionForm.title"
                            required
                            placeholder="Pengenalan"
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="section-description">Deskripsi</Label>
                        <Textarea
                            id="section-description"
                            v-model="sectionForm.description"
                            rows="3"
                            placeholder="Ringkasan bab ini (opsional)"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="sectionDialogOpen = false"
                        >
                            Batal
                        </Button>
                        <Button :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Bab' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="lessonDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingLessonId === null
                                ? 'Tambah Materi'
                                : 'Ubah Materi'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Materi adalah bagian-bagian yang dipelajari siswa satu
                        per satu.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitLesson" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="lesson-title">Judul</Label>
                        <Input
                            id="lesson-title"
                            v-model="lessonForm.title"
                            required
                            placeholder="Instalasi Laravel"
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="lesson-type">Jenis konten</Label>
                        <Select v-model="lessonForm.content_type">
                            <SelectTrigger id="lesson-type">
                                <SelectValue placeholder="Pilih jenis" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="type in contentTypes"
                                    :key="type"
                                    :value="type"
                                >
                                    {{ contentTypeLabel(type) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <InputError :message="errors.content_type" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="lesson-url">URL konten</Label>
                        <Input
                            id="lesson-url"
                            v-model="lessonForm.content_url"
                            type="url"
                            placeholder="https://example.com/video"
                        />
                        <InputError :message="errors.content_url" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="lesson-duration">Durasi (menit)</Label>
                        <Input
                            id="lesson-duration"
                            v-model="lessonForm.duration_minutes"
                            type="number"
                            min="0"
                            placeholder="12"
                        />
                        <InputError :message="errors.duration_minutes" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="lessonDialogOpen = false"
                        >
                            Batal
                        </Button>
                        <Button :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Materi' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="quizDialogOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            editingQuizId === null ? 'Tambah Kuis' : 'Ubah Kuis'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        Kuis memiliki soal, kunci jawaban, dan poinnya sendiri.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitQuiz" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="quiz-title">Judul</Label>
                        <Input
                            id="quiz-title"
                            v-model="quizForm.title"
                            required
                            placeholder="Kuis Bab 1"
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="quiz-description">Deskripsi</Label>
                        <Textarea
                            id="quiz-description"
                            v-model="quizForm.description"
                            rows="3"
                            placeholder="Ringkasan kuis ini (opsional)"
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
                            max="600"
                            placeholder="Tanpa batas"
                        />
                        <p class="text-xs text-muted-foreground">
                            Kosongkan jika tanpa batas waktu.
                        </p>
                        <InputError :message="errors.time_limit_minutes" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="quizDialogOpen = false"
                        >
                            Batal
                        </Button>
                        <Button :disabled="processing">
                            {{ processing ? 'Menyimpan...' : 'Simpan Kuis' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="pickerOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>
                        Tambah {{ pickerKind === 'lesson' ? 'materi' : 'kuis' }}
                        yang sudah ada
                    </DialogTitle>
                    <DialogDescription>
                        Pilih {{ pickerKind === 'lesson' ? 'materi' : 'kuis' }}
                        dari kursus mana pun yang Anda kelola. Item ini
                        dipindahkan ke sini, bukan disalin, sehingga keluar dari
                        bab asalnya.
                    </DialogDescription>
                </DialogHeader>

                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="pickerSearch"
                        class="pl-9"
                        placeholder="Cari menurut judul, bab, atau kursus..."
                    />
                </div>

                <p
                    v-if="pickerLoading"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Memuat...
                </p>

                <p
                    v-else-if="pickerItems.length === 0"
                    class="py-6 text-center text-sm text-muted-foreground"
                >
                    Tidak ada yang bisa dipindahkan ke sini.
                </p>

                <ul
                    v-else
                    class="max-h-80 divide-y overflow-y-auto rounded-md border"
                >
                    <li
                        v-for="item in pickerItems"
                        :key="item.id"
                        class="flex items-center justify-between gap-4 px-3 py-2.5"
                    >
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium">
                                {{ item.title }}
                            </p>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ item.course_title }} ›
                                {{ item.section_title }}
                            </p>
                        </div>
                        <Button
                            size="sm"
                            variant="outline"
                            @click="moveIntoSection(item)"
                        >
                            Pindahkan ke sini
                        </Button>
                    </li>
                </ul>

                <DialogFooter>
                    <Button variant="secondary" @click="pickerOpen = false"
                        >Tutup</Button
                    >
                </DialogFooter>
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
                    <DialogTitle>
                        Hapus
                        {{ deleteLabel }}
                    </DialogTitle>
                    <DialogDescription>
                        Yakin ingin menghapus
                        <strong>{{ deleteTarget?.title }}</strong
                        >?
                        <template v-if="deleteTarget?.kind === 'section'">
                            Semua materi dan kuis di dalamnya juga akan dihapus.
                        </template>
                        <template v-else-if="deleteTarget?.kind === 'quiz'">
                            Soal dan kunci jawabannya juga akan dihapus.
                        </template>
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
