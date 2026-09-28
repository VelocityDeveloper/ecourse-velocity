<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import Heading from '@/components/Heading.vue';
import PostForm from '@/components/PostForm.vue';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Blog', href: '/dasbor/blog' },
            { title: 'Tulis Artikel', href: '/dasbor/blog/tulis' },
        ],
    },
});
</script>

<template>
    <Head title="Tulis Artikel" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Tulis Artikel"
            description="Simpan sebagai draf dulu, lalu terbitkan saat siap"
        />

        <PostForm />
    </div>
</template>
