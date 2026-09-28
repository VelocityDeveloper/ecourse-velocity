<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { ExternalLink, ImageIcon } from '@lucide/vue';
import { computed, onBeforeUnmount, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { oversizedImageError } from '@/lib/imageUpload';
import blogRoutes from '@/routes/blog';
import postRoutes from '@/routes/admin/posts';
import type { PostFormValues, PostStatus } from '@/types';

// Writes a new article when `post` is missing, edits it otherwise.
const props = defineProps<{
    post?: PostFormValues;
}>();

// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 3;

const STATUS_LABELS: Record<PostStatus, string> = {
    draft: 'Draf',
    published: 'Terbit',
};

// <input type="datetime-local"> wants local "YYYY-MM-DDTHH:mm".
function toLocalInput(iso: string | null | undefined): string {
    if (!iso) {
        return '';
    }

    const date = new Date(iso);
    const offset = date.getTimezoneOffset() * 60_000;

    return new Date(date.getTime() - offset).toISOString().slice(0, 16);
}

const form = reactive({
    title: props.post?.title ?? '',
    slug: props.post?.slug ?? '',
    excerpt: props.post?.excerpt ?? '',
    content: props.post?.content ?? '',
    status: (props.post?.status ?? 'draft') as PostStatus,
    published_at: toLocalInput(props.post?.published_at),
    cover: null as File | null,
    remove_cover: false,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

const coverInputKey = ref(0);
const coverPreview = ref<string | null>(null);
const coverError = ref<string | null>(null);

const shownCover = computed(() => {
    if (coverPreview.value) {
        return coverPreview.value;
    }

    return form.remove_cover ? null : (props.post?.cover_url ?? null);
});

const isScheduled = computed(
    () =>
        form.status === 'published' &&
        form.published_at !== '' &&
        new Date(form.published_at).getTime() > Date.now(),
);

function clearPreview(): void {
    if (coverPreview.value) {
        URL.revokeObjectURL(coverPreview.value);
    }

    coverPreview.value = null;
}

function onCoverChange(event: Event): void {
    const picked = (event.target as HTMLInputElement).files?.[0] ?? null;

    clearPreview();
    coverError.value = picked ? oversizedImageError(picked, MAX_MB) : null;

    if (coverError.value) {
        form.cover = null;
        coverInputKey.value++;

        return;
    }

    form.cover = picked;

    if (picked) {
        coverPreview.value = URL.createObjectURL(picked);
        form.remove_cover = false;
    }
}

function removeCover(): void {
    clearPreview();
    form.cover = null;
    coverInputKey.value++;
    form.remove_cover = true;
}

onBeforeUnmount(clearPreview);

function submit(): void {
    processing.value = true;
    errors.value = {};

    const data = {
        ...form,
        published_at: form.published_at
            ? new Date(form.published_at).toISOString()
            : '',
    };

    // Files need multipart, which PUT cannot carry: POST with a spoofed method.
    router.post(
        props.post
            ? postRoutes.update(props.post.slug).url
            : postRoutes.store().url,
        props.post ? { ...data, _method: 'put' } : data,
        {
            forceFormData: true,
            // Keep what was typed when validation fails; start fresh from the saved article otherwise.
            preserveState: 'errors',
            preserveScroll: true,
            onError: (err) => {
                errors.value = err as Record<string, string>;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <form
        class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_18rem]"
        @submit.prevent="submit"
    >
        <div class="min-w-0 space-y-6">
            <div class="grid gap-2">
                <Label for="title">Judul</Label>
                <Input
                    id="title"
                    v-model="form.title"
                    required
                    placeholder="Cara Belajar Pemrograman agar Konsisten"
                    class="text-base"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <div class="flex items-center">
                    <span
                        class="flex h-9 items-center rounded-l-md border border-r-0 bg-muted px-3 text-sm text-muted-foreground"
                        >/blog/</span
                    >
                    <Input
                        id="slug"
                        v-model="form.slug"
                        class="rounded-l-none"
                        placeholder="Kosongkan untuk dibuat dari judul"
                    />
                </div>
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="excerpt">Ringkasan (opsional)</Label>
                <Textarea
                    id="excerpt"
                    v-model="form.excerpt"
                    rows="3"
                    maxlength="500"
                    placeholder="Satu-dua kalimat untuk kartu artikel. Kosongkan untuk memakai awal isi artikel."
                />
                <InputError :message="errors.excerpt" />
            </div>

            <div class="grid gap-2">
                <Label>Isi artikel</Label>
                <RichTextEditor v-model="form.content" />
                <p class="text-xs text-muted-foreground">
                    Pakai Judul 2 dan Judul 3 untuk subjudul. Format dirapikan
                    saat disimpan.
                </p>
                <InputError :message="errors.content" />
            </div>
        </div>

        <aside class="space-y-6 lg:sticky lg:top-4 lg:self-start">
            <div class="space-y-4 rounded-lg border p-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="text-sm font-semibold">Terbitkan</h3>
                    <Badge
                        v-if="post"
                        :variant="post.is_live ? 'default' : 'secondary'"
                    >
                        {{ post.is_live ? 'Tayang' : 'Belum tayang' }}
                    </Badge>
                </div>

                <div class="grid gap-2">
                    <Label for="status">Status</Label>
                    <Select v-model="form.status">
                        <SelectTrigger id="status" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="(label, value) in STATUS_LABELS"
                                :key="value"
                                :value="value"
                            >
                                {{ label }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.status" />
                </div>

                <div class="grid gap-2">
                    <Label for="published-at">Tanggal terbit</Label>
                    <Input
                        id="published-at"
                        v-model="form.published_at"
                        type="datetime-local"
                    />
                    <p class="text-xs text-muted-foreground">
                        {{
                            isScheduled
                                ? 'Terjadwal: tayang otomatis pada tanggal ini.'
                                : 'Kosongkan untuk memakai waktu saat diterbitkan.'
                        }}
                    </p>
                    <InputError :message="errors.published_at" />
                </div>

                <div class="flex flex-wrap items-center gap-2 pt-1">
                    <Button :disabled="processing" class="flex-1">
                        {{ processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                    <Button
                        v-if="post"
                        as-child
                        variant="outline"
                        :title="post.is_live ? 'Lihat artikel' : 'Pratinjau'"
                    >
                        <a
                            :href="blogRoutes.show(post.slug).url"
                            target="_blank"
                            rel="noopener"
                        >
                            <ExternalLink class="h-4 w-4" />
                            {{ post.is_live ? 'Lihat' : 'Pratinjau' }}
                        </a>
                    </Button>
                </div>
                <Link
                    v-if="!post"
                    :href="postRoutes.index()"
                    class="block text-center text-sm text-muted-foreground hover:text-foreground"
                >
                    Batal
                </Link>
            </div>

            <div class="space-y-3 rounded-lg border p-4">
                <Label for="cover" class="text-sm font-semibold"
                    >Gambar sampul</Label
                >
                <div
                    class="flex aspect-video w-full items-center justify-center overflow-hidden rounded-md border bg-muted text-muted-foreground"
                >
                    <img
                        v-if="shownCover"
                        :src="shownCover"
                        alt=""
                        class="size-full object-cover"
                    />
                    <ImageIcon v-else class="size-8" aria-hidden="true" />
                </div>
                <Input
                    id="cover"
                    :key="coverInputKey"
                    type="file"
                    accept="image/png,image/jpeg,image/webp"
                    @change="onCoverChange"
                />
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p class="text-xs text-muted-foreground">
                        PNG, JPG, atau WebP, maksimal 3 MB. Ukuran ideal 1200 ×
                        675 px.
                    </p>
                    <Button
                        v-if="shownCover"
                        type="button"
                        variant="link"
                        size="sm"
                        class="h-auto p-0 text-xs text-destructive"
                        @click="removeCover"
                    >
                        Hapus gambar
                    </Button>
                </div>
                <InputError :message="coverError ?? errors.cover" />
            </div>
        </aside>
    </form>
</template>
