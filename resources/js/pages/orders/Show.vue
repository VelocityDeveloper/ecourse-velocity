<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CalendarClock,
    CircleAlert,
    CircleCheck,
    Clock,
    Copy,
    Hourglass,
    Landmark,
    PlayCircle,
    Printer,
    Upload,
    UserRound,
    Wallet,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
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
import {
    formatDateTime,
    formatRupiah,
    orderStatusVariant,
    paymentMethodLabel,
    publicOrderStatusLabel,
} from '@/lib/course';
import learn from '@/routes/learn';
import orderRoutes from '@/routes/orders';
import type { CheckoutPayment, OrderSummary, PaymentDetails } from '@/types';

const props = defineProps<{
    order: OrderSummary & {
        price: number;
        payment_details: PaymentDetails | null;
        payer_name: string | null;
        proof_uploaded_at: string | null;
        has_proof: boolean;
        rejection_reason: string | null;
        accepts_proof: boolean;
        seconds_left: number | null;
    };
    buyer: { name: string; email: string };
    payment: CheckoutPayment;
}>();

const copied = ref<string | null>(null);
const cancelOpen = ref(false);

async function copy(text: string, key: string): Promise<void> {
    try {
        await navigator.clipboard.writeText(text);
        copied.value = key;
        setTimeout(() => (copied.value = null), 2000);
    } catch {
        // Clipboard can be blocked; the text stays visible to copy by hand.
    }
}

function printInvoice(): void {
    window.print();
}

function cancelOrder(): void {
    router.patch(
        orderRoutes.cancel(props.order.number).url,
        {},
        { onFinish: () => (cancelOpen.value = false) },
    );
}

// Countdown to the payment deadline.
const secondsLeft = ref(props.order.seconds_left);
let timer: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    if (secondsLeft.value !== null) {
        const deadline = Date.now() + secondsLeft.value * 1000;
        timer = setInterval(() => {
            secondsLeft.value = Math.max(
                0,
                Math.round((deadline - Date.now()) / 1000),
            );
        }, 1000);
    }
});

onBeforeUnmount(() => {
    if (timer !== null) {
        clearInterval(timer);
    }
});

const countdown = computed(() => {
    if (secondsLeft.value === null) {
        return null;
    }

    const hours = Math.floor(secondsLeft.value / 3600);
    const minutes = Math.floor((secondsLeft.value % 3600) / 60);
    const seconds = secondsLeft.value % 60;

    return [hours, minutes, seconds]
        .map((part) => String(part).padStart(2, '0'))
        .join(':');
});

const isPending = computed(() => props.order.status === 'pending');
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-4xl flex-col gap-6 px-4 py-10 sm:px-6 print:max-w-none print:p-0"
    >
        <Head :title="`Invoice ${order.number}`" />

        <div
            class="flex flex-wrap items-center justify-between gap-3 print:hidden"
        >
            <Link
                :href="orderRoutes.index()"
                class="flex w-fit items-center gap-1 text-sm text-muted-foreground hover:text-foreground"
            >
                <ArrowLeft class="h-4 w-4" />
                Pesanan saya
            </Link>
            <Button
                variant="outline"
                size="sm"
                class="rounded-xl"
                @click="printInvoice"
            >
                <Printer class="mr-2 h-4 w-4" />
                Cetak / simpan PDF
            </Button>
        </div>

        <!-- Status messages -->
        <div class="print:hidden">
            <section
                v-if="order.status === 'paid'"
                class="flex flex-wrap items-center gap-4 rounded-2xl border border-primary/30 bg-gradient-to-r from-primary/10 to-primary/5 p-5"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-primary text-primary-foreground"
                >
                    <CircleCheck class="size-6" />
                </span>
                <div class="min-w-56 flex-1">
                    <p class="font-bold">Pembayaran lunas</p>
                    <p class="text-sm text-muted-foreground">
                        Dikonfirmasi {{ formatDateTime(order.paid_at) }}. Anda
                        sudah terdaftar di kursus ini.
                    </p>
                </div>
                <Link
                    v-if="order.course_id !== null"
                    :href="learn.show(order.course_id)"
                >
                    <Button class="rounded-xl font-bold">
                        <PlayCircle class="mr-2 h-4 w-4" />
                        Mulai belajar
                    </Button>
                </Link>
            </section>

            <section
                v-else-if="order.status === 'awaiting_confirmation'"
                class="flex flex-wrap items-center gap-4 rounded-2xl border border-amber-500/30 bg-amber-500/5 p-5"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-amber-500/15 text-amber-600"
                >
                    <Hourglass class="size-6" />
                </span>
                <div class="min-w-56 flex-1">
                    <p class="font-bold">Bukti pembayaran sedang dicek</p>
                    <p class="text-sm text-muted-foreground">
                        Dikirim
                        {{ formatDateTime(order.proof_uploaded_at) }} atas nama
                        {{ order.payer_name }}. Anda otomatis terdaftar setelah
                        admin mengonfirmasi.
                    </p>
                </div>
                <Link
                    v-if="order.accepts_proof"
                    :href="orderRoutes.proof.create(order.number)"
                >
                    <Button variant="outline" class="rounded-xl"
                        >Kirim ulang bukti</Button
                    >
                </Link>
            </section>

            <section
                v-else-if="
                    order.status === 'expired' || order.status === 'cancelled'
                "
                class="flex flex-wrap items-center gap-4 rounded-2xl border bg-muted/40 p-5"
            >
                <span
                    class="flex size-12 shrink-0 items-center justify-center rounded-full bg-muted text-muted-foreground"
                >
                    <CircleAlert class="size-6" />
                </span>
                <div class="min-w-56 flex-1">
                    <p class="font-bold">
                        {{
                            order.status === 'expired'
                                ? 'Batas waktu pembayaran habis'
                                : 'Pesanan dibatalkan'
                        }}
                    </p>
                    <p class="text-sm text-muted-foreground">
                        Invoice ini tidak bisa dibayar lagi.
                    </p>
                </div>
                <Link
                    v-if="order.course_id !== null"
                    :href="orderRoutes.checkout(order.course_id)"
                >
                    <Button class="rounded-xl font-bold">Pesan lagi</Button>
                </Link>
            </section>

            <div
                v-else-if="order.rejection_reason"
                class="flex gap-3 rounded-2xl border border-destructive/40 bg-destructive/5 p-5 text-sm"
                role="alert"
            >
                <CircleAlert class="size-5 shrink-0 text-destructive" />
                <p>
                    <span class="font-semibold"
                        >Bukti pembayaran sebelumnya ditolak:</span
                    >
                    {{ order.rejection_reason }} Silakan kirim bukti yang benar.
                </p>
            </div>
        </div>

        <!-- Invoice -->
        <article
            class="relative overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm print:rounded-none print:border-0 print:shadow-none"
        >
            <div class="h-1.5 bg-primary print:hidden" />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-20 -right-20 size-56 rounded-full bg-primary/10 blur-2xl print:hidden"
            />
            <div class="relative p-6 sm:p-8 print:p-0">
                <header
                    class="flex flex-wrap items-start justify-between gap-4 border-b pb-6"
                >
                    <div class="space-y-3">
                        <SiteLogo size="md" wordmark />
                        <div>
                            <p
                                class="text-3xl font-extrabold tracking-tight text-primary"
                            >
                                INVOICE
                            </p>
                            <p class="font-mono text-sm text-muted-foreground">
                                {{ order.number }}
                            </p>
                        </div>
                    </div>
                    <div class="sm:text-right">
                        <p class="text-xs text-muted-foreground">Status</p>
                        <Badge
                            :variant="orderStatusVariant(order.status)"
                            class="mt-1"
                        >
                            {{ publicOrderStatusLabel(order.status) }}
                        </Badge>
                        <p class="mt-3 text-xs text-muted-foreground">
                            Total tagihan
                        </p>
                        <p class="text-xl font-bold tabular-nums">
                            {{ formatRupiah(order.total) }}
                        </p>
                    </div>
                </header>

                <div class="grid gap-4 border-b py-6 text-sm sm:grid-cols-3">
                    <div class="flex gap-3">
                        <UserRound
                            class="mt-0.5 size-4 shrink-0 text-primary"
                        />
                        <div class="min-w-0">
                            <p class="text-muted-foreground">
                                Ditagihkan kepada
                            </p>
                            <p class="font-semibold">{{ buyer.name }}</p>
                            <p class="break-all text-muted-foreground">
                                {{ buyer.email }}
                            </p>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <CalendarClock
                            class="mt-0.5 size-4 shrink-0 text-primary"
                        />
                        <div>
                            <p class="text-muted-foreground">Tanggal invoice</p>
                            <p class="font-semibold">
                                {{ formatDateTime(order.created_at) }}
                            </p>
                            <template v-if="isPending">
                                <p class="mt-2 text-muted-foreground">
                                    Jatuh tempo
                                </p>
                                <p class="font-semibold">
                                    {{ formatDateTime(order.expires_at) }}
                                </p>
                            </template>
                            <template v-if="order.paid_at">
                                <p class="mt-2 text-muted-foreground">
                                    Dibayar
                                </p>
                                <p class="font-semibold">
                                    {{ formatDateTime(order.paid_at) }}
                                </p>
                            </template>
                        </div>
                    </div>
                    <div class="flex gap-3">
                        <Wallet class="mt-0.5 size-4 shrink-0 text-primary" />
                        <div>
                            <p class="text-muted-foreground">
                                Metode pembayaran
                            </p>
                            <p class="font-semibold">
                                {{ paymentMethodLabel(order.payment_method) }}
                            </p>
                        </div>
                    </div>
                </div>

                <table class="mt-6 w-full text-sm">
                    <thead>
                        <tr class="bg-muted/60 text-muted-foreground">
                            <th
                                class="rounded-l-lg px-3 py-2.5 text-left font-medium"
                            >
                                Item
                            </th>
                            <th class="px-3 py-2.5 text-center font-medium">
                                Jumlah
                            </th>
                            <th
                                class="rounded-r-lg px-3 py-2.5 text-right font-medium"
                            >
                                Harga
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr class="border-b">
                            <td class="px-3 py-4">
                                <p class="font-semibold">
                                    {{ order.course_title }}
                                </p>
                                <p class="text-xs text-muted-foreground">
                                    Akses kursus online
                                </p>
                            </td>
                            <td class="px-3 py-4 text-center">1</td>
                            <td class="px-3 py-4 text-right tabular-nums">
                                {{ formatRupiah(order.price) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
                <div class="mt-4 flex justify-end">
                    <div
                        class="flex w-full items-center justify-between gap-6 rounded-xl bg-primary/10 px-4 py-3 sm:w-auto"
                    >
                        <span class="font-semibold">Total</span>
                        <span
                            class="text-xl font-bold text-primary tabular-nums"
                            >{{ formatRupiah(order.total) }}</span
                        >
                    </div>
                </div>
            </div>
        </article>

        <!-- How to pay -->
        <section
            v-if="isPending"
            class="flex flex-col gap-5 rounded-2xl border bg-card p-6 shadow-sm print:border-0 print:p-0 print:shadow-none"
        >
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="flex items-center gap-2 text-lg font-bold">
                    <span
                        class="flex size-8 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Wallet class="size-4" />
                    </span>
                    Cara membayar
                </h2>
                <span
                    v-if="countdown"
                    class="flex items-center gap-2 rounded-full border border-primary/30 bg-primary/5 px-3 py-1.5 text-sm print:hidden"
                >
                    <Clock class="size-4 text-primary" />
                    Sisa waktu
                    <span class="font-mono font-bold tabular-nums">{{
                        countdown
                    }}</span>
                </span>
            </div>

            <div
                class="flex flex-wrap items-center justify-between gap-3 rounded-xl bg-gradient-to-r from-primary to-primary/80 p-4 text-primary-foreground"
            >
                <div>
                    <p class="text-sm opacity-80">Bayar tepat</p>
                    <p class="text-2xl font-bold tabular-nums">
                        {{ formatRupiah(order.total) }}
                    </p>
                </div>
                <Button
                    variant="secondary"
                    size="sm"
                    class="rounded-lg print:hidden"
                    @click="copy(String(order.total), 'total')"
                >
                    <Copy class="mr-1 h-3.5 w-3.5" />
                    {{ copied === 'total' ? 'Disalin' : 'Salin nominal' }}
                </Button>
            </div>

            <div
                v-if="order.payment_method === 'bank_transfer'"
                class="grid gap-3 sm:grid-cols-2"
            >
                <div
                    v-for="(account, index) in payment.bank_accounts"
                    :key="index"
                    class="flex items-center gap-3 rounded-xl border p-4 transition-colors hover:border-primary/40"
                >
                    <span
                        class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-surface text-surface-foreground"
                    >
                        <Landmark class="size-5" />
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-xs font-bold text-muted-foreground">
                            {{ account.bank }}
                        </p>
                        <p
                            class="font-mono text-lg font-semibold tracking-wide"
                        >
                            {{ account.account_number }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            a.n. {{ account.account_name }}
                        </p>
                    </div>
                    <Button
                        variant="ghost"
                        size="sm"
                        class="shrink-0 print:hidden"
                        @click="
                            copy(
                                account.account_number.replace(/[^0-9]/g, ''),
                                `bank-${index}`,
                            )
                        "
                    >
                        <Copy class="mr-1 h-3.5 w-3.5" />
                        {{ copied === `bank-${index}` ? 'Disalin' : 'Salin' }}
                    </Button>
                </div>
            </div>

            <div
                v-else-if="order.payment_method === 'qris' && payment.qris_url"
                class="flex flex-col items-center gap-2 rounded-xl border-2 border-dashed border-primary/30 bg-muted/30 p-5 text-center"
            >
                <img
                    :src="payment.qris_url"
                    alt="Kode QRIS"
                    class="w-full max-w-64 rounded-lg bg-white p-2 shadow-sm"
                />
                <p v-if="payment.qris_name" class="text-sm font-semibold">
                    {{ payment.qris_name }}
                </p>
                <p class="text-xs text-muted-foreground">
                    Pindai dengan aplikasi bank atau e-wallet, lalu isi nominal
                    {{ formatRupiah(order.total) }}.
                </p>
            </div>

            <p
                v-if="payment.instructions"
                class="rounded-lg border-l-4 border-primary bg-muted/50 p-3 text-sm whitespace-pre-line text-muted-foreground"
            >
                {{ payment.instructions }}
            </p>

            <div
                class="flex flex-wrap items-center gap-3 border-t pt-5 print:hidden"
            >
                <Link :href="orderRoutes.proof.create(order.number)">
                    <Button
                        size="lg"
                        class="rounded-xl font-bold shadow-lg shadow-primary/25"
                    >
                        <Upload class="mr-2 h-4 w-4" />
                        Sudah bayar? Kirim bukti pembayaran
                    </Button>
                </Link>
                <Button
                    variant="ghost"
                    class="text-destructive"
                    @click="cancelOpen = true"
                >
                    Batalkan pesanan
                </Button>
            </div>
        </section>

        <Dialog v-model:open="cancelOpen">
            <DialogContent>
                <DialogHeader>
                    <DialogTitle>Batalkan pesanan ini?</DialogTitle>
                    <DialogDescription>
                        Invoice {{ order.number }} tidak bisa dibayar lagi
                        setelah dibatalkan. Anda bisa membuat pesanan baru kapan
                        saja.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2">
                    <Button variant="secondary" @click="cancelOpen = false">
                        Tidak jadi
                    </Button>
                    <Button variant="destructive" @click="cancelOrder">
                        Batalkan pesanan
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
