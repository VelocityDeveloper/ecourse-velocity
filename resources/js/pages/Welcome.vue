<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Check,
    ClipboardCheck,
    Clock,
    Compass,
    GraduationCap,
    Layers,
    Search,
    Sparkles,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import BannerSlider from '@/components/BannerSlider.vue';
import CardCarousel from '@/components/CardCarousel.vue';
import CourseCard from '@/components/CourseCard.vue';
import SectionHeading from '@/components/SectionHeading.vue';
import TestimonialCarousel from '@/components/TestimonialCarousel.vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { dashboard, login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import myCourses from '@/routes/my-courses';
import users from '@/routes/users';
import type {
    CatalogCourse,
    HomeBanner,
    HomeBannerSlide,
    HomeCategory,
    HomeInstructor,
    HomeStats,
    HomeTestimonial,
} from '@/types';

const props = defineProps<{
    stats: HomeStats;
    featuredCourses: CatalogCourse[];
    categories: HomeCategory[];
    instructors: HomeInstructor[];
    banner: HomeBanner;
    banners: HomeBannerSlide[];
    testimonials: HomeTestimonial[];
}>();

const page = usePage();
const canRegister = computed(() => page.props.canRegister);
const isAuthenticated = computed(() => Boolean(page.props.auth.user));
const isStaff = computed(() =>
    ['admin', 'instructor'].includes(page.props.auth.user?.role ?? ''),
);

const search = ref('');

/**
 * Accent chips cycle through the theme's chart tokens so they follow light/dark mode.
 */
const ACCENTS = [
    'bg-chart-1/10 text-chart-1',
    'bg-chart-2/10 text-chart-2',
    'bg-chart-3/10 text-chart-3',
    'bg-chart-4/10 text-chart-4',
    'bg-chart-5/10 text-chart-5',
];

const steps = [
    {
        Icon: Compass,
        title: 'Jelajahi katalog',
        description:
            'Saring kursus berdasarkan kategori dan tingkat untuk menemukan yang paling cocok.',
    },
    {
        Icon: ClipboardCheck,
        title: 'Daftar dengan satu klik',
        description:
            'Ikuti kursus seketika. Kursus langsung muncul di Kursus Saya.',
    },
    {
        Icon: GraduationCap,
        title: 'Belajar sesuai ritme Anda',
        description:
            'Pelajari materi video dan artikel, lalu uji pemahaman Anda dengan kuis.',
    },
];

const statItems = computed(() => [
    { label: 'Siswa', value: props.stats.students },
    { label: 'Kursus', value: props.stats.courses },
    { label: 'Materi', value: props.stats.lessons },
    { label: 'Instruktur', value: props.stats.instructors },
]);

function formatCount(value: number): string {
    return new Intl.NumberFormat('id-ID').format(value);
}

function searchCatalog(): void {
    router.get(catalogRoutes.index().url, {
        search: search.value.trim() || undefined,
    });
}
</script>

<template>
    <div>
        <Head title="Selamat Datang" />

        <!-- Hero: dark band, the admin's banner image as a photo background -->
        <section
            class="relative isolate overflow-hidden bg-surface text-surface-foreground"
        >
            <template v-if="banner.hero_image_url">
                <img
                    :src="banner.hero_image_url"
                    alt=""
                    class="absolute inset-0 -z-20 size-full object-cover"
                />
                <div
                    aria-hidden="true"
                    class="absolute inset-0 -z-10 bg-gradient-to-r from-surface via-surface/85 to-surface/30"
                />
            </template>
            <template v-else>
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -top-40 right-0 -z-10 size-[40rem] rounded-full bg-primary/30 blur-3xl"
                />
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute -bottom-40 -left-32 -z-10 size-96 rounded-full bg-brand/15 blur-3xl"
                />
            </template>
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
            />
            <div
                aria-hidden="true"
                class="absolute inset-x-0 bottom-0 -z-10 h-1 bg-gradient-to-r from-primary via-brand to-primary"
            />

            <div
                class="mx-auto grid w-full max-w-site items-center gap-12 px-4 py-16 sm:px-6 lg:py-24"
                :class="
                    banner.hero_image_url ? '' : 'lg:grid-cols-[1.15fr_1fr]'
                "
            >
                <div
                    class="flex flex-col gap-6"
                    :class="banner.hero_image_url ? 'max-w-3xl' : ''"
                >
                    <p
                        class="flex w-fit items-center gap-2 rounded-full border border-surface-foreground/15 bg-surface-foreground/10 px-3 py-1 font-mono text-xs font-semibold tracking-widest uppercase"
                    >
                        <span class="size-2 rounded-full bg-brand" />
                        {{ banner.hero_badge }}
                    </p>

                    <h1
                        class="text-4xl leading-[1.1] font-extrabold tracking-tight text-balance sm:text-5xl lg:text-6xl"
                    >
                        {{ banner.hero_title }}
                        <span
                            class="bg-gradient-to-r from-brand to-[color-mix(in_oklch,var(--brand)_45%,white)] bg-clip-text text-transparent"
                            >{{ banner.hero_highlight }}</span
                        >
                    </h1>

                    <p
                        class="max-w-xl text-lg text-pretty text-surface-foreground/75"
                    >
                        {{ banner.hero_description }}
                    </p>

                    <form
                        class="flex w-full max-w-xl items-center gap-2 rounded-2xl bg-background p-2 text-foreground shadow-2xl shadow-black/30"
                        role="search"
                        @submit.prevent="searchCatalog"
                    >
                        <Search
                            class="ml-2 h-4 w-4 shrink-0 text-muted-foreground"
                        />
                        <input
                            v-model="search"
                            type="search"
                            aria-label="Cari kursus"
                            placeholder="Apa yang ingin Anda pelajari?"
                            class="h-11 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                        />
                        <Button
                            type="submit"
                            size="lg"
                            class="h-11 rounded-xl px-5 font-bold shadow-md shadow-primary/30"
                        >
                            Cari Kelas
                            <ArrowRight class="ml-1 h-4 w-4" />
                        </Button>
                    </form>

                    <dl class="grid max-w-2xl grid-cols-2 gap-3 sm:grid-cols-4">
                        <div
                            v-for="stat in statItems"
                            :key="stat.label"
                            class="flex flex-col-reverse rounded-xl border border-surface-foreground/15 bg-surface-foreground/5 px-4 py-3 backdrop-blur"
                        >
                            <dt
                                class="text-[11px] font-semibold tracking-wider text-surface-foreground/60 uppercase"
                            >
                                {{ stat.label }}
                            </dt>
                            <dd class="text-2xl font-extrabold tracking-tight">
                                {{ formatCount(stat.value) }}+
                            </dd>
                        </div>
                    </dl>
                </div>

                <!-- Without a banner image: a product preview built from theme tokens -->
                <div
                    v-if="!banner.hero_image_url"
                    aria-hidden="true"
                    class="relative mx-auto hidden w-full max-w-md lg:mx-0 lg:ml-auto lg:block"
                >
                    <div
                        class="rounded-2xl border border-t-4 border-t-primary bg-card p-5 pb-12 text-card-foreground shadow-2xl shadow-black/30"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Layers class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground">
                                    Lanjutkan belajar
                                </p>
                                <p class="truncate font-semibold">
                                    Laravel dari Nol
                                </p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div
                                class="flex justify-between text-xs text-muted-foreground"
                            >
                                <span>Progres</span>
                                <span>62%</span>
                            </div>
                            <div
                                class="mt-1.5 h-2 overflow-hidden rounded-full bg-muted"
                            >
                                <div
                                    class="h-full w-[62%] rounded-full bg-gradient-to-r from-primary to-brand"
                                />
                            </div>
                        </div>

                        <ul class="mt-5 space-y-2 text-sm">
                            <li
                                v-for="(lesson, index) in [
                                    'Routing & controller',
                                    'Relasi Eloquent',
                                    'Validasi dengan form request',
                                ]"
                                :key="lesson"
                                class="flex items-center gap-3 rounded-md border px-3 py-2"
                                :class="index === 2 ? 'bg-muted/50' : ''"
                            >
                                <span
                                    class="flex size-5 shrink-0 items-center justify-center rounded-full"
                                    :class="
                                        index < 2
                                            ? 'bg-primary text-primary-foreground'
                                            : 'border'
                                    "
                                >
                                    <Check v-if="index < 2" class="h-3 w-3" />
                                </span>
                                <span
                                    :class="
                                        index < 2
                                            ? 'text-muted-foreground line-through'
                                            : 'font-medium'
                                    "
                                >
                                    {{ lesson }}
                                </span>
                            </li>
                        </ul>
                    </div>

                    <div
                        class="absolute -bottom-6 -left-8 flex items-center gap-3 rounded-lg border bg-card p-3 text-card-foreground shadow-lg"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-md bg-chart-1/10 text-chart-1"
                        >
                            <Clock class="h-4 w-4" />
                        </span>
                        <div class="text-sm">
                            <p class="font-semibold">Kuis bab</p>
                            <p class="text-xs text-muted-foreground">
                                10 soal · 30 mnt
                            </p>
                        </div>
                    </div>

                    <div
                        class="absolute -top-5 -right-3 flex items-center gap-2 rounded-lg border bg-card px-3 py-2 text-sm text-card-foreground shadow-lg"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-full bg-chart-4/15 text-chart-4"
                        >
                            <Sparkles class="h-3.5 w-3.5" />
                        </span>
                        Terdaftar!
                    </div>
                </div>
            </div>
        </section>

        <!-- Promo banners (Admin → Pengaturan Situs → Banner Promo) -->
        <section
            v-if="banners.length > 0"
            class="border-b bg-muted/40"
            aria-label="Promo"
        >
            <div class="mx-auto w-full max-w-site px-4 py-10 sm:px-6">
                <BannerSlider :slides="banners" />
            </div>
        </section>

        <!-- Featured courses -->
        <section
            id="courses"
            class="mx-auto w-full max-w-site scroll-mt-20 px-4 py-16 sm:px-6 lg:py-20"
        >
            <SectionHeading
                eyebrow="E-Course"
                title="Katalog Kursus Unggulan"
                description="Kursus pilihan yang paling banyak diikuti siswa, lengkap dengan materi terstruktur dan kuis."
            />

            <div
                v-if="featuredCourses.length > 0"
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-4"
            >
                <!-- Phones show the first four; the button below leads to the rest. -->
                <CourseCard
                    v-for="(course, index) in featuredCourses"
                    :key="course.id"
                    :course="course"
                    :class="index >= 4 ? 'hidden sm:flex' : ''"
                />
            </div>
            <div
                v-else
                class="rounded-2xl border border-dashed p-10 text-center"
            >
                <Layers class="mx-auto size-10 text-muted-foreground" />
                <h3 class="mt-3 font-semibold">Belum ada kursus</h3>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kursus yang sudah terbit akan muncul di sini.
                </p>
            </div>

            <div v-if="featuredCourses.length > 0" class="mt-10 text-center">
                <Link :href="catalogRoutes.index()">
                    <Button size="lg" class="rounded-xl font-bold">
                        Lihat semua kursus
                        <ArrowRight class="ml-1 h-4 w-4" />
                    </Button>
                </Link>
            </div>
        </section>

        <!-- Learning paths: one dark card per category -->
        <section
            v-if="categories.length > 0"
            id="categories"
            class="scroll-mt-20 border-y bg-muted/40"
        >
            <div class="mx-auto w-full max-w-site px-4 py-16 sm:px-6 lg:py-20">
                <SectionHeading
                    eyebrow="Jalur Belajar"
                    title="Belajar Sesuai Bidang"
                    description="Pilih bidang yang ingin Anda kuasai, lalu ikuti kursusnya dari dasar hingga mahir."
                />

                <div class="grid gap-4 md:grid-cols-2">
                    <Link
                        v-for="category in categories"
                        :key="category.id"
                        :href="
                            catalogRoutes.index({
                                query: { category_id: category.id },
                            })
                        "
                        class="group relative isolate flex min-h-56 flex-col justify-between gap-6 overflow-hidden rounded-2xl bg-surface p-7 text-surface-foreground shadow-lg shadow-black/10 transition-transform hover:-translate-y-0.5 md:[&:last-child:nth-child(odd)]:col-span-2"
                    >
                        <!-- The admin's category picture, darkened so the text stays readable -->
                        <template v-if="category.image_url">
                            <img
                                :src="category.image_url"
                                alt=""
                                loading="lazy"
                                class="absolute inset-0 -z-20 size-full object-cover transition-transform duration-500 group-hover:scale-[1.03]"
                            />
                            <div
                                aria-hidden="true"
                                class="absolute inset-0 -z-10 bg-gradient-to-r from-surface via-surface/80 to-surface/20"
                            />
                            <div
                                aria-hidden="true"
                                class="absolute inset-0 -z-10 bg-gradient-to-t from-surface/70 to-transparent"
                            />
                        </template>
                        <template v-else>
                            <div
                                aria-hidden="true"
                                class="absolute -top-24 -right-24 -z-10 size-72 rounded-full bg-primary/40 blur-3xl transition-opacity group-hover:opacity-80"
                            />
                            <div
                                aria-hidden="true"
                                class="absolute inset-0 -z-10 [background-image:radial-gradient(currentColor_1px,transparent_1px)] [mask-image:linear-gradient(to_left,black,transparent_70%)] [background-size:18px_18px] opacity-15"
                            />
                            <Layers
                                aria-hidden="true"
                                class="absolute right-6 bottom-6 -z-10 size-28 text-surface-foreground/5"
                            />
                        </template>

                        <p
                            class="w-fit rounded-full border border-surface-foreground/15 bg-surface-foreground/10 px-3 py-1 text-[11px] font-bold tracking-wider uppercase"
                        >
                            {{ category.courses_count }} kursus
                        </p>

                        <div
                            class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between"
                        >
                            <div class="min-w-0 flex-1 space-y-2">
                                <h3
                                    class="text-2xl font-extrabold tracking-tight"
                                >
                                    {{ category.name }}
                                </h3>
                                <p
                                    v-if="category.description"
                                    class="line-clamp-2 text-sm text-surface-foreground/70"
                                >
                                    {{ category.description }}
                                </p>
                            </div>
                            <span
                                class="flex w-fit shrink-0 items-center gap-1 rounded-lg bg-surface-foreground px-3 py-2 text-sm font-bold text-surface"
                            >
                                Mulai Belajar
                                <ArrowRight
                                    class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                                />
                            </span>
                        </div>
                    </Link>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section
            id="how-it-works"
            class="mx-auto w-full max-w-site scroll-mt-20 px-4 py-16 sm:px-6 lg:py-20"
        >
            <SectionHeading
                eyebrow="Cara Kerja"
                title="Dari Penasaran Menjadi Mahir"
                description="Tiga langkah sederhana untuk mulai belajar hari ini."
            />

            <ol class="grid gap-5 md:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="relative flex flex-col gap-4 rounded-2xl border bg-card p-6 text-card-foreground shadow-sm"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="flex size-11 items-center justify-center rounded-xl"
                            :class="ACCENTS[index % ACCENTS.length]"
                        >
                            <component :is="step.Icon" class="h-5 w-5" />
                        </span>
                        <span class="text-4xl font-extrabold text-primary/20">
                            {{ String(index + 1).padStart(2, '0') }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-bold">{{ step.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </div>
                </li>
            </ol>
        </section>

        <!-- Success stories (Admin → Pengaturan Situs → Testimoni, else course reviews) -->
        <section
            v-if="testimonials.length > 0"
            id="testimonials"
            class="scroll-mt-20 border-y bg-muted/40"
        >
            <div class="mx-auto w-full max-w-site px-4 py-16 sm:px-6 lg:py-20">
                <SectionHeading
                    eyebrow="Testimoni"
                    title="Cerita Sukses Alumni"
                    description="Ulasan langsung dari siswa yang telah mengikuti kursus kami."
                />

                <TestimonialCarousel :items="testimonials" />
            </div>
        </section>

        <!-- Instructors -->
        <section
            v-if="instructors.length > 0"
            id="instructors"
            class="mx-auto w-full max-w-site scroll-mt-20 px-4 py-16 sm:px-6 lg:py-20"
        >
            <SectionHeading
                eyebrow="Instruktur"
                title="Belajar dari Praktisi di Bidangnya"
                description="Materi disusun dan diajarkan langsung oleh instruktur yang berpengalaman."
            />

            <CardCarousel
                :items="instructors"
                :per-view-large="4"
                label="Instruktur"
                item-name="Instruktur"
            >
                <template #default="{ item: instructor }">
                    <Link
                        :href="users.show(instructor.id)"
                        class="flex w-full flex-col items-center gap-3 rounded-2xl border bg-card p-6 text-center text-card-foreground shadow-sm transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10"
                    >
                        <Avatar class="size-20 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="instructor.avatar"
                                :src="instructor.avatar"
                                :alt="instructor.name"
                            />
                            <AvatarFallback
                                class="bg-primary/10 text-xl font-semibold text-primary"
                            >
                                {{ getInitials(instructor.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="space-y-1">
                            <h3 class="font-bold">
                                {{ instructor.name }}
                            </h3>
                            <p
                                v-if="instructor.headline"
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ instructor.headline }}
                            </p>
                        </div>
                        <Badge variant="secondary" class="mt-auto">
                            {{ instructor.courses_count }} kursus
                        </Badge>
                    </Link>
                </template>
            </CardCarousel>
        </section>

        <!-- Call to action -->
        <section class="mx-auto w-full max-w-site px-4 pb-16 sm:px-6 lg:pb-20">
            <div
                class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-primary to-primary-deep px-6 py-12 text-center text-primary-foreground shadow-xl shadow-primary/20 sm:px-12"
            >
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_70%)] [background-size:20px_20px] opacity-20"
                />
                <div class="relative mx-auto max-w-2xl space-y-4">
                    <h2
                        class="text-2xl font-extrabold tracking-tight sm:text-3xl"
                    >
                        {{
                            isAuthenticated
                                ? 'Lanjutkan dari terakhir kali Anda belajar'
                                : banner.cta_title
                        }}
                    </h2>
                    <p class="text-primary-foreground/80">
                        {{
                            isStaff
                                ? 'Buka dasbor Anda atau temukan kursus berikutnya di katalog.'
                                : isAuthenticated
                                  ? 'Lanjutkan kursus Anda atau temukan kursus baru di katalog.'
                                  : banner.cta_description
                        }}
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-3 pt-2"
                    >
                        <Link v-if="isStaff" :href="dashboard()">
                            <Button
                                variant="secondary"
                                size="lg"
                                class="rounded-xl font-bold"
                            >
                                Buka dasbor
                            </Button>
                        </Link>
                        <Link
                            v-else-if="isAuthenticated"
                            :href="myCourses.index()"
                        >
                            <Button
                                variant="secondary"
                                size="lg"
                                class="rounded-xl font-bold"
                            >
                                Buka Kursus Saya
                            </Button>
                        </Link>
                        <Link v-else-if="canRegister" :href="register()">
                            <Button
                                variant="secondary"
                                size="lg"
                                class="rounded-xl font-bold"
                            >
                                Buat akun gratis
                            </Button>
                        </Link>
                        <Link v-else :href="login()">
                            <Button
                                variant="secondary"
                                size="lg"
                                class="rounded-xl font-bold"
                            >
                                Masuk
                            </Button>
                        </Link>
                        <Link
                            :href="catalogRoutes.index()"
                            class="text-sm font-semibold underline-offset-4 hover:underline"
                        >
                            Jelajahi katalog
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
