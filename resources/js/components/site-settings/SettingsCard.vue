<script setup lang="ts">
import type { Component } from 'vue';
import { useId } from 'vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Label } from '@/components/ui/label';

defineProps<{
    icon: Component;
    title: string;
    description?: string;
}>();

/** When bound, the header gets a "Tampilkan" toggle and the body hides while off. */
const show = defineModel<boolean | undefined>('show', { default: undefined });

const id = useId();
</script>

<template>
    <section
        class="overflow-hidden rounded-xl border bg-card"
        :aria-labelledby="`${id}-title`"
    >
        <header
            class="flex items-start gap-3 p-4 sm:px-5"
            :class="{ 'border-b': show !== false }"
        >
            <span
                class="flex size-9 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
            >
                <component :is="icon" class="size-4.5" />
            </span>
            <div class="min-w-0 flex-1">
                <h2 :id="`${id}-title`" class="font-semibold">
                    {{ title }}
                </h2>
                <p v-if="description" class="text-sm text-muted-foreground">
                    {{ description }}
                </p>
            </div>
            <!-- Badges and the toggle stay together on the right, whatever the description length. -->
            <div
                v-if="$slots.aside || show !== undefined"
                class="flex shrink-0 flex-col items-end gap-2 sm:flex-row sm:items-center"
            >
                <slot name="aside" />
                <Label
                    v-if="show !== undefined"
                    class="flex cursor-pointer items-center gap-2 rounded-full border px-3 py-1 text-sm font-medium"
                >
                    <Checkbox v-model="show" />
                    Tampilkan
                </Label>
            </div>
        </header>
        <div v-if="show !== false" class="flex flex-col gap-4 p-4 sm:p-5">
            <slot />
        </div>
    </section>
</template>
