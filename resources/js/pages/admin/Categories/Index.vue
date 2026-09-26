<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
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
import { Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import categoryRoutes from '@/routes/admin/categories';
import type { Category, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Categories', href: '/admin/categories' },
        ],
    },
});

const props = defineProps<{
    categories: Paginated<Category>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function applyFilters() {
    router.get(
        categoryRoutes.index().url,
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}

function goToPage(page: number) {
    router.get(
        categoryRoutes.index().url,
        { search: search.value || undefined, page },
        { preserveState: true },
    );
}

function deleteCategory(id: number) {
    router.delete(categoryRoutes.destroy(id).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Course Categories" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Course Categories"
                description="Group courses so they are easier to browse"
            />
            <Link :href="categoryRoutes.create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add Category
                </Button>
            </Link>
        </div>

        <div class="flex items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search by name or slug..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
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
                                Name
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Slug
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Description
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Courses
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="categories.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No categories found.
                            </td>
                        </tr>
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">
                                {{ category.name }}
                            </td>
                            <td class="p-4 align-middle text-muted-foreground">
                                {{ category.slug }}
                            </td>
                            <td
                                class="max-w-sm truncate p-4 align-middle text-muted-foreground"
                            >
                                {{ category.description || '-' }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ category.courses_count ?? 0 }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Link
                                        :href="categoryRoutes.edit(category.id)"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Dialog>
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
                                                    >Delete
                                                    Category</DialogTitle
                                                >
                                                <DialogDescription>
                                                    Delete
                                                    <strong>{{
                                                        category.name
                                                    }}</strong
                                                    >? Its
                                                    {{
                                                        category.courses_count ??
                                                        0
                                                    }}
                                                    course(s) will stay, but
                                                    become uncategorised.
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
                                                        deleteCategory(
                                                            category.id,
                                                        )
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
                </table>
            </div>
        </div>

        <div
            v-if="categories.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="categories.current_page <= 1"
                @click="goToPage(categories.current_page - 1)"
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ categories.current_page }} of {{ categories.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="categories.current_page >= categories.last_page"
                @click="goToPage(categories.current_page + 1)"
            >
                Next
            </Button>
        </div>
    </div>
</template>
