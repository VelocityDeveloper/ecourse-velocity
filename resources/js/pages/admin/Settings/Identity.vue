<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import LogoSettings from '@/components/site-settings/LogoSettings.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dashboard' },
            { title: 'Pengaturan Situs', href: '/admin/settings/identitas' },
            { title: 'Identitas & Logo', href: '/admin/settings/identitas' },
        ],
    },
});

defineProps<{
    logoUrl: string | null;
}>();
</script>

<template>
    <Head title="Identitas & Logo · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Identitas & Logo"
            description="Logo yang tampil di header, footer, dasbor, dan halaman masuk"
        />

        <div class="max-w-2xl space-y-8">
            <LogoSettings :logo-url="logoUrl" />
        </div>
    </div>
</template>
