<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import enrollmentRoutes from '@/routes/enrollments';

const props = defineProps<{
    enrollmentId: number | null;
    title?: string;
    description: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const reason = ref('');
const error = ref<string | undefined>();
const processing = ref(false);

watch(open, (isOpen) => {
    if (isOpen) {
        reason.value = '';
        error.value = undefined;
    }
});

function confirmCancel(): void {
    if (props.enrollmentId === null) {
        return;
    }

    processing.value = true;

    router.patch(
        enrollmentRoutes.cancel(props.enrollmentId).url,
        { reason: reason.value },
        {
            preserveScroll: true,
            onSuccess: () => {
                open.value = false;
            },
            onError: (errors) => {
                error.value = errors.reason;
            },
            onFinish: () => {
                processing.value = false;
            },
        },
    );
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>{{ title ?? 'Cancel Enrollment' }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <Label for="cancel-reason">Reason (optional)</Label>
                <Textarea
                    id="cancel-reason"
                    v-model="reason"
                    rows="3"
                    maxlength="1000"
                    placeholder="Why is this enrollment being cancelled?"
                />
                <InputError :message="error" />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="open = false">
                    Keep enrollment
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirmCancel"
                >
                    {{ processing ? 'Cancelling...' : 'Cancel enrollment' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
