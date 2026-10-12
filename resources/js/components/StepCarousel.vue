<script setup lang="ts" generic="T extends { id: number | string }">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { useMediaQuery } from '@vueuse/core';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';

/**
 * Carousel that moves one card per step and stops at both ends (no loop).
 * Autoplay walks forward and waits on the last card until someone steps back.
 */
const props = withDefaults(
    defineProps<{
        items: T[];
        /** Cards per view on large screens (≥1024px); tablets show 2, phones 1. */
        perViewLarge?: number;
        /** What the region is called for screen readers, e.g. "Langkah belajar". */
        label: string;
        /** Singular noun for the arrow buttons, e.g. "Langkah". */
        itemName: string;
        autoplayMs?: number;
        durationMs?: number;
    }>(),
    { perViewLarge: 3, autoplayMs: 4000, durationMs: 900 },
);

defineSlots<{ default(props: { item: T }): unknown }>();

const SWIPE_MIN_PX = 40;
const GAP_REM = 1.25;

const isLarge = useMediaQuery('(min-width: 1024px)');
const isMedium = useMediaQuery('(min-width: 640px)');
const perView = computed(() =>
    Math.min(
        props.items.length,
        isLarge.value ? props.perViewLarge : isMedium.value ? 2 : 1,
    ),
);

// `position` is the index of the first visible card.
const lastPosition = computed(() => props.items.length - perView.value);
const canSlide = computed(() => lastPosition.value > 0);

const position = ref(0);
const paused = ref(false);
const reducedMotion = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;
let touchStartX: number | null = null;

const atStart = computed(() => position.value === 0);
const atEnd = computed(() => position.value >= lastPosition.value);

const cardWidth = computed(
    () =>
        `calc((100% - ${(perView.value - 1) * GAP_REM}rem) / ${perView.value})`,
);

const EASE = 'cubic-bezier(0.22, 1, 0.36, 1)';

const transitionOn = computed(() => !reducedMotion.value);

const trackStyle = computed(() => ({
    gap: `${GAP_REM}rem`,
    transform: `translateX(calc(-${position.value} * (${cardWidth.value} + ${GAP_REM}rem)))`,
    transition: transitionOn.value
        ? `transform ${props.durationMs}ms ${EASE}`
        : 'none',
}));

/** Cards fade and shrink as they leave the view and grow back as they enter. */
function slideStyle(slot: number): Record<string, string> {
    const shown = isVisible(slot);

    return {
        width: cardWidth.value,
        opacity: shown ? '1' : '0',
        transform: shown ? 'none' : 'translateY(1.5rem) scale(0.8)',
        filter: shown ? 'none' : 'blur(3px)',
        transition: transitionOn.value
            ? ['opacity', 'transform', 'filter']
                  .map(
                      (property) => `${property} ${props.durationMs}ms ${EASE}`,
                  )
                  .join(', ')
            : 'none',
    };
}

// A wider screen shows more cards, so the last reachable position moves.
watch(lastPosition, (value) => {
    position.value = Math.max(0, Math.min(position.value, value));
});

function moveTo(index: number): void {
    position.value = Math.max(0, Math.min(index, lastPosition.value));
}

function step(direction: 1 | -1): void {
    moveTo(position.value + direction);
}

function isVisible(slot: number): boolean {
    return slot >= position.value && slot < position.value + perView.value;
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
            step(distance < 0 ? 1 : -1);
        }
    }

    touchStartX = null;
    paused.value = false;
}

onMounted(() => {
    reducedMotion.value = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (reducedMotion.value) {
        return;
    }

    timer = setInterval(() => {
        if (
            !atEnd.value &&
            !paused.value &&
            document.visibilityState === 'visible'
        ) {
            step(1);
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
        <!-- Vertical padding leaves room for card shadows and hover lift. -->
        <div
            class="-my-4 overflow-hidden py-4"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <ul
                class="flex will-change-transform"
                :class="{ 'justify-center': !canSlide }"
                :style="trackStyle"
            >
                <li
                    v-for="(item, slot) in items"
                    :key="item.id"
                    class="flex shrink-0"
                    :style="slideStyle(slot)"
                    role="group"
                    aria-roledescription="slide"
                    :aria-hidden="!isVisible(slot)"
                    :inert="!isVisible(slot) || undefined"
                >
                    <slot :item="item" />
                </li>
            </ul>
        </div>

        <div
            v-if="canSlide"
            class="mt-8 flex items-center justify-center gap-4"
        >
            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border bg-card text-foreground shadow-sm transition-colors hover:border-primary/40 hover:text-primary disabled:pointer-events-none disabled:opacity-40"
                :aria-label="`${itemName} sebelumnya`"
                :disabled="atStart"
                @click="step(-1)"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>
            <div class="flex items-center gap-2">
                <button
                    v-for="index in lastPosition + 1"
                    :key="index"
                    type="button"
                    class="h-2 rounded-full transition-all duration-500"
                    :class="
                        index - 1 === position
                            ? 'w-7 bg-primary'
                            : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/50'
                    "
                    :aria-label="`Tampilkan ${itemName.toLowerCase()} ${index}`"
                    :aria-current="index - 1 === position"
                    @click="moveTo(index - 1)"
                />
            </div>
            <button
                type="button"
                class="flex size-10 items-center justify-center rounded-full border bg-card text-foreground shadow-sm transition-colors hover:border-primary/40 hover:text-primary disabled:pointer-events-none disabled:opacity-40"
                :aria-label="`${itemName} berikutnya`"
                :disabled="atEnd"
                @click="step(1)"
            >
                <ChevronRight class="h-5 w-5" />
            </button>
        </div>
    </div>
</template>
