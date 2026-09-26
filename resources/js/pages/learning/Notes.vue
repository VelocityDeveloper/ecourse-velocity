<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { NotebookPen, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import LearningHeader from '@/components/LearningHeader.vue';
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
    <div>
        <Head title="Catatan Saya" />

        <LearningHeader
            title="Catatan Saya"
            description="Semua yang Anda catat selama belajar, dalam satu tempat."
        ></LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-6 px-4 py-10 sm:px-6"
        >
            <form
                class="flex w-full max-w-lg items-center gap-2 rounded-2xl border bg-card p-2 shadow-sm"
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
                        aria-label="Cari catatan"
                        placeholder="Cari catatan atau materi..."
                        class="border-0 pl-9 shadow-none focus-visible:ring-0"
                    />
                </div>
                <Button type="submit" class="rounded-xl font-bold">Cari</Button>
            </form>

            <div
                v-if="notes.length === 0"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <NotebookPen class="size-8 text-muted-foreground" />
                <p class="font-medium">
                    {{
                        filters.search
                            ? 'Tidak ada catatan yang cocok dengan pencarian Anda'
                            : 'Belum ada catatan'
                    }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Gunakan kotak "Catatan Saya" di bawah materi mana pun untuk
                    mulai menulis.
                </p>
            </div>

            <section
                v-for="group in groups"
                :key="group.course.id"
                class="space-y-3"
            >
                <h2
                    class="flex items-center gap-3 text-xl font-bold tracking-tight"
                >
                    <span
                        class="h-6 w-1 rounded-full bg-primary"
                        aria-hidden="true"
                    />
                    {{ group.course.title }}
                </h2>
                <article
                    v-for="note in group.notes"
                    :key="note.id"
                    class="space-y-3 rounded-2xl border bg-card p-5 text-card-foreground shadow-sm"
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
                            class="font-bold hover:text-primary"
                        >
                            {{ note.lesson.title }}
                        </Link>
                        <span class="text-xs text-muted-foreground">
                            {{ note.section.title }} · Diperbarui
                            {{ formatDate(note.updated_at) }}
                        </span>
                    </div>
                    <p
                        class="rounded-xl bg-muted/40 p-4 text-sm leading-relaxed whitespace-pre-line"
                    >
                        {{ note.body }}
                    </p>
                </article>
            </section>
        </div>
    </div>
</template>
