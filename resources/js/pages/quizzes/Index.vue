<script setup lang="ts">
import { Head, Link, router } from "@inertiajs/vue3";
import { Pencil, Plus, Search } from "@lucide/vue";
import { computed, reactive, ref } from "vue";
import Heading from "@/components/Heading.vue";
import InputError from "@/components/InputError.vue";
import { Badge } from "@/components/ui/badge";
import { Button } from "@/components/ui/button";
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from "@/components/ui/dialog";
import { Input } from "@/components/ui/input";
import { Label } from "@/components/ui/label";
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from "@/components/ui/select";
import { Textarea } from "@/components/ui/textarea";
import { formatTimeLimit } from "@/lib/course";
import quizRoutes from "@/routes/quizzes";
import type { Paginated } from "@/types";

defineOptions({
    layout: {
        breadcrumbs: [
            { title: "Dasbor", href: "/dasbor" },
            { title: "Kursus", href: "/dasbor/kursus" },
            { title: "Kuis", href: "/dasbor/kuis" },
        ],
    },
});

type QuizRow = {
    id: number;
    slug: string;
    title: string;
    time_limit_minutes: number | null;
    questions_count: number;
    total_points: number;
    section: { id: number; title: string };
    course: { id: number; slug: string; title: string };
};

type CourseOption = {
    id: number;
    title: string;
    sections: Array<{ id: number; title: string }>;
};

const ANY = "all";

const props = defineProps<{
    quizzes: Paginated<QuizRow>;
    filters: { search?: string; course_id?: string | number };
    courses: CourseOption[];
}>();

const search = ref(props.filters.search ?? "");
const courseId = ref(String(props.filters.course_id ?? ANY) || ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        course_id: courseId.value === ANY ? undefined : courseId.value,
        page,
    };
}

function applyFilters() {
    router.get(quizRoutes.index().url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

const createOpen = ref(false);
const processing = ref(false);
const errors = ref<Record<string, string>>({});
const createForm = reactive({
    course_id: "",
    section_id: "",
    title: "",
    description: "",
    time_limit_minutes: "",
});

const createSections = computed(
    () =>
        props.courses.find(
            (course) => String(course.id) === createForm.course_id,
        )?.sections ?? [],
);

function openCreate() {
    const filtered = courseId.value === ANY ? "" : courseId.value;

    createForm.course_id = filtered;
    createForm.section_id = "";
    createForm.title = "";
    createForm.description = "";
    createForm.time_limit_minutes = "";
    errors.value = {};
    pickFirstSection();
    createOpen.value = true;
}

function pickFirstSection() {
    const first = createSections.value[0];

    createForm.section_id = first ? String(first.id) : "";
}

function submitCreate() {
    if (createForm.section_id === "") {
        return;
    }

    processing.value = true;
    errors.value = {};

    router.post(
        quizRoutes.store(Number(createForm.section_id)).url,
        {
            title: createForm.title,
            description: createForm.description,
            time_limit_minutes: createForm.time_limit_minutes,
            open_builder: true,
        },
        {
            onError: (formErrors) => {
                errors.value = formErrors;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function goToPage(page: number) {
    router.get(quizRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Kuis" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <Heading
                variant="small"
                title="Kuis"
                description="Semua kuis dari kursus yang Anda kelola"
            />
            <Button :disabled="courses.length === 0" @click="openCreate">
                <Plus class="h-4 w-4" />
                Buat Kuis
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari judul kuis..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="courseId" @update:model-value="applyFilters">
                <SelectTrigger class="w-[220px]">
                    <SelectValue placeholder="Semua Kursus" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">Semua Kursus</SelectItem>
                    <SelectItem
                        v-for="course in courses"
                        :key="course.id"
                        :value="String(course.id)"
                    >
                        {{ course.title }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">Cari</Button>
        </div>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr class="border-b transition-colors">
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Kuis
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Soal
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Batas waktu
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Total poin
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="quizzes.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Tidak ada kuis.
                            </td>
                        </tr>
                        <tr
                            v-for="quiz in quizzes.data"
                            :key="quiz.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="
                                        quizRoutes.edit({
                                            course: quiz.course.slug,
                                            quiz: quiz.slug,
                                        })
                                    "
                                    class="font-medium hover:underline"
                                >
                                    {{ quiz.title }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ quiz.course.title }} ›
                                    {{ quiz.section.title }}
                                </p>
                            </td>
                            <td class="p-4 align-middle">
                                {{ quiz.questions_count }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ formatTimeLimit(quiz.time_limit_minutes) }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge variant="secondary"
                                    >{{ quiz.total_points }} poin</Badge
                                >
                            </td>
                            <td class="p-4 text-right align-middle">
                                <Link
                                    :href="
                                        quizRoutes.edit({
                                            course: quiz.course.slug,
                                            quiz: quiz.slug,
                                        })
                                    "
                                >
                                    <Button variant="ghost" size="sm">
                                        <Pencil class="h-4 w-4" />
                                    </Button>
                                </Link>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t">
                        <tr>
                            <td
                                colspan="5"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                Menampilkan {{ quizzes.from ?? 0 }}–{{
                                    quizzes.to ?? 0
                                }}
                                dari {{ quizzes.total }} kuis
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div
            v-if="quizzes.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="quizzes.current_page <= 1"
                @click="goToPage(quizzes.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ quizzes.current_page }} dari {{ quizzes.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="quizzes.current_page >= quizzes.last_page"
                @click="goToPage(quizzes.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
        <Dialog v-model:open="createOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Buat Kuis</DialogTitle>
                    <DialogDescription>
                        Pilih kursus dan bab tempat kuis ini berada. Setelah
                        disimpan, Anda langsung diarahkan ke penyusun soal.
                    </DialogDescription>
                </DialogHeader>

                <form @submit.prevent="submitCreate" class="space-y-4">
                    <div class="grid gap-2">
                        <Label for="create-quiz-course">Kursus</Label>
                        <Select
                            v-model="createForm.course_id"
                            @update:model-value="pickFirstSection"
                        >
                            <SelectTrigger
                                id="create-quiz-course"
                                class="w-full"
                            >
                                <SelectValue placeholder="Pilih kursus" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="course in courses"
                                    :key="course.id"
                                    :value="String(course.id)"
                                >
                                    {{ course.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-quiz-section">Bab</Label>
                        <Select
                            v-model="createForm.section_id"
                            :disabled="createSections.length === 0"
                        >
                            <SelectTrigger
                                id="create-quiz-section"
                                class="w-full"
                            >
                                <SelectValue placeholder="Pilih bab" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="section in createSections"
                                    :key="section.id"
                                    :value="String(section.id)"
                                >
                                    {{ section.title }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p
                            v-if="
                                createForm.course_id !== '' &&
                                createSections.length === 0
                            "
                            class="text-xs text-muted-foreground"
                        >
                            Kursus ini belum punya bab. Tambahkan bab dulu di
                            halaman kursus.
                        </p>
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-quiz-title">Judul</Label>
                        <Input
                            id="create-quiz-title"
                            v-model="createForm.title"
                            required
                            placeholder="Kuis Bab 1"
                        />
                        <InputError :message="errors.title" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-quiz-description">Deskripsi</Label>
                        <Textarea
                            id="create-quiz-description"
                            v-model="createForm.description"
                            rows="3"
                            placeholder="Ringkasan kuis ini (opsional)"
                        />
                        <InputError :message="errors.description" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="create-quiz-time-limit"
                            >Batas waktu (menit)</Label
                        >
                        <Input
                            id="create-quiz-time-limit"
                            v-model="createForm.time_limit_minutes"
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
                            @click="createOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            :disabled="
                                processing || createForm.section_id === ''
                            "
                        >
                            {{ processing ? "Menyimpan..." : "Buat Kuis" }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
