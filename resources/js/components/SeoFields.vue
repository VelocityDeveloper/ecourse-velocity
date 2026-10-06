<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

/**
 * The optional search-result title and description of a course or article,
 * with a preview of how Google shows the page. Empty fields use the defaults.
 */
const props = defineProps<{
    /** The page's own title and description, used while the fields are empty. */
    fallbackTitle: string;
    fallbackDescription: string;
    /** The page's path, such as /blog/judul-artikel. */
    path: string;
    errors?: Record<string, string>;
}>();

const title = defineModel<string>('title', { required: true });
const description = defineModel<string>('description', { required: true });

// Keep in step with App\Support\Seo.
const TITLE_LIMIT = 60;
const DESCRIPTION_LIMIT = 160;

const page = usePage();
const siteName = computed(() => page.props.name);

const shownTitle = computed(
    () =>
        title.value.trim() ||
        `${props.fallbackTitle || 'Judul'} - ${siteName.value}`,
);
const shownDescription = computed(() => {
    const text = (description.value.trim() || props.fallbackDescription)
        .replace(/<[^>]*>/g, ' ')
        .replace(/\s+/g, ' ')
        .trim();

    return text.length > DESCRIPTION_LIMIT
        ? `${text.slice(0, DESCRIPTION_LIMIT).trimEnd()}…`
        : text;
});
const url = computed(
    () =>
        `${typeof window === 'undefined' ? '' : window.location.origin}${props.path}`,
);

function tone(length: number, limit: number): string {
    return length > limit ? 'text-amber-600 dark:text-amber-400' : '';
}
</script>

<template>
    <div class="space-y-5">
        <div class="rounded-lg border bg-white p-3 text-left">
            <p class="mb-1 text-[11px] font-medium text-muted-foreground">
                Pratinjau di Google
            </p>
            <p class="truncate text-xs text-[#4d5156]">{{ url }}</p>
            <p class="line-clamp-2 text-base leading-snug text-[#1a0dab]">
                {{ shownTitle }}
            </p>
            <p class="line-clamp-2 text-xs leading-relaxed text-[#4d5156]">
                {{ shownDescription || 'Belum ada deskripsi.' }}
            </p>
        </div>

        <div class="grid gap-2">
            <Label for="seo_title">Judul SEO (opsional)</Label>
            <Input
                id="seo_title"
                v-model="title"
                maxlength="70"
                :placeholder="`${fallbackTitle || 'Judul'} - ${siteName}`"
            />
            <p
                class="flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
            >
                <span>Kosong = judul ditambah nama situs.</span>
                <span
                    class="tabular-nums"
                    :class="tone(shownTitle.length, TITLE_LIMIT)"
                    >{{ shownTitle.length }}/{{ TITLE_LIMIT }}</span
                >
            </p>
            <InputError :message="errors?.seo_title" />
        </div>

        <div class="grid gap-2">
            <Label for="seo_description">Deskripsi SEO (opsional)</Label>
            <Textarea
                id="seo_description"
                v-model="description"
                maxlength="300"
                rows="3"
                placeholder="Satu-dua kalimat yang membuat orang ingin mengklik hasil pencarian."
            />
            <p
                class="flex flex-wrap justify-between gap-x-3 text-xs text-muted-foreground"
            >
                <span
                    >Kosong = diambil dari awal deskripsi. Idealnya 120–{{
                        DESCRIPTION_LIMIT
                    }}
                    karakter.</span
                >
                <span
                    class="tabular-nums"
                    :class="tone(description.trim().length, DESCRIPTION_LIMIT)"
                    >{{ description.trim().length }}/{{
                        DESCRIPTION_LIMIT
                    }}</span
                >
            </p>
            <InputError :message="errors?.seo_description" />
        </div>
    </div>
</template>
