<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Award, Check, Download, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseManageLayout from '@/components/CourseManageLayout.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import { passStatusLabel, passStatusVariant } from '@/lib/course';
import certificateRoutes from '@/routes/certificates';
import courses from '@/routes/courses';
import quizRoutes from '@/routes/quizzes';
import type { GradebookQuiz, GradebookRow } from '@/types';

const props = defineProps<{
    course: { id: number; slug: string; title: string; passing_grade: number };
    quizzes: GradebookQuiz[];
    students: GradebookRow[];
    totalWeight: number;
    gradeScale: Array<{ letter: string; min: number }>;
    summary: {
        students: number;
        average_final: number | null;
        passed: number;
        not_passed: number;
    };
    filters: { search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kursus', href: '/dasbor/kursus' },
            { title: 'Buku Nilai', href: '#' },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const passingGrade = ref(String(props.course.passing_grade));
const errors = ref<Record<string, string>>({});
const saving = ref(false);

const summaryItems = computed(() => [
    { label: 'Siswa terdaftar', value: String(props.summary.students) },
    {
        label: 'Rata-rata nilai akhir',
        value:
            props.summary.average_final === null
                ? '-'
                : `${props.summary.average_final}%`,
    },
    { label: 'Lulus kursus', value: String(props.summary.passed) },
    { label: 'Belum lulus', value: String(props.summary.not_passed) },
]);

const scaleText = computed(() =>
    props.gradeScale
        .map((row) =>
            row.min === 0
                ? `${row.letter} < ${props.gradeScale.at(-2)?.min ?? 0}`
                : `${row.letter} ≥ ${row.min}`,
        )
        .join(' · '),
);

function applySearch(): void {
    router.get(
        courses.grades.index(props.course.slug).url,
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function savePassingGrade(): void {
    saving.value = true;
    errors.value = {};

    router.patch(
        courses.grading.update(props.course.slug).url,
        { passing_grade: passingGrade.value },
        {
            preserveScroll: true,
            onError: (err) => {
                errors.value = err as Record<string, string>;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function weightShare(quiz: GradebookQuiz): string {
    if (props.totalWeight === 0 || quiz.weight === 0) {
        return 'tidak dihitung';
    }

    return `${Math.round((quiz.weight / props.totalWeight) * 100)}% nilai akhir`;
}
</script>

<template>
    <CourseManageLayout :course="course">
        <div class="flex flex-col space-y-6">
            <Head :title="`Buku Nilai · ${course.title}`" />

            <div class="flex flex-wrap items-start justify-between gap-4">
                <Heading
                    variant="small"
                    title="Buku Nilai"
                    :description="course.title"
                />
                <div class="flex flex-wrap items-center gap-2">
                    <a :href="courses.grades.export(course.slug).url">
                        <Button variant="outline" size="sm">
                            <Download class="mr-2 h-4 w-4" />
                            Unduh CSV
                        </Button>
                    </a>
                </div>
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
                    <p class="text-sm text-muted-foreground">
                        {{ item.label }}
                    </p>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_320px]">
                <section class="space-y-2 rounded-lg border p-4 text-sm">
                    <h3 class="font-medium">Cara nilai dihitung</h3>
                    <ul
                        class="list-disc space-y-1 pl-5 text-muted-foreground marker:text-muted-foreground/60"
                    >
                        <li>
                            Nilai tiap kuis = persentase percobaan terbaik
                            siswa.
                        </li>
                        <li>
                            Nilai akhir = rata-rata berbobot semua kuis. Kuis
                            yang belum dikerjakan dihitung 0. Bobot dan KKM
                            diatur di halaman tiap kuis.
                        </li>
                        <li>Predikat: {{ scaleText }}.</li>
                        <li>
                            Siswa lulus kursus bila nilai akhir ≥ nilai minimum
                            lulus kursus.
                        </li>
                    </ul>
                </section>
                <form
                    class="grid content-start gap-2 rounded-lg border p-4"
                    @submit.prevent="savePassingGrade"
                >
                    <Label for="passing-grade"
                        >Nilai minimum lulus kursus (%)</Label
                    >
                    <div class="flex gap-2">
                        <Input
                            id="passing-grade"
                            v-model="passingGrade"
                            type="number"
                            min="0"
                            max="100"
                            required
                        />
                        <Button
                            type="submit"
                            variant="outline"
                            :disabled="saving"
                        >
                            {{ saving ? 'Menyimpan...' : 'Simpan' }}
                        </Button>
                    </div>
                    <InputError :message="errors.passing_grade" />
                </form>
            </div>

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
                        aria-label="Cari siswa"
                        placeholder="Cari berdasarkan nama atau email..."
                        class="pl-9"
                    />
                </div>
                <Button variant="outline" type="submit">Cari</Button>
            </form>

            <p
                v-if="quizzes.length === 0"
                class="rounded-lg border p-8 text-center text-sm text-muted-foreground"
            >
                Kursus ini belum punya kuis, jadi belum ada nilai.
            </p>

            <div v-else class="rounded-lg border">
                <div class="overflow-x-auto">
                    <table class="w-full caption-bottom text-sm">
                        <thead class="border-b">
                            <tr>
                                <th
                                    class="sticky left-0 z-10 h-12 min-w-56 bg-background px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Siswa
                                </th>
                                <th
                                    v-for="quiz in quizzes"
                                    :key="quiz.id"
                                    class="min-w-36 px-4 py-2 text-left align-bottom font-medium"
                                >
                                    <Link
                                        :href="
                                            quizRoutes.edit({
                                                course: course.slug,
                                                quiz: quiz.slug,
                                            })
                                        "
                                        class="line-clamp-2 hover:underline"
                                        :title="`${quiz.section_title} › ${quiz.title}`"
                                    >
                                        {{ quiz.title }}
                                    </Link>
                                    <span
                                        class="mt-0.5 block text-xs font-normal text-muted-foreground"
                                    >
                                        Bobot {{ quiz.weight }} ·
                                        {{ weightShare(quiz) }}
                                        <template
                                            v-if="quiz.passing_score !== null"
                                            ><br />KKM
                                            {{ quiz.passing_score }}%</template
                                        >
                                    </span>
                                </th>
                                <th
                                    class="h-12 min-w-28 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Nilai akhir
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Predikat
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Status
                                </th>
                                <th
                                    class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                                >
                                    Sertifikat
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="students.length === 0">
                                <td
                                    :colspan="quizzes.length + 5"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    Tidak ada siswa terdaftar.
                                </td>
                            </tr>
                            <tr
                                v-for="row in students"
                                :key="row.enrollment_id"
                                class="group border-b transition-colors hover:bg-muted/50"
                            >
                                <td
                                    class="sticky left-0 z-10 bg-background p-4 align-middle group-hover:bg-muted"
                                >
                                    <Link
                                        :href="
                                            courses.progress.show({
                                                course: course.slug,
                                                student: row.student.slug,
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
                                                {{
                                                    getInitials(
                                                        row.student.name,
                                                    )
                                                }}
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
                                <td
                                    v-for="quiz in quizzes"
                                    :key="quiz.id"
                                    class="p-4 align-middle tabular-nums"
                                >
                                    <span
                                        v-if="
                                            row.grades[quiz.id]?.percent == null
                                        "
                                        class="text-muted-foreground"
                                        >-</span
                                    >
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1"
                                        :title="`${row.grades[quiz.id].score}/${row.grades[quiz.id].max_score} poin · ${row.grades[quiz.id].attempts} percobaan`"
                                    >
                                        {{ row.grades[quiz.id].percent }}%
                                        <Check
                                            v-if="
                                                row.grades[quiz.id].passed ===
                                                true
                                            "
                                            class="h-4 w-4 text-primary"
                                            aria-label="Lulus"
                                        />
                                        <X
                                            v-else-if="
                                                row.grades[quiz.id].passed ===
                                                false
                                            "
                                            class="h-4 w-4 text-destructive"
                                            aria-label="Belum lulus"
                                        />
                                    </span>
                                </td>
                                <td
                                    class="p-4 align-middle font-semibold tabular-nums"
                                >
                                    {{
                                        row.final_percent === null
                                            ? '-'
                                            : `${row.final_percent}%`
                                    }}
                                </td>
                                <td class="p-4 align-middle font-semibold">
                                    {{ row.letter ?? '-' }}
                                </td>
                                <td class="p-4 align-middle">
                                    <Badge
                                        v-if="row.passed !== null"
                                        :variant="passStatusVariant(row.passed)"
                                    >
                                        {{ passStatusLabel(row.passed) }}
                                    </Badge>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                                <td class="p-4 align-middle">
                                    <a
                                        v-if="row.certificate_code"
                                        :href="
                                            certificateRoutes.show(
                                                row.certificate_code,
                                            ).url
                                        "
                                        target="_blank"
                                        rel="noopener"
                                        class="inline-flex items-center gap-1.5 font-mono text-xs font-medium text-primary underline-offset-4 hover:underline"
                                    >
                                        <Award class="size-3.5 shrink-0" />
                                        {{ row.certificate_code }}
                                    </a>
                                    <span v-else class="text-muted-foreground"
                                        >-</span
                                    >
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p
                v-if="quizzes.length > 0 && totalWeight === 0"
                class="text-sm text-destructive"
            >
                Semua kuis berbobot 0, jadi nilai akhir belum bisa dihitung.
            </p>
        </div>
    </CourseManageLayout>
</template>
