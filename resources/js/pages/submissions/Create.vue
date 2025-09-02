<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Badge } from '@/components/ui/badge'

import { ArrowLeft, Target, DollarSign } from 'lucide-vue-next'

const props = defineProps({
    bounty: {
        type: Object,
        required: true
    }
})

const form = useForm({
    bounty_id: props.bounty?.id,
    pr_url: '',
})

function submit() {
    form.post(route('submissions.store'))
}
</script>

<template>
    <AppLayout title="Submit Solution">
        <div v-if="!bounty" class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <Card>
                    <CardContent class="p-8 text-center">
                        <p class="text-red-600">Error: Bounty not found</p>
                    </CardContent>
                </Card>
            </div>
        </div>

        <div v-else class="py-12">
            <div class="max-w-4xl mx-auto sm:px-6 lg:px-8">
                <!-- Back Button with glassmorphism -->
                <div class="mb-6 bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10 rounded-lg p-4">
                    <a :href="`/bounties/${bounty.id}`" class="inline-flex items-center gap-2 text-gray-600 hover:text-gray-900 dark:text-gray-300 dark:hover:text-gray-100">
                        <ArrowLeft class="h-4 w-4" />
                        Back to Bounty
                    </a>
                </div>

                <!-- Bounty Info -->
                <Card class="mb-8 bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                    <CardHeader>
                        <div class="flex items-start justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2 mb-2">
                                    <Target class="h-5 w-5" />
                                    {{ bounty.title || 'Untitled Bounty' }}
                                </CardTitle>
                                <CardDescription>
                                    Submit your Pull Request for this bounty
                                </CardDescription>
                            </div>
                            <Badge class="bg-green-100 text-green-800">
                                <DollarSign class="h-4 w-4 mr-1" />
                                {{ bounty.reward_xp || 0 }} XP
                            </Badge>
                        </div>
                    </CardHeader>
                </Card>

                <!-- Submission Form -->
                <Card class="bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                    <CardHeader class="border-b border-gray-200 dark:border-gray-700">
                        <CardTitle class="text-gray-900 dark:text-white">Submit Your Solution</CardTitle>
                    </CardHeader>
                    <CardContent class="p-6">
                        <form @submit.prevent="submit" class="space-y-6">
                            <!-- PR URL -->
                            <div class="space-y-3">
                                <Label for="pr_url" class="text-gray-800 font-medium dark:text-gray-200">Pull Request URL *</Label>
                                <Input
                                    id="pr_url"
                                    v-model="form.pr_url"
                                    type="url"
                                    placeholder="https://github.com/username/repository/pull/123"
                                    required
                                    class="bg-white/60 border-gray-300 focus:bg-white focus:border-gray-500 dark:bg-gray-800/60 dark:border-gray-600 dark:focus:bg-gray-800 dark:text-white dark:focus:border-gray-400"
                                    :class="[form.errors.pr_url && 'border-red-500 focus:border-red-500']"
                                />
                                <div class="bg-gray-50 border border-gray-200 rounded-lg p-4 dark:bg-gray-800 dark:border-gray-700">
                                    <p class="text-sm text-gray-700 mb-2 font-medium dark:text-gray-300">Your Pull Request must be submitted to:</p>
                                    <p class="font-mono text-xs bg-black text-white px-3 py-2 rounded-md dark:bg-gray-900">
                                        {{ bounty.issue?.repo?.url || 'Repository URL not available' }}
                                    </p>
                                </div>
                                <div v-if="form.errors.pr_url" class="bg-red-50 border border-red-200 rounded-lg p-3 dark:bg-red-900/20 dark:border-red-800">
                                    <p class="text-red-600 text-sm font-medium dark:text-red-400">{{ form.errors.pr_url }}</p>
                                </div>
                            </div>

                        </form>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
