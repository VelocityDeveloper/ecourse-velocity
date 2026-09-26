<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, Compass, PlayCircle } from '@lucide/vue';
import { ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import LearningNav from '@/components/LearningNav.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatDate, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import learn from '@/routes/learn';
import type { MyCourseEnrollment } from '@/types';

defineProps<{
    enrollments: MyCourseEnrollment[];
}>();

const cancelTarget = ref<MyCourseEnrollment | null>(null);
const cancelDialogOpen = ref(false);

function askCancel(enrollment: MyCourseEnrollment): void {
    cancelTarget.value = enrollment;
    cancelDialogOpen.value = true;
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-6xl flex-col space-y-6 px-4 py-10 sm:px-6"
    >
        <Head title="My Courses" />

        <div class="flex flex-wrap items-end justify-between gap-4">
            <div class="max-w-2xl space-y-2">
                <p class="text-sm font-semibold text-primary">My learning</p>
                <h1 class="text-3xl font-semibold tracking-tight">
                    My Courses
                </h1>
                <p class="text-muted-foreground">
                    Courses you are currently enrolled in.
                </p>
            </div>
            <Link :href="catalogRoutes.index()">
                <Button variant="outline">
                    <Compass class="mr-2 h-4 w-4" />
                    Browse catalog
                </Button>
            </Link>
        </div>

        <LearningNav />

        <div
            v-if="enrollments.length === 0"
            class="flex flex-col items-center gap-3 rounded-lg border p-10 text-center"
        >
            <BookOpen class="size-10 text-muted-foreground" />
            <p class="text-sm text-muted-foreground">
                You are not enrolled in any course yet.
            </p>
            <Link :href="catalogRoutes.index()">
                <Button>Find a course</Button>
            </Link>
        </div>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <div
                v-for="enrollment in enrollments"
                :key="enrollment.id"
                class="flex flex-col overflow-hidden rounded-lg border"
            >
                <Link :href="learn.show(enrollment.course.id)">
                    <img
                        v-if="enrollment.course.thumbnail_url"
                        :src="enrollment.course.thumbnail_url"
                        :alt="enrollment.course.title"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
                    >
                        <BookOpen class="size-8" />
                    </div>
                </Link>

                <div class="flex flex-1 flex-col gap-2 p-4">
                    <div class="flex flex-wrap items-center gap-2">
                        <Badge variant="outline">
                            {{ levelLabel(enrollment.course.level) }}
                        </Badge>
                        <span
                            v-if="enrollment.course.category"
                            class="text-xs text-muted-foreground"
                        >
                            {{ enrollment.course.category.name }}
                        </span>
                    </div>
                    <Link
                        :href="learn.show(enrollment.course.id)"
                        class="line-clamp-2 font-semibold hover:underline"
                    >
                        {{ enrollment.course.title }}
                    </Link>
                    <p
                        v-if="enrollment.course.instructor"
                        class="text-sm text-muted-foreground"
                    >
                        {{ enrollment.course.instructor.name }}
                    </p>

                    <div class="mt-auto space-y-1.5 pt-2">
                        <div
                            class="h-2 overflow-hidden rounded-full bg-muted"
                            role="progressbar"
                            :aria-valuenow="enrollment.progress.percent"
                            aria-valuemin="0"
                            aria-valuemax="100"
                            :aria-label="`${enrollment.course.title} progress`"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{
                                    width: `${enrollment.progress.percent}%`,
                                }"
                            />
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ enrollment.progress.completed }} of
                            {{ enrollment.progress.total }} complete ·
                            {{ enrollment.progress.percent }}%
                        </p>
                    </div>

                    <Link :href="learn.show(enrollment.course.id)">
                        <Button class="w-full">
                            <PlayCircle class="mr-2 h-4 w-4" />
                            {{
                                enrollment.progress.completed === 0
                                    ? 'Start learning'
                                    : enrollment.progress.percent === 100
                                      ? 'Review course'
                                      : 'Continue learning'
                            }}
                        </Button>
                    </Link>

                    <div class="flex items-center justify-between gap-2">
                        <span class="text-xs text-muted-foreground">
                            Enrolled {{ formatDate(enrollment.enrolled_at) }}
                        </span>
                        <Button
                            v-if="enrollment.can_cancel"
                            variant="ghost"
                            size="sm"
                            class="text-destructive"
                            @click="askCancel(enrollment)"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </div>
        </div>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="cancelTarget?.id ?? null"
            :description="`You will lose access to ${cancelTarget?.course.title ?? 'this course'}. You can enroll again later while the course is published.`"
        />
    </div>
</template>
