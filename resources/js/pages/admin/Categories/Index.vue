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
import { ImageOff, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import categoryRoutes from '@/routes/admin/categories';
import type { Category, Paginated } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kategori', href: '/dasbor/kategori' },
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

function deleteCategory(slug: string) {
    router.delete(categoryRoutes.destroy(slug).url, { preserveScroll: true });
}
</script>

<template>
    <Head title="Kategori Kursus" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="Kategori Kursus"
                description="Kelompokkan kursus agar lebih mudah dijelajahi"
            />
            <Link :href="categoryRoutes.create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Kategori
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
                    placeholder="Cari nama atau slug..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
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
                                Nama
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Slug
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Deskripsi
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Kursus
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="categories.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Kategori tidak ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="category in categories.data"
                            :key="category.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle font-medium">
                                <div class="flex items-center gap-3">
                                    <img
                                        v-if="category.image_url"
                                        :src="category.image_url"
                                        alt=""
                                        class="h-9 w-16 shrink-0 rounded-md border object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-9 w-16 shrink-0 items-center justify-center rounded-md border border-dashed text-muted-foreground"
                                        title="Belum ada gambar"
                                    >
                                        <ImageOff class="h-4 w-4" />
                                    </span>
                                    {{ category.name }}
                                </div>
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
                                        :href="
                                            categoryRoutes.edit(category.slug)
                                        "
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
                                                    >Hapus Kategori</DialogTitle
                                                >
                                                <DialogDescription>
                                                    Hapus
                                                    <strong>{{
                                                        category.name
                                                    }}</strong
                                                    >? Sebanyak
                                                    {{
                                                        category.courses_count ??
                                                        0
                                                    }}
                                                    kursus di dalamnya tetap
                                                    ada, tetapi menjadi tanpa
                                                    kategori.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter class="gap-2">
                                                <DialogClose as-child>
                                                    <Button variant="secondary"
                                                        >Batal</Button
                                                    >
                                                </DialogClose>
                                                <Button
                                                    variant="destructive"
                                                    @click="
                                                        deleteCategory(
                                                            category.slug,
                                                        )
                                                    "
                                                >
                                                    Hapus
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
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ categories.current_page }} dari
                {{ categories.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="categories.current_page >= categories.last_page"
                @click="goToPage(categories.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
