<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import FinanceNav from '@/components/FinanceNav.vue';
import { formatDateTime, formatRupiah } from '@/lib/course';
import financeInstructors from '@/routes/admin/finance/instructors';
import orders from '@/routes/admin/orders';
import withdrawals from '@/routes/admin/withdrawals';

const props = defineProps<{
    sales: {
        today: number;
        today_count: number;
        month: number;
        month_count: number;
        commission_month: number;
        instructor_month: number;
    };
    instructors: { balance: number; paid_out: number };
    waiting: {
        orders_count: number;
        orders_total: number;
        withdrawals_count: number;
        withdrawals_total: number;
    };
    waitingOrders: Array<{
        number: string;
        course_title: string;
        total: number;
        student: string;
        updated_at: string | null;
    }>;
    waitingWithdrawals: Array<{
        id: number;
        amount: number;
        bank_name: string;
        instructor: string;
        created_at: string | null;
    }>;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
        ],
    },
});

function isoDate(date: Date): string {
    const pad = (n: number) => String(n).padStart(2, '0');

    return `${date.getFullYear()}-${pad(date.getMonth() + 1)}-${pad(date.getDate())}`;
}

const now = new Date();
const todayUrl = orders.index({
    query: { status: 'paid', from: isoDate(now), to: isoDate(now) },
}).url;
const monthUrl = orders.index({
    query: {
        status: 'paid',
        from: isoDate(new Date(now.getFullYear(), now.getMonth(), 1)),
        to: isoDate(now),
    },
}).url;

const salesTiles = computed(() => [
    {
        label: 'Penjualan hari ini',
        value: props.sales.today,
        note: `${props.sales.today_count} transaksi`,
        href: todayUrl,
    },
    {
        label: 'Penjualan bulan ini',
        value: props.sales.month,
        note: `${props.sales.month_count} transaksi`,
        href: monthUrl,
    },
    {
        label: 'Pemasukan platform bulan ini',
        value: props.sales.commission_month,
        note: 'bagian platform dari penjualan',
        href: monthUrl,
    },
    {
        label: 'Pendapatan instruktur bulan ini',
        value: props.sales.instructor_month,
        note: 'setelah dikurangi pemasukan platform',
        href: financeInstructors.index().url,
    },
]);

const balanceTiles = computed(() => [
    {
        label: 'Saldo instruktur belum ditarik',
        value: props.instructors.balance,
        note: `Sudah ditransfer ${formatRupiah(props.instructors.paid_out)}`,
        href: financeInstructors.index().url,
        alert: false,
    },
    {
        label: 'Penarikan menunggu transfer',
        value: props.waiting.withdrawals_total,
        note: `${props.waiting.withdrawals_count} permintaan`,
        href: withdrawals.index().url,
        alert: props.waiting.withdrawals_count > 0,
    },
    {
        label: 'Pesanan menunggu konfirmasi',
        value: props.waiting.orders_total,
        note: `${props.waiting.orders_count} bukti bayar perlu dicek`,
        href: orders.index({ query: { status: 'awaiting_confirmation' } }).url,
        alert: props.waiting.orders_count > 0,
    },
]);
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Ringkasan · Keuangan" />

        <FinanceNav />

        <section class="flex flex-col gap-3">
            <h2 class="text-sm font-medium text-muted-foreground">Penjualan</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <Link
                    v-for="tile in salesTiles"
                    :key="tile.label"
                    :href="tile.href"
                    class="group rounded-lg border p-4 transition-colors hover:border-primary/40 hover:bg-muted/40"
                >
                    <p
                        class="text-xl font-semibold tracking-tight tabular-nums sm:text-2xl"
                    >
                        {{ formatRupiah(tile.value) }}
                    </p>
                    <p class="text-sm">{{ tile.label }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ tile.note }}
                    </p>
                </Link>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <h2 class="text-sm font-medium text-muted-foreground">
                Saldo & yang perlu ditindaklanjuti
            </h2>
            <div class="grid gap-3 sm:grid-cols-3 sm:gap-4">
                <Link
                    v-for="tile in balanceTiles"
                    :key="tile.label"
                    :href="tile.href"
                    class="group flex items-center justify-between gap-3 rounded-lg border p-4 transition-colors hover:bg-muted/40"
                    :class="
                        tile.alert
                            ? 'border-amber-300 bg-amber-50 dark:border-amber-500/40 dark:bg-amber-500/10'
                            : 'hover:border-primary/40'
                    "
                >
                    <span>
                        <span
                            class="block text-xl font-semibold tracking-tight tabular-nums sm:text-2xl"
                            >{{ formatRupiah(tile.value) }}</span
                        >
                        <span class="block text-sm">{{ tile.label }}</span>
                        <span class="mt-1 block text-xs text-muted-foreground">
                            {{ tile.note }}
                        </span>
                    </span>
                    <ChevronRight
                        class="size-5 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                    />
                </Link>
            </div>
        </section>

        <div class="grid items-start gap-6 lg:grid-cols-2">
            <section class="min-w-0 rounded-lg border">
                <div
                    class="flex items-center justify-between gap-2 border-b px-4 py-3"
                >
                    <h2 class="font-semibold">Pesanan menunggu konfirmasi</h2>
                    <Link
                        :href="
                            orders.index({
                                query: { status: 'awaiting_confirmation' },
                            })
                        "
                        class="text-sm text-primary hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <p
                    v-if="waitingOrders.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    Tidak ada bukti bayar yang menunggu.
                </p>
                <ul v-else class="divide-y">
                    <li v-for="order in waitingOrders" :key="order.number">
                        <Link
                            :href="orders.show(order.number)"
                            class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-muted/40"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{
                                    order.course_title
                                }}</span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ order.student }} · {{ order.number }}
                                    <template v-if="order.updated_at">
                                        ·
                                        {{
                                            formatDateTime(order.updated_at)
                                        }}</template
                                    ></span
                                >
                            </span>
                            <span
                                class="shrink-0 font-medium whitespace-nowrap tabular-nums"
                                >{{ formatRupiah(order.total) }}</span
                            >
                        </Link>
                    </li>
                </ul>
            </section>

            <section class="min-w-0 rounded-lg border">
                <div
                    class="flex items-center justify-between gap-2 border-b px-4 py-3"
                >
                    <h2 class="font-semibold">Penarikan menunggu transfer</h2>
                    <Link
                        :href="withdrawals.index()"
                        class="text-sm text-primary hover:underline"
                    >
                        Lihat semua
                    </Link>
                </div>
                <p
                    v-if="waitingWithdrawals.length === 0"
                    class="px-4 py-6 text-center text-sm text-muted-foreground"
                >
                    Tidak ada penarikan yang menunggu.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="withdrawal in waitingWithdrawals"
                        :key="withdrawal.id"
                    >
                        <Link
                            :href="withdrawals.index()"
                            class="flex items-center justify-between gap-3 px-4 py-3 hover:bg-muted/40"
                        >
                            <span class="min-w-0">
                                <span class="block truncate font-medium">{{
                                    withdrawal.instructor
                                }}</span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ withdrawal.bank_name }}
                                    <template v-if="withdrawal.created_at">
                                        ·
                                        {{
                                            formatDateTime(
                                                withdrawal.created_at,
                                            )
                                        }}</template
                                    ></span
                                >
                            </span>
                            <span
                                class="shrink-0 font-medium whitespace-nowrap tabular-nums"
                                >{{ formatRupiah(withdrawal.amount) }}</span
                            >
                        </Link>
                    </li>
                </ul>
            </section>
        </div>
    </div>
</template>
