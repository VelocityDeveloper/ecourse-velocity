<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { NotebookPen, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import LearningNav from '@/components/LearningNav.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { formatDate } from '@/lib/course';
import learn from '@/routes/learn';
import learning from '@/routes/learning';
import type { NoteEntry } from '@/types';

const props = defineProps<{
    notes: NoteEntry[];
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

/**
 * Notes grouped by course, keeping the newest-first order inside each group.
 */
const groups = computed(() => {
    const byCourse = new Map<
        number,
        { course: NoteEntry['course']; notes: NoteEntry[] }
    >();

    for (const note of props.notes) {
        const group = byCourse.get(note.course.id) ?? {
            course: note.course,
            notes: [],
        };

        group.notes.push(note);
        byCourse.set(note.course.id, group);
    }

    return [...byCourse.values()];
});

function applySearch(): void {
    router.get(
        learning.notes().url,
        { search: search.value || undefined },
        { preserveState: true, replace: true },
    );
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-10 sm:px-6"
    >
        <Head title="My Notes" />

        <div class="space-y-6">
            <div class="max-w-2xl space-y-2">
                <p class="text-sm font-semibold text-primary">My learning</p>
                <h1 class="text-3xl font-semibold tracking-tight">My Notes</h1>
                <p class="text-muted-foreground">
                    Everything you wrote down while learning, in one place.
                </p>
            </div>
            <LearningNav />
        </div>

        <form
            class="flex max-w-md gap-2"
            role="search"
            @submit.prevent="applySearch"
        >
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    aria-label="Search notes"
                    placeholder="Search notes or lessons..."
                    class="pl-9"
                />
            </div>
            <Button variant="outline" type="submit">Search</Button>
        </form>

        <div
            v-if="notes.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-10 text-center"
        >
            <NotebookPen class="size-8 text-muted-foreground" />
            <p class="font-medium">
                {{
                    filters.search
                        ? 'No notes match your search'
                        : 'No notes yet'
                }}
            </p>
            <p class="text-sm text-muted-foreground">
                Use the "My notes" box under any lesson to start writing.
            </p>
        </div>

        <section
            v-for="group in groups"
            :key="group.course.id"
            class="space-y-3"
        >
            <h2 class="text-lg font-semibold">{{ group.course.title }}</h2>
            <article
                v-for="note in group.notes"
                :key="note.id"
                class="space-y-2 rounded-lg border bg-card p-5 text-card-foreground"
            >
                <div
                    class="flex flex-wrap items-baseline justify-between gap-2"
                >
                    <Link
                        :href="
                            learn.lessons.show({
                                course: note.course.id,
                                lesson: note.lesson.id,
                            })
                        "
                        class="font-medium hover:underline"
                    >
                        {{ note.lesson.title }}
                    </Link>
                    <span class="text-xs text-muted-foreground">
                        {{ note.section.title }} · Updated
                        {{ formatDate(note.updated_at) }}
                    </span>
                </div>
                <p class="text-sm whitespace-pre-line text-muted-foreground">
                    {{ note.body }}
                </p>
            </article>
        </section>
    </div>
</template>
