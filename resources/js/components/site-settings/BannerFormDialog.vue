<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import BannerController from '@/actions/App/Http/Controllers/Admin/BannerController';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { oversizedImageError } from '@/lib/imageUpload';
import type { AdminBanner } from '@/types';

// Add a promo banner, or edit one when `banner` is given.
const props = defineProps<{
    banner?: AdminBanner | null;
}>();

const open = defineModel<boolean>('open', { required: true });

const isActive = ref(true);
const preview = ref<string | null>(null);
const inputKey = ref(0);
// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 3;
const fileError = ref<string | null>(null);

const shownImage = computed(
    () => preview.value ?? props.banner?.image_url ?? null,
);

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

watch(open, (value) => {
    if (value) {
        isActive.value = props.banner?.is_active ?? true;
        fileError.value = null;
        clearPreview();
        inputKey.value++;
    }
});

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
    }
}

onBeforeUnmount(clearPreview);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    banner ? 'Ubah Banner' : 'Tambah Banner'
                }}</DialogTitle>
                <DialogDescription>
                    Banner tampil sebagai slider di bawah hero beranda.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    banner
                        ? BannerController.update.form(banner.id)
                        : BannerController.store.form()
                "
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <div class="grid gap-2">
                    <Label for="banner_image">Gambar</Label>
                    <div
                        class="flex aspect-[3/1] w-full items-center justify-center overflow-hidden rounded-lg border bg-muted text-xs text-muted-foreground"
                    >
                        <img
                            v-if="shownImage"
                            :src="shownImage"
                            alt="Pratinjau banner"
                            class="size-full object-cover"
                        />
                        <span v-else>Belum ada gambar</span>
                    </div>
                    <Input
                        id="banner_image"
                        :key="inputKey"
                        type="file"
                        name="image"
                        accept="image/png,image/jpeg,image/webp"
                        :required="!banner"
                        @change="onImageChange"
                    />
                    <p class="text-xs text-muted-foreground">
                        PNG, JPG, atau WebP, maksimal 3 MB. Rasio 3:1, misalnya
                        1500 × 500 px.
                    </p>
                    <InputError :message="fileError ?? errors.image" />
                </div>

                <div class="grid gap-2">
                    <Label for="banner_title">Judul</Label>
                    <Input
                        id="banner_title"
                        name="title"
                        :default-value="banner?.title ?? ''"
                        maxlength="120"
                        required
                        placeholder="Promo kelas baru bulan ini"
                    />
                    <p class="text-xs text-muted-foreground">
                        Dibacakan pembaca layar sebagai teks gambar.
                    </p>
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="banner_link">Tautan (opsional)</Label>
                    <Input
                        id="banner_link"
                        name="link_url"
                        :default-value="banner?.link_url ?? ''"
                        maxlength="255"
                        placeholder="/kursus atau https://..."
                    />
                    <InputError :message="errors.link_url" />
                </div>

                <label class="flex items-center gap-2 text-sm">
                    <input
                        v-model="isActive"
                        type="checkbox"
                        class="size-4 accent-primary"
                    />
                    Tampilkan di beranda
                </label>
                <input
                    type="hidden"
                    name="is_active"
                    :value="isActive ? 1 : 0"
                />

                <DialogFooter class="gap-2">
                    <DialogClose as-child>
                        <Button type="button" variant="secondary">Batal</Button>
                    </DialogClose>
                    <Button :disabled="processing">
                        {{ processing ? 'Menyimpan...' : 'Simpan' }}
                    </Button>
                </DialogFooter>
            </Form>
        </DialogContent>
    </Dialog>
</template>
