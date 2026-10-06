<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { MessageCircleQuestion } from '@lucide/vue';
import PanelCard from '@/components/dashboard/PanelCard.vue';
import type { UnansweredQuestion } from '@/lib/dashboard';
import { timeAgo } from '@/lib/dashboard';
import learn from '@/routes/learn';

defineProps<{ questions: UnansweredQuestion[]; total: number }>();
</script>

<template>
    <PanelCard
        title="Pertanyaan belum dijawab"
        :description="
            total > questions.length
                ? `${questions.length} terlama dari ${total}`
                : 'Yang paling lama menunggu di atas'
        "
    >
        <p
            v-if="questions.length === 0"
            class="flex flex-col items-center gap-2 px-4 py-10 text-center text-sm text-muted-foreground"
        >
            <MessageCircleQuestion class="size-6" />
            Semua pertanyaan sudah dijawab.
        </p>
        <ul v-else class="divide-y">
            <li v-for="question in questions" :key="question.id">
                <Link
                    :href="
                        learn.lessons.show({
                            course: question.course.slug,
                            lesson: question.lesson.slug,
                        })
                    "
                    class="block px-4 py-3 hover:bg-muted/50 sm:px-5"
                >
                    <p class="line-clamp-2 text-sm">"{{ question.body }}"</p>
                    <p class="mt-1 truncate text-xs text-muted-foreground">
                        {{ question.author.name }} ·
                        {{ question.course.title }} ·
                        {{ timeAgo(question.created_at) }}
                    </p>
                </Link>
            </li>
        </ul>
    </PanelCard>
</template>
