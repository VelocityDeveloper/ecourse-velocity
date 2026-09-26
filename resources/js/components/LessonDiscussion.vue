<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MessageCircle, MessagesSquare, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import learn from '@/routes/learn';
import type { DiscussionAuthor, DiscussionQuestion } from '@/types';

const props = defineProps<{
    courseId: number;
    lessonId: number;
    questions: DiscussionQuestion[];
}>();

const questionBody = ref('');
const replyBodies = ref<Record<number, string>>({});
const openReplyId = ref<number | null>(null);
const errors = ref<Record<string, string>>({});
const posting = ref(false);

function timeAgo(date: string | null): string {
    if (date === null) {
        return '';
    }

    const seconds = Math.round((Date.now() - new Date(date).getTime()) / 1000);
    const units: Array<[Intl.RelativeTimeFormatUnit, number]> = [
        ['year', 31536000],
        ['month', 2592000],
        ['day', 86400],
        ['hour', 3600],
        ['minute', 60],
    ];
    const formatter = new Intl.RelativeTimeFormat('en', { numeric: 'auto' });

    for (const [unit, size] of units) {
        if (seconds >= size) {
            return formatter.format(-Math.floor(seconds / size), unit);
        }
    }

    return 'just now';
}

function ask(): void {
    posting.value = true;
    errors.value = {};

    router.post(
        learn.questions.store({
            course: props.courseId,
            lesson: props.lessonId,
        }).url,
        { body: questionBody.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                questionBody.value = '';
            },
            onError: (err) => {
                errors.value = { question: err.body };
            },
            onFinish: () => {
                posting.value = false;
            },
        },
    );
}

function reply(questionId: number): void {
    posting.value = true;
    errors.value = {};

    router.post(
        learn.replies.store(questionId).url,
        { body: replyBodies.value[questionId] ?? '' },
        {
            preserveScroll: true,
            onSuccess: () => {
                replyBodies.value[questionId] = '';
                openReplyId.value = null;
            },
            onError: (err) => {
                errors.value = { [`reply-${questionId}`]: err.body };
            },
            onFinish: () => {
                posting.value = false;
            },
        },
    );
}

const pendingDelete = ref<{ kind: 'question' | 'reply'; id: number } | null>(
    null,
);

function removeQuestion(questionId: number): void {
    pendingDelete.value = { kind: 'question', id: questionId };
}

function removeReply(replyId: number): void {
    pendingDelete.value = { kind: 'reply', id: replyId };
}

function confirmDelete(): void {
    const target = pendingDelete.value;

    if (target === null) {
        return;
    }

    const url =
        target.kind === 'question'
            ? learn.questions.destroy(target.id).url
            : learn.replies.destroy(target.id).url;

    router.delete(url, {
        preserveScroll: true,
        onFinish: () => {
            pendingDelete.value = null;
        },
    });
}

function authorLabel(author: DiscussionAuthor): string {
    return author.is_staff ? 'Instructor' : '';
}
</script>

<template>
    <section class="space-y-4" aria-labelledby="discussion-heading">
        <h2
            id="discussion-heading"
            class="flex items-center gap-2 text-lg font-semibold"
        >
            <MessagesSquare class="h-4 w-4" />
            Discussion
            <span class="text-sm font-normal text-muted-foreground">
                ({{ questions.length }})
            </span>
        </h2>

        <form
            class="space-y-2 rounded-lg border bg-card p-4 text-card-foreground"
            @submit.prevent="ask"
        >
            <Textarea
                v-model="questionBody"
                rows="3"
                maxlength="5000"
                aria-label="Ask a question about this lesson"
                placeholder="Stuck on something? Ask the instructor and your classmates."
            />
            <InputError :message="errors.question" />
            <div class="flex justify-end">
                <Button
                    size="sm"
                    :disabled="posting || questionBody.trim().length < 3"
                >
                    Post question
                </Button>
            </div>
        </form>

        <p
            v-if="questions.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            No questions yet. Be the first to start the discussion.
        </p>

        <article
            v-for="question in questions"
            :key="question.id"
            class="space-y-3 rounded-lg border bg-card p-4 text-card-foreground"
        >
            <div class="flex items-start gap-3">
                <Avatar class="size-8 shrink-0 overflow-hidden rounded-full">
                    <AvatarImage
                        v-if="question.author.avatar"
                        :src="question.author.avatar"
                        :alt="question.author.name"
                    />
                    <AvatarFallback class="text-xs">
                        {{ getInitials(question.author.name) }}
                    </AvatarFallback>
                </Avatar>
                <div class="min-w-0 flex-1 space-y-1">
                    <div class="flex flex-wrap items-center gap-2 text-sm">
                        <span class="font-medium">{{
                            question.author.name
                        }}</span>
                        <Badge
                            v-if="question.author.is_staff"
                            variant="secondary"
                        >
                            {{ authorLabel(question.author) }}
                        </Badge>
                        <span class="text-xs text-muted-foreground">
                            {{ timeAgo(question.created_at) }}
                        </span>
                    </div>
                    <p class="text-sm whitespace-pre-line">
                        {{ question.body }}
                    </p>
                </div>
                <Button
                    v-if="question.can_delete"
                    variant="ghost"
                    size="icon"
                    class="size-8 shrink-0"
                    aria-label="Delete question"
                    @click="removeQuestion(question.id)"
                >
                    <Trash2 class="h-4 w-4 text-destructive" />
                </Button>
            </div>

            <ul
                v-if="question.replies.length > 0"
                class="ml-11 space-y-3 border-l pl-4"
            >
                <li
                    v-for="replyItem in question.replies"
                    :key="replyItem.id"
                    class="flex items-start gap-3"
                >
                    <Avatar
                        class="size-7 shrink-0 overflow-hidden rounded-full"
                    >
                        <AvatarImage
                            v-if="replyItem.author.avatar"
                            :src="replyItem.author.avatar"
                            :alt="replyItem.author.name"
                        />
                        <AvatarFallback class="text-[10px]">
                            {{ getInitials(replyItem.author.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2 text-sm">
                            <span class="font-medium">{{
                                replyItem.author.name
                            }}</span>
                            <Badge
                                v-if="replyItem.author.is_staff"
                                variant="secondary"
                            >
                                {{ authorLabel(replyItem.author) }}
                            </Badge>
                            <span class="text-xs text-muted-foreground">
                                {{ timeAgo(replyItem.created_at) }}
                            </span>
                        </div>
                        <p class="text-sm whitespace-pre-line">
                            {{ replyItem.body }}
                        </p>
                    </div>
                    <Button
                        v-if="replyItem.can_delete"
                        variant="ghost"
                        size="icon"
                        class="size-7 shrink-0"
                        aria-label="Delete reply"
                        @click="removeReply(replyItem.id)"
                    >
                        <Trash2 class="h-3.5 w-3.5 text-destructive" />
                    </Button>
                </li>
            </ul>

            <div class="ml-11">
                <form
                    v-if="openReplyId === question.id"
                    class="space-y-2"
                    @submit.prevent="reply(question.id)"
                >
                    <Textarea
                        v-model="replyBodies[question.id]"
                        rows="2"
                        maxlength="5000"
                        :aria-label="`Reply to ${question.author.name}`"
                        placeholder="Write a reply..."
                    />
                    <InputError :message="errors[`reply-${question.id}`]" />
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="openReplyId = null"
                        >
                            Cancel
                        </Button>
                        <Button
                            size="sm"
                            :disabled="
                                posting ||
                                (replyBodies[question.id] ?? '').trim().length <
                                    3
                            "
                        >
                            Reply
                        </Button>
                    </div>
                </form>
                <Button
                    v-else
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 text-muted-foreground"
                    @click="openReplyId = question.id"
                >
                    <MessageCircle class="mr-1.5 h-3.5 w-3.5" />
                    Reply
                </Button>
            </div>
        </article>

        <Dialog
            :open="pendingDelete !== null"
            @update:open="
                (open) => {
                    if (!open) pendingDelete = null;
                }
            "
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>
                        {{
                            pendingDelete?.kind === 'question'
                                ? 'Delete question'
                                : 'Delete reply'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            pendingDelete?.kind === 'question'
                                ? 'The question and all of its replies will be removed. This cannot be undone.'
                                : 'This reply will be removed. This cannot be undone.'
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="pendingDelete = null">
                        Cancel
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Delete
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
