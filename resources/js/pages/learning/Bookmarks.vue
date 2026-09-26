<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Bookmark, BookmarkX, FileText, PlayCircle } from '@lucide/vue';
import LearningNav from '@/components/LearningNav.vue';
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
    <div
        class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6"
    >
        <Head title="Bookmarks" />

        <div class="space-y-6">
            <div class="max-w-2xl space-y-2">
                <p class="text-sm font-semibold text-primary">My learning</p>
                <h1 class="text-3xl font-semibold tracking-tight">Bookmarks</h1>
                <p class="text-muted-foreground">
                    Lessons you saved to come back to.
                </p>
            </div>
            <LearningNav />
        </div>

        <div
            v-if="bookmarks.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-10 text-center"
        >
            <Bookmark class="size-8 text-muted-foreground" />
            <p class="font-medium">No bookmarks yet</p>
            <p class="text-sm text-muted-foreground">
                Press "Bookmark" on a lesson to save it here.
            </p>
        </div>

        <ul v-else class="divide-y rounded-lg border">
            <li
                v-for="bookmark in bookmarks"
                :key="bookmark.id"
                class="flex items-center gap-3 p-4"
            >
                <PlayCircle
                    v-if="bookmark.content_type === 'video'"
                    class="h-5 w-5 shrink-0 text-muted-foreground"
                />
                <FileText
                    v-else
                    class="h-5 w-5 shrink-0 text-muted-foreground"
                />
                <Link
                    :href="
                        learn.lessons.show({
                            course: bookmark.course.id,
                            lesson: bookmark.id,
                        })
                    "
                    class="min-w-0 flex-1"
                >
                    <span class="block truncate font-medium hover:underline">
                        {{ bookmark.title }}
                    </span>
                    <span class="block truncate text-xs text-muted-foreground">
                        {{ bookmark.course.title }} ·
                        {{ bookmark.section.title }}
                        <template v-if="bookmark.duration_minutes">
                            · {{ formatDuration(bookmark.duration_minutes) }}
                        </template>
                        · Saved {{ formatDate(bookmark.bookmarked_at) }}
                    </span>
                </Link>
                <Button
                    variant="ghost"
                    size="icon"
                    class="shrink-0"
                    :aria-label="`Remove bookmark for ${bookmark.title}`"
                    @click="removeBookmark(bookmark)"
                >
                    <BookmarkX class="h-4 w-4" />
                </Button>
            </li>
        </ul>
    </div>
</template>
