<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { BookOpen, CircleCheck, Users } from '@lucide/vue';
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
        class="group flex flex-col overflow-hidden rounded-lg border bg-card text-card-foreground transition-all hover:-translate-y-0.5 hover:border-primary/40 hover:shadow-lg hover:shadow-primary/10"
    >
        <div class="relative overflow-hidden">
            <img
                v-if="course.thumbnail_url"
                :src="course.thumbnail_url"
                :alt="course.title"
                class="aspect-video w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
            />
            <div
                v-else
                class="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
            >
                <BookOpen class="size-8" />
            </div>
            <Badge v-if="course.is_enrolled" class="absolute top-2 right-2">
                <CircleCheck class="h-3 w-3" />
                Enrolled
            </Badge>
        </div>

        <div class="flex flex-1 flex-col gap-3 p-4">
            <div class="flex flex-wrap items-center gap-2">
                <Badge variant="outline">
                    {{ levelLabel(course.level) }}
                </Badge>
                <span
                    v-if="course.category"
                    class="text-xs text-muted-foreground"
                >
                    {{ course.category.name }}
                </span>
            </div>

            <h3
                class="line-clamp-2 font-semibold transition-colors group-hover:text-primary"
            >
                {{ course.title }}
            </h3>

            <div
                v-if="course.reviews_count > 0"
                class="flex items-center gap-1.5 text-sm"
            >
                <span class="font-semibold">{{
                    course.rating_average?.toFixed(1)
                }}</span>
                <StarRating :rating="course.rating_average" />
                <span class="text-muted-foreground"
                    >({{ course.reviews_count }})</span
                >
            </div>

            <div
                v-if="course.instructor"
                class="flex items-center gap-2 text-sm text-muted-foreground"
            >
                <Avatar class="size-6 overflow-hidden rounded-full">
                    <AvatarImage
                        v-if="course.instructor.avatar"
                        :src="course.instructor.avatar"
                        :alt="course.instructor.name"
                    />
                    <AvatarFallback class="text-[10px]">
                        {{ getInitials(course.instructor.name) }}
                    </AvatarFallback>
                </Avatar>
                <span class="truncate">{{ course.instructor.name }}</span>
            </div>

            <div
                class="mt-auto flex items-center justify-between gap-2 text-sm"
            >
                <span class="font-semibold text-primary">
                    {{ formatPrice(course.price) }}
                </span>
                <span class="flex items-center gap-1 text-muted-foreground">
                    <Users class="h-4 w-4" />
                    {{ course.students_count }}
                </span>
            </div>
        </div>
    </Link>
</template>
