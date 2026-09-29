<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Inbox, Pencil, Search, Star, Wallet } from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { getInitials } from '@/composables/useInitials';
import { formatDate } from '@/lib/course';
import financeInstructors from '@/routes/admin/finance/instructors';
import instructorApplications from '@/routes/admin/instructor-applications';
import instructorRoutes from '@/routes/admin/instructors';
import adminUsers from '@/routes/admin/users';
import type { InstructorPerson, Paginated } from '@/types';

const props = defineProps<{
    instructors: Paginated<InstructorPerson>;
    filters: { search?: string; sort?: string };
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Instruktur', href: '/dasbor/instruktur' },
        ],
    },
});

const SORTS = [
    { value: 'name', label: 'Nama (A–Z)' },
    { value: 'newest', label: 'Terbaru bergabung' },
    { value: 'courses', label: 'Kursus terbanyak' },
    { value: 'students', label: 'Siswa terbanyak' },
];

const search = ref(props.filters.search ?? '');
const sort = ref(props.filters.sort ?? 'name');

const query = computed(() => ({
    search: search.value || undefined,
    sort: sort.value === 'name' ? undefined : sort.value,
}));

const page = usePage();
const pendingApplications = computed(
    () => page.props.pendingInstructorApplications,
);

function apply(): void {
    router.get(instructorRoutes.index().url, query.value, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
}

function goToPage(number: number): void {
    router.get(
        instructorRoutes.index().url,
        { ...query.value, page: number },
        { preserveState: true },
    );
}
</script>

<template>
    <div class="flex flex-col space-y-6">
        <Head title="Instruktur" />

        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Instruktur"
                description="Semua instruktur beserta kursus, siswa, dan rating. Pendapatan dan saldonya ada di Keuangan."
            />
            <div class="flex flex-wrap gap-2">
                <Link :href="instructorApplications.index()">
                    <Button variant="outline" size="sm">
                        <Inbox class="mr-2 h-4 w-4" />
                        Pengajuan
                        <span
                            v-if="pendingApplications > 0"
                            class="ml-1.5 rounded-full bg-primary px-1.5 text-xs font-semibold text-primary-foreground tabular-nums"
                            >{{ pendingApplications }}</span
                        >
                    </Button>
                </Link>
                <Link :href="financeInstructors.index()">
                    <Button variant="outline" size="sm">
                        <Wallet class="mr-2 h-4 w-4" />
                        Keuangan instruktur
                    </Button>
                </Link>
            </div>
        </div>

        <form
            class="grid gap-3 sm:grid-cols-[1fr_200px_auto]"
            @submit.prevent="apply"
        >
            <div class="relative">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Cari nama, email, atau keahlian..."
                    aria-label="Cari instruktur"
                    class="pl-9"
                />
            </div>
            <Select v-model="sort" @update:model-value="apply">
                <SelectTrigger class="w-full" aria-label="Urutkan">
                    <SelectValue />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem
                        v-for="option in SORTS"
                        :key="option.value"
                        :value="option.value"
                    >
                        {{ option.label }}
                    </SelectItem>
                </SelectContent>
            </Select>
            <Button type="submit" variant="secondary">Cari</Button>
        </form>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Instruktur ({{ instructors.total }})
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Kursus
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Siswa
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Rating
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                Bergabung
                            </th>
                            <th class="h-12 px-4">
                                <span class="sr-only">Aksi</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="instructors.data.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Tidak ada instruktur yang cocok.
                            </td>
                        </tr>
                        <tr
                            v-for="row in instructors.data"
                            :key="row.id"
                            class="border-b transition-colors last:border-0 hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex min-w-56 items-center gap-3">
                                    <Avatar class="size-10 shrink-0">
                                        <AvatarImage
                                            v-if="row.avatar"
                                            :src="row.avatar"
                                            :alt="row.name"
                                        />
                                        <AvatarFallback
                                            class="bg-primary/10 text-sm font-semibold text-primary"
                                        >
                                            {{ getInitials(row.name) }}
                                        </AvatarFallback>
                                    </Avatar>
                                    <span class="min-w-0">
                                        <span class="block font-medium"
                                            >{{ row.name }}
                                            <span
                                                v-if="row.suspended"
                                                class="ml-1 rounded-full bg-red-100 px-1.5 py-0.5 text-[11px] font-medium text-red-800 dark:bg-red-500/15 dark:text-red-300"
                                                >Ditangguhkan</span
                                            ></span
                                        >
                                        <span
                                            class="block truncate text-xs text-muted-foreground"
                                            >{{ row.email }}</span
                                        >
                                        <span
                                            v-if="row.headline"
                                            class="block max-w-64 truncate text-xs text-muted-foreground"
                                            >{{ row.headline }}</span
                                        >
                                    </span>
                                </div>
                            </td>
                            <td
                                class="p-4 text-right align-middle whitespace-nowrap tabular-nums"
                            >
                                {{ row.courses_count }}
                                <span
                                    class="block text-xs text-muted-foreground"
                                    >{{
                                        row.published_courses_count
                                    }}
                                    terbit</span
                                >
                            </td>
                            <td
                                class="p-4 text-right align-middle tabular-nums"
                            >
                                {{ row.students_count }}
                            </td>
                            <td
                                class="p-4 text-right align-middle whitespace-nowrap tabular-nums"
                            >
                                <span
                                    v-if="row.rating !== null"
                                    class="inline-flex items-center gap-1"
                                >
                                    <Star
                                        class="size-3.5 fill-rating text-rating"
                                    />
                                    {{ row.rating.toFixed(1) }}
                                </span>
                                <span v-else class="text-muted-foreground"
                                    >-</span
                                >
                            </td>
                            <td
                                class="p-4 align-middle whitespace-nowrap text-muted-foreground"
                            >
                                {{
                                    row.joined_at
                                        ? formatDate(row.joined_at)
                                        : '-'
                                }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div class="flex justify-end gap-1">
                                    <Link
                                        :href="
                                            financeInstructors.show(row.slug)
                                        "
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Wallet class="mr-1.5 size-4" />
                                            Keuangan
                                        </Button>
                                    </Link>
                                    <Link :href="adminUsers.edit(row.slug)">
                                        <Button
                                            variant="ghost"
                                            size="icon"
                                            class="size-8"
                                            :aria-label="`Ubah akun ${row.name}`"
                                        >
                                            <Pencil class="size-4" />
                                        </Button>
                                    </Link>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <div
            v-if="instructors.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="instructors.current_page <= 1"
                @click="goToPage(instructors.current_page - 1)"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ instructors.current_page }} dari
                {{ instructors.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="instructors.current_page >= instructors.last_page"
                @click="goToPage(instructors.current_page + 1)"
            >
                Berikutnya
            </Button>
        </div>
    </div>
</template>
