<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookCheck,
    BookOpen,
    ClipboardCheck,
    GraduationCap,
    HandCoins,
    Landmark,
    MessageCircleQuestion,
    Newspaper,
    Percent,
    Plus,
    Receipt,
    UserPlus,
    UserRoundCheck,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import ActionTile from '@/components/dashboard/ActionTile.vue';
import KpiTile from '@/components/dashboard/KpiTile.vue';
import PanelCard from '@/components/dashboard/PanelCard.vue';
import PaymentList from '@/components/dashboard/PaymentList.vue';
import PeriodFilter from '@/components/dashboard/PeriodFilter.vue';
import QuestionList from '@/components/dashboard/QuestionList.vue';
import TrendChart from '@/components/dashboard/TrendChart.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { formatRupiah } from '@/lib/course';
import type {
    Comparison,
    CourseRef,
    DashboardPeriod,
    Person,
    RecentPayment,
    SeriesPoint,
    UnansweredQuestion,
} from '@/lib/dashboard';
import { change, greeting, timeAgo } from '@/lib/dashboard';
import { dashboard } from '@/routes';
import financeRoutes from '@/routes/admin/finance';
import financeInstructors from '@/routes/admin/finance/instructors';
import instructorApplications from '@/routes/admin/instructor-applications';
import adminOrders from '@/routes/admin/orders';
import posts from '@/routes/admin/posts';
import withdrawals from '@/routes/admin/withdrawals';
import courses from '@/routes/courses';
import enrollments from '@/routes/enrollments';
import type { RatingSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dasbor', href: dashboard() }],
    },
});

const props = defineProps<{
    period: DashboardPeriod;
    kpis: {
        revenue: Comparison;
        commission: Comparison;
        enrollments: Comparison;
        new_users: Comparison;
    };
    actions: {
        awaiting_orders: number;
        pending_withdrawals: number;
        pending_withdrawals_amount: number;
        pending_applications: number;
        pending_courses: number;
        unanswered_questions: number;
    };
    series: SeriesPoint[];
    finance: {
        gross: number;
        commission: number;
        instructor_share: number;
        paid_out: number;
        pending_payout: number;
        owed: number;
    };
    orders: { placed: number; paid: number; open: number; lost: number };
    learning: {
        lessons_completed: Comparison;
        quizzes_submitted: Comparison;
        certificates: Comparison;
        active_learners: number;
    };
    platform: {
        students: number;
        instructors: number;
        courses: Record<string, number>;
        active_enrollments: number;
        rating: RatingSummary;
    };
    topCourses: {
        slug: string;
        title: string;
        thumbnail_url: string | null;
        instructor: string | null;
        revenue: number;
        new_enrollments: number;
        rating: number | null;
    }[];
    topInstructors: {
        id: number;
        slug: string;
        name: string;
        avatar: string | null;
        courses: number;
        sales: number;
        gross: number;
        net: number;
    }[];
    recentPayments: RecentPayment[];
    recentEnrollments: {
        id: number;
        status: string;
        enrolled_at: string;
        is_self_enrolled: boolean;
        student: Person;
        course: CourseRef;
    }[];
    questions: UnansweredQuestion[];
}>();

const page = usePage();
const name = computed(() => page.props.auth.user.name.split(' ')[0]);
const periodText = computed(() => `${props.period.label} terakhir`);

const revenuePoints = computed(() =>
    props.series.map((point) => ({
        bucket: point.bucket,
        value: point.revenue,
    })),
);
const enrollmentPoints = computed(() =>
    props.series.map((point) => ({
        bucket: point.bucket,
        value: point.enrollments,
    })),
);

// Orders placed this period, split by what became of them.
const outcomes = computed(() => {
    const total = Math.max(props.orders.placed, 1);

    return [
        {
            key: 'paid',
            label: 'Lunas',
            value: props.orders.paid,
            class: 'bg-primary',
        },
        {
            key: 'open',
            label: 'Belum dibayar',
            value: props.orders.open,
            class: 'bg-primary/40',
        },
        {
            key: 'lost',
            label: 'Batal / kedaluwarsa',
            value: props.orders.lost,
            class: 'bg-muted-foreground/30',
        },
    ].map((outcome) => ({
        ...outcome,
        percent: (outcome.value / total) * 100,
    }));
});
const paidRate = computed(() =>
    props.orders.placed === 0
        ? null
        : Math.round((props.orders.paid / props.orders.placed) * 100),
);

const payoutShare = computed(() =>
    props.finance.instructor_share === 0
        ? 0
        : (props.finance.paid_out / props.finance.instructor_share) * 100,
);

const learningTiles = computed(() => [
    {
        label: 'Materi diselesaikan',
        icon: BookCheck,
        value: props.learning.lessons_completed,
    },
    {
        label: 'Kuis dikumpulkan',
        icon: ClipboardCheck,
        value: props.learning.quizzes_submitted,
    },
    {
        label: 'Sertifikat terbit',
        icon: GraduationCap,
        value: props.learning.certificates,
    },
]);

const courseCounts = computed(() => [
    { label: 'Terbit', value: props.platform.courses.published ?? 0 },
    { label: 'Ditinjau', value: props.platform.courses.pending ?? 0 },
    { label: 'Draf', value: props.platform.courses.draft ?? 0 },
    { label: 'Diarsipkan', value: props.platform.courses.archived ?? 0 },
]);

function trendText(value: Comparison): string {
    const percent = change(value);

    if (percent === null) {
        return value.current > 0 ? 'baru di periode ini' : 'belum ada';
    }

    return `${percent > 0 ? '+' : ''}${percent}% dari sebelumnya`;
}
</script>

<template>
    <Head title="Dasbor" />

    <div class="flex flex-col gap-6">
        <div class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="text-2xl font-bold tracking-tight">
                    {{ greeting() }}, {{ name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    Ringkasan seluruh platform, {{ periodText }}.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <PeriodFilter :period="period" />
                <Link :href="posts.create()">
                    <Button variant="outline" size="sm">
                        <Newspaper class="mr-2 size-4" />
                        Tulis Artikel
                    </Button>
                </Link>
                <Link :href="courses.create()">
                    <Button size="sm">
                        <Plus class="mr-2 size-4" />
                        Buat Kursus
                    </Button>
                </Link>
            </div>
        </div>

        <section aria-labelledby="kpi-heading">
            <h2 id="kpi-heading" class="sr-only">Angka utama</h2>
            <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
                <KpiTile
                    label="Pendapatan kotor"
                    :value="formatRupiah(kpis.revenue.current)"
                    :icon="Wallet"
                    :comparison="kpis.revenue"
                />
                <KpiTile
                    label="Pendapatan platform"
                    :value="formatRupiah(kpis.commission.current)"
                    :icon="Percent"
                    :comparison="kpis.commission"
                />
                <KpiTile
                    label="Pendaftar baru"
                    :value="kpis.enrollments.current.toLocaleString('id-ID')"
                    :icon="UserPlus"
                    :comparison="kpis.enrollments"
                />
                <KpiTile
                    label="Pengguna baru"
                    :value="kpis.new_users.current.toLocaleString('id-ID')"
                    :icon="Users"
                    :comparison="kpis.new_users"
                />
            </div>
        </section>

        <section aria-labelledby="actions-heading" class="space-y-3">
            <h2
                id="actions-heading"
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Perlu tindakan
            </h2>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
                <ActionTile
                    label="Pembayaran menunggu konfirmasi"
                    :count="actions.awaiting_orders"
                    :icon="Receipt"
                    :href="
                        adminOrders.index({
                            query: { status: 'awaiting_confirmation' },
                        }).url
                    "
                />
                <ActionTile
                    label="Penarikan dana diajukan"
                    :count="actions.pending_withdrawals"
                    :icon="HandCoins"
                    :detail="formatRupiah(actions.pending_withdrawals_amount)"
                    :href="
                        withdrawals.index({ query: { status: 'pending' } }).url
                    "
                />
                <ActionTile
                    label="Pengajuan instruktur"
                    :count="actions.pending_applications"
                    :icon="UserRoundCheck"
                    :href="instructorApplications.index().url"
                />
                <ActionTile
                    label="Kursus menunggu tinjauan"
                    :count="actions.pending_courses"
                    :icon="BookOpen"
                    :href="courses.index({ query: { status: 'pending' } }).url"
                />
                <ActionTile
                    label="Pertanyaan belum dijawab"
                    :count="actions.unanswered_questions"
                    :icon="MessageCircleQuestion"
                    href="#pertanyaan"
                />
            </div>
        </section>

        <div class="grid items-start gap-4 lg:grid-cols-3">
            <TrendChart
                class="lg:col-span-2"
                :title="`Pendapatan kotor · ${periodText}`"
                :points="revenuePoints"
                kind="area"
                money
                :monthly="period.monthly"
            />

            <PanelCard
                title="Keuangan platform"
                description="Sejak awal, semua kursus"
                :href="financeRoutes.index().url"
                link-label="Keuangan"
            >
                <dl class="space-y-3 p-4 text-sm sm:p-5">
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">Pendapatan kotor</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ formatRupiah(finance.gross) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">
                            Pendapatan platform
                        </dt>
                        <dd class="font-semibold tabular-nums">
                            {{ formatRupiah(finance.commission) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3 border-t pt-3">
                        <dt class="text-muted-foreground">Hak instruktur</dt>
                        <dd class="font-semibold tabular-nums">
                            {{ formatRupiah(finance.instructor_share) }}
                        </dd>
                    </div>
                    <div>
                        <div
                            class="h-2 overflow-hidden rounded-full bg-primary/15"
                            role="meter"
                            aria-label="Hak instruktur yang sudah ditransfer"
                            :aria-valuenow="Math.round(payoutShare)"
                            aria-valuemin="0"
                            aria-valuemax="100"
                        >
                            <div
                                class="h-full rounded-full bg-primary"
                                :style="{ width: `${payoutShare}%` }"
                            />
                        </div>
                        <p class="mt-1.5 text-xs text-muted-foreground">
                            {{ Math.round(payoutShare) }}% sudah ditransfer ke
                            instruktur
                        </p>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">Sudah ditransfer</dt>
                        <dd class="tabular-nums">
                            {{ formatRupiah(finance.paid_out) }}
                        </dd>
                    </div>
                    <div class="flex justify-between gap-3">
                        <dt class="text-muted-foreground">Sedang diproses</dt>
                        <dd class="tabular-nums">
                            {{ formatRupiah(finance.pending_payout) }}
                        </dd>
                    </div>
                    <div
                        class="flex justify-between gap-3 rounded-lg bg-muted/60 px-3 py-2"
                    >
                        <dt class="font-medium">
                            Saldo instruktur belum ditarik
                        </dt>
                        <dd class="font-bold tabular-nums">
                            {{ formatRupiah(finance.owed) }}
                        </dd>
                    </div>
                </dl>
            </PanelCard>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-3">
            <TrendChart
                class="lg:col-span-2"
                :title="`Pendaftar baru · ${periodText}`"
                :points="enrollmentPoints"
                kind="bar"
                :monthly="period.monthly"
            />

            <PanelCard
                title="Pesanan"
                :description="`Dibuat ${periodText}`"
                :href="adminOrders.index().url"
                link-label="Pesanan"
            >
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="flex items-end justify-between gap-3">
                        <div>
                            <p class="text-3xl font-bold tabular-nums">
                                {{ orders.placed }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                pesanan dibuat
                            </p>
                        </div>
                        <div class="text-right">
                            <p class="text-xl font-bold tabular-nums">
                                {{ paidRate === null ? '–' : `${paidRate}%` }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                dibayar lunas
                            </p>
                        </div>
                    </div>
                    <div
                        class="flex h-3 gap-0.5 overflow-hidden rounded-full bg-muted"
                        aria-hidden="true"
                    >
                        <div
                            v-for="outcome in outcomes"
                            v-show="outcome.value > 0"
                            :key="outcome.key"
                            :class="outcome.class"
                            :style="{ width: `${outcome.percent}%` }"
                        />
                    </div>
                    <ul class="space-y-2 text-sm">
                        <li
                            v-for="outcome in outcomes"
                            :key="outcome.key"
                            class="flex items-center gap-2"
                        >
                            <span
                                class="size-2.5 shrink-0 rounded-sm"
                                :class="outcome.class"
                            />
                            <span class="text-muted-foreground">{{
                                outcome.label
                            }}</span>
                            <span class="ml-auto font-semibold tabular-nums">{{
                                outcome.value
                            }}</span>
                        </li>
                    </ul>
                </div>
            </PanelCard>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-3">
            <PanelCard
                class="lg:col-span-2"
                title="Keaktifan belajar"
                :description="`Di semua kursus, ${periodText}`"
            >
                <div class="grid grid-cols-2 gap-px bg-border xl:grid-cols-4">
                    <div
                        v-for="tile in learningTiles"
                        :key="tile.label"
                        class="bg-card p-4 sm:p-5"
                    >
                        <component
                            :is="tile.icon"
                            class="size-4 text-primary"
                        />
                        <p class="mt-2 text-2xl font-bold tabular-nums">
                            {{ tile.value.current.toLocaleString('id-ID') }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            {{ tile.label }}
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            {{ trendText(tile.value) }}
                        </p>
                    </div>
                    <div class="bg-card p-4 sm:p-5">
                        <Users class="size-4 text-primary" />
                        <p class="mt-2 text-2xl font-bold tabular-nums">
                            {{
                                learning.active_learners.toLocaleString('id-ID')
                            }}
                        </p>
                        <p class="text-sm text-muted-foreground">
                            Siswa aktif belajar
                        </p>
                        <p class="mt-1 text-xs text-muted-foreground">
                            membuka materi di periode ini
                        </p>
                    </div>
                </div>
            </PanelCard>

            <PanelCard title="Ringkasan platform" description="Saat ini">
                <dl class="grid grid-cols-2 gap-x-4 gap-y-3 p-4 text-sm sm:p-5">
                    <div>
                        <dt class="text-xs text-muted-foreground">Siswa</dt>
                        <dd class="text-lg font-bold tabular-nums">
                            {{ platform.students.toLocaleString('id-ID') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Instruktur
                        </dt>
                        <dd class="text-lg font-bold tabular-nums">
                            {{ platform.instructors.toLocaleString('id-ID') }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">
                            Pendaftaran aktif
                        </dt>
                        <dd class="text-lg font-bold tabular-nums">
                            {{
                                platform.active_enrollments.toLocaleString(
                                    'id-ID',
                                )
                            }}
                        </dd>
                    </div>
                    <div>
                        <dt class="text-xs text-muted-foreground">Rating</dt>
                        <dd class="text-lg font-bold tabular-nums">
                            {{ platform.rating.average ?? '–' }}
                            <span
                                class="text-xs font-normal text-muted-foreground"
                                >({{ platform.rating.count }} ulasan)</span
                            >
                        </dd>
                    </div>
                    <div class="col-span-2 border-t pt-3">
                        <dt class="mb-2 text-xs text-muted-foreground">
                            Kursus
                        </dt>
                        <dd class="grid grid-cols-4 gap-2 text-center">
                            <Link
                                v-for="count in courseCounts"
                                :key="count.label"
                                :href="courses.index().url"
                                class="rounded-lg bg-muted/60 px-1 py-2 hover:bg-muted"
                            >
                                <span class="block font-bold tabular-nums">{{
                                    count.value
                                }}</span>
                                <span
                                    class="block text-[11px] text-muted-foreground"
                                    >{{ count.label }}</span
                                >
                            </Link>
                        </dd>
                    </div>
                </dl>
            </PanelCard>
        </div>

        <div class="grid items-start gap-4 xl:grid-cols-2">
            <PanelCard
                title="Kursus terlaris"
                :description="`Pendapatan ${periodText}`"
                :href="courses.index().url"
                link-label="Semua kursus"
            >
                <p
                    v-if="topCourses.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada kursus.
                </p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs text-muted-foreground">
                            <tr class="border-b">
                                <th class="px-4 py-2 font-medium sm:px-5">
                                    Kursus
                                </th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Siswa baru
                                </th>
                                <th
                                    class="px-4 py-2 text-right font-medium sm:px-5"
                                >
                                    Pendapatan
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr v-for="course in topCourses" :key="course.slug">
                                <td class="px-4 py-2.5 sm:px-5">
                                    <Link
                                        :href="courses.show(course.slug)"
                                        class="flex items-center gap-3 hover:text-primary"
                                    >
                                        <img
                                            v-if="course.thumbnail_url"
                                            :src="course.thumbnail_url"
                                            alt=""
                                            class="hidden aspect-video w-14 shrink-0 rounded object-cover sm:block"
                                        />
                                        <span class="min-w-0">
                                            <span
                                                class="line-clamp-1 font-medium"
                                                >{{ course.title }}</span
                                            >
                                            <span
                                                class="block truncate text-xs text-muted-foreground"
                                                >{{ course.instructor ?? '—' }}
                                                <template
                                                    v-if="
                                                        course.rating !== null
                                                    "
                                                    >· ★
                                                    {{
                                                        course.rating
                                                    }}</template
                                                ></span
                                            >
                                        </span>
                                    </Link>
                                </td>
                                <td class="px-2 py-2.5 text-right tabular-nums">
                                    {{ course.new_enrollments }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-semibold whitespace-nowrap tabular-nums sm:px-5"
                                >
                                    {{ formatRupiah(course.revenue) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </PanelCard>

            <PanelCard
                title="Instruktur teratas"
                :description="`Penjualan ${periodText}`"
                :href="financeInstructors.index().url"
                link-label="Keuangan instruktur"
            >
                <p
                    v-if="topInstructors.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada penjualan di periode ini.
                </p>
                <div v-else class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-left text-xs text-muted-foreground">
                            <tr class="border-b">
                                <th class="px-4 py-2 font-medium sm:px-5">
                                    Instruktur
                                </th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Terjual
                                </th>
                                <th class="px-2 py-2 text-right font-medium">
                                    Kotor
                                </th>
                                <th
                                    class="px-4 py-2 text-right font-medium sm:px-5"
                                >
                                    Hak instruktur
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y">
                            <tr
                                v-for="instructor in topInstructors"
                                :key="instructor.id"
                            >
                                <td class="px-4 py-2.5 sm:px-5">
                                    <Link
                                        :href="
                                            financeInstructors.show(
                                                instructor.slug,
                                            )
                                        "
                                        class="flex items-center gap-3 hover:text-primary"
                                    >
                                        <Avatar class="size-8">
                                            <AvatarImage
                                                v-if="instructor.avatar"
                                                :src="instructor.avatar"
                                                :alt="instructor.name"
                                            />
                                            <AvatarFallback class="text-xs">{{
                                                getInitials(instructor.name)
                                            }}</AvatarFallback>
                                        </Avatar>
                                        <span class="min-w-0">
                                            <span
                                                class="block truncate font-medium"
                                                >{{ instructor.name }}</span
                                            >
                                            <span
                                                class="block text-xs text-muted-foreground"
                                                >{{ instructor.courses }} kursus
                                                terbit</span
                                            >
                                        </span>
                                    </Link>
                                </td>
                                <td class="px-2 py-2.5 text-right tabular-nums">
                                    {{ instructor.sales }}
                                </td>
                                <td
                                    class="px-2 py-2.5 text-right whitespace-nowrap tabular-nums"
                                >
                                    {{ formatRupiah(instructor.gross) }}
                                </td>
                                <td
                                    class="px-4 py-2.5 text-right font-semibold whitespace-nowrap tabular-nums sm:px-5"
                                >
                                    {{ formatRupiah(instructor.net) }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </PanelCard>
        </div>

        <div class="grid items-start gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <PaymentList
                title="Pembayaran terbaru"
                description="Pesanan yang sudah dikonfirmasi"
                :payments="recentPayments"
                :href="financeRoutes.index().url"
            />

            <PanelCard
                title="Pendaftar terbaru"
                :href="enrollments.index().url"
                link-label="Siswa"
            >
                <p
                    v-if="recentEnrollments.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada pendaftar.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="enrollment in recentEnrollments"
                        :key="enrollment.id"
                    >
                        <Link
                            :href="enrollments.show(enrollment.id)"
                            class="flex items-center gap-3 px-4 py-3 hover:bg-muted/50 sm:px-5"
                        >
                            <Avatar class="size-8">
                                <AvatarImage
                                    v-if="enrollment.student.avatar"
                                    :src="enrollment.student.avatar"
                                    :alt="enrollment.student.name"
                                />
                                <AvatarFallback class="text-xs">{{
                                    getInitials(enrollment.student.name)
                                }}</AvatarFallback>
                            </Avatar>
                            <span class="min-w-0 flex-1">
                                <span
                                    class="block truncate text-sm font-medium"
                                    >{{ enrollment.student.name }}</span
                                >
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                    >{{ enrollment.course.title }}</span
                                >
                            </span>
                            <span
                                class="shrink-0 text-xs text-muted-foreground"
                                >{{ timeAgo(enrollment.enrolled_at) }}</span
                            >
                        </Link>
                    </li>
                </ul>
            </PanelCard>

            <QuestionList
                id="pertanyaan"
                class="scroll-mt-4 lg:col-span-2 xl:col-span-1"
                :questions="questions"
                :total="actions.unanswered_questions"
            />
        </div>
    </div>
</template>
