<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { NotebookPen } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Textarea } from '@/components/ui/textarea';
import learn from '@/routes/learn';

const props = defineProps<{
    courseId: number;
    lessonId: number;
    note: string | null;
}>();

const body = ref(props.note ?? '');
const saving = ref(false);
const error = ref<string | undefined>();

watch(
    () => [props.lessonId, props.note] as const,
    ([, note]) => {
        body.value = note ?? '';
    },
);

const isDirty = computed(() => body.value.trim() !== (props.note ?? '').trim());

function save(): void {
    saving.value = true;
    error.value = undefined;

    router.put(
        learn.lessons.note.update({
            course: props.courseId,
            lesson: props.lessonId,
        }).url,
        { body: body.value },
        {
            preserveScroll: true,
            preserveState: true,
            onError: (errors) => {
                error.value = errors.body;
            },
            onFinish: () => {
                saving.value = false;
            },
        },
    );
}
</script>

<template>
    <section
        class="space-y-3 rounded-lg border bg-card p-5 text-card-foreground"
    >
        <div class="flex items-center justify-between gap-3">
            <h2 class="flex items-center gap-2 font-semibold">
                <NotebookPen class="h-4 w-4" />
                My notes
            </h2>
            <span class="text-xs text-muted-foreground">
                Only you can see these
            </span>
        </div>
        <Textarea
            v-model="body"
            rows="4"
            maxlength="10000"
            aria-label="My notes for this lesson"
            placeholder="Write down key points, questions or code snippets..."
        />
        <InputError :message="error" />
        <div class="flex items-center justify-end gap-2">
            <span v-if="isDirty" class="text-xs text-muted-foreground">
                Unsaved changes
            </span>
            <Button size="sm" :disabled="saving || !isDirty" @click="save">
                {{ saving ? 'Saving...' : 'Save note' }}
            </Button>
        </div>
    </section>
</template>
