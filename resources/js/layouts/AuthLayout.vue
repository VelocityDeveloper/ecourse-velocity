<script setup lang="ts">
import AuthLayout from '@/layouts/auth/AuthSimpleLayout.vue';
import { setDarkModeAllowed } from '@/composables/useAppearance';

const { title = '', description = '' } = defineProps<{
    title?: string;
    description?: string;
}>();

// The public site is always light.
setDarkModeAllowed(false);
</script>

<template>
    <AuthLayout :title="title" :description="description">
        <slot />
    </AuthLayout>
</template>
