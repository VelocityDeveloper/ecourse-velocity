<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref, watch } from 'vue';
import TestimonialController from '@/actions/App/Http/Controllers/Admin/TestimonialController';
import InputError from '@/components/InputError.vue';
import StarRatingInput from '@/components/StarRatingInput.vue';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { oversizedImageError } from '@/lib/imageUpload';
import type { AdminTestimonial } from '@/types';

// Add a testimonial, or edit one when `testimonial` is given.
const props = defineProps<{
    testimonial?: AdminTestimonial | null;
}>();

const open = defineModel<boolean>('open', { required: true });

// Keep in step with the server rule; PHP drops larger files silently.
const MAX_MB = 2;

const name = ref('');
const rating = ref(5);
const maskName = ref(false);
const isActive = ref(true);
const isRemovingPhoto = ref(false);
const preview = ref<string | null>(null);
const inputKey = ref(0);
const fileError = ref<string | null>(null);

const shownPhoto = computed(() => {
    if (preview.value) {
        return preview.value;
    }

    return isRemovingPhoto.value
        ? null
        : (props.testimonial?.photo_url ?? null);
});

// Same rule as Testimonial::mask(): keep each word's first and last letter.
const maskedName = computed(() =>
    name.value
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .map((word) =>
            word.length <= 2
                ? word
                : word[0] + '*'.repeat(word.length - 2) + word[word.length - 1],
        )
        .join(' '),
);

function clearPreview(): void {
    if (preview.value) {
        URL.revokeObjectURL(preview.value);
    }

    preview.value = null;
}

watch(open, (value) => {
    if (value) {
        name.value = props.testimonial?.name ?? '';
        rating.value = props.testimonial?.rating ?? 5;
        maskName.value = props.testimonial?.mask_name ?? false;
        isActive.value = props.testimonial?.is_active ?? true;
        isRemovingPhoto.value = false;
        fileError.value = null;
        clearPreview();
        inputKey.value++;
    }
});

function onPhotoChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    clearPreview();
    fileError.value = file ? oversizedImageError(file, MAX_MB) : null;

    if (fileError.value) {
        inputKey.value++;

        return;
    }

    if (file) {
        preview.value = URL.createObjectURL(file);
        isRemovingPhoto.value = false;
    }
}

function removePhoto(): void {
    clearPreview();
    inputKey.value++;
    isRemovingPhoto.value = true;
}

onBeforeUnmount(clearPreview);
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[90svh] overflow-y-auto sm:max-w-lg">
            <DialogHeader>
                <DialogTitle>{{
                    testimonial ? 'Ubah Testimoni' : 'Tambah Testimoni'
                }}</DialogTitle>
                <DialogDescription>
                    Tampil di seksi "Cerita Sukses Alumni" pada beranda.
                </DialogDescription>
            </DialogHeader>

            <Form
                v-bind="
                    testimonial
                        ? TestimonialController.update.form(testimonial.id)
                        : TestimonialController.store.form()
                "
                class="space-y-4"
                v-slot="{ errors, processing }"
                @success="open = false"
            >
                <div class="flex items-center gap-4">
                    <Avatar
                        class="size-14 shrink-0 overflow-hidden rounded-full"
                    >
                        <AvatarImage
                            v-if="shownPhoto"
                            :src="shownPhoto"
                            alt="Pratinjau foto"
                        />
                        <AvatarFallback
                            class="bg-primary font-semibold text-primary-foreground"
                        >
                            {{ getInitials(name || '?') }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 gap-1">
                        <Label for="testimonial_photo">Foto (opsional)</Label>
                        <Input
                            id="testimonial_photo"
                            :key="inputKey"
                            type="file"
                            name="photo"
                            accept="image/png,image/jpeg,image/webp"
                            @change="onPhotoChange"
                        />
                        <div class="flex items-center gap-3">
                            <p class="text-xs text-muted-foreground">
                                Tanpa foto, inisial nama yang tampil. Maks. 2
                                MB.
                            </p>
                            <Button
                                v-if="shownPhoto"
                                type="button"
                                variant="link"
                                size="sm"
                                class="h-auto p-0 text-xs text-destructive"
                                @click="removePhoto"
                            >
                                Hapus foto
                            </Button>
                        </div>
                    </div>
                </div>
                <input
                    type="hidden"
                    name="remove_photo"
                    :value="isRemovingPhoto ? 1 : 0"
                />
                <InputError :message="fileError ?? errors.photo" />

                <div class="grid gap-2">
                    <Label for="testimonial_name">Nama</Label>
                    <Input
                        id="testimonial_name"
                        v-model="name"
                        name="name"
                        maxlength="80"
                        required
                        placeholder="Nama alumni"
                    />
                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="maskName"
                            type="checkbox"
                            class="size-4 accent-primary"
                        />
                        Samarkan nama
                        <span
                            v-if="maskName && maskedName"
                            class="font-mono text-xs text-muted-foreground"
                            >→ {{ maskedName }}</span
                        >
                    </label>
                    <input
                        type="hidden"
                        name="mask_name"
                        :value="maskName ? 1 : 0"
                    />
                    <InputError :message="errors.name" />
                </div>

                <div class="grid gap-2">
                    <Label for="testimonial_subtitle"
                        >Instansi atau keterangan (opsional)</Label
                    >
                    <Input
                        id="testimonial_subtitle"
                        name="subtitle"
                        :default-value="testimonial?.subtitle ?? ''"
                        maxlength="120"
                        placeholder="Universitas, perusahaan, atau jabatan"
                    />
                    <InputError :message="errors.subtitle" />
                </div>

                <div class="grid gap-2">
                    <Label for="testimonial_quote">Testimoni</Label>
                    <Textarea
                        id="testimonial_quote"
                        name="quote"
                        :default-value="testimonial?.quote ?? ''"
                        maxlength="500"
                        rows="4"
                        required
                        placeholder="Apa kata alumni tentang kursusnya?"
                    />
                    <InputError :message="errors.quote" />
                </div>

                <div class="grid gap-2">
                    <Label>Rating</Label>
                    <StarRatingInput v-model="rating" />
                    <input type="hidden" name="rating" :value="rating" />
                    <InputError :message="errors.rating" />
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
