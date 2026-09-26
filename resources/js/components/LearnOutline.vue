<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookmarkCheck,
    CircleCheck,
    FileText,
    ListChecks,
    PlayCircle,
} from '@lucide/vue';
import { formatDuration, formatTimeLimit } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import learn from '@/routes/learn';
import type { LearningOutline, OutlineItem } from '@/types';

const props = defineProps<{
    outline: LearningOutline;
    activeType?: 'lesson' | 'quiz';
    activeId?: number;
}>();

function itemHref(item: OutlineItem) {
    return item.type === 'lesson'
        ? learn.lessons.show({
              course: props.outline.course.id,
              lesson: item.id,
          })
        : learn.quizzes.show({
              course: props.outline.course.id,
              quiz: item.id,
          });
}

function isActive(item: OutlineItem): boolean {
    return item.type === props.activeType && item.id === props.activeId;
}
</script>

<template>
    <nav class="flex flex-col gap-5" aria-label="Isi kursus">
        <div class="space-y-3">
            <Link
                :href="catalogRoutes.show(outline.course.id)"
                class="line-clamp-2 font-semibold hover:underline"
            >
                {{ outline.course.title }}
            </Link>
            <div class="space-y-1.5">
                <div
                    class="h-2 overflow-hidden rounded-full bg-muted"
                    role="progressbar"
                    :aria-valuenow="outline.progress.percent"
                    aria-valuemin="0"
                    aria-valuemax="100"
                    aria-label="Progres kursus"
                >
                    <div
                        class="h-full rounded-full bg-primary transition-[width]"
                        :style="{ width: `${outline.progress.percent}%` }"
                    />
                </div>
                <p class="text-xs text-muted-foreground">
                    {{ outline.progress.completed }} dari
                    {{ outline.progress.total }} selesai ·
                    {{ outline.progress.percent }}%
                </p>
            </div>
        </div>

        <div
            v-for="(section, sectionIndex) in outline.sections"
            :key="section.id"
            class="space-y-1"
        >
            <p
                class="px-2 text-xs font-medium tracking-wide text-muted-foreground uppercase"
            >
                {{ sectionIndex + 1 }}. {{ section.title }}
            </p>
            <Link
                v-for="item in section.items"
                :key="`${item.type}-${item.id}`"
                :href="itemHref(item)"
                preserve-scroll
                class="flex items-start gap-2 rounded-md px-2 py-2 text-sm transition-colors hover:bg-muted/60"
                :class="{ 'bg-muted font-medium': isActive(item) }"
                :aria-current="isActive(item) ? 'page' : undefined"
            >
                <CircleCheck
                    v-if="item.is_done"
                    class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                    aria-label="Selesai"
                />
                <ListChecks
                    v-else-if="item.type === 'quiz'"
                    class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                />
                <PlayCircle
                    v-else-if="item.content_type === 'video'"
                    class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                />
                <FileText
                    v-else
                    class="mt-0.5 h-4 w-4 shrink-0 text-muted-foreground"
                />
                <span class="min-w-0 flex-1">
                    <span class="line-clamp-2">{{ item.title }}</span>
                    <span class="text-xs font-normal text-muted-foreground">
                        {{
                            item.type === 'quiz'
                                ? `Kuis · ${formatTimeLimit(item.time_limit_minutes)}`
                                : formatDuration(item.duration_minutes)
                        }}
                    </span>
                </span>
                <BookmarkCheck
                    v-if="item.type === 'lesson' && item.is_bookmarked"
                    class="mt-0.5 h-3.5 w-3.5 shrink-0 text-muted-foreground"
                    aria-label="Ditandai"
                />
            </Link>
        </div>
    </nav>
</template>
