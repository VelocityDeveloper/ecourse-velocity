<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowRight,
    CalendarDays,
    ChevronRight,
    Clock,
    Eye,
    GraduationCap,
    Link2,
    MessageCircle,
    Pencil,
} from '@lucide/vue';
import { computed } from 'vue';
import { toast } from 'vue-sonner';
import BlogPostCard from '@/components/BlogPostCard.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/course';
import { home } from '@/routes';
import postRoutes from '@/routes/admin/posts';
import blogRoutes from '@/routes/blog';
import catalogRoutes from '@/routes/catalog';
import users from '@/routes/users';
import type { BlogPostCard as Card, BlogPostDetail } from '@/types';

const props = defineProps<{
    post: BlogPostDetail;
    related: Card[];
    can: { edit: boolean };
}>();

const shareUrl = computed(() =>
    typeof window === 'undefined'
        ? ''
        : `${window.location.origin}${blogRoutes.show(props.post.slug).url}`,
);

const shareLinks = computed(() => {
    const url = encodeURIComponent(shareUrl.value);
    const text = encodeURIComponent(props.post.title);

    return [
        {
            label: 'WhatsApp',
            href: `https://wa.me/?text=${text}%20${url}`,
            network: null,
        },
        {
            label: 'Facebook',
            href: `https://www.facebook.com/sharer/sharer.php?u=${url}`,
            network: 'facebook',
        },
        {
            label: 'LinkedIn',
            href: `https://www.linkedin.com/sharing/share-offsite/?url=${url}`,
            network: 'linkedin',
        },
    ];
});

async function copyLink(): Promise<void> {
    try {
        await navigator.clipboard.writeText(shareUrl.value);
        toast.success('Tautan artikel disalin.');
    } catch {
        toast.error('Tautan tidak bisa disalin.');
    }
}
</script>

<template>
    <div>
        <Head :title="post.title">
            <meta
                head-key="description"
                name="description"
                :content="post.summary"
            />
        </Head>

        <div
            v-if="!post.is_live"
            class="bg-amber-100 px-4 py-2 text-center text-sm font-medium text-amber-900"
        >
            <Eye class="mr-1 inline h-4 w-4 align-[-3px]" />
            Pratinjau: artikel ini belum tayang dan hanya terlihat oleh admin.
        </div>

        <article>
            <header
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
                    class="mx-auto w-full max-w-site px-4 pt-10 pb-12 sm:px-6 lg:pt-14 lg:pb-16"
                >
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
                            :href="blogRoutes.index()"
                            class="font-semibold text-surface-foreground hover:underline"
                            >Blog</Link
                        >
                    </nav>

                    <h1
                        class="mt-5 max-w-4xl text-3xl leading-tight font-extrabold tracking-tight text-balance sm:text-4xl lg:text-5xl"
                    >
                        {{ post.title }}
                    </h1>

                    <!-- Only a written excerpt: the fallback summary would repeat the first paragraph. -->
                    <p
                        v-if="post.excerpt"
                        class="mt-4 max-w-3xl text-lg text-pretty text-surface-foreground/75"
                    >
                        {{ post.summary }}
                    </p>

                    <div
                        class="mt-6 flex flex-wrap items-center gap-x-6 gap-y-3 border-t border-surface-foreground/10 pt-5 text-sm text-surface-foreground/70"
                    >
                        <div
                            v-if="post.author"
                            class="flex items-center gap-2.5"
                        >
                            <Avatar class="size-9">
                                <AvatarImage
                                    v-if="post.author.avatar"
                                    :src="post.author.avatar"
                                    :alt="post.author.name"
                                />
                                <AvatarFallback
                                    class="bg-surface-muted text-surface-foreground"
                                >
                                    {{ getInitials(post.author.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <span
                                class="font-semibold text-surface-foreground"
                                >{{ post.author.name }}</span
                            >
                        </div>
                        <span class="flex items-center gap-1.5">
                            <CalendarDays class="h-4 w-4" aria-hidden="true" />
                            <time :datetime="post.published_at ?? undefined">{{
                                formatDate(post.published_at)
                            }}</time>
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Clock class="h-4 w-4" aria-hidden="true" />
                            {{ post.reading_minutes }} menit baca
                        </span>
                        <Button
                            v-if="can.edit"
                            as-child
                            size="sm"
                            variant="secondary"
                            class="sm:ml-auto"
                        >
                            <Link :href="postRoutes.edit(post.slug)">
                                <Pencil class="h-3.5 w-3.5" />
                                Ubah artikel
                            </Link>
                        </Button>
                    </div>
                </div>
            </header>

            <div
                class="mx-auto grid w-full max-w-site gap-10 px-4 py-10 sm:px-6 lg:grid-cols-[minmax(0,1fr)_20rem] lg:gap-12 lg:py-14"
            >
                <div class="min-w-0">
                    <img
                        v-if="post.cover_url"
                        :src="post.cover_url"
                        :alt="post.title"
                        class="mb-10 aspect-video w-full rounded-2xl border object-cover"
                    />

                    <!-- Article HTML is sanitised on save by SanitizeLessonContent. -->
                    <div
                        v-if="post.content"
                        class="prose-editor blog-content max-w-none text-base leading-7 sm:text-lg sm:leading-8"
                        v-html="post.content"
                    />
                    <p v-else class="text-muted-foreground">
                        Artikel ini belum memiliki isi.
                    </p>
                </div>

                <aside class="space-y-5 lg:sticky lg:top-24 lg:self-start">
                    <div
                        v-if="post.author"
                        class="rounded-2xl border bg-card p-5 text-card-foreground"
                    >
                        <p
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Ditulis oleh
                        </p>
                        <div class="mt-3 flex items-center gap-3">
                            <Avatar class="size-12">
                                <AvatarImage
                                    v-if="post.author.avatar"
                                    :src="post.author.avatar"
                                    :alt="post.author.name"
                                />
                                <AvatarFallback
                                    class="bg-primary text-primary-foreground"
                                >
                                    {{ getInitials(post.author.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <Link
                                    v-if="
                                        post.author.is_instructor &&
                                        post.author.slug
                                    "
                                    :href="users.show(post.author.slug)"
                                    class="font-bold hover:text-primary"
                                    >{{ post.author.name }}</Link
                                >
                                <p v-else class="font-bold">
                                    {{ post.author.name }}
                                </p>
                                <p
                                    v-if="post.author.headline"
                                    class="text-sm text-muted-foreground"
                                >
                                    {{ post.author.headline }}
                                </p>
                            </div>
                        </div>
                    </div>

                    <div
                        class="rounded-2xl border bg-card p-5 text-card-foreground"
                    >
                        <p
                            class="text-xs font-bold tracking-wider text-muted-foreground uppercase"
                        >
                            Bagikan artikel
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            <a
                                v-for="link in shareLinks"
                                :key="link.label"
                                :href="link.href"
                                target="_blank"
                                rel="noopener"
                                class="flex size-10 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                                :aria-label="`Bagikan ke ${link.label}`"
                                :title="link.label"
                            >
                                <SocialIcon
                                    v-if="link.network"
                                    :network="link.network"
                                    class="size-4"
                                />
                                <MessageCircle v-else class="size-4" />
                            </a>
                            <button
                                type="button"
                                class="flex size-10 items-center justify-center rounded-lg border text-muted-foreground transition-colors hover:border-primary hover:bg-primary hover:text-primary-foreground"
                                aria-label="Salin tautan"
                                title="Salin tautan"
                                @click="copyLink"
                            >
                                <Link2 class="size-4" />
                            </button>
                        </div>
                    </div>

                    <div
                        class="relative isolate overflow-hidden rounded-2xl bg-surface p-6 text-surface-foreground"
                    >
                        <div
                            aria-hidden="true"
                            class="pointer-events-none absolute -right-16 -bottom-16 -z-10 size-48 rounded-full bg-primary/40 blur-3xl"
                        />
                        <GraduationCap
                            class="size-8 text-brand"
                            aria-hidden="true"
                        />
                        <p class="mt-3 text-lg leading-snug font-extrabold">
                            Siap mempraktikkan ilmunya?
                        </p>
                        <p class="mt-1.5 text-sm text-surface-foreground/70">
                            Belajar terarah lewat kursus bermateri lengkap,
                            kuis, dan sertifikat.
                        </p>
                        <Button
                            as-child
                            class="mt-4 w-full rounded-xl font-bold"
                        >
                            <Link :href="catalogRoutes.index()">
                                Jelajahi kursus
                                <ArrowRight class="h-4 w-4" />
                            </Link>
                        </Button>
                    </div>
                </aside>
            </div>
        </article>

        <section v-if="related.length > 0" class="border-t bg-muted/40">
            <div class="mx-auto w-full max-w-site px-4 py-12 sm:px-6 lg:py-16">
                <div class="mb-6 flex items-end justify-between gap-4">
                    <h2 class="text-2xl font-extrabold tracking-tight">
                        Artikel Lainnya
                    </h2>
                    <Link
                        :href="blogRoutes.index()"
                        class="text-sm font-semibold text-primary hover:underline"
                        >Lihat semua</Link
                    >
                </div>
                <div class="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    <BlogPostCard
                        v-for="item in related"
                        :key="item.id"
                        :post="item"
                    />
                </div>
            </div>
        </section>
    </div>
</template>
