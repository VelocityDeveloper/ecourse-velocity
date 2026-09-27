<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { BookOpen, CalendarDays, ChevronRight, Star, Users } from '@lucide/vue';
import { computed } from 'vue';
import CourseCard from '@/components/CourseCard.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import { home } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import type { CatalogCourse, PublicProfile, UserRole } from '@/types';

const props = defineProps<{
    profile: PublicProfile;
    courses: CatalogCourse[];
    stats: {
        courses: number;
        students: number;
        reviews: number;
        rating: number | null;
    };
}>();

const ROLE_LABELS: Record<UserRole, string> = {
    admin: 'Admin',
    instructor: 'Instruktur',
    student: 'Siswa',
};

const isInstructor = computed(() => props.profile.role === 'instructor');

const joinedAt = computed(() =>
    props.profile.joined_at
        ? new Date(props.profile.joined_at).toLocaleDateString('id-ID', {
              month: 'long',
              year: 'numeric',
          })
        : null,
);

const statItems = computed(() => [
    { label: 'Kursus', value: String(props.stats.courses), Icon: BookOpen },
    {
        label: 'Siswa',
        value: props.stats.students.toLocaleString('id-ID'),
        Icon: Users,
    },
    {
        label: 'Rating',
        value: props.stats.rating === null ? '-' : String(props.stats.rating),
        note: props.stats.reviews > 0 ? `${props.stats.reviews} ulasan` : null,
        Icon: Star,
    },
]);
</script>

<template>
    <div>
        <Head :title="profile.name" />

        <!-- Header band, same width as the site header and footer -->
        <section
            class="relative isolate overflow-hidden bg-surface text-surface-foreground"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-24 -right-24 -z-10 size-96 rounded-full bg-primary/25 blur-3xl"
            />

            <div class="mx-auto w-full max-w-site px-4 py-10 sm:px-6 lg:py-14">
                <nav
                    aria-label="Jejak navigasi"
                    class="mb-6 flex flex-wrap items-center gap-1.5 text-sm text-surface-foreground/60"
                >
                    <Link :href="home()" class="hover:text-surface-foreground"
                        >Beranda</Link
                    >
                    <ChevronRight class="h-3.5 w-3.5" />
                    <a
                        v-if="isInstructor"
                        href="/#instructors"
                        class="hover:text-surface-foreground"
                        >Instruktur</a
                    >
                    <span v-else>Pengguna</span>
                    <ChevronRight class="h-3.5 w-3.5" />
                    <span class="truncate text-surface-foreground">{{
                        profile.name
                    }}</span>
                </nav>

                <div
                    class="flex flex-col items-center gap-6 text-center sm:flex-row sm:items-center sm:text-left"
                >
                    <Avatar
                        class="size-28 shrink-0 overflow-hidden rounded-full ring-4 ring-surface-foreground/15"
                    >
                        <AvatarImage
                            v-if="profile.avatar"
                            :src="profile.avatar"
                            :alt="profile.name"
                        />
                        <AvatarFallback
                            class="bg-primary text-3xl font-bold text-primary-foreground"
                        >
                            {{ getInitials(profile.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex min-w-0 flex-col gap-2">
                        <span
                            class="mx-auto w-fit rounded-md bg-primary px-2 py-0.5 text-xs font-bold text-primary-foreground sm:mx-0"
                        >
                            {{ ROLE_LABELS[profile.role] ?? profile.role }}
                        </span>
                        <h1
                            class="text-3xl font-extrabold tracking-tight sm:text-4xl"
                        >
                            {{ profile.name }}
                        </h1>
                        <p
                            v-if="profile.headline"
                            class="text-lg text-surface-foreground/75"
                        >
                            {{ profile.headline }}
                        </p>
                        <p
                            v-if="joinedAt"
                            class="flex items-center justify-center gap-1.5 text-sm text-surface-foreground/60 sm:justify-start"
                        >
                            <CalendarDays class="size-4" />
                            Bergabung sejak {{ joinedAt }}
                        </p>
                    </div>
                </div>

                <dl
                    v-if="isInstructor"
                    class="mt-8 grid max-w-2xl grid-cols-3 gap-3"
                >
                    <div
                        v-for="item in statItems"
                        :key="item.label"
                        class="rounded-xl border border-surface-foreground/10 bg-surface-foreground/5 p-4"
                    >
                        <dt
                            class="flex items-center gap-1.5 text-xs text-surface-foreground/60"
                        >
                            <component
                                :is="item.Icon"
                                class="size-3.5 text-primary"
                            />
                            {{ item.label }}
                        </dt>
                        <dd class="mt-1 text-2xl font-bold tabular-nums">
                            {{ item.value }}
                            <span
                                v-if="'note' in item && item.note"
                                class="block text-xs font-normal text-surface-foreground/60 sm:inline"
                                >{{ item.note }}</span
                            >
                        </dd>
                    </div>
                </dl>
            </div>
        </section>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-10 px-4 py-12 sm:px-6"
        >
            <section class="grid gap-6 lg:grid-cols-[280px_1fr]">
                <div class="space-y-1">
                    <p class="text-sm font-semibold text-primary">Profil</p>
                    <h2 class="text-2xl font-bold tracking-tight">
                        Tentang {{ profile.name.split(' ')[0] }}
                    </h2>
                </div>
                <div class="rounded-2xl border bg-card p-6">
                    <p
                        v-if="profile.bio"
                        class="leading-relaxed whitespace-pre-line text-muted-foreground"
                    >
                        {{ profile.bio }}
                    </p>
                    <p v-else class="text-muted-foreground">
                        {{ profile.name }} belum menulis bio.
                    </p>
                </div>
            </section>

            <section v-if="isInstructor" class="flex flex-col gap-6">
                <div class="flex flex-wrap items-end justify-between gap-3">
                    <div class="space-y-1">
                        <p class="text-sm font-semibold text-primary">Kursus</p>
                        <h2 class="text-2xl font-bold tracking-tight">
                            Kursus oleh {{ profile.name }}
                        </h2>
                    </div>
                    <Link
                        :href="catalogRoutes.index()"
                        class="text-sm font-semibold text-primary underline-offset-4 hover:underline"
                        >Lihat semua kursus</Link
                    >
                </div>
                <div
                    v-if="courses.length > 0"
                    class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3"
                >
                    <CourseCard
                        v-for="course in courses"
                        :key="course.id"
                        :course="course"
                    />
                </div>
                <div
                    v-else
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card p-12 text-center"
                >
                    <BookOpen class="size-10 text-muted-foreground" />
                    <p class="text-sm text-muted-foreground">
                        Belum ada kursus yang terbit.
                    </p>
                </div>
            </section>
        </div>
    </div>
</template>
