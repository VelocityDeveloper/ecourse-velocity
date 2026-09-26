<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!--
        Ecourse Velocity mark: a check-shaped "V" (velocity, a finished lesson)
        trailed by three speed lines. Drawn in currentColor so it follows the theme.
    -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 40 40"
        role="img"
        aria-label="Ecourse Velocity"
        :class="className"
        v-bind="$attrs"
    >
        <rect
            x="4"
            y="10.5"
            width="7"
            height="3"
            rx="1.5"
            fill="currentColor"
        />
        <rect
            x="1.5"
            y="17.5"
            width="12"
            height="3"
            rx="1.5"
            fill="currentColor"
        />
        <rect
            x="6"
            y="24.5"
            width="10"
            height="3"
            rx="1.5"
            fill="currentColor"
        />
        <path
            d="M15 9 22.5 31 37 9"
            fill="none"
            stroke="currentColor"
            stroke-width="5"
            stroke-linecap="round"
            stroke-linejoin="round"
        />
    </svg>
</template>
