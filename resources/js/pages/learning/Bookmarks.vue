<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bookmark, BookmarkX, FileText, PlayCircle } from '@lucide/vue';
import LearningHeader from '@/components/LearningHeader.vue';
import { Button } from '@/components/ui/button';
import { formatDate, formatDuration } from '@/lib/course';
import learn from '@/routes/learn';
import type { BookmarkEntry } from '@/types';

defineProps<{
    bookmarks: BookmarkEntry[];
}>();

function removeBookmark(bookmark: BookmarkEntry): void {
    router.delete(
        learn.lessons.bookmark.destroy({
            course: bookmark.course.id,
            lesson: bookmark.id,
        }).url,
        { preserveScroll: true },
    );
}
</script>

<template>
    <div>
        <Head title="Markah" />

        <LearningHeader
            title="Markah"
            description="Materi yang Anda simpan untuk dibuka lagi nanti."
        ></LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-6 px-4 py-10 sm:px-6"
        >
            <div
                v-if="bookmarks.length === 0"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <Bookmark class="size-8 text-muted-foreground" />
                <p class="font-medium">Belum ada markah</p>
                <p class="text-sm text-muted-foreground">
                    Tekan "Tandai" pada materi untuk menyimpannya di sini.
                </p>
            </div>

            <ul
                v-else
                class="divide-y overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm"
            >
                <li
                    v-for="bookmark in bookmarks"
                    :key="bookmark.id"
                    class="flex items-center gap-4 p-4 transition-colors hover:bg-muted/40 sm:px-5"
                >
                    <span
                        class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <PlayCircle
                            v-if="bookmark.content_type === 'video'"
                            class="h-5 w-5"
                        />
                        <FileText v-else class="h-5 w-5" />
                    </span>
                    <Link
                        :href="
                            learn.lessons.show({
                                course: bookmark.course.id,
                                lesson: bookmark.id,
                            })
                        "
                        class="min-w-0 flex-1"
                    >
                        <span
                            class="block truncate font-bold hover:text-primary"
                        >
                            {{ bookmark.title }}
                        </span>
                        <span
                            class="block truncate text-xs text-muted-foreground"
                        >
                            {{ bookmark.course.title }} ·
                            {{ bookmark.section.title }}
                            <template v-if="bookmark.duration_minutes">
                                ·
                                {{ formatDuration(bookmark.duration_minutes) }}
                            </template>
                            · Disimpan {{ formatDate(bookmark.bookmarked_at) }}
                        </span>
                    </Link>
                    <Button
                        variant="ghost"
                        size="icon"
                        class="shrink-0"
                        :aria-label="`Hapus markah untuk ${bookmark.title}`"
                        @click="removeBookmark(bookmark)"
                    >
                        <BookmarkX class="h-4 w-4" />
                    </Button>
                </li>
            </ul>
        </div>
    </div>
</template>
