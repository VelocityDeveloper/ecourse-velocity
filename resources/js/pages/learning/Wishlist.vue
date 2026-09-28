<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Heart } from '@lucide/vue';
import CourseCard from '@/components/CourseCard.vue';
import LearningHeader from '@/components/LearningHeader.vue';
import { Button } from '@/components/ui/button';
import catalogRoutes from '@/routes/catalog';
import type { WishlistCourse } from '@/types';

defineProps<{
    courses: WishlistCourse[];
}>();
</script>

<template>
    <div>
        <Head title="Wishlist" />

        <LearningHeader
            title="Wishlist"
            description="Kursus yang Anda simpan untuk diikuti nanti."
        ></LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-6 px-4 py-10 sm:px-6"
        >
            <div
                v-if="courses.length === 0"
                class="flex flex-col items-center gap-2 rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <Heart class="size-8 text-muted-foreground" />
                <p class="font-medium">Wishlist Anda masih kosong</p>
                <p class="text-sm text-muted-foreground">
                    Tekan ikon hati pada kursus untuk menyimpannya di sini.
                </p>
                <Link :href="catalogRoutes.index()" class="mt-2">
                    <Button class="rounded-xl font-bold"
                        >Jelajahi katalog</Button
                    >
                </Link>
            </div>

            <template v-else>
                <p class="text-sm text-muted-foreground">
                    {{ courses.length }} kursus tersimpan. Kursus otomatis
                    keluar dari wishlist setelah Anda terdaftar.
                </p>
                <div
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4"
                >
                    <CourseCard
                        v-for="course in courses"
                        :key="course.id"
                        :course="course"
                    />
                </div>
            </template>
        </div>
    </div>
</template>
