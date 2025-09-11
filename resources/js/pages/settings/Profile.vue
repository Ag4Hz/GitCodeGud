<script setup lang="ts">
import HeadingSmall from '@/components/HeadingSmall.vue';
import InputError from '@/components/InputError.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { getInitials } from '@/composables/useInitials';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    mustVerifyEmail: boolean;
    status?: string;
}

defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Profile settings',
        href: '/settings/profile',
    },
];

const page = usePage();
const user = page.props.auth.user as User;

const form = useForm({
    name: user.name,
    email: user.email,
    description: user.description || '',
});

const descriptionLength = computed(() => form.description.length);
const maxDescriptionLength = 1000;
const isDescriptionTooLong = computed(() => descriptionLength.value > maxDescriptionLength);

const submit = () => {
    form.patch(route('profile.update'), {
        preserveScroll: true,
    });
};

const cancel = () => {
    form.reset();
    form.clearErrors();
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Profile settings" />

        <SettingsLayout>
            <div class="flex flex-col space-y-6">
                <HeadingSmall title="Profile information" description="Update your profile information and settings" />

                <!-- Avatar Section -->
                <div class="flex items-center gap-6 rounded-lg border-gray-200 bg-white/40 p-6 dark:border-white/10 dark:bg-white/5">
                    <div class="relative">
                        <Avatar class="h-20 w-20 overflow-hidden rounded-lg">
                            <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                            <AvatarFallback class="rounded-lg bg-neutral-200 text-2xl font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                {{ getInitials(user.name) }}
                            </AvatarFallback>
                        </Avatar>
                    </div>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold">{{ user.name }}</h3>
                        <p class="text-sm text-muted-foreground">{{ user.email }}</p>
                    </div>
                </div>

                <form @submit.prevent="submit" class="space-y-6">
                    <div class="grid gap-2">
                        <Label for="name">Name</Label>
                        <Input id="name" class="mt-1 block w-full" v-model="form.name" required autocomplete="name" placeholder="Full name" />
                        <InputError class="mt-2" :message="form.errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Email address</Label>
                        <Input
                            id="email"
                            type="email"
                            class="mt-1 block w-full"
                            v-model="form.email"
                            readonly
                            autocomplete="username"
                            placeholder="Email address"
                        />
                        <InputError class="mt-2" :message="form.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="description">Description</Label>
                        <div class="relative">
                            <textarea
                                id="description"
                                class="dark:focus-visible:ring-ring/50' w-full rounded-lg border border-gray-200 bg-white/40 p-4 text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 placeholder:text-muted-foreground focus-visible:border-green-500 focus-visible:ring-2 focus-visible:ring-green-500/50 focus-visible:outline-none sm:backdrop-blur-2xl dark:border-white/10 dark:bg-white/10 dark:text-gray-100 dark:focus-visible:border-ring"
                                :class="{ 'border-red-500 focus:ring-red-500': isDescriptionTooLong }"
                                v-model="form.description"
                                placeholder="Tell others about yourself..."
                                rows="3"
                            ></textarea>
                            <div class="absolute right-2 bottom-2 text-xs text-muted-foreground" :class="{ 'text-red-500': isDescriptionTooLong }">
                                {{ descriptionLength }}/{{ maxDescriptionLength }}
                            </div>
                        </div>
                        <InputError class="mt-1" :message="form.errors.description" />
                        <p class="text-xs text-muted-foreground">This will be displayed on your public profile.</p>
                    </div>
                    <div v-if="mustVerifyEmail && !user.email_verified_at">
                        <p class="-mt-4 text-sm text-muted-foreground">
                            Your email address is unverified.
                            <Link
                                :href="route('verification.send')"
                                method="post"
                                as="button"
                                class="text-foreground underline decoration-neutral-300 underline-offset-4 transition-colors duration-300 ease-out hover:decoration-current"
                            >
                                Click here to re-send the verification email.
                            </Link>
                        </p>

                        <div v-show="status === 'verification-link-sent'" class="mt-2 text-sm font-medium text-green-600">
                            A new verification link has been sent to your email address.
                        </div>
                    </div>

                    <!-- Save/Cancel buttons with validation -->
                    <div class="flex items-center gap-4">
                        <Button type="submit" :disabled="form.processing || isDescriptionTooLong" class="min-w-20">
                            {{ form.processing ? 'Saving...' : 'Save' }}
                        </Button>

                        <Button type="button" variant="outline" @click="cancel" :disabled="form.processing"> Cancel </Button>

                        <Transition
                            enter-active-class="transition ease-in-out"
                            enter-from-class="opacity-0"
                            leave-active-class="transition ease-in-out"
                            leave-to-class="opacity-0"
                        >
                            <p v-if="form.recentlySuccessful" class="text-sm font-medium text-green-600">Saved successfully!</p>
                        </Transition>
                    </div>
                </form>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
