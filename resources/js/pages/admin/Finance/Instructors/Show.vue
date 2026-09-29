<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowLeft, ExternalLink, Pencil, Star } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import {
    formatDate,
    formatDateTime,
    formatRupiah,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import instructorRoutes from '@/routes/admin/finance/instructors';
import adminOrders from '@/routes/admin/orders';
import adminUsers from '@/routes/admin/users';
import adminWithdrawals from '@/routes/admin/withdrawals';
import courseRoutes from '@/routes/courses';
import userRoutes from '@/routes/users';
import type {
    InstructorCourseRevenue,
    InstructorPayment,
    InstructorRow,
} from '@/types';

const props = defineProps<{
    instructor: InstructorRow & { bio: string | null };
    courses: InstructorCourseRevenue[];
    monthly: Array<{ month: string; revenue: number }>;
    transactions: InstructorPayment[];
    filters: { from?: string; to?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Keuangan', href: '/dasbor/keuangan' },
            { title: 'Instruktur', href: '/dasbor/keuangan/instruktur' },
            { title: 'Detail', href: '#' },
        ],
    },
});

const from = ref(props.filters.from ?? '');
const to = ref(props.filters.to ?? '');

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

// Without a period the first tile would repeat the all-time total, so it counts sales instead.
const tiles = computed(() => [
    {
        label: `Penjualan (${periodLabel.value})`,
        value: formatRupiah(props.instructor.gross),
        note: `${props.instructor.sales_count} transaksi`,
    },
    {
        label: `Pemasukan platform (${periodLabel.value})`,
        value: formatRupiah(props.instructor.commission),
        note: null,
    },
    {
        label: `Pendapatan bersih (${periodLabel.value})`,
        value: formatRupiah(props.instructor.revenue),
        note: hasPeriod.value
            ? `Sepanjang waktu ${formatRupiah(props.instructor.revenue_all_time)}`
            : 'setelah dikurangi pemasukan platform',
    },
    {
        label: 'Pendapatan bersih bulan ini',
        value: formatRupiah(props.instructor.revenue_this_month),
        note: `${props.instructor.students_count} siswa aktif`,
    },
]);

// Fixed figures, independent of the period filter below.
const finance = computed(() => [
    { label: 'Pendapatan hari ini', value: props.instructor.revenue_today },
    {
        label: 'Pendapatan bulan ini',
        value: props.instructor.revenue_this_month,
    },
    { label: 'Sudah ditarik', value: props.instructor.withdrawn },
    { label: 'Saldo tersedia', value: props.instructor.balance },
]);

const withdrawalsUrl = computed(
    () =>
        adminWithdrawals.index({
            query: { status: 'all', search: props.instructor.email },
        }).url,
);

function formatRate(rate: number): string {
    return `${rate.toLocaleString('id-ID', { maximumFractionDigits: 2 })}%`;
}

const chartMax = computed(() =>
    Math.max(...props.monthly.map((month) => month.revenue), 1),
);

const monthFormatter = new Intl.DateTimeFormat('id-ID', {
    month: 'short',
    year: '2-digit',
});

function monthLabel(month: string): string {
    const [year, index] = month.split('-').map(Number);

    return monthFormatter.format(new Date(year, index - 1, 1));
}

function apply(): void {
    router.get(
        instructorRoutes.show(props.instructor.slug).url,
        { from: from.value || undefined, to: to.value || undefined },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

function reset(): void {
    from.value = '';
    to.value = '';
    apply();
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head :title="`Keuangan: ${instructor.name}`" />

        <Link
            :href="instructorRoutes.index()"
            class="inline-flex w-fit items-center gap-1.5 text-sm text-muted-foreground hover:text-foreground"
        >
            <ArrowLeft class="size-4" />
            Semua instruktur
        </Link>

        <div
            class="flex flex-col gap-4 rounded-lg border p-5 sm:flex-row sm:items-center"
        >
            <Avatar class="size-16 shrink-0">
                <AvatarImage
                    v-if="instructor.avatar"
                    :src="instructor.avatar"
                    :alt="instructor.name"
                />
                <AvatarFallback
                    class="bg-primary/10 text-lg font-semibold text-primary"
                >
                    {{ getInitials(instructor.name) }}
                </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1 space-y-1">
                <h1 class="text-xl font-bold tracking-tight">
                    {{ instructor.name }}
                </h1>
                <p v-if="instructor.headline" class="text-sm">
                    {{ instructor.headline }}
                </p>
                <p class="text-sm text-muted-foreground">
                    {{ instructor.email }} · Bergabung
                    {{ formatDate(instructor.joined_at) }}
                    <template v-if="instructor.rating !== null">
                        ·
                        <span class="inline-flex items-center gap-1">
                            <Star class="size-3.5 fill-rating text-rating" />
                            {{ instructor.rating.toFixed(1) }}
                        </span>
                    </template>
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a
                    :href="userRoutes.show(instructor.slug).url"
                    target="_blank"
                    rel="noopener"
                >
                    <Button variant="outline" size="sm">
                        <ExternalLink class="mr-2 size-4" />
                        Profil publik
                    </Button>
                </a>
                <Link :href="adminUsers.edit(instructor.slug)">
                    <Button variant="outline" size="sm">
                        <Pencil class="mr-2 size-4" />
                        Ubah akun
                    </Button>
                </Link>
            </div>
        </div>

        <section class="rounded-lg border">
            <div
                class="flex flex-wrap items-center justify-between gap-2 border-b px-4 py-3"
            >
                <h2 class="font-semibold">Keuangan</h2>
                <Link
                    :href="withdrawalsUrl"
                    class="text-sm text-primary hover:underline"
                >
                    Riwayat penarikan
                </Link>
            </div>
            <dl class="grid grid-cols-2 lg:grid-cols-4">
                <div
                    v-for="(item, index) in finance"
                    :key="item.label"
                    class="p-4"
                    :class="{
                        'border-l': index % 2 === 1,
                        'border-t lg:border-t-0': index >= 2,
                        'lg:border-l': index === 2,
                    }"
                >
                    <dt class="text-sm text-muted-foreground">
                        {{ item.label }}
                    </dt>
                    <dd
                        class="mt-1 text-xl font-semibold tracking-tight tabular-nums"
                        :class="{ 'text-primary': index === 3 }"
                    >
                        {{ formatRupiah(item.value) }}
                    </dd>
                </div>
            </dl>
            <p
                v-if="instructor.withdrawal_pending > 0"
                class="border-t px-4 py-2 text-xs text-amber-700 dark:text-amber-300"
            >
                {{ formatRupiah(instructor.withdrawal_pending) }} sedang
                menunggu transfer (sudah dikurangi dari saldo).
            </p>
        </section>

        <form
            class="grid gap-3 rounded-lg border p-4 sm:grid-cols-[160px_160px_auto] sm:items-end"
            @submit.prevent="apply"
        >
            <div class="grid content-start gap-2">
                <Label for="from">Pendapatan dari</Label>
                <Input id="from" v-model="from" type="date" />
            </div>
            <div class="grid content-start gap-2">
                <Label for="to">Sampai</Label>
                <Input id="to" v-model="to" type="date" />
            </div>
            <div class="flex gap-2">
                <Button type="submit" variant="outline">Terapkan</Button>
                <Button
                    v-if="hasPeriod"
                    type="button"
                    variant="ghost"
                    @click="reset"
                >
                    Sepanjang waktu
                </Button>
            </div>
        </form>

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

        <section class="rounded-lg border p-4">
            <h2 class="font-semibold">Pendapatan bersih 12 bulan terakhir</h2>
            <div
                class="mt-4 flex h-44 items-end gap-1.5 sm:gap-2"
                role="img"
                :aria-label="`Grafik pendapatan bulanan ${instructor.name}`"
            >
                <div
                    v-for="month in monthly"
                    :key="month.month"
                    class="group flex h-full min-w-0 flex-1 flex-col items-center justify-end gap-1.5"
                    :title="`${monthLabel(month.month)}: ${formatRupiah(month.revenue)}`"
                >
                    <div
                        class="w-full rounded-t bg-primary/80 transition-colors group-hover:bg-primary"
                        :class="{ 'bg-muted!': month.revenue === 0 }"
                        :style="{
                            height: `${Math.max((month.revenue / chartMax) * 100, 2)}%`,
                        }"
                    />
                    <span
                        class="truncate text-[10px] text-muted-foreground sm:text-xs"
                        >{{ monthLabel(month.month) }}</span
                    >
                </div>
            </div>
        </section>

        <section class="rounded-lg border">
            <h2 class="border-b px-4 py-3 font-semibold">
                Pendapatan per kursus
                <span class="text-sm font-normal text-muted-foreground"
                    >({{ periodLabel }})</span
                >
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-muted-foreground">
                            <th class="h-11 px-4 text-left font-medium">
                                Kursus
                            </th>
                            <th class="h-11 px-4 text-left font-medium">
                                Status
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Harga
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Siswa
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Rating
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Terjual
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Penjualan
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Pemasukan platform
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Pendapatan bersih
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="courses.length === 0">
                            <td
                                colspan="9"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Belum punya kursus.
                            </td>
                        </tr>
                        <tr
                            v-for="course in courses"
                            :key="course.id"
                            class="border-b last:border-0 hover:bg-muted/50"
                        >
                            <td class="max-w-72 p-4">
                                <Link
                                    :href="courseRoutes.show(course.slug)"
                                    class="line-clamp-2 font-medium hover:text-primary hover:underline"
                                    >{{ course.title }}</Link
                                >
                            </td>
                            <td class="p-4">
                                <Badge
                                    :variant="statusBadgeVariant(course.status)"
                                    >{{ statusLabel(course.status) }}</Badge
                                >
                            </td>
                            <td
                                class="p-4 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    course.price > 0
                                        ? formatRupiah(course.price)
                                        : 'Gratis'
                                }}
                            </td>
                            <td class="p-4 text-right tabular-nums">
                                {{ course.students_count }}
                            </td>
                            <td
                                class="p-4 text-right whitespace-nowrap tabular-nums"
                            >
                                {{
                                    course.rating !== null
                                        ? course.rating.toFixed(1)
                                        : '-'
                                }}
                            </td>
                            <td class="p-4 text-right tabular-nums">
                                {{ course.sales_count }}
                            </td>
                            <td
                                class="p-4 text-right whitespace-nowrap tabular-nums"
                            >
                                {{ formatRupiah(course.gross) }}
                            </td>
                            <td
                                class="p-4 text-right whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatRupiah(course.commission) }}
                            </td>
                            <td
                                class="p-4 text-right font-semibold whitespace-nowrap text-primary tabular-nums"
                            >
                                {{ formatRupiah(course.revenue) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-lg border">
            <h2 class="border-b px-4 py-3 font-semibold">
                Transaksi terbaru
                <span class="text-sm font-normal text-muted-foreground"
                    >({{ periodLabel }}, maks. 10)</span
                >
            </h2>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr class="text-muted-foreground">
                            <th class="h-11 px-4 text-left font-medium">
                                Tanggal bayar
                            </th>
                            <th class="h-11 px-4 text-left font-medium">
                                Pesanan
                            </th>
                            <th class="h-11 px-4 text-left font-medium">
                                Siswa
                            </th>
                            <th class="h-11 px-4 text-left font-medium">
                                Kursus
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Jumlah
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Pemasukan platform
                            </th>
                            <th class="h-11 px-4 text-right font-medium">
                                Diterima instruktur
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="transactions.length === 0">
                            <td
                                colspan="7"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Belum ada transaksi.
                            </td>
                        </tr>
                        <tr
                            v-for="payment in transactions"
                            :key="payment.id"
                            class="border-b last:border-0 hover:bg-muted/50"
                        >
                            <td class="p-4 whitespace-nowrap">
                                {{ formatDateTime(payment.paid_at) }}
                            </td>
                            <td class="p-4">
                                <Link
                                    :href="
                                        adminOrders.show(payment.order_number)
                                    "
                                    class="font-mono font-medium text-primary underline-offset-4 hover:underline"
                                    >{{ payment.order_number }}</Link
                                >
                            </td>
                            <td class="p-4">
                                <span class="block font-medium">{{
                                    payment.student.name
                                }}</span>
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{ payment.student.email }}</span
                                >
                            </td>
                            <td class="max-w-56 truncate p-4">
                                {{ payment.course_title }}
                            </td>
                            <td
                                class="p-4 text-right font-medium whitespace-nowrap tabular-nums"
                            >
                                {{ formatRupiah(payment.amount) }}
                            </td>
                            <td
                                class="p-4 text-right whitespace-nowrap text-muted-foreground tabular-nums"
                            >
                                {{ formatRupiah(payment.commission_amount) }}
                                <span class="block text-xs">{{
                                    formatRate(payment.commission_rate)
                                }}</span>
                            </td>
                            <td
                                class="p-4 text-right font-semibold whitespace-nowrap text-primary tabular-nums"
                            >
                                {{ formatRupiah(payment.instructor_amount) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
