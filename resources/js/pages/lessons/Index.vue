<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { FileText, Paperclip, Pencil, Search } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { contentTypeLabel, formatDuration } from '@/lib/course';
import lessonRoutes from '@/routes/lessons';
import type { LessonContentType, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kursus', href: '/dasbor/kursus' },
            { title: 'Materi', href: '/dasbor/materi' },
        ],
    },
});

type LessonRow = {
    id: number;
    slug: string;
    title: string;
    content_type: LessonContentType;
    duration_minutes: number | null;
    has_content: boolean;
    attachments_count: number;
    section: { id: number; title: string };
    course: { id: number; slug: string; title: string };
};

const ANY = 'all';

const props = defineProps<{
    lessons: Paginated<LessonRow>;
    filters: {
        search?: string;
        course_id?: string | number;
        content_type?: string;
    };
    courses: Array<{ id: number; title: string }>;
    contentTypes: LessonContentType[];
}>();

const search = ref(props.filters.search ?? '');
const courseId = ref(String(props.filters.course_id ?? ANY) || ANY);
const contentType = ref(String(props.filters.content_type ?? ANY) || ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        course_id: courseId.value === ANY ? undefined : courseId.value,
        content_type: contentType.value === ANY ? undefined : contentType.value,
        page,
    };
}

function applyFilters() {
    router.get(lessonRoutes.index().url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number) {
    router.get(lessonRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Materi" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Materi"
            description="Semua materi dari kursus yang Anda kelola"
        />

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari judul materi..."
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

            <Select v-model="contentType" @update:model-value="applyFilters">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="Semua Jenis" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">Semua Jenis</SelectItem>
                    <SelectItem
                        v-for="type in contentTypes"
                        :key="type"
                        :value="type"
                    >
                        {{ contentTypeLabel(type) }}
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
                                Materi
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Jenis
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Durasi
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Isi
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="lessons.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Tidak ada materi.
                            </td>
                        </tr>
                        <tr
                            v-for="lesson in lessons.data"
                            :key="lesson.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="
                                        lessonRoutes.edit({
                                            course: lesson.course.slug,
                                            lesson: lesson.slug,
                                        })
                                    "
                                    class="font-medium hover:underline"
                                >
                                    {{ lesson.title }}
                                </Link>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ lesson.course.title }} ›
                                    {{ lesson.section.title }}
                                </p>
                            </td>
                            <td class="p-4 align-middle">
                                <Badge variant="secondary">
                                    {{ contentTypeLabel(lesson.content_type) }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                {{ formatDuration(lesson.duration_minutes) }}
                            </td>
                            <td class="p-4 align-middle">
                                <div
                                    class="flex items-center gap-3 text-xs text-muted-foreground"
                                >
                                    <span
                                        v-if="lesson.has_content"
                                        class="flex items-center gap-1"
                                    >
                                        <FileText class="h-3 w-3" />
                                        Tertulis
                                    </span>
                                    <span v-else>—</span>
                                    <span
                                        v-if="lesson.attachments_count > 0"
                                        class="flex items-center gap-1"
                                    >
                                        <Paperclip class="h-3 w-3" />
                                        {{ lesson.attachments_count }}
                                    </span>
                                </div>
                            </td>
                            <td class="p-4 text-right align-middle">
                                <Link
                                    :href="
                                        lessonRoutes.edit({
                                            course: lesson.course.slug,
                                            lesson: lesson.slug,
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
                                Menampilkan {{ lessons.from ?? 0 }}–{{
                                    lessons.to ?? 0
                                }}
                                dari {{ lessons.total }} materi
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <div
            v-if="lessons.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="lessons.current_page <= 1"
                @click="goToPage(lessons.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ lessons.current_page }} dari {{ lessons.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="lessons.current_page >= lessons.last_page"
                @click="goToPage(lessons.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
