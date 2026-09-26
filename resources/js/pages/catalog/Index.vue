<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Search } from '@lucide/vue';
import { ref } from 'vue';
import CourseCard from '@/components/CourseCard.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import type {
    CatalogCourse,
    CourseLevel,
    CourseOption,
    Paginated,
} from '@/types';

const ANY = 'all';

const props = defineProps<{
    courses: Paginated<CatalogCourse>;
    filters: { search?: string; category_id?: string | number; level?: string };
    categories: CourseOption[];
    levels: CourseLevel[];
}>();

const search = ref(props.filters.search ?? '');
const categoryId = ref(String(props.filters.category_id ?? ANY) || ANY);
const level = ref(props.filters.level ?? ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        category_id: categoryId.value === ANY ? undefined : categoryId.value,
        level: level.value === ANY ? undefined : level.value,
        page,
    };
}

function applyFilters() {
    router.get(catalogRoutes.index().url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number) {
    router.get(catalogRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-6xl flex-col space-y-6 px-4 py-10 sm:px-6"
    >
        <Head title="Course Catalog" />

        <div class="max-w-2xl space-y-2">
            <p class="text-sm font-semibold text-primary">Catalog</p>
            <h1 class="text-3xl font-semibold tracking-tight">
                Explore our courses
            </h1>
            <p class="text-muted-foreground">
                Browse published courses and enroll to start learning.
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search courses..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="categoryId" @update:model-value="applyFilters">
                <SelectTrigger class="w-[200px]">
                    <SelectValue placeholder="All Categories" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Categories</SelectItem>
                    <SelectItem
                        v-for="category in categories"
                        :key="category.id"
                        :value="String(category.id)"
                    >
                        {{ category.name }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="level" @update:model-value="applyFilters">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="All Levels" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Levels</SelectItem>
                    <SelectItem
                        v-for="option in levels"
                        :key="option"
                        :value="option"
                    >
                        {{ levelLabel(option) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">Search</Button>
        </div>

        <p
            v-if="courses.data.length === 0"
            class="rounded-lg border p-8 text-center text-sm text-muted-foreground"
        >
            No courses match your filters.
        </p>

        <div v-else class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
            <CourseCard
                v-for="course in courses.data"
                :key="course.id"
                :course="course"
            />
        </div>

        <div
            v-if="courses.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="courses.current_page <= 1"
                @click="goToPage(courses.current_page - 1)"
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ courses.current_page }} of {{ courses.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="courses.current_page >= courses.last_page"
                @click="goToPage(courses.current_page + 1)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
