<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Award,
    BookOpen,
    ClipboardCheck,
    MessagesSquare,
} from '@lucide/vue';
import { computed } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const page = usePage();
const siteDescription = computed(() => page.props.site.description);
const year = new Date().getFullYear();

// Only features the app really has.
const highlights = [
    {
        Icon: BookOpen,
        title: 'Materi tersusun per bab',
        text: 'Video dan artikel yang runtut dari dasar hingga mahir.',
    },
    {
        Icon: ClipboardCheck,
        title: 'Kuis dengan nilai',
        text: 'Uji pemahaman Anda dan lihat hasilnya langsung.',
    },
    {
        Icon: MessagesSquare,
        title: 'Diskusi dengan instruktur',
        text: 'Tanyakan materi yang belum jelas di setiap pelajaran.',
    },
    {
        Icon: Award,
        title: 'Sertifikat kelulusan',
        text: 'Dapatkan sertifikat yang bisa diverifikasi setelah lulus.',
    },
];
</script>

<template>
    <div
        class="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1fr)_minmax(0,1.1fr)]"
    >
        <!-- Brand panel: the same dark surface, glow and grid as the home hero -->
        <aside
            class="relative isolate hidden flex-col justify-between gap-10 overflow-hidden bg-surface p-10 text-surface-foreground lg:flex xl:p-14"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -top-40 -right-40 -z-10 size-[36rem] rounded-full bg-primary/30 blur-3xl"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute -bottom-40 -left-32 -z-10 size-96 rounded-full bg-brand/15 blur-3xl"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 -z-10 [background-image:linear-gradient(currentColor_1px,transparent_1px),linear-gradient(90deg,currentColor_1px,transparent_1px)] [mask-image:radial-gradient(ellipse_at_top_left,black_10%,transparent_70%)] [background-size:48px_48px] opacity-[0.06]"
            />

            <Link :href="home()" class="w-fit">
                <SiteLogo wordmark />
            </Link>

            <div class="flex max-w-lg flex-col gap-8">
                <h2
                    class="text-4xl leading-[1.15] font-extrabold tracking-tight text-balance xl:text-5xl"
                >
                    Belajar terarah,
                    <span
                        class="bg-gradient-to-r from-brand to-[color-mix(in_oklch,var(--brand)_45%,white)] bg-clip-text text-transparent"
                        >dari dasar hingga mahir.</span
                    >
                </h2>

                <ul class="grid gap-5">
                    <li
                        v-for="item in highlights"
                        :key="item.title"
                        class="flex gap-4"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-xl border border-surface-foreground/15 bg-surface-foreground/10 text-brand"
                        >
                            <component :is="item.Icon" class="size-5" />
                        </span>
                        <span>
                            <span class="block font-bold">{{
                                item.title
                            }}</span>
                            <span
                                class="block text-sm text-surface-foreground/70"
                                >{{ item.text }}</span
                            >
                        </span>
                    </li>
                </ul>
            </div>

            <div class="space-y-1 text-sm text-surface-foreground/55">
                <p class="max-w-md">{{ siteDescription }}</p>
                <p>© {{ year }} {{ page.props.name }}</p>
            </div>
        </aside>

        <main class="flex flex-col px-4 py-6 sm:px-10 lg:px-14 lg:py-10">
            <div class="flex items-center justify-between gap-4">
                <Link :href="home()" class="text-foreground lg:invisible">
                    <SiteLogo wordmark />
                </Link>
                <Link
                    :href="home()"
                    class="flex items-center gap-1.5 text-sm font-semibold text-muted-foreground transition-colors hover:text-foreground"
                >
                    <ArrowLeft class="size-4" />
                    <span class="sm:hidden">Beranda</span>
                    <span class="hidden sm:inline">Kembali ke beranda</span>
                </Link>
            </div>

            <div
                class="flex flex-1 justify-center pt-10 pb-6 sm:items-center sm:py-10"
            >
                <div class="flex w-full max-w-md flex-col gap-8">
                    <div class="space-y-2">
                        <h1
                            class="text-3xl font-extrabold tracking-tight text-balance"
                        >
                            {{ title }}
                        </h1>
                        <p v-if="description" class="text-muted-foreground">
                            {{ description }}
                        </p>
                    </div>
                    <slot />
                </div>
            </div>
        </main>
    </div>
</template>
