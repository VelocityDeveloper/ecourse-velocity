<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    BadgeCheck,
    Clock,
    FileText,
    GraduationCap,
    LayoutGrid,
    Presentation,
    UsersRound,
    Wallet,
    XCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { formatDateTime } from '@/lib/course';
import { dashboard } from '@/routes';
import applicationRoutes from '@/routes/instructor-applications';
import type { InstructorApplication } from '@/types';

const props = defineProps<{
    application: InstructorApplication | null;
    commissionRate: number;
}>();

const page = usePage();
const user = computed(() => page.props.auth.user);

/**
 * What the visitor sees below the introduction: a login prompt, a note that
 * their account can already teach, the status of a request under review, or
 * the form (again with the admin's reason after a rejection).
 */
const state = computed(() => {
    if (!user.value) {
        return 'guest';
    }

    if (user.value.role !== 'student') {
        return 'staff';
    }

    return props.application?.status === 'pending' ? 'pending' : 'form';
});

const rejected = computed(
    () => state.value === 'form' && props.application?.status === 'rejected',
);

const instructorShare = computed(() =>
    (100 - props.commissionRate).toLocaleString('id-ID', {
        maximumFractionDigits: 2,
    }),
);

const benefits = computed(() => [
    {
        icon: UsersRound,
        title: 'Jangkau banyak siswa',
        text: 'Kursus Anda tampil di katalog dan halaman profil instruktur publik.',
    },
    {
        icon: Wallet,
        title: 'Penghasilan dari setiap penjualan',
        text: `Anda menerima ${instructorShare.value}% dari setiap kursus yang terjual, terpantau di dasbor.`,
    },
    {
        icon: Presentation,
        title: 'Alat mengajar lengkap',
        text: 'Materi video & teks, lampiran, kuis, diskusi, hingga sertifikat otomatis.',
    },
]);

const steps = [
    {
        title: 'Kirim pengajuan',
        text: 'Ceritakan keahlian dan pengalaman Anda melalui formulir di bawah.',
    },
    {
        title: 'Ditinjau admin',
        text: 'Tim kami memeriksa pengajuan Anda. Selama ditinjau, akun Anda tetap akun siswa.',
    },
    {
        title: 'Mulai mengajar',
        text: 'Setelah disetujui, akun Anda menjadi instruktur dan bisa membuat kursus.',
    },
];

// After a rejection the form starts from the previous answers, so only the weak parts need rewriting.
const previous = rejected.value ? props.application : null;

const form = useForm({
    headline: previous?.headline ?? user.value?.headline ?? '',
    expertise: previous?.expertise ?? '',
    experience: previous?.experience ?? '',
    motivation: previous?.motivation ?? '',
    portfolio_url: previous?.portfolio_url ?? '',
    phone: previous?.phone ?? '',
    agreement: false,
});

function submit(): void {
    form.post(applicationRoutes.store().url, {
        preserveScroll: true,
        onSuccess: () => form.reset(),
    });
}
</script>

<template>
    <div>
        <Head title="Jadi Instruktur" />

        <section
            class="relative isolate overflow-hidden bg-surface text-surface-foreground"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-40 right-0 -z-10 size-[32rem] rounded-full bg-primary/30 blur-3xl"
            />
            <div
                class="mx-auto grid w-full max-w-site gap-8 px-4 py-12 sm:px-6 lg:grid-cols-[1.3fr_1fr] lg:items-center lg:py-16"
            >
                <div class="space-y-4">
                    <p
                        class="w-fit rounded-full border border-surface-foreground/15 bg-surface-foreground/10 px-3 py-1 font-mono text-xs font-semibold tracking-widest uppercase"
                    >
                        Jadi Instruktur
                    </p>
                    <h1
                        class="text-3xl font-extrabold tracking-tight text-balance sm:text-4xl lg:text-5xl"
                    >
                        Bagikan keahlian Anda, dapatkan penghasilan
                    </h1>
                    <p class="max-w-xl text-pretty text-surface-foreground/75">
                        Ajukan diri sebagai instruktur. Setiap pengajuan
                        ditinjau admin terlebih dahulu sebelum akun Anda bisa
                        membuat dan menjual kursus.
                    </p>
                    <a href="#formulir" class="inline-block pt-2">
                        <Button size="lg" class="rounded-xl font-bold">
                            Ajukan sekarang
                        </Button>
                    </a>
                </div>

                <ul class="grid gap-3">
                    <li
                        v-for="benefit in benefits"
                        :key="benefit.title"
                        class="flex gap-4 rounded-2xl border border-surface-foreground/10 bg-surface-foreground/5 p-4"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl bg-primary text-primary-foreground"
                        >
                            <component :is="benefit.icon" class="size-5" />
                        </span>
                        <div>
                            <p class="font-semibold">{{ benefit.title }}</p>
                            <p class="text-sm text-surface-foreground/70">
                                {{ benefit.text }}
                            </p>
                        </div>
                    </li>
                </ul>
            </div>
        </section>

        <div
            class="mx-auto flex w-full max-w-site flex-col gap-10 px-4 py-12 sm:px-6"
        >
            <section aria-labelledby="steps-title" class="space-y-5">
                <h2
                    id="steps-title"
                    class="text-2xl font-extrabold tracking-tight"
                >
                    Cara menjadi instruktur
                </h2>
                <ol class="grid gap-4 md:grid-cols-3">
                    <li
                        v-for="(step, index) in steps"
                        :key="step.title"
                        class="rounded-2xl border bg-card p-5"
                    >
                        <span
                            class="flex size-8 items-center justify-center rounded-full bg-primary/10 font-mono text-sm font-bold text-primary"
                            >{{ index + 1 }}</span
                        >
                        <p class="mt-3 font-semibold">{{ step.title }}</p>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{ step.text }}
                        </p>
                    </li>
                </ol>
            </section>

            <section id="formulir" class="scroll-mt-24">
                <div
                    v-if="state === 'guest'"
                    class="flex flex-col items-center gap-3 rounded-2xl border border-dashed bg-card p-10 text-center"
                >
                    <GraduationCap class="size-8 text-muted-foreground" />
                    <p class="text-lg font-semibold">
                        Masuk dulu untuk mengajukan diri
                    </p>
                    <p class="max-w-md text-sm text-muted-foreground">
                        Pengajuan dikirim dari akun Anda. Belum punya akun?
                        Daftar gratis, lalu Anda akan kembali ke halaman ini.
                    </p>
                    <a :href="applicationRoutes.login().url" class="mt-2">
                        <Button class="rounded-xl font-bold">
                            Masuk atau daftar
                        </Button>
                    </a>
                </div>

                <div
                    v-else-if="state === 'staff'"
                    class="flex flex-col items-center gap-3 rounded-2xl border bg-card p-10 text-center"
                >
                    <BadgeCheck class="size-8 text-primary" />
                    <p class="text-lg font-semibold">
                        Akun Anda sudah bisa mengajar
                    </p>
                    <p class="max-w-md text-sm text-muted-foreground">
                        Buat dan kelola kursus Anda dari dasbor.
                    </p>
                    <Link :href="dashboard()" class="mt-2">
                        <Button class="rounded-xl font-bold">
                            <LayoutGrid class="mr-2 size-4" />
                            Buka dasbor
                        </Button>
                    </Link>
                </div>

                <div
                    v-else-if="state === 'pending' && application"
                    class="space-y-5 rounded-2xl border bg-card p-6 sm:p-8"
                >
                    <div class="flex flex-wrap items-start gap-4">
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-400"
                        >
                            <Clock class="size-5" />
                        </span>
                        <div class="min-w-0 flex-1">
                            <p class="text-lg font-semibold">
                                Pengajuan Anda sedang ditinjau
                            </p>
                            <p class="text-sm text-muted-foreground">
                                Dikirim
                                {{ formatDateTime(application.created_at) }}.
                                Selama ditinjau akun Anda tetap akun siswa; kami
                                akan mengabari lewat email.
                            </p>
                        </div>
                    </div>

                    <dl class="grid gap-4 border-t pt-5 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="text-muted-foreground">Profesi</dt>
                            <dd class="font-medium">
                                {{ application.headline }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">
                                Bidang keahlian
                            </dt>
                            <dd class="font-medium">
                                {{ application.expertise }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground">Pengalaman</dt>
                            <dd class="whitespace-pre-line">
                                {{ application.experience }}
                            </dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="text-muted-foreground">
                                Rencana mengajar
                            </dt>
                            <dd class="whitespace-pre-line">
                                {{ application.motivation }}
                            </dd>
                        </div>
                    </dl>
                </div>

                <form
                    v-else
                    class="space-y-6 rounded-2xl border bg-card p-6 sm:p-8"
                    @submit.prevent="submit"
                >
                    <div class="flex items-start gap-4">
                        <span
                            class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-primary/10 text-primary"
                        >
                            <FileText class="size-5" />
                        </span>
                        <div>
                            <h2 class="text-xl font-extrabold tracking-tight">
                                Formulir pengajuan instruktur
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Diajukan sebagai {{ user?.name }} ({{
                                    user?.email
                                }}).
                            </p>
                        </div>
                    </div>

                    <div
                        v-if="rejected && application"
                        class="flex gap-3 rounded-xl border border-red-200 bg-red-50 p-4 text-sm text-red-800 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-300"
                    >
                        <XCircle class="mt-0.5 size-4 shrink-0" />
                        <div>
                            <p class="font-semibold">
                                Pengajuan sebelumnya belum disetujui
                                <span
                                    v-if="application.reviewed_at"
                                    class="font-normal"
                                    >({{
                                        formatDateTime(application.reviewed_at)
                                    }})</span
                                >
                            </p>
                            <p v-if="application.admin_note" class="mt-1">
                                Catatan admin: {{ application.admin_note }}
                            </p>
                            <p class="mt-1">
                                Perbaiki data di bawah lalu ajukan kembali.
                            </p>
                        </div>
                    </div>

                    <div class="grid gap-5 sm:grid-cols-2">
                        <div class="grid content-start gap-2">
                            <Label for="headline">Profesi / jabatan</Label>
                            <Input
                                id="headline"
                                v-model="form.headline"
                                maxlength="150"
                                placeholder="Contoh: Web Developer di PT Maju"
                                required
                            />
                            <InputError :message="form.errors.headline" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="expertise"
                                >Bidang yang akan diajarkan</Label
                            >
                            <Input
                                id="expertise"
                                v-model="form.expertise"
                                maxlength="150"
                                placeholder="Contoh: Laravel, desain UI, akuntansi"
                                required
                            />
                            <InputError :message="form.errors.expertise" />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="experience"
                                >Pengalaman & keahlian</Label
                            >
                            <Textarea
                                id="experience"
                                v-model="form.experience"
                                rows="5"
                                maxlength="3000"
                                placeholder="Lama berkarya di bidang ini, proyek, sertifikasi, atau pengalaman mengajar."
                                required
                            />
                            <p class="text-xs text-muted-foreground">
                                Minimal 30 karakter. Akan dipakai sebagai bio
                                profil instruktur Anda bila bio masih kosong.
                            </p>
                            <InputError :message="form.errors.experience" />
                        </div>
                        <div class="grid content-start gap-2 sm:col-span-2">
                            <Label for="motivation"
                                >Kursus yang ingin Anda buat</Label
                            >
                            <Textarea
                                id="motivation"
                                v-model="form.motivation"
                                rows="4"
                                maxlength="3000"
                                placeholder="Topik kursus, untuk siapa, dan apa yang akan dipelajari siswa."
                                required
                            />
                            <InputError :message="form.errors.motivation" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="portfolio_url">
                                Tautan portofolio
                                <span class="font-normal text-muted-foreground"
                                    >(opsional)</span
                                >
                            </Label>
                            <Input
                                id="portfolio_url"
                                v-model="form.portfolio_url"
                                type="url"
                                placeholder="https://"
                            />
                            <InputError :message="form.errors.portfolio_url" />
                        </div>
                        <div class="grid content-start gap-2">
                            <Label for="phone">
                                Nomor WhatsApp
                                <span class="font-normal text-muted-foreground"
                                    >(opsional)</span
                                >
                            </Label>
                            <Input
                                id="phone"
                                v-model="form.phone"
                                type="tel"
                                maxlength="30"
                                placeholder="08xxxxxxxxxx"
                            />
                            <InputError :message="form.errors.phone" />
                        </div>
                    </div>

                    <div class="space-y-2">
                        <label class="flex items-start gap-3 text-sm">
                            <input
                                v-model="form.agreement"
                                type="checkbox"
                                class="mt-0.5 size-4 accent-primary"
                            />
                            <span>
                                Saya menyatakan data di atas benar dan memahami
                                bahwa ada potongan platform sebesar
                                {{
                                    commissionRate.toLocaleString('id-ID', {
                                        maximumFractionDigits: 2,
                                    })
                                }}% dari setiap penjualan kursus.
                            </span>
                        </label>
                        <InputError :message="form.errors.agreement" />
                    </div>

                    <div class="flex justify-end border-t pt-5">
                        <Button
                            type="submit"
                            size="lg"
                            class="rounded-xl font-bold"
                            :disabled="form.processing"
                        >
                            {{
                                rejected ? 'Ajukan kembali' : 'Kirim pengajuan'
                            }}
                        </Button>
                    </div>
                </form>
            </section>
        </div>
    </div>
</template>
