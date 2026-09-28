<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowRight,
    ChevronLeft,
    ChevronRight,
    SearchX,
    Search,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import CourseCard from '@/components/CourseCard.vue';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { levelLabel } from '@/lib/course';
import { home } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import type { CatalogCourse, CourseLevel, Paginated } from '@/types';

type CatalogSort = 'terbaru' | 'populer' | 'rating' | 'termurah';

const ANY = 'all';

const SORT_LABELS: Record<CatalogSort, string> = {
    terbaru: 'Terbaru',
    populer: 'Terpopuler',
    rating: 'Rating tertinggi',
    termurah: 'Harga termurah',
};

const props = defineProps<{
    courses: Paginated<CatalogCourse>;
    filters: {
        search?: string;
        kategori?: string;
        level?: string;
        sort: CatalogSort;
    };
    categories: {
        id: number;
        name: string;
        slug: string;
        courses_count: number;
    }[];
    levels: CourseLevel[];
    sorts: CatalogSort[];
    total: number;
}>();

const search = ref(props.filters.search ?? '');
// Filtered by category slug, e.g. /kursus?kategori=web-development
const categoryId = ref(props.filters.kategori || ANY);
const level = ref(props.filters.level ?? ANY);
const sort = ref<CatalogSort>(props.filters.sort);

const categoryOptions = computed(() => [
    { id: ANY, name: 'Semua Kategori', courses_count: props.total },
    ...props.categories.map((category) => ({
        ...category,
        id: category.slug,
    })),
]);

const activeCategory = computed(() =>
    props.categories.find(
        (category) => String(category.id) === categoryId.value,
    ),
);

const hasFilters = computed(
    () =>
        Boolean(props.filters.search) ||
        props.filters.kategori !== undefined ||
        props.filters.level !== undefined,
);

// Page numbers around the current one, with gaps shown as "…".
const pageItems = computed<(number | '…')[]>(() => {
    const last = props.courses.last_page;
    const current = props.courses.current_page;
    const sorted = [...new Set([1, last, current - 1, current, current + 1])]
        .filter((page) => page >= 1 && page <= last)
        .sort((a, b) => a - b);
    const items: (number | '…')[] = [];

    sorted.forEach((page, index) => {
        if (index > 0 && page - sorted[index - 1] > 1) {
            items.push('…');
        }

        items.push(page);
    });

    return items;
});

function currentQuery(page?: number) {
    return {
        search: search.value.trim() || undefined,
        kategori: categoryId.value === ANY ? undefined : categoryId.value,
        level: level.value === ANY ? undefined : level.value,
        sort: sort.value === props.sorts[0] ? undefined : sort.value,
        page,
    };
}

function applyFilters() {
    router.get(catalogRoutes.index().url, currentQuery(), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function resetFilters() {
    search.value = '';
    categoryId.value = ANY;
    level.value = ANY;
    applyFilters();
}

function goToPage(page: number) {
    router.get(catalogRoutes.index().url, currentQuery(page), {
        preserveState: true,
        onSuccess: () =>
            document
                .getElementById('catalog-results')
                ?.scrollIntoView({ behavior: 'smooth' }),
    });
}
</script>

<template>
    <div>
        <Head title="Katalog Kursus" />

        <!-- Header band -->
        <section
            class="relative isolate overflow-hidden bg-surface text-surface-foreground"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-40 right-0 -z-10 size-[36rem] rounded-full bg-primary/30 blur-3xl"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
            />

            <div
                class="mx-auto w-full max-w-site px-4 pt-10 pb-28 sm:px-6 lg:pt-14"
            >
                <nav
                    aria-label="Jejak navigasi"
                    class="flex items-center gap-1.5 text-sm text-surface-foreground/60"
                >
                    <Link :href="home()" class="hover:text-surface-foreground"
                        >Beranda</Link
                    >
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="font-semibold text-surface-foreground"
                        >Kursus</span
                    >
                </nav>

                <div class="mt-5 flex flex-wrap items-center gap-3">
                    <h1
                        class="text-4xl font-extrabold tracking-tight sm:text-5xl"
                    >
                        Katalog Kursus
                    </h1>
                    <span
                        class="rounded-full border border-surface-foreground/15 bg-surface-foreground/10 px-3 py-1 text-sm font-semibold"
                    >
                        {{ total }} kursus
                    </span>
                </div>
                <p
                    class="mt-3 max-w-2xl text-lg text-pretty text-surface-foreground/75"
                >
                    Temukan kursus yang sesuai dengan bidang dan tingkat
                    kemampuan Anda, lalu mulai belajar hari ini.
                </p>

                <form
                    class="mt-7 flex w-full max-w-xl items-center gap-2 rounded-2xl bg-background p-2 text-foreground shadow-2xl shadow-black/30"
                    role="search"
                    @submit.prevent="applyFilters"
                >
                    <Search
                        class="ml-2 h-4 w-4 shrink-0 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="search"
                        aria-label="Cari kursus"
                        placeholder="Cari judul kursus..."
                        class="h-11 min-w-0 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
                    />
                    <Button
                        type="submit"
                        size="lg"
                        class="h-11 rounded-xl px-5 font-bold"
                    >
                        Cari
                        <ArrowRight class="ml-1 h-4 w-4" />
                    </Button>
                </form>
            </div>
        </section>

        <div
            id="catalog-results"
            class="relative mx-auto -mt-16 w-full max-w-site scroll-mt-20 px-4 pb-16 sm:px-6 lg:pb-20"
        >
            <!-- Filters: one row of selects -->
            <div
                class="grid gap-3 rounded-2xl border bg-card p-4 text-card-foreground shadow-lg shadow-black/5 sm:grid-cols-3"
                role="group"
                aria-label="Saring kursus"
            >
                <div class="grid gap-1.5">
                    <Label
                        for="filter-category"
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >Kategori</Label
                    >
                    <Select
                        v-model="categoryId"
                        @update:model-value="applyFilters"
                    >
                        <SelectTrigger id="filter-category" class="w-full">
                            <SelectValue placeholder="Semua Kategori" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in categoryOptions"
                                :key="option.id"
                                :value="option.id"
                            >
                                {{ option.name }}
                                <span class="text-muted-foreground"
                                    >({{ option.courses_count }})</span
                                >
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-1.5">
                    <Label
                        for="filter-level"
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >Tingkat</Label
                    >
                    <Select v-model="level" @update:model-value="applyFilters">
                        <SelectTrigger id="filter-level" class="w-full">
                            <SelectValue placeholder="Semua Tingkat" />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem :value="ANY">Semua Tingkat</SelectItem>
                            <SelectItem
                                v-for="option in levels"
                                :key="option"
                                :value="option"
                            >
                                {{ levelLabel(option) }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>

                <div class="grid gap-1.5">
                    <Label
                        for="filter-sort"
                        class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >Urutkan</Label
                    >
                    <Select v-model="sort" @update:model-value="applyFilters">
                        <SelectTrigger id="filter-sort" class="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem
                                v-for="option in sorts"
                                :key="option"
                                :value="option"
                            >
                                {{ SORT_LABELS[option] }}
                            </SelectItem>
                        </SelectContent>
                    </Select>
                </div>
            </div>

            <!-- Result summary -->
            <div
                class="mt-6 mb-5 flex flex-wrap items-center justify-between gap-3 text-sm"
            >
                <p class="text-muted-foreground" aria-live="polite">
                    <template v-if="courses.total > 0">
                        Menampilkan
                        <span class="font-semibold text-foreground"
                            >{{ courses.from }}–{{ courses.to }}</span
                        >
                        dari
                        <span class="font-semibold text-foreground">{{
                            courses.total
                        }}</span>
                        kursus
                        <template v-if="activeCategory">
                            di
                            <span class="font-semibold text-foreground">{{
                                activeCategory.name
                            }}</span>
                        </template>
                        <template v-if="filters.search">
                            untuk “<span
                                class="font-semibold text-foreground"
                                >{{ filters.search }}</span
                            >”
                        </template>
                    </template>
                </p>
                <Button
                    v-if="hasFilters"
                    variant="ghost"
                    size="sm"
                    @click="resetFilters"
                >
                    <X class="mr-1 h-4 w-4" />
                    Hapus filter
                </Button>
            </div>

            <div
                v-if="courses.data.length === 0"
                class="rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <SearchX class="mx-auto size-10 text-muted-foreground" />
                <h2 class="mt-3 text-lg font-bold">Kursus tidak ditemukan</h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Coba kata kunci lain atau hapus beberapa filter.
                </p>
                <Button
                    v-if="hasFilters"
                    class="mt-5 rounded-xl font-bold"
                    @click="resetFilters"
                >
                    Lihat semua kursus
                </Button>
            </div>

            <div
                v-else
                class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
            >
                <CourseCard
                    v-for="course in courses.data"
                    :key="course.id"
                    :course="course"
                />
            </div>

            <!-- Pagination -->
            <nav
                v-if="courses.last_page > 1"
                class="mt-10 flex items-center justify-center gap-1.5"
                aria-label="Halaman katalog"
            >
                <Button
                    variant="outline"
                    size="icon"
                    class="rounded-xl"
                    :disabled="courses.current_page <= 1"
                    aria-label="Halaman sebelumnya"
                    @click="goToPage(courses.current_page - 1)"
                >
                    <ChevronLeft class="h-4 w-4" />
                </Button>
                <template v-for="(item, index) in pageItems" :key="index">
                    <span
                        v-if="item === '…'"
                        class="px-1 text-muted-foreground"
                        aria-hidden="true"
                        >…</span
                    >
                    <Button
                        v-else
                        :variant="
                            item === courses.current_page
                                ? 'default'
                                : 'outline'
                        "
                        size="icon"
                        class="rounded-xl font-bold"
                        :aria-current="
                            item === courses.current_page ? 'page' : undefined
                        "
                        :aria-label="`Halaman ${item}`"
                        @click="goToPage(item)"
                    >
                        {{ item }}
                    </Button>
                </template>
                <Button
                    variant="outline"
                    size="icon"
                    class="rounded-xl"
                    :disabled="courses.current_page >= courses.last_page"
                    aria-label="Halaman berikutnya"
                    @click="goToPage(courses.current_page + 1)"
                >
                    <ChevronRight class="h-4 w-4" />
                </Button>
            </nav>
        </div>
    </div>
</template>
