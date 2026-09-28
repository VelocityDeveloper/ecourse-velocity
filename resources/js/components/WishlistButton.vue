<script setup lang="ts">
import { Heart } from '@lucide/vue';
import { computed } from 'vue';
import { useWishlist } from '@/composables/useWishlist';
import { cn } from '@/lib/utils';

const props = withDefaults(
    defineProps<{
        courseId: number;
        courseSlug: string;
        courseTitle: string;
        // "icon": a round heart laid over a thumbnail; "full": a labelled outline button.
        variant?: 'icon' | 'full';
    }>(),
    { variant: 'icon' },
);

const { isWishlisted, isPending, toggle } = useWishlist();

const active = computed(() => isWishlisted(props.courseId));
const label = computed(() =>
    active.value
        ? `Hapus ${props.courseTitle} dari wishlist`
        : `Simpan ${props.courseTitle} ke wishlist`,
);
</script>

<template>
    <button
        type="button"
        :aria-label="label"
        :aria-pressed="active"
        :title="active ? 'Hapus dari wishlist' : 'Simpan ke wishlist'"
        :disabled="isPending(courseId)"
        :class="
            cn(
                'inline-flex items-center justify-center gap-2 transition-colors focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none disabled:opacity-70',
                variant === 'icon'
                    ? 'size-9 rounded-full bg-background/90 text-foreground shadow-sm backdrop-blur hover:bg-background hover:text-wishlist'
                    : 'h-11 w-full rounded-xl border bg-background text-sm font-bold hover:bg-muted',
                active && 'text-wishlist',
            )
        "
        @click.prevent.stop="toggle({ id: courseId, slug: courseSlug })"
    >
        <Heart
            class="size-4.5"
            :class="active ? 'fill-current' : ''"
            aria-hidden="true"
        />
        <template v-if="variant === 'full'">
            {{ active ? 'Tersimpan di wishlist' : 'Simpan ke wishlist' }}
        </template>
    </button>
</template>
