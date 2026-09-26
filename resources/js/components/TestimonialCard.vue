<script setup lang="ts">
import { BadgeCheck, Quote } from '@lucide/vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import type { HomeTestimonial } from '@/types';

defineProps<{
    item: HomeTestimonial;
}>();
</script>

<template>
    <figure
        class="flex h-full flex-col rounded-2xl border bg-card p-6 text-card-foreground shadow-sm transition-shadow hover:shadow-md"
    >
        <div class="flex items-center justify-between gap-3">
            <StarRating :rating="item.rating" />
            <Quote
                aria-hidden="true"
                class="size-7 shrink-0 fill-current text-primary/15"
            />
        </div>

        <!-- Four lines at most, so every card in a row has the same height. -->
        <blockquote
            class="mt-4 line-clamp-4 min-h-24 text-[15px] leading-6 text-foreground/80"
            :title="item.quote"
        >
            “{{ item.quote }}”
        </blockquote>

        <figcaption class="mt-5 flex items-center gap-3 border-t pt-4">
            <Avatar class="size-10 shrink-0 overflow-hidden rounded-full">
                <AvatarImage
                    v-if="item.avatar"
                    :src="item.avatar"
                    :alt="item.name"
                />
                <AvatarFallback
                    class="bg-primary text-sm font-semibold text-primary-foreground"
                >
                    {{ getInitials(item.name.replace(/\*/g, '')) }}
                </AvatarFallback>
            </Avatar>
            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-1 font-bold">
                    <span class="truncate">{{ item.name }}</span>
                    <BadgeCheck
                        class="h-4 w-4 shrink-0 text-chart-2"
                        aria-label="Alumni terverifikasi"
                    />
                </p>
                <p
                    class="truncate text-[11px] font-bold tracking-wider text-muted-foreground uppercase"
                    :title="item.subtitle ?? undefined"
                >
                    {{ item.subtitle ?? 'Alumni' }}
                </p>
            </div>
        </figcaption>
    </figure>
</template>
