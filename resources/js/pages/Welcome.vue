<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    BookOpen,
    Check,
    ClipboardCheck,
    Clock,
    Compass,
    GraduationCap,
    Layers,
    PlayCircle,
    Search,
    Sparkles,
    Users,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseCard from '@/components/CourseCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { getInitials } from '@/composables/useInitials';
import { dashboard, login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import myCourses from '@/routes/my-courses';
import users from '@/routes/users';
import type {
    CatalogCourse,
    HomeCategory,
    HomeInstructor,
    HomeStats,
} from '@/types';

const props = defineProps<{
    stats: HomeStats;
    featuredCourses: CatalogCourse[];
    categories: HomeCategory[];
    instructors: HomeInstructor[];
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
        title: 'Browse the catalog',
        description:
            'Filter published courses by category and level to find the right fit.',
    },
    {
        Icon: ClipboardCheck,
        title: 'Enroll in one click',
        description:
            'Join a course instantly. It appears in My Courses right away.',
    },
    {
        Icon: GraduationCap,
        title: 'Learn at your pace',
        description:
            'Work through video and article lessons, then check yourself with quizzes.',
    },
];

const statItems = computed(() => [
    { label: 'Courses', value: props.stats.courses, Icon: BookOpen },
    { label: 'Lessons', value: props.stats.lessons, Icon: PlayCircle },
    { label: 'Students', value: props.stats.students, Icon: Users },
    {
        label: 'Instructors',
        value: props.stats.instructors,
        Icon: GraduationCap,
    },
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
        <Head title="Welcome" />

        <!-- Hero -->
        <section
            class="relative overflow-hidden border-b bg-gradient-to-b from-primary/5 via-background to-background"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-40 left-1/2 size-[36rem] -translate-x-1/2 rounded-full bg-brand/25 blur-3xl sm:left-2/3"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute top-1/3 -left-32 size-80 rounded-full bg-chart-4/20 blur-3xl"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 [background-image:radial-gradient(var(--border)_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top,black_30%,transparent_75%)] [background-size:24px_24px]"
            />

            <div
                class="relative mx-auto grid w-full max-w-6xl items-center gap-12 px-4 py-16 sm:px-6 lg:grid-cols-2 lg:py-24"
            >
                <div class="flex flex-col gap-6">
                    <Badge
                        variant="outline"
                        class="w-fit gap-1.5 border-primary/30 bg-primary/10 py-1 text-primary"
                    >
                        <Sparkles class="h-3.5 w-3.5" />
                        Learn from practising instructors
                    </Badge>

                    <h1
                        class="text-4xl font-semibold tracking-tight text-balance sm:text-5xl"
                    >
                        Build real skills,
                        <span
                            class="bg-gradient-to-r from-primary to-brand bg-clip-text text-transparent"
                            >one course at a time.</span
                        >
                    </h1>

                    <p
                        class="max-w-xl text-lg text-pretty text-muted-foreground"
                    >
                        Structured video and article lessons, quizzes that check
                        your understanding, and instructors who teach what they
                        practise.
                    </p>

                    <form
                        class="flex w-full max-w-lg flex-col gap-2 sm:flex-row"
                        role="search"
                        @submit.prevent="searchCatalog"
                    >
                        <div class="relative flex-1">
                            <Search
                                class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                            />
                            <Input
                                v-model="search"
                                type="search"
                                aria-label="Search courses"
                                placeholder="What do you want to learn?"
                                class="h-11 pl-9"
                            />
                        </div>
                        <Button
                            type="submit"
                            size="lg"
                            class="h-11 shadow-md shadow-primary/25"
                        >
                            Search
                        </Button>
                    </form>

                    <div class="flex flex-wrap items-center gap-3">
                        <Link :href="catalogRoutes.index()">
                            <Button variant="outline">
                                Browse all courses
                                <ArrowRight class="ml-1 h-4 w-4" />
                            </Button>
                        </Link>
                        <Link
                            v-if="!isAuthenticated && canRegister"
                            :href="register()"
                            class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                        >
                            Create a free account
                        </Link>
                    </div>
                </div>

                <!-- Hero visual: a product preview built from theme tokens -->
                <div
                    aria-hidden="true"
                    class="relative mx-auto w-full max-w-md lg:mx-0 lg:ml-auto"
                >
                    <div
                        class="rounded-xl border border-t-4 border-t-primary bg-card p-5 text-card-foreground shadow-xl shadow-primary/10"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                class="flex size-10 items-center justify-center rounded-lg bg-primary/10 text-primary"
                            >
                                <Layers class="h-5 w-5" />
                            </span>
                            <div class="min-w-0">
                                <p class="text-xs text-muted-foreground">
                                    Continue learning
                                </p>
                                <p class="truncate font-medium">
                                    Laravel from Scratch
                                </p>
                            </div>
                        </div>

                        <div class="mt-5">
                            <div
                                class="flex justify-between text-xs text-muted-foreground"
                            >
                                <span>Progress</span>
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
                                    'Routing & controllers',
                                    'Eloquent relationships',
                                    'Validation with form requests',
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
                        class="absolute -bottom-6 -left-4 flex items-center gap-3 rounded-lg border bg-card p-3 text-card-foreground shadow-sm sm:-left-8"
                    >
                        <span
                            class="flex size-9 items-center justify-center rounded-md bg-chart-1/10 text-chart-1"
                        >
                            <Clock class="h-4 w-4" />
                        </span>
                        <div class="text-sm">
                            <p class="font-medium">Chapter quiz</p>
                            <p class="text-xs text-muted-foreground">
                                10 questions · 30 min
                            </p>
                        </div>
                    </div>

                    <div
                        class="absolute -top-5 -right-3 hidden items-center gap-2 rounded-lg border bg-card px-3 py-2 text-sm text-card-foreground shadow-sm sm:flex"
                    >
                        <span
                            class="flex size-6 items-center justify-center rounded-full bg-chart-4/15 text-chart-4"
                        >
                            <Sparkles class="h-3.5 w-3.5" />
                        </span>
                        Enrolled!
                    </div>
                </div>
            </div>

            <!-- Stats -->
            <div class="relative border-t bg-background/60 backdrop-blur">
                <div
                    class="mx-auto grid w-full max-w-6xl grid-cols-2 gap-6 px-4 py-8 sm:px-6 md:grid-cols-4"
                >
                    <div
                        v-for="item in statItems"
                        :key="item.label"
                        class="flex items-center gap-3"
                    >
                        <span
                            class="flex size-11 items-center justify-center rounded-xl bg-primary/10 text-primary ring-1 ring-primary/15"
                        >
                            <component :is="item.Icon" class="h-5 w-5" />
                        </span>
                        <div>
                            <p class="text-2xl font-semibold tracking-tight">
                                {{ formatCount(item.value) }}
                            </p>
                            <p class="text-sm text-muted-foreground">
                                {{ item.label }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </section>

        <!-- Featured courses -->
        <section
            id="courses"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6 lg:py-20"
        >
            <div class="mb-8 flex flex-wrap items-end justify-between gap-4">
                <div class="max-w-2xl space-y-2">
                    <p class="text-sm font-semibold text-primary">
                        Featured courses
                    </p>
                    <h2
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Popular with our students
                    </h2>
                </div>
                <Link :href="catalogRoutes.index()">
                    <Button variant="ghost">
                        View all
                        <ArrowRight class="ml-1 h-4 w-4" />
                    </Button>
                </Link>
            </div>

            <div
                v-if="featuredCourses.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <CourseCard
                    v-for="course in featuredCourses"
                    :key="course.id"
                    :course="course"
                />
            </div>
            <div
                v-else
                class="flex flex-col items-center gap-2 rounded-lg border border-dashed p-10 text-center"
            >
                <BookOpen class="size-8 text-muted-foreground" />
                <p class="font-medium">New courses are on the way</p>
                <p class="text-sm text-muted-foreground">
                    Published courses will appear here.
                </p>
            </div>
        </section>

        <!-- Categories -->
        <section
            v-if="categories.length > 0"
            id="categories"
            class="scroll-mt-20 border-y bg-muted/30"
        >
            <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
                <div class="mb-8 max-w-2xl space-y-2">
                    <p class="text-sm font-semibold text-primary">Categories</p>
                    <h2
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Explore by topic
                    </h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="(category, index) in categories"
                        :key="category.id"
                        :href="
                            catalogRoutes.index({
                                query: { category_id: category.id },
                            })
                        "
                        class="group flex flex-col gap-3 rounded-lg border bg-card p-5 text-card-foreground transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10"
                    >
                        <span
                            class="flex size-10 items-center justify-center rounded-lg"
                            :class="ACCENTS[index % ACCENTS.length]"
                        >
                            <Layers class="h-5 w-5" />
                        </span>
                        <div class="space-y-1">
                            <h3 class="font-semibold">
                                {{ category.name }}
                            </h3>
                            <p
                                v-if="category.description"
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ category.description }}
                            </p>
                        </div>
                        <span
                            class="mt-auto flex items-center gap-1 text-sm text-muted-foreground group-hover:text-foreground"
                        >
                            {{ category.courses_count }} course(s)
                            <ArrowRight
                                class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                            />
                        </span>
                    </Link>
                </div>
            </div>
        </section>

        <!-- How it works -->
        <section
            id="how-it-works"
            class="mx-auto w-full max-w-6xl scroll-mt-20 px-4 py-16 sm:px-6 lg:py-20"
        >
            <div class="mx-auto mb-10 max-w-2xl space-y-2 text-center">
                <p class="text-sm font-semibold text-primary">How it works</p>
                <h2 class="text-2xl font-semibold tracking-tight sm:text-3xl">
                    From curious to capable in three steps
                </h2>
            </div>

            <ol class="grid gap-4 md:grid-cols-3">
                <li
                    v-for="(step, index) in steps"
                    :key="step.title"
                    class="relative flex flex-col gap-4 rounded-lg border bg-card p-6 text-card-foreground"
                >
                    <div class="flex items-center justify-between">
                        <span
                            class="flex size-10 items-center justify-center rounded-lg"
                            :class="ACCENTS[index % ACCENTS.length]"
                        >
                            <component :is="step.Icon" class="h-5 w-5" />
                        </span>
                        <span class="text-4xl font-semibold text-primary/25">
                            {{ index + 1 }}
                        </span>
                    </div>
                    <div class="space-y-1">
                        <h3 class="font-semibold">{{ step.title }}</h3>
                        <p class="text-sm text-muted-foreground">
                            {{ step.description }}
                        </p>
                    </div>
                </li>
            </ol>
        </section>

        <!-- Instructors -->
        <section
            v-if="instructors.length > 0"
            id="instructors"
            class="scroll-mt-20 border-y bg-muted/30"
        >
            <div class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
                <div class="mb-8 max-w-2xl space-y-2">
                    <p class="text-sm font-semibold text-primary">
                        Instructors
                    </p>
                    <h2
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        Learn from people who do the work
                    </h2>
                </div>

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    <Link
                        v-for="instructor in instructors"
                        :key="instructor.id"
                        :href="users.show(instructor.id)"
                        class="flex flex-col items-center gap-3 rounded-lg border bg-card p-6 text-center text-card-foreground transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10"
                    >
                        <Avatar class="size-20 overflow-hidden rounded-full">
                            <AvatarImage
                                v-if="instructor.avatar"
                                :src="instructor.avatar"
                                :alt="instructor.name"
                            />
                            <AvatarFallback class="text-xl">
                                {{ getInitials(instructor.name) }}
                            </AvatarFallback>
                        </Avatar>
                        <div class="space-y-1">
                            <h3 class="font-semibold">
                                {{ instructor.name }}
                            </h3>
                            <p
                                v-if="instructor.headline"
                                class="line-clamp-2 text-sm text-muted-foreground"
                            >
                                {{ instructor.headline }}
                            </p>
                        </div>
                        <Badge variant="secondary">
                            {{ instructor.courses_count }} course(s)
                        </Badge>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Call to action -->
        <section class="mx-auto w-full max-w-6xl px-4 py-16 sm:px-6 lg:py-20">
            <div
                class="relative overflow-hidden rounded-xl bg-gradient-to-br from-primary to-primary-deep px-6 py-12 text-center text-primary-foreground shadow-xl shadow-primary/20 sm:px-12"
            >
                <div
                    aria-hidden="true"
                    class="pointer-events-none absolute inset-0 [background-image:radial-gradient(currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_center,black_20%,transparent_70%)] [background-size:20px_20px] opacity-20"
                />
                <div class="relative mx-auto max-w-2xl space-y-4">
                    <h2
                        class="text-2xl font-semibold tracking-tight sm:text-3xl"
                    >
                        {{
                            isAuthenticated
                                ? 'Pick up where you left off'
                                : 'Start learning today'
                        }}
                    </h2>
                    <p class="text-primary-foreground/80">
                        {{
                            isStaff
                                ? 'Head to your dashboard or find your next course in the catalog.'
                                : isAuthenticated
                                  ? 'Continue your courses or find a new one in the catalog.'
                                  : 'Create a free account, enroll in a course and take your first lesson in minutes.'
                        }}
                    </p>
                    <div
                        class="flex flex-wrap items-center justify-center gap-3 pt-2"
                    >
                        <Link v-if="isStaff" :href="dashboard()">
                            <Button variant="secondary" size="lg">
                                Go to dashboard
                            </Button>
                        </Link>
                        <Link
                            v-else-if="isAuthenticated"
                            :href="myCourses.index()"
                        >
                            <Button variant="secondary" size="lg">
                                Go to My Courses
                            </Button>
                        </Link>
                        <Link v-else-if="canRegister" :href="register()">
                            <Button variant="secondary" size="lg">
                                Create free account
                            </Button>
                        </Link>
                        <Link v-else :href="login()">
                            <Button variant="secondary" size="lg">
                                Log in
                            </Button>
                        </Link>
                        <Link
                            :href="catalogRoutes.index()"
                            class="text-sm font-medium underline-offset-4 hover:underline"
                        >
                            Browse the catalog
                        </Link>
                    </div>
                </div>
            </div>
        </section>
    </div>
</template>
