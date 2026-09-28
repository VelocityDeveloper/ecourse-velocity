<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { computed, reactive, ref } from 'vue';
import InputError from '@/components/InputError.vue';
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
import { levelLabel, statusLabel } from '@/lib/course';
import courses from '@/routes/courses';
import type {
    CourseFormValues,
    CourseLevel,
    CourseOption,
    CourseStatus,
} from '@/types';

export type CourseFormTab = 'info' | 'price' | 'thumbnail' | 'status';

// Which tab holds each field, so a failed save can jump to the right one.
const TAB_FIELDS: Record<CourseFormTab, string[]> = {
    info: ['title', 'slug', 'description', 'category_id', 'instructor_id'],
    price: ['price', 'level'],
    thumbnail: ['thumbnail'],
    status: ['status'],
};

const NONE = 'none';

// One form shown a tab at a time; every tab is saved together.
const props = defineProps<{
    tab: CourseFormTab;
    course: CourseFormValues;
    categories: CourseOption[];
    instructors: CourseOption[];
    levels: CourseLevel[];
    statuses: CourseStatus[];
    isAdmin: boolean;
}>();

const emit = defineEmits<{ 'update:tab': [tab: CourseFormTab] }>();

const form = reactive({
    title: props.course.title,
    slug: props.course.slug,
    description: props.course.description ?? '',
    category_id:
        props.course.category_id === null
            ? NONE
            : String(props.course.category_id),
    instructor_id: String(props.course.instructor_id),
    price: props.course.price,
    level: props.course.level,
    status: props.course.status,
    thumbnail: null as File | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const thumbnailPreview = ref<string | null>(null);

const tabsWithErrors = computed(
    () =>
        new Set(
            (Object.keys(TAB_FIELDS) as CourseFormTab[]).filter((tab) =>
                TAB_FIELDS[tab].some((field) => errors.value[field]),
            ),
        ),
);

defineExpose({ tabsWithErrors });

function onThumbnailChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.thumbnail = input.files?.[0] ?? null;

    if (thumbnailPreview.value) {
        URL.revokeObjectURL(thumbnailPreview.value);
    }

    thumbnailPreview.value = form.thumbnail
        ? URL.createObjectURL(form.thumbnail)
        : null;
}

function submit() {
    processing.value = true;
    errors.value = {};

    router.post(
        courses.update(props.course.slug).url,
        {
            _method: 'put',
            ...form,
            category_id: form.category_id === NONE ? null : form.category_id,
        },
        {
            forceFormData: true,
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                form.thumbnail = null;
            },
            onError: (err) => {
                errors.value = err as Record<string, string>;

                const withError = tabsWithErrors.value.values().next().value;

                if (withError) {
                    emit('update:tab', withError);
                }
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <form @submit.prevent="submit" class="max-w-2xl space-y-6">
        <div
            v-show="tab === 'info'"
            id="panel-info"
            role="tabpanel"
            aria-labelledby="tab-info"
            class="space-y-6"
        >
            <div class="grid gap-2">
                <Label for="title">Judul</Label>
                <Input
                    id="title"
                    v-model="form.title"
                    required
                    placeholder="Judul kursus"
                />
                <InputError :message="errors.title" />
            </div>
            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="Kosongkan untuk dibuat ulang dari judul"
                />
                <InputError :message="errors.slug" />
            </div>
            <div class="grid gap-2">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="5"
                    placeholder="Apa yang akan dipelajari siswa?"
                />
                <InputError :message="errors.description" />
            </div>
            <div class="grid gap-6 sm:grid-cols-2">
                <div class="grid gap-2">
                    <Label for="category">Kategori</Label>
                    <Select v-model="form.category_id">
                        <SelectTrigger id="category">
                            <SelectValue placeholder="Pilih kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="NONE"
                                >Tanpa kategori</SelectItem
                            >
                            <SelectItem
                                v-for="category in categories"
                                :key="category.id"
                                :value="String(category.id)"
                            >
                                {{ category.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.category_id" />
                </div>
                <div v-if="isAdmin" class="grid gap-2">
                    <Label for="instructor">Instruktur</Label>
                    <Select v-model="form.instructor_id">
                        <SelectTrigger id="instructor">
                            <SelectValue placeholder="Pilih instruktur" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="instructor in instructors"
                                :key="instructor.id"
                                :value="String(instructor.id)"
                            >
                                {{ instructor.name }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.instructor_id" />
                </div>
            </div>
        </div>

        <div
            v-show="tab === 'price'"
            id="panel-price"
            role="tabpanel"
            aria-labelledby="tab-price"
            class="grid gap-6 sm:grid-cols-2"
        >
            <div class="grid gap-2">
                <Label for="price">Harga (IDR)</Label>
                <Input
                    id="price"
                    v-model="form.price"
                    type="number"
                    min="0"
                    step="1000"
                    required
                />
                <InputError :message="errors.price" />
            </div>
            <div class="grid gap-2">
                <Label for="level">Tingkat</Label>
                <Select v-model="form.level">
                    <SelectTrigger id="level">
                        <SelectValue placeholder="Pilih tingkat" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="level in levels"
                            :key="level"
                            :value="level"
                        >
                            {{ levelLabel(level) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.level" />
            </div>
        </div>

        <div
            v-show="tab === 'thumbnail'"
            id="panel-thumbnail"
            role="tabpanel"
            aria-labelledby="tab-thumbnail"
            class="space-y-6"
        >
            <div class="grid gap-2">
                <Label for="thumbnail">Gambar sampul</Label>
                <img
                    v-if="thumbnailPreview || course.thumbnail_url"
                    :src="thumbnailPreview ?? course.thumbnail_url ?? ''"
                    :alt="course.title"
                    class="aspect-video w-full max-w-md rounded-lg border object-cover"
                />
                <div
                    v-else
                    class="flex aspect-video w-full max-w-md items-center justify-center rounded-lg border bg-muted text-sm text-muted-foreground"
                >
                    Tanpa gambar sampul
                </div>
                <Input
                    id="thumbnail"
                    type="file"
                    accept="image/*"
                    @change="onThumbnailChange"
                />
                <p class="text-xs text-muted-foreground">
                    Kosongkan untuk mempertahankan gambar saat ini. JPG, PNG,
                    atau WebP, maksimal 2 MB.
                </p>
                <InputError :message="errors.thumbnail" />
            </div>
        </div>

        <div
            v-show="tab === 'status'"
            id="panel-status"
            role="tabpanel"
            aria-labelledby="tab-status"
            class="space-y-6"
        >
            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="status">
                        <SelectValue placeholder="Pilih status" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="status in statuses"
                            :key="status"
                            :value="status"
                        >
                            {{ statusLabel(status) }}
                        </SelectItem>
                    </SelectContent>
                </Select>
                <p v-if="!isAdmin" class="text-xs text-muted-foreground">
                    Hanya administrator yang dapat menerbitkan atau mengarsipkan
                    kursus.
                </p>
                <InputError :message="errors.status" />
            </div>
        </div>

        <div class="flex items-center gap-4 border-t pt-6">
            <Button :disabled="processing">
                {{ processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
            </Button>
            <p class="text-xs text-muted-foreground">
                Perubahan di semua tab disimpan sekaligus.
            </p>
        </div>
    </form>
</template>
