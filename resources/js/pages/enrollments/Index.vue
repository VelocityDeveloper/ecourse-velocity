<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ChevronDown, Eye, Plus, Search, UserMinus } from '@lucide/vue';
import { computed, ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
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
import { getInitials } from '@/composables/useInitials';
import {
    enrollmentStatusLabel,
    enrollmentStatusVariant,
    formatDate,
} from '@/lib/course';
import enrollmentRoutes from '@/routes/enrollments';
import type {
    EnrollmentRow,
    EnrollmentStatus,
    Paginated,
    StudentEnrollments,
    StudentOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Siswa', href: '/dasbor/pendaftaran' },
        ],
    },
});

const ANY = 'all';

const props = defineProps<{
    learners: Paginated<StudentEnrollments>;
    filters: { search?: string; course_id?: string | number; status?: string };
    statuses: EnrollmentStatus[];
    courses: Array<{ id: number; title: string }>;
    students?: StudentOption[];
}>();

const search = ref(props.filters.search ?? '');
const courseId = ref(String(props.filters.course_id ?? ANY) || ANY);
const status = ref(props.filters.status ?? ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        course_id: courseId.value === ANY ? undefined : courseId.value,
        status: status.value === ANY ? undefined : status.value,
        page,
    };
}

function applyFilters() {
    router.get(enrollmentRoutes.index().url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number) {
    router.get(enrollmentRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}

// Courses named in a student's row; the rest sit behind "+N lainnya" and the details.
const PREVIEW_COURSES = 2;

const expanded = ref(new Set<number>());

function isExpanded(learner: StudentEnrollments): boolean {
    return expanded.value.has(learner.student.id);
}

function toggle(learner: StudentEnrollments): void {
    const next = new Set(expanded.value);

    if (!next.delete(learner.student.id)) {
        next.add(learner.student.id);
    }

    expanded.value = next;
}

function activeCount(learner: StudentEnrollments): number {
    return learner.enrollments.filter(
        (enrollment) => enrollment.status === 'active',
    ).length;
}

const cancelTarget = ref<EnrollmentRow | null>(null);
const cancelDialogOpen = ref(false);

function askCancel(enrollment: EnrollmentRow): void {
    cancelTarget.value = enrollment;
    cancelDialogOpen.value = true;
}

const enrollDialogOpen = ref(false);
const enrollErrors = ref<Record<string, string>>({});
const enrolling = ref(false);
const loadingStudents = ref(false);
const studentSearch = ref('');
const enrollForm = ref<{ course_id: string; user_id: number | null }>({
    course_id: '',
    user_id: null,
});

const filteredStudents = computed(() => {
    const needle = studentSearch.value.trim().toLowerCase();
    const students = props.students ?? [];

    if (needle === '') {
        return students;
    }

    return students.filter(
        (student) =>
            student.name.toLowerCase().includes(needle) ||
            student.email.toLowerCase().includes(needle),
    );
});

function openEnrollDialog(): void {
    enrollErrors.value = {};
    studentSearch.value = '';
    enrollForm.value = {
        course_id: courseId.value === ANY ? '' : courseId.value,
        user_id: null,
    };
    enrollDialogOpen.value = true;

    if (props.students === undefined) {
        loadingStudents.value = true;
        router.reload({
            only: ['students'],
            onFinish: () => {
                loadingStudents.value = false;
            },
        });
    }
}

function submitEnrollment(): void {
    enrolling.value = true;
    enrollErrors.value = {};

    router.post(
        enrollmentRoutes.store().url,
        {
            course_id: enrollForm.value.course_id,
            user_id: enrollForm.value.user_id,
        },
        {
            preserveScroll: true,
            onSuccess: () => {
                enrollDialogOpen.value = false;
            },
            onError: (errors) => {
                enrollErrors.value = errors;
            },
            onFinish: () => {
                enrolling.value = false;
            },
        },
    );
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Siswa" />

        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Siswa"
                description="Siswa yang terdaftar di kursus yang Anda kelola"
            />
            <Button @click="openEnrollDialog">
                <Plus class="mr-2 h-4 w-4" />
                Daftarkan Siswa
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari siswa atau kursus..."
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

            <Select v-model="status" @update:model-value="applyFilters">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="Semua Status" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">Semua Status</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option"
                        :value="option"
                    >
                        {{ enrollmentStatusLabel(option) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">Cari</Button>
        </div>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Siswa
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Kursus yang diikuti
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Terakhir daftar
                            </th>
                            <th class="h-12 w-28 px-4" aria-label="Rincian" />
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="learners.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Siswa tidak ditemukan.
                            </td>
                        </tr>
                        <template
                            v-for="learner in learners.data"
                            :key="learner.student.id"
                        >
                            <tr
                                class="border-b transition-colors hover:bg-muted/50"
                                :class="{
                                    'bg-muted/40': isExpanded(learner),
                                }"
                            >
                                <td class="p-4 align-middle">
                                    <div
                                        class="flex min-w-52 items-center gap-3"
                                    >
                                        <Avatar
                                            class="size-9 shrink-0 overflow-hidden rounded-full"
                                        >
                                            <AvatarImage
                                                v-if="learner.student.avatar"
                                                :src="learner.student.avatar"
                                                :alt="learner.student.name"
                                            />
                                            <AvatarFallback class="text-xs">
                                                {{
                                                    getInitials(
                                                        learner.student.name,
                                                    )
                                                }}
                                            </AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0">
                                            <p class="truncate font-medium">
                                                {{ learner.student.name }}
                                            </p>
                                            <p
                                                class="truncate text-xs text-muted-foreground"
                                            >
                                                {{ learner.student.email }}
                                            </p>
                                        </div>
                                    </div>
                                </td>
                                <td class="p-4 align-middle">
                                    <div
                                        class="flex w-[26rem] items-center gap-1.5"
                                    >
                                        <span
                                            v-for="enrollment in learner.enrollments.slice(
                                                0,
                                                PREVIEW_COURSES,
                                            )"
                                            :key="enrollment.id"
                                            class="max-w-44 min-w-0 truncate rounded-md border bg-background px-2 py-0.5 text-xs"
                                            :class="{
                                                'border-dashed text-muted-foreground':
                                                    enrollment.status !==
                                                    'active',
                                            }"
                                            :title="enrollment.course.title"
                                        >
                                            {{ enrollment.course.title }}
                                        </span>
                                        <button
                                            v-if="
                                                learner.enrollments.length >
                                                PREVIEW_COURSES
                                            "
                                            type="button"
                                            class="shrink-0 rounded-md bg-primary/10 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-primary hover:bg-primary/15"
                                            @click="toggle(learner)"
                                        >
                                            +{{
                                                learner.enrollments.length -
                                                PREVIEW_COURSES
                                            }}
                                            lainnya
                                        </button>
                                    </div>
                                </td>
                                <td class="p-4 align-middle whitespace-nowrap">
                                    <span class="font-medium tabular-nums">{{
                                        activeCount(learner)
                                    }}</span>
                                    <span class="text-muted-foreground">
                                        /
                                        {{ learner.enrollments.length }}
                                        aktif</span
                                    >
                                </td>
                                <td
                                    class="p-4 align-middle whitespace-nowrap text-muted-foreground"
                                >
                                    {{
                                        formatDate(
                                            learner.enrollments[0]
                                                ?.enrolled_at ?? null,
                                        )
                                    }}
                                </td>
                                <td class="p-4 text-right align-middle">
                                    <Button
                                        variant="outline"
                                        size="sm"
                                        :aria-expanded="isExpanded(learner)"
                                        @click="toggle(learner)"
                                    >
                                        Rincian
                                        <ChevronDown
                                            class="ml-1 size-4 transition-transform"
                                            :class="{
                                                'rotate-180':
                                                    isExpanded(learner),
                                            }"
                                        />
                                    </Button>
                                </td>
                            </tr>
                            <tr
                                v-if="isExpanded(learner)"
                                class="border-b bg-muted/20"
                            >
                                <td colspan="5" class="px-4 pt-1 pb-4">
                                    <table
                                        class="w-full overflow-hidden rounded-md border bg-background text-sm"
                                    >
                                        <thead class="border-b bg-muted/40">
                                            <tr
                                                class="text-xs text-muted-foreground"
                                            >
                                                <th
                                                    class="px-3 py-2 text-left font-medium"
                                                >
                                                    Kursus
                                                </th>
                                                <th
                                                    class="w-28 px-3 py-2 text-left font-medium"
                                                >
                                                    Status
                                                </th>
                                                <th
                                                    class="w-32 px-3 py-2 text-left font-medium"
                                                >
                                                    Terdaftar
                                                </th>
                                                <th
                                                    class="w-48 px-3 py-2 text-left font-medium"
                                                >
                                                    Cara daftar
                                                </th>
                                                <th
                                                    class="w-24 px-3 py-2 text-right font-medium"
                                                >
                                                    Aksi
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="enrollment in learner.enrollments"
                                                :key="enrollment.id"
                                                class="border-b last:border-0"
                                            >
                                                <td
                                                    class="px-3 py-2 font-medium"
                                                >
                                                    {{
                                                        enrollment.course.title
                                                    }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <Badge
                                                        :variant="
                                                            enrollmentStatusVariant(
                                                                enrollment.status,
                                                            )
                                                        "
                                                    >
                                                        {{
                                                            enrollmentStatusLabel(
                                                                enrollment.status,
                                                            )
                                                        }}
                                                    </Badge>
                                                </td>
                                                <td
                                                    class="px-3 py-2 whitespace-nowrap"
                                                >
                                                    {{
                                                        formatDate(
                                                            enrollment.enrolled_at,
                                                        )
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-2 text-muted-foreground"
                                                >
                                                    {{
                                                        enrollment.is_self_enrolled
                                                            ? 'Daftar mandiri'
                                                            : `Oleh ${enrollment.enrolled_by?.name ?? 'staf'}`
                                                    }}
                                                </td>
                                                <td
                                                    class="px-3 py-1 text-right"
                                                >
                                                    <div
                                                        class="flex items-center justify-end gap-1"
                                                    >
                                                        <Link
                                                            :href="
                                                                enrollmentRoutes.show(
                                                                    enrollment.id,
                                                                )
                                                            "
                                                        >
                                                            <Button
                                                                variant="ghost"
                                                                size="sm"
                                                                aria-label="Lihat pendaftaran"
                                                            >
                                                                <Eye
                                                                    class="h-4 w-4"
                                                                />
                                                            </Button>
                                                        </Link>
                                                        <Button
                                                            v-if="
                                                                enrollment.can_cancel
                                                            "
                                                            variant="ghost"
                                                            size="sm"
                                                            aria-label="Batalkan pendaftaran"
                                                            @click="
                                                                askCancel(
                                                                    enrollment,
                                                                )
                                                            "
                                                        >
                                                            <UserMinus
                                                                class="h-4 w-4 text-destructive"
                                                            />
                                                        </Button>
                                                    </div>
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                    <tfoot class="border-t">
                        <tr>
                            <td
                                colspan="5"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                <template v-if="learners.total > 0">
                                    Menampilkan {{ learners.from }}–{{
                                        learners.to
                                    }}
                                    dari {{ learners.total }} siswa
                                </template>
                                <template v-else>0 siswa</template>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div
            v-if="learners.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="learners.current_page <= 1"
                @click="goToPage(learners.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ learners.current_page }} dari
                {{ learners.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="learners.current_page >= learners.last_page"
                @click="goToPage(learners.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>

        <Dialog v-model:open="enrollDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Daftarkan Siswa</DialogTitle>
                    <DialogDescription>
                        Tambahkan siswa ke salah satu kursus Anda. Pendaftaran
                        yang pernah dibatalkan akan diaktifkan kembali.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitEnrollment">
                    <div class="grid gap-2">
                        <Label for="enroll-course">Kursus</Label>
                        <Select v-model="enrollForm.course_id">
                            <SelectTrigger id="enroll-course">
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
                        <InputError :message="enrollErrors.course_id" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="enroll-student-search">Siswa</Label>
                        <Input
                            id="enroll-student-search"
                            v-model="studentSearch"
                            placeholder="Cari nama atau email..."
                        />
                        <div
                            class="max-h-60 overflow-y-auto rounded-md border"
                            role="listbox"
                            aria-label="Siswa"
                        >
                            <p
                                v-if="loadingStudents"
                                class="p-3 text-sm text-muted-foreground"
                            >
                                Memuat siswa...
                            </p>
                            <p
                                v-else-if="filteredStudents.length === 0"
                                class="p-3 text-sm text-muted-foreground"
                            >
                                Siswa tidak ditemukan.
                            </p>
                            <template v-else>
                                <button
                                    v-for="student in filteredStudents"
                                    :key="student.id"
                                    type="button"
                                    role="option"
                                    :aria-selected="
                                        enrollForm.user_id === student.id
                                    "
                                    class="flex w-full flex-col items-start border-b px-3 py-2 text-left text-sm last:border-b-0 hover:bg-muted/50"
                                    :class="{
                                        'bg-primary/10':
                                            enrollForm.user_id === student.id,
                                    }"
                                    @click="enrollForm.user_id = student.id"
                                >
                                    <span class="font-medium">{{
                                        student.name
                                    }}</span>
                                    <span
                                        class="text-xs text-muted-foreground"
                                        >{{ student.email }}</span
                                    >
                                </button>
                            </template>
                        </div>
                        <InputError :message="enrollErrors.user_id" />
                    </div>

                    <DialogFooter class="gap-2">
                        <Button
                            type="button"
                            variant="secondary"
                            @click="enrollDialogOpen = false"
                        >
                            Batal
                        </Button>
                        <Button
                            :disabled="
                                enrolling ||
                                enrollForm.course_id === '' ||
                                enrollForm.user_id === null
                            "
                        >
                            {{ enrolling ? 'Mendaftarkan...' : 'Daftarkan' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="cancelTarget?.id ?? null"
            :description="`Batalkan pendaftaran ${cancelTarget?.student.name ?? 'siswa ini'} di ${cancelTarget?.course.title ?? 'kursus ini'}? Siswa akan kehilangan akses ke kursus.`"
        />
    </div>
</template>
