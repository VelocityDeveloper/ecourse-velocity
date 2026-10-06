<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { ArrowRight, Clock, Mail, MapPin, MessageCircle } from '@lucide/vue';
import { computed } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
import SocialIcon from '@/components/SocialIcon.vue';
import { accountLinks } from '@/composables/useAccountLinks';
import { home, login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import passwordRoutes from '@/routes/password';

type FooterLink = { label: string; href: string };

const page = usePage();
const appName = computed(() => page.props.name);
const site = computed(() => page.props.site);
// Columns, links and the bottom bar come from Pengaturan Situs → Footer.
const layout = computed(() => site.value.footer);
const categories = computed(() =>
    layout.value.categories.show
        ? (page.props.navCategories ?? []).slice(
              0,
              layout.value.categories.limit,
          )
        : [],
);
const exploreLinks = computed(() =>
    layout.value.explore.show ? layout.value.explore.links : [],
);
// Visitor counts, laid out like velocity-addons' [velocity-statistics] list.
const statistics = computed(() => {
    const stats = page.props.visitorStats;

    if (!layout.value.statistics.show || !stats) {
        return [];
    }

    return [
        { label: 'Kunjungan hari ini', value: stats.today_visits },
        { label: 'Pengunjung hari ini', value: stats.today_visitors },
        { label: 'Total kunjungan', value: stats.total_visits },
        { label: 'Total pengunjung', value: stats.total_visitors },
        ...(layout.value.statistics.online
            ? [{ label: 'Pengunjung online', value: stats.online }]
            : []),
    ];
});
const copyright = computed(() =>
    layout.value.copyright
        .replaceAll('{tahun}', String(new Date().getFullYear()))
        .replaceAll('{nama}', appName.value),
);

// The brand column takes the room of any column the admin hides.
const linkColumns = computed(
    () =>
        Number(categories.value.length > 0) +
        Number(exploreLinks.value.length > 0) +
        Number(layout.value.account.show) +
        Number(statistics.value.length > 0),
);
const gridClass = computed(
    () =>
        ({
            0: 'lg:grid-cols-1',
            1: 'md:grid-cols-3 lg:grid-cols-[2fr_1fr]',
            2: 'md:grid-cols-3 lg:grid-cols-[1.6fr_1fr_1fr]',
            3: 'md:grid-cols-3 lg:grid-cols-[1.6fr_1fr_1fr_1fr]',
            4: 'md:grid-cols-4 lg:grid-cols-[1.5fr_1fr_1fr_1fr_1.3fr]',
        })[linkColumns.value],
);

function isExternal(url: string): boolean {
    return /^https?:\/\//.test(url);
}

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
</script>

<template>
    <footer class="bg-surface text-surface-foreground">
        <div
            class="mx-auto grid w-full max-w-site grid-cols-2 gap-x-8 gap-y-10 px-4 pt-14 pb-12 sm:px-6"
            :class="gridClass"
        >
            <div
                class="col-span-2 space-y-5 md:col-span-3 lg:col-span-1"
                :class="{ 'md:col-span-4': linkColumns === 4 }"
            >
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
                <h2 :class="headingClass">{{ layout.categories.title }}</h2>
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
                            {{ layout.categories.see_all_label }}
                            <ArrowRight class="size-3.5" />
                        </Link>
                    </li>
                </ul>
            </nav>

            <nav
                v-if="exploreLinks.length > 0"
                :aria-label="layout.explore.title"
            >
                <h2 :class="headingClass">{{ layout.explore.title }}</h2>
                <ul class="space-y-3 text-sm">
                    <li
                        v-for="(link, index) in exploreLinks"
                        :key="`${index}-${link.url}`"
                    >
                        <a
                            :href="link.url"
                            :class="linkClass"
                            v-bind="
                                isExternal(link.url)
                                    ? { target: '_blank', rel: 'noopener' }
                                    : {}
                            "
                        >
                            {{ link.label }}
                        </a>
                    </li>
                </ul>
            </nav>

            <nav v-if="layout.account.show" :aria-label="layout.account.title">
                <h2 :class="headingClass">{{ layout.account.title }}</h2>
                <ul class="space-y-3 text-sm">
                    <li v-for="link in accountColumn" :key="link.label">
                        <Link :href="link.href" :class="linkClass">
                            {{ link.label }}
                        </Link>
                    </li>
                </ul>
            </nav>

            <section
                v-if="statistics.length > 0"
                :aria-label="layout.statistics.title"
            >
                <h2 :class="headingClass">{{ layout.statistics.title }}</h2>
                <dl class="text-sm">
                    <div
                        v-for="item in statistics"
                        :key="item.label"
                        class="flex items-baseline justify-between gap-4 border-b border-surface-muted py-2 first:pt-0 last:border-b-0"
                    >
                        <dt class="text-surface-foreground/65">
                            {{ item.label }}
                        </dt>
                        <dd class="font-bold text-brand tabular-nums">
                            {{ item.value.toLocaleString('id-ID') }}
                        </dd>
                    </div>
                </dl>
            </section>
        </div>

        <div class="border-t border-surface-muted">
            <div
                class="mx-auto flex w-full max-w-site flex-col items-center gap-4 px-4 py-6 text-xs text-surface-foreground/55 sm:flex-row sm:justify-between sm:px-6"
            >
                <p>{{ copyright }}</p>
                <p>
                    Design by
                    <a
                        href="https://velocitydeveloper.com"
                        target="_blank"
                        rel="noopener"
                        class="font-semibold text-surface-foreground/80 transition-colors hover:text-surface-foreground"
                        >Velocity Developer</a
                    >
                </p>
            </div>
        </div>
    </footer>
</template>
