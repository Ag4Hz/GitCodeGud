<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { useForm } from '@inertiajs/vue3';
import { ArrowLeft, DollarSign, Target } from 'lucide-vue-next';

const props = defineProps({
    bounty: {
        type: Object,
        required: true,
    },
});

const form = useForm({
    bounty_id: props.bounty?.id,
    pr_url: '',
});

function submit() {
    form.post(route('submissions.store'));
}
</script>

<template>
    <AppLayout title="Submit Solution">
        <div v-if="!bounty" class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <Card>
                    <CardContent class="p-8 text-center">
                        <p class="text-red-600">Error: Bounty not found</p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div v-else class="py-12">
            <div class="mx-auto max-w-4xl sm:px-6 lg:px-8">
                <!-- Back Button with glassmorphism -->
                <div class="mb-6 rounded-lg bg-white/40 p-4 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                    <a
                        :href="`/bounties/${bounty.id}`"
                        class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100"
                    >
                        <ArrowLeft class="h-4 w-4" />
                        Back to Bounty
                    </a>
                </div>

                <!-- Bounty Info -->
                <Card class="mb-8 bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="mb-2 flex items-center gap-2">
                                    <Target class="h-5 w-5" />
                                    {{ bounty.title || 'Untitled Bounty' }}
                                </CardTitle>
                                <CardDescription> Submit your Pull Request for this bounty </CardDescription>
                            </div>
                            <Badge class="bg-green-100 text-green-800">
                                <DollarSign class="mr-1 h-4 w-4" />
                                {{ bounty.reward_xp || 0 }} XP
                            </Badge>
                        </div>
                    </CardHeader>
                    <CardContent v-if="bounty.description" class="pt-0">
                        <div
                            class="rounded-2xl border border-gray-200 bg-white/40 p-0 p-2 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5"
                        >
                            <p class="">{{ bounty.description }}</p>
                        </div>
                    </CardContent>
                </Card>

                <!-- Submission Form -->
                <Card
                    class="gap-0 rounded-2xl border border-gray-200 bg-white/40 p-0 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5"
                >
                    <CardHeader class="gap-0 rounded-t-2xl bg-white/40 px-2 py-3 backdrop-blur-xl sm:px-5 md:px-6 dark:bg-white/5">
                        <CardTitle class="my-2 text-lg dark:text-white">Submit Your Solution</CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- PR URL -->
                            <div class="space-y-3">
                                <Label for="pr_url" class="font-medium text-gray-800 dark:text-gray-200">Pull Request URL *</Label>
                                <Input
                                    id="pr_url"
                                    v-model="form.pr_url"
                                    type="url"
                                    placeholder="https://github.com/username/repository/pull/123"
                                    required
                                    :class="[form.errors.pr_url && 'border-red-500 focus:border-red-500']"
                                />
                                <div class="rounded-lg bg-white/40 p-4 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                                    <p class="mb-2 text-sm font-medium text-gray-700 dark:text-gray-300">Your Pull Request must be submitted to:</p>
                                    <p class="rounded-2xl bg-white/40 p-4 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                                        {{ bounty.issue?.repo?.url || 'Repository URL not available' }}
                                    </p>
                                </div>
                                <div
                                    v-if="form.errors.pr_url"
                                    class="rounded-lg border border-red-200 bg-red-50 p-3 dark:border-red-800 dark:bg-red-900/20"
                                >
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ form.errors.pr_url }}</p>
                                </div>
                            </div>

                            <!-- Submit Buttons -->
                            <div class="flex gap-4 border-t border-gray-200 pt-6 dark:border-gray-700">
                                <Button
                                    type="submit"
                                    :disabled="form.processing"
                                    class="flex items-center gap-2 border-0 bg-black text-white transition-colors duration-200 hover:bg-gray-800 disabled:opacity-50 dark:bg-white dark:text-black dark:hover:bg-gray-100"
                                >
                                    <Target class="h-4 w-4" />
                                    {{ form.processing ? 'Submitting...' : 'Submit Solution' }}
                                </Button>

                                <a
                                    :href="`/bounties/${bounty.id}`"
                                    class="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white/40 p-0 px-4 py-1 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5"
                                >
                                    Cancel
                                </a>
                            </div>
                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
