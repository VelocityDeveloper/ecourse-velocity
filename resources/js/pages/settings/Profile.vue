<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, ref } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import { getInitials } from '@/composables/useInitials';
import { edit } from '@/routes/profile';
import users from '@/routes/users';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Pengaturan profil',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const avatarInputKey = ref(0);
const avatarPreview = ref<string | null>(null);
const isRemovingAvatar = ref(false);

const displayedAvatar = computed(() => {
    if (avatarPreview.value) {
        return avatarPreview.value;
    }

    return isRemovingAvatar.value ? null : (user.value.avatar ?? null);
});

function clearAvatarPreview(): void {
    if (avatarPreview.value) {
        URL.revokeObjectURL(avatarPreview.value);
    }

    avatarPreview.value = null;
}

function onAvatarChange(event: Event): void {
    const file = (event.target as HTMLInputElement).files?.[0];

    clearAvatarPreview();

    if (file) {
        avatarPreview.value = URL.createObjectURL(file);
        isRemovingAvatar.value = false;
    }
}

function removeAvatar(): void {
    clearAvatarPreview();

    avatarInputKey.value++;

    isRemovingAvatar.value = true;
}

function onSaved(): void {
    clearAvatarPreview();

    avatarInputKey.value++;

    isRemovingAvatar.value = false;
}

onBeforeUnmount(clearAvatarPreview);
</script>

<template>
    <Head title="Pengaturan profil" />

    <h1 class="sr-only">Pengaturan profil</h1>

    <div class="flex flex-col space-y-6">
        <div class="flex items-start justify-between gap-4">
            <Heading
                variant="small"
                title="Profil"
                description="Perbarui foto, nama, alamat email, dan bio Anda"
            />
            <Link
                :href="users.show(user.slug)"
                class="shrink-0 text-sm text-muted-foreground underline underline-offset-4 hover:text-foreground"
            >
                Lihat profil publik
            </Link>
        </div>

        <Form
            v-bind="ProfileController.update.form()"
            class="space-y-6"
            v-slot="{ errors, processing }"
            @success="onSaved"
        >
            <div class="grid gap-2">
                <Label for="avatar">Foto</Label>
                <div class="flex items-center gap-4">
                    <Avatar class="size-16 overflow-hidden rounded-full">
                        <AvatarImage
                            v-if="displayedAvatar"
                            :src="displayedAvatar"
                            :alt="user.name"
                        />
                        <AvatarFallback class="text-lg">
                            {{ getInitials(user.name) }}
                        </AvatarFallback>
                    </Avatar>
                    <div class="grid flex-1 gap-2">
                        <Input
                            id="avatar"
                            :key="avatarInputKey"
                            type="file"
                            name="avatar"
                            accept="image/*"
                            @change="onAvatarChange"
                        />
                        <div class="flex items-center gap-3">
                            <p class="text-xs text-muted-foreground">
                                JPG, PNG, atau WebP, maksimal 2 MB.
                            </p>
                            <Button
                                v-if="displayedAvatar"
                                type="button"
                                variant="link"
                                size="sm"
                                class="h-auto p-0 text-xs text-destructive"
                                @click="removeAvatar"
                            >
                                Hapus foto
                            </Button>
                        </div>
                    </div>
                </div>
                <input
                    type="hidden"
                    name="remove_avatar"
                    :value="isRemovingAvatar ? 1 : 0"
                />
                <InputError class="mt-2" :message="errors.avatar" />
            </div>

            <div class="grid gap-2">
                <Label for="name">Nama</Label>
                <Input
                    id="name"
                    class="mt-1 block w-full"
                    name="name"
                    :default-value="user.name"
                    required
                    autocomplete="name"
                    placeholder="Nama lengkap"
                />
                <InputError class="mt-2" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email">Alamat email</Label>
                <Input
                    id="email"
                    type="email"
                    class="mt-1 block w-full"
                    name="email"
                    :default-value="user.email"
                    required
                    autocomplete="username"
                    placeholder="Alamat email"
                />
                <InputError class="mt-2" :message="errors.email" />
            </div>

            <div class="grid gap-2">
                <Label for="headline">Judul singkat</Label>
                <Input
                    id="headline"
                    class="mt-1 block w-full"
                    name="headline"
                    :default-value="user.headline ?? ''"
                    maxlength="120"
                    placeholder="mis. Senior Laravel Developer"
                />
                <InputError class="mt-2" :message="errors.headline" />
            </div>

            <div class="grid gap-2">
                <Label for="bio">Bio</Label>
                <Textarea
                    id="bio"
                    class="mt-1 block w-full"
                    name="bio"
                    rows="5"
                    :default-value="user.bio ?? ''"
                    maxlength="2000"
                    placeholder="Ceritakan sedikit tentang diri Anda"
                />
                <InputError class="mt-2" :message="errors.bio" />
            </div>

            <div class="flex items-center gap-4">
                <Button :disabled="processing" data-test="update-profile-button"
                    >Simpan</Button
                >
            </div>
        </Form>
    </div>

    <DeleteUser />
</template>
