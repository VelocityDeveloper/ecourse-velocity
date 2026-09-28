<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Clock, Mail, MapPin, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { home, login, register } from '@/routes';
import blogRoutes from '@/routes/blog';
import catalogRoutes from '@/routes/catalog';

const page = usePage();
const appName = computed(() => page.props.name);
const site = computed(() => page.props.site);
const year = new Date().getFullYear();

const NETWORK_LABELS: Record<string, string> = {
    instagram: 'Instagram',
    tiktok: 'TikTok',
    youtube: 'YouTube',
    facebook: 'Facebook',
    linkedin: 'LinkedIn',
};

const columns = computed(() => [
    {
        title: 'Belajar',
        links: [
            { label: 'Semua Kursus', href: catalogRoutes.index().url },
            { label: 'Jalur Belajar', href: '/#categories' },
            { label: 'Cara Kerja', href: '/#how-it-works' },
            { label: 'Blog', href: blogRoutes.index().url },
        ],
    },
    {
        title: 'Komunitas',
        links: [
            { label: 'Instruktur', href: '/#instructors' },
            { label: 'Cerita Alumni', href: '/#testimonials' },
            ...(page.props.auth.user
                ? []
                : [
                      page.props.canRegister
                          ? { label: 'Daftar Akun', href: register().url }
                          : { label: 'Masuk', href: login().url },
                  ]),
        ],
    },
]);

const hasContact = computed(
    () =>
        Boolean(site.value.address) ||
        Boolean(site.value.email) ||
        Boolean(site.value.phone) ||
        Boolean(site.value.hours),
);
</script>

<template>
    <footer class="bg-surface text-surface-foreground">
        <div
            class="mx-auto grid w-full max-w-site gap-10 px-4 py-14 sm:px-6 md:grid-cols-2 lg:grid-cols-[1.4fr_1fr_1fr_1.3fr]"
        >
            <div class="space-y-5">
                <Link :href="home()" class="inline-flex">
                    <SiteLogo wordmark />
                </Link>
                <p class="max-w-sm text-sm text-surface-foreground/65">
                    {{ site.description }}
                </p>
                <ul
                    v-if="site.socials.length > 0"
                    class="flex flex-wrap gap-2"
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

            <nav
                v-for="column in columns"
                :key="column.title"
                :aria-label="column.title"
            >
                <h2 class="mb-4 text-sm font-bold tracking-wider uppercase">
                    {{ column.title }}
                </h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="link in column.links" :key="link.label">
                        <a
                            :href="link.href"
                            class="text-surface-foreground/65 transition-colors hover:text-surface-foreground"
                        >
                            {{ link.label }}
                        </a>
                    </li>
                </ul>
            </nav>

            <div v-if="hasContact">
                <h2 class="mb-4 text-sm font-bold tracking-wider uppercase">
                    Hubungi Kami
                </h2>
                <ul class="space-y-3 text-sm text-surface-foreground/65">
                    <li v-if="site.address" class="flex gap-2">
                        <MapPin class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                        <span>{{ site.address }}</span>
                    </li>
                    <li v-if="site.email" class="flex gap-2">
                        <Mail class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                        <a
                            :href="`mailto:${site.email}`"
                            class="break-all hover:text-surface-foreground"
                            >{{ site.email }}</a
                        >
                    </li>
                    <li v-if="site.phone" class="flex gap-2">
                        <MessageCircle
                            class="mt-0.5 h-4 w-4 shrink-0 text-brand"
                        />
                        <a
                            v-if="site.whatsapp_url"
                            :href="site.whatsapp_url"
                            target="_blank"
                            rel="noopener"
                            class="hover:text-surface-foreground"
                            >{{ site.phone }}</a
                        >
                        <span v-else>{{ site.phone }}</span>
                    </li>
                    <li v-if="site.hours" class="flex gap-2">
                        <Clock class="mt-0.5 h-4 w-4 shrink-0 text-brand" />
                        <span>{{ site.hours }}</span>
                    </li>
                </ul>
            </div>
        </div>

        <div class="border-t border-surface-muted">
            <div
                class="mx-auto w-full max-w-site px-4 py-6 text-xs text-surface-foreground/55 sm:px-6"
            >
                © {{ year }} {{ appName }}. Hak cipta dilindungi.
            </div>
        </div>
    </footer>
</template>
