<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Eye, Plus, Search, UserMinus } from '@lucide/vue';
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
    StudentOption,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Enrollments', href: '/enrollments' },
        ],
    },
});

const ANY = 'all';

const props = defineProps<{
    enrollments: Paginated<EnrollmentRow>;
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
        <Head title="Enrollments" />

        <div class="flex items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Enrollments"
                description="Students enrolled in the courses you manage"
            />
            <Button @click="openEnrollDialog">
                <Plus class="mr-2 h-4 w-4" />
                Enroll Student
            </Button>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search student or course..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="courseId" @update:model-value="applyFilters">
                <SelectTrigger class="w-[220px]">
                    <SelectValue placeholder="All Courses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Courses</SelectItem>
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
                    <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Statuses</SelectItem>
                    <SelectItem
                        v-for="option in statuses"
                        :key="option"
                        :value="option"
                    >
                        {{ enrollmentStatusLabel(option) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">Search</Button>
        </div>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr class="border-b transition-colors">
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Student
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Course
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Enrolled
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Method
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="enrollments.data.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No enrollments found.
                            </td>
                        </tr>
                        <tr
                            v-for="enrollment in enrollments.data"
                            :key="enrollment.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        class="size-9 overflow-hidden rounded-full"
                                    >
                                        <AvatarImage
                                            v-if="enrollment.student.avatar"
                                            :src="enrollment.student.avatar"
                                            :alt="enrollment.student.name"
                                        />
                                        <AvatarFallback class="text-xs">
                                            {{
                                                getInitials(
                                                    enrollment.student.name,
                                                )
                                            }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <div class="min-w-0">
                                        <p class="truncate font-medium">
                                            {{ enrollment.student.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ enrollment.student.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                {{ enrollment.course.title }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge
                                    :variant="
                                        enrollmentStatusVariant(
                                            enrollment.status,
                                        )
                                    "
                                >
                                    {{
                                        enrollmentStatusLabel(enrollment.status)
                                    }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                {{ formatDate(enrollment.enrolled_at) }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{
                                    enrollment.is_self_enrolled
                                        ? 'Self-enrolled'
                                        : `By ${enrollment.enrolled_by?.name ?? 'staff'}`
                                }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Link
                                        :href="
                                            enrollmentRoutes.show(enrollment.id)
                                        "
                                    >
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            aria-label="View enrollment"
                                        >
                                            <Eye class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Button
                                        v-if="enrollment.can_cancel"
                                        variant="ghost"
                                        size="sm"
                                        aria-label="Cancel enrollment"
                                        @click="askCancel(enrollment)"
                                    >
                                        <UserMinus
                                            class="h-4 w-4 text-destructive"
                                        />
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t">
                        <tr>
                            <td
                                colspan="6"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                <template v-if="enrollments.total > 0">
                                    Showing {{ enrollments.from }} to
                                    {{ enrollments.to }} of
                                    {{ enrollments.total }} enrollments
                                </template>
                                <template v-else>0 enrollments</template>
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div
            v-if="enrollments.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="enrollments.current_page <= 1"
                @click="goToPage(enrollments.current_page - 1)"
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ enrollments.current_page }} of
                {{ enrollments.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="enrollments.current_page >= enrollments.last_page"
                @click="goToPage(enrollments.current_page + 1)"
            >
                Next
            </Button>
        </div>

        <Dialog v-model:open="enrollDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Enroll Student</DialogTitle>
                    <DialogDescription>
                        Add a student to one of your courses. A previously
                        cancelled enrollment is reactivated.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-4" @submit.prevent="submitEnrollment">
                    <div class="grid gap-2">
                        <Label for="enroll-course">Course</Label>
                        <Select v-model="enrollForm.course_id">
                            <SelectTrigger id="enroll-course">
                                <SelectValue placeholder="Select a course" />
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
                        <Label for="enroll-student-search">Student</Label>
                        <Input
                            id="enroll-student-search"
                            v-model="studentSearch"
                            placeholder="Search by name or email..."
                        />
                        <div
                            class="max-h-60 overflow-y-auto rounded-md border"
                            role="listbox"
                            aria-label="Students"
                        >
                            <p
                                v-if="loadingStudents"
                                class="p-3 text-sm text-muted-foreground"
                            >
                                Loading students...
                            </p>
                            <p
                                v-else-if="filteredStudents.length === 0"
                                class="p-3 text-sm text-muted-foreground"
                            >
                                No students found.
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
                            Cancel
                        </Button>
                        <Button
                            :disabled="
                                enrolling ||
                                enrollForm.course_id === '' ||
                                enrollForm.user_id === null
                            "
                        >
                            {{ enrolling ? 'Enrolling...' : 'Enroll' }}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="cancelTarget?.id ?? null"
            :description="`Cancel ${cancelTarget?.student.name ?? 'this student'}'s enrollment in ${cancelTarget?.course.title ?? 'this course'}? They will lose access to the course.`"
        />
    </div>
</template>
