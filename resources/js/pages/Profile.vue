<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import BountyManagement from '@/components/BountyManagement.vue';
import { useInitials } from '@/composables/useInitials';
import { useXP } from '@/composables/useXP';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { type BountyPagination } from '@/types/bounty';
import { Trophy, Zap, Star, Target, Code, Database, Settings, Plus, AlertCircle, Github } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface UserWithXP extends Omit<User, 'skills'> {
    total_xp: number;
    level: number;
    current_level_xp: number;
    next_level_xp: number;
    progress_percentage: number;
    skills: Array<{
        skill_name: string;
        type: string;
        xp: number;
        level: number;
    }>;
}

interface Props {
    user: UserWithXP;
    bounties?: BountyPagination;
    isOwner?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
});

const isOwner = computed(() => props.isOwner);


const { getInitials } = useInitials();
const { formatXP } = useXP();

const syncing = ref(false);
const showCreateForm = ref(false);
const activeTab = ref('bounties');

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
      {
        title: isOwner.value ? 'My Profile' : `${props.user.name}'s Profile`,
        href: isOwner.value ? '/profile' : `/profile/${props.user.id}`,
      },
]);

const bountyForm = useForm({
    title: '',
    description: '',
    reward_xp: 50,
    repo_url: '',
    issue_url: '',
});

const showDuplicateBountyWarning = computed(() => {
    return bountyForm.errors.issue_url?.includes('A bounty already exists for this GitHub issue');
});

// Repository ownership warning
const showRepoWarning = computed(() => {
    return bountyForm.errors.repo_url?.includes('only create bounties for repositories you own') ||
        bountyForm.errors.repo_url?.includes('Could not verify repository access');
});

// Issue status warning
const showIssueStatusWarning = computed(() => {
    return bountyForm.errors.issue_url?.includes('Only open GitHub issues');
});

// GitHub API access warning
const showApiWarning = computed(() => {
    return bountyForm.errors.issue_url?.includes('GitHub API access is required') ||
        bountyForm.errors.issue_url?.includes('Could not verify issue status');
});

const levelProgress = computed(() => {
    return {
        currentLevelXP: props.user.current_level_xp ?? 0,
        nextLevelXP: props.user.next_level_xp ?? 0,
        progressXP: props.user.total_xp - (props.user.current_level_xp ?? 0),
        totalNeeded: (props.user.next_level_xp ?? 0) - (props.user.current_level_xp ?? 0),
        percentage: props.user.progress_percentage ?? 0,
    };
});

const skillsByType = computed(() => {
    if (!props.user.skills?.length) return {};

    const grouped = props.user.skills.reduce(
        (acc, skill) => {
            const type = skill.type || 'other';
            (acc[type] = acc[type] || []).push(skill);
            return acc;
        },
        {} as Record<string, typeof props.user.skills>,
    );

    Object.values(grouped).forEach((skills) => {
        skills.sort((a, b) => b.xp - a.xp);
    });

    return grouped;
});

const typeConfig = {
    language: { name: 'Programming Languages', icon: Code },
    framework: { name: 'Frameworks', icon: Settings },
    tool: { name: 'Tools', icon: Settings },
    database: { name: 'Databases', icon: Database },
    other: { name: 'Other', icon: Star },
};

const getTypeIcon = (type: string) => {
    const config = typeConfig[type as keyof typeof typeConfig] || typeConfig.other;
    return config.icon;
};

const getTypeDisplayName = (type: string) => {
    const config = typeConfig[type as keyof typeof typeConfig] || typeConfig.other;
    return config.name;
};

const syncGitHubSkills = () => {
    syncing.value = true;
    router.post(
        '/profile/sync-github-skills',
        {},
        {
            onFinish: () => (syncing.value = false),
        },
    );
};

const toggleCreateForm = () => {
    showCreateForm.value = !showCreateForm.value;
    if (!showCreateForm.value) {
        bountyForm.reset();
        bountyForm.clearErrors();
    }
};

const submitBounty = () => {
    bountyForm.post(route('bounties.store'), {
        onSuccess: () => {
            showCreateForm.value = false;
            bountyForm.reset();
        },
        onError: (errors) => {
            console.log('Validation errors:', errors);
        }
    });
};

const cancelBountyCreation = () => {
    showCreateForm.value = false;
    bountyForm.reset();
    bountyForm.clearErrors();
};

const switchTab = (tab: string) => {
    activeTab.value = tab;
    if (tab !== 'bounties') {
        showCreateForm.value = false;
    }
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isOwner ? 'My Profile' : `${user.name}'s Profile`" />
        <div class="px-4 py-6">
            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Profile Header -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <Avatar class="h-20 w-20 overflow-hidden rounded-lg">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 text-2xl font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <!-- Large XP Level Badge -->
                                <div class="absolute -right-2 -bottom-2 flex items-center justify-center">
                                    <Badge
                                        variant="secondary"
                                        class="h-8 min-w-8 border-3 border-white bg-orange-500 px-2 text-sm font-bold text-white shadow-lg dark:border-gray-900"
                                    >
                                        {{ user.level }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="flex-1">
                                <CardTitle class="text-2xl">{{ user.name }}</CardTitle>
                                <CardDescription class="text-base">{{ user.email }}</CardDescription>
                                <div class="mt-2 flex items-center gap-4">
                                    <div class="flex items-center gap-1 text-sm font-medium">
                                        <Trophy class="h-4 w-4 text-orange-500" />
                                        Level {{ user.level }}
                                    </div>
                                    <div class="flex items-center gap-1 text-sm font-medium">
                                        <Zap class="h-4 w-4 text-blue-500" />
                                        {{ formatXP(user.total_xp) }} XP
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- XP Progress -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Level Progress
                        </CardTitle>
                        <CardDescription>
                            {{ formatXP(levelProgress.progressXP) }} / {{ formatXP(levelProgress.totalNeeded) }} XP to next level
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-3 rounded-full bg-blue-600 transition-all duration-300 ease-in-out"
                                    :style="`width: ${levelProgress.percentage}%`"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>Level {{ user.level }}</span>
                                <span v-if="user.level < 9">{{ levelProgress.percentage }}%</span>
                                <span v-if="user.level < 9">Level {{ user.level + 1 }}</span>
                                <span v-else>Max Level</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Custom Tab Implementation -->
                <div class="space-y-6">
                    <!-- Tab Navigation -->
                    <div class="flex items-center justify-center">
                        <div class="inline-flex h-10 items-center justify-center rounded-md bg-muted p-1 text-muted-foreground">
                            <button
                                @click="switchTab('bounties')"
                                :class="[
                                    'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all',
                                    activeTab === 'bounties'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'hover:bg-background/50'
                                ]"
                            >
                                Bounty Management
                            </button>
                            <button
                                @click="switchTab('skills')"
                                :class="[
                                    'inline-flex items-center justify-center whitespace-nowrap rounded-sm px-3 py-1.5 text-sm font-medium ring-offset-background transition-all',
                                    activeTab === 'skills'
                                        ? 'bg-background text-foreground shadow-sm'
                                        : 'hover:bg-background/50'
                                ]"
                            >
                                Skills & XP
                            </button>
                        </div>
                    </div>

                    <!-- Bounty Management Tab Content -->
                    <div v-if="activeTab === 'bounties'" class="space-y-6">
                        <!-- Create Bounty Section -->
                        <Card>
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="flex items-center gap-2">
                                            <Plus class="h-5 w-5" />
                                            Create Bounty
                                        </CardTitle>
                                        <CardDescription>
                                            Create a new bounty to incentivize contributions to your GitHub issues
                                        </CardDescription>
                                    </div>
                                    <Button
                                        @click="toggleCreateForm"
                                        :variant="showCreateForm ? 'outline' : 'default'"
                                    >
                                        {{ showCreateForm ? 'Cancel' : 'Create Bounty' }}
                                    </Button>
                                </div>
                            </CardHeader>

                            <CardContent v-if="showCreateForm">
                                <div v-if="showDuplicateBountyWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-yellow-200 bg-yellow-50 p-4 dark:border-yellow-800 dark:bg-yellow-950">
                                    <AlertCircle class="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 class="font-medium text-yellow-800 dark:text-yellow-200 mb-1">Bounty Already Exists</h4>
                                        <p class="text-sm text-yellow-700 dark:text-yellow-300">
                                            A bounty already exists for this GitHub issue. Each issue can only have one bounty.
                                            Please select a different issue or check your existing bounties.
                                        </p>
                                    </div>
                                </div>

                                <!-- Repository ownership warning -->
                                <div v-if="showRepoWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-orange-200 bg-orange-50 p-4 dark:border-orange-800 dark:bg-orange-950">
                                    <AlertCircle class="h-5 w-5 text-orange-600 dark:text-orange-400 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 class="font-medium text-orange-800 dark:text-orange-200 mb-1">Repository Access Required</h4>
                                        <p class="text-sm text-orange-700 dark:text-orange-300">
                                            You can only create bounties for repositories you own or have push access to.
                                            Make sure you're the owner or a collaborator of this repository.
                                        </p>
                                    </div>
                                </div>

                                <!-- Issue status warning -->
                                <div v-if="showIssueStatusWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4 dark:border-red-800 dark:bg-red-950">
                                    <AlertCircle class="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 class="font-medium text-red-800 dark:text-red-200 mb-1">Issue is Closed</h4>
                                        <p class="text-sm text-red-700 dark:text-red-300">
                                            Only open GitHub issues can be used for bounties. This issue appears to be closed.
                                            Please reopen the issue on GitHub or select a different open issue.
                                        </p>
                                    </div>
                                </div>

                                <!-- GitHub API warning -->
                                <div v-if="showApiWarning" class="mb-4 flex items-start gap-3 rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
                                    <Github class="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 flex-shrink-0" />
                                    <div>
                                        <h4 class="font-medium text-blue-800 dark:text-blue-200 mb-1">GitHub Access Issue</h4>
                                        <p class="text-sm text-blue-700 dark:text-blue-300">
                                            Unable to verify the issue status. Please ensure:
                                        </p>
                                        <ul class="text-sm text-blue-700 dark:text-blue-300 mt-1 ml-4 list-disc">
                                            <li>You're connected to GitHub</li>
                                            <li>The issue exists and you have access to it</li>
                                            <li>The repository is public or you have access to it</li>
                                        </ul>
                                    </div>
                                </div>

                                <form @submit.prevent="submitBounty" class="space-y-6">
                                    <!-- Title -->
                                    <div class="space-y-2">
                                        <Label for="title">Bounty Title *</Label>
                                        <Input
                                            id="title"
                                            v-model="bountyForm.title"
                                            placeholder="e.g., Fix responsive layout bug on mobile devices"
                                            required
                                            :class="[
                                                bountyForm.errors.title && 'border-red-500 focus-visible:ring-red-500'
                                            ]"
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
                                                'flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50',
                                                bountyForm.errors.description && 'border-red-500 focus-visible:ring-red-500'
                                            ]"
                                        ></textarea>
                                        <p class="text-sm text-muted-foreground">
                                            {{ bountyForm.description.length }}/2000 characters
                                        </p>
                                        <InputError :message="bountyForm.errors.description" />
                                    </div>

                                    <!-- Repository and Issue URLs -->
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <!-- Repository URL -->
                                        <div class="space-y-2">
                                            <Label for="repo_url">GitHub Repository URL *</Label>
                                            <Input
                                                id="repo_url"
                                                v-model="bountyForm.repo_url"
                                                type="url"
                                                placeholder="https://github.com/username/repository"
                                                required
                                                :class="[
                                                    bountyForm.errors.repo_url && 'border-red-500 focus-visible:ring-red-500'
                                                ]"
                                            />
                                            <p class="text-sm text-muted-foreground">
                                                Languages will be detected automatically from the repository
                                            </p>
                                            <InputError :message="bountyForm.errors.repo_url" />
                                        </div>

                                        <!-- Issue URL -->
                                        <div class="space-y-2">
                                            <Label for="issue_url">GitHub Issue URL *</Label>
                                            <Input
                                                id="issue_url"
                                                v-model="bountyForm.issue_url"
                                                type="url"
                                                placeholder="https://github.com/username/repository/issues/123"
                                                required
                                                :class="[
                                                    bountyForm.errors.issue_url && 'border-red-500 focus-visible:ring-red-500'
                                                ]"
                                            />
                                            <InputError :message="bountyForm.errors.issue_url" />
                                        </div>
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
                                                :class="[
                                                    bountyForm.errors.reward_xp && 'border-red-500 focus-visible:ring-red-500'
                                                ]"
                                            />
                                            <span class="text-sm text-muted-foreground">
                                                XP (1-1000)
                                            </span>
                                        </div>
                                        <p class="text-sm text-muted-foreground">
                                            Higher rewards attract more contributors. Consider the complexity of the task.
                                        </p>
                                        <InputError :message="bountyForm.errors.reward_xp" />
                                    </div>

                                    <!-- Form Actions -->
                                    <div class="flex gap-4 pt-4 border-t">
                                        <Button
                                            type="submit"
                                            :disabled="bountyForm.processing"
                                        >
                                            {{ bountyForm.processing ? 'Creating...' : 'Create Bounty' }}
                                        </Button>
                                        <Button
                                            type="button"
                                            variant="outline"
                                            @click="cancelBountyCreation"
                                            :disabled="bountyForm.processing"
                                        >
                                            Cancel
                                        </Button>
                                    </div>
                                </form>
                            </CardContent>
                        </Card>

                        <!-- Bounty List -->
                        <BountyManagement :bounties="bounties" />
                    </div>

                    <!-- Skills Tab Content -->
                    <div v-if="activeTab === 'skills'">
                        <!-- Skills organized by categories -->
                        <Card v-if="user.skills && user.skills.length > 0">
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="flex items-center gap-2">
                                            <Star class="h-5 w-5" />
                                            Skills & Experience
                                        </CardTitle>
                                        <CardDescription>Your programming skills organized by category</CardDescription>
                                    </div>
                                    <button
                                        @click="syncGitHubSkills"
                                        :disabled="syncing"
                                        class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:outline-none disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                                    </button>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-6">
                                <!-- Skill Categories -->
                                <div v-for="(skills, type) in skillsByType" :key="type" class="space-y-3">
                                    <div class="rounded-lg border p-4">
                                        <div class="mb-4 flex items-center gap-2">
                                            <component :is="getTypeIcon(type)" class="h-5 w-5 text-blue-600" />
                                            <h3 class="text-lg font-semibold">{{ getTypeDisplayName(type) }}</h3>
                                            <Badge variant="outline" class="ml-auto">
                                                {{ skills.length }} {{ skills.length === 1 ? 'skill' : 'skills' }}
                                            </Badge>
                                        </div>
                                        <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                            <div
                                                v-for="skill in skills"
                                                :key="skill.skill_name"
                                                class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                            >
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="truncate font-medium">{{ skill.skill_name }}</h4>
                                                    <p class="text-sm text-muted-foreground">{{ formatXP(skill.xp) }} XP</p>
                                                </div>
                                                <Badge
                                                    variant="secondary"
                                                    class="ml-2 bg-blue-100 font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200"
                                                >
                                                    L{{ skill.level }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </CardContent>
                        </Card>

                        <!-- No Skills Message with GitHub Sync -->
                        <Card v-else>
                            <CardHeader>
                                <div class="flex items-center justify-between">
                                    <div>
                                        <CardTitle class="flex items-center gap-2">
                                            <Star class="h-5 w-5" />
                                            Skills & Experience
                                        </CardTitle>
                                        <CardDescription>Sync your skills from GitHub repositories!</CardDescription>
                                    </div>
                                    <button
                                        @click="syncGitHubSkills"
                                        :disabled="syncing"
                                        class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:outline-none disabled:opacity-50"
                                    >
                                        <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path
                                                fill-rule="evenodd"
                                                d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                                clip-rule="evenodd"
                                            ></path>
                                        </svg>
                                        {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                                    </button>
                                </div>
                            </CardHeader>
                            <CardContent>
                                <div class="py-8 text-center text-muted-foreground">
                                    <Star class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                    <p>No skills earned yet. Sync from GitHub or complete your first bounty to start earning XP!</p>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
