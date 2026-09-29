<script setup lang="ts">
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    Ban,
    Eye,
    LockOpen,
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    ShieldOff,
    ShoppingCart,
    Trash2,
    UserCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import adminUsers from '@/routes/admin/users';
import userRoutes from '@/routes/users';

type UserRow = {
    id: number;
    name: string;
    slug: string;
    email: string;
    role: string;
    avatar: string | null;
    headline: string | null;
    bio: string | null;
    email_verified_at: string | null;
    suspended_at: string | null;
    suspension_reason: string | null;
    purchase_blocked_at: string | null;
    purchase_block_reason: string | null;
    courses_count: number;
    paid_orders_count: number;
    created_at: string;
};

type StatusFilter = 'all' | 'active' | 'suspended' | 'purchase_blocked';

type Action =
    | 'suspend'
    | 'unsuspend'
    | 'block-purchases'
    | 'unblock-purchases'
    | 'delete';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Pengguna', href: '/dasbor/pengguna' },
        ],
    },
});

const props = defineProps<{
    users: {
        data: UserRow[];
        current_page: number;
        last_page: number;
        per_page: number;
        total: number;
    };
    filters: {
        search?: string;
        role?: string;
        status?: StatusFilter;
    };
    restrictionCounts: { suspended: number; purchase_blocked: number };
}>();

const page = usePage();
const currentUserId = computed(() => page.props.auth.user?.id);

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || 'all');
const status = computed<StatusFilter>(() => props.filters.status ?? 'all');

const statusTabs = computed(() => [
    { value: 'all', label: 'Semua' },
    { value: 'active', label: 'Aktif' },
    {
        value: 'suspended',
        label: 'Ditangguhkan',
        count: props.restrictionCounts.suspended,
    },
    {
        value: 'purchase_blocked',
        label: 'Pembelian dibatasi',
        count: props.restrictionCounts.purchase_blocked,
    },
]);

function visit(overrides: Record<string, unknown> = {}) {
    router.get(
        adminUsers.index().url,
        {
            search: search.value || undefined,
            role:
                selectedRole.value && selectedRole.value !== 'all'
                    ? selectedRole.value
                    : undefined,
            status: status.value !== 'all' ? status.value : undefined,
            ...overrides,
        },
        { preserveState: true, preserveScroll: true, replace: true },
    );
}

const viewedUser = ref<UserRow | null>(null);
const isDetailOpen = ref(false);

function showDetail(user: UserRow) {
    viewedUser.value = user;
    isDetailOpen.value = true;
}

// Accounts with courses or paid orders keep their history: they can only be suspended.
function canDelete(user: UserRow): boolean {
    return user.courses_count === 0 && user.paid_orders_count === 0;
}

const pending = ref<{ user: UserRow; action: Action } | null>(null);
const actionForm = useForm({ reason: '' });

function open(user: UserRow, action: Action) {
    actionForm.reset();
    actionForm.clearErrors();
    pending.value = { user, action };
}

type ActionDialog = {
    title: string;
    description: string;
    reason?: string;
    confirm: string | null;
    destructive: boolean;
};

const dialog = computed<ActionDialog | null>(() => {
    if (!pending.value) {
        return null;
    }

    const { user, action } = pending.value;

    switch (action) {
        case 'suspend':
            return {
                title: `Tangguhkan ${user.name}?`,
                description:
                    'Akun tidak bisa masuk dan langsung keluar dari semua perangkat. Data, kursus, dan riwayat pembelian tetap tersimpan, dan akun bisa diaktifkan kembali kapan saja.',
                reason: 'Alasan penangguhan (opsional, hanya terlihat admin)',
                confirm: 'Tangguhkan',
                destructive: true,
            };
        case 'unsuspend':
            return {
                title: `Aktifkan kembali ${user.name}?`,
                description: 'Akun bisa masuk lagi seperti biasa.',
                confirm: 'Aktifkan',
                destructive: false,
            };
        case 'block-purchases':
            return {
                title: `Batasi pembelian ${user.name}?`,
                description:
                    'Akun tetap bisa masuk dan belajar di kursus yang sudah dimiliki, tetapi tidak bisa membeli kursus berbayar baru.',
                reason: 'Alasan pembatasan (opsional, hanya terlihat admin)',
                confirm: 'Batasi pembelian',
                destructive: true,
            };
        case 'unblock-purchases':
            return {
                title: `Buka pembatasan ${user.name}?`,
                description: 'Akun bisa membeli kursus lagi.',
                confirm: 'Buka pembatasan',
                destructive: false,
            };
        default:
            return canDelete(user)
                ? {
                      title: `Hapus ${user.name}?`,
                      description:
                          'Akun beserta pendaftaran kursus, progres, catatan, dan pesanan yang belum dibayar akan dihapus permanen. Tindakan ini tidak bisa dibatalkan.',
                      confirm: 'Hapus permanen',
                      destructive: true,
                  }
                : {
                      title: `${user.name} tidak bisa dihapus`,
                      description: `Akun ini memiliki ${user.courses_count} kursus dan ${user.paid_orders_count} pembelian yang sudah dibayar. Menghapusnya akan ikut menghapus data tersebut, jadi tangguhkan akunnya saja.`,
                      confirm: user.suspended_at ? null : 'Tangguhkan saja',
                      destructive: true,
                  };
    }
});

function submitAction() {
    if (!pending.value) {
        return;
    }

    const { user, action } = pending.value;
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            pending.value = null;
            isDetailOpen.value = false;
        },
    };

    switch (action) {
        case 'suspend':
            actionForm.post(adminUsers.suspend(user.slug).url, options);
            break;
        case 'unsuspend':
            actionForm.delete(adminUsers.unsuspend(user.slug).url, options);
            break;
        case 'block-purchases':
            actionForm.post(adminUsers.blockPurchases(user.slug).url, options);
            break;
        case 'unblock-purchases':
            actionForm.delete(
                adminUsers.unblockPurchases(user.slug).url,
                options,
            );
            break;
        default:
            if (canDelete(user)) {
                actionForm.delete(adminUsers.destroy(user.slug).url, options);
            } else {
                open(user, 'suspend');
            }
    }
}

function roleBadgeVariant(role: string) {
    switch (role) {
        case 'admin':
            return 'destructive' as const;
        case 'instructor':
            return 'secondary' as const;
        default:
            return 'outline' as const;
    }
}

function roleLabel(role: string): string {
    const labels: Record<string, string> = {
        admin: 'Admin',
        instructor: 'Instruktur',
        student: 'Siswa',
    };

    return labels[role] ?? role;
}

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="Manajemen Pengguna" />

    <div class="flex flex-col space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Manajemen Pengguna"
                description="Kelola pengguna, peran, dan izin akunnya: tangguhkan, batasi pembelian, atau hapus."
            />
            <Link :href="adminUsers.create()">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Tambah Pengguna
                </Button>
            </Link>
        </div>

        <div class="flex flex-wrap gap-1 self-start rounded-lg border p-1">
            <button
                v-for="tab in statusTabs"
                :key="tab.value"
                type="button"
                class="flex items-center gap-2 rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                :class="
                    status === tab.value
                        ? 'bg-primary text-primary-foreground'
                        : 'text-muted-foreground hover:bg-muted'
                "
                @click="
                    visit({
                        status: tab.value === 'all' ? undefined : tab.value,
                        page: undefined,
                    })
                "
            >
                {{ tab.label }}
                <span
                    v-if="tab.count !== undefined"
                    class="rounded-full px-1.5 text-xs tabular-nums"
                    :class="
                        status === tab.value
                            ? 'bg-primary-foreground/20'
                            : 'bg-muted'
                    "
                    >{{ tab.count }}</span
                >
            </button>
        </div>

        <form
            class="flex flex-wrap items-center gap-3"
            @submit.prevent="visit({ page: undefined })"
        >
            <div class="relative min-w-56 flex-1 sm:max-w-sm">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    type="search"
                    placeholder="Cari nama atau email..."
                    class="pl-9"
                />
            </div>
            <Select
                v-model="selectedRole"
                @update:model-value="visit({ page: undefined })"
            >
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="Semua Peran" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">Semua Peran</SelectItem>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="instructor">Instruktur</SelectItem>
                    <SelectItem value="student">Siswa</SelectItem>
                </SelectContent>
            </Select>
            <Button type="submit" variant="outline">Cari</Button>
        </form>

        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr>
                            <th
                                v-for="heading in [
                                    'Pengguna',
                                    'Peran',
                                    'Status',
                                    'Bergabung',
                                ]"
                                :key="heading"
                                class="h-12 px-4 text-left align-middle font-medium whitespace-nowrap text-muted-foreground"
                            >
                                {{ heading }}
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Aksi
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="5"
                                class="py-8 text-center text-muted-foreground"
                            >
                                Pengguna tidak ditemukan.
                            </td>
                        </tr>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b transition-colors hover:bg-muted/50"
                            :class="{
                                'bg-red-50/50 dark:bg-red-500/5':
                                    user.suspended_at,
                            }"
                        >
                            <td class="p-4 align-middle">
                                <div class="flex items-center gap-3">
                                    <Avatar
                                        class="size-9 shrink-0 overflow-hidden rounded-full"
                                        :class="{
                                            'opacity-50': user.suspended_at,
                                        }"
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
                                    <div class="min-w-0">
                                        <p class="font-medium">
                                            {{ user.name }}
                                        </p>
                                        <p
                                            class="truncate text-xs text-muted-foreground"
                                        >
                                            {{ user.email }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="p-4 align-middle">
                                <Badge :variant="roleBadgeVariant(user.role)">
                                    {{ roleLabel(user.role) }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                <div class="flex flex-wrap gap-1">
                                    <span
                                        v-if="user.suspended_at"
                                        class="inline-flex items-center gap-1 rounded-full bg-red-100 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-red-800 dark:bg-red-500/15 dark:text-red-300"
                                        :title="
                                            user.suspension_reason ?? undefined
                                        "
                                    >
                                        <Ban class="size-3" />
                                        Ditangguhkan
                                    </span>
                                    <span
                                        v-if="user.purchase_blocked_at"
                                        class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium whitespace-nowrap text-amber-800 dark:bg-amber-500/15 dark:text-amber-300"
                                        :title="
                                            user.purchase_block_reason ??
                                            undefined
                                        "
                                    >
                                        <ShoppingCart class="size-3" />
                                        Pembelian dibatasi
                                    </span>
                                    <span
                                        v-if="
                                            !user.suspended_at &&
                                            !user.purchase_blocked_at
                                        "
                                        class="rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-500/15 dark:text-emerald-300"
                                        >Aktif</span
                                    >
                                </div>
                            </td>
                            <td class="p-4 align-middle whitespace-nowrap">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div
                                    class="flex items-center justify-end gap-1"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`Lihat detail ${user.name}`"
                                        @click="showDetail(user)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <Link :href="adminUsers.edit(user.slug)">
                                        <Button
                                            variant="ghost"
                                            size="sm"
                                            :aria-label="`Ubah ${user.name}`"
                                        >
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <DropdownMenu
                                        v-if="user.id !== currentUserId"
                                    >
                                        <DropdownMenuTrigger as-child>
                                            <Button
                                                variant="ghost"
                                                size="sm"
                                                :aria-label="`Izin akun ${user.name}`"
                                            >
                                                <MoreHorizontal
                                                    class="h-4 w-4"
                                                />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent
                                            align="end"
                                            class="w-60"
                                        >
                                            <DropdownMenuLabel
                                                >Izin akun</DropdownMenuLabel
                                            >
                                            <DropdownMenuItem
                                                v-if="user.suspended_at"
                                                @select="
                                                    open(user, 'unsuspend')
                                                "
                                            >
                                                <UserCheck />
                                                Aktifkan kembali
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-else
                                                @select="open(user, 'suspend')"
                                            >
                                                <Ban />
                                                Tangguhkan akun
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-if="user.purchase_blocked_at"
                                                @select="
                                                    open(
                                                        user,
                                                        'unblock-purchases',
                                                    )
                                                "
                                            >
                                                <LockOpen />
                                                Buka pembatasan pembelian
                                            </DropdownMenuItem>
                                            <DropdownMenuItem
                                                v-else
                                                @select="
                                                    open(
                                                        user,
                                                        'block-purchases',
                                                    )
                                                "
                                            >
                                                <ShieldOff />
                                                Batasi pembelian
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                variant="destructive"
                                                @select="open(user, 'delete')"
                                            >
                                                <Trash2 />
                                                Hapus pengguna
                                            </DropdownMenuItem>
                                        </DropdownMenuContent>
                                    </DropdownMenu>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot v-if="users.total > 0" class="border-t">
                        <tr>
                            <td
                                colspan="5"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                Menampilkan
                                {{
                                    (users.current_page - 1) * users.per_page +
                                    1
                                }}–{{
                                    Math.min(
                                        users.current_page * users.per_page,
                                        users.total,
                                    )
                                }}
                                dari {{ users.total }} pengguna
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Pagination -->
        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="users.current_page <= 1"
                @click="visit({ page: users.current_page - 1 })"
            >
                Sebelumnya
            </Button>
            <span class="text-sm text-muted-foreground">
                Halaman {{ users.current_page }} dari {{ users.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="users.current_page >= users.last_page"
                @click="visit({ page: users.current_page + 1 })"
            >
                Berikutnya
            </Button>
        </div>

        <!-- Permission action -->
        <Dialog
            :open="pending !== null"
            @update:open="(value) => !value && (pending = null)"
        >
            <DialogContent v-if="pending && dialog">
                <DialogHeader>
                    <DialogTitle>{{ dialog.title }}</DialogTitle>
                    <DialogDescription>{{
                        dialog.description
                    }}</DialogDescription>
                </DialogHeader>

                <form
                    id="user-action-form"
                    class="grid gap-2"
                    @submit.prevent="submitAction"
                >
                    <template v-if="dialog.reason">
                        <Label for="reason">{{ dialog.reason }}</Label>
                        <Textarea
                            id="reason"
                            v-model="actionForm.reason"
                            rows="3"
                            maxlength="500"
                        />
                        <InputError :message="actionForm.errors.reason" />
                    </template>
                </form>

                <DialogFooter class="gap-2">
                    <Button variant="outline" @click="pending = null"
                        >Batal</Button
                    >
                    <Button
                        v-if="dialog.confirm"
                        type="submit"
                        form="user-action-form"
                        :variant="
                            dialog.destructive ? 'destructive' : 'default'
                        "
                        :disabled="actionForm.processing"
                    >
                        {{ dialog.confirm }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- User detail -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent v-if="viewedUser" class="sm:max-w-lg">
                <DialogHeader class="sr-only">
                    <DialogTitle>{{ viewedUser.name }}</DialogTitle>
                    <DialogDescription
                        >Detail profil pengguna</DialogDescription
                    >
                </DialogHeader>

                <div class="flex flex-col items-center gap-3 text-center">
                    <Avatar class="size-24 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="viewedUser.avatar"
                            :src="viewedUser.avatar"
                            :alt="viewedUser.name"
                        />
                        <AvatarFallback class="text-2xl">
                            {{ getInitials(viewedUser.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="flex flex-col items-center gap-1">
                        <h2 class="text-lg font-semibold">
                            {{ viewedUser.name }}
                        </h2>
                        <p
                            v-if="viewedUser.headline"
                            class="text-sm text-muted-foreground"
                        >
                            {{ viewedUser.headline }}
                        </p>
                        <Badge
                            :variant="roleBadgeVariant(viewedUser.role)"
                            class="mt-1"
                        >
                            {{ roleLabel(viewedUser.role) }}
                        </Badge>
                    </div>
                </div>

                <div
                    v-if="
                        viewedUser.suspended_at ||
                        viewedUser.purchase_blocked_at
                    "
                    class="space-y-2 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-900 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
                >
                    <p v-if="viewedUser.suspended_at">
                        <span class="font-semibold"
                            >Ditangguhkan sejak
                            {{ formatDate(viewedUser.suspended_at) }}.</span
                        >
                        {{ viewedUser.suspension_reason }}
                    </p>
                    <p v-if="viewedUser.purchase_blocked_at">
                        <span class="font-semibold"
                            >Pembelian dibatasi sejak
                            {{
                                formatDate(viewedUser.purchase_blocked_at)
                            }}.</span
                        >
                        {{ viewedUser.purchase_block_reason }}
                    </p>
                </div>

                <div class="flex flex-col gap-1">
                    <h3 class="text-sm font-medium">Bio</h3>
                    <p
                        v-if="viewedUser.bio"
                        class="max-h-40 overflow-y-auto text-sm leading-relaxed whitespace-pre-line"
                    >
                        {{ viewedUser.bio }}
                    </p>
                    <p v-else class="text-sm text-muted-foreground">
                        Belum ada bio.
                    </p>
                </div>

                <dl class="space-y-2 rounded-lg border p-4 text-sm">
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Email</dt>
                        <dd class="truncate text-right">
                            {{ viewedUser.email }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">
                            Email terverifikasi
                        </dt>
                        <dd class="text-right">
                            {{
                                viewedUser.email_verified_at
                                    ? formatDate(viewedUser.email_verified_at)
                                    : 'Belum terverifikasi'
                            }}
                        </dd>
                    </div>
                    <div
                        v-if="viewedUser.role === 'instructor'"
                        class="flex items-center justify-between gap-4"
                    >
                        <dt class="text-muted-foreground">Kursus</dt>
                        <dd class="text-right">
                            {{ viewedUser.courses_count }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Pembelian dibayar</dt>
                        <dd class="text-right">
                            {{ viewedUser.paid_orders_count }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Bergabung</dt>
                        <dd class="text-right">
                            {{ formatDate(viewedUser.created_at) }}
                        </dd>
                    </div>
                </dl>

                <DialogFooter class="gap-2">
                    <Link :href="userRoutes.show(viewedUser.slug)">
                        <Button variant="outline">Profil Publik</Button>
                    </Link>
                    <Link :href="adminUsers.edit(viewedUser.slug)">
                        <Button>
                            <Pencil class="mr-2 h-4 w-4" />
                            Ubah
                        </Button>
                    </Link>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>
