<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { Award, BadgeCheck, Copy, Download } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import catalogRoutes from '@/routes/catalog';
import certificateRoutes from '@/routes/certificates';
import { copyText } from '@/lib/clipboard';

const props = defineProps<{
    certificate: {
        code: string;
        student_name: string;
        course_title: string;
        course_url: string | null;
        instructor_name: string | null;
        final_percent: number | null;
        letter: string | null;
        issued_at: string;
    };
    canDownload: boolean;
    verifyUrl: string;
}>();

const copied = ref(false);

const issuedAt = new Date(props.certificate.issued_at).toLocaleDateString(
    'id-ID',
    { day: 'numeric', month: 'long', year: 'numeric' },
);

async function copyLink(): Promise<void> {
    // When copying is blocked the link is still shown on the page.
    if (await copyText(props.verifyUrl)) {
        copied.value = true;
        setTimeout(() => (copied.value = false), 2000);
    }
}
</script>

<template>
    <div
        class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-10 sm:px-6"
    >
        <Head :title="`Sertifikat ${certificate.code}`" />

        <div
            class="flex items-center gap-3 rounded-xl border border-primary/30 bg-primary/5 p-4 text-sm"
            role="status"
        >
            <BadgeCheck class="size-6 shrink-0 text-primary" />
            <p>
                <span class="font-semibold">Sertifikat valid.</span>
                Diterbitkan oleh {{ $page.props.name }} dan tercatat dengan
                nomor
                <span class="font-mono font-semibold">{{
                    certificate.code
                }}</span
                >.
            </p>
        </div>

        <article
            class="overflow-hidden rounded-2xl border bg-card text-card-foreground shadow-sm"
        >
            <div
                class="flex items-center gap-3 bg-surface px-6 py-5 text-surface-foreground"
            >
                <span
                    class="flex size-10 items-center justify-center rounded-lg bg-primary text-primary-foreground"
                >
                    <Award class="size-5" />
                </span>
                <div>
                    <p
                        class="text-xs font-semibold tracking-widest uppercase opacity-80"
                    >
                        Sertifikat Kelulusan
                    </p>
                    <p class="font-semibold">{{ $page.props.name }}</p>
                </div>
            </div>

            <dl class="grid gap-5 p-6 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <dt class="text-sm text-muted-foreground">
                        Diberikan kepada
                    </dt>
                    <dd class="text-2xl font-bold tracking-tight">
                        {{ certificate.student_name }}
                    </dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="text-sm text-muted-foreground">Kursus</dt>
                    <dd class="text-lg font-semibold text-primary">
                        <Link
                            v-if="certificate.course_url"
                            :href="certificate.course_url"
                            class="underline-offset-4 hover:underline"
                        >
                            {{ certificate.course_title }}
                        </Link>
                        <template v-else>{{
                            certificate.course_title
                        }}</template>
                    </dd>
                </div>
                <div v-if="certificate.final_percent !== null">
                    <dt class="text-sm text-muted-foreground">Nilai akhir</dt>
                    <dd class="font-semibold">
                        {{ certificate.final_percent }} (predikat
                        {{ certificate.letter }})
                    </dd>
                </div>
                <div v-if="certificate.instructor_name">
                    <dt class="text-sm text-muted-foreground">Instruktur</dt>
                    <dd class="font-semibold">
                        {{ certificate.instructor_name }}
                    </dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">Diterbitkan</dt>
                    <dd class="font-semibold">{{ issuedAt }}</dd>
                </div>
                <div>
                    <dt class="text-sm text-muted-foreground">
                        Nomor sertifikat
                    </dt>
                    <dd class="font-mono font-semibold">
                        {{ certificate.code }}
                    </dd>
                </div>
            </dl>

            <div
                class="flex flex-wrap items-center gap-3 border-t bg-muted/30 px-6 py-4"
            >
                <a
                    v-if="canDownload"
                    :href="certificateRoutes.download(certificate.code).url"
                >
                    <Button class="rounded-xl font-bold">
                        <Download class="mr-2 h-4 w-4" />
                        Unduh PDF
                    </Button>
                </a>
                <Button
                    variant="outline"
                    class="rounded-xl"
                    type="button"
                    @click="copyLink"
                >
                    <Copy class="mr-2 h-4 w-4" />
                    {{ copied ? 'Tautan disalin' : 'Salin tautan verifikasi' }}
                </Button>
            </div>
        </article>
    </div>
</template>
