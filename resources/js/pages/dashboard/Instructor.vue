<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookCheck,
    ClipboardCheck,
    FilePen,
    GraduationCap,
    Hourglass,
    MessageCircleQuestion,
    Plus,
    Receipt,
    Star,
    UserPlus,
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
import StarRating from '@/components/StarRating.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { formatRupiah, statusBadgeVariant, statusLabel } from '@/lib/course';
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
import adminOrders from '@/routes/admin/orders';
import withdrawals from '@/routes/admin/withdrawals';
import courseRoutes from '@/routes/courses';
import type { CourseStatus, RatingSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [{ title: 'Dasbor', href: dashboard() }],
    },
});

const props = defineProps<{
    period: DashboardPeriod;
    kpis: {
        revenue: Comparison;
        enrollments: Comparison;
        students: number;
        rating: RatingSummary;
    };
    balance: {
        earned: number;
        paid_out: number;
        pending: number;
        available: number;
    };
    actions: {
        unanswered_questions: number;
        pending_courses: number;
        draft_courses: number;
        awaiting_orders: number;
    };
    series: SeriesPoint[];
    learning: {
        lessons_completed: Comparison;
        quizzes_submitted: Comparison;
        certificates: Comparison;
        active_learners: number;
    };
    courses: {
        slug: string;
        title: string;
        status: CourseStatus;
        thumbnail_url: string | null;
        students: number;
        new_enrollments: number;
        average_progress: number | null;
        certificates: number;
        rating: number | null;
        reviews: number;
        revenue: number;
    }[];
    questions: UnansweredQuestion[];
    recentReviews: {
        id: number;
        rating: number;
        comment: string | null;
        created_at: string | null;
        author: Person;
        course: CourseRef;
    }[];
    recentPayments: RecentPayment[];
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

const learningRows = computed(() => [
    {
        label: 'Materi diselesaikan siswa',
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

function trendText(value: Comparison): string {
    const percent = change(value);

    if (percent === null) {
        return value.current > 0 ? 'baru' : '';
    }

    return `${percent > 0 ? '+' : ''}${percent}%`;
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
                    Kursus yang Anda ajar, {{ periodText }}.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <PeriodFilter :period="period" />
                <Link :href="courseRoutes.create()">
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
                    label="Pendapatan bersih"
                    :value="formatRupiah(kpis.revenue.current)"
                    :icon="Wallet"
                    :comparison="kpis.revenue"
                />
                <KpiTile
                    label="Pendaftar baru"
                    :value="kpis.enrollments.current.toLocaleString('id-ID')"
                    :icon="UserPlus"
                    :comparison="kpis.enrollments"
                />
                <KpiTile
                    label="Siswa aktif"
                    :value="kpis.students.toLocaleString('id-ID')"
                    :icon="Users"
                    hint="Di semua kursus Anda"
                />
                <KpiTile
                    label="Rating rata-rata"
                    :value="
                        kpis.rating.average === null
                            ? '–'
                            : `${kpis.rating.average}`
                    "
                    :icon="Star"
                    :hint="`${kpis.rating.count} ulasan`"
                />
            </div>
        </section>

        <section aria-labelledby="actions-heading" class="space-y-3">
            <h2
                id="actions-heading"
                class="text-xs font-semibold tracking-wider text-muted-foreground uppercase"
            >
                Perlu perhatian
            </h2>
            <div class="grid grid-cols-2 gap-3 xl:grid-cols-4">
                <ActionTile
                    label="Pertanyaan belum dijawab"
                    :count="actions.unanswered_questions"
                    :icon="MessageCircleQuestion"
                    href="#pertanyaan"
                />
                <ActionTile
                    label="Kursus sedang ditinjau"
                    :count="actions.pending_courses"
                    :icon="Hourglass"
                    :href="
                        courseRoutes.index({ query: { status: 'pending' } }).url
                    "
                />
                <ActionTile
                    label="Kursus masih draf"
                    :count="actions.draft_courses"
                    :icon="FilePen"
                    :href="
                        courseRoutes.index({ query: { status: 'draft' } }).url
                    "
                />
                <ActionTile
                    label="Pembayaran siswa menunggu admin"
                    :count="actions.awaiting_orders"
                    :icon="Receipt"
                    :href="
                        adminOrders.index({
                            query: { status: 'awaiting_confirmation' },
                        }).url
                    "
                />
            </div>
        </section>

        <div class="grid items-start gap-4 lg:grid-cols-3">
            <TrendChart
                class="lg:col-span-2"
                :title="`Pendapatan bersih · ${periodText}`"
                :points="revenuePoints"
                kind="area"
                money
                :monthly="period.monthly"
            />

            <PanelCard
                title="Saldo Anda"
                description="Setelah komisi platform"
                :href="withdrawals.index().url"
                link-label="Penarikan"
            >
                <div class="space-y-4 p-4 sm:p-5">
                    <div class="rounded-lg bg-primary/10 p-4">
                        <p class="text-xs font-medium text-primary">
                            Bisa ditarik
                        </p>
                        <p class="text-2xl font-bold tabular-nums">
                            {{ formatRupiah(balance.available) }}
                        </p>
                    </div>
                    <dl class="space-y-2.5 text-sm">
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">
                                Total diperoleh
                            </dt>
                            <dd class="font-semibold tabular-nums">
                                {{ formatRupiah(balance.earned) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">Sudah ditarik</dt>
                            <dd class="tabular-nums">
                                {{ formatRupiah(balance.paid_out) }}
                            </dd>
                        </div>
                        <div class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">
                                Sedang diproses
                            </dt>
                            <dd class="tabular-nums">
                                {{ formatRupiah(balance.pending) }}
                            </dd>
                        </div>
                    </dl>
                    <Link :href="withdrawals.index()" class="block">
                        <Button
                            variant="outline"
                            class="w-full"
                            :disabled="balance.available <= 0"
                        >
                            Tarik dana
                        </Button>
                    </Link>
                </div>
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
                title="Keaktifan belajar"
                :description="`Siswa Anda, ${periodText}`"
            >
                <ul class="divide-y">
                    <li
                        v-for="row in learningRows"
                        :key="row.label"
                        class="flex items-center gap-3 px-4 py-3 sm:px-5"
                    >
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <component :is="row.icon" class="size-4" />
                        </span>
                        <span
                            class="min-w-0 flex-1 text-sm text-muted-foreground"
                            >{{ row.label }}</span
                        >
                        <span class="text-right">
                            <span
                                class="block text-lg font-bold tabular-nums"
                                >{{
                                    row.value.current.toLocaleString('id-ID')
                                }}</span
                            >
                            <span
                                v-if="trendText(row.value)"
                                class="block text-[11px] text-muted-foreground"
                                >{{ trendText(row.value) }}</span
                            >
                        </span>
                    </li>
                    <li class="flex items-center gap-3 px-4 py-3 sm:px-5">
                        <span
                            class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                        >
                            <Users class="size-4" />
                        </span>
                        <span
                            class="min-w-0 flex-1 text-sm text-muted-foreground"
                            >Siswa yang belajar</span
                        >
                        <span class="text-lg font-bold tabular-nums">{{
                            learning.active_learners.toLocaleString('id-ID')
                        }}</span>
                    </li>
                </ul>
            </PanelCard>
        </div>

        <PanelCard
            title="Performa kursus"
            :description="`Siswa aktif, progres rata-rata, dan pendapatan ${periodText}`"
            :href="courseRoutes.index().url"
            link-label="Kelola kursus"
        >
            <p
                v-if="courses.length === 0"
                class="px-4 py-10 text-center text-sm text-muted-foreground"
            >
                Anda belum punya kursus.
            </p>
            <div v-else class="overflow-x-auto">
                <table class="w-full min-w-[720px] text-sm">
                    <thead class="text-left text-xs text-muted-foreground">
                        <tr class="border-b">
                            <th class="px-4 py-2 font-medium sm:px-5">
                                Kursus
                            </th>
                            <th class="px-2 py-2 text-right font-medium">
                                Siswa aktif
                            </th>
                            <th class="px-2 py-2 text-right font-medium">
                                Baru
                            </th>
                            <th class="w-40 px-2 py-2 font-medium">
                                Progres rata-rata
                            </th>
                            <th class="px-2 py-2 text-right font-medium">
                                Sertifikat
                            </th>
                            <th class="px-2 py-2 font-medium">Rating</th>
                            <th
                                class="px-4 py-2 text-right font-medium sm:px-5"
                            >
                                Pendapatan
                            </th>
                        </tr>
                    </thead>
                    <tbody class="divide-y">
                        <tr v-for="course in courses" :key="course.slug">
                            <td class="px-4 py-2.5 sm:px-5">
                                <Link
                                    :href="courseRoutes.show(course.slug)"
                                    class="flex items-center gap-3 hover:text-primary"
                                >
                                    <img
                                        v-if="course.thumbnail_url"
                                        :src="course.thumbnail_url"
                                        alt=""
                                        class="aspect-video w-14 shrink-0 rounded object-cover"
                                    />
                                    <span class="min-w-0">
                                        <span
                                            class="line-clamp-1 font-medium"
                                            >{{ course.title }}</span
                                        >
                                        <Badge
                                            :variant="
                                                statusBadgeVariant(
                                                    course.status,
                                                )
                                            "
                                            class="mt-0.5"
                                            >{{
                                                statusLabel(course.status)
                                            }}</Badge
                                        >
                                    </span>
                                </Link>
                            </td>
                            <td
                                class="px-2 py-2.5 text-right font-semibold tabular-nums"
                            >
                                {{ course.students }}
                            </td>
                            <td class="px-2 py-2.5 text-right tabular-nums">
                                {{ course.new_enrollments }}
                            </td>
                            <td class="px-2 py-2.5">
                                <template
                                    v-if="course.average_progress !== null"
                                >
                                    <div class="flex items-center gap-2">
                                        <div
                                            class="h-1.5 flex-1 overflow-hidden rounded-full bg-primary/15"
                                        >
                                            <div
                                                class="h-full rounded-full bg-primary"
                                                :style="{
                                                    width: `${course.average_progress}%`,
                                                }"
                                            />
                                        </div>
                                        <span
                                            class="w-9 text-right text-xs tabular-nums"
                                            >{{
                                                course.average_progress
                                            }}%</span
                                        >
                                    </div>
                                </template>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >–</span
                                >
                            </td>
                            <td class="px-2 py-2.5 text-right tabular-nums">
                                {{ course.certificates }}
                            </td>
                            <td class="px-2 py-2.5 whitespace-nowrap">
                                <span v-if="course.rating !== null"
                                    >★ {{ course.rating }}
                                    <span class="text-xs text-muted-foreground"
                                        >({{ course.reviews }})</span
                                    ></span
                                >
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                    >–</span
                                >
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

        <div class="grid items-start gap-4 lg:grid-cols-2 xl:grid-cols-3">
            <QuestionList
                id="pertanyaan"
                class="scroll-mt-4"
                :questions="questions"
                :total="actions.unanswered_questions"
            />

            <PanelCard title="Ulasan terbaru">
                <p
                    v-if="recentReviews.length === 0"
                    class="px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    Belum ada ulasan.
                </p>
                <ul v-else class="divide-y">
                    <li
                        v-for="review in recentReviews"
                        :key="review.id"
                        class="space-y-1 px-4 py-3 sm:px-5"
                    >
                        <div class="flex items-center justify-between gap-2">
                            <span class="truncate text-sm font-medium">{{
                                review.author.name
                            }}</span>
                            <StarRating :rating="review.rating" size="sm" />
                        </div>
                        <p
                            v-if="review.comment"
                            class="line-clamp-2 text-sm text-muted-foreground"
                        >
                            {{ review.comment }}
                        </p>
                        <p class="truncate text-xs text-muted-foreground">
                            {{ review.course.title }} ·
                            {{ timeAgo(review.created_at) }}
                        </p>
                    </li>
                </ul>
            </PanelCard>

            <PaymentList
                class="lg:col-span-2 xl:col-span-1"
                title="Penjualan terbaru"
                description="Bagian Anda setelah komisi"
                :payments="recentPayments"
            />
        </div>
    </div>
</template>
