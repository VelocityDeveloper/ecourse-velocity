<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    ArrowUp,
    Clock,
    Mail,
    MapPin,
    MessageCircle,
} from '@lucide/vue';
import { computed } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { accountLinks } from '@/composables/useAccountLinks';
import { home, login, register } from '@/routes';
import blogRoutes from '@/routes/blog';
import catalogRoutes from '@/routes/catalog';
import instructorApplications from '@/routes/instructor-applications';
import passwordRoutes from '@/routes/password';

type FooterLink = { label: string; href: string };

const page = usePage();
const appName = computed(() => page.props.name);
const site = computed(() => page.props.site);
const categories = computed(() => (page.props.navCategories ?? []).slice(0, 6));
const year = new Date().getFullYear();

const headingClass = 'mb-4 text-sm font-bold tracking-wider uppercase';
const linkClass =
    'text-surface-foreground/65 transition-colors hover:text-surface-foreground';

const NETWORK_LABELS: Record<string, string> = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    facebook: 'Facebook',
    linkedin: 'LinkedIn',
};

const exploreLinks: FooterLink[] = [
    { label: 'Semua Kursus', href: catalogRoutes.index().url },
    { label: 'Jalur Belajar', href: '/#categories' },
    { label: 'Cara Kerja', href: '/#how-it-works' },
    { label: 'Instruktur', href: '/#instructors' },
    { label: 'Jadi Instruktur', href: instructorApplications.create().url },
    { label: 'Cerita Alumni', href: '/#testimonials' },
    { label: 'Blog', href: blogRoutes.index().url },
];

// Guests get the way in; logged-in users get the same shortcuts as their account menu.
const accountColumn = computed<FooterLink[]>(() => {
    const user = page.props.auth.user;

    if (user) {
        return accountLinks(user).map(({ label, href }) => ({ label, href }));
    }

    return [
        { label: 'Masuk', href: login().url },
        ...(page.props.canRegister
            ? [{ label: 'Daftar Akun Gratis', href: register().url }]
            : []),
        { label: 'Lupa Kata Sandi', href: passwordRoutes.request().url },
    ];
});

const hasContact = computed(
    () =>
        Boolean(site.value.address) ||
        Boolean(site.value.email) ||
        Boolean(site.value.phone) ||
        Boolean(site.value.hours),
);

function backToTop(): void {
    const reduceMotion = window.matchMedia(
        '(prefers-reduced-motion: reduce)',
    ).matches;

    window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
}
</script>

<template>
    <footer class="bg-surface text-surface-foreground">
        <div
            class="mx-auto grid w-full max-w-site grid-cols-2 gap-x-8 gap-y-10 px-4 pt-14 pb-12 sm:px-6 md:grid-cols-3 lg:grid-cols-[1.6fr_1fr_1fr_1fr]"
        >
            <div class="col-span-2 space-y-5 md:col-span-3 lg:col-span-1">
                <Link :href="home()" class="inline-flex">
                    <SiteLogo wordmark />
                </Link>
                <p
                    class="max-w-sm text-sm leading-relaxed text-surface-foreground/65"
                >
                    {{ site.description }}
                </p>

                <ul
                    v-if="hasContact"
                    class="space-y-2.5 text-sm text-surface-foreground/65"
                    aria-label="Kontak"
                >
                    <li v-if="site.address" class="flex gap-2.5">
                        <MapPin class="mt-0.5 size-4 shrink-0 text-brand" />
                        <span>{{ site.address }}</span>
                    </li>
                    <li v-if="site.email" class="flex gap-2.5">
                        <Mail class="mt-0.5 size-4 shrink-0 text-brand" />
                        <a
                            :href="`mailto:${site.email}`"
                            class="break-all transition-colors hover:text-surface-foreground"
                            >{{ site.email }}</a
                        >
                    </li>
                    <li v-if="site.phone" class="flex gap-2.5">
                        <MessageCircle
                            class="mt-0.5 size-4 shrink-0 text-brand"
                        />
                        <a
                            v-if="site.whatsapp_url"
                            :href="site.whatsapp_url"
                            target="_blank"
                            rel="noopener"
                            class="transition-colors hover:text-surface-foreground"
                            >{{ site.phone }}</a
                        >
                        <span v-else>{{ site.phone }}</span>
                    </li>
                    <li v-if="site.hours" class="flex gap-2.5">
                        <Clock class="mt-0.5 size-4 shrink-0 text-brand" />
                        <span>{{ site.hours }}</span>
                    </li>
                </ul>

                <ul
                    v-if="site.socials.length > 0"
                    class="flex flex-wrap gap-2 pt-1"
                    aria-label="Media sosial"
                >
                    <li v-for="social in site.socials" :key="social.network">
                        <a
                            :href="social.url"
                            target="_blank"
                            rel="noopener"
                            class="flex size-10 items-center justify-center rounded-lg bg-surface-muted text-surface-foreground/80 transition-colors hover:bg-primary hover:text-primary-foreground"
                            :aria-label="
                                NETWORK_LABELS[social.network] ?? social.network
                            "
                        >
                            <SocialIcon
                                :network="social.network"
                                class="size-4"
                            />
                        </a>
                    </li>
                </ul>
            </div>

            <nav v-if="categories.length > 0" aria-label="Kategori kursus">
                <h2 :class="headingClass">Kategori</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="category in categories" :key="category.id">
                        <Link
                            :href="
                                catalogRoutes.index({
                                    query: { kategori: category.slug },
                                })
                            "
                            :class="linkClass"
                        >
                            {{ category.name }}
                        </Link>
                    </li>
                    <li>
                        <Link
                            :href="catalogRoutes.index()"
                            class="inline-flex items-center gap-1 font-semibold text-brand transition-colors hover:text-surface-foreground"
                        >
                            Lihat semua
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </li>
                </ul>
            </nav>

            <nav aria-label="Jelajahi">
                <h2 :class="headingClass">Jelajahi</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="link in exploreLinks" :key="link.label">
                        <a :href="link.href" :class="linkClass">
                            {{ link.label }}
                        </a>
                    </li>
                </ul>
            </nav>

            <nav aria-label="Akun">
                <h2 :class="headingClass">Akun</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="link in accountColumn" :key="link.label">
                        <Link :href="link.href" :class="linkClass">
                            {{ link.label }}
                        </Link>
                    </li>
                </ul>
            </nav>
        </div>

        <div class="border-t border-surface-muted">
            <div
                class="mx-auto flex w-full max-w-site flex-col-reverse items-center gap-4 px-4 py-6 text-xs text-surface-foreground/55 sm:flex-row sm:justify-between sm:px-6"
            >
                <p>© {{ year }} {{ appName }}. Hak cipta dilindungi.</p>
                <button
                    type="button"
                    class="inline-flex items-center gap-1.5 rounded-lg bg-surface-muted px-3 py-2 font-semibold text-surface-foreground/80 transition-colors hover:bg-primary hover:text-primary-foreground"
                    @click="backToTop"
                >
                    <ArrowUp class="size-3.5" />
                    Kembali ke atas
                </button>
            </div>
        </div>
    </footer>
</template>
