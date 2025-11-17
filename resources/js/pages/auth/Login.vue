<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onFinish: () => form.reset('password'),
    });
};
</script>

<template>
    <AuthBase title="Log in to your account" description="Enter your credentials to access your account">
        <Head title="Log in" />

        <div v-if="status" class="mb-4 text-center text-sm font-medium text-green-600">
            {{ status }}
        </div>

        <form @submit.prevent="submit" class="flex flex-col gap-6">
            <div class="grid gap-6">
                <div class="grid gap-2">
                    <Label for="email">Email address</Label>
                    <Input
                        id="email"
                        type="email"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="email"
                        v-model="form.email"
                        placeholder="email@example.com"
                    />
                    <InputError :message="form.errors.email" />
                </div>

                <div class="grid gap-2">
                    <Label for="password">Password</Label>
                    <Input
                        id="password"
                        type="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        v-model="form.password"
                        placeholder="Password"
                    />
                    <InputError :message="form.errors.password" />
                </div>

                <Button type="submit" class="mt-2 w-full" tabindex="3" :disabled="form.processing">
                    <LoaderCircle v-if="form.processing" class="h-4 w-4 animate-spin" />
                    Log in
                </Button>
            </div>

            <!-- Social login buttons -->
            <div class="mt-4 flex flex-col gap-3">
                <p class="text-sm text-muted-foreground">Or continue with:</p>

                <div class="flex flex-col items-stretch gap-3">
                    <!-- GitHub: active redirect -->
                    <Button as-child class="w-full">
                        <Link :href="route('github.redirect')" class="btn-social btn-social-active w-full">
                            <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                                <path
                                    d="M12 .5C5.73.5.5 5.73.5 12c0 5.08 3.29 9.39 7.86 10.91.58.11.79-.25.79-.56 0-.28-.01-1.02-.02-2-3.2.7-3.88-1.54-3.88-1.54-.53-1.36-1.3-1.72-1.3-1.72-1.06-.72.08-.71.08-.71 1.17.08 1.78 1.2 1.78 1.2 1.04 1.78 2.72 1.27 3.38.97.11-.76.41-1.27.75-1.56-2.55-.29-5.23-1.28-5.23-5.72 0-1.26.45-2.29 1.2-3.1-.12-.3-.52-1.52.11-3.16 0 0 .98-.31 3.2 1.19a11.1 11.1 0 0 1 2.92-.39c.99 0 1.99.13 2.92.39 2.22-1.5 3.2-1.19 3.2-1.19.63 1.64.23 2.86.11 3.16.75.81 1.2 1.84 1.2 3.1 0 4.45-2.69 5.43-5.25 5.71.42.37.8 1.1.8 2.22 0 1.6-.01 2.89-.01 3.28 0 .31.21.68.8.56A11.51 11.51 0 0 0 23.5 12C23.5 5.73 18.27.5 12 .5z"
                                />
                            </svg>
                            <span>Continue with GitHub</span>
                        </Link>
                    </Button>

                    <!-- GitLab: placeholder (disabled) -->
                    <Button variant="outline" disabled class="w-full">
                        <span class="btn-social btn-social-disabled w-full">Continue with GitLab</span>
                    </Button>

                    <!-- Bitbucket: placeholder (disabled) -->
                    <Button variant="outline" disabled class="w-full">
                        <span class="btn-social btn-social-disabled w-full">Continue with Bitbucket</span>
                    </Button>
                </div>
            </div>

            <div class="text-center text-sm text-muted-foreground">
                Don't have an account?
                <TextLink :href="route('register')" class="underline underline-offset-4" :tabindex="4">Sign up</TextLink>
            </div>
        </form>
    </AuthBase>
</template>
