<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    Compass,
    GraduationCap,
    LayoutGrid,
    Newspaper,
    Settings,
    Users,
    Wallet,
    Presentation,
    UsersRound,
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
import instructorApplications from '@/routes/admin/instructor-applications';
import instructors from '@/routes/admin/instructors';
import lessons from '@/routes/lessons';
import quizzes from '@/routes/quizzes';
import banners from '@/routes/admin/banners';
import finance from '@/routes/admin/finance';
import posts from '@/routes/admin/posts';
import siteSettings from '@/routes/admin/settings';
import testimonials from '@/routes/admin/testimonials';
import users from '@/routes/admin/users';
import withdrawals from '@/routes/admin/withdrawals';
import catalog from '@/routes/catalog';
import courses from '@/routes/courses';
import enrollments from '@/routes/enrollments';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage<{
    auth: Auth;
    pendingInstructorApplications: number;
    pendingWithdrawals: number;
    pendingOrders: number;
}>();
const role = computed(() => page.props.auth.user?.role);
const isAdmin = computed(() => role.value === 'admin');
const canManageCourses = computed(
    () => isAdmin.value || role.value === 'instructor',
);

const mainNavItems = computed<NavItem[]>(() => {
    const items: NavItem[] = [
        {
            title: 'Dasbor',
            href: dashboard(),
            icon: LayoutGrid,
        },
    ];

    if (canManageCourses.value) {
        const courseChildren: NavItem[] = [
            { title: 'Semua Kursus', href: courses.index() },
            { title: 'Materi', href: lessons.index() },
            { title: 'Kuis', href: quizzes.index() },
        ];

        if (isAdmin.value) {
            courseChildren.push({
                title: 'Kategori',
                href: categories.index(),
            });
        }

        items.push({
            title: 'Kursus',
            href: courses.index(),
            icon: GraduationCap,
            items: courseChildren,
        });
    }

    // Every money page sits behind this one entry, as tabs (FinanceNav).
    if (canManageCourses.value) {
        items.push({
            title: 'Keuangan',
            href: isAdmin.value ? finance.index() : withdrawals.index(),
            icon: Wallet,
            badge: page.props.pendingWithdrawals + page.props.pendingOrders,
            activeFor: ['/dasbor/keuangan'],
        });
    }

    if (isAdmin.value) {
        const pending = page.props.pendingInstructorApplications;

        items.push({
            title: 'Instruktur',
            href: instructors.index(),
            icon: Presentation,
            badge: pending,
            items: [
                {
                    title: 'Semua Instruktur',
                    href: instructors.index(),
                },
                {
                    title: 'Pengajuan',
                    href: instructorApplications.index(),
                    badge: pending,
                },
            ],
        });
    }

    if (canManageCourses.value) {
        items.push({
            title: 'Siswa',
            href: enrollments.index(),
            icon: UsersRound,
        });
    }

    if (isAdmin.value) {
        items.push({
            title: 'Blog',
            href: posts.index(),
            icon: Newspaper,
        });
        items.push({
            title: 'Pengguna',
            href: users.index(),
            icon: Users,
        });
        items.push({
            title: 'Pengaturan Situs',
            href: siteSettings.edit('identitas'),
            icon: Settings,
            activeFor: [
                '/dasbor/pengaturan-situs',
                banners.index(),
                testimonials.index(),
            ],
        });
    }

    return items;
});

const footerNavItems: NavItem[] = [
    {
        title: 'Lihat Situs',
        href: catalog.index(),
        icon: Compass,
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
