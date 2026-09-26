<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import {
    BookMarked,
    BookOpen,
    ChevronDown,
    CircleHelp,
    GraduationCap,
    House,
    LayoutGrid,
    LogOut,
    Menu,
    Settings,
    X,
} from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import SiteLogo from '@/components/SiteLogo.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button, buttonVariants } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard, home, login, logout, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';
import { cn } from '@/lib/utils';
import { edit as profileEdit } from '@/routes/profile';

const page = usePage();
const user = computed(() => page.props.auth.user);
const canRegister = computed(() => page.props.canRegister);
const navCategories = computed(() => page.props.navCategories ?? []);
const isStaff = computed(
    () => user.value?.role === 'admin' || user.value?.role === 'instructor',
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const navLinks = computed(() => [
    {
        label: 'Beranda',
        href: home().url,
        icon: House,
        active: isCurrentUrl(home()),
    },
    {
        label: 'Kursus',
        href: catalogRoutes.index().url,
        icon: BookOpen,
        active: isCurrentOrParentUrl(catalogRoutes.index()),
    },
    {
        label: 'Instruktur',
        href: '/#instructors',
        icon: GraduationCap,
        active: false,
    },
    {
        label: 'Cara kerja',
        href: '/#how-it-works',
        icon: CircleHelp,
        active: false,
    },
]);

// Ghost buttons on the dark header band.
const onSurface =
    'text-surface-foreground hover:bg-surface-muted hover:text-surface-foreground';

// Phones: a panel that drops down under the header, like kursussipil.id.
const menuOpen = ref(false);
const coursesOpen = ref(false);

function closeMenu(): void {
    menuOpen.value = false;
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        closeMenu();
    }
}

// Any visit (a menu link, the browser's back button) closes the panel.
let stopListening: VoidFunction | undefined;

onMounted(() => {
    window.addEventListener('keydown', onKeydown);
    stopListening = router.on('navigate', closeMenu);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', onKeydown);
    stopListening?.();
    document.documentElement.style.overflow = '';
});

// Keep the page behind the open panel from scrolling.
watch(menuOpen, (open) => {
    document.documentElement.style.overflow = open ? 'hidden' : '';

    if (open) {
        coursesOpen.value = isCurrentOrParentUrl(catalogRoutes.index());
    }
});

function logoutFromMenu(): void {
    closeMenu();
    router.flushAll();
}
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b border-surface-muted bg-surface text-surface-foreground"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-site items-center justify-between gap-4 px-4 sm:px-6"
        >
            <Link
                :href="home()"
                class="flex min-w-0 items-center"
                @click="closeMenu"
            >
                <SiteLogo wordmark />
            </Link>

            <nav
                class="hidden items-center gap-1 text-sm font-semibold md:flex"
                aria-label="Utama"
            >
                <a
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href"
                    class="flex items-center gap-1.5 rounded-lg px-3 py-2 transition-colors hover:bg-surface-muted hover:text-surface-foreground"
                    :class="
                        link.active
                            ? 'text-surface-foreground'
                            : 'text-surface-foreground/70'
                    "
                    :aria-current="link.active ? 'page' : undefined"
                >
                    <component
                        :is="link.icon"
                        class="h-4 w-4"
                        :class="link.active ? 'text-brand' : ''"
                    />
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex shrink-0 items-center gap-2">
                <template v-if="user">
                    <Link
                        v-if="isStaff"
                        :href="dashboard()"
                        class="hidden sm:block"
                    >
                        <Button size="sm" class="font-bold">Dasbor</Button>
                    </Link>
                    <Link
                        v-else
                        :href="learning.dashboard()"
                        class="hidden sm:block"
                    >
                        <Button size="sm" class="font-bold"
                            >Belajar Saya</Button
                        >
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="hidden rounded-full md:inline-flex"
                                :class="onSurface"
                                aria-label="Buka menu akun"
                                data-test="public-user-menu"
                            >
                                <Avatar
                                    class="size-8 overflow-hidden rounded-full"
                                >
                                    <AvatarImage
                                        v-if="user.avatar"
                                        :src="user.avatar"
                                        :alt="user.name"
                                    />
                                    <AvatarFallback
                                        class="bg-primary text-xs font-semibold text-primary-foreground"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>
                            </Button>
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="w-56">
                            <UserMenuContent :user="user" />
                        </DropdownMenuContent>
                    </DropdownMenu>
                </template>

                <template v-else>
                    <Link :href="login()" class="md:hidden" @click="closeMenu">
                        <Button size="sm" class="rounded-lg font-bold"
                            >Masuk</Button
                        >
                    </Link>
                    <div class="hidden items-center gap-1 md:flex">
                        <Link :href="login()">
                            <Button
                                variant="ghost"
                                size="sm"
                                class="font-bold"
                                :class="onSurface"
                                >Masuk</Button
                            >
                        </Link>
                        <Link v-if="canRegister" :href="register()">
                            <Button size="sm" class="rounded-xl px-4 font-bold"
                                >Daftar</Button
                            >
                        </Link>
                    </div>
                </template>

                <!-- Phones: the menu button sits on the right, like the reference site -->
                <Button
                    variant="ghost"
                    size="icon"
                    class="md:hidden"
                    :class="onSurface"
                    :aria-label="menuOpen ? 'Tutup menu' : 'Buka menu'"
                    :aria-expanded="menuOpen"
                    aria-controls="mobile-menu"
                    @click="menuOpen = !menuOpen"
                >
                    <X v-if="menuOpen" class="size-6" />
                    <Menu v-else class="size-6" />
                </Button>
            </div>
        </div>

        <!-- Phone menu: drops down under the header -->
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0 -translate-y-2"
            leave-active-class="transition duration-150 ease-in"
            leave-to-class="opacity-0 -translate-y-2"
        >
            <div
                v-if="menuOpen"
                id="mobile-menu"
                class="absolute inset-x-0 top-full max-h-[calc(100svh-4rem)] overflow-y-auto border-b bg-background text-foreground shadow-2xl md:hidden"
            >
                <nav class="px-4 pt-4 pb-2" aria-label="Menu utama">
                    <ul class="space-y-1">
                        <li v-for="link in navLinks" :key="link.label">
                            <!-- "Kursus" opens a submenu of categories -->
                            <template v-if="link.label === 'Kursus'">
                                <button
                                    type="button"
                                    class="flex w-full items-center gap-3 rounded-xl px-3 py-3 text-left font-bold transition-colors hover:bg-muted"
                                    :class="link.active ? 'text-primary' : ''"
                                    :aria-expanded="coursesOpen"
                                    aria-controls="mobile-menu-courses"
                                    @click="coursesOpen = !coursesOpen"
                                >
                                    <component
                                        :is="link.icon"
                                        class="h-5 w-5"
                                        :class="
                                            link.active
                                                ? 'text-primary'
                                                : 'text-muted-foreground'
                                        "
                                    />
                                    <span class="flex-1">{{ link.label }}</span>
                                    <ChevronDown
                                        class="h-4 w-4 text-muted-foreground transition-transform"
                                        :class="coursesOpen ? 'rotate-180' : ''"
                                    />
                                </button>
                                <ul
                                    v-show="coursesOpen"
                                    id="mobile-menu-courses"
                                    class="mt-1 mb-2 ml-6 space-y-0.5 border-l pl-4"
                                >
                                    <li>
                                        <Link
                                            :href="catalogRoutes.index()"
                                            class="block rounded-lg px-3 py-2 text-sm font-semibold hover:bg-muted"
                                            >Semua Kursus</Link
                                        >
                                    </li>
                                    <li
                                        v-for="category in navCategories"
                                        :key="category.id"
                                    >
                                        <Link
                                            :href="
                                                catalogRoutes.index({
                                                    query: {
                                                        category_id:
                                                            category.id,
                                                    },
                                                })
                                            "
                                            class="block rounded-lg px-3 py-2 text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                            >{{ category.name }}</Link
                                        >
                                    </li>
                                </ul>
                            </template>
                            <a
                                v-else
                                :href="link.href"
                                class="flex items-center gap-3 rounded-xl px-3 py-3 font-bold transition-colors hover:bg-muted"
                                :class="link.active ? 'text-primary' : ''"
                                :aria-current="link.active ? 'page' : undefined"
                                @click="closeMenu"
                            >
                                <component
                                    :is="link.icon"
                                    class="h-5 w-5"
                                    :class="
                                        link.active
                                            ? 'text-primary'
                                            : 'text-muted-foreground'
                                    "
                                />
                                {{ link.label }}
                            </a>
                        </li>

                        <template v-if="user">
                            <li v-if="isStaff">
                                <Link
                                    :href="dashboard()"
                                    class="flex items-center gap-3 rounded-xl px-3 py-3 font-bold hover:bg-muted"
                                >
                                    <LayoutGrid
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                    Dasbor
                                </Link>
                            </li>
                            <template v-else>
                                <li>
                                    <Link
                                        :href="learning.dashboard()"
                                        class="flex items-center gap-3 rounded-xl px-3 py-3 font-bold hover:bg-muted"
                                    >
                                        <LayoutGrid
                                            class="h-5 w-5 text-muted-foreground"
                                        />
                                        Belajar Saya
                                    </Link>
                                </li>
                                <li>
                                    <Link
                                        :href="myCourses.index()"
                                        class="flex items-center gap-3 rounded-xl px-3 py-3 font-bold hover:bg-muted"
                                    >
                                        <BookMarked
                                            class="h-5 w-5 text-muted-foreground"
                                        />
                                        Kursus Saya
                                    </Link>
                                </li>
                            </template>
                            <li>
                                <Link
                                    :href="profileEdit()"
                                    class="flex items-center gap-3 rounded-xl px-3 py-3 font-bold hover:bg-muted"
                                >
                                    <Settings
                                        class="h-5 w-5 text-muted-foreground"
                                    />
                                    Pengaturan
                                </Link>
                            </li>
                        </template>
                    </ul>
                </nav>

                <div class="space-y-4 border-t px-4 pt-4 pb-5">
                    <template v-if="user">
                        <div
                            class="flex items-center gap-3 rounded-xl bg-muted/50 p-3"
                        >
                            <Avatar
                                class="size-10 overflow-hidden rounded-full"
                            >
                                <AvatarImage
                                    v-if="user.avatar"
                                    :src="user.avatar"
                                    :alt="user.name"
                                />
                                <AvatarFallback
                                    class="bg-primary text-sm font-semibold text-primary-foreground"
                                >
                                    {{ getInitials(user.name) }}
                                </AvatarFallback>
                            </Avatar>
                            <div class="min-w-0">
                                <p class="truncate font-bold">
                                    {{ user.name }}
                                </p>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ user.email }}
                                </p>
                            </div>
                        </div>
                        <Link
                            :href="logout()"
                            as="button"
                            :class="
                                cn(
                                    buttonVariants({ variant: 'outline' }),
                                    'h-11 w-full rounded-xl font-bold',
                                )
                            "
                            data-test="mobile-logout-button"
                            @click="logoutFromMenu"
                        >
                            <LogOut class="mr-2 h-4 w-4" />
                            Keluar
                        </Link>
                    </template>
                    <template v-else>
                        <Link :href="login()" class="block">
                            <Button
                                class="h-11 w-full rounded-xl font-bold shadow-lg shadow-primary/25"
                            >
                                Masuk / Daftar
                            </Button>
                        </Link>
                        <p
                            v-if="canRegister"
                            class="text-center text-sm text-muted-foreground"
                        >
                            Belum punya akun?
                            <Link
                                :href="register()"
                                class="font-bold text-primary hover:underline"
                                >Daftar gratis</Link
                            >
                        </p>
                    </template>
                </div>
            </div>
        </Transition>

        <!-- Dims the page behind the phone menu; a tap closes it -->
        <div
            v-if="menuOpen"
            class="fixed inset-x-0 top-16 bottom-0 -z-10 bg-black/50 md:hidden"
            aria-hidden="true"
            @click="closeMenu"
        />
    </header>
</template>
