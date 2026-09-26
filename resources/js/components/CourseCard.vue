<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CircleCheck, Users } from '@lucide/vue';
import StarRating from '@/components/StarRating.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { getInitials } from '@/composables/useInitials';
import { formatPrice, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import type { CatalogCourse } from '@/types';

defineProps<{
    course: CatalogCourse;
}>();
</script>

<template>
    <Link
        :href="catalogRoutes.show(course.id)"
        class="group flex h-full flex-col overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm transition-all hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/10"
    >
        <div class="relative aspect-video overflow-hidden bg-muted">
            <img
                v-if="course.thumbnail_url"
                :src="course.thumbnail_url"
                :alt="course.title"
                loading="lazy"
                class="size-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
            />
            <div
                v-else
                class="flex size-full items-center justify-center text-muted-foreground"
            >
                <BookOpen class="size-8" />
            </div>
            <span
                class="absolute top-3 right-3 rounded-md bg-primary px-2 py-0.5 text-[11px] font-bold text-primary-foreground shadow-sm"
            >
                {{ levelLabel(course.level) }}
            </span>
            <Badge
                v-if="course.is_enrolled"
                variant="secondary"
                class="absolute top-3 left-3 shadow-sm"
            >
                <CircleCheck class="h-3 w-3" />
                Terdaftar
            </Badge>
        </div>

        <div class="flex flex-1 flex-col gap-2.5 p-5">
            <p
                v-if="course.category"
                class="truncate text-[11px] font-bold tracking-wider text-primary uppercase"
            >
                {{ course.category.name }}
            </p>

            <h3
                class="line-clamp-2 min-h-12 leading-6 font-bold transition-colors group-hover:text-primary"
            >
                {{ course.title }}
            </h3>

            <p
                v-if="course.summary"
                class="line-clamp-2 text-sm text-muted-foreground"
            >
                {{ course.summary }}
            </p>

            <div
                class="flex items-center gap-4 text-xs font-medium text-muted-foreground"
            >
                <span class="flex items-center gap-1.5">
                    <BookOpen class="h-3.5 w-3.5 text-primary" />
                    {{ course.lessons_count }} materi
                </span>
                <span class="flex items-center gap-1.5">
                    <Users class="h-3.5 w-3.5 text-primary" />
                    {{ course.students_count }} siswa
                </span>
            </div>

            <div
                v-if="course.instructor"
                class="flex min-w-0 items-center gap-2 text-xs text-muted-foreground"
            >
                <Avatar class="size-6 shrink-0 overflow-hidden rounded-full">
                    <AvatarImage
                        v-if="course.instructor.avatar"
                        :src="course.instructor.avatar"
                        :alt="course.instructor.name"
                    />
                    <AvatarFallback class="text-[10px]">
                        {{ getInitials(course.instructor.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="truncate"
                    >Oleh
                    <span class="font-semibold text-foreground">{{
                        course.instructor.name
                    }}</span></span
                >
            </div>

            <div class="mt-auto space-y-3 border-t pt-3">
                <div class="flex items-center gap-1.5 text-xs">
                    <template v-if="course.reviews_count > 0">
                        <StarRating :rating="course.rating_average" />
                        <span class="font-semibold">{{
                            course.rating_average?.toFixed(1)
                        }}</span>
                        <span class="text-muted-foreground"
                            >({{ course.reviews_count }})</span
                        >
                    </template>
                    <span v-else class="text-muted-foreground"
                        >Belum ada ulasan</span
                    >
                </div>
                <div class="flex items-center justify-between gap-3">
                    <span class="text-lg font-extrabold text-primary">
                        {{ formatPrice(course.price) }}
                    </span>
                    <span
                        class="flex items-center gap-1 text-sm font-bold text-primary"
                    >
                        Detail
                        <ArrowRight
                            class="h-4 w-4 transition-transform group-hover:translate-x-0.5"
                        />
                    </span>
                </div>
            </div>
        </div>
    </Link>
</template>
