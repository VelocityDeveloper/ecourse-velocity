<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Paperclip, Trash2, Upload } from '@lucide/vue';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import RichTextEditor from '@/components/RichTextEditor.vue';
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
import { contentTypeLabel, formatBytes } from '@/lib/course';
import courseRoutes from '@/routes/courses';
import lessonAttachments from '@/routes/lesson-attachments';
import lessonRoutes from '@/routes/lessons';
import type { LessonContentType } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Lessons', href: '/lessons' },
            { title: 'Edit Lesson', href: '/lessons' },
        ],
    },
});

type Attachment = {
    id: number;
    name: string;
    size: number;
    mime_type: string | null;
    url: string;
};

const props = defineProps<{
    lesson: {
        id: number;
        title: string;
        content_type: LessonContentType;
        content: string | null;
        content_url: string | null;
        duration_minutes: number | null;
        attachments: Attachment[];
    };
    section: { id: number; title: string };
    course: { id: number; title: string };
    contentTypes: LessonContentType[];
    maxAttachmentKilobytes: number;
}>();

const form = reactive({
    title: props.lesson.title,
    content_type: props.lesson.content_type,
    content: props.lesson.content ?? '',
    content_url: props.lesson.content_url ?? '',
    duration_minutes: props.lesson.duration_minutes?.toString() ?? '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);
const uploading = ref(false);
const fileInput = ref<HTMLInputElement | null>(null);

function submit() {
    processing.value = true;
    errors.value = {};

    router.put(
        lessonRoutes.update(props.lesson.id).url,
        { ...form },
        {
            preserveScroll: true,
            onError: (err) => {
                errors.value = err as Record<string, string>;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}

function uploadFiles(event: Event) {
    const input = event.target as HTMLInputElement;
    const files = Array.from(input.files ?? []);

    if (files.length === 0) {
        return;
    }

    uploading.value = true;
    errors.value = {};

    router.post(
        lessonAttachments.store(props.lesson.id).url,
        { files },
        {
            forceFormData: true,
            preserveScroll: true,
            onError: (err) => {
                errors.value = err as Record<string, string>;
            },
            onFinish: () => {
                uploading.value = false;
                input.value = '';
            },
        },
    );
}

function removeAttachment(id: number) {
    router.delete(lessonAttachments.destroy(id).url, { preserveScroll: true });
}
</script>

<template>
    <Head :title="`Edit ${lesson.title}`" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Edit Lesson"
                :description="`${course.title} › ${section.title}`"
            />
            <Link :href="courseRoutes.show(course.id)">
                <Button variant="outline" size="sm">Back to course</Button>
            </Link>
        </div>

        <form @submit.prevent="submit" class="space-y-6">
            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="title">Title</Label>
                    <Input id="title" v-model="form.title" required />
                    <InputError :message="errors.title" />
                </div>

                <div class="grid gap-2">
                    <Label for="content-type">Content type</Label>
                    <Select v-model="form.content_type">
                        <SelectTrigger id="content-type">
                            <SelectValue placeholder="Select type" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="type in contentTypes"
                                :key="type"
                                :value="type"
                            >
                                {{ contentTypeLabel(type) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                    <InputError :message="errors.content_type" />
                </div>
            </div>

            <div class="grid gap-2">
                <Label>Material</Label>
                <RichTextEditor v-model="form.content" />
                <p class="text-xs text-muted-foreground">
                    Written material for this lesson. Formatting is cleaned on
                    save.
                </p>
                <InputError :message="errors.content" />
            </div>

            <div class="grid gap-4 md:grid-cols-3">
                <div class="grid gap-2 md:col-span-2">
                    <Label for="content-url">Learning URL</Label>
                    <Input
                        id="content-url"
                        v-model="form.content_url"
                        type="url"
                        placeholder="https://example.com/video"
                    />
                    <InputError :message="errors.content_url" />
                </div>

                <div class="grid gap-2">
                    <Label for="duration">Duration (minutes)</Label>
                    <Input
                        id="duration"
                        v-model="form.duration_minutes"
                        type="number"
                        min="0"
                        placeholder="12"
                    />
                    <InputError :message="errors.duration_minutes" />
                </div>
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Saving...' : 'Save Lesson' }}
                </Button>
                <Link :href="lessonRoutes.index()">
                    <Button type="button" variant="ghost"
                        >Back to lessons</Button
                    >
                </Link>
            </div>
        </form>

        <div class="rounded-lg border">
            <div class="flex items-center justify-between gap-4 border-b p-4">
                <div>
                    <h3 class="flex items-center gap-2 text-sm font-medium">
                        <Paperclip class="h-4 w-4" />
                        Attachments
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        Documents, archives or images, up to
                        {{ Math.round(maxAttachmentKilobytes / 1024) }} MB each.
                    </p>
                </div>
                <div>
                    <input
                        ref="fileInput"
                        type="file"
                        multiple
                        class="hidden"
                        accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.zip,.txt,.png,.jpg,.jpeg,.webp"
                        @change="uploadFiles"
                    />
                    <Button
                        type="button"
                        size="sm"
                        :disabled="uploading"
                        @click="fileInput?.click()"
                    >
                        <Upload class="mr-2 h-4 w-4" />
                        {{ uploading ? 'Uploading...' : 'Add Files' }}
                    </Button>
                </div>
            </div>

            <InputError
                :message="errors['files.0'] ?? errors.files"
                class="px-4 pt-3"
            />

            <p
                v-if="lesson.attachments.length === 0"
                class="p-8 text-center text-sm text-muted-foreground"
            >
                No files attached yet.
            </p>

            <ul v-else>
                <li
                    v-for="attachment in lesson.attachments"
                    :key="attachment.id"
                    class="flex items-center justify-between gap-4 border-b px-4 py-3 last:border-b-0"
                >
                    <div class="min-w-0">
                        <p class="truncate text-sm font-medium">
                            {{ attachment.name }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            {{ formatBytes(attachment.size) }}
                            <template v-if="attachment.mime_type">
                                · {{ attachment.mime_type }}
                            </template>
                        </p>
                    </div>
                    <div class="flex shrink-0 items-center gap-1">
                        <a
                            :href="attachment.url"
                            target="_blank"
                            rel="noopener"
                        >
                            <Button type="button" variant="ghost" size="sm">
                                <Download class="h-4 w-4" />
                            </Button>
                        </a>
                        <Button
                            type="button"
                            variant="ghost"
                            size="sm"
                            @click="removeAttachment(attachment.id)"
                        >
                            <Trash2 class="h-4 w-4 text-destructive" />
                        </Button>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</template>
