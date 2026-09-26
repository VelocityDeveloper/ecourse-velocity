<script setup lang="ts">
// Centered section heading for public pages: a pill eyebrow, a bold H2 and a
// short description, the way kursussipil.id introduces each homepage band.
withDefaults(
    defineProps<{
        eyebrow: string;
        title: string;
        description?: string;
        onSurface?: boolean;
    }>(),
    { onSurface: false },
);
</script>

<template>
    <div class="mx-auto mb-10 max-w-2xl space-y-3 text-center">
        <p
            class="mx-auto w-fit rounded-full border px-3 py-1 font-mono text-xs font-semibold tracking-widest uppercase"
            :class="
                onSurface
                    ? 'border-surface-foreground/20 bg-surface-foreground/10 text-surface-foreground'
                    : 'border-primary/20 bg-primary/10 text-primary'
            "
        >
            {{ eyebrow }}
        </p>
        <h2
            class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl"
        >
            {{ title }}
        </h2>
        <p
            v-if="description"
            class="text-pretty"
            :class="
                onSurface
                    ? 'text-surface-foreground/70'
                    : 'text-muted-foreground'
            "
        >
            {{ description }}
        </p>
        <slot />
    </div>
</template>
