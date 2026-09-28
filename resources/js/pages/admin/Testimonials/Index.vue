<script setup lang="ts">
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';
import { Head, router } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    ExternalLink,
    Eye,
    EyeOff,
    MessageSquareQuote,
    PenLine,
    Pencil,
    Plus,
    Sparkles,
    Trash2,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import Heading from '@/components/Heading.vue';
import StarRating from '@/components/StarRating.vue';
import TestimonialFormDialog from '@/components/site-settings/TestimonialFormDialog.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import InputError from '@/components/InputError.vue';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { home } from '@/routes';
import type { AdminTestimonial } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            { title: 'Testimoni', href: '/dasbor/pengaturan-situs/testimoni' },
        ],
    },
});

type Source = 'reviews' | 'manual';

const props = defineProps<{
    testimonials: AdminTestimonial[];
    source: Source;
    reviews: Array<{
        id: number;
        name: string;
        course: string;
        quote: string;
        rating: number;
        avatar: string | null;
        created_at: string | null;
    }>;
    minRating: number;
    limit: number;
    limitRange: { min: number; max: number };
}>();

const SOURCES: Array<{ value: Source; title: string; description: string }> = [
    {
        value: 'reviews',
        title: 'Otomatis dari ulasan kursus',
        description:
            'Ulasan terbaru dengan komentar dan rating tinggi, selalu ikut terbaru.',
    },
    {
        value: 'manual',
        title: 'Testimoni manual',
        description:
            'Hanya testimoni yang Anda tulis di bawah, dengan urutan pilihan Anda.',
    },
];

const savingSource = ref(false);

function chooseSource(source: Source): void {
    if (source === props.source || savingSource.value) {
        return;
    }

    savingSource.value = true;
    router.post(
        TestimonialController.updateSource().url,
        { source },
        {
            preserveScroll: true,
            onFinish: () => {
                savingSource.value = false;
            },
        },
    );
}

// How many testimonials the homepage shows
const limitInput = ref(String(props.limit));
const limitError = ref<string | undefined>();
const savingLimit = ref(false);

watch(
    () => props.limit,
    (value) => {
        limitInput.value = String(value);
    },
);

const limitDirty = computed(() => Number(limitInput.value) !== props.limit);

function saveLimit(): void {
    savingLimit.value = true;
    limitError.value = undefined;
    router.post(
        TestimonialController.updateLimit().url,
        { limit: limitInput.value },
        {
            preserveScroll: true,
            onError: (errors) => {
                limitError.value = errors.limit;
            },
            onFinish: () => {
                savingLimit.value = false;
            },
        },
    );
}

// Active testimonials past the limit are kept but not shown on the homepage.
const hiddenByLimit = computed(() => {
    const ids = new Set<number>();
    props.testimonials
        .filter((item) => item.is_active)
        .slice(props.limit)
        .forEach((item) => ids.add(item.id));

    return ids;
});

const formOpen = ref(false);
const editing = ref<AdminTestimonial | null>(null);
const deleting = ref<AdminTestimonial | null>(null);

function openCreate(): void {
    editing.value = null;
    formOpen.value = true;
}

function openEdit(item: AdminTestimonial): void {
    editing.value = item;
    formOpen.value = true;
}

function move(item: AdminTestimonial, direction: 'up' | 'down'): void {
    router.post(
        TestimonialController.move(item.id).url,
        { direction },
        { preserveScroll: true },
    );
}

function toggle(item: AdminTestimonial): void {
    router.post(
        TestimonialController.update(item.id).url,
        {
            name: item.name,
            subtitle: item.subtitle ?? '',
            quote: item.quote,
            rating: item.rating,
            is_active: item.is_active ? 0 : 1,
        },
        { preserveScroll: true },
    );
}

function destroy(): void {
    if (!deleting.value) {
        return;
    }

    router.delete(TestimonialController.destroy(deleting.value.id).url, {
        preserveScroll: true,
        onFinish: () => {
            deleting.value = null;
        },
    });
}
</script>

<template>
    <Head title="Testimoni · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Testimoni"
                description='Cerita alumni di seksi "Cerita Sukses Alumni" beranda.'
            />
            <a :href="home().url" target="_blank" rel="noopener">
                <Button variant="outline">
                    <ExternalLink class="mr-2 h-4 w-4" />
                    Lihat di beranda
                </Button>
            </a>
        </div>

        <!-- Where the homepage quotes come from -->
        <div
            class="grid gap-3 sm:grid-cols-2"
            role="radiogroup"
            aria-label="Sumber testimoni"
        >
            <button
                v-for="option in SOURCES"
                :key="option.value"
                type="button"
                role="radio"
                :aria-checked="source === option.value"
                :disabled="savingSource"
                class="flex items-start gap-3 rounded-lg border p-4 text-left transition-colors disabled:opacity-70"
                :class="
                    source === option.value
                        ? 'border-primary bg-primary/5 ring-1 ring-primary'
                        : 'hover:bg-muted/50'
                "
                @click="chooseSource(option.value)"
            >
                <span
                    class="mt-0.5 flex size-4 shrink-0 items-center justify-center rounded-full border"
                    :class="
                        source === option.value
                            ? 'border-primary'
                            : 'border-muted-foreground/40'
                    "
                >
                    <span
                        v-if="source === option.value"
                        class="size-2 rounded-full bg-primary"
                    />
                </span>
                <span>
                    <span class="flex items-center gap-2 font-medium">
                        <component
                            :is="
                                option.value === 'reviews' ? Sparkles : PenLine
                            "
                            class="size-4 text-primary"
                        />
                        {{ option.title }}
                    </span>
                    <span class="mt-1 block text-sm text-muted-foreground">{{
                        option.description
                    }}</span>
                </span>
            </button>
        </div>

        <!-- How many of them the homepage shows -->
        <form
            class="flex flex-col gap-3 rounded-lg border p-4 sm:flex-row sm:items-end sm:justify-between"
            @submit.prevent="saveLimit"
        >
            <div class="space-y-1">
                <label for="testimonial-limit" class="font-medium"
                    >Jumlah yang tampil di beranda</label
                >
                <p class="text-sm text-muted-foreground">
                    Berlaku untuk kedua sumber, {{ limitRange.min }}–{{
                        limitRange.max
                    }}
                    testimoni. Beranda menampilkan 3 sekaligus di layar lebar
                    dan menggeser sisanya.
                </p>
                <InputError :message="limitError" />
            </div>
            <div class="flex shrink-0 items-center gap-2">
                <Input
                    id="testimonial-limit"
                    v-model="limitInput"
                    type="number"
                    inputmode="numeric"
                    :min="limitRange.min"
                    :max="limitRange.max"
                    class="w-24"
                />
                <Button type="submit" :disabled="savingLimit || !limitDirty">
                    Simpan
                </Button>
            </div>
        </form>

        <!-- Automatic: the reviews that are on the homepage right now -->
        <section v-if="source === 'reviews'" class="space-y-3">
            <div>
                <h3 class="font-semibold">Tampil di beranda</h3>
                <p class="text-sm text-muted-foreground">
                    {{ reviews.length }} ulasan terbaru berkomentar dengan
                    rating {{ minRating }}–5 dari kursus yang terbit. Daftar ini
                    ikut berganti saat ada ulasan baru.
                </p>
            </div>

            <div
                v-if="reviews.length === 0"
                class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
            >
                <MessageSquareQuote class="mx-auto mb-2 size-8" />
                Belum ada ulasan yang memenuhi syarat.
            </div>

            <ul v-else class="grid gap-3 lg:grid-cols-2">
                <li
                    v-for="review in reviews"
                    :key="review.id"
                    class="flex gap-3 rounded-lg border p-4"
                >
                    <Avatar
                        class="size-10 shrink-0 overflow-hidden rounded-full"
                    >
                        <AvatarImage
                            v-if="review.avatar"
                            :src="review.avatar"
                            :alt="review.name"
                        />
                        <AvatarFallback
                            class="bg-primary text-sm font-semibold text-primary-foreground"
                        >
                            {{ getInitials(review.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div
                            class="flex flex-wrap items-center justify-between gap-2"
                        >
                            <span class="font-medium">{{ review.name }}</span>
                            <StarRating :rating="review.rating" />
                        </div>
                        <p
                            class="truncate text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ review.course }}
                        </p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            “{{ review.quote }}”
                        </p>
                    </div>
                </li>
            </ul>
        </section>

        <!-- Hand-written testimonials -->
        <div
            v-if="source === 'manual'"
            class="flex flex-wrap items-end justify-between gap-3"
        >
            <div>
                <h3 class="font-semibold">Testimoni manual</h3>
                <p class="text-sm text-muted-foreground">
                    Selama belum ada testimoni aktif, beranda tetap memakai
                    ulasan kursus terbaru.
                </p>
            </div>
            <Button @click="openCreate">
                <Plus class="mr-2 h-4 w-4" />
                Tambah Testimoni
            </Button>
        </div>

        <template v-if="source === 'manual'">
            <div
                v-if="testimonials.length === 0"
                class="rounded-lg border border-dashed p-10 text-center"
            >
                <MessageSquareQuote
                    class="mx-auto size-10 text-muted-foreground"
                />
                <h2 class="mt-3 font-semibold">Belum ada testimoni</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Tambahkan cerita dari alumni lewat tombol Tambah Testimoni.
                </p>
            </div>

            <ul v-else class="space-y-3">
                <li
                    v-for="(item, index) in testimonials"
                    :key="item.id"
                    class="flex flex-col gap-4 rounded-lg border p-4 sm:flex-row sm:items-center"
                    :class="item.is_active ? '' : 'bg-muted/40'"
                >
                    <Avatar
                        class="size-12 shrink-0 overflow-hidden rounded-full"
                        :class="item.is_active ? '' : 'opacity-60'"
                    >
                        <AvatarImage
                            v-if="item.photo_url"
                            :src="item.photo_url"
                            :alt="item.name"
                        />
                        <AvatarFallback
                            class="bg-primary font-semibold text-primary-foreground"
                        >
                            {{ getInitials(item.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0 flex-1 space-y-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="font-medium">{{
                                item.display_name
                            }}</span>
                            <Badge
                                :variant="
                                    item.is_active ? 'default' : 'outline'
                                "
                            >
                                {{ item.is_active ? 'Active' : 'Hidden' }}
                            </Badge>
                            <Badge
                                v-if="hiddenByLimit.has(item.id)"
                                variant="outline"
                                class="border-amber-500/50 text-amber-700 dark:text-amber-400"
                            >
                                Melebihi jumlah tampil
                            </Badge>
                            <StarRating :rating="item.rating" />
                        </div>
                        <p
                            v-if="item.subtitle"
                            class="text-xs font-semibold tracking-wide text-muted-foreground uppercase"
                        >
                            {{ item.subtitle }}
                        </p>
                        <p class="line-clamp-2 text-sm text-muted-foreground">
                            “{{ item.quote }}”
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <Button
                            variant="ghost"
                            size="icon"
                            :disabled="index === 0"
                            aria-label="Pindahkan ke atas"
                            @click="move(item, 'up')"
                        >
                            <ArrowUp class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :disabled="index === testimonials.length - 1"
                            aria-label="Pindahkan ke bawah"
                            @click="move(item, 'down')"
                        >
                            <ArrowDown class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            :aria-label="
                                item.is_active
                                    ? 'Sembunyikan dari beranda'
                                    : 'Tampilkan di beranda'
                            "
                            @click="toggle(item)"
                        >
                            <EyeOff v-if="item.is_active" class="h-4 w-4" />
                            <Eye v-else class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Ubah testimoni"
                            @click="openEdit(item)"
                        >
                            <Pencil class="h-4 w-4" />
                        </Button>
                        <Button
                            variant="ghost"
                            size="icon"
                            aria-label="Hapus testimoni"
                            @click="deleting = item"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </li>
            </ul>
        </template>

        <TestimonialFormDialog v-model:open="formOpen" :testimonial="editing" />

        <Dialog
            :open="deleting !== null"
            @update:open="(value) => !value && (deleting = null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Hapus Testimoni</DialogTitle>
                    <DialogDescription>
                        Hapus testimoni dari
                        <strong>{{ deleting?.name }}</strong
                        >? Tindakan ini tidak bisa dibatalkan.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button variant="destructive" @click="destroy"
                        >Hapus</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
