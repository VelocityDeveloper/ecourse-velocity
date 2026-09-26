<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';
import Heading from '@/components/Heading.vue';
import { Button } from '@/components/ui/button';
import { Badge } from '@/components/ui/badge';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Eye, Plus, Search, Trash2, Pencil } from '@lucide/vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { getInitials } from '@/composables/useInitials';
import userRoutes from '@/routes/users';

type UserRow = {
    id: number;
    name: string;
    email: string;
    role: string;
    avatar: string | null;
    headline: string | null;
    bio: string | null;
    email_verified_at: string | null;
    courses_count: number;
    created_at: string;
};

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dashboard', href: '/dashboard' },
            { title: 'Users', href: '/admin/users' },
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
    };
}>();

const search = ref(props.filters.search || '');
const selectedRole = ref(props.filters.role || '');

const viewedUser = ref<UserRow | null>(null);
const isDetailOpen = ref(false);

function showDetail(user: UserRow) {
    viewedUser.value = user;
    isDetailOpen.value = true;
}

function applyFilters() {
    router.get(
        '/admin/users',
        {
            search: search.value || undefined,
            role:
                selectedRole.value && selectedRole.value !== 'all'
                    ? selectedRole.value
                    : undefined,
        },
        { preserveState: true, replace: true },
    );
}

function deleteUser(id: number) {
    router.delete(`/admin/users/${id}`);
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

function formatDate(date: string) {
    return new Date(date).toLocaleDateString('id-ID', {
        day: 'numeric',
        month: 'short',
        year: 'numeric',
    });
}
</script>

<template>
    <Head title="User Management" />

    <div class="flex flex-col space-y-6">
        <div class="flex items-center justify-between">
            <Heading
                variant="small"
                title="User Management"
                description="Manage all users and their roles"
            />
            <Link href="/admin/users/create">
                <Button>
                    <Plus class="mr-2 h-4 w-4" />
                    Add User
                </Button>
            </Link>
        </div>

        <!-- Filters -->
        <div class="flex items-center gap-3">
            <div class="relative max-w-sm flex-1">
                <Search
                    class="absolute top-1/2 left-3 h-4 w-4 -translate-y-1/2 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    placeholder="Search by name or email..."
                    class="pl-9"
                    @keyup.enter="applyFilters"
                />
            </div>
            <Select v-model="selectedRole" @update:model-value="applyFilters">
                <SelectTrigger class="w-[180px]">
                    <SelectValue placeholder="All Roles" />
                </SelectTrigger>
                <SelectContent>
                    <SelectItem value="all">All Roles</SelectItem>
                    <SelectItem value="admin">Admin</SelectItem>
                    <SelectItem value="instructor">Instructor</SelectItem>
                    <SelectItem value="student">Student</SelectItem>
                </SelectContent>
            </Select>
            <Button variant="outline" @click="applyFilters">Search</Button>
        </div>

        <!-- Table -->
        <div class="rounded-lg border">
            <div class="overflow-x-auto">
                <table class="w-full caption-bottom text-sm">
                    <thead class="border-b">
                        <tr class="border-b transition-colors">
                            <th
                                class="h-12 w-16 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Photo
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Name
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Email
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Role
                            </th>
                            <th
                                class="h-12 px-4 text-left align-middle font-medium text-muted-foreground"
                            >
                                Joined
                            </th>
                            <th
                                class="h-12 px-4 text-right align-middle font-medium text-muted-foreground"
                            >
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-if="users.data.length === 0">
                            <td
                                colspan="6"
                                class="py-8 text-center text-muted-foreground"
                            >
                                No users found.
                            </td>
                        </tr>
                        <tr
                            v-for="user in users.data"
                            :key="user.id"
                            class="border-b transition-colors hover:bg-muted/50"
                        >
                            <td class="p-4 align-middle">
                                <Avatar
                                    class="size-9 overflow-hidden rounded-full"
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
                            </td>
                            <td class="p-4 align-middle font-medium">
                                {{ user.name }}
                            </td>
                            <td class="p-4 align-middle">{{ user.email }}</td>
                            <td class="p-4 align-middle">
                                <Badge :variant="roleBadgeVariant(user.role)">
                                    {{ user.role }}
                                </Badge>
                            </td>
                            <td class="p-4 align-middle">
                                {{ formatDate(user.created_at) }}
                            </td>
                            <td class="p-4 text-right align-middle">
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        :aria-label="`View ${user.name} details`"
                                        @click="showDetail(user)"
                                    >
                                        <Eye class="h-4 w-4" />
                                    </Button>
                                    <Link
                                        :href="`/admin/users/${user.id}/edit`"
                                    >
                                        <Button variant="ghost" size="sm">
                                            <Pencil class="h-4 w-4" />
                                        </Button>
                                    </Link>
                                    <Dialog
                                        v-if="
                                            user.id !==
                                            (usePage().props.auth as any).user
                                                .id
                                        "
                                    >
                                        <DialogTrigger as-child>
                                            <Button variant="ghost" size="sm">
                                                <Trash2
                                                    class="h-4 w-4 text-destructive"
                                                />
                                            </Button>
                                        </DialogTrigger>
                                        <DialogContent>
                                            <DialogHeader>
                                                <DialogTitle
                                                    >Delete User</DialogTitle
                                                >
                                                <DialogDescription>
                                                    Are you sure you want to
                                                    delete
                                                    <strong>{{
                                                        user.name
                                                    }}</strong
                                                    >? This action cannot be
                                                    undone.
                                                </DialogDescription>
                                            </DialogHeader>
                                            <DialogFooter class="gap-2">
                                                <DialogClose as-child>
                                                    <Button variant="secondary"
                                                        >Cancel</Button
                                                    >
                                                </DialogClose>
                                                <Button
                                                    variant="destructive"
                                                    @click="deleteUser(user.id)"
                                                >
                                                    Delete
                                                </Button>
                                            </DialogFooter>
                                        </DialogContent>
                                    </Dialog>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                    <tfoot class="border-t">
                        <tr>
                            <td
                                colspan="6"
                                class="h-12 px-4 text-sm text-muted-foreground"
                            >
                                Showing
                                {{
                                    (users.current_page - 1) * users.per_page +
                                    1
                                }}
                                to
                                {{
                                    Math.min(
                                        users.current_page * users.per_page,
                                        users.total,
                                    )
                                }}
                                of {{ users.total }} users
                            </td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- User detail -->
        <Dialog v-model:open="isDetailOpen">
            <DialogContent v-if="viewedUser" class="sm:max-w-lg">
                <DialogHeader class="sr-only">
                    <DialogTitle>{{ viewedUser.name }}</DialogTitle>
                    <DialogDescription>User profile details</DialogDescription>
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
                            {{ viewedUser.role }}
                        </Badge>
                    </div>
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
                        No bio yet.
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
                        <dt class="text-muted-foreground">Email verified</dt>
                        <dd class="text-right">
                            {{
                                viewedUser.email_verified_at
                                    ? formatDate(viewedUser.email_verified_at)
                                    : 'Not verified'
                            }}
                        </dd>
                    </div>
                    <div
                        v-if="viewedUser.role === 'instructor'"
                        class="flex items-center justify-between gap-4"
                    >
                        <dt class="text-muted-foreground">Courses</dt>
                        <dd class="text-right">
                            {{ viewedUser.courses_count }}
                        </dd>
                    </div>
                    <div class="flex items-center justify-between gap-4">
                        <dt class="text-muted-foreground">Joined</dt>
                        <dd class="text-right">
                            {{ formatDate(viewedUser.created_at) }}
                        </dd>
                    </div>
                </dl>

                <DialogFooter class="gap-2">
                    <Link :href="userRoutes.show(viewedUser.id)">
                        <Button variant="outline">Public Profile</Button>
                    </Link>
                    <Link :href="`/admin/users/${viewedUser.id}/edit`">
                        <Button>
                            <Pencil class="mr-2 h-4 w-4" />
                            Edit
                        </Button>
                    </Link>
                </DialogFooter>
            </DialogContent>
        </Dialog>

        <!-- Pagination -->
        <div
            v-if="users.last_page > 1"
            class="flex items-center justify-end gap-2"
        >
            <Button
                variant="outline"
                size="sm"
                :disabled="users.current_page <= 1"
                @click="
                    router.get(`/admin/users?page=${users.current_page - 1}`, {
                        preserveState: true,
                    })
                "
            >
                Previous
            </Button>
            <span class="text-sm text-muted-foreground">
                Page {{ users.current_page }} of {{ users.last_page }}
            </span>
            <Button
                variant="outline"
                size="sm"
                :disabled="users.current_page >= users.last_page"
                @click="
                    router.get(`/admin/users?page=${users.current_page + 1}`, {
                        preserveState: true,
                    })
                "
            >
                Next
            </Button>
        </div>
    </div>
</template>
