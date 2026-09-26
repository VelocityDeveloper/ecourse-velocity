<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import {
    ChevronDown,
    MessageCircle,
    MessagesSquare,
    SendHorizontal,
    Trash2,
} from '@lucide/vue';
import { nextTick, ref } from 'vue';
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

// Replies stay folded under "Lihat N balasan" so a long discussion stays scannable.
const expandedIds = ref<number[]>([]);

function isExpanded(questionId: number): boolean {
    return expandedIds.value.includes(questionId);
}

function toggleReplies(questionId: number): void {
    expandedIds.value = isExpanded(questionId)
        ? expandedIds.value.filter((id) => id !== questionId)
        : [...expandedIds.value, questionId];
}

// Replying opens the thread so the answer is written with its context in view.
function openReply(questionId: number): void {
    openReplyId.value = questionId;

    if (!isExpanded(questionId)) {
        expandedIds.value = [...expandedIds.value, questionId];
    }
}
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
    const formatter = new Intl.RelativeTimeFormat('id-ID', { numeric: 'auto' });

    for (const [unit, size] of units) {
        if (seconds >= size) {
            return formatter.format(-Math.floor(seconds / size), unit);
        }
    }

    return 'baru saja';
}

// The question box under the conversation; kept in view after posting so the
// new question (added at the bottom) shows right above it.
const composer = ref<HTMLFormElement | null>(null);

function ask(): void {
    // Ctrl + Enter reaches here even while the button is disabled.
    if (posting.value || questionBody.value.trim().length < 3) {
        return;
    }

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
            onSuccess: async () => {
                questionBody.value = '';
                await nextTick();
                composer.value?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'end',
                });
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
    return author.is_staff ? 'Instruktur' : '';
}
</script>

<template>
    <section class="space-y-4" aria-labelledby="discussion-heading">
        <h2
            id="discussion-heading"
            class="flex items-center gap-2 text-lg font-semibold"
        >
            <MessagesSquare class="h-4 w-4" />
            Diskusi
            <span class="text-sm font-normal text-muted-foreground">
                ({{ questions.length }})
            </span>
        </h2>

        <p
            v-if="questions.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Belum ada pertanyaan. Jadilah yang pertama memulai diskusi.
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
                    aria-label="Hapus pertanyaan"
                    @click="removeQuestion(question.id)"
                >
                    <Trash2 class="h-4 w-4 text-destructive" />
                </Button>
            </div>

            <div class="ml-11 flex flex-wrap items-center gap-1">
                <Button
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 text-muted-foreground"
                    @click="openReply(question.id)"
                >
                    <MessageCircle class="mr-1.5 h-3.5 w-3.5" />
                    Balas
                </Button>
                <Button
                    v-if="question.replies.length > 0"
                    variant="ghost"
                    size="sm"
                    class="h-auto px-2 py-1 font-semibold text-primary hover:text-primary"
                    :aria-expanded="isExpanded(question.id)"
                    :aria-controls="`replies-${question.id}`"
                    @click="toggleReplies(question.id)"
                >
                    <ChevronDown
                        class="mr-1 h-4 w-4 transition-transform"
                        :class="isExpanded(question.id) ? 'rotate-180' : ''"
                    />
                    {{
                        isExpanded(question.id)
                            ? 'Sembunyikan balasan'
                            : `Lihat ${question.replies.length} balasan`
                    }}
                </Button>
            </div>

            <ul
                v-if="question.replies.length > 0 && isExpanded(question.id)"
                :id="`replies-${question.id}`"
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
                        aria-label="Hapus balasan"
                        @click="removeReply(replyItem.id)"
                    >
                        <Trash2 class="h-3.5 w-3.5 text-destructive" />
                    </Button>
                </li>
            </ul>

            <div v-if="openReplyId === question.id" class="ml-11">
                <form class="space-y-2" @submit.prevent="reply(question.id)">
                    <Textarea
                        v-model="replyBodies[question.id]"
                        rows="2"
                        maxlength="5000"
                        :aria-label="`Balas ${question.author.name}`"
                        placeholder="Tulis balasan..."
                    />
                    <InputError :message="errors[`reply-${question.id}`]" />
                    <div class="flex justify-end gap-2">
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="openReplyId = null"
                        >
                            Batal
                        </Button>
                        <Button
                            size="sm"
                            :disabled="
                                posting ||
                                (replyBodies[question.id] ?? '').trim().length <
                                    3
                            "
                        >
                            Balas
                        </Button>
                    </div>
                </form>
            </div>
        </article>

        <!-- The question box sits under the conversation, like a chat. -->
        <form
            ref="composer"
            class="space-y-3 rounded-2xl border bg-card p-4 text-card-foreground shadow-sm"
            @submit.prevent="ask"
        >
            <Textarea
                v-model="questionBody"
                rows="3"
                maxlength="5000"
                aria-label="Ajukan pertanyaan tentang materi ini"
                class="resize-none"
                @keydown.ctrl.enter.prevent="ask"
                @keydown.meta.enter.prevent="ask"
                placeholder="Ada yang membingungkan? Tanyakan kepada instruktur dan teman sekelas Anda."
            />
            <InputError :message="errors.question" />
            <div class="flex items-center justify-between gap-3">
                <p class="text-xs text-muted-foreground">
                    Tekan Ctrl + Enter untuk mengirim.
                </p>
                <Button
                    size="sm"
                    class="rounded-xl font-bold"
                    :disabled="posting || questionBody.trim().length < 3"
                >
                    <SendHorizontal class="mr-1.5 h-4 w-4" />
                    Kirim pertanyaan
                </Button>
            </div>
        </form>

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
                                ? 'Hapus pertanyaan'
                                : 'Hapus balasan'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        {{
                            pendingDelete?.kind === 'question'
                                ? 'Pertanyaan beserta semua balasannya akan dihapus. Tindakan ini tidak bisa dibatalkan.'
                                : 'Balasan ini akan dihapus. Tindakan ini tidak bisa dibatalkan.'
                        }}
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="pendingDelete = null">
                        Batal
                    </Button>
                    <Button variant="destructive" @click="confirmDelete">
                        Hapus
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </section>
</template>
