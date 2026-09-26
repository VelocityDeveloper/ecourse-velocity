<script setup lang="ts">
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { HomeBannerSlide } from '@/types';

const props = defineProps<{
    slides: HomeBannerSlide[];
}>();

const AUTOPLAY_MS = 5000;

const current = ref(0);
const paused = ref(false);
let timer: ReturnType<typeof setInterval> | undefined;

const count = computed(() => props.slides.length);

function go(index: number): void {
    current.value = (index + count.value) % count.value;
}

// Swipe on touch screens, where the arrow buttons are hidden.
const SWIPE_MIN_PX = 40;
let touchStartX: number | null = null;

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

function isExternal(url: string): boolean {
    return /^https?:\/\//.test(url);
}

onMounted(() => {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    if (count.value > 1 && !reduceMotion) {
        timer = setInterval(() => {
            if (!paused.value) {
                go(current.value + 1);
            }
        }, AUTOPLAY_MS);
    }
});

onBeforeUnmount(() => clearInterval(timer));
</script>

<template>
    <div
        class="relative"
        role="region"
        aria-roledescription="carousel"
        aria-label="Banner promo"
        @mouseenter="paused = true"
        @mouseleave="paused = false"
        @focusin="paused = true"
        @focusout="paused = false"
    >
        <div
            class="overflow-hidden rounded-2xl shadow-xl shadow-primary/10"
            @touchstart.passive="onTouchStart"
            @touchend.passive="onTouchEnd"
        >
            <div
                class="flex transition-transform duration-500 ease-out motion-reduce:transition-none"
                :style="{ transform: `translateX(-${current * 100}%)` }"
            >
                <div
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    class="w-full shrink-0"
                    role="group"
                    aria-roledescription="slide"
                    :aria-label="`${index + 1} dari ${count}: ${slide.title}`"
                    :aria-hidden="index !== current"
                >
                    <component
                        :is="slide.link_url ? 'a' : 'div'"
                        :href="slide.link_url ?? undefined"
                        :target="
                            slide.link_url && isExternal(slide.link_url)
                                ? '_blank'
                                : undefined
                        "
                        :rel="
                            slide.link_url && isExternal(slide.link_url)
                                ? 'noopener'
                                : undefined
                        "
                        :tabindex="index === current ? undefined : -1"
                        class="block"
                    >
                        <img
                            :src="slide.image_url"
                            :alt="slide.title"
                            class="aspect-[3/1] w-full bg-muted object-cover"
                            :loading="index === 0 ? 'eager' : 'lazy'"
                        />
                    </component>
                </div>
            </div>
        </div>

        <template v-if="count > 1">
            <button
                type="button"
                class="absolute top-1/2 left-3 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-background/80 text-foreground shadow-md backdrop-blur transition-colors hover:bg-background sm:flex"
                aria-label="Banner sebelumnya"
                @click="go(current - 1)"
            >
                <ChevronLeft class="h-5 w-5" />
            </button>
            <button
                type="button"
                class="absolute top-1/2 right-3 hidden size-10 -translate-y-1/2 items-center justify-center rounded-full bg-background/80 text-foreground shadow-md backdrop-blur transition-colors hover:bg-background sm:flex"
                aria-label="Banner berikutnya"
                @click="go(current + 1)"
            >
                <ChevronRight class="h-5 w-5" />
            </button>

            <div class="mt-4 flex justify-center gap-2">
                <button
                    v-for="(slide, index) in slides"
                    :key="slide.id"
                    type="button"
                    class="h-2 rounded-full transition-all"
                    :class="
                        index === current
                            ? 'w-7 bg-primary'
                            : 'w-2 bg-muted-foreground/30 hover:bg-muted-foreground/50'
                    "
                    :aria-label="`Tampilkan banner ${index + 1}`"
                    :aria-current="index === current"
                    @click="go(index)"
                />
            </div>
        </template>
    </div>
</template>
