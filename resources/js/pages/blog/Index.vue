<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { ArrowRight, ChevronRight, Newspaper, Search } from '@lucide/vue';
import { ref } from 'vue';
import BlogPostCard from '@/components/BlogPostCard.vue';
import { Button } from '@/components/ui/button';
import { home } from '@/routes';
import blogRoutes from '@/routes/blog';
import type { BlogPostCard as Card, Paginated } from '@/types';

const props = defineProps<{
    posts: Paginated<Card>;
    filters: { search?: string };
}>();

const search = ref(props.filters.search ?? '');

function visit(page?: number): void {
    router.get(
        blogRoutes.index().url,
        { search: search.value || undefined, page },
        { preserveState: true, preserveScroll: page === undefined },
    );
}
</script>

<template>
    <div>
        <Head title="Blog">
            <meta
                head-key="description"
                name="description"
                content="Artikel, tips belajar, dan kabar terbaru seputar dunia teknologi."
            />
        </Head>

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
                class="mx-auto w-full max-w-site px-4 pt-10 pb-14 sm:px-6 lg:pt-14 lg:pb-16"
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
                        >Blog</span
                    >
                </nav>

                <h1
                    class="mt-5 text-4xl font-extrabold tracking-tight sm:text-5xl"
                >
                    Blog
                </h1>
                <p
                    class="mt-3 max-w-2xl text-lg text-pretty text-surface-foreground/75"
                >
                    Tips belajar, panduan karier, dan kabar terbaru seputar
                    dunia teknologi.
                </p>

                <form
                    class="mt-7 flex w-full max-w-xl items-center gap-2 rounded-2xl bg-background p-2 text-foreground shadow-2xl shadow-black/30"
                    role="search"
                    @submit.prevent="visit()"
                >
                    <Search
                        class="ml-2 h-4 w-4 shrink-0 text-muted-foreground"
                    />
                    <input
                        v-model="search"
                        type="search"
                        aria-label="Cari artikel"
                        placeholder="Cari artikel..."
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

        <div class="mx-auto w-full max-w-site px-4 py-12 sm:px-6 lg:py-16">
            <p v-if="filters.search" class="mb-6 text-sm text-muted-foreground">
                {{ posts.total }} artikel untuk "<span
                    class="font-semibold text-foreground"
                    >{{ filters.search }}</span
                >"
            </p>

            <div
                v-if="posts.data.length === 0"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed py-16 text-center text-muted-foreground"
            >
                <Newspaper class="size-10" aria-hidden="true" />
                <p>
                    {{
                        filters.search
                            ? 'Artikel tidak ditemukan.'
                            : 'Belum ada artikel.'
                    }}
                </p>
            </div>

            <div v-else class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                <BlogPostCard
                    v-for="post in posts.data"
                    :key="post.id"
                    :post="post"
                />
            </div>

            <div
                v-if="posts.last_page > 1"
                class="mt-10 flex items-center justify-center gap-3"
            >
                <Button
                    variant="outline"
                    :disabled="posts.current_page <= 1"
                    @click="visit(posts.current_page - 1)"
                >
                    Sebelumnya
                </Button>
                <span class="text-sm text-muted-foreground">
                    Halaman {{ posts.current_page }} dari
                    {{ posts.last_page }}
                </span>
                <Button
                    variant="outline"
                    :disabled="posts.current_page >= posts.last_page"
                    @click="visit(posts.current_page + 1)"
                >
                    Berikutnya
                </Button>
            </div>
        </div>
    </div>
</template>
