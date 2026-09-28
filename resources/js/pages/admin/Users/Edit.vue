<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive, ref } from 'vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import PasswordInput from '@/components/PasswordInput.vue';
import adminUsers from '@/routes/admin/users';

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Dasbor', href: '/dasbor' },
            { title: 'Pengguna', href: '/dasbor/pengguna' },
            { title: 'Ubah Pengguna', href: '#' },
        ],
    },
});

const props = defineProps<{
    user: {
        id: number;
        slug: string;
        name: string;
        email: string;
        role: string;
    };
}>();

const form = reactive({
    name: props.user.name,
    email: props.user.email,
    role: props.user.role,
    password: '',
    password_confirmation: '',
});

const errors = ref<Record<string, string>>({});
const processing = ref(false);

function submit() {
    processing.value = true;
    errors.value = {};

    router.put(adminUsers.update(props.user.slug).url, form, {
        onError: (err) => {
            errors.value = err;
        },
        onFinish: () => {
            processing.value = false;
        },
    });
}
</script>

<template>
    <Head title="Ubah Pengguna" />

    <div class="flex flex-col space-y-6">
        <Heading
            variant="small"
            title="Ubah Pengguna"
            :description="`Perbarui pengguna: ${user.name}`"
        />

        <form @submit.prevent="submit" class="max-w-xl space-y-6">
            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input
                    id="name"
                    v-model="form.name"
                    required
                    autocomplete="name"
                    placeholder="Nama lengkap"
                />
                <InputError :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    v-model="form.email"
                    type="email"
                    required
                    autocomplete="email"
                    placeholder="Alamat email"
                />
                <InputError :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="role">Peran</Label>
                <Select v-model="form.role">
                    <SelectTrigger>
                        <SelectValue placeholder="Pilih peran" />
                    </SelectTrigger>
                    <SelectContent>
                        <SelectItem value="student">Siswa</SelectItem>
                        <SelectItem value="instructor">Instruktur</SelectItem>
                        <SelectItem value="admin">Admin</SelectItem>
                    </SelectContent>
                </Select>
                <InputError :message="errors.role" />
            </div>

            <div class="grid gap-2">
                <Label for="password">Kata sandi</Label>
                <PasswordInput
                    id="password"
                    v-model="form.password"
                    placeholder="Kosongkan jika tidak ingin mengganti kata sandi"
                />
                <InputError :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation">Konfirmasi Kata Sandi</Label>
                <PasswordInput
                    id="password_confirmation"
                    v-model="form.password_confirmation"
                    placeholder="Konfirmasi kata sandi baru"
                />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing">
                    {{ processing ? 'Memperbarui...' : 'Perbarui Pengguna' }}
                </Button>
                <Link :href="adminUsers.index()">
                    <Button type="button" variant="ghost">Batal</Button>
                </Link>
            </div>
        </form>
    </div>
</template>
