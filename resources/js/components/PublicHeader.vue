<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Menu } from '@lucide/vue';
import { computed } from 'vue';
import AppearanceToggle from '@/components/AppearanceToggle.vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import AppWordmark from '@/components/AppWordmark.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sheet,
    SheetContent,
    SheetDescription,
    SheetHeader,
    SheetTitle,
    SheetTrigger,
} from '@/components/ui/sheet';
import UserMenuContent from '@/components/UserMenuContent.vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { getInitials } from '@/composables/useInitials';
import { dashboard, home, login, register } from '@/routes';
import catalogRoutes from '@/routes/catalog';
import learning from '@/routes/learning';

const page = usePage();
const user = computed(() => page.props.auth.user);
const canRegister = computed(() => page.props.canRegister);
const isStaff = computed(
    () => user.value?.role === 'admin' || user.value?.role === 'instructor',
);

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();

const navLinks = computed(() => [
    { label: 'Home', href: home().url, active: isCurrentUrl(home()) },
    {
        label: 'Courses',
        href: catalogRoutes.index().url,
        active: isCurrentOrParentUrl(catalogRoutes.index()),
    },
    { label: 'Instructors', href: '/#instructors', active: false },
    { label: 'How it works', href: '/#how-it-works', active: false },
]);
</script>

<template>
    <header
        class="sticky top-0 z-40 border-b bg-background/80 backdrop-blur supports-[backdrop-filter]:bg-background/60"
    >
        <div
            class="mx-auto flex h-16 w-full max-w-6xl items-center justify-between gap-4 px-4 sm:px-6"
        >
            <div class="flex items-center gap-2">
                <Sheet>
                    <SheetTrigger as-child>
                        <Button
                            variant="ghost"
                            size="icon"
                            class="md:hidden"
                            aria-label="Open menu"
                        >
                            <Menu class="h-5 w-5" />
                        </Button>
                    </SheetTrigger>
                    <SheetContent side="left" class="w-72">
                        <SheetHeader>
                            <SheetTitle class="flex items-center gap-2">
                                <AppLogoIcon class="size-5" />
                                <AppWordmark />
                            </SheetTitle>
                            <SheetDescription class="sr-only">
                                Site navigation
                            </SheetDescription>
                        </SheetHeader>
                        <nav class="flex flex-col gap-1 px-4">
                            <a
                                v-for="link in navLinks"
                                :key="link.label"
                                :href="link.href"
                                class="rounded-md px-3 py-2 text-sm hover:bg-muted"
                                :class="{
                                    'bg-accent font-medium text-accent-foreground':
                                        link.active,
                                }"
                            >
                                {{ link.label }}
                            </a>
                            <Link
                                v-if="user && !isStaff"
                                :href="learning.dashboard()"
                                class="rounded-md px-3 py-2 text-sm hover:bg-muted"
                            >
                                My Learning
                            </Link>
                            <Link
                                v-if="isStaff"
                                :href="dashboard()"
                                class="rounded-md px-3 py-2 text-sm hover:bg-muted"
                            >
                                Dashboard
                            </Link>
                        </nav>
                    </SheetContent>
                </Sheet>

                <Link :href="home()" class="flex items-center gap-2">
                    <span
                        class="flex size-8 items-center justify-center rounded-md bg-primary text-primary-foreground"
                    >
                        <AppLogoIcon class="size-5" />
                    </span>
                    <AppWordmark class="hidden sm:inline" />
                </Link>
            </div>

            <nav
                class="hidden items-center gap-6 text-sm md:flex"
                aria-label="Main"
            >
                <a
                    v-for="link in navLinks"
                    :key="link.label"
                    :href="link.href"
                    class="transition-colors hover:text-foreground"
                    :class="
                        link.active
                            ? 'font-medium text-primary'
                            : 'text-muted-foreground'
                    "
                    :aria-current="link.active ? 'page' : undefined"
                >
                    {{ link.label }}
                </a>
            </nav>

            <div class="flex items-center gap-2">
                <AppearanceToggle />

                <template v-if="user">
                    <Link
                        v-if="isStaff"
                        :href="dashboard()"
                        class="hidden sm:block"
                    >
                        <Button size="sm">Dashboard</Button>
                    </Link>
                    <Link
                        v-else
                        :href="learning.dashboard()"
                        class="hidden sm:block"
                    >
                        <Button size="sm" variant="outline">My Learning</Button>
                    </Link>

                    <DropdownMenu>
                        <DropdownMenuTrigger as-child>
                            <Button
                                variant="ghost"
                                size="icon"
                                class="rounded-full"
                                aria-label="Open account menu"
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
                                    <AvatarFallback class="text-xs">
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
                    <Link :href="login()">
                        <Button variant="ghost" size="sm">Log in</Button>
                    </Link>
                    <Link v-if="canRegister" :href="register()">
                        <Button size="sm">Register</Button>
                    </Link>
                </template>
            </div>
        </div>
    </header>
</template>
