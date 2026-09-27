<script setup lang="ts" generic="T extends { id: number | string }">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

const props = withDefaults(
    defineProps<{
        items: T[];
        /** Cards per view on large screens (≥1024px); tablets show 2, phones 1. */
        perViewLarge?: number;
        /** What the region is called for screen readers, e.g. "Testimoni alumni". */
        label: string;
        /** Singular noun for the arrow buttons, e.g. "testimoni". */
        itemName: string;
        autoplayMs?: number;
    }>(),
    { perViewLarge: 3, autoplayMs: 6000 },
);

defineSlots<{ default(props: { item: T }): unknown }>();

const SWIPE_MIN_PX = 40;
const GAP_REM = 1.25;

// Whole cards only; a page with fewer cards than fit is centred.
const isLarge = useMediaQuery('(min-width: 1024px)');
const isMedium = useMediaQuery('(min-width: 640px)');
const perView = computed(() =>
    isLarge.value ? props.perViewLarge : isMedium.value ? 2 : 1,
);

const pages = computed(() => {
    const chunks: T[][] = [];

    for (let index = 0; index < props.items.length; index += perView.value) {
        chunks.push(props.items.slice(index, index + perView.value));
    }

    return chunks;
});

const cardWidth = computed(
    () =>
        `calc((100% - ${(perView.value - 1) * GAP_REM}rem) / ${perView.value})`,
);

const current = ref(0);
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;
let touchStartX: number | null = null;

// Changing the layout can leave fewer pages than the one being shown.
watch(pages, (value) => {
    current.value = Math.max(0, Math.min(current.value, value.length - 1));
});

function go(index: number): void {
    current.value = (index + pages.value.length) % pages.value.length;
}

function onTouchStart(event: TouchEvent): void {
    touchStartX = event.touches[0]?.clientX ?? null;
    paused.value = true;
}

function onTouchEnd(event: TouchEvent): void {
    const endX = event.changedTouches[0]?.clientX;

    if (touchStartX !== null && endX !== undefined) {
        const distance = endX - touchStartX;

        if (Math.abs(distance) >= SWIPE_MIN_PX) {
            go(current.value + (distance < 0 ? 1 : -1));
        }
    }

    touchStartX = null;
    paused.value = false;
}

onMounted(() => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }

    timer = setInterval(() => {
        if (!paused.value && pages.value.length > 1) {
            go(current.value + 1);
        }
    }, props.autoplayMs);
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <div
        role="region"
        aria-roledescription="carousel"
        :aria-label="label"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="paused = false"
    >
        <!-- The negative margin leaves room for card shadows; each page pads it back,
             so the next page never peeks in at the edge. -->
        <div
            class="-mx-3 -my-3 overflow-hidden"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <div
                class="flex transition-transform duration-500 ease-out motion-reduce:transition-none"
                :style="{ transform: `translateX(-${current * 100}%)` }"
            >
                <ul
                    v-for="(page, index) in pages"
                    :key="index"
                    class="flex w-full shrink-0 justify-center gap-5 p-3"
                    role="group"
                    aria-roledescription="slide"
                    :aria-label="`${index + 1} dari ${pages.length}`"
                    :aria-hidden="index !== current"
                    :inert="index !== current || undefined"
                >
                    <li
                        v-for="item in page"
                        :key="item.id"
                        class="flex shrink-0"
                        :style="{ width: cardWidth }"
                    >
                        <slot :item="item" />
                    </li>
                </ul>
            </div>
        </div>

        <div
            v-if="pages.length > 1"
            class="mt-8 flex items-center justify-center gap-4"
        >
            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border bg-card text-foreground shadow-sm transition-colors hover:border-primary/40 hover:text-primary"
                :aria-label="`${itemName} sebelumnya`"
                @click="go(current - 1)"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>
            <div class="flex items-center gap-2">
                <button
                    v-for="(_, index) in pages"
                    :key="index"
                    type="button"
                    class="h-2 rounded-full transition-all"
                    :class="
                        index === current
                            ? 'w-7 bg-primary'
                            : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/50'
                    "
                    :aria-label="`Tampilkan ${itemName} halaman ${index + 1}`"
                    :aria-current="index === current"
                    @click="go(index)"
                />
            </div>
            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border bg-card text-foreground shadow-sm transition-colors hover:border-primary/40 hover:text-primary"
                :aria-label="`${itemName} berikutnya`"
                @click="go(current + 1)"
            >
                <ChevronRight class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>
