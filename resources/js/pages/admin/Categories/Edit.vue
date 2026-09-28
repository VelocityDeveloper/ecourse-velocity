<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import CategoryImageField from '@/components/CategoryImageField.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import categories from '@/routes/admin/categories';
import type { Category } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kategori', href: '/dasbor/kategori' },
            { title: 'Ubah Kategori', href: '/dasbor/kategori' },
        ],
    },
});

const props = defineProps<{
    category: Category;
}>();

const form = reactive({
    name: props.category.name,
    slug: props.category.slug,
    description: props.category.description ?? '',
    image: null as File | null,
    remove_image: false,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

function submit() {
    processing.value = true;
    errors.value = {};

    // Files need multipart, which PUT cannot carry: POST with a spoofed method.
    router.post(
        categories.update(props.category.slug).url,
        { ...form, _method: 'put' },
        {
            forceFormData: true,
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
    <Head title="Ubah Kategori" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Ubah Kategori"
            :description="category.name"
        />

        <form @submit.prevent="submit" class="max-w-xl space-y-6">
            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Pengembangan Web"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="Kosongkan untuk dibuat ulang dari nama"
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Deskripsi</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="Apa cakupan kategori ini?"
                />
                <InputError :message="errors.description" />
            </div>

            <CategoryImageField
                v-model:file="form.image"
                v-model:remove="form.remove_image"
                :current-url="category.image_url"
                :name="form.name"
                :error="errors.image"
            />

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Menyimpan...' : 'Simpan Perubahan' }}
                </Button>
                <Link :href="categories.index()">
                    <Button type="button" variant="ghost">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
