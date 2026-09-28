<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import {
    Bookmark,
    BookmarkCheck,
    CircleCheck,
    Download,
    ExternalLink,
    FileText,
    Paperclip,
    PlayCircle,
    RotateCcw,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import LearnPager from '@/components/LearnPager.vue';
import LessonDiscussion from '@/components/LessonDiscussion.vue';
import LessonNotes from '@/components/LessonNotes.vue';
import LearnShell from '@/components/LearnShell.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatBytes, formatDuration } from '@/lib/course';
import learn from '@/routes/learn';
import type {
    DiscussionQuestion,
    LearningOutline,
    LearnLesson,
    OutlineNeighbours,
} from '@/types';

const props = defineProps<{
    outline: LearningOutline;
    lesson: LearnLesson;
    discussion: DiscussionQuestion[];
    neighbours: OutlineNeighbours;
}>();

const saving = ref(false);

const courseSlug = computed(() => props.outline.course.slug);
const routeParams = computed(() => ({
    course: courseSlug.value,
    lesson: props.lesson.slug,
}));

function markComplete(): void {
    saving.value = true;

    router.post(
        learn.lessons.complete(routeParams.value).url,
        {},
        {
            preserveScroll: true,
            onSuccess: () => {
                const next = props.neighbours.next;

                if (next) {
                    router.visit(
                        next.type === 'lesson'
                            ? learn.lessons.show({
                                  course: courseSlug.value,
                                  lesson: next.slug,
                              })
                            : learn.quizzes.show({
                                  course: courseSlug.value,
                                  quiz: next.slug,
                              }),
                    );
                }
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function toggleBookmark(): void {
    const url = props.lesson.is_bookmarked
        ? learn.lessons.bookmark.destroy(routeParams.value).url
        : learn.lessons.bookmark.store(routeParams.value).url;

    router.visit(url, {
        method: props.lesson.is_bookmarked ? 'delete' : 'post',
        preserveScroll: true,
        preserveState: true,
    });
}

function markIncomplete(): void {
    saving.value = true;

    router.delete(learn.lessons.uncomplete(routeParams.value).url, {
        preserveScroll: true,
        onFinish: () => {
            saving.value = false;
        },
    });
}
</script>

<template>
    <LearnShell :outline="outline" active-type="lesson" :active-id="lesson.id">
        <Head :title="`${lesson.title} · ${outline.course.title}`" />

        <header class="space-y-2">
            <div class="flex items-start justify-between gap-4">
                <div class="space-y-2">
                    <p class="text-sm font-semibold text-primary">
                        {{ lesson.section_title }}
                    </p>
                    <h1
                        class="text-3xl font-semibold tracking-tight text-balance"
                    >
                        {{ lesson.title }}
                    </h1>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    class="shrink-0"
                    :aria-pressed="lesson.is_bookmarked"
                    @click="toggleBookmark"
                >
                    <BookmarkCheck
                        v-if="lesson.is_bookmarked"
                        class="mr-2 h-4 w-4 text-primary"
                    />
                    <Bookmark v-else class="mr-2 h-4 w-4" />
                    {{ lesson.is_bookmarked ? 'Ditandai' : 'Tandai' }}
                </Button>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline">
                    <PlayCircle
                        v-if="lesson.content_type === 'video'"
                        class="h-3 w-3"
                    />
                    <FileText v-else class="h-3 w-3" />
                    {{ lesson.content_type === 'video' ? 'Video' : 'Artikel' }}
                </Badge>
                <span
                    v-if="lesson.duration_minutes"
                    class="text-sm text-muted-foreground"
                >
                    {{ formatDuration(lesson.duration_minutes) }}
                </span>
                <Badge v-if="lesson.is_completed">
                    <CircleCheck class="h-3 w-3" />
                    Selesai
                </Badge>
            </div>
        </header>

        <div
            v-if="lesson.embed_url"
            class="overflow-hidden rounded-xl border bg-muted"
        >
            <iframe
                :src="lesson.embed_url"
                :title="lesson.title"
                class="aspect-video w-full"
                allow="
                    accelerometer;
                    autoplay;
                    clipboard-write;
                    encrypted-media;
                    gyroscope;
                    picture-in-picture;
                "
                referrerpolicy="strict-origin-when-cross-origin"
                allowfullscreen
            />
        </div>
        <a
            v-else-if="lesson.content_url"
            :href="lesson.content_url"
            target="_blank"
            rel="noopener noreferrer"
            class="group flex items-center gap-4 rounded-lg border bg-card p-5 text-card-foreground transition-colors hover:bg-muted/40"
        >
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-chart-2/10 text-chart-2"
            >
                <PlayCircle class="h-5 w-5" />
            </span>
            <span class="min-w-0 flex-1">
                <span class="block font-medium">Tonton video materi</span>
                <span class="block truncate text-sm text-muted-foreground">
                    {{ lesson.content_url }}
                </span>
            </span>
            <ExternalLink class="h-4 w-4 shrink-0 text-muted-foreground" />
        </a>

        <!-- Lesson HTML is sanitised on save by SanitizeLessonContent. -->
        <article
            v-if="lesson.content"
            class="prose-editor max-w-none rounded-lg border bg-card p-6 text-card-foreground"
            v-html="lesson.content"
        />

        <p
            v-if="!lesson.content && !lesson.content_url"
            class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground"
        >
            Materi ini belum memiliki isi.
        </p>

        <section v-if="lesson.attachments.length > 0" class="space-y-3">
            <h2 class="flex items-center gap-2 text-lg font-semibold">
                <Paperclip class="h-4 w-4" />
                Lampiran
            </h2>
            <ul class="divide-y rounded-lg border">
                <li
                    v-for="attachment in lesson.attachments"
                    :key="attachment.id"
                >
                    <a
                        :href="learn.attachments.download(attachment.id).url"
                        class="flex items-center justify-between gap-3 px-4 py-3 text-sm transition-colors hover:bg-muted/40"
                    >
                        <span class="min-w-0 truncate font-medium">
                            {{ attachment.name }}
                        </span>
                        <span
                            class="flex shrink-0 items-center gap-2 text-muted-foreground"
                        >
                            {{ formatBytes(attachment.size) }}
                            <Download class="h-4 w-4" />
                        </span>
                    </a>
                </li>
            </ul>
        </section>

        <div
            class="flex flex-wrap items-center justify-between gap-3 rounded-lg border bg-muted/30 p-4"
        >
            <p class="text-sm text-muted-foreground">
                {{
                    lesson.is_completed
                        ? 'Anda telah menyelesaikan materi ini.'
                        : 'Sudah selesai dengan materi ini? Tandai selesai untuk melacak progres Anda.'
                }}
            </p>
            <Button
                v-if="lesson.is_completed"
                variant="ghost"
                size="sm"
                :disabled="saving"
                @click="markIncomplete"
            >
                <RotateCcw class="mr-2 h-4 w-4" />
                Tandai belum selesai
            </Button>
            <Button v-else :disabled="saving" @click="markComplete">
                <CircleCheck class="mr-2 h-4 w-4" />
                {{ neighbours.next ? 'Selesai & lanjutkan' : 'Tandai selesai' }}
            </Button>
        </div>

        <LessonNotes
            :course-slug="courseSlug"
            :lesson-slug="lesson.slug"
            :note="lesson.note"
        />

        <LearnPager :course-slug="courseSlug" :neighbours="neighbours" />

        <LessonDiscussion
            :course-slug="courseSlug"
            :lesson-slug="lesson.slug"
            :questions="discussion"
        />
    </LearnShell>
</template>
