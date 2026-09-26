<script setup lang="ts">
import { Layers } from '@lucide/vue';
import { computed, onBeforeUnmount, ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { oversizedImageError } from '@/lib/imageUpload';

// The picture behind the category's card in the homepage "Belajar Sesuai Bidang"
// section. The preview mirrors that card: dark overlay, name on the left.
const props = defineProps<{
    currentUrl?: string | null;
    name: string;
    error?: string;
}>();

const file = defineModel<File | null>('file', { required: true });
const remove = defineModel<boolean>('remove', { default: false });

// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 3;

const inputKey = ref(0);
const preview = ref<string | null>(null);
const fileError = ref<string | null>(null);

const shownImage = computed(() => {
    if (preview.value) {
        return preview.value;
    }

    return remove.value ? null : (props.currentUrl ?? null);
});

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

function onChange(event: Event): void {
    const picked = (event.target as HTMLInputElement).files?.[0] ?? null;

    clearPreview();
    fileError.value = picked ? oversizedImageError(picked, MAX_MB) : null;

    if (fileError.value) {
        file.value = null;
        inputKey.value++;

        return;
    }

    file.value = picked;

    if (picked) {
        preview.value = URL.createObjectURL(picked);
        remove.value = false;
    }
}

function removeImage(): void {
    clearPreview();
    file.value = null;
    inputKey.value++;
    remove.value = true;
}

onBeforeUnmount(clearPreview);
</script>

<template>
    <div class="grid gap-2">
        <Label for="image">Gambar latar (opsional)</Label>
        <div
            class="relative isolate flex aspect-[2.6/1] w-full max-w-md flex-col justify-end overflow-hidden rounded-xl bg-surface p-5 text-surface-foreground"
        >
            <template v-if="shownImage">
                <img
                    :src="shownImage"
                    alt=""
                    class="absolute inset-0 -z-20 size-full object-cover"
                />
                <div
                    class="absolute inset-0 -z-10 bg-gradient-to-r from-surface via-surface/80 to-surface/20"
                />
            </template>
            <Layers
                v-else
                aria-hidden="true"
                class="absolute right-5 bottom-5 -z-10 size-16 text-surface-foreground/10"
            />
            <p class="text-lg font-extrabold">
                {{ name || 'Nama kategori' }}
            </p>
            <p class="text-xs text-surface-foreground/70">
                Pratinjau kartu di "Belajar Sesuai Bidang"
            </p>
        </div>
        <Input
            id="image"
            :key="inputKey"
            type="file"
            accept="image/png,image/jpeg,image/webp"
            @change="onChange"
        />
        <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
            <p class="text-xs text-muted-foreground">
                PNG, JPG, atau WebP, maksimal 3 MB. Pakai foto mendatar
                (misalnya 1200 × 600 px); sisi kiri diberi lapisan gelap agar
                teks terbaca.
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
        <InputError :message="fileError ?? error" />
    </div>
</template>
