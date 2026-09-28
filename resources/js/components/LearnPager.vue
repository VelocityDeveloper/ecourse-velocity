<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import learn from '@/routes/learn';
import type { OutlineNeighbours, OutlineReference } from '@/types';

const props = defineProps<{
    courseSlug: string;
    neighbours: OutlineNeighbours;
}>();

function href(item: OutlineReference) {
    return item.type === 'lesson'
        ? learn.lessons.show({ course: props.courseSlug, lesson: item.slug })
        : learn.quizzes.show({ course: props.courseSlug, quiz: item.slug });
}
</script>

<template>
    <nav
        class="grid gap-3 border-t pt-6 sm:grid-cols-2"
        aria-label="Navigasi materi"
    >
        <Link
            v-if="neighbours.previous"
            :href="href(neighbours.previous)"
            class="group flex items-center gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/40"
        >
            <ChevronLeft
                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-hover:-translate-x-0.5"
            />
            <span class="min-w-0">
                <span class="block text-xs text-muted-foreground">
                    Sebelumnya
                </span>
                <span class="line-clamp-1 text-sm font-medium">
                    {{ neighbours.previous.title }}
                </span>
            </span>
        </Link>
        <span v-else class="hidden sm:block" />

        <Link
            v-if="neighbours.next"
            :href="href(neighbours.next)"
            class="group flex items-center justify-end gap-3 rounded-lg border p-4 text-right transition-colors hover:bg-muted/40"
        >
            <span class="min-w-0">
                <span class="block text-xs text-muted-foreground"
                    >Berikutnya</span
                >
                <span class="line-clamp-1 text-sm font-medium">
                    {{ neighbours.next.title }}
                </span>
            </span>
            <ChevronRight
                class="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
            />
        </Link>
    </nav>
</template>
