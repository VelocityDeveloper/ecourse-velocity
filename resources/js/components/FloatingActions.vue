<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { ArrowUp } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

const page = usePage();
const site = computed(() => page.props.site);
const layout = computed(() => site.value.footer);
const whatsappUrl = computed(() => site.value.floating_whatsapp_url);
const onLeft = computed(() => layout.value.floating_position === 'left');

// "Back to top" only appears once the visitor has scrolled down a little.
const scrolled = ref(false);

function onScroll(): void {
    scrolled.value = window.scrollY > 400;
}

onMounted(() => {
    onScroll();
    window.addEventListener('scroll', onScroll, { passive: true });
});

onBeforeUnmount(() => window.removeEventListener('scroll', onScroll));

function backToTop(): void {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
}
</script>

<template>
    <div
        v-if="whatsappUrl || layout.back_to_top.show"
        class="fixed bottom-5 z-40 flex items-center gap-3 sm:bottom-6"
        :class="
            onLeft
                ? 'left-4 flex-row-reverse sm:left-6'
                : 'right-4 flex-row sm:right-6'
        "
    >
        <a
            v-if="whatsappUrl"
            :href="whatsappUrl"
            target="_blank"
            rel="noopener"
            class="flex size-14 items-center justify-center rounded-full bg-[#25D366] text-white shadow-lg ring-4 ring-[#25D366]/20 transition hover:scale-105 hover:bg-[#1ebe5b] focus-visible:ring-[#25D366]/50 focus-visible:outline-none"
            aria-label="Chat WhatsApp"
            title="Chat WhatsApp"
        >
            <svg
                viewBox="0 0 24 24"
                fill="currentColor"
                class="size-7"
                aria-hidden="true"
            >
                <path
                    d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"
                />
            </svg>
        </a>

        <!-- Back to top keeps its place in the corner while hidden, so the chat button never shifts. -->
        <button
            v-if="layout.back_to_top.show"
            type="button"
            class="flex size-11 items-center justify-center rounded-full bg-surface text-surface-foreground shadow-lg transition duration-200 hover:bg-primary hover:text-primary-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none motion-reduce:transition-none"
            :class="
                scrolled
                    ? 'translate-y-0 opacity-100'
                    : 'pointer-events-none invisible translate-y-2 opacity-0'
            "
            :aria-label="layout.back_to_top.label"
            :title="layout.back_to_top.label"
            :aria-hidden="!scrolled"
            :tabindex="scrolled ? 0 : -1"
            @click="backToTop"
        >
            <ArrowUp class="size-5" />
        </button>
    </div>
</template>
