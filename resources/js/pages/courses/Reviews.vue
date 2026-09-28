<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import CourseManageLayout from '@/components/CourseManageLayout.vue';
import Heading from '@/components/Heading.vue';
import ReviewsPanel from '@/components/ReviewsPanel.vue';
import courses from '@/routes/courses';
import type { DashboardReview, Paginated, RatingSummary } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Kursus', href: '/dasbor/kursus' },
            { title: 'Ulasan', href: '#' },
        ],
    },
});

defineProps<{
    course: { id: number; slug: string; title: string };
    reviews: Paginated<DashboardReview>;
    summary: RatingSummary;
    filters: { search?: string; rating?: string | number };
}>();
</script>

<template>
    <Head :title="`Ulasan · ${course.title}`" />

    <CourseManageLayout :course="course">
        <div class="flex flex-col space-y-6">
            <Heading
                variant="small"
                title="Ulasan"
                description="Rating dan ulasan siswa untuk kursus ini"
            />

            <ReviewsPanel
                :url="courses.reviews.index(course.slug).url"
                :reviews="reviews"
                :summary="summary"
                :filters="filters"
            />
        </div>
    </CourseManageLayout>
</template>
