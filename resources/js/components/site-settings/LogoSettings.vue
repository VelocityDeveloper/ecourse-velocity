<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import SiteSettingController from '@/actions/App/Http/Controllers/Admin/SiteSettingController';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { oversizedImageError } from '@/lib/imageUpload';

const props = defineProps<{
    logoUrl: string | null;
}>();

const inputKey = ref(0);
// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 2;
const fileError = ref<string | null>(null);
const preview = ref<string | null>(null);
const isRemoving = ref(false);

// What the header will show after saving: the picked file, the current upload,
// or null for the built-in logo.
const shownLogo = computed(() => {
    if (preview.value) {
        return preview.value;
    }

    return isRemoving.value ? null : props.logoUrl;
});

const isDirty = computed(() => preview.value !== null || isRemoving.value);

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

function onLogoChange(event: Event): void {
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

function useDefaultLogo(): void {
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

const PREVIEWS = [
    { label: 'Tema saat ini', themeClass: '' },
    { label: 'Tema gelap', themeClass: 'dark' },
];
</script>

<template>
    <section class="space-y-4" aria-labelledby="logo-heading">
        <div>
            <h2 id="logo-heading" class="font-semibold">Logo</h2>
            <p class="text-sm text-muted-foreground">
                Tampil di header, footer, dasbor, dan halaman masuk.
            </p>
        </div>

        <Form
            v-bind="SiteSettingController.update.form()"
            class="space-y-4"
            v-slot="{ errors, processing }"
            @success="onSaved"
        >
            <input type="hidden" name="section" value="identitas" />
            <div class="grid gap-2">
                <Label for="logo" class="sr-only">Berkas logo</Label>

                <div class="grid gap-3 sm:grid-cols-2">
                    <div
                        v-for="item in PREVIEWS"
                        :key="item.label"
                        :class="item.themeClass"
                    >
                        <div
                            class="flex h-16 items-center rounded-lg border bg-background px-4 text-foreground"
                        >
                            <img
                                v-if="shownLogo"
                                :src="shownLogo"
                                alt="Pratinjau logo"
                                class="h-8 w-auto max-w-44 object-contain object-left"
                            />
                            <span v-else class="flex items-center gap-2">
                                <span
                                    class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                                >
                                    <AppLogoIcon class="size-5" />
                                </span>
                                <AppWordmark />
                            </span>
                        </div>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ item.label }}
                        </p>
                    </div>
                </div>

                <Input
                    id="logo"
                    :key="inputKey"
                    type="file"
                    name="logo"
                    accept="image/png,image/jpeg,image/webp"
                    @change="onLogoChange"
                />
                <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
                    <p class="text-xs text-muted-foreground">
                        PNG, JPG, atau WebP, maksimal 2 MB. Pakai gambar
                        mendatar berlatar transparan; logo tampil setinggi 32
                        px.
                    </p>
                    <Button
                        v-if="shownLogo"
                        type="button"
                        variant="link"
                        size="sm"
                        class="h-auto p-0 text-xs text-destructive"
                        @click="useDefaultLogo"
                    >
                        Kembalikan logo bawaan
                    </Button>
                </div>
                <input
                    type="hidden"
                    name="remove_logo"
                    :value="isRemoving ? 1 : 0"
                />
                <InputError :message="fileError ?? errors.logo" />
            </div>

            <Button :disabled="processing || !isDirty">
                {{ processing ? 'Menyimpan...' : 'Simpan' }}
            </Button>
        </Form>
    </section>
</template>
