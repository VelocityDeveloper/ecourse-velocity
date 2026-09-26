<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, BookOpen, UserMinus } from '@lucide/vue';
import { ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { getInitials } from '@/composables/useInitials';
import {
    enrollmentStatusLabel,
    enrollmentStatusVariant,
    formatDate,
    formatPrice,
    levelLabel,
    statusBadgeVariant,
    statusLabel,
} from '@/lib/course';
import courses from '@/routes/courses';
import enrollmentRoutes from '@/routes/enrollments';
import users from '@/routes/users';
import type { EnrollmentActor, EnrollmentDetail } from '@/types';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Enrollments', href: '/enrollments' },
            { title: 'Detail', href: '#' },
        ],
    },
});

defineProps<{
    enrollment: EnrollmentDetail;
    can: { cancel: boolean };
}>();

const cancelDialogOpen = ref(false);

function formatDateTime(date: string | null): string {
    if (date === null) {
        return '-';
    }

    return new Date(date).toLocaleString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
        hour: '2-digit',
        minute: '2-digit',
    });
}

function actorLabel(actor: EnrollmentActor | null, fallback: string): string {
    return actor === null ? fallback : `${actor.name} (${actor.role})`;
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head :title="`Enrollment #${enrollment.id}`" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <div class="flex items-start gap-3">
                <Heading
                    variant="small"
                    :title="`Enrollment #${enrollment.id}`"
                    :description="`${enrollment.student.name} in ${enrollment.course.title}`"
                />
                <Badge :variant="enrollmentStatusVariant(enrollment.status)">
                    {{ enrollmentStatusLabel(enrollment.status) }}
                </Badge>
            </div>
            <div class="flex items-center gap-2">
                <Link :href="enrollmentRoutes.index()">
                    <Button variant="outline" size="sm">
                        <ArrowLeft class="mr-2 h-4 w-4" />
                        Back
                    </Button>
                </Link>
                <Button
                    v-if="can.cancel"
                    variant="destructive"
                    size="sm"
                    @click="cancelDialogOpen = true"
                >
                    <UserMinus class="mr-2 h-4 w-4" />
                    Cancel enrollment
                </Button>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <h2 class="text-sm font-medium text-muted-foreground">
                    Student
                </h2>
                <div class="flex items-center gap-4">
                    <Avatar class="size-14 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="enrollment.student.avatar"
                            :src="enrollment.student.avatar"
                            :alt="enrollment.student.name"
                        />
                        <AvatarFallback>
                            {{ getInitials(enrollment.student.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="min-w-0">
                        <Link
                            :href="users.show(enrollment.student.id)"
                            class="font-semibold hover:underline"
                        >
                            {{ enrollment.student.name }}
                        </Link>
                        <p class="truncate text-sm text-muted-foreground">
                            {{ enrollment.student.email }}
                        </p>
                        <p
                            v-if="enrollment.student.headline"
                            class="text-sm text-muted-foreground"
                        >
                            {{ enrollment.student.headline }}
                        </p>
                    </div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Member since</dt>
                        <dd>{{ formatDate(enrollment.student.joined_at) }}</dd>
                    </div>
                </dl>
            </section>

            <section class="flex flex-col gap-4 rounded-lg border p-4">
                <h2 class="text-sm font-medium text-muted-foreground">
                    Course
                </h2>
                <div class="flex items-center gap-4">
                    <img
                        v-if="enrollment.course.thumbnail_url"
                        :src="enrollment.course.thumbnail_url"
                        :alt="enrollment.course.title"
                        class="h-14 w-24 shrink-0 rounded object-cover"
                    />
                    <div
                        v-else
                        class="flex h-14 w-24 shrink-0 items-center justify-center rounded bg-muted text-muted-foreground"
                    >
                        <BookOpen class="size-6" />
                    </div>
                    <div class="min-w-0">
                        <Link
                            :href="courses.show(enrollment.course.id)"
                            class="font-semibold hover:underline"
                        >
                            {{ enrollment.course.title }}
                        </Link>
                        <p class="text-sm text-muted-foreground">
                            {{ enrollment.course.instructor?.name ?? '-' }}
                        </p>
                    </div>
                </div>
                <dl class="space-y-2 text-sm">
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Status</dt>
                        <dd>
                            <Badge
                                :variant="
                                    statusBadgeVariant(enrollment.course.status)
                                "
                            >
                                {{ statusLabel(enrollment.course.status) }}
                            </Badge>
                        </dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Category</dt>
                        <dd>{{ enrollment.course.category?.name ?? '-' }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Level</dt>
                        <dd>{{ levelLabel(enrollment.course.level) }}</dd>
                    </div>
                    <div class="flex justify-between gap-4">
                        <dt class="text-muted-foreground">Price</dt>
                        <dd>{{ formatPrice(enrollment.course.price) }}</dd>
                    </div>
                </dl>
            </section>
        </div>

        <section class="flex flex-col gap-4 rounded-lg border p-4">
            <h2 class="text-sm font-medium text-muted-foreground">History</h2>
            <ol class="relative ml-2 space-y-5 border-l pl-6">
                <li class="relative">
                    <span
                        class="absolute top-1 -left-[31px] size-3 rounded-full bg-primary"
                    />
                    <p class="text-sm font-medium">Enrolled</p>
                    <p class="text-sm text-muted-foreground">
                        {{ formatDateTime(enrollment.enrolled_at) }} ·
                        {{
                            enrollment.is_self_enrolled
                                ? 'Self-enrolled by the student'
                                : `Added by ${actorLabel(enrollment.enrolled_by, 'a staff member')}`
                        }}
                    </p>
                </li>
                <li v-if="enrollment.status === 'cancelled'" class="relative">
                    <span
                        class="absolute top-1 -left-[31px] size-3 rounded-full bg-destructive"
                    />
                    <p class="text-sm font-medium">Cancelled</p>
                    <p class="text-sm text-muted-foreground">
                        {{ formatDateTime(enrollment.cancelled_at) }} · by
                        {{
                            actorLabel(
                                enrollment.cancelled_by,
                                'a deleted user',
                            )
                        }}
                    </p>
                    <p
                        class="mt-2 rounded-md bg-muted/50 p-3 text-sm whitespace-pre-line"
                    >
                        {{
                            enrollment.cancellation_reason ?? 'No reason given.'
                        }}
                    </p>
                </li>
            </ol>
        </section>

        <CancelEnrollmentDialog
            v-model:open="cancelDialogOpen"
            :enrollment-id="enrollment.id"
            :description="`Cancel ${enrollment.student.name}'s enrollment in ${enrollment.course.title}? They will lose access to the course.`"
        />
    </div>
</template>
