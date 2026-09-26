<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Search } from '@lucide/vue';
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
import { formatTimeLimit } from '@/lib/course';
import quizRoutes from '@/routes/quizzes';
import type { Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Kursus', href: '/courses' },
            { title: 'Kuis', href: '/quizzes' },
        ],
    },
});

type QuizRow = {
    id: number;
    title: string;
    time_limit_minutes: number | null;
    questions_count: number;
    total_points: number;
    section: { id: number; title: string };
    course: { id: number; title: string };
};

const ANY = 'all';

const props = defineProps<{
    quizzes: Paginated<QuizRow>;
    filters: { search?: string; course_id?: string | number };
    courses: Array<{ id: number; title: string }>;
}>();

const search = ref(props.filters.search ?? '');
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

function goToPage(page: number) {
    router.get(quizRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}
</script>

<template>
    <Head title="Kuis" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Kuis"
            description="Semua kuis dari kursus yang Anda kelola"
        />

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
                                    :href="quizRoutes.edit(quiz.id)"
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
                                <Link :href="quizRoutes.edit(quiz.id)">
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
    </div>
</template>
