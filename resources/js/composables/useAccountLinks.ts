import {
    BookMarked,
    GraduationCap,
    Heart,
    LayoutGrid,
    Presentation,
    SquareUser,
    Wallet,
} from '@lucide/vue';
import type { Component } from 'vue';
import { dashboard } from '@/routes';
import finance from '@/routes/admin/finance';
import withdrawals from '@/routes/admin/withdrawals';
import courses from '@/routes/courses';
import instructorApplications from '@/routes/instructor-applications';
import learning from '@/routes/learning';
import myCourses from '@/routes/my-courses';
import users from '@/routes/users';
import type { User } from '@/types';

export type AccountLink = {
    label: string;
    href: string;
    icon: Component;
    // Shows the wishlist count beside the link.
    wishlist?: boolean;
};

/**
 * The shortcuts in the account menu, which depend on who is logged in:
 * students get their learning pages, staff get their teaching pages.
 * "Pengaturan" and "Keluar" are added by the menus themselves.
 */
export function accountLinks(user: User): AccountLink[] {
    const learningLinks: AccountLink[] = [
        {
            label: 'Belajar Saya',
            href: learning.dashboard().url,
            icon: GraduationCap,
        },
        {
            label: 'Kursus Saya',
            href: myCourses.index().url,
            icon: BookMarked,
        },
        {
            label: 'Wishlist',
            href: learning.wishlist().url,
            icon: Heart,
            wishlist: true,
        },
    ];

    if (user.role === 'student') {
        return [
            ...learningLinks,
            {
                label: 'Jadi Instruktur',
                href: instructorApplications.create().url,
                icon: Presentation,
            },
        ];
    }

    const links: AccountLink[] = [
        { label: 'Dasbor', href: dashboard().url, icon: LayoutGrid },
        {
            label:
                user.role === 'instructor'
                    ? 'Kursus yang Saya Ajar'
                    : 'Kelola Kursus',
            href: courses.index().url,
            icon: BookMarked,
        },
        {
            label: 'Keuangan',
            href:
                user.role === 'admin'
                    ? finance.index().url
                    : withdrawals.index().url,
            icon: Wallet,
        },
    ];

    if (user.role === 'instructor') {
        links.push({
            label: 'Profil Publik Saya',
            href: users.show(user.slug).url,
            icon: SquareUser,
        });

        // Instructors can take other instructors' courses too.
        links.push(...learningLinks);
    }

    return links;
}
