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
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import courseRoutes from '@/routes/courses';
import {
    formatPrice,
    levelLabel,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import type {
    CourseOption,
    CourseStatus,
    CourseSummary,
    Paginated,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Courses', href: '/courses' },
        ],
    },
});

const ANY = 'all';

const props = defineProps<{
    courses: Paginated<CourseSummary>;
    filters: {
        search?: string;
        status?: string;
        category_id?: string | number;
        instructor_id?: string | number;
    };
    categories: CourseOption[];
    instructors: CourseOption[];
    statuses: CourseStatus[];
    can: { create: boolean; manageAllCourses: boolean };
}>();

const search = ref(props.filters.search ?? '');
const status = ref(String(props.filters.status ?? ANY) || ANY);
const categoryId = ref(String(props.filters.category_id ?? ANY) || ANY);
const instructorId = ref(String(props.filters.instructor_id ?? ANY) || ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        status: status.value === ANY ? undefined : status.value,
        category_id: categoryId.value === ANY ? undefined : categoryId.value,
        instructor_id:
            instructorId.value === ANY ? undefined : instructorId.value,
        page,
    };
}

function applyFilters() {
    router.get(courseRoutes.index().url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number) {
    router.get(courseRoutes.index().url, currentQuery(page), {
        preserveState: true,
    });
}

function deleteCourse(id: number) {
    router.delete(courseRoutes.destroy(id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Courses" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Course Management"
                :description="
                    can.manageAllCourses
                        ? 'Manage every course across all instructors'
                        : 'Manage the courses you teach'
                "
            />
            <Link v-if="can.create" :href="courseRoutes.create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add Course
                </Button>
            </Link>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search by title or slug..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="status" @update:model-value="applyFilters">
                <SelectTrigger class="w-[170px]">
                    <SelectValue placeholder="All Statuses" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Statuses</SelectItem>
                    <SelectItem v-for="s in statuses" :key="s" :value="s">
                        {{ statusLabel(s) }}
                    </SelectItem>
                </SelectContent>
            </Select>

            <Select v-model="categoryId" @update:model-value="applyFilters">
                <SelectTrigger class="w-[180px]">
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

            <Select
                v-if="can.manageAllCourses"
                v-model="instructorId"
                @update:model-value="applyFilters"
            >
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="All Instructors" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">All Instructors</SelectItem>
                    <SelectItem
                        v-for="instructor in instructors"
                        :key="instructor.id"
                        :value="String(instructor.id)"
                    >
                        {{ instructor.name }}
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
                                Course
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Category
                            </th>
                            <th
                                v-if="can.manageAllCourses"
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Instructor
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Level
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Price
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="courses.data.length === 0">
                            <td
                                :colspan="can.manageAllCourses ? 7 : 6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No courses found.
                            </td>
                        </tr>
                        <tr
                            v-for="course in courses.data"
                            :key="course.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="course.thumbnail_url"
                                        :src="course.thumbnail_url"
                                        :alt="course.title"
                                        class="h-10 w-16 shrink-0 rounded object-cover"
                                    />
                                    <div
                                        v-else
                                        class="h-10 w-16 shrink-0 rounded bg-muted"
                                    />
                                    <div class="min-w-0">
                                        <Link
                                            :href="courseRoutes.show(course.id)"
                                            class="font-medium hover:underline"
                                        >
                                            {{ course.title }}
                                        </Link>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ course.slug }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                {{ course.category?.name ?? '-' }}
                            </td>
                            <td
                                v-if="can.manageAllCourses"
                                class="p-4 align-middle"
                            >
                                {{ course.instructor?.name ?? '-' }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge
                                    :variant="statusBadgeVariant(course.status)"
                                >
                                    {{ statusLabel(course.status) }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                {{ levelLabel(course.level) }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ formatPrice(course.price) }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        v-if="course.can.update"
                                        :href="courseRoutes.edit(course.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Dialog v-if="course.can.delete">
                                        <DialogTrigger as-child>
                                            <Button variant="ghost" size="sm">
                                                <Trash2
                                                    class="h-4 w-4 text-destructive"
                                                />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle
                                                    >Delete Course</DialogTitle
                                                >
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    delete
                                                    <strong>{{
                                                        course.title
                                                    }}</strong
                                                    >? This action cannot be
                                                    undone.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter class="gap-2">
                                                <DialogClose as-child>
                                                    <Button variant="secondary"
                                                        >Cancel</Button
                                                    >
                                                </DialogClose>
                                                <Button
                                                    variant="destructive"
                                                    @click="
                                                        deleteCourse(course.id)
                                                    "
                                                >
                                                    Delete
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t">
                        <tr>
                            <td
                                :colspan="can.manageAllCourses ? 7 : 6"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                Showing {{ courses.from ?? 0 }} to
                                {{ courses.to ?? 0 }} of
                                {{ courses.total }} courses
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
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
