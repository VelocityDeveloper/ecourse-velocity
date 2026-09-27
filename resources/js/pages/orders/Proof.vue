<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CircleAlert,
    FileUp,
    Landmark,
    QrCode,
    Receipt,
    Upload,
} from '@lucide/vue';
import { ref } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, formatRupiah, paymentMethodLabel } from '@/lib/course';
import orderRoutes from '@/routes/orders';
import type { BankAccount, OrderSummary } from '@/types';

const props = defineProps<{
    order: OrderSummary & {
        payer_name: string | null;
        rejection_reason: string | null;
    };
    bankAccounts: BankAccount[];
    qrisName: string | null;
    maxProofKb: number;
}>();

const form = useForm<{
    bank_index: number | null;
    payer_name: string;
    payer_note: string;
    proof: File | null;
}>({
    bank_index: props.bankAccounts.length > 0 ? 0 : null,
    payer_name: props.order.payer_name ?? '',
    payer_note: '',
    proof: null,
});

const fileError = ref<string | null>(null);
const preview = ref<string | null>(null);

function pickFile(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0] ?? null;
    fileError.value = null;
    preview.value = null;

    if (file && file.size > props.maxProofKb * 1024) {
        fileError.value = `Ukuran file maksimal ${Math.round(props.maxProofKb / 1024)} MB.`;
        form.proof = null;

        return;
    }

    form.proof = file;

    if (file && file.type.startsWith('image/')) {
        preview.value = URL.createObjectURL(file);
    }
}

function submit(): void {
    form.post(orderRoutes.proof.store(props.order.number).url, {
        forceFormData: true,
        preserveScroll: true,
    });
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-2xl flex-col gap-6 px-4 py-10 sm:px-6"
    >
        <Head :title="`Konfirmasi Pembayaran ${order.number}`" />

        <Link
            :href="orderRoutes.show(order.number)"
            class="flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="h-4 w-4" />
            Kembali ke invoice
        </Link>

        <div class="flex items-start gap-4">
            <span
                class="flex size-12 shrink-0 items-center justify-center rounded-2xl bg-primary text-primary-foreground shadow-lg shadow-primary/25"
            >
                <FileUp class="size-6" />
            </span>
            <div class="space-y-1">
                <p class="text-sm font-semibold text-primary">
                    Konfirmasi pembayaran
                </p>
                <h1 class="text-3xl font-bold tracking-tight">
                    Kirim bukti pembayaran
                </h1>
            </div>
        </div>

        <div
            class="flex items-center gap-4 overflow-hidden rounded-2xl border bg-gradient-to-r from-primary/10 via-primary/5 to-card p-4"
        >
            <span
                class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-card text-primary shadow-sm"
            >
                <Receipt class="size-5" />
            </span>
            <div class="min-w-0 flex-1 text-sm">
                <p class="truncate font-semibold">{{ order.course_title }}</p>
                <p class="text-xs text-muted-foreground">
                    <span class="font-mono">{{ order.number }}</span> ·
                    {{ paymentMethodLabel(order.payment_method) }} · jatuh tempo
                    {{ formatDateTime(order.expires_at) }}
                </p>
            </div>
            <p class="shrink-0 text-lg font-bold text-primary tabular-nums">
                {{ formatRupiah(order.total) }}
            </p>
        </div>

        <div
            v-if="order.rejection_reason"
            class="flex gap-3 rounded-xl border border-destructive/40 bg-destructive/5 p-4 text-sm"
            role="alert"
        >
            <CircleAlert class="size-5 shrink-0 text-destructive" />
            <p>
                <span class="font-semibold">Bukti sebelumnya ditolak:</span>
                {{ order.rejection_reason }}
            </p>
        </div>

        <form
            class="flex flex-col gap-6 rounded-2xl border bg-card p-6 shadow-sm"
            @submit.prevent="submit"
        >
            <div
                v-if="order.payment_method === 'bank_transfer'"
                class="grid content-start gap-2"
            >
                <Label class="flex items-center gap-2">
                    <Landmark class="size-4 text-primary" />
                    Rekening tujuan yang Anda transfer
                </Label>
                <div class="grid gap-2" role="radiogroup">
                    <label
                        v-for="(account, index) in bankAccounts"
                        :key="index"
                        class="flex cursor-pointer items-center gap-3 rounded-xl border-2 p-3 text-sm transition-all"
                        :class="
                            form.bank_index === index
                                ? 'border-primary bg-primary/5'
                                : 'border-border hover:border-primary/40'
                        "
                    >
                        <input
                            v-model="form.bank_index"
                            type="radio"
                            name="bank_index"
                            :value="index"
                            class="h-4 w-4 shrink-0 accent-primary"
                        />
                        <span
                            class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-surface text-surface-foreground"
                        >
                            <Landmark class="size-4" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-mono font-semibold">{{
                                account.account_number
                            }}</span>
                            <span
                                class="block truncate text-xs text-muted-foreground"
                                >{{ account.bank }} · a.n.
                                {{ account.account_name }}</span
                            >
                        </span>
                    </label>
                </div>
                <InputError :message="form.errors.bank_index" />
            </div>
            <p
                v-else
                class="flex items-center gap-2 rounded-xl bg-muted/50 p-3 text-sm"
            >
                <QrCode class="size-4 text-primary" />
                Dibayar lewat QRIS
                <span v-if="qrisName" class="font-semibold">{{
                    qrisName
                }}</span>
            </p>

            <div class="grid content-start gap-2">
                <Label for="payer-name">Nama pengirim / pemilik rekening</Label>
                <Input
                    id="payer-name"
                    v-model="form.payer_name"
                    required
                    maxlength="100"
                />
                <InputError :message="form.errors.payer_name" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="proof">Bukti pembayaran</Label>
                <label
                    for="proof"
                    class="flex cursor-pointer flex-col items-center gap-2 rounded-xl border-2 border-dashed p-6 text-center transition-colors hover:border-primary/50 hover:bg-primary/5"
                    :class="form.proof ? 'border-primary/50 bg-primary/5' : ''"
                >
                    <img
                        v-if="preview"
                        :src="preview"
                        alt="Pratinjau bukti pembayaran"
                        class="max-h-60 rounded-lg border object-contain"
                    />
                    <FileUp v-else class="size-8 text-primary" />
                    <span class="text-sm font-semibold">{{
                        form.proof
                            ? form.proof.name
                            : 'Pilih file bukti transfer'
                    }}</span>
                    <span class="text-xs text-muted-foreground">
                        JPG, PNG, WebP, atau PDF · maksimal
                        {{ Math.round(maxProofKb / 1024) }} MB
                    </span>
                </label>
                <input
                    id="proof"
                    type="file"
                    accept="image/jpeg,image/png,image/webp,application/pdf"
                    class="sr-only"
                    @change="pickFile"
                />
                <InputError :message="fileError ?? form.errors.proof" />
            </div>

            <div class="grid content-start gap-2">
                <Label for="payer-note">Catatan (opsional)</Label>
                <Textarea
                    id="payer-note"
                    v-model="form.payer_note"
                    rows="2"
                    maxlength="500"
                />
                <InputError :message="form.errors.payer_note" />
            </div>

            <Button
                type="submit"
                size="lg"
                class="h-12 rounded-xl font-bold shadow-lg shadow-primary/25"
                :disabled="form.processing || !form.proof"
            >
                <Upload class="mr-2 h-4 w-4" />
                {{ form.processing ? 'Mengirim...' : 'Kirim bukti pembayaran' }}
            </Button>
        </form>
    </div>
</template>
