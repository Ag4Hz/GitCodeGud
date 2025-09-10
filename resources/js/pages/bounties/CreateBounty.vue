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
import { AlertCircle, CheckCircle, Github, Plus, Search } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';

interface Repository {
    id: number;
    name: string;
    full_name: string;
    description: string;
    url: string;
    language: string;
    updated_at: string;
    open_issues_count: number;
}

interface Issue {
    id: number;
    number: number;
    title: string;
    body: string;
    url: string;
    state: string;
    created_at: string;
    updated_at: string;
    user: {
        login: string;
        avatar_url: string;
    };
    labels: Array<{
        name: string;
        color: string;
    }>;
    comments: number;
}

interface Props {
    bounties?: BountyPagination;
    repositories?: Repository[];
    issues?: Issue[];
    repositoryQuery?: string;
    selectedRepository?: string;
}

const props = withDefaults(defineProps<Props>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
    repositories: () => [],
    issues: () => [],
    repositoryQuery: '',
    selectedRepository: '',
});

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
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
                preserveScroll: true,
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

        <div class="mx-auto mb-8 w-full max-w-4xl space-y-6 px-4 sm:px-6">
            <!-- Page Header -->
            <div class="mx-auto my-16 max-w-2xl text-center">
                <h1 class="mb-2 text-3xl font-bold tracking-tight">Create New Bounty</h1>
                <p class="text-muted-foreground">Create a bounty to incentivize contributions to your GitHub issues</p>
            </div>

            <!-- Create Bounty Form -->
            <Card class="gap-0 rounded-2xl border border-gray-200 bg-white/40 p-0 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
                <CardHeader class="gap-0 rounded-t-2xl bg-white/40 px-2 py-3 backdrop-blur-xl sm:px-5 md:px-6 dark:bg-white/5">
                    <CardTitle class="flex items-center gap-0">
                        <Plus class="h-5 w-5" />
                        Bounty Details
                    </CardTitle>
                    <CardDescription>Fill out the details below to create your bounty</CardDescription>
                </CardHeader>

                <CardContent class="my-8">
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
                    <div v-if="showDuplicateBountyWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-800 bg-red-800/10 p-4">
                        <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-800" />
                        <div>
                            <h4 class="mb-1 font-medium text-red-800">Bounty Already Exists</h4>
                            <p class="text-sm text-red-800/70">A bounty already exists for this GitHub issue. Each issue can only have one bounty.</p>
                        </div>
                    </div>
                    <div v-if="showArchivedBountyWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-800 bg-red-800/10 p-4">
                        <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-800" />
                        <div>
                            <h4 class="mb-1 font-medium text-red-800">Bounty Already Exists</h4>
                            <p class="text-sm text-red-800/70">
                                An archived bounty already exists for this issue. Please restore the existing bounty instead.
                            </p>
                        </div>
                    </div>

                    <div v-if="showRepoWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-800 bg-red-800/10 p-4">
                        <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-800" />
                        <div>
                            <h4 class="mb-1 font-medium text-red-800">Repository Access Required</h4>
                            <p class="text-sm text-red-800/70">You can only create bounties for repositories you own or have push access to.</p>
                        </div>
                    </div>

                    <div v-if="showIssueStatusWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-800 bg-red-800/10 p-4">
                        <AlertCircle class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-800" />
                        <div>
                            <h4 class="mb-1 font-medium text-red-800">Issue is Closed</h4>
                            <p class="text-sm text-red-800/70">Only open GitHub issues can be used for bounties.</p>
                        </div>
                    </div>

                    <div v-if="showApiWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-800 bg-red-800/10 p-4">
                        <Github class="mt-0.5 h-5 w-5 flex-shrink-0 text-red-800" />
                        <div>
                            <h4 class="mb-1 font-medium text-red-800">GitHub Access Issue</h4>
                            <p class="text-sm text-red-800/70">Unable to verify the issue status. Please ensure you're connected to GitHub.</p>
                        </div>
                    </div>

                    <form @submit.prevent="submitBounty" class="space-y-6">
                        <BountySearchForm
                            ref="bountySearchFormRef"
                            :form="bountyForm"
                            :repositories="props.repositories"
                            :issues="props.issues"
                            :repository-query="props.repositoryQuery"
                            :selected-repository="props.selectedRepository"
                            @updateForm="updateBountyForm"
                        />

                        <!-- Title -->
                        <div class="mx-auto w-full max-w-4xl space-y-4">
                            <Label for="title">Bounty Title *</Label>
                            <div class="relative flex-1">
                                <Input
                                    id="title"
                                    v-model="bountyForm.title"
                                    placeholder="e.g., Fix responsive layout bug on mobile devices"
                                    required
                                    :class="[bountyForm.errors.title && 'border-red-500 focus-visible:ring-red-500']"
                                />
                                <Search class="absolute top-1/2 left-4 z-10 h-5 w-5 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                            </div>
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
                                    'flex min-h-[80px] w-full rounded-2xl border bg-white/40 border-gray-200 p-4 text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 placeholder:text-muted-foreground sm:backdrop-blur-2xl dark:border-white/10 dark:bg-white/10 dark:text-gray-100 focus-visible:outline-none focus-visible:border-green-500 focus-visible:ring-2 focus-visible:ring-green-500/50 dark:focus-visible:border-ring dark:focus-visible:ring-ring/50',
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
                                    class="h-12 w-32 pl-4 text-center text-2xl sm:pl-4 md:pl-4 lg:pl-4"
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
                        <div class="flex gap-4 border-t py-6 dark:border-t-white/30">
                            <Button type="submit" :disabled="bountyForm.processing" variant="button">
                                {{ bountyForm.processing ? 'Creating...' : 'Create Bounty' }}
                            </Button>
                            <Button type="button" variant="button" @click="cancelBountyCreation" :disabled="bountyForm.processing"> Cancel </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>

            <!-- My Bounties List -->
            <div class="space-y-6">
                <BountyManagement :bounties="props.bounties" :canEditBounties="true" />
            </div>
        </div>
    </AppLayout>
</template>
