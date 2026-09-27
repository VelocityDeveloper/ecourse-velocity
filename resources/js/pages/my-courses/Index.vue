<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Award, BookOpen, Compass, PlayCircle } from '@lucide/vue';
import { ref } from 'vue';
import CancelEnrollmentDialog from '@/components/CancelEnrollmentDialog.vue';
import LearningHeader from '@/components/LearningHeader.vue';
import { Button } from '@/components/ui/button';
import { formatDate, levelLabel } from '@/lib/course';
import catalogRoutes from '@/routes/catalog';
import certificateRoutes from '@/routes/certificates';
import learn from '@/routes/learn';
import type { MyCourseEnrollment } from '@/types';

defineProps<{
    enrollments: MyCourseEnrollment[];
}>();

const cancelTarget = ref<MyCourseEnrollment | null>(null);
const cancelDialogOpen = ref(false);

const claiming = ref<number | null>(null);

function claimCertificate(enrollment: MyCourseEnrollment): void {
    claiming.value = enrollment.id;

    router.post(
        certificateRoutes.store(enrollment.course.id).url,
        {},
        {
            onFinish: () => {
                claiming.value = null;
            },
        },
    );
}

function askCancel(enrollment: MyCourseEnrollment): void {
    cancelTarget.value = enrollment;
    cancelDialogOpen.value = true;
}
</script>

<template>
    <div>
        <Head title="Kursus Saya" />

        <LearningHeader
            title="Kursus Saya"
            description="Kursus yang sedang Anda ikuti beserta progres belajarnya."
        >
            <template #actions>
                <Link :href="catalogRoutes.index()">
                    <Button variant="secondary" class="rounded-xl font-bold">
                        <Compass class="mr-2 h-4 w-4" />
                        Jelajahi katalog
                    </Button>
                </Link>
            </template>
        </LearningHeader>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-6 px-4 py-10 sm:px-6"
        >
            <div
                v-if="enrollments.length === 0"
                class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card p-12 text-center"
            >
                <BookOpen class="size-10 text-muted-foreground" />
                <p class="text-sm text-muted-foreground">
                    Anda belum terdaftar di kursus mana pun.
                </p>
                <Link :href="catalogRoutes.index()">
                    <Button class="rounded-xl font-bold">Cari kursus</Button>
                </Link>
            </div>

            <div v-else class="grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                <div
                    v-for="enrollment in enrollments"
                    :key="enrollment.id"
                    class="group flex flex-col overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm transition-all hover:-translate-y-1 hover:border-primary/30 hover:shadow-xl hover:shadow-primary/10"
                >
                    <Link
                        :href="learn.show(enrollment.course.id)"
                        class="relative block overflow-hidden bg-muted"
                    >
                        <img
                            v-if="enrollment.course.thumbnail_url"
                            :src="enrollment.course.thumbnail_url"
                            :alt="enrollment.course.title"
                            class="aspect-video w-full object-cover transition-transform duration-300 group-hover:scale-[1.03]"
                        />
                        <div
                            v-else
                            class="flex aspect-video w-full items-center justify-center bg-muted text-muted-foreground"
                        >
                            <BookOpen class="size-8" />
                        </div>
                        <span
                            class="absolute top-3 right-3 rounded-md bg-primary px-2 py-0.5 text-[11px] font-bold text-primary-foreground shadow-sm"
                            >{{ levelLabel(enrollment.course.level) }}</span
                        >
                    </Link>

                    <div class="flex flex-1 flex-col gap-2 p-5">
                        <p
                            v-if="enrollment.course.category"
                            class="truncate text-[11px] font-bold tracking-wider text-primary uppercase"
                        >
                            {{ enrollment.course.category.name }}
                        </p>
                        <Link
                            :href="learn.show(enrollment.course.id)"
                            class="line-clamp-2 min-h-12 leading-6 font-bold transition-colors hover:text-primary"
                        >
                            {{ enrollment.course.title }}
                        </Link>
                        <p
                            v-if="enrollment.course.instructor"
                            class="text-sm text-muted-foreground"
                        >
                            {{ enrollment.course.instructor.name }}
                        </p>

                        <div class="mt-auto space-y-2 border-t pt-4">
                            <div
                                class="flex items-center justify-between text-xs"
                            >
                                <span class="text-muted-foreground"
                                    >{{ enrollment.progress.completed }} dari
                                    {{ enrollment.progress.total }}
                                    selesai</span
                                >
                                <span
                                    class="text-sm font-bold text-primary tabular-nums"
                                    >{{ enrollment.progress.percent }}%</span
                                >
                            </div>
                            <div
                                class="h-2 overflow-hidden rounded-full bg-muted"
                                role="progressbar"
                                :aria-valuenow="enrollment.progress.percent"
                                aria-valuemin="0"
                                aria-valuemax="100"
                                :aria-label="`Progres ${enrollment.course.title}`"
                            >
                                <div
                                    class="h-full rounded-full bg-primary"
                                    :style="{
                                        width: `${enrollment.progress.percent}%`,
                                    }"
                                />
                            </div>
                        </div>

                        <Link
                            :href="learn.show(enrollment.course.id)"
                            class="mt-2"
                        >
                            <Button class="w-full rounded-xl font-bold">
                                <PlayCircle class="mr-2 h-4 w-4" />
                                {{
                                    enrollment.progress.completed === 0
                                        ? 'Mulai belajar'
                                        : enrollment.progress.percent === 100
                                          ? 'Tinjau ulang kursus'
                                          : 'Lanjutkan belajar'
                                }}
                            </Button>
                        </Link>

                        <Link
                            v-if="enrollment.certificate.code"
                            :href="
                                certificateRoutes.show(
                                    enrollment.certificate.code,
                                )
                            "
                        >
                            <Button
                                variant="outline"
                                class="w-full rounded-xl border-primary/40 font-bold text-primary"
                            >
                                <Award class="mr-2 h-4 w-4" />
                                Lihat sertifikat
                            </Button>
                        </Link>
                        <Button
                            v-else-if="enrollment.certificate.eligible"
                            variant="outline"
                            class="w-full rounded-xl border-primary/40 font-bold text-primary"
                            :disabled="claiming === enrollment.id"
                            @click="claimCertificate(enrollment)"
                        >
                            <Award class="mr-2 h-4 w-4" />
                            {{
                                claiming === enrollment.id
                                    ? 'Menyiapkan...'
                                    : 'Ambil sertifikat'
                            }}
                        </Button>
                        <p
                            v-else-if="
                                enrollment.progress.percent === 100 &&
                                enrollment.certificate.final_percent !== null
                            "
                            class="rounded-lg bg-muted/50 px-3 py-2 text-xs text-muted-foreground"
                        >
                            Nilai akhir Anda
                            {{ enrollment.certificate.final_percent }}%. Butuh
                            minimal {{ enrollment.certificate.passing_grade }}%
                            untuk sertifikat. Ulangi kuis untuk menaikkan nilai.
                        </p>

                        <div class="flex items-center justify-between gap-2">
                            <span class="text-xs text-muted-foreground">
                                Terdaftar
                                {{ formatDate(enrollment.enrolled_at) }}
                            </span>
                            <Button
                                v-if="enrollment.can_cancel"
                                variant="ghost"
                                size="sm"
                                class="text-destructive"
                                @click="askCancel(enrollment)"
                            >
                                Batalkan
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <CancelEnrollmentDialog
                v-model:open="cancelDialogOpen"
                :enrollment-id="cancelTarget?.id ?? null"
                :description="`Anda akan kehilangan akses ke ${cancelTarget?.course.title ?? 'kursus ini'}. Anda bisa mendaftar lagi nanti selama kursus masih terbit.`"
            />
        </div>
    </div>
</template>
