<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    ClipboardCheck,
    Compass,
    FolderGit2,
    GraduationCap,
    LayoutGrid,
    Users,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import categories from '@/routes/admin/categories';
import lessons from '@/routes/lessons';
import quizzes from '@/routes/quizzes';
import users from '@/routes/admin/users';
import catalog from '@/routes/catalog';
import courses from '@/routes/courses';
import enrollments from '@/routes/enrollments';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage<{ auth: Auth }>();
const role = computed(() => page.props.auth.user?.role);
const isAdmin = computed(() => role.value === 'admin');
const canManageCourses = computed(
    () => isAdmin.value || role.value === 'instructor',
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (canManageCourses.value) {
        const courseChildren: NavItem[] = [
            { title: 'All Courses', href: courses.index() },
            { title: 'Lessons', href: lessons.index() },
            { title: 'Quizzes', href: quizzes.index() },
        ];

        if (isAdmin.value) {
            courseChildren.push({
                title: 'Categories',
                href: categories.index(),
            });
        }

        items.push({
            title: 'Courses',
            href: courses.index(),
            icon: GraduationCap,
            items: courseChildren,
        });
    }

    if (canManageCourses.value) {
        items.push({
            title: 'Enrollments',
            href: enrollments.index(),
            icon: ClipboardCheck,
        });
    }

    items.push({
        title: 'View Site',
        href: catalog.index(),
        icon: Compass,
    });

    if (isAdmin.value) {
        items.push({
            title: 'Users',
            href: users.index(),
            icon: Users,
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: 'https://laravel.com/docs/starter-kits#vue',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
