<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ExternalLink, ImageOff, Plus, Search } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDateTime } from '@/lib/course';
import blogRoutes from '@/routes/blog';
import postRoutes from '@/routes/admin/posts';
import type { AdminPost, Paginated, PostStatus } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Blog', href: '/dasbor/blog' },
        ],
    },
});

const props = defineProps<{
    posts: Paginated<AdminPost>;
    filters: { search?: string; status?: string };
    counts: Record<'all' | PostStatus, number>;
}>();

const STATUS_TABS: Array<{ value: '' | PostStatus; label: string }> = [
    { value: '', label: 'Semua' },
    { value: 'published', label: 'Terbit' },
    { value: 'draft', label: 'Draf' },
];

const search = ref(props.filters.search ?? '');
const status = ref(props.filters.status ?? '');

function visit(page?: number): void {
    router.get(
        postRoutes.index().url,
        {
            search: search.value || undefined,
            status: status.value || undefined,
            page,
        },
        { preserveState: true, replace: page === undefined },
    );
}

function selectStatus(value: string): void {
    status.value = value;
    visit();
}

function statusLabel(post: AdminPost): string {
    if (post.status === 'draft') {
        return 'Draf';
    }

    return post.is_live ? 'Terbit' : 'Terjadwal';
}
</script>

<template>
    <Head title="Blog" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <Heading
                variant="small"
                title="Blog"
                description="Artikel yang tampil di halaman Blog situs"
            />
            <Link :href="postRoutes.create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Tulis Artikel
                </Button>
            </Link>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex gap-1 rounded-lg bg-muted p-1" role="tablist">
                <button
                    v-for="tab in STATUS_TABS"
                    :key="tab.value"
                    type="button"
                    role="tab"
                    :aria-selected="status === tab.value"
                    class="rounded-md px-3 py-1.5 text-sm transition-colors"
                    :class="
                        status === tab.value
                            ? 'bg-background font-medium shadow-sm'
                            : 'text-muted-foreground hover:text-foreground'
                    "
                    @click="selectStatus(tab.value)"
                >
                    {{ tab.label }}
                    <span class="text-muted-foreground"
                        >({{ counts[tab.value || 'all'] }})</span
                    >
                </button>
            </div>

            <div class="flex w-full items-center gap-2 sm:w-auto">
                <div class="relative flex-1 sm:w-72">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        v-model="search"
                        placeholder="Cari judul artikel..."
                        class="pl-9"
                        @keyup.enter="visit()"
                    />
                </div>
                <Button variant="outline" @click="visit()">Cari</Button>
            </div>
        </div>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Judul
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Tanggal terbit
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Penulis
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="posts.data.length === 0">
                            <td
                                colspan="5"
                                class="py-10 text-center text-muted-foreground"
                            >
                                Belum ada artikel.
                            </td>
                        </tr>
                        <tr
                            v-for="post in posts.data"
                            :key="post.id"
                            class="border-b transition-colors last:border-b-0 hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="postRoutes.edit(post.slug)"
                                    class="flex min-w-64 items-center gap-3 font-medium hover:text-primary"
                                >
                                    <img
                                        v-if="post.cover_url"
                                        :src="post.cover_url"
                                        alt=""
                                        class="h-10 w-16 shrink-0 rounded-md border object-cover"
                                    />
                                    <span
                                        v-else
                                        class="flex h-10 w-16 shrink-0 items-center justify-center rounded-md border border-dashed text-muted-foreground"
                                    >
                                        <ImageOff class="h-4 w-4" />
                                    </span>
                                    <span class="line-clamp-2">{{
                                        post.title
                                    }}</span>
                                </Link>
                            </td>
                            <td class="p-4 align-middle">
                                <Badge
                                    :variant="
                                        post.is_live
                                            ? 'default'
                                            : post.status === 'draft'
                                              ? 'secondary'
                                              : 'outline'
                                    "
                                >
                                    {{ statusLabel(post) }}
                                </Badge>
                            </td>
                            <td
                                class="p-4 align-middle whitespace-nowrap text-muted-foreground"
                            >
                                {{ formatDateTime(post.published_at) }}
                            </td>
                            <td
                                class="p-4 align-middle whitespace-nowrap text-muted-foreground"
                            >
                                {{ post.author ?? '-' }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <a
                                    :href="blogRoutes.show(post.slug).url"
                                    target="_blank"
                                    rel="noopener"
                                    class="inline-flex items-center gap-1 text-muted-foreground hover:text-foreground"
                                    :title="
                                        post.is_live
                                            ? 'Lihat di situs'
                                            : 'Pratinjau'
                                    "
                                >
                                    <ExternalLink class="h-4 w-4" />
                                    <span class="sr-only">Lihat</span>
                                </a>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="posts.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="posts.current_page <= 1"
                @click="visit(posts.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ posts.current_page }} dari {{ posts.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="posts.current_page >= posts.last_page"
                @click="visit(posts.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
