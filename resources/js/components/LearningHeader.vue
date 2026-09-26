<script setup lang="ts">
import LearningNav from '@/components/LearningNav.vue';

// The dark band that opens every "Belajar Saya" page, with the section tabs at its foot.
defineProps<{
    title: string;
    description: string;
}>();
</script>

<template>
    <section
        class="relative isolate overflow-hidden bg-surface text-surface-foreground"
    >
        <div
            aria-hidden="true"
            class="pointer-events-none absolute -top-40 right-0 -z-10 size-[32rem] rounded-full bg-primary/30 blur-3xl"
        />
        <div
            aria-hidden="true"
            class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
        />

        <div class="mx-auto w-full max-w-site px-4 pt-10 sm:px-6 lg:pt-12">
            <div
                class="flex flex-wrap items-end justify-between gap-x-6 gap-y-4 pb-8"
            >
                <div class="max-w-2xl space-y-3">
                    <p
                        class="w-fit rounded-full border border-surface-foreground/15 bg-surface-foreground/10 px-3 py-1 font-mono text-xs font-semibold tracking-widest uppercase"
                    >
                        Belajar Saya
                    </p>
                    <h1
                        class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-pretty text-surface-foreground/75">
                        {{ description }}
                    </p>
                </div>
                <slot name="actions" />
            </div>

            <LearningNav />
        </div>
    </section>
</template>
