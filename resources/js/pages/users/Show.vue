<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen } from '@lucide/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { getInitials } from '@/composables/useInitials';
import { formatDate, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import type { ProfileCourse, PublicProfile, UserRole } from '@/types';

defineProps<{
    profile: PublicProfile;
    courses: ProfileCourse[];
}>();

const ROLE_LABELS: Record<UserRole, string> = {
    admin: 'Admin',
    instructor: 'Instruktur',
    student: 'Siswa',
};
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-4xl flex-col gap-8 px-4 py-10 sm:px-6"
    >
        <Head :title="profile.name" />

        <section
            class="flex flex-col items-center gap-6 rounded-xl border p-6 text-center sm:flex-row sm:items-start sm:text-left"
        >
            <Avatar class="size-24 shrink-0 overflow-hidden rounded-full">
                <AvatarImage
                    v-if="profile.avatar"
                    :src="profile.avatar"
                    :alt="profile.name"
                />
                <AvatarFallback class="text-2xl">
                    {{ getInitials(profile.name) }}
                </AvatarFallback>
            </Avatar>

            <div class="flex min-w-0 flex-1 flex-col gap-2">
                <div
                    class="flex flex-col items-center gap-2 sm:flex-row sm:items-center"
                >
                    <h1 class="text-2xl font-semibold">{{ profile.name }}</h1>
                    <Badge variant="secondary">
                        {{ ROLE_LABELS[profile.role] ?? profile.role }}
                    </Badge>
                </div>
                <p v-if="profile.headline" class="text-muted-foreground">
                    {{ profile.headline }}
                </p>
                <p
                    v-if="profile.joined_at"
                    class="text-xs text-muted-foreground"
                >
                    Bergabung {{ formatDate(profile.joined_at) }}
                </p>
            </div>
        </section>

        <section class="flex flex-col gap-3">
            <h2 class="text-lg font-semibold">Tentang</h2>
            <p
                v-if="profile.bio"
                class="text-sm leading-relaxed whitespace-pre-line"
            >
                {{ profile.bio }}
            </p>
            <p v-else class="text-sm text-muted-foreground">
                {{ profile.name }} belum menulis bio.
            </p>
        </section>

        <section
            v-if="profile.role === 'instructor'"
            class="flex flex-col gap-3"
        >
            <h2 class="text-lg font-semibold">Kursus</h2>
            <div
                v-if="courses.length > 0"
                class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <Link
                    v-for="course in courses"
                    :key="course.id"
                    :href="catalogRoutes.show(course.id)"
                    class="overflow-hidden rounded-lg border bg-card text-card-foreground transition-colors hover:bg-muted/40"
                >
                    <img
                        v-if="course.thumbnail_url"
                        :src="course.thumbnail_url"
                        :alt="course.title"
                        class="aspect-video w-full object-cover"
                    />
                    <div
                        v-else
                        class="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
                    >
                        <BookOpen class="size-8" />
                    </div>
                    <div class="flex flex-col gap-1 p-3">
                        <span class="line-clamp-2 font-medium">
                            {{ course.title }}
                        </span>
                        <span class="text-xs text-muted-foreground">
                            {{ levelLabel(course.level) }}
                        </span>
                    </div>
                </Link>
            </div>
            <p v-else class="text-sm text-muted-foreground">
                Belum ada kursus yang terbit.
            </p>
        </section>
    </div>
</template>
