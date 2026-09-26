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
                <DialogTitle>{{ title ?? 'Batalkan Pendaftaran' }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="grid gap-2">
                <Label for="cancel-reason">Alasan (opsional)</Label>
                <Textarea
                    id="cancel-reason"
                    v-model="reason"
                    rows="3"
                    maxlength="1000"
                    placeholder="Mengapa pendaftaran ini dibatalkan?"
                />
                <InputError :message="error" />
            </div>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="open = false">
                    Pertahankan pendaftaran
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirmCancel"
                >
                    {{ processing ? 'Membatalkan...' : 'Batalkan pendaftaran' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
