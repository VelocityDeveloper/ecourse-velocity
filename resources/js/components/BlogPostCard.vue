<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Clock, Newspaper } from '@lucide/vue';
import { formatDate } from '@/lib/course';
import blogRoutes from '@/routes/blog';
import type { BlogPostCard } from '@/types';

defineProps<{
    post: BlogPostCard;
}>();
</script>

<template>
    <Link
        :href="blogRoutes.show(post.slug)"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm transition-all hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/10"
    >
        <div class="aspect-video overflow-hidden bg-muted">
            <img
                v-if="post.cover_url"
                :src="post.cover_url"
                :alt="post.title"
                loading="lazy"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            />
            <div
                v-else
                class="flex size-full items-center justify-center bg-surface text-surface-foreground/30"
            >
                <Newspaper class="size-10" aria-hidden="true" />
            </div>
        </div>

        <div class="flex flex-1 flex-col gap-2.5 p-5">
            <p
                class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
            >
                <time :datetime="post.published_at ?? undefined">{{
                    formatDate(post.published_at)
                }}</time>
                <span aria-hidden="true">·</span>
                <span class="flex items-center gap-1">
                    <Clock class="h-3.5 w-3.5" aria-hidden="true" />
                    {{ post.reading_minutes }} menit baca
                </span>
            </p>

            <h3
                class="line-clamp-2 text-lg leading-snug font-bold transition-colors group-hover:text-primary"
            >
                {{ post.title }}
            </h3>

            <p class="line-clamp-3 text-sm text-muted-foreground">
                {{ post.summary }}
            </p>

            <p
                v-if="post.author"
                class="mt-auto pt-2 text-xs font-semibold text-foreground/80"
            >
                {{ post.author.name }}
            </p>
        </div>
    </Link>
</template>
