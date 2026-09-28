<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Search, TriangleAlert } from '@lucide/vue';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import SalesTabs from '@/components/SalesTabs.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
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
};

const props = defineProps<{
    orders: Paginated<OrderRow>;
    counts: Partial<Record<OrderStatus, number>>;
    statuses: OrderStatus[];
    filters: { status?: string; search?: string };
    paymentReady: boolean;
    canManage: boolean;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Penjualan', href: '/dasbor/pesanan' },
            { title: 'Pesanan', href: '/dasbor/pesanan' },
        ],
    },
});

const search = ref(props.filters.search ?? '');

function query(overrides: Record<string, string | number | undefined> = {}) {
    return {
        status: props.filters.status || undefined,
        search: search.value || undefined,
        ...overrides,
    };
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
        <Head title="Pesanan" />

        <SalesTabs />

        <Heading
            variant="small"
            title="Pesanan"
            :description="
                canManage
                    ? 'Pembelian kursus berbayar. Cek bukti pembayaran lalu konfirmasi agar siswa langsung terdaftar.'
                    : 'Pembelian kursus Anda. Pembayaran dikonfirmasi oleh admin.'
            "
        />

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
            class="flex max-w-md gap-2"
            role="search"
            @submit.prevent="applySearch"
        >
            <div class="relative flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    aria-label="Cari pesanan"
                    placeholder="Nomor pesanan, siswa, atau kursus..."
                    class="pl-9"
                />
            </div>
            <Button variant="outline" type="submit">Cari</Button>
        </form>

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
                                Belum ada pesanan.
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
                                    class="font-mono font-medium text-primary underline-offset-4 hover:underline"
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
                                class="p-4 text-right align-middle font-medium tabular-nums"
                            >
                                {{ formatRupiah(order.total) }}
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
