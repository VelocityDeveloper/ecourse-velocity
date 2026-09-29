<script setup lang="ts">
import FinanceNav from '@/components/FinanceNav.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Search, TriangleAlert } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import {
    formatDateTime,
    formatRupiah,
    orderStatusLabel,
    orderStatusVariant,
    paymentMethodLabel,
} from '@/lib/course';
import adminOrders from '@/routes/admin/orders';
import paymentSettings from '@/routes/admin/payment-settings';
import type {
    OrderPerson,
    OrderStatus,
    OrderSummary,
    Paginated,
} from '@/types';

type OrderRow = OrderSummary & {
    student: OrderPerson;
    proof_uploaded_at: string | null;
    /** How a paid order's money was split; null until it is paid. */
    payment: {
        commission_rate: number;
        commission_amount: number;
        instructor_amount: number;
        confirmer: string | null;
    } | null;
};

const props = defineProps<{
    orders: Paginated<OrderRow>;
    counts: Partial<Record<OrderStatus, number>>;
    statuses: OrderStatus[];
    paid: {
        count: number;
        amount: number;
        commission: number;
        instructor: number;
    };
    filters: { status?: string; search?: string; from?: string; to?: string };
    paymentReady: boolean;
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
            { title: 'Pesanan', href: '/dasbor/keuangan/pesanan' },
        ],
    },
});

const search = ref(props.filters.search ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

function query(overrides: Record<string, string | number | undefined> = {}) {
    return {
        status: props.filters.status || undefined,
        search: search.value || undefined,
        from: from.value || undefined,
        to: to.value || undefined,
        ...overrides,
    };
}

const exportUrl = computed(
    () => adminOrders.export({ query: query({ page: undefined }) }).url,
);

const hasFilters = computed(() =>
    Boolean(props.filters.search || props.filters.from || props.filters.to),
);

function resetFilters(): void {
    search.value = '';
    from.value = '';
    to.value = '';
    applySearch();
}

function formatRate(rate: number): string {
    return `${rate.toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
}

function filterStatus(status: string | undefined): void {
    router.get(adminOrders.index().url, query({ status }), {
        preserveState: true,
        replace: true,
    });
}

function applySearch(): void {
    router.get(adminOrders.index().url, query(), {
        preserveState: true,
        replace: true,
    });
}

function goToPage(page: number): void {
    router.get(adminOrders.index().url, query({ page }), {
        preserveState: true,
    });
}

const totalCount = (): number =>
    Object.values(props.counts).reduce((sum, value) => sum + (value ?? 0), 0);
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Pesanan · Keuangan" />

        <FinanceNav />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Pesanan & pembayaran"
                :description="
                    canManage
                        ? 'Semua pembelian kursus. Cek bukti bayar lalu konfirmasi; pesanan yang lunas menjadi pemasukan beserta bagian platformnya.'
                        : 'Pembelian kursus Anda. Pembayaran dikonfirmasi oleh admin.'
                "
            />
            <a :href="exportUrl">
                <Button variant="outline" size="sm">
                    <Download class="mr-2 h-4 w-4" />
                    Unduh CSV
                </Button>
            </a>
        </div>

        <div
            v-if="canManage && !paymentReady"
            class="flex items-start gap-3 rounded-lg border border-destructive/40 bg-destructive/5 p-4 text-sm"
        >
            <TriangleAlert class="size-5 shrink-0 text-destructive" />
            <p>
                Belum ada rekening bank atau QRIS, jadi siswa belum bisa
                membayar.
                <Link
                    :href="paymentSettings.edit()"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                    >Atur pembayaran</Link
                >
            </p>
        </div>

        <div class="flex flex-wrap gap-2">
            <Button
                size="sm"
                :variant="!filters.status ? 'default' : 'outline'"
                @click="filterStatus(undefined)"
            >
                Semua
                <Badge variant="secondary" class="ml-1.5">{{
                    totalCount()
                }}</Badge>
            </Button>
            <Button
                v-for="status in statuses"
                :key="status"
                size="sm"
                :variant="filters.status === status ? 'default' : 'outline'"
                @click="filterStatus(status)"
            >
                {{ orderStatusLabel(status) }}
                <Badge variant="secondary" class="ml-1.5">{{
                    counts[status] ?? 0
                }}</Badge>
            </Button>
        </div>

        <form
            class="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 lg:grid-cols-[1fr_160px_160px_auto] lg:items-end"
            role="search"
            @submit.prevent="applySearch"
        >
            <div class="grid content-start gap-2 sm:col-span-2 lg:col-span-1">
                <Label for="order-search">Cari</Label>
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="order-search"
                        v-model="search"
                        type="search"
                        placeholder="Nomor pesanan, siswa, atau kursus..."
                        class="pl-9"
                    />
                </div>
            </div>
            <div class="grid content-start gap-2">
                <Label for="order-from">Dari tanggal</Label>
                <Input id="order-from" v-model="from" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="order-to">Sampai</Label>
                <Input id="order-to" v-model="to" type="date" />
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-1">
                <Button
                    v-if="hasFilters"
                    type="button"
                    variant="ghost"
                    @click="resetFilters"
                >
                    Atur ulang
                </Button>
                <Button type="submit">Terapkan</Button>
            </div>
            <p
                class="text-xs text-muted-foreground sm:col-span-2 lg:col-span-4"
            >
                Tanggal dihitung dari tanggal bayar untuk pesanan lunas, dan
                tanggal dibuat untuk pesanan lainnya.
            </p>
        </form>

        <div
            class="grid grid-cols-2 gap-px overflow-hidden rounded-lg border bg-border lg:grid-cols-4"
        >
            <div class="bg-background p-4">
                <p class="text-xs text-muted-foreground">Pesanan lunas</p>
                <p class="text-lg font-semibold tabular-nums">
                    {{ paid.count }}
                </p>
            </div>
            <div class="bg-background p-4">
                <p class="text-xs text-muted-foreground">Dibayar siswa</p>
                <p class="text-lg font-semibold tabular-nums">
                    {{ formatRupiah(paid.amount) }}
                </p>
            </div>
            <div class="bg-background p-4">
                <p class="text-xs text-muted-foreground">
                    {{ canManage ? 'Pemasukan platform' : 'Potongan platform' }}
                </p>
                <p class="text-lg font-semibold tabular-nums">
                    {{ formatRupiah(paid.commission) }}
                </p>
            </div>
            <div class="bg-background p-4">
                <p class="text-xs text-muted-foreground">
                    {{ canManage ? 'Bagian instruktur' : 'Pendapatan Anda' }}
                </p>
                <p class="text-lg font-semibold text-primary tabular-nums">
                    {{ formatRupiah(paid.instructor) }}
                </p>
            </div>
        </div>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Pesanan
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Siswa
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Kursus
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Total
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Metode
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Status
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="orders.data.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Tidak ada pesanan yang cocok.
                            </td>
                        </tr>
                        <tr
                            v-for="order in orders.data"
                            :key="order.number"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="adminOrders.show(order.number)"
                                    class="font-mono font-medium whitespace-nowrap text-primary underline-offset-4 hover:underline"
                                >
                                    {{ order.number }}
                                </Link>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{
                                        formatDateTime(order.created_at)
                                    }}</span
                                >
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        class="size-8 overflow-hidden rounded-full"
                                    >
                                        <AvatarImage
                                            v-if="order.student.avatar"
                                            :src="order.student.avatar"
                                            :alt="order.student.name"
                                        />
                                        <AvatarFallback class="text-xs">{{
                                            getInitials(order.student.name)
                                        }}</AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0">
                                        <span
                                            class="block truncate font-medium"
                                            >{{ order.student.name }}</span
                                        >
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                            >{{ order.student.email }}</span
                                        >
                                    </span>
                                </div>
                            </td>
                            <td class="max-w-56 truncate p-4 align-middle">
                                {{ order.course_title }}
                            </td>
                            <td
                                class="p-4 text-right align-middle whitespace-nowrap tabular-nums"
                            >
                                <span class="font-medium">{{
                                    formatRupiah(order.total)
                                }}</span>
                                <span
                                    v-if="order.payment"
                                    class="block text-xs text-muted-foreground"
                                    >{{ canManage ? 'Platform' : 'Potongan' }}
                                    {{
                                        formatRupiah(
                                            order.payment.commission_amount,
                                        )
                                    }}
                                    ({{
                                        formatRate(
                                            order.payment.commission_rate,
                                        )
                                    }})</span
                                >
                                <span
                                    v-if="order.payment"
                                    class="block text-xs text-primary"
                                    >{{ canManage ? 'Instruktur' : 'Anda' }}
                                    {{
                                        formatRupiah(
                                            order.payment.instructor_amount,
                                        )
                                    }}</span
                                >
                            </td>
                            <td class="p-4 align-middle">
                                {{ paymentMethodLabel(order.payment_method) }}
                            </td>
                            <td class="p-4 align-middle">
                                <Badge
                                    :variant="orderStatusVariant(order.status)"
                                >
                                    {{ orderStatusLabel(order.status) }}
                                </Badge>
                                <span
                                    v-if="order.paid_at"
                                    class="mt-1 block text-xs whitespace-nowrap text-muted-foreground"
                                    >{{ formatDateTime(order.paid_at) }}</span
                                >
                                <span
                                    v-if="order.payment?.confirmer"
                                    class="block text-xs whitespace-nowrap text-muted-foreground"
                                    >oleh {{ order.payment.confirmer }}</span
                                >
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="orders.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="orders.current_page <= 1"
                @click="goToPage(orders.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ orders.current_page }} dari {{ orders.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="orders.current_page >= orders.last_page"
                @click="goToPage(orders.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
