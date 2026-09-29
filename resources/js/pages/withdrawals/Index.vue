<script setup lang="ts">
import FinanceNav from '@/components/FinanceNav.vue';
import { Head, router, useForm } from '@inertiajs/vue3';
import { Banknote, Clock, Info, Receipt } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ProofDialog from '@/components/ProofDialog.vue';
import InputError from '@/components/InputError.vue';
import WithdrawalStatusBadge from '@/components/WithdrawalStatusBadge.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime, formatRupiah } from '@/lib/course';
import withdrawalRoutes from '@/routes/admin/withdrawals';
import type { InstructorBalance, Paginated, Withdrawal } from '@/types';

const props = defineProps<{
    balance: InstructorBalance;
    withdrawals: Paginated<Withdrawal>;
    minimum: number;
    hasPending: boolean;
    lastAccount: {
        bank_name: string;
        account_number: string;
        account_name: string;
    } | null;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
            {
                title: 'Saldo & Penarikan',
                href: '/dasbor/keuangan/penarikan-dana',
            },
        ],
    },
});

const tiles = computed(() => [
    {
        label: 'Saldo tersedia',
        value: props.balance.available,
        note: 'Bisa ditarik sekarang',
        highlight: true,
    },
    {
        label: 'Sedang diproses',
        value: props.balance.pending,
        note: 'Menunggu transfer admin',
    },
    {
        label: 'Sudah ditarik',
        value: props.balance.paid_out,
        note: 'Sudah ditransfer ke rekening Anda',
    },
    {
        label: 'Total pendapatan bersih',
        value: props.balance.earned,
        note: 'Setelah potongan platform',
    },
]);

// Why the form is closed, if it is.
const blocked = computed<string | null>(() => {
    if (props.hasPending) {
        return 'Anda masih punya penarikan yang menunggu diproses. Tunggu sampai admin mentransfernya, atau batalkan dulu untuk mengajukan yang baru.';
    }

    if (props.balance.available < Math.max(1, props.minimum)) {
        return `Saldo tersedia belum mencapai minimal penarikan ${formatRupiah(props.minimum)}.`;
    }

    return null;
});

const form = useForm({
    amount: '' as number | string,
    bank_name: props.lastAccount?.bank_name ?? '',
    account_number: props.lastAccount?.account_number ?? '',
    account_name: props.lastAccount?.account_name ?? '',
    note: '',
});

function submit(): void {
    form.post(withdrawalRoutes.store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset('amount', 'note'),
    });
}

function cancel(withdrawal: Withdrawal): void {
    if (
        !window.confirm(
            `Batalkan penarikan ${formatRupiah(withdrawal.amount)}? Jumlahnya kembali ke saldo tersedia.`,
        )
    ) {
        return;
    }

    router.post(
        withdrawalRoutes.cancel(withdrawal.id).url,
        {},
        { preserveScroll: true },
    );
}

function goToPage(page: number): void {
    router.get(
        withdrawalRoutes.index().url,
        { page },
        { preserveState: true, preserveScroll: true },
    );
}

// The transfer receipt shown in a popup.
const proof = ref<Withdrawal | null>(null);
const proofOpen = computed({
    get: () => proof.value !== null,
    set: (open: boolean) => {
        if (!open) {
            proof.value = null;
        }
    },
});

function showProof(row: Withdrawal): void {
    proof.value = row;
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Saldo & Penarikan · Keuangan" />

        <FinanceNav />

        <Heading
            variant="small"
            title="Saldo & Penarikan"
            description="Tarik pendapatan bersih dari penjualan kursus Anda ke rekening bank."
        />

        <div class="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
            <div
                v-for="tile in tiles"
                :key="tile.label"
                class="rounded-lg border p-4"
                :class="{
                    'border-primary/40 bg-primary/5': tile.highlight,
                }"
            >
                <p
                    class="text-2xl font-semibold tracking-tight tabular-nums"
                    :class="{ 'text-primary': tile.highlight }"
                >
                    {{ formatRupiah(tile.value) }}
                </p>
                <p class="text-sm font-medium">{{ tile.label }}</p>
                <p class="text-xs text-muted-foreground">{{ tile.note }}</p>
            </div>
        </div>

        <div class="grid gap-6 lg:grid-cols-[1.4fr_1fr]">
            <section class="rounded-lg border">
                <header class="flex items-center gap-3 border-b p-4">
                    <span
                        class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                    >
                        <Banknote class="size-4.5" />
                    </span>
                    <div>
                        <h2 class="font-semibold">Ajukan penarikan</h2>
                        <p class="text-sm text-muted-foreground">
                            Minimal {{ formatRupiah(minimum) }}, maksimal
                            sebesar saldo tersedia.
                        </p>
                    </div>
                </header>

                <p
                    v-if="blocked"
                    class="m-4 flex gap-2 rounded-lg bg-muted p-3 text-sm text-muted-foreground"
                >
                    <Info class="mt-0.5 size-4 shrink-0" />
                    {{ blocked }}
                </p>

                <form
                    v-else
                    class="grid gap-4 p-4 sm:grid-cols-2"
                    @submit.prevent="submit"
                >
                    <div class="grid content-start gap-2 sm:col-span-2">
                        <Label for="amount">Jumlah penarikan</Label>
                        <div class="flex gap-2">
                            <div class="relative flex-1">
                                <span
                                    class="pointer-events-none absolute inset-y-0 left-3 flex items-center text-sm text-muted-foreground"
                                    >Rp</span
                                >
                                <Input
                                    id="amount"
                                    v-model="form.amount"
                                    type="number"
                                    :min="minimum"
                                    :max="balance.available"
                                    class="pl-10"
                                    required
                                />
                            </div>
                            <Button
                                type="button"
                                variant="outline"
                                @click="form.amount = balance.available"
                            >
                                Tarik semua
                            </Button>
                        </div>
                        <InputError :message="form.errors.amount" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="bank_name">Nama bank / e-wallet</Label>
                        <Input
                            id="bank_name"
                            v-model="form.bank_name"
                            maxlength="50"
                            placeholder="Contoh: BCA"
                            required
                        />
                        <InputError :message="form.errors.bank_name" />
                    </div>
                    <div class="grid content-start gap-2">
                        <Label for="account_number">Nomor rekening</Label>
                        <Input
                            id="account_number"
                            v-model="form.account_number"
                            maxlength="50"
                            inputmode="numeric"
                            required
                        />
                        <InputError :message="form.errors.account_number" />
                    </div>
                    <div class="grid content-start gap-2 sm:col-span-2">
                        <Label for="account_name">Nama pemilik rekening</Label>
                        <Input
                            id="account_name"
                            v-model="form.account_name"
                            maxlength="100"
                            required
                        />
                        <InputError :message="form.errors.account_name" />
                    </div>
                    <div class="grid content-start gap-2 sm:col-span-2">
                        <Label for="note">
                            Catatan untuk admin
                            <span class="font-normal text-muted-foreground"
                                >(opsional)</span
                            >
                        </Label>
                        <Textarea
                            id="note"
                            v-model="form.note"
                            rows="2"
                            maxlength="500"
                        />
                        <InputError :message="form.errors.note" />
                    </div>
                    <div class="flex justify-end sm:col-span-2">
                        <Button type="submit" :disabled="form.processing">
                            Ajukan penarikan
                        </Button>
                    </div>
                </form>
            </section>

            <aside class="space-y-3 rounded-lg border bg-muted/30 p-4 text-sm">
                <h2 class="flex items-center gap-2 font-semibold">
                    <Clock class="size-4 text-muted-foreground" />
                    Cara kerja penarikan
                </h2>
                <ol class="list-decimal space-y-2 pl-5 text-muted-foreground">
                    <li>
                        Saldo berasal dari pembayaran siswa yang sudah
                        dikonfirmasi, setelah dikurangi potongan platform.
                    </li>
                    <li>
                        Ajukan jumlah dan rekening tujuan. Jumlahnya langsung
                        ditahan dari saldo tersedia.
                    </li>
                    <li>
                        Admin mentransfer secara manual lalu menandai penarikan
                        sudah dibayar, disertai bukti transfer.
                    </li>
                    <li>
                        Bila ditolak, jumlahnya kembali ke saldo dan Anda bisa
                        mengajukan lagi.
                    </li>
                </ol>
            </aside>
        </div>

        <section class="space-y-3">
            <h2 class="font-semibold">Riwayat penarikan</h2>
            <div class="rounded-lg border">
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="border-b">
                            <tr>
                                <th
                                    v-for="heading in [
                                        'Diajukan',
                                        'Jumlah',
                                        'Rekening tujuan',
                                        'Status',
                                        'Diproses',
                                        '',
                                    ]"
                                    :key="heading"
                                    class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                                    :class="{
                                        'text-right': heading === 'Jumlah',
                                    }"
                                >
                                    {{ heading }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="withdrawals.data.length === 0">
                                <td
                                    colspan="6"
                                    class="py-8 text-center text-muted-foreground"
                                >
                                    Belum ada penarikan.
                                </td>
                            </tr>
                            <tr
                                v-for="row in withdrawals.data"
                                :key="row.id"
                                class="border-b last:border-0"
                            >
                                <td class="p-4 align-top whitespace-nowrap">
                                    {{ formatDateTime(row.created_at) }}
                                </td>
                                <td
                                    class="p-4 text-right align-top font-semibold whitespace-nowrap tabular-nums"
                                >
                                    {{ formatRupiah(row.amount) }}
                                </td>
                                <td class="p-4 align-top">
                                    <span class="block font-medium"
                                        >{{ row.bank_name }}
                                        {{ row.account_number }}</span
                                    >
                                    <span
                                        class="block text-xs text-muted-foreground"
                                        >a.n. {{ row.account_name }}</span
                                    >
                                </td>
                                <td class="max-w-72 p-4 align-top">
                                    <WithdrawalStatusBadge
                                        :status="row.status"
                                    />
                                    <p
                                        v-if="row.admin_note"
                                        class="mt-1 text-xs text-muted-foreground"
                                    >
                                        Catatan admin: {{ row.admin_note }}
                                    </p>
                                </td>
                                <td
                                    class="p-4 align-top whitespace-nowrap text-muted-foreground"
                                >
                                    {{
                                        row.processed_at
                                            ? formatDateTime(row.processed_at)
                                            : '-'
                                    }}
                                </td>
                                <td class="p-4 text-right align-top">
                                    <Button
                                        v-if="row.status === 'pending'"
                                        size="sm"
                                        variant="outline"
                                        @click="cancel(row)"
                                    >
                                        Batalkan
                                    </Button>
                                    <Button
                                        v-else-if="row.has_proof"
                                        size="sm"
                                        variant="outline"
                                        @click="showProof(row)"
                                    >
                                        <Receipt class="mr-1 size-4" />
                                        Bukti
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div
                v-if="withdrawals.last_page > 1"
                class="flex items-center justify-end gap-2"
            >
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="withdrawals.current_page <= 1"
                    @click="goToPage(withdrawals.current_page - 1)"
                >
                    Sebelumnya
                </Button>
                <span class="text-sm text-muted-foreground">
                    Halaman {{ withdrawals.current_page }} dari
                    {{ withdrawals.last_page }}
                </span>
                <Button
                    variant="outline"
                    size="sm"
                    :disabled="
                        withdrawals.current_page >= withdrawals.last_page
                    "
                    @click="goToPage(withdrawals.current_page + 1)"
                >
                    Berikutnya
                </Button>
            </div>
        </section>

        <ProofDialog
            v-model:open="proofOpen"
            :url="proof ? withdrawalRoutes.proof(proof.id).url : null"
            title="Bukti transfer"
            :description="
                proof
                    ? `Transfer ${formatRupiah(proof.amount)} ke ${proof.bank_name} ${proof.account_number}`
                    : undefined
            "
        />
    </div>
</template>
