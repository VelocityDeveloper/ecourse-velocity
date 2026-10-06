<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import SecuritySettings from '@/components/site-settings/SecuritySettings.vue';
import SiteSettingsNav from '@/components/site-settings/SiteSettingsNav.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            {
                title: 'Pengaturan Situs',
                href: '/dasbor/pengaturan-situs/identitas',
            },
            { title: 'Keamanan', href: '/dasbor/pengaturan-situs/keamanan' },
        ],
    },
});

defineProps<{
    captcha: { login: boolean; register: boolean };
    loginAttempts: number;
}>();
</script>

<template>
    <Head title="Keamanan · Pengaturan Situs" />

    <div class="flex flex-col space-y-6">
        <SiteSettingsNav />

        <Heading
            variant="small"
            title="Keamanan"
            description="Lindungi form masuk dan daftar dari bot dengan kode keamanan berupa gambar angka."
        />

        <div>
            <SecuritySettings
                :captcha="captcha"
                :login-attempts="loginAttempts"
            />
        </div>
    </div>
</template>
