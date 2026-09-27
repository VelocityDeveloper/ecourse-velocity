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
import { Link2, Pencil, Trash2 } from '@lucide/vue';
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
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Kursus', href: '/courses' },
            { title: 'Detail Kursus', href: '/courses' },
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
                        Ubah
                    </Button>
                </Link>
                <Dialog v-if="course.can.delete">
                    <DialogTrigger as-child>
                        <Button variant="outline" size="sm">
                            <Trash2 class="mr-2 h-4 w-4 text-destructive" />
                            Hapus
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Hapus Kursus</DialogTitle>
                            <DialogDescription>
                                Yakin ingin menghapus
                                <strong>{{ course.title }}</strong
                                >? Tindakan ini tidak bisa dibatalkan.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Batal</Button>
                            </DialogClose>
                            <Button variant="destructive" @click="deleteCourse"
                                >Hapus</Button
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
                    Tanpa gambar sampul
                </div>

                <div class="rounded-lg border p-4">
                    <h3 class="mb-2 text-sm font-medium">Deskripsi</h3>
                    <p
                        class="text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ course.description || 'Belum ada deskripsi.' }}
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
                        <Label for="status">Ubah status</Label>
                        <Select
                            v-model="status"
                            @update:model-value="changeStatus(status)"
                        >
                            <SelectTrigger id="status">
                                <SelectValue placeholder="Pilih status" />
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
                        <dt class="text-muted-foreground">Kategori</dt>
                        <dd class="text-right">
                            {{ course.category?.name ?? '-' }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Instruktur</dt>
                        <dd class="text-right">
                            <Link
                                v-if="course.instructor"
                                :href="users.show(course.instructor.id)"
                                class="inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                            >
                                <Link2
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
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
                        <dt class="text-muted-foreground">Siswa</dt>
                        <dd class="text-right">
                            <Link
                                :href="
                                    enrollmentRoutes.index({
                                        query: { course_id: course.id },
                                    })
                                "
                                class="inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                            >
                                <Link2
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                {{ course.students_count }} terdaftar
                            </Link>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Progres</dt>
                        <dd class="text-right">
                            <Link
                                :href="courses.progress.index(course.id)"
                                class="inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                            >
                                <Link2
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                Lihat progres siswa
                            </Link>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Nilai</dt>
                        <dd class="text-right">
                            <Link
                                :href="courses.grades.index(course.id)"
                                class="inline-flex items-center gap-1.5 font-medium text-primary underline-offset-4 hover:underline"
                            >
                                <Link2
                                    class="size-3.5 shrink-0"
                                    aria-hidden="true"
                                />
                                Buku nilai
                            </Link>
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Tingkat</dt>
                        <dd class="text-right">
                            {{ levelLabel(course.level) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Harga</dt>
                        <dd class="text-right">
                            {{ formatPrice(course.price) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Dibuat</dt>
                        <dd class="text-right">
                            {{ formatDate(course.created_at) }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Diperbarui</dt>
                        <dd class="text-right">
                            {{ formatDate(course.updated_at) }}
                        </dd>
                    </div>
                </dl>

                <Link :href="courses.index()">
                    <Button variant="ghost" class="w-full"
                        >Kembali ke kursus</Button
                    >
                </Link>
            </div>
        </div>
    </div>
</template>
