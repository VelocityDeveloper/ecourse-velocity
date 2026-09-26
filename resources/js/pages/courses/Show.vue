<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Pencil, Trash2 } from '@lucide/vue';
import CourseCurriculum from '@/components/CourseCurriculum.vue';
import courses from '@/routes/courses';
import enrollmentRoutes from '@/routes/enrollments';
import users from '@/routes/users';
import {
    formatDate,
    formatPrice,
    levelLabel,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import type {
    CourseDetail,
    CourseStatus,
    LessonContentType,
    MovableItem,
    Section,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Courses', href: '/courses' },
            { title: 'Course Detail', href: '/courses' },
        ],
    },
});

const props = defineProps<{
    course: CourseDetail;
    sections: Section[];
    statuses: CourseStatus[];
    contentTypes: LessonContentType[];
    movableLessons?: MovableItem[];
    movableQuizzes?: MovableItem[];
}>();

const status = ref<CourseStatus>(props.course.status);
const statusError = ref<string | undefined>(undefined);

function changeStatus(next: CourseStatus) {
    statusError.value = undefined;

    router.patch(
        courses.status.update(props.course.id).url,
        { status: next },
        {
            preserveScroll: true,
            onError: (err) => {
                statusError.value = err.status;
                status.value = props.course.status;
            },
        },
    );
}

function deleteCourse() {
    router.delete(courses.destroy(props.course.id).url);
}
</script>

<template>
    <Head :title="course.title" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="course.title"
                :description="course.slug"
            />
            <div class="flex items-center gap-2">
                <Link v-if="course.can.update" :href="courses.edit(course.id)">
                    <Button variant="outline" size="sm">
                        <Pencil class="mr-2 h-4 w-4" />
                        Edit
                    </Button>
                </Link>
                <Dialog v-if="course.can.delete">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="sm">
                            <Trash2 class="mr-2 h-4 w-4 text-destructive" />
                            Delete
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Delete Course</DialogTitle>
                            <DialogDescription>
                                Are you sure you want to delete
                                <strong>{{ course.title }}</strong
                                >? This action cannot be undone.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Cancel</Button>
                            </DialogClose>
                            <Button variant="destructive" @click="deleteCourse"
                                >Delete</Button
                            >
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-3">
            <div class="space-y-4 lg:col-span-2">
                <img
                    v-if="course.thumbnail_url"
                    :src="course.thumbnail_url"
                    :alt="course.title"
                    class="aspect-video w-full rounded-lg border object-cover"
                />
                <div
                    v-else
                    class="flex aspect-video w-full items-center justify-center rounded-lg border bg-muted text-sm text-muted-foreground"
                >
                    No thumbnail
                </div>

                <div class="rounded-lg border p-4">
                    <h3 class="mb-2 text-sm font-medium">Description</h3>
                    <p
                        class="text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ course.description || 'No description yet.' }}
                    </p>
                </div>

                <CourseCurriculum
                    :course-id="course.id"
                    :sections="sections"
                    :content-types="contentTypes"
                    :movable-lessons="movableLessons"
                    :movable-quizzes="movableQuizzes"
                    :can-edit="course.can.update"
                />
            </div>

            <div class="space-y-4">
                <div class="space-y-4 rounded-lg border p-4">
                    <div>
                        <p class="text-xs text-muted-foreground">Status</p>
                        <Badge
                            :variant="statusBadgeVariant(course.status)"
                            class="mt-1"
                        >
                            {{ statusLabel(course.status) }}
                        </Badge>
                    </div>

                    <div v-if="course.can.update" class="grid gap-2">
                        <Label for="status">Change status</Label>
                        <Select
                            v-model="status"
                            @update:model-value="changeStatus(status)"
                        >
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Select status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem
                                    v-for="s in statuses"
                                    :key="s"
                                    :value="s"
                                >
                                    {{ statusLabel(s) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                        <p v-if="statusError" class="text-sm text-destructive">
                            {{ statusError }}
                        </p>
                    </div>
                </div>

                <dl class="space-y-3 rounded-lg border p-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Category</dt>
                        <dd class="text-right">
                            {{ course.category?.name ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Instructor</dt>
                        <dd class="text-right">
                            <Link
                                v-if="course.instructor"
                                :href="users.show(course.instructor.id)"
                                class="underline-offset-4 hover:underline"
                            >
                                {{ course.instructor.name }}
                            </Link>
                            <template v-else>-</template>
                        </dd>
                    </div>
                    <div
                        v-if="course.instructor_email"
                        class="flex items-center justify-between gap-4"
                    >
                        <dt class="text-muted-foreground">Email</dt>
                        <dd class="truncate text-right">
                            {{ course.instructor_email }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Students</dt>
                        <dd class="text-right">
                            <Link
                                :href="
                                    enrollmentRoutes.index({
                                        query: { course_id: course.id },
                                    })
                                "
                                class="underline-offset-4 hover:underline"
                            >
                                {{ course.students_count }} enrolled
                            </Link>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Progress</dt>
                        <dd class="text-right">
                            <Link
                                :href="courses.progress.index(course.id)"
                                class="underline-offset-4 hover:underline"
                            >
                                View student progress
                            </Link>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Level</dt>
                        <dd class="text-right">
                            {{ levelLabel(course.level) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Price</dt>
                        <dd class="text-right">
                            {{ formatPrice(course.price) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Created</dt>
                        <dd class="text-right">
                            {{ formatDate(course.created_at) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Updated</dt>
                        <dd class="text-right">
                            {{ formatDate(course.updated_at) }}
                        </dd>
                    </div>
                </dl>

                <Link :href="courses.index()">
                    <Button variant="ghost" class="w-full"
                        >Back to courses</Button
                    >
                </Link>
            </div>
        </div>
    </div>
</template>
