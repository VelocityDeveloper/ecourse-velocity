<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
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
import courses from '@/routes/courses';
import { levelLabel, statusLabel } from '@/lib/course';
import type {
    CourseFormValues,
    CourseLevel,
    CourseOption,
    CourseStatus,
} from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Courses', href: '/courses' },
            { title: 'Edit Course', href: '/courses' },
        ],
    },
});

const NONE = 'none';

const props = defineProps<{
    course: CourseFormValues;
    categories: CourseOption[];
    instructors: CourseOption[];
    levels: CourseLevel[];
    statuses: CourseStatus[];
    isAdmin: boolean;
}>();

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

function onThumbnailChange(event: Event) {
    const input = event.target as HTMLInputElement;
    form.thumbnail = input.files?.[0] ?? null;
}

function submit() {
    processing.value = true;
    errors.value = {};

    router.post(
        courses.update(props.course.id).url,
        {
            _method: 'put',
            ...form,
            category_id: form.category_id === NONE ? null : form.category_id,
        },
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
    <Head title="Edit Course" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Edit Course"
            :description="course.title"
        />

        <form @submit.prevent="submit" class="max-w-xl space-y-6">
            <div class="grid gap-2">
                <Label for="title">Title</Label>
                <Input
                    id="title"
                    v-model="form.title"
                    required
                    placeholder="Course title"
                />
                <InputError :message="errors.title" />
            </div>

            <div class="grid gap-2">
                <Label for="slug">Slug</Label>
                <Input
                    id="slug"
                    v-model="form.slug"
                    placeholder="Leave blank to regenerate from the title"
                />
                <InputError :message="errors.slug" />
            </div>

            <div class="grid gap-2">
                <Label for="description">Description</Label>
                <Textarea
                    id="description"
                    v-model="form.description"
                    rows="5"
                    placeholder="What will students learn?"
                />
                <InputError :message="errors.description" />
            </div>

            <div class="grid gap-2">
                <Label for="category">Category</Label>
                <Select v-model="form.category_id">
                    <SelectTrigger id="category">
                        <SelectValue placeholder="Select category" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem :value="NONE">No category</SelectItem>
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
                <Label for="instructor">Instructor</Label>
                <Select v-model="form.instructor_id">
                    <SelectTrigger id="instructor">
                        <SelectValue placeholder="Select instructor" />
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

            <div class="grid gap-2">
                <Label for="price">Price (IDR)</Label>
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
                <Label for="level">Level</Label>
                <Select v-model="form.level">
                    <SelectTrigger id="level">
                        <SelectValue placeholder="Select level" />
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

            <div class="grid gap-2">
                <Label for="status">Status</Label>
                <Select v-model="form.status">
                    <SelectTrigger id="status">
                        <SelectValue placeholder="Select status" />
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
                    Only an administrator can publish or archive a course.
                </p>
                <InputError :message="errors.status" />
            </div>

            <div class="grid gap-2">
                <Label for="thumbnail">Thumbnail</Label>
                <img
                    v-if="course.thumbnail_url"
                    :src="course.thumbnail_url"
                    :alt="course.title"
                    class="h-24 w-40 rounded border object-cover"
                />
                <Input
                    id="thumbnail"
                    type="file"
                    accept="image/*"
                    @change="onThumbnailChange"
                />
                <p class="text-xs text-muted-foreground">
                    Leave empty to keep the current image. JPG, PNG or WebP, up
                    to 2 MB.
                </p>
                <InputError :message="errors.thumbnail" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Saving...' : 'Save Changes' }}
                </Button>
                <Link :href="courses.show(course.id)">
                    <Button type="button" variant="ghost">Cancel</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
