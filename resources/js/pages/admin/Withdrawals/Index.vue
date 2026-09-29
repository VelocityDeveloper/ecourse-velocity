<script setup lang="ts">
import FinanceNav from '@/components/FinanceNav.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { Check, Copy, Inbox, Receipt, Search, X } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import ProofDialog from '@/components/ProofDialog.vue';
import InputError from '@/components/InputError.vue';
import WithdrawalStatusBadge from '@/components/WithdrawalStatusBadge.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { formatDateTime, formatRupiah } from '@/lib/course';
import instructorRoutes from '@/routes/admin/finance/instructors';
import withdrawalRoutes from '@/routes/admin/withdrawals';
import type { Paginated, WithdrawalRow, WithdrawalStatus } from '@/types';

type StatusFilter = WithdrawalStatus | 'all';

const props = defineProps<{
    withdrawals: Paginated<WithdrawalRow>;
    counts: Record<WithdrawalStatus, number>;
    summary: {
        pending_amount: number;
        paid_this_month: number;
        paid_all_time: number;
    };
    filters: { status: StatusFilter; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
            {
                title: 'Penarikan Dana',
                href: '/dasbor/keuangan/penarikan-dana',
            },
        ],
    },
});

const tiles = computed(() => [
    {
        label: 'Menunggu transfer',
        value: props.summary.pending_amount,
        note: `${props.counts.pending} permintaan`,
    },
    {
        label: 'Ditransfer bulan ini',
        value: props.summary.paid_this_month,
    },
    {
        label: 'Ditransfer sepanjang waktu',
        value: props.summary.paid_all_time,
    },
]);

const tabs = computed(() => [
    { value: 'pending', label: 'Menunggu', count: props.counts.pending },
    { value: 'paid', label: 'Ditransfer', count: props.counts.paid },
    { value: 'rejected', label: 'Ditolak', count: props.counts.rejected },
    { value: 'cancelled', label: 'Dibatalkan', count: props.counts.cancelled },
    { value: 'all', label: 'Semua' },
]);

const search = ref(props.filters.search ?? '');

function visit(query: Record<string, unknown>): void {
    router.get(
        withdrawalRoutes.index().url,
        {
            status: props.filters.status,
            search: search.value || undefined,
            ...query,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const copied = ref<number | null>(null);

function copyAccount(row: WithdrawalRow): void {
    void navigator.clipboard
        ?.writeText(row.account_number.replace(/\D/g, ''))
        .then(() => {
            copied.value = row.id;
            setTimeout(() => (copied.value = null), 1500);
        });
}

// The "mark paid" / "reject" dialog.
const decision = ref<{
    row: WithdrawalRow;
    action: 'pay' | 'reject';
} | null>(null);
const decisionForm = useForm<{ note: string; proof: File | null }>({
    note: '',
    proof: null,
});

function decide(row: WithdrawalRow, action: 'pay' | 'reject'): void {
    decisionForm.reset();
    decisionForm.clearErrors();
    decision.value = { row, action };
}

function pickProof(event: Event): void {
    decisionForm.proof = (event.target as HTMLInputElement).files?.[0] ?? null;
}

function submitDecision(): void {
    if (!decision.value) {
        return;
    }

    const { row, action } = decision.value;
    const url =
        action === 'pay'
            ? withdrawalRoutes.pay(row.id).url
            : withdrawalRoutes.reject(row.id).url;

    decisionForm.post(url, {
        preserveScroll: true,
        forceFormData: action === 'pay',
        onSuccess: () => {
            decision.value = null;
        },
    });
}

// The transfer receipt shown in a popup.
const proof = ref<WithdrawalRow | null>(null);
const proofOpen = computed({
    get: () => proof.value !== null,
    set: (open: boolean) => {
        if (!open) {
            proof.value = null;
        }
    },
});

function showProof(row: WithdrawalRow): void {
    proof.value = row;
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Penarikan Dana · Keuangan" />

        <FinanceNav />

        <Heading
            variant="small"
            title="Penarikan Dana"
            description="Permintaan penarikan dari instruktur. Transfer secara manual, lalu tandai sudah ditransfer."
        />

        <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
            <div
                v-for="tile in tiles"
                :key="tile.label"
                class="rounded-lg border p-4"
            >
                <p class="text-2xl font-semibold tracking-tight tabular-nums">
                    {{ formatRupiah(tile.value) }}
                </p>
                <p class="text-sm font-medium">{{ tile.label }}</p>
                <p v-if="tile.note" class="text-xs text-muted-foreground">
                    {{ tile.note }}
                </p>
            </div>
        </div>

        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap gap-1 rounded-lg border p-1">
                <button
                    v-for="tab in tabs"
                    :key="tab.value"
                    type="button"
                    class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                    :class="
                        filters.status === tab.value
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted'
                    "
                    @click="visit({ status: tab.value, page: undefined })"
                >
                    {{ tab.label }}
                    <span
                        v-if="tab.count !== undefined"
                        class="rounded-full px-1.5 text-xs tabular-nums"
                        :class="
                            filters.status === tab.value
                                ? 'bg-primary-foreground/20'
                                : 'bg-muted'
                        "
                        >{{ tab.count }}</span
                    >
                </button>
            </div>

            <form
                class="relative w-full sm:w-72"
                @submit.prevent="visit({ page: undefined })"
            >
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Cari instruktur..."
                    class="pl-9"
                />
            </form>
        </div>

        <div
            v-if="withdrawals.data.length === 0"
            class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-12 text-center"
        >
            <Inbox class="size-8 text-muted-foreground" />
            <p class="font-medium">
                {{
                    filters.status === 'pending'
                        ? 'Tidak ada penarikan yang menunggu.'
                        : 'Belum ada penarikan.'
                }}
            </p>
        </div>

        <ul v-else class="space-y-3">
            <li
                v-for="row in withdrawals.data"
                :key="row.id"
                class="grid gap-4 rounded-lg border p-4 lg:grid-cols-[1.2fr_1fr_auto] lg:items-center"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <Avatar class="size-10 shrink-0">
                        <AvatarImage
                            v-if="row.instructor.avatar"
                            :src="row.instructor.avatar"
                            :alt="row.instructor.name"
                        />
                        <AvatarFallback>{{
                            getInitials(row.instructor.name)
                        }}</AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                        <p class="flex flex-wrap items-center gap-2">
                            <Link
                                :href="
                                    instructorRoutes.show(row.instructor.slug)
                                "
                                class="font-semibold hover:underline"
                                >{{ row.instructor.name }}</Link
                            >
                            <WithdrawalStatusBadge :status="row.status" />
                        </p>
                        <p
                            class="text-2xl font-bold tracking-tight tabular-nums"
                        >
                            {{ formatRupiah(row.amount) }}
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Diajukan {{ formatDateTime(row.created_at) }} ·
                            saldo tersedia
                            {{ formatRupiah(row.instructor.balance.available) }}
                            dari
                            {{ formatRupiah(row.instructor.balance.earned) }}
                        </p>
                        <p v-if="row.note" class="mt-1 text-sm">
                            “{{ row.note }}”
                        </p>
                    </div>
                </div>

                <div class="rounded-lg bg-muted/50 p-3 text-sm">
                    <p class="text-xs text-muted-foreground">Transfer ke</p>
                    <p class="flex items-center gap-2 font-semibold">
                        {{ row.bank_name }}
                        <span class="font-mono">{{ row.account_number }}</span>
                        <button
                            type="button"
                            class="text-muted-foreground hover:text-foreground"
                            :aria-label="`Salin nomor rekening ${row.account_number}`"
                            @click="copyAccount(row)"
                        >
                            <Check
                                v-if="copied === row.id"
                                class="size-4 text-emerald-600"
                            />
                            <Copy v-else class="size-4" />
                        </button>
                    </p>
                    <p>a.n. {{ row.account_name }}</p>
                    <p
                        v-if="
                            row.status !== 'pending' &&
                            row.status !== 'cancelled'
                        "
                        class="mt-2 border-t pt-2 text-xs text-muted-foreground"
                    >
                        {{ row.status === 'paid' ? 'Ditransfer' : 'Ditolak' }}
                        oleh {{ row.processor?.name ?? '-' }}
                        <template v-if="row.processed_at">
                            · {{ formatDateTime(row.processed_at) }}</template
                        >
                        <span
                            v-if="row.admin_note"
                            class="block text-foreground"
                            >Catatan: {{ row.admin_note }}</span
                        >
                    </p>
                </div>

                <div class="flex flex-wrap gap-2 lg:justify-end">
                    <template v-if="row.status === 'pending'">
                        <Button
                            size="sm"
                            variant="outline"
                            class="text-red-600 hover:text-red-700"
                            @click="decide(row, 'reject')"
                        >
                            <X class="mr-1 size-4" />
                            Tolak
                        </Button>
                        <Button size="sm" @click="decide(row, 'pay')">
                            <Check class="mr-1 size-4" />
                            Tandai sudah ditransfer
                        </Button>
                    </template>
                    <Button
                        v-else-if="row.has_proof"
                        size="sm"
                        variant="outline"
                        @click="showProof(row)"
                    >
                        <Receipt class="mr-1 size-4" />
                        Bukti transfer
                    </Button>
                </div>
            </li>
        </ul>

        <div
            v-if="withdrawals.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="withdrawals.current_page <= 1"
                @click="visit({ page: withdrawals.current_page - 1 })"
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
                :disabled="withdrawals.current_page >= withdrawals.last_page"
                @click="visit({ page: withdrawals.current_page + 1 })"
            >
                Berikutnya
            </Button>
        </div>

        <Dialog
            :open="decision !== null"
            @update:open="(open) => !open && (decision = null)"
        >
            <DialogContent v-if="decision">
                <DialogHeader>
                    <DialogTitle>
                        {{
                            decision.action === 'pay'
                                ? 'Tandai sudah ditransfer?'
                                : 'Tolak penarikan?'
                        }}
                    </DialogTitle>
                    <DialogDescription>
                        <template v-if="decision.action === 'pay'">
                            Pastikan Anda sudah mentransfer
                            {{ formatRupiah(decision.row.amount) }} ke
                            {{ decision.row.bank_name }}
                            {{ decision.row.account_number }} a.n.
                            {{ decision.row.account_name }}.
                        </template>
                        <template v-else>
                            {{ formatRupiah(decision.row.amount) }} kembali ke
                            saldo {{ decision.row.instructor.name }}. Alasan di
                            bawah ditampilkan kepadanya.
                        </template>
                    </DialogDescription>
                </DialogHeader>

                <form
                    id="withdrawal-decision"
                    class="grid gap-4"
                    @submit.prevent="submitDecision"
                >
                    <div v-if="decision.action === 'pay'" class="grid gap-2">
                        <Label for="proof">
                            Bukti transfer
                            <span class="font-normal text-muted-foreground"
                                >(opsional, JPG/PNG/WEBP maks. 3 MB)</span
                            >
                        </Label>
                        <Input
                            id="proof"
                            type="file"
                            accept="image/jpeg,image/png,image/webp"
                            @change="pickProof"
                        />
                        <InputError :message="decisionForm.errors.proof" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="note">
                            {{
                                decision.action === 'pay'
                                    ? 'Catatan (opsional)'
                                    : 'Alasan penolakan'
                            }}
                        </Label>
                        <Textarea
                            id="note"
                            v-model="decisionForm.note"
                            rows="3"
                            maxlength="1000"
                            :required="decision.action === 'reject'"
                            :placeholder="
                                decision.action === 'pay'
                                    ? 'Contoh: nomor referensi transfer'
                                    : 'Contoh: nama pemilik rekening tidak sesuai'
                            "
                        />
                        <InputError :message="decisionForm.errors.note" />
                    </div>
                </form>

                <DialogFooter>
                    <Button variant="outline" @click="decision = null"
                        >Batal</Button
                    >
                    <Button
                        type="submit"
                        form="withdrawal-decision"
                        :variant="
                            decision.action === 'pay'
                                ? 'default'
                                : 'destructive'
                        "
                        :disabled="decisionForm.processing"
                    >
                        {{
                            decision.action === 'pay'
                                ? 'Sudah ditransfer'
                                : 'Tolak'
                        }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

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
