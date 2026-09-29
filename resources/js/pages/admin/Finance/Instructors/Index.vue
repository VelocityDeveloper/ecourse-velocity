<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Download, Search, Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import FinanceNav from '@/components/FinanceNav.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
import { formatDate, formatRupiah } from '@/lib/course';
import instructorRoutes from '@/routes/admin/finance/instructors';
import type { InstructorRow, Paginated } from '@/types';

type Filters = {
    search?: string;
    from?: string;
    to?: string;
    sort?: string;
    sales?: string;
};

const props = defineProps<{
    instructors: Paginated<InstructorRow>;
    summary: {
        instructors: number;
        with_sales: number;
        gross: number;
        commission: number;
        revenue: number;
        transactions: number;
    };
    filters: Filters;
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
            { title: 'Instruktur', href: '/dasbor/keuangan/instruktur' },
        ],
    },
});

const SORTS = [
    { value: 'revenue', label: 'Pendapatan tertinggi' },
    { value: 'students', label: 'Siswa terbanyak' },
    { value: 'courses', label: 'Kursus terbanyak' },
    { value: 'newest', label: 'Terbaru bergabung' },
    { value: 'name', label: 'Nama (A–Z)' },
];

const SALES = [
    { value: 'all', label: 'Semua instruktur' },
    { value: 'with_sales', label: 'Ada penjualan' },
    { value: 'without_sales', label: 'Belum ada penjualan' },
];

const search = ref(props.filters.search ?? '');
const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');
const sort = ref(props.filters.sort ?? 'revenue');
const sales = ref(props.filters.sales ?? 'all');

const query = computed(() => ({
    search: search.value || undefined,
    from: from.value || undefined,
    to: to.value || undefined,
    sort: sort.value === 'revenue' ? undefined : sort.value,
    sales: sales.value === 'all' ? undefined : sales.value,
}));

const hasPeriod = computed(() =>
    Boolean(props.filters.from || props.filters.to),
);

const periodLabel = computed(() => {
    const { from: start, to: end } = props.filters;

    if (start && end) {
        return `${formatDate(start)} – ${formatDate(end)}`;
    }

    if (start) {
        return `sejak ${formatDate(start)}`;
    }

    if (end) {
        return `sampai ${formatDate(end)}`;
    }

    return 'sepanjang waktu';
});

const exportUrl = computed(
    () => instructorRoutes.export({ query: query.value }).url,
);

const tiles = computed(() => [
    {
        label: 'Instruktur terdaftar',
        value: String(props.summary.instructors),
        note: `${props.summary.with_sales} dengan penjualan (${periodLabel.value})`,
    },
    {
        label: `Total penjualan (${periodLabel.value})`,
        value: formatRupiah(props.summary.gross),
        note: `${props.summary.transactions} transaksi`,
    },
    {
        label: `Pemasukan platform (${periodLabel.value})`,
        value: formatRupiah(props.summary.commission),
        note: null,
    },
    {
        label: `Pendapatan instruktur (${periodLabel.value})`,
        value: formatRupiah(props.summary.revenue),
        note: 'setelah dikurangi pemasukan platform',
    },
]);

function apply(): void {
    router.get(instructorRoutes.index().url, query.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function reset(): void {
    search.value = '';
    from.value = '';
    to.value = '';
    sort.value = 'revenue';
    sales.value = 'all';
    apply();
}

function goToPage(page: number): void {
    router.get(
        instructorRoutes.index().url,
        { ...query.value, page },
        { preserveState: true },
    );
}

function detailUrl(row: InstructorRow): string {
    return instructorRoutes.show(row.slug, {
        query: { from: query.value.from, to: query.value.to },
    }).url;
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Instruktur · Keuangan" />

        <FinanceNav />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Pendapatan & saldo instruktur"
                description="Penjualan, pemasukan platform, pendapatan bersih, penarikan, dan saldo setiap instruktur."
            />
            <a :href="exportUrl">
                <Button variant="outline" size="sm">
                    <Download class="mr-2 h-4 w-4" />
                    Unduh CSV
                </Button>
            </a>
        </div>

        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <div
                v-for="tile in tiles"
                :key="tile.label"
                class="rounded-lg border p-4"
            >
                <p class="text-2xl font-semibold tracking-tight tabular-nums">
                    {{ tile.value }}
                </p>
                <p class="text-sm text-muted-foreground">{{ tile.label }}</p>
                <p v-if="tile.note" class="mt-1 text-xs text-muted-foreground">
                    {{ tile.note }}
                </p>
            </div>
        </div>

        <form
            class="grid gap-3 rounded-lg border p-4 sm:grid-cols-2 lg:grid-cols-[1fr_150px_150px_190px_190px] lg:items-end"
            @submit.prevent="apply"
        >
            <div class="grid content-start gap-2 sm:col-span-2 lg:col-span-1">
                <Label for="search">Cari</Label>
                <div class="relative">
                    <Search
                        class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                    />
                    <Input
                        id="search"
                        v-model="search"
                        type="search"
                        placeholder="Nama, email, atau keahlian..."
                        class="pl-9"
                    />
                </div>
            </div>
            <div class="grid content-start gap-2">
                <Label for="from">Pendapatan dari</Label>
                <Input id="from" v-model="from" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="to">Sampai</Label>
                <Input id="to" v-model="to" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label>Penjualan</Label>
                <Select v-model="sales" @update:model-value="apply">
                    <SelectTrigger class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in SALES"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="grid content-start gap-2">
                <Label>Urutkan</Label>
                <Select v-model="sort" @update:model-value="apply">
                    <SelectTrigger class="w-full">
                        <SelectValue />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem
                            v-for="option in SORTS"
                            :key="option.value"
                            :value="option.value"
                        >
                            {{ option.label }}
                        </SelectItem>
                    </SelectContent>
                </Select>
            </div>
            <div class="flex gap-2 sm:col-span-2 lg:col-span-5 lg:justify-end">
                <Button type="button" variant="ghost" @click="reset">
                    Atur ulang
                </Button>
                <Button type="submit">Terapkan</Button>
            </div>
        </form>

        <p class="text-sm text-muted-foreground">
            {{ instructors.total }} instruktur · pendapatan
            {{ periodLabel }}
        </p>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Instruktur
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Kursus
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Siswa
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Rating
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Keuangan
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="instructors.data.length === 0">
                            <td
                                :colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Tidak ada instruktur yang cocok.
                            </td>
                        </tr>
                        <tr
                            v-for="row in instructors.data"
                            :key="row.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Link
                                    :href="detailUrl(row)"
                                    class="group flex min-w-56 items-center gap-3"
                                >
                                    <Avatar class="size-10 shrink-0">
                                        <AvatarImage
                                            v-if="row.avatar"
                                            :src="row.avatar"
                                            :alt="row.name"
                                        />
                                        <AvatarFallback
                                            class="bg-primary/10 text-sm font-semibold text-primary"
                                        >
                                            {{ getInitials(row.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0">
                                        <span
                                            class="block font-medium group-hover:text-primary group-hover:underline"
                                            >{{ row.name }}</span
                                        >
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                            >{{ row.email }}</span
                                        >
                                        <span
                                            v-if="row.headline"
                                            class="block max-w-64 truncate text-xs text-muted-foreground"
                                            >{{ row.headline }}</span
                                        >
                                    </span>
                                </Link>
                            </td>
                            <td
                                class="p-4 text-right align-middle whitespace-nowrap tabular-nums"
                            >
                                {{ row.courses_count }}
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{
                                        row.published_courses_count
                                    }}
                                    terbit</span
                                >
                            </td>
                            <td
                                class="p-4 text-right align-middle tabular-nums"
                            >
                                {{ row.students_count }}
                            </td>
                            <td
                                class="p-4 text-right align-middle whitespace-nowrap tabular-nums"
                            >
                                <span
                                    v-if="row.rating !== null"
                                    class="inline-flex items-center gap-1"
                                >
                                    <Star
                                        class="size-3.5 fill-rating text-rating"
                                    />
                                    {{ row.rating.toFixed(1) }}
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td class="p-4 align-middle">
                                <dl
                                    class="grid min-w-72 grid-cols-2 gap-x-6 gap-y-2 text-xs"
                                >
                                    <div>
                                        <dt class="text-muted-foreground">
                                            Hari ini
                                        </dt>
                                        <dd
                                            class="text-sm font-medium tabular-nums"
                                        >
                                            {{
                                                formatRupiah(row.revenue_today)
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-muted-foreground">
                                            Bulan ini
                                        </dt>
                                        <dd
                                            class="text-sm font-medium tabular-nums"
                                        >
                                            {{
                                                formatRupiah(
                                                    row.revenue_this_month,
                                                )
                                            }}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-muted-foreground">
                                            Sudah ditarik
                                        </dt>
                                        <dd
                                            class="text-sm font-medium tabular-nums"
                                        >
                                            {{ formatRupiah(row.withdrawn) }}
                                        </dd>
                                        <dd
                                            v-if="row.withdrawal_pending > 0"
                                            class="text-amber-700 tabular-nums dark:text-amber-300"
                                        >
                                            +{{
                                                formatRupiah(
                                                    row.withdrawal_pending,
                                                )
                                            }}
                                            diproses
                                        </dd>
                                    </div>
                                    <div>
                                        <dt class="text-muted-foreground">
                                            Saldo tersedia
                                        </dt>
                                        <dd
                                            class="text-sm font-semibold text-primary tabular-nums"
                                        >
                                            {{ formatRupiah(row.balance) }}
                                        </dd>
                                    </div>
                                    <div
                                        v-if="hasPeriod"
                                        class="col-span-2 border-t pt-2 text-muted-foreground"
                                    >
                                        Periode ini:
                                        <span
                                            class="font-medium text-foreground tabular-nums"
                                            >{{
                                                formatRupiah(row.revenue)
                                            }}</span
                                        >
                                        · {{ row.sales_count }} transaksi
                                    </div>
                                </dl>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="instructors.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="instructors.current_page <= 1"
                @click="goToPage(instructors.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ instructors.current_page }} dari
                {{ instructors.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="instructors.current_page >= instructors.last_page"
                @click="goToPage(instructors.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
