<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { oversizedImageError } from '@/lib/imageUpload';
import { Textarea } from '@/components/ui/textarea';
import type { BannerSettingsData, BannerTextKey } from '@/types';

const props = defineProps<BannerSettingsData>();

type Field = {
    key: BannerTextKey;
    label: string;
    max: number;
    multiline?: boolean;
    hint?: string;
};

const GROUPS: { title: string; description: string; fields: Field[] }[] = [
    {
        title: 'Hero',
        description: 'Bagian paling atas beranda.',
        fields: [
            { key: 'hero_badge', label: 'Label kecil', max: 60 },
            { key: 'hero_title', label: 'Judul', max: 120 },
            {
                key: 'hero_highlight',
                label: 'Judul yang disorot',
                max: 80,
                hint: 'Tampil berwarna setelah judul.',
            },
            {
                key: 'hero_description',
                label: 'Deskripsi',
                max: 300,
                multiline: true,
            },
        ],
    },
    {
        title: 'Banner ajakan',
        description:
            'Kotak berwarna di bawah beranda, untuk pengunjung yang belum masuk.',
        fields: [
            { key: 'cta_title', label: 'Judul', max: 120 },
            {
                key: 'cta_description',
                label: 'Deskripsi',
                max: 300,
                multiline: true,
            },
        ],
    },
];

const inputKey = ref(0);
// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 3;
const fileError = ref<string | null>(null);
const preview = ref<string | null>(null);
const isRemoving = ref(false);

const shownImage = computed(() => {
    if (preview.value) {
        return preview.value;
    }

    return isRemoving.value ? null : props.heroImageUrl;
});

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

function onImageChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    clearPreview();
    fileError.value = file ? oversizedImageError(file, MAX_MB) : null;

    if (fileError.value) {
        inputKey.value++;

        return;
    }

    if (file) {
        preview.value = URL.createObjectURL(file);
        isRemoving.value = false;
    }
}

function removeImage(): void {
    clearPreview();
    inputKey.value++;
    isRemoving.value = true;
}

function onSaved(): void {
    clearPreview();
    inputKey.value++;
    isRemoving.value = false;
}

onBeforeUnmount(clearPreview);
</script>

<template>
    <section class="space-y-4" aria-labelledby="banner-heading">
        <div>
            <h2 id="banner-heading" class="font-semibold">Teks dan gambar</h2>
            <p class="text-sm text-muted-foreground">
                Kosongkan kolom untuk memakai teks bawaan yang tampil sebagai
                contoh di dalam kolom.
            </p>
        </div>

        <Form
            v-bind="SiteSettingController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
            @success="onSaved"
        >
            <input type="hidden" name="section" value="hero" />
            <fieldset
                v-for="group in GROUPS"
                :key="group.title"
                class="space-y-4 rounded-lg border p-4"
            >
                <legend class="px-1 text-sm font-medium">
                    {{ group.title }}
                </legend>
                <p class="-mt-2 text-xs text-muted-foreground">
                    {{ group.description }}
                </p>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div
                        v-for="field in group.fields"
                        :key="field.key"
                        class="grid content-start gap-2"
                        :class="{ 'sm:col-span-2': field.multiline }"
                    >
                        <Label :for="field.key">{{ field.label }}</Label>
                        <Textarea
                            v-if="field.multiline"
                            :id="field.key"
                            :name="field.key"
                            :default-value="texts[field.key] ?? ''"
                            :placeholder="defaults[field.key]"
                            :maxlength="field.max"
                            rows="3"
                        />
                        <Input
                            v-else
                            :id="field.key"
                            :name="field.key"
                            :default-value="texts[field.key] ?? ''"
                            :placeholder="defaults[field.key]"
                            :maxlength="field.max"
                        />
                        <p
                            v-if="field.hint"
                            class="text-xs text-muted-foreground"
                        >
                            {{ field.hint }}
                        </p>
                        <InputError :message="errors[field.key]" />
                    </div>
                </div>

                <div v-if="group.title === 'Hero'" class="grid gap-2">
                    <Label for="hero_image">Foto latar hero</Label>
                    <div
                        class="relative flex aspect-video w-full max-w-sm items-center justify-center overflow-hidden rounded-lg border bg-surface text-center text-xs text-surface-foreground/70"
                    >
                        <template v-if="shownImage">
                            <img
                                :src="shownImage"
                                alt="Pratinjau foto latar hero"
                                class="absolute inset-0 size-full object-cover"
                            />
                            <div
                                class="absolute inset-0 bg-gradient-to-r from-surface via-surface/85 to-surface/30"
                            />
                            <span
                                class="relative mr-auto ml-4 text-left text-sm font-extrabold text-surface-foreground"
                                >Contoh judul hero</span
                            >
                        </template>
                        <span v-else class="px-4">
                            Tanpa foto: hero memakai latar gelap dengan
                            ilustrasi bawaan.
                        </span>
                    </div>
                    <Input
                        id="hero_image"
                        :key="inputKey"
                        type="file"
                        name="hero_image"
                        accept="image/png,image/jpeg,image/webp"
                        @change="onImageChange"
                    />
                    <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                        <p class="text-xs text-muted-foreground">
                            PNG, JPG, atau WebP, maksimal 3 MB. Pakai foto
                            mendatar (lebar minimal 1600 px); sisi kiri diberi
                            lapisan gelap agar teks tetap terbaca.
                        </p>
                        <Button
                            v-if="shownImage"
                            type="button"
                            variant="link"
                            size="sm"
                            class="h-auto p-0 text-xs text-destructive"
                            @click="removeImage"
                        >
                            Hapus gambar
                        </Button>
                    </div>
                    <input
                        type="hidden"
                        name="remove_hero_image"
                        :value="isRemoving ? 1 : 0"
                    />
                    <InputError :message="fileError ?? errors.hero_image" />
                </div>
            </fieldset>

            <Button :disabled="processing">
                {{ processing ? 'Menyimpan...' : 'Simpan hero' }}
            </Button>
        </Form>
    </section>
</template>
