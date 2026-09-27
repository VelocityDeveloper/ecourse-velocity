<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Search } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { formatDateTime, formatRupiah, paymentMethodLabel } from '@/lib/course';
import adminOrders from '@/routes/admin/orders';
import transactionRoutes from '@/routes/admin/transactions';
import type {
    OrderPerson,
    Paginated,
    PaymentDetails,
    PaymentMethod,
} from '@/types';

type TransactionRow = {
    id: number;
    amount: number;
    payment_method: PaymentMethod;
    payment_details: PaymentDetails | null;
    paid_at: string;
    order: { number: string; course_id: number | null; course_title: string };
    student: OrderPerson;
    confirmer: { id: number; name: string } | null;
};

const props = defineProps<{
    transactions: Paginated<TransactionRow>;
    summary: {
        filtered_total: number;
        filtered_count: number;
        today: number;
        this_month: number;
        all_time: number;
    };
    filters: { from?: string; to?: string; search?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Transaksi', href: '/admin/transactions' },
        ],
    },
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const search = ref(props.filters.search ?? '');

const query = computed(() => ({
    from: from.value || undefined,
    to: to.value || undefined,
    search: search.value || undefined,
}));

const exportUrl = computed(
    () => transactionRoutes.export({ query: query.value }).url,
);

const tiles = computed(() => [
    { label: 'Hari ini', value: props.summary.today },
    { label: 'Bulan ini', value: props.summary.this_month },
    { label: 'Sepanjang waktu', value: props.summary.all_time },
]);

function apply(): void {
    router.get(transactionRoutes.index().url, query.value, {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number): void {
    router.get(
        transactionRoutes.index().url,
        { ...query.value, page },
        { preserveState: true },
    );
}

function destination(row: TransactionRow): string {
    const details = row.payment_details;

    if (details?.bank) {
        return `${details.bank} ${details.account_number}`;
    }

    return details?.qris_name ?? '-';
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Transaksi" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Transaksi"
                description="Pembayaran yang sudah dikonfirmasi."
            />
            <a :href="exportUrl">
                <Button variant="outline" size="sm">
                    <Download class="mr-2 h-4 w-4" />
                    Unduh CSV
                </Button>
            </a>
        </div>

        <div class="grid gap-4 sm:grid-cols-3">
            <div
                v-for="tile in tiles"
                :key="tile.label"
                class="rounded-lg border p-4"
            >
                <p class="text-2xl font-semibold tracking-tight tabular-nums">
                    {{ formatRupiah(tile.value) }}
                </p>
                <p class="text-sm text-muted-foreground">
                    Pemasukan {{ tile.label.toLowerCase() }}
                </p>
            </div>
        </div>

        <form
            class="grid gap-3 rounded-lg border p-4 sm:grid-cols-[160px_160px_1fr_auto] sm:items-end"
            @submit.prevent="apply"
        >
            <div class="grid content-start gap-2">
                <Label for="from">Dari tanggal</Label>
                <Input id="from" v-model="from" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="to">Sampai tanggal</Label>
                <Input id="to" v-model="to" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="search">Cari</Label>
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="search"
                        v-model="search"
                        type="search"
                        placeholder="Nomor pesanan, siswa, atau kursus..."
                        class="pl-9"
                    />
                </div>
            </div>
            <Button type="submit" variant="outline">Terapkan</Button>
        </form>

        <p class="text-sm text-muted-foreground">
            {{ summary.filtered_count }} transaksi ·
            <span class="font-semibold text-foreground">{{
                formatRupiah(summary.filtered_total)
            }}</span>
        </p>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                v-for="heading in [
                                    'Tanggal bayar',
                                    'Pesanan',
                                    'Siswa',
                                    'Kursus',
                                    'Metode',
                                    'Jumlah',
                                    'Dikonfirmasi',
                                ]"
                                :key="heading"
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                                :class="{ 'text-right': heading === 'Jumlah' }"
                            >
                                {{ heading }}
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transactions.data.length === 0">
                            <td
                                colspan="7"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Belum ada transaksi.
                            </td>
                        </tr>
                        <tr
                            v-for="row in transactions.data"
                            :key="row.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle whitespace-nowrap">
                                {{ formatDateTime(row.paid_at) }}
                            </td>
                            <td class="p-4 align-middle">
                                <Link
                                    :href="adminOrders.show(row.order.number)"
                                    class="font-mono font-medium text-primary underline-offset-4 hover:underline"
                                    >{{ row.order.number }}</Link
                                >
                            </td>
                            <td class="p-4 align-middle">
                                <span class="block font-medium">{{
                                    row.student.name
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ row.student.email }}</span
                                >
                            </td>
                            <td class="max-w-56 truncate p-4 align-middle">
                                {{ row.order.course_title }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ paymentMethodLabel(row.payment_method) }}
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ destination(row) }}</span
                                >
                            </td>
                            <td
                                class="p-4 text-right align-middle font-medium tabular-nums"
                            >
                                {{ formatRupiah(row.amount) }}
                            </td>
                            <td class="p-4 align-middle">
                                {{ row.confirmer?.name ?? '-' }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="transactions.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="transactions.current_page <= 1"
                @click="goToPage(transactions.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ transactions.current_page }} dari
                {{ transactions.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="transactions.current_page >= transactions.last_page"
                @click="goToPage(transactions.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
