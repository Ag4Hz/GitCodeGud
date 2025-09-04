<script setup lang="ts">
import BountyManagement from '@/components/BountyManagement.vue';
import BountySearchForm from '@/components/BountySearchForm.vue';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { type BountyPagination } from '@/types/bounty';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { AlertCircle, Github, Plus, CheckCircle } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Props {
    bounties?: BountyPagination;
}

const { bounties } = withDefaults(defineProps<Props>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
});

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Create Bounty',
        href: '/bounties/create',
    },
]);

const page = usePage();

const bountyForm = useForm({
    title: '',
    description: '',
    reward_xp: 50,
    repository_full_name: '',
    issue_number: '',
});

const showDuplicateBountyWarning = computed(() => {
    return bountyForm.errors.issue_number?.includes('A bounty already exists for this issue');
});

const showArchivedBountyWarning = computed(() => {
    return bountyForm.errors.issue_number?.includes('An archived bounty already exists for this issue');
});

const showRepoWarning = computed(() => {
    return (
        bountyForm.errors.repository_full_name?.includes('only create bounties for repositories you own') ||
        bountyForm.errors.repository_full_name?.includes('Could not verify repository access')
    );
});

const showIssueStatusWarning = computed(() => {
    return bountyForm.errors.issue_number?.includes('Only open GitHub issues');
});

const showApiWarning = computed(() => {
    return (
        bountyForm.errors.repository_full_name?.includes('GitHub API access is required') ||
        bountyForm.errors.issue_number?.includes('Could not verify issue status')
    );
});

const flashSuccess = computed(() => {
    return (page.props as any).flash?.success;
});

const bountySearchFormRef = ref<{ clearForm: () => void } | null>(null);

const submitBounty = () => {
    bountyForm.post(route('bounties.store'), {
        preserveScroll: true,
        onSuccess: () => {
            bountyForm.reset();
            if (bountySearchFormRef.value) {
                bountySearchFormRef.value.clearForm();
            }
            router.visit(route('bounties.create'), {
                preserveState: false,
                preserveScroll: true
            });
        },
        onError: (errors) => {
            console.log('Validation errors:', errors);
        },
    });
};

const cancelBountyCreation = () => {
    bountyForm.reset();
    bountyForm.clearErrors();
    router.visit('/dashboard');
};

const updateBountyForm = (field: string, value: any) => {
    if (field === 'repository_full_name') {
        bountyForm.repository_full_name = value;
    } else if (field === 'issue_number') {
        bountyForm.issue_number = value;
    } else if (field === 'title') {
        bountyForm.title = value;
    } else if (field === 'description') {
        bountyForm.description = value;
    }
};

watch(flashSuccess, (newValue) => {
    if (newValue) {
        setTimeout(() => {
            router.reload({ only: [] });
        }, 5000);
    }
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Bounty" />

        <div class="px-4 py-6">
            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Page Header -->
                <div class="space-y-2 text-center">
                    <h1 class="text-3xl font-bold tracking-tight">Create New Bounty</h1>
                    <p class="text-muted-foreground">Create a bounty to incentivize contributions to your GitHub issues</p>
                </div>

                <!-- Create Bounty Form -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Plus class="h-5 w-5" />
                            Bounty Details
                        </CardTitle>
                        <CardDescription> Fill out the details below to create your bounty </CardDescription>
                    </CardHeader>

                    <CardContent>
                        <!-- Success Message -->
                        <div
                            v-if="flashSuccess"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-green-200 bg-green-50 p-4 dark:border-green-800 dark:bg-green-950"
                        >
                            <CheckCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-green-600 dark:text-green-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-green-800 dark:text-green-200">Success!</h4>
                                <p class="text-sm text-green-700 dark:text-green-300">
                                    {{ flashSuccess }}
                                </p>
                            </div>
                        </div>

                        <!-- Warning Messages -->
                        <div
                            v-if="showDuplicateBountyWarning"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950"
                        >
                            <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-yellow-600 dark:text-yellow-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-yellow-800 dark:text-yellow-200">Bounty Already Exists</h4>
                                <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                    A bounty already exists for this GitHub issue. Each issue can only have one bounty.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="showArchivedBountyWarning"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
                        >
                            <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-blue-800 dark:text-blue-200">Archived Bounty Exists</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    An archived bounty already exists for this issue. Please restore the existing bounty instead.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="showRepoWarning"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-950"
                        >
                            <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-orange-600 dark:text-orange-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-orange-800 dark:text-orange-200">Repository Access Required</h4>
                                <p class="text-sm text-orange-700 dark:text-orange-300">
                                    You can only create bounties for repositories you own or have push access to.
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="showIssueStatusWarning"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950"
                        >
                            <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-600 dark:text-red-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-red-800 dark:text-red-200">Issue is Closed</h4>
                                <p class="text-sm text-red-700 dark:text-red-300">Only open GitHub issues can be used for bounties.</p>
                            </div>
                        </div>

                        <div
                            v-if="showApiWarning"
                            class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950"
                        >
                            <Github class="mt-0.5 h-5 w-5 flex-shrink-0 text-blue-600 dark:text-blue-400" />
                            <div>
                                <h4 class="mb-1 font-medium text-blue-800 dark:text-blue-200">GitHub Access Issue</h4>
                                <p class="text-sm text-blue-700 dark:text-blue-300">
                                    Unable to verify the issue status. Please ensure you're connected to GitHub.
                                </p>
                            </div>
                        </div>

                        <form @submit.prevent="submitBounty" class="space-y-6">
                            <BountySearchForm ref="bountySearchFormRef" :form="bountyForm" @updateForm="updateBountyForm"
                            />

                            <!-- Title -->
                            <div class="space-y-2">
                                <Label for="title">Bounty Title *</Label>
                                <Input
                                    id="title"
                                    v-model="bountyForm.title"
                                    placeholder="e.g., Fix responsive layout bug on mobile devices"
                                    required
                                    :class="[bountyForm.errors.title && 'border-red-500 focus-visible:ring-red-500']"
                                />
                                <InputError :message="bountyForm.errors.title" />
                            </div>

                            <!-- Description -->
                            <div class="space-y-2">
                                <Label for="description">Description</Label>
                                <textarea
                                    id="description"
                                    v-model="bountyForm.description"
                                    placeholder="Describe the task in detail. Include acceptance criteria, expected behavior, and any relevant context..."
                                    rows="4"
                                    :class="[
                                        'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50',
                                        bountyForm.errors.description && 'border-red-500 focus-visible:ring-red-500',
                                    ]"
                                ></textarea>
                                <p class="text-sm text-muted-foreground">{{ bountyForm.description.length }}/2000 characters</p>
                                <InputError :message="bountyForm.errors.description" />
                            </div>

                            <!-- Reward XP -->
                            <div class="space-y-2">
                                <Label for="reward_xp">Reward (XP) *</Label>
                                <div class="flex items-center gap-4">
                                    <Input
                                        id="reward_xp"
                                        v-model.number="bountyForm.reward_xp"
                                        type="number"
                                        min="1"
                                        max="1000"
                                        required
                                        class="w-32"
                                        :class="[bountyForm.errors.reward_xp && 'border-red-500 focus-visible:ring-red-500']"
                                    />
                                    <span class="text-sm text-muted-foreground">XP (1-1000)</span>
                                </div>
                                <p class="text-sm text-muted-foreground">
                                    Higher rewards attract more contributors. Consider the complexity of the task.
                                </p>
                                <InputError :message="bountyForm.errors.reward_xp" />
                            </div>

                            <!-- Form Actions -->
                            <div class="flex gap-4 border-t pt-6">
                                <Button type="submit" :disabled="bountyForm.processing">
                                    {{ bountyForm.processing ? 'Creating...' : 'Create Bounty' }}
                                </Button>
                                <Button type="button" variant="outline" @click="cancelBountyCreation" :disabled="bountyForm.processing">
                                    Cancel
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

                <!-- My Bounties List -->
                <div class="space-y-6">
                    <BountyManagement :bounties="bounties" :canEditBounties="true" />
                </div>
            </div>
        </div>
    </AppLayout>
</template>
