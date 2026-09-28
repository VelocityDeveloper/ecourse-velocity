<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { Trash2 } from '@lucide/vue';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import StarRating from '@/components/StarRating.vue';
import StarRatingInput from '@/components/StarRatingInput.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import reviewRoutes from '@/routes/reviews';
import type { CourseReviewEntry, RatingSummary } from '@/types';

const props = defineProps<{
    courseSlug: string;
    rating: RatingSummary;
    reviews: CourseReviewEntry[];
    myReview: { id: number; rating: number; comment: string | null } | null;
    canReview: boolean;
}>();

const STARS = [5, 4, 3, 2, 1];

const form = ref({
    rating: props.myReview?.rating ?? 0,
    comment: props.myReview?.comment ?? '',
});
const errors = ref<Record<string, string>>({});
const saving = ref(false);
const editing = ref(props.myReview === null);

watch(
    () => props.myReview,
    (review) => {
        form.value = {
            rating: review?.rating ?? 0,
            comment: review?.comment ?? '',
        };
        editing.value = review === null;
    },
);

function barWidth(stars: number): string {
    if (props.rating.count === 0) {
        return '0%';
    }

    return `${Math.round(((props.rating.distribution[stars] ?? 0) / props.rating.count) * 100)}%`;
}

function submit(): void {
    saving.value = true;
    errors.value = {};

    router.put(
        catalogRoutes.review.update(props.courseSlug).url,
        { ...form.value },
        {
            preserveScroll: true,
            onError: (err) => {
                errors.value = err;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}

function remove(reviewId: number): void {
    router.delete(reviewRoutes.destroy(reviewId).url, { preserveScroll: true });
}
</script>

<template>
    <section class="flex flex-col gap-4" aria-labelledby="reviews-heading">
        <h2
            id="reviews-heading"
            class="flex items-center gap-3 text-xl font-bold tracking-tight"
        >
            <span class="h-6 w-1 rounded-full bg-primary" aria-hidden="true" />
            Ulasan Siswa
        </h2>

        <div class="grid gap-6 rounded-lg border p-5 sm:grid-cols-[160px_1fr]">
            <div
                class="flex flex-col items-center justify-center gap-1 text-center"
            >
                <p class="text-4xl font-semibold tracking-tight">
                    {{ rating.average?.toFixed(1) ?? '–' }}
                </p>
                <StarRating :rating="rating.average" size="md" />
                <p class="text-sm text-muted-foreground">
                    {{ rating.count }} penilaian
                </p>
            </div>
            <ul class="space-y-1.5" aria-label="Rincian penilaian">
                <li
                    v-for="stars in STARS"
                    :key="stars"
                    class="flex items-center gap-3 text-sm"
                >
                    <span
                        class="w-16 shrink-0 whitespace-nowrap text-muted-foreground"
                    >
                        {{ stars }} bintang
                    </span>
                    <div
                        class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                    >
                        <div
                            class="h-full rounded-full bg-rating"
                            :style="{ width: barWidth(stars) }"
                        />
                    </div>
                    <span
                        class="w-8 shrink-0 text-right text-muted-foreground tabular-nums"
                    >
                        {{ rating.distribution[stars] ?? 0 }}
                    </span>
                </li>
            </ul>
        </div>

        <div
            v-if="canReview"
            class="space-y-3 rounded-lg border bg-muted/30 p-5"
        >
            <template v-if="editing">
                <p class="font-medium">
                    {{
                        myReview
                            ? 'Ubah ulasan Anda'
                            : 'Beri penilaian kursus ini'
                    }}
                </p>
                <form class="space-y-3" @submit.prevent="submit">
                    <StarRatingInput v-model="form.rating" />
                    <InputError :message="errors.rating" />
                    <Textarea
                        v-model="form.comment"
                        rows="3"
                        maxlength="2000"
                        aria-label="Ulasan Anda"
                        placeholder="Apa yang Anda sukai? Apa yang bisa lebih baik? (opsional)"
                    />
                    <InputError :message="errors.comment" />
                    <div class="flex justify-end gap-2">
                        <Button
                            v-if="myReview"
                            type="button"
                            variant="ghost"
                            @click="editing = false"
                        >
                            Batal
                        </Button>
                        <Button :disabled="saving || form.rating === 0">
                            {{ saving ? 'Menyimpan...' : 'Kirim ulasan' }}
                        </Button>
                    </div>
                </form>
            </template>
            <div
                v-else-if="myReview"
                class="flex flex-wrap items-center justify-between gap-3"
            >
                <div class="space-y-1">
                    <p class="text-sm font-medium">Ulasan Anda</p>
                    <StarRating :rating="myReview.rating" />
                    <p
                        v-if="myReview.comment"
                        class="text-sm whitespace-pre-line text-muted-foreground"
                    >
                        {{ myReview.comment }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Button variant="outline" size="sm" @click="editing = true">
                        Ubah
                    </Button>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="text-destructive"
                        @click="remove(myReview.id)"
                    >
                        Hapus
                    </Button>
                </div>
            </div>
        </div>

        <p
            v-if="reviews.length === 0"
            class="rounded-lg border border-dashed p-6 text-center text-sm text-muted-foreground"
        >
            Belum ada ulasan tertulis.
        </p>

        <article
            v-for="review in reviews"
            :key="review.id"
            class="flex items-start gap-3 rounded-lg border p-4"
        >
            <Avatar class="size-9 shrink-0 overflow-hidden rounded-full">
                <AvatarImage
                    v-if="review.author.avatar"
                    :src="review.author.avatar"
                    :alt="review.author.name"
                />
                <AvatarFallback class="text-xs">
                    {{ getInitials(review.author.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1 space-y-1">
                <div class="flex flex-wrap items-center gap-2">
                    <span class="text-sm font-medium">{{
                        review.author.name
                    }}</span>
                    <StarRating :rating="review.rating" />
                    <span class="text-xs text-muted-foreground">
                        {{ formatDate(review.created_at) }}
                    </span>
                </div>
                <p class="text-sm whitespace-pre-line text-muted-foreground">
                    {{ review.comment }}
                </p>
            </div>
            <Button
                v-if="review.can_delete"
                variant="ghost"
                size="icon"
                class="size-8 shrink-0"
                :aria-label="`Hapus ulasan dari ${review.author.name}`"
                @click="remove(review.id)"
            >
                <Trash2 class="h-4 w-4 text-destructive" />
            </Button>
        </article>
    </section>
</template>
