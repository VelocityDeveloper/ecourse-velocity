<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import enrollmentRoutes from '@/routes/enrollments';

const props = defineProps<{
    enrollmentId: number | null;
    studentName: string;
    courseTitle: string;
}>();

const open = defineModel<boolean>('open', { required: true });

const processing = ref(false);

function confirmDelete(): void {
    if (props.enrollmentId === null) {
        return;
    }

    processing.value = true;

    router.delete(enrollmentRoutes.destroy(props.enrollmentId).url, {
        preserveScroll: true,
        onSuccess: () => {
            open.value = false;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent>
            <DialogHeader>
                <DialogTitle>Hapus Pendaftaran</DialogTitle>
                <DialogDescription>
                    Hapus {{ studentName }} dari {{ courseTitle }}? Berbeda
                    dengan membatalkan, tindakan ini tidak bisa diurungkan.
                </DialogDescription>
            </DialogHeader>

            <ul class="list-disc space-y-1 pl-5 text-sm text-muted-foreground">
                <li>
                    Progres materi, percobaan kuis, catatan, dan markah siswa di
                    kursus ini ikut dihapus.
                </li>
                <li>
                    Pembayaran sebelumnya tidak lagi memberi akses: untuk kursus
                    berbayar, siswa harus membeli dan membayar lagi.
                </li>
                <li>
                    Pesanan, transaksi, dan sertifikat yang sudah terbit tetap
                    tersimpan.
                </li>
            </ul>

            <DialogFooter class="gap-2">
                <Button variant="secondary" @click="open = false">
                    Batal
                </Button>
                <Button
                    variant="destructive"
                    :disabled="processing"
                    @click="confirmDelete"
                >
                    {{ processing ? 'Menghapus...' : 'Hapus pendaftaran' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
