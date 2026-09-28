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

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kategori', href: '/dasbor/kategori' },
            { title: 'Buat Kategori', href: '/dasbor/kategori/tambah' },
        ],
    },
});

const form = reactive({
    name: '',
    slug: '',
    description: '',
    image: null as File | null,
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

function submit() {
    processing.value = true;
    errors.value = {};

    router.post(categories.store().url, form, {
        forceFormData: true,
        onError: (err) => {
            errors.value = err as Record<string, string>;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Buat Kategori" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Buat Kategori"
            description="Tambah kategori kursus baru"
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
                    placeholder="Kosongkan untuk dibuat otomatis dari nama"
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
                :name="form.name"
                :error="errors.image"
            />

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Membuat...' : 'Buat Kategori' }}
                </Button>
                <Link :href="categories.index()">
                    <Button type="button" variant="ghost">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
