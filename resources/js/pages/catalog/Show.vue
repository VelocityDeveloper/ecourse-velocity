<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    BarChart3,
    BookOpen,
    Bookmark,
    CalendarClock,
    ChevronDown,
    ChevronRight,
    CircleCheck,
    Clock,
    FileText,
    Layers,
    ListChecks,
    Lock,
    MessagesSquare,
    NotebookPen,
    PlayCircle,
    Tag,
    TrendingUp,
    Users,
    Video,
    Receipt,
    ShoppingCart,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import CourseCard from '@/components/CourseCard.vue';
import CourseReviews from '@/components/CourseReviews.vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import { getInitials } from '@/composables/useInitials';
import {
    formatDate,
    formatDuration,
    formatPrice,
    formatTimeLimit,
    levelLabel,
    publicOrderStatusLabel,
} from '@/lib/course';
import { home, login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import courses from '@/routes/courses';
import learn from '@/routes/learn';
import orderRoutes from '@/routes/orders';
import users from '@/routes/users';
import type {
    CatalogCourse,
    CatalogCourseDetail,
    CatalogSection,
    CourseReviewEntry,
    OwnEnrollment,
    OrderStatus,
    RatingSummary,
} from '@/types';

const props = defineProps<{
    course: CatalogCourseDetail;
    sections: CatalogSection[];
    related: CatalogCourse[];
    enrollment: OwnEnrollment | null;
    purchase: {
        paid: boolean;
        open_order: { number: string; status: OrderStatus } | null;
    } | null;
    rating: RatingSummary;
    reviews: CourseReviewEntry[];
    myReview: { id: number; rating: number; comment: string | null } | null;
    can: {
        enroll: boolean;
        cancel: boolean;
        manage: boolean;
        learn: boolean;
        review: boolean;
    };
}>();

const enrolling = ref(false);
const cancelDialogOpen = ref(false);

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const canRegister = computed(() => page.props.canRegister);

const isEnrolled = computed(() => props.enrollment?.status === 'active');
const isFree = computed(() => Number(props.course.price) === 0);
// A paid course is bought first, unless the student already paid for it before.
const mustBuy = computed(() => !isFree.value && !props.purchase?.paid);

const lessonCount = computed(() =>
    props.sections.reduce(
        (count, section) => count + section.lessons.length,
        0,
    ),
);

const quizCount = computed(() =>
    props.sections.reduce(
        (count, section) => count + section.quizzes.length,
        0,
    ),
);

const videoCount = computed(() =>
    props.sections.reduce(
        (count, section) =>
            count +
            section.lessons.filter((lesson) => lesson.content_type === 'video')
                .length,
        0,
    ),
);

// The first paragraph of the description, shown under the title.
const summary = computed(() => {
    const first = (props.course.description ?? '').split(/\n\s*\n/)[0] ?? '';

    return first.length > 220 ? `${first.slice(0, 217).trimEnd()}…` : first;
});

function sectionMinutes(section: CatalogSection): number {
    return section.lessons.reduce(
        (total, lesson) => total + (lesson.duration_minutes ?? 0),
        0,
    );
}

// What every enrolled student gets; each line is a feature the app really has.
const INCLUDES = computed(() => [
    {
        Icon: Video,
        text: `${lessonCount.value} materi (${videoCount.value} video, ${lessonCount.value - videoCount.value} artikel)`,
    },
    {
        Icon: ListChecks,
        text: `${quizCount.value} kuis untuk menguji pemahaman`,
    },
    {
        Icon: MessagesSquare,
        text: 'Diskusi tanya jawab dengan instruktur di setiap materi',
    },
    { Icon: NotebookPen, text: 'Catatan pribadi di setiap materi' },
    { Icon: Bookmark, text: 'Tandai materi penting untuk dibuka lagi' },
    { Icon: TrendingUp, text: 'Progres belajar tersimpan otomatis' },
]);

const facts = computed(() => [
    { Icon: Users, label: 'Siswa', value: `${props.course.students_count}` },
    { Icon: Layers, label: 'Bab', value: `${props.sections.length}` },
    { Icon: BookOpen, label: 'Materi', value: `${lessonCount.value}` },
    { Icon: ListChecks, label: 'Kuis', value: `${quizCount.value}` },
    {
        Icon: Clock,
        label: 'Durasi',
        value: formatDuration(props.course.total_minutes),
    },
    {
        Icon: BarChart3,
        label: 'Tingkat',
        value: levelLabel(props.course.level),
    },
    ...(props.course.category
        ? [{ Icon: Tag, label: 'Kategori', value: props.course.category.name }]
        : []),
    ...(props.course.updated_at
        ? [
              {
                  Icon: CalendarClock,
                  label: 'Diperbarui',
                  value: formatDate(props.course.updated_at),
              },
          ]
        : []),
]);

const TABS = [
    { id: 'deskripsi', label: 'Deskripsi' },
    { id: 'materi', label: 'Materi' },
    { id: 'instruktur', label: 'Instruktur' },
    { id: 'ulasan', label: 'Ulasan' },
];

// Highlight the tab of the section in view.
const activeTab = ref(TABS[0].id);
let observer: IntersectionObserver | undefined;

onMounted(() => {
    observer = new IntersectionObserver(
        (entries) => {
            const visible = entries.find((entry) => entry.isIntersecting);

            if (visible) {
                activeTab.value = visible.target.id;
            }
        },
        { rootMargin: '-140px 0px -60% 0px' },
    );

    TABS.forEach((tab) => {
        const element = document.getElementById(tab.id);

        if (element) {
            observer?.observe(element);
        }
    });
});

onBeforeUnmount(() => observer?.disconnect());

function enroll(): void {
    enrolling.value = true;

    router.post(
        catalogRoutes.enroll(props.course.id).url,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                enrolling.value = false;
            },
        },
    );
}
</script>

<template>
    <div>
        <Head :title="course.title" />

        <!-- Header band -->
        <section
            class="relative isolate overflow-hidden bg-surface text-surface-foreground"
        >
            <img
                v-if="course.thumbnail_url"
                :src="course.thumbnail_url"
                alt=""
                class="absolute inset-0 -z-20 size-full object-cover opacity-20 blur-sm"
            />
            <div
                aria-hidden="true"
                class="absolute inset-0 -z-10 bg-gradient-to-r from-surface via-surface/95 to-surface/70"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
            />

            <div class="mx-auto w-full max-w-site px-4 py-10 sm:px-6 lg:py-14">
                <div class="flex max-w-4xl min-w-0 flex-col gap-5">
                    <nav
                        aria-label="Jejak navigasi"
                        class="flex flex-wrap items-center gap-1.5 text-sm text-surface-foreground/60"
                    >
                        <Link
                            :href="home()"
                            class="hover:text-surface-foreground"
                            >Beranda</Link
                        >
                        <ChevronRight class="h-3.5 w-3.5" />
                        <Link
                            :href="catalogRoutes.index()"
                            class="hover:text-surface-foreground"
                            >Kursus</Link
                        >
                        <template v-if="course.category">
                            <ChevronRight class="h-3.5 w-3.5" />
                            <Link
                                :href="
                                    catalogRoutes.index({
                                        query: {
                                            category_id: course.category.id,
                                        },
                                    })
                                "
                                class="font-semibold text-surface-foreground hover:underline"
                                >{{ course.category.name }}</Link
                            >
                        </template>
                    </nav>

                    <div class="flex flex-wrap items-center gap-2">
                        <span
                            class="rounded-md border border-surface-foreground/15 bg-surface-foreground/10 px-2.5 py-1 text-xs font-bold tracking-wider uppercase"
                            >{{ levelLabel(course.level) }}</span
                        >
                        <span
                            v-if="isFree"
                            class="rounded-md bg-primary px-2.5 py-1 text-xs font-bold tracking-wider text-primary-foreground uppercase"
                            >Gratis</span
                        >
                        <span
                            v-if="isEnrolled"
                            class="flex items-center gap-1 rounded-md bg-chart-2/20 px-2.5 py-1 text-xs font-bold tracking-wider uppercase"
                        >
                            <CircleCheck class="h-3.5 w-3.5" />
                            Terdaftar
                        </span>
                    </div>

                    <h1
                        class="text-3xl leading-tight font-extrabold tracking-tight text-balance sm:text-4xl lg:text-5xl"
                    >
                        {{ course.title }}
                    </h1>

                    <p
                        v-if="summary"
                        class="max-w-3xl text-lg text-pretty text-surface-foreground/75"
                    >
                        {{ summary }}
                    </p>

                    <div
                        class="flex flex-wrap items-center gap-x-8 gap-y-4 border-t border-surface-foreground/10 pt-5"
                    >
                        <Link
                            v-if="course.instructor"
                            :href="users.show(course.instructor.id)"
                            class="flex items-center gap-3"
                        >
                            <Avatar
                                class="size-11 overflow-hidden rounded-full ring-2 ring-surface-foreground/20"
                            >
                                <AvatarImage
                                    v-if="course.instructor.avatar"
                                    :src="course.instructor.avatar"
                                    :alt="course.instructor.name"
                                />
                                <AvatarFallback
                                    class="bg-primary font-semibold text-primary-foreground"
                                >
                                    {{ getInitials(course.instructor.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div>
                                <p
                                    class="text-[11px] font-bold tracking-wider text-surface-foreground/55 uppercase"
                                >
                                    Instruktur
                                </p>
                                <p class="font-bold hover:underline">
                                    {{ course.instructor.name }}
                                </p>
                            </div>
                        </Link>

                        <div>
                            <p
                                class="text-[11px] font-bold tracking-wider text-surface-foreground/55 uppercase"
                            >
                                Rating
                            </p>
                            <a
                                v-if="rating.count > 0"
                                href="#ulasan"
                                class="flex items-center gap-1.5 font-bold"
                            >
                                <StarRating :rating="rating.average" />
                                {{ rating.average?.toFixed(1) }}
                                <span
                                    class="font-normal text-surface-foreground/60"
                                    >({{ rating.count }} ulasan)</span
                                >
                            </a>
                            <p v-else class="font-bold">Belum ada ulasan</p>
                        </div>

                        <ul
                            class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-surface-foreground/80"
                        >
                            <li class="flex items-center gap-1.5">
                                <Users class="h-4 w-4 text-brand" />
                                {{ course.students_count }} siswa
                            </li>
                            <li class="flex items-center gap-1.5">
                                <BookOpen class="h-4 w-4 text-brand" />
                                {{ lessonCount }} materi
                            </li>
                            <li
                                v-if="course.total_minutes > 0"
                                class="flex items-center gap-1.5"
                            >
                                <Clock class="h-4 w-4 text-brand" />
                                {{ formatDuration(course.total_minutes) }}
                            </li>
                        </ul>
                    </div>
                </div>
            </div>
        </section>

        <div
            class="mx-auto grid w-full max-w-site gap-8 px-4 py-10 sm:px-6 lg:grid-cols-[1fr_360px] lg:py-12"
        >
            <!-- Price card: first on phones, a sticky column beside the content on large screens -->
            <aside class="lg:col-start-2 lg:row-start-1">
                <div
                    class="overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-xl shadow-black/5 lg:sticky lg:top-24"
                >
                    <div class="p-3 pb-0">
                        <img
                            v-if="course.thumbnail_url"
                            :src="course.thumbnail_url"
                            :alt="course.title"
                            class="aspect-video w-full rounded-xl object-cover"
                        />
                        <div
                            v-else
                            class="flex aspect-video w-full items-center justify-center rounded-xl bg-muted text-muted-foreground"
                        >
                            <BookOpen class="size-10" />
                        </div>
                    </div>

                    <div class="flex flex-col gap-4 p-5">
                        <div class="flex items-end justify-between gap-2">
                            <span
                                class="text-3xl font-extrabold tracking-tight"
                                :class="isFree ? 'text-primary' : ''"
                            >
                                {{ formatPrice(course.price) }}
                            </span>
                            <span
                                class="rounded-md border px-2 py-0.5 text-xs font-bold tracking-wider text-muted-foreground uppercase"
                                >{{ levelLabel(course.level) }}</span
                            >
                        </div>

                        <template v-if="isEnrolled && enrollment">
                            <div
                                class="flex items-center gap-2 rounded-xl bg-primary/10 p-3 text-sm"
                            >
                                <CircleCheck
                                    class="h-4 w-4 shrink-0 text-primary"
                                />
                                <span>
                                    Anda terdaftar sejak
                                    {{ formatDate(enrollment.enrolled_at) }}.
                                </span>
                            </div>
                            <Link :href="learn.show(course.id)">
                                <Button
                                    size="lg"
                                    class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                                >
                                    <PlayCircle class="mr-2 h-5 w-5" />
                                    Buka kursus
                                </Button>
                            </Link>
                            <Button
                                v-if="can.cancel"
                                variant="ghost"
                                class="text-destructive"
                                @click="cancelDialogOpen = true"
                            >
                                Batalkan pendaftaran
                            </Button>
                        </template>

                        <template v-else>
                            <p
                                v-if="enrollment?.status === 'cancelled'"
                                class="text-xs text-muted-foreground"
                            >
                                Pendaftaran Anda sebelumnya dibatalkan pada
                                {{ formatDate(enrollment.cancelled_at) }}.
                            </p>
                            <template v-if="can.enroll && mustBuy">
                                <template v-if="purchase?.open_order">
                                    <p
                                        class="flex items-center gap-2 rounded-xl bg-primary/10 p-3 text-sm"
                                    >
                                        <Receipt
                                            class="h-4 w-4 shrink-0 text-primary"
                                        />
                                        <span>
                                            Pesanan
                                            <span class="font-mono">{{
                                                purchase.open_order.number
                                            }}</span>
                                            ·
                                            {{
                                                publicOrderStatusLabel(
                                                    purchase.open_order.status,
                                                )
                                            }}
                                        </span>
                                    </p>
                                    <Link
                                        :href="
                                            orderRoutes.show(
                                                purchase.open_order.number,
                                            )
                                        "
                                    >
                                        <Button
                                            size="lg"
                                            class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                                        >
                                            {{
                                                purchase.open_order.status ===
                                                'pending'
                                                    ? 'Lanjutkan pembayaran'
                                                    : 'Lihat status pembayaran'
                                            }}
                                        </Button>
                                    </Link>
                                </template>
                                <Link
                                    v-else
                                    :href="orderRoutes.checkout(course.id)"
                                >
                                    <Button
                                        size="lg"
                                        class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                                    >
                                        <ShoppingCart class="mr-2 h-5 w-5" />
                                        Beli kursus
                                    </Button>
                                </Link>
                                <p
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    Bayar via transfer bank atau QRIS, akses
                                    terbuka setelah pembayaran dikonfirmasi.
                                </p>
                            </template>
                            <Button
                                v-else-if="can.enroll"
                                size="lg"
                                class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                                :disabled="enrolling"
                                @click="enroll"
                            >
                                {{
                                    enrolling
                                        ? 'Mendaftar...'
                                        : enrollment
                                          ? 'Daftar lagi'
                                          : 'Daftar sekarang'
                                }}
                            </Button>
                            <template v-else-if="!isAuthenticated">
                                <Link :href="login()">
                                    <Button
                                        size="lg"
                                        class="h-12 w-full rounded-xl text-base font-bold shadow-lg shadow-primary/25"
                                    >
                                        {{
                                            isFree
                                                ? 'Masuk untuk mendaftar'
                                                : 'Masuk untuk membeli'
                                        }}
                                    </Button>
                                </Link>
                                <p
                                    v-if="canRegister"
                                    class="text-center text-xs text-muted-foreground"
                                >
                                    Baru di sini?
                                    <Link
                                        :href="register()"
                                        class="font-semibold text-foreground underline-offset-4 hover:underline"
                                    >
                                        Buat akun gratis
                                    </Link>
                                </p>
                            </template>
                            <p v-else class="text-xs text-muted-foreground">
                                Hanya siswa yang dapat mendaftar ke kursus yang
                                terbit.
                            </p>
                        </template>

                        <Link
                            v-if="can.manage && !isEnrolled"
                            :href="learn.show(course.id)"
                        >
                            <Button variant="outline" class="w-full rounded-xl">
                                <PlayCircle class="mr-2 h-4 w-4" />
                                Pratinjau sebagai siswa
                            </Button>
                        </Link>
                        <Link
                            v-if="can.manage"
                            :href="courses.show(course.id)"
                            class="text-center text-sm text-muted-foreground underline-offset-4 hover:text-foreground hover:underline"
                        >
                            Kelola kursus ini di dasbor
                        </Link>

                        <dl class="hidden divide-y border-t text-sm lg:block">
                            <div
                                v-for="fact in facts"
                                :key="fact.label"
                                class="flex items-center justify-between gap-3 py-2.5"
                            >
                                <dt
                                    class="flex items-center gap-2 text-muted-foreground"
                                >
                                    <component
                                        :is="fact.Icon"
                                        class="h-4 w-4"
                                    />
                                    {{ fact.label }}
                                </dt>
                                <dd class="text-right font-semibold">
                                    {{ fact.value }}
                                </dd>
                            </div>
                        </dl>
                    </div>
                </div>
            </aside>

            <div class="flex min-w-0 flex-col gap-6 lg:row-start-1">
                <!-- Section tabs -->
                <nav
                    class="sticky top-16 z-20 -mx-4 overflow-x-auto border-b bg-background/95 px-4 backdrop-blur sm:mx-0 sm:rounded-2xl sm:border sm:px-2 sm:shadow-sm"
                    aria-label="Bagian halaman"
                >
                    <ul class="flex gap-1">
                        <li v-for="tab in TABS" :key="tab.id">
                            <a
                                :href="`#${tab.id}`"
                                class="block border-b-2 px-4 py-3.5 text-sm font-bold whitespace-nowrap transition-colors"
                                :class="
                                    activeTab === tab.id
                                        ? 'border-primary text-primary'
                                        : 'border-transparent text-muted-foreground hover:text-foreground'
                                "
                                :aria-current="
                                    activeTab === tab.id ? 'true' : undefined
                                "
                                >{{ tab.label }}</a
                            >
                        </li>
                    </ul>
                </nav>

                <!-- Description -->
                <section
                    id="deskripsi"
                    class="scroll-mt-32 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
                    aria-labelledby="deskripsi-heading"
                >
                    <h2
                        id="deskripsi-heading"
                        class="flex items-center gap-3 text-xl font-bold tracking-tight"
                    >
                        <span
                            class="h-6 w-1 rounded-full bg-primary"
                            aria-hidden="true"
                        />
                        Deskripsi Kursus
                    </h2>
                    <p
                        v-if="course.description"
                        class="mt-4 leading-relaxed whitespace-pre-line text-muted-foreground"
                    >
                        {{ course.description }}
                    </p>

                    <h3 class="mt-7 font-bold">Yang Anda dapatkan</h3>
                    <ul class="mt-3 grid gap-3 sm:grid-cols-2">
                        <li
                            v-for="item in INCLUDES"
                            :key="item.text"
                            class="flex items-start gap-3 rounded-xl border bg-muted/30 p-3 text-sm"
                        >
                            <span
                                class="flex size-8 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <component :is="item.Icon" class="h-4 w-4" />
                            </span>
                            <span class="pt-1.5">{{ item.text }}</span>
                        </li>
                    </ul>
                </section>

                <!-- Curriculum -->
                <section
                    id="materi"
                    class="scroll-mt-32 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
                    aria-labelledby="materi-heading"
                >
                    <div
                        class="flex flex-wrap items-baseline justify-between gap-2"
                    >
                        <h2
                            id="materi-heading"
                            class="flex items-center gap-3 text-xl font-bold tracking-tight"
                        >
                            <span
                                class="h-6 w-1 rounded-full bg-primary"
                                aria-hidden="true"
                            />
                            Materi Kursus
                        </h2>
                        <span class="text-sm text-muted-foreground">
                            {{ sections.length }} bab · {{ lessonCount }} materi
                            · {{ quizCount }} kuis
                        </span>
                    </div>

                    <p
                        v-if="sections.length === 0"
                        class="mt-4 rounded-xl border border-dashed p-6 text-center text-sm text-muted-foreground"
                    >
                        Kurikulum belum diterbitkan.
                    </p>

                    <div v-else class="mt-5 space-y-3">
                        <Collapsible
                            v-for="(section, sectionIndex) in sections"
                            :key="section.id"
                            :default-open="sectionIndex === 0"
                            class="group/section overflow-hidden rounded-xl border"
                        >
                            <CollapsibleTrigger
                                class="flex w-full items-center gap-3 bg-muted/40 px-4 py-3.5 text-left transition-colors hover:bg-muted/70"
                            >
                                <span
                                    class="flex size-7 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-sm font-bold text-primary"
                                    >{{ sectionIndex + 1 }}</span
                                >
                                <span class="min-w-0 flex-1 font-bold">{{
                                    section.title
                                }}</span>
                                <span
                                    class="hidden shrink-0 rounded-md bg-background px-2 py-0.5 text-xs font-semibold text-muted-foreground sm:inline"
                                >
                                    {{
                                        section.lessons.length +
                                        section.quizzes.length
                                    }}
                                    item
                                    <template v-if="sectionMinutes(section) > 0"
                                        >·
                                        {{
                                            formatDuration(
                                                sectionMinutes(section),
                                            )
                                        }}</template
                                    >
                                </span>
                                <ChevronDown
                                    class="h-4 w-4 shrink-0 text-muted-foreground transition-transform group-data-[state=open]/section:rotate-180"
                                />
                            </CollapsibleTrigger>
                            <CollapsibleContent>
                                <ul class="divide-y">
                                    <li
                                        v-for="lesson in section.lessons"
                                        :key="`lesson-${lesson.id}`"
                                        class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                                    >
                                        <span
                                            class="flex min-w-0 items-center gap-3"
                                        >
                                            <PlayCircle
                                                v-if="
                                                    lesson.content_type ===
                                                    'video'
                                                "
                                                class="h-4 w-4 shrink-0 text-primary"
                                            />
                                            <FileText
                                                v-else
                                                class="h-4 w-4 shrink-0 text-primary"
                                            />
                                            <Link
                                                v-if="can.learn"
                                                :href="
                                                    learn.lessons.show({
                                                        course: course.id,
                                                        lesson: lesson.id,
                                                    })
                                                "
                                                class="truncate font-medium hover:underline"
                                            >
                                                {{ lesson.title }}
                                            </Link>
                                            <span
                                                v-else
                                                class="truncate font-medium"
                                                >{{ lesson.title }}</span
                                            >
                                        </span>
                                        <span
                                            class="flex shrink-0 items-center gap-2 text-xs text-muted-foreground"
                                        >
                                            <span
                                                class="rounded-md border px-1.5 py-0.5 tabular-nums"
                                                >{{
                                                    formatDuration(
                                                        lesson.duration_minutes,
                                                    )
                                                }}</span
                                            >
                                            <Lock
                                                v-if="!can.learn"
                                                class="h-3.5 w-3.5"
                                                aria-label="Terkunci"
                                            />
                                        </span>
                                    </li>
                                    <li
                                        v-for="quiz in section.quizzes"
                                        :key="`quiz-${quiz.id}`"
                                        class="flex items-center justify-between gap-3 px-4 py-3 text-sm"
                                    >
                                        <span
                                            class="flex min-w-0 items-center gap-3"
                                        >
                                            <ListChecks
                                                class="h-4 w-4 shrink-0 text-chart-2"
                                            />
                                            <Link
                                                v-if="can.learn"
                                                :href="
                                                    learn.quizzes.show({
                                                        course: course.id,
                                                        quiz: quiz.id,
                                                    })
                                                "
                                                class="truncate font-medium hover:underline"
                                            >
                                                {{ quiz.title }}
                                            </Link>
                                            <span
                                                v-else
                                                class="truncate font-medium"
                                                >{{ quiz.title }}</span
                                            >
                                        </span>
                                        <span
                                            class="flex shrink-0 items-center gap-2 text-xs text-muted-foreground"
                                        >
                                            <span
                                                class="rounded-md border px-1.5 py-0.5"
                                            >
                                                {{ quiz.questions_count }} soal
                                                <template
                                                    v-if="
                                                        quiz.time_limit_minutes !==
                                                        null
                                                    "
                                                    >·
                                                    {{
                                                        formatTimeLimit(
                                                            quiz.time_limit_minutes,
                                                        )
                                                    }}</template
                                                >
                                            </span>
                                            <Lock
                                                v-if="!can.learn"
                                                class="h-3.5 w-3.5"
                                                aria-label="Terkunci"
                                            />
                                        </span>
                                    </li>
                                    <li
                                        v-if="
                                            section.lessons.length === 0 &&
                                            section.quizzes.length === 0
                                        "
                                        class="px-4 py-3 text-sm text-muted-foreground"
                                    >
                                        Belum ada konten.
                                    </li>
                                </ul>
                            </CollapsibleContent>
                        </Collapsible>
                    </div>
                </section>

                <!-- Instructor -->
                <section
                    v-if="course.instructor"
                    id="instruktur"
                    class="scroll-mt-32 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
                    aria-labelledby="instruktur-heading"
                >
                    <h2
                        id="instruktur-heading"
                        class="flex items-center gap-3 text-xl font-bold tracking-tight"
                    >
                        <span
                            class="h-6 w-1 rounded-full bg-primary"
                            aria-hidden="true"
                        />
                        Instruktur
                    </h2>
                    <div class="mt-5 flex flex-col gap-5 sm:flex-row">
                        <Avatar
                            class="size-20 shrink-0 overflow-hidden rounded-2xl"
                        >
                            <AvatarImage
                                v-if="course.instructor.avatar"
                                :src="course.instructor.avatar"
                                :alt="course.instructor.name"
                            />
                            <AvatarFallback
                                class="rounded-2xl bg-primary text-2xl font-semibold text-primary-foreground"
                            >
                                {{ getInitials(course.instructor.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="min-w-0 space-y-2">
                            <Link
                                :href="users.show(course.instructor.id)"
                                class="text-lg font-bold hover:underline"
                                >{{ course.instructor.name }}</Link
                            >
                            <p
                                v-if="course.instructor.headline"
                                class="text-sm font-semibold text-primary"
                            >
                                {{ course.instructor.headline }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ course.instructor.courses_count }} kursus
                                terbit
                            </p>
                            <p
                                v-if="course.instructor.bio"
                                class="text-sm leading-relaxed whitespace-pre-line text-muted-foreground"
                            >
                                {{ course.instructor.bio }}
                            </p>
                            <Link
                                :href="users.show(course.instructor.id)"
                                class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline"
                            >
                                Lihat profil
                                <ChevronRight class="h-4 w-4" />
                            </Link>
                        </div>
                    </div>
                </section>

                <!-- Reviews -->
                <div
                    id="ulasan"
                    class="scroll-mt-32 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm sm:p-8"
                >
                    <CourseReviews
                        :course-id="course.id"
                        :rating="rating"
                        :reviews="reviews"
                        :my-review="myReview"
                        :can-review="can.review"
                    />
                </div>
            </div>
        </div>

        <!-- Related courses -->
        <section
            v-if="related.length > 0"
            class="border-t bg-muted/40"
            aria-labelledby="related-heading"
        >
            <div class="mx-auto w-full max-w-site px-4 py-14 sm:px-6">
                <div
                    class="mb-8 flex flex-wrap items-end justify-between gap-3"
                >
                    <h2
                        id="related-heading"
                        class="text-2xl font-extrabold tracking-tight sm:text-3xl"
                    >
                        Kursus Terkait
                    </h2>
                    <Link
                        :href="catalogRoutes.index()"
                        class="inline-flex items-center gap-1 text-sm font-bold text-primary hover:underline"
                    >
                        Lihat semua kursus
                        <ChevronRight class="h-4 w-4" />
                    </Link>
                </div>
                <div class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4">
                    <CourseCard
                        v-for="item in related"
                        :key="item.id"
                        :course="item"
                    />
                </div>
            </div>
        </section>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="enrollment?.id ?? null"
            :description="`Anda akan kehilangan akses ke ${course.title}. Anda dapat mendaftar lagi nanti selama kursus masih terbit.`"
        />
    </div>
</template>
