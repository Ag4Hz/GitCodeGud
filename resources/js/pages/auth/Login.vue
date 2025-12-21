<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useToast } from '@/composables/useToast';
import AuthBase from '@/layouts/AuthLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { LoaderCircle } from 'lucide-vue-next';

const { success: showSuccess, error: showError } = useToast();

defineProps<{
    status?: string;
}>();

const form = useForm({
    email: '',
    password: '',
});

const submit = () => {
    form.post(route('login'), {
        onSuccess: () => {
            showSuccess('Login successful! Welcome back!');
        },
        onError: () => {
            showError('Login failed.');
        },
        onFinish: () => form.reset('password'),
    });
};
const redirectToProvider = (provider: string) => {
    window.location.href = route('oauth.redirect', { provider });
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
                    <!-- GitHub button -->
                    <Button type="button" @click="redirectToProvider('github')" class="w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                            <path
                                d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                            />
                        </svg>
                        <span>Continue with GitHub</span>
                    </Button>

                    <!-- GitLab button -->
                    <Button type="button" @click="redirectToProvider('gitlab')" class="w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                            <path
                                d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"
                            />
                        </svg>
                        <span>Continue with GitLab</span>
                    </Button>

                    <!-- Bitbucket button -->
                    <Button type="button" @click="redirectToProvider('bitbucket')" class="w-full">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" class="h-4 w-4" fill="currentColor">
                            <path
                                d="M2.4 3A1.3 1.3 0 0 0 1.1 4.5l2.7 15.9c.1.5.6.9 1.2.9h13a1.3 1.3 0 0 0 1.2-1.1l2.7-15.7A1.3 1.3 0 0 0 20.7 3H2.4zm9.6 12.3H9.3l-.9-6.6h7.2l-.9 6.6h-2.7z"
                            />
                        </svg>
                        <span>Continue with Bitbucket</span>
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
