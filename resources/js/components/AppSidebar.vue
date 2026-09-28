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
import banners from '@/routes/admin/banners';
import adminOrders from '@/routes/admin/orders';
import paymentSettings from '@/routes/admin/payment-settings';
import posts from '@/routes/admin/posts';
import siteSettings from '@/routes/admin/settings';
import testimonials from '@/routes/admin/testimonials';
import transactions from '@/routes/admin/transactions';
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

    if (canManageCourses.value) {
        const salesChildren: NavItem[] = [
            {
                title: 'Pesanan & User Terdaftar',
                href: adminOrders.index(),
                activeFor: [adminOrders.index(), enrollments.index()],
            },
            { title: 'Transaksi', href: transactions.index() },
        ];

        if (isAdmin.value) {
            salesChildren.push({
                title: 'Pengaturan Pembayaran',
                href: paymentSettings.edit(),
            });
        }

        items.push({
            title: 'Penjualan',
            href: adminOrders.index(),
            icon: Wallet,
            items: salesChildren,
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
