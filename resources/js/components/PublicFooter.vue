<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import catalogRoutes from '@/routes/catalog';

const page = usePage();
const appName = computed(() => page.props.name);
const year = new Date().getFullYear();

const links = [
    { label: 'Courses', href: catalogRoutes.index().url },
    { label: 'Instructors', href: '/#instructors' },
    { label: 'How it works', href: '/#how-it-works' },
];
</script>

<template>
    <footer class="border-t">
        <div
            class="mx-auto flex w-full max-w-6xl flex-col items-center justify-between gap-4 px-4 py-8 text-sm text-muted-foreground sm:flex-row sm:px-6"
        >
            <Link :href="home()" class="flex items-center gap-2">
                <span
                    class="flex size-6 items-center justify-center rounded-md bg-primary text-primary-foreground"
                >
                    <AppLogoIcon class="size-4" />
                </span>
                <span>© {{ year }} {{ appName }}</span>
            </Link>
            <nav class="flex flex-wrap items-center gap-4" aria-label="Footer">
                <a
                    v-for="link in links"
                    :key="link.label"
                    :href="link.href"
                    class="transition-colors hover:text-foreground"
                >
                    {{ link.label }}
                </a>
            </nav>
        </div>
    </footer>
</template>
