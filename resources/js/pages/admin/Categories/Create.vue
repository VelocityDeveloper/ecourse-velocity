<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import categories from '@/routes/admin/categories';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Categories', href: '/admin/categories' },
            { title: 'Create Category', href: '/admin/categories/create' },
        ],
    },
});

const form = reactive({
    name: '',
    slug: '',
    description: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

function submit() {
    processing.value = true;
    errors.value = {};

    router.post(categories.store().url, form, {
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
    <Head title="Create Category" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Create Category"
            description="Add a new course category"
        />

        <form @submit.prevent="submit" class="max-w-xl space-y-6">
            <div class="grid gap-2">
                <Label for="name">Name</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    placeholder="Web Development"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="Leave blank to generate from the name"
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="4"
                    placeholder="What does this category cover?"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Creating...' : 'Create Category' }}
                </Button>
                <Link :href="categories.index()">
                    <Button type="button" variant="ghost">Cancel</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
