<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { MessageSquareText, Search, Star, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/course';
import reviewRoutes from '@/routes/reviews';
import type { DashboardReview, Paginated, RatingSummary } from '@/types';

const ANY = 'all';
const STARS = [5, 4, 3, 2, 1];

// The reviews of one course, inside the course's own menu.
const props = defineProps<{
    url: string;
    reviews: Paginated<DashboardReview>;
    summary: RatingSummary;
    filters: {
        search?: string;
        rating?: string | number;
    };
}>();

const search = ref(props.filters.search ?? '');
const rating = ref(String(props.filters.rating ?? ANY) || ANY);

function currentQuery(page?: number) {
    return {
        search: search.value || undefined,
        rating: rating.value === ANY ? undefined : rating.value,
        page,
    };
}

function applyFilters() {
    router.get(props.url, currentQuery(), {
        preserveState: true,
        replace: true,
    });
}

function filterByStars(stars: number) {
    rating.value = rating.value === String(stars) ? ANY : String(stars);
    applyFilters();
}

function goToPage(page: number) {
    router.get(props.url, currentQuery(page), { preserveState: true });
}

function barWidth(stars: number): string {
    if (props.summary.count === 0) {
        return '0%';
    }

    return `${Math.round(((props.summary.distribution[stars] ?? 0) / props.summary.count) * 100)}%`;
}

function remove(reviewId: number) {
    router.delete(reviewRoutes.destroy(reviewId).url, { preserveScroll: true });
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <div
            class="grid gap-6 rounded-lg border p-5 sm:grid-cols-[auto_1fr] sm:items-center"
        >
            <div class="flex flex-col items-center gap-1 sm:px-6">
                <span class="text-4xl font-bold">
                    {{ summary.average?.toFixed(1) ?? '-' }}
                </span>
                <StarRating :rating="summary.average" size="md" />
                <span class="text-sm text-muted-foreground">
                    {{ summary.count }} ulasan
                </span>
            </div>

            <ul class="flex flex-col gap-1.5">
                <li v-for="stars in STARS" :key="stars">
                    <button
                        type="button"
                        class="flex w-full items-center gap-3 rounded-md px-2 py-1 text-sm transition-colors hover:bg-muted/60"
                        :class="{ 'bg-muted': rating === String(stars) }"
                        :aria-pressed="rating === String(stars)"
                        @click="filterByStars(stars)"
                    >
                        <span
                            class="inline-flex w-8 items-center gap-1 text-muted-foreground"
                        >
                            {{ stars }}
                            <Star
                                class="size-3.5 fill-current text-rating"
                                aria-hidden="true"
                            />
                        </span>
                        <span
                            class="h-2 flex-1 overflow-hidden rounded-full bg-muted"
                        >
                            <span
                                class="block h-full rounded-full bg-rating"
                                :style="{ width: barWidth(stars) }"
                            />
                        </span>
                        <span class="w-8 text-right text-muted-foreground">
                            {{ summary.distribution[stars] ?? 0 }}
                        </span>
                    </button>
                </li>
            </ul>
        </div>

        <div class="flex flex-wrap items-center gap-3">
            <div class="relative w-full sm:w-auto sm:max-w-sm sm:flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Cari nama siswa atau isi ulasan..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>

            <Select v-model="rating" @update:model-value="applyFilters">
                <SelectTrigger class="w-[160px]">
                    <SelectValue placeholder="Semua Bintang" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem :value="ANY">Semua Bintang</SelectItem>
                    <SelectItem
                        v-for="stars in STARS"
                        :key="stars"
                        :value="String(stars)"
                    >
                        {{ stars }} bintang
                    </SelectItem>
                </SelectContent>
            </Select>

            <Button variant="outline" @click="applyFilters">Cari</Button>
        </div>

        <div
            v-if="reviews.data.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed py-12 text-center text-muted-foreground"
        >
            <MessageSquareText class="size-8" aria-hidden="true" />
            <p>Belum ada ulasan.</p>
        </div>

        <ul v-else class="flex flex-col gap-3">
            <li
                v-for="review in reviews.data"
                :key="review.id"
                class="flex gap-4 rounded-lg border p-4"
            >
                <Avatar class="size-10 shrink-0">
                    <AvatarImage
                        v-if="review.author.avatar"
                        :src="review.author.avatar"
                        :alt="review.author.name"
                    />
                    <AvatarFallback>
                        {{ getInitials(review.author.name) }}
                    </AvatarFallback>
                </Avatar>

                <div class="min-w-0 flex-1 space-y-1.5">
                    <div
                        class="flex flex-wrap items-start justify-between gap-x-4 gap-y-1"
                    >
                        <p class="min-w-0 font-medium">
                            {{ review.author.name }}
                        </p>
                        <div class="flex items-center gap-2">
                            <StarRating :rating="review.rating" />
                            <span class="text-xs text-muted-foreground">
                                {{ formatDate(review.created_at) }}
                            </span>
                        </div>
                    </div>

                    <p
                        v-if="review.comment"
                        class="text-sm whitespace-pre-line"
                    >
                        {{ review.comment }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground italic">
                        Hanya memberi rating, tanpa komentar.
                    </p>
                </div>

                <Dialog v-if="review.can_delete">
                    <DialogTrigger as-child>
                        <Button
                            variant="ghost"
                            size="sm"
                            class="shrink-0 self-start text-muted-foreground hover:text-destructive"
                            aria-label="Hapus ulasan"
                        >
                            <Trash2 class="h-4 w-4" />
                        </Button>
                    </DialogTrigger>
                    <DialogContent>
                        <DialogHeader>
                            <DialogTitle>Hapus ulasan?</DialogTitle>
                            <DialogDescription>
                                Ulasan {{ review.author.name }} untuk "{{
                                    review.course.title
                                }}" akan dihapus dan rating kursus dihitung
                                ulang.
                            </DialogDescription>
                        </DialogHeader>
                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button variant="secondary">Batal</Button>
                            </DialogClose>
                            <DialogClose as-child>
                                <Button
                                    variant="destructive"
                                    @click="remove(review.id)"
                                    >Hapus</Button
                                >
                            </DialogClose>
                        </DialogFooter>
                    </DialogContent>
                </Dialog>
            </li>
        </ul>

        <div
            v-if="reviews.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="reviews.current_page <= 1"
                @click="goToPage(reviews.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ reviews.current_page }} dari {{ reviews.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="reviews.current_page >= reviews.last_page"
                @click="goToPage(reviews.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
