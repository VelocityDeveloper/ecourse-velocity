<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CircleCheck, ExternalLink, FileText } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
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
import {
    formatDateTime,
    formatRupiah,
    orderStatusLabel,
    orderStatusVariant,
    paymentMethodLabel,
} from '@/lib/course';
import adminOrders from '@/routes/admin/orders';
import courses from '@/routes/courses';
import type { OrderPerson, OrderSummary, PaymentDetails } from '@/types';

const props = defineProps<{
    order: OrderSummary & {
        price: number;
        payment_details: PaymentDetails | null;
        payer_name: string | null;
        payer_note: string | null;
        proof_uploaded_at: string | null;
        proof_url: string | null;
        proof_is_pdf: boolean;
        rejection_reason: string | null;
        cancelled_at: string | null;
        handler: { id: number; name: string } | null;
        student: OrderPerson;
        transaction: {
            id: number;
            amount: number;
            note: string | null;
            paid_at: string;
            confirmer: { id: number; name: string } | null;
        } | null;
    };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pesanan', href: '/admin/orders' },
            { title: 'Detail Pesanan', href: '#' },
        ],
    },
});

const dialog = ref<'confirm' | 'reject' | 'cancel' | null>(null);
const confirmForm = useForm({ note: '' });
const rejectForm = useForm({ reason: '' });
const cancelForm = useForm({});

const isOpen = () =>
    props.order.status === 'pending' ||
    props.order.status === 'awaiting_confirmation';

function confirmPayment(): void {
    confirmForm.post(adminOrders.confirm(props.order.number).url, {
        preserveScroll: true,
        onSuccess: () => (dialog.value = null),
    });
}

function rejectPayment(): void {
    rejectForm.post(adminOrders.reject(props.order.number).url, {
        preserveScroll: true,
        onSuccess: () => (dialog.value = null),
    });
}

function cancelOrder(): void {
    cancelForm.post(adminOrders.cancel(props.order.number).url, {
        preserveScroll: true,
        onSuccess: () => (dialog.value = null),
    });
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head :title="`Pesanan ${order.number}`" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                :title="`Pesanan ${order.number}`"
                :description="`Dibuat ${formatDateTime(order.created_at)}`"
            />
            <Link :href="adminOrders.index()">
                <Button variant="outline" size="sm">
                    <ArrowLeft class="mr-2 h-4 w-4" />
                    Semua pesanan
                </Button>
            </Link>
        </div>

        <div class="grid items-start gap-6 lg:grid-cols-[1fr_340px]">
            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <div class="flex items-center justify-between gap-2">
                    <h3 class="font-medium">Bukti pembayaran</h3>
                    <a
                        v-if="order.proof_url"
                        :href="order.proof_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-flex items-center gap-1.5 text-sm font-medium text-primary underline-offset-4 hover:underline"
                    >
                        <ExternalLink class="size-3.5" />
                        Buka ukuran penuh
                    </a>
                </div>

                <template v-if="order.proof_url">
                    <a
                        v-if="order.proof_is_pdf"
                        :href="order.proof_url"
                        target="_blank"
                        rel="noopener"
                        class="flex items-center gap-3 rounded-lg border p-4 text-sm hover:bg-muted/50"
                    >
                        <FileText class="size-8 text-primary" />
                        Bukti berupa PDF. Klik untuk membuka.
                    </a>
                    <img
                        v-else
                        :src="order.proof_url"
                        alt="Bukti pembayaran"
                        class="max-h-[70vh] w-full rounded-lg border bg-muted object-contain"
                    />
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Metode</dt>
                            <dd class="font-medium">
                                {{ paymentMethodLabel(order.payment_method) }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Tujuan</dt>
                            <dd class="font-medium">
                                <template v-if="order.payment_details?.bank">
                                    {{ order.payment_details.bank }}
                                    {{ order.payment_details.account_number }}
                                    a.n.
                                    {{ order.payment_details.account_name }}
                                </template>
                                <template v-else>
                                    {{
                                        order.payment_details?.qris_name ?? '-'
                                    }}
                                </template>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Nama pengirim</dt>
                            <dd class="font-medium">{{ order.payer_name }}</dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Dikirim</dt>
                            <dd class="font-medium">
                                {{ formatDateTime(order.proof_uploaded_at) }}
                            </dd>
                        </div>
                        <div v-if="order.payer_note" class="sm:col-span-2">
                            <dt class="text-muted-foreground">Catatan siswa</dt>
                            <dd class="whitespace-pre-line">
                                {{ order.payer_note }}
                            </dd>
                        </div>
                    </dl>
                </template>
                <p v-else class="text-sm text-muted-foreground">
                    Siswa belum mengunggah bukti pembayaran.
                    <template v-if="order.rejection_reason">
                        Bukti sebelumnya ditolak: "{{
                            order.rejection_reason
                        }}".
                    </template>
                </p>
            </section>

            <aside class="flex flex-col gap-4">
                <section class="space-y-3 rounded-lg border p-4 text-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-muted-foreground">Status</span>
                        <Badge :variant="orderStatusVariant(order.status)">
                            {{ orderStatusLabel(order.status) }}
                        </Badge>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Siswa</span>
                        <span class="text-right font-medium"
                            >{{ order.student.name
                            }}<span
                                class="block text-xs font-normal text-muted-foreground"
                                >{{ order.student.email }}</span
                            ></span
                        >
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="text-muted-foreground">Kursus</span>
                        <Link
                            v-if="order.course_id !== null"
                            :href="courses.show(order.course_id)"
                            class="text-right font-medium text-primary underline-offset-4 hover:underline"
                            >{{ order.course_title }}</Link
                        >
                        <span v-else class="text-right">{{
                            order.course_title
                        }}</span>
                    </div>
                    <div class="flex justify-between gap-4 border-t pt-3">
                        <span class="text-muted-foreground">Harga</span>
                        <span class="tabular-nums">{{
                            formatRupiah(order.price)
                        }}</span>
                    </div>
                    <div class="flex justify-between gap-4">
                        <span class="font-medium">Total</span>
                        <span
                            class="text-lg font-bold text-primary tabular-nums"
                            >{{ formatRupiah(order.total) }}</span
                        >
                    </div>
                    <div
                        v-if="order.status === 'pending'"
                        class="flex justify-between gap-4"
                    >
                        <span class="text-muted-foreground">Batas bayar</span>
                        <span>{{ formatDateTime(order.expires_at) }}</span>
                    </div>
                </section>

                <section
                    v-if="order.transaction"
                    class="space-y-2 rounded-lg border border-primary/30 bg-primary/5 p-4 text-sm"
                >
                    <p class="flex items-center gap-2 font-medium">
                        <CircleCheck class="size-4 text-primary" />
                        Lunas {{ formatDateTime(order.transaction.paid_at) }}
                    </p>
                    <p class="text-muted-foreground">
                        Dikonfirmasi oleh
                        {{ order.transaction.confirmer?.name ?? '-' }}.
                        <template v-if="order.transaction.note">
                            Catatan: {{ order.transaction.note }}
                        </template>
                    </p>
                </section>

                <p
                    v-if="order.status === 'cancelled'"
                    class="rounded-lg border p-4 text-sm text-muted-foreground"
                >
                    Dibatalkan {{ formatDateTime(order.cancelled_at) }}
                    <template v-if="order.handler">
                        oleh {{ order.handler.name }}</template
                    >.
                </p>

                <div v-if="isOpen()" class="flex flex-col gap-2">
                    <Button @click="dialog = 'confirm'">
                        <CircleCheck class="mr-2 h-4 w-4" />
                        Konfirmasi pembayaran
                    </Button>
                    <Button
                        v-if="order.status === 'awaiting_confirmation'"
                        variant="outline"
                        @click="dialog = 'reject'"
                    >
                        Tolak bukti
                    </Button>
                    <Button
                        variant="ghost"
                        class="text-destructive"
                        @click="dialog = 'cancel'"
                    >
                        Batalkan pesanan
                    </Button>
                </div>
            </aside>
        </div>

        <Dialog
            :open="dialog === 'confirm'"
            @update:open="(open) => (dialog = open ? 'confirm' : null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Konfirmasi pembayaran?</DialogTitle>
                    <DialogDescription>
                        Pastikan dana {{ formatRupiah(order.total) }} sudah
                        masuk. {{ order.student.name }} langsung terdaftar di
                        "{{ order.course_title }}".
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="confirm-note">Catatan (opsional)</Label>
                    <Textarea
                        id="confirm-note"
                        v-model="confirmForm.note"
                        rows="2"
                        placeholder="Misal: masuk ke BCA 10.15 WIB"
                    />
                    <InputError :message="confirmForm.errors.note" />
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="dialog = null"
                        >Batal</Button
                    >
                    <Button
                        :disabled="confirmForm.processing"
                        @click="confirmPayment"
                        >Ya, konfirmasi</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="dialog === 'reject'"
            @update:open="(open) => (dialog = open ? 'reject' : null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Tolak bukti pembayaran?</DialogTitle>
                    <DialogDescription>
                        Siswa melihat alasan ini dan bisa mengunggah bukti baru.
                        Batas bayar diperpanjang.
                    </DialogDescription>
                </DialogHeader>
                <div class="grid gap-2">
                    <Label for="reject-reason">Alasan</Label>
                    <Textarea
                        id="reject-reason"
                        v-model="rejectForm.reason"
                        rows="3"
                        placeholder="Misal: nominal tidak sesuai, dana belum masuk"
                    />
                    <InputError :message="rejectForm.errors.reason" />
                </div>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="dialog = null"
                        >Batal</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="rejectForm.processing"
                        @click="rejectPayment"
                        >Tolak bukti</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <Dialog
            :open="dialog === 'cancel'"
            @update:open="(open) => (dialog = open ? 'cancel' : null)"
        >
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Batalkan pesanan?</DialogTitle>
                    <DialogDescription>
                        Pesanan {{ order.number }} tidak bisa dibayar lagi.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="dialog = null"
                        >Tidak jadi</Button
                    >
                    <Button
                        variant="destructive"
                        :disabled="cancelForm.processing"
                        @click="cancelOrder"
                        >Batalkan pesanan</Button
                    >
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
