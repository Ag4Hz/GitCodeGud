<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { type Bounty } from '@/types/bounty';
import { DollarSign, ExternalLink, Target, User as UserIcon, GitBranch } from 'lucide-vue-next';
import { computed } from 'vue';

interface BountyWithDetails extends Bounty {
    issue: {
        url: string;
        description?: string;
        repo: {
            url: string;
            git_id: string;
            name?: string;
            user_id: number;
            user?: User;
        };
    };
    submissions?: Array<{
        id: number;
        status: string;
        user: User;
        created_at: string;
    }>;
}

interface Props {
    bounty: BountyWithDetails;
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = page.props.auth?.user as User;

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
    {
        title: 'Bounty Details',
        href: `/bounties/${props.bounty.id}`,
    },
]);



const getStatusColor = (status: string) => {
    switch (status) {
        case 'open':
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'closed':
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getStatusDisplayText = (status: string): string => {
    return status.toUpperCase();
};

const isValidGitHubUrl = (url: string | undefined): boolean => {
    if (!url) return false;
    try {
        const parsedUrl = new URL(url);
        return parsedUrl.hostname === 'github.com';
    } catch {
        return false;
    }
};

const getRepositoryName = (repoUrl: string | undefined): string => {
    if (!repoUrl || !isValidGitHubUrl(repoUrl)) return 'Repository';

    try {
        const url = new URL(repoUrl);
        const pathParts = url.pathname.split('/').filter(part => part);
        if (pathParts.length >= 2) {
            return `${pathParts[0]}/${pathParts[1]}`;
        }
        return 'Repository';
    } catch {
        return 'Repository';
    }
};

const ownerInfo = computed(() => {
    if (props.bounty.issue?.repo?.user) {
        const user = props.bounty.issue.repo.user;
        return {
            id: user.id,
            name: user.name,
            nickname: user.nickname,
            avatar: user.avatar || `https://github.com/${user.nickname}.png`,
        };
    }

    const repoName = getRepositoryName(props.bounty.issue?.repo?.url || '');
    const ownerNickname = repoName.split('/')[0] || 'owner';

    return {
        id: props.bounty.issue?.repo?.user_id || 1,
        name: 'Repository Owner',
        nickname: ownerNickname,
        avatar: `https://github.com/${ownerNickname}.png`,
    };
});

const canUserSubmit = computed(() => {
    return currentUser &&
        props.bounty.status === 'open' &&
        currentUser.id !== ownerInfo.value.id;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="bounty.title" />

        <div class="max-w-4xl mx-auto space-y-6">
            <!-- Main Bounty Card -->
            <Card>
                <CardHeader>
                    <div class="flex items-start justify-between">
                        <div class="space-y-2">
                            <div class="flex items-center gap-3">
                                <h1 class="text-2xl font-bold">{{ bounty.title }}</h1>
                                <Badge :class="getStatusColor(bounty.status)" class="text-sm">
                                    {{ getStatusDisplayText(bounty.status) }}
                                </Badge>
                            </div>

                            <!-- Reward XP -->
                            <div class="flex items-center gap-2 text-lg font-semibold text-green-600">
                                <DollarSign class="h-5 w-5" />
                                <span>{{ bounty.reward_xp }} XP Reward</span>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div class="flex gap-2">
                            <Button v-if="canUserSubmit" class="flex items-center gap-2">
                                <Target class="h-4 w-4" />
                                Submit Solution
                            </Button>
                        </div>
                    </div>
                </CardHeader>

                <CardContent class="space-y-6">
                    <!-- Repository and Issue Links -->
                    <div>
                        <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                            <GitBranch class="h-5 w-5" />
                            Repository & Issue
                        </h3>

                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Repository Link -->
                            <Card class="group hover:shadow-lg transition-all duration-300 border-l-4 border-l-blue-500">
                                <CardContent class="p-6">
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-2 bg-blue-100 dark:bg-blue-900 rounded-lg">
                                                <GitBranch class="h-5 w-5 text-blue-600 dark:text-blue-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-lg mb-1">Repository</h4>
                                                <p class="text-sm text-muted-foreground font-mono truncate">
                                                    {{ getRepositoryName(bounty.issue?.repo?.url || '') }}
                                                </p>
                                            </div>
                                        </div>

                                        <a
                                            v-if="isValidGitHubUrl(bounty.issue?.repo?.url || '')"
                                            :href="bounty.issue?.repo?.url || '#'"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 group-hover:scale-105"
                                        >
                                            <ExternalLink class="h-4 w-4" />
                                            View Repository
                                        </a>
                                        <div v-else class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm bg-gray-100 dark:bg-gray-800 text-gray-500 rounded-lg">
                                            <span>Invalid Repository URL</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>

                            <!-- Issue Link -->
                            <Card class="group hover:shadow-lg transition-all duration-300 border-l-4 border-l-green-500">
                                <CardContent class="p-6">
                                    <div class="space-y-4">
                                        <div class="flex items-start gap-3">
                                            <div class="p-2 bg-green-100 dark:bg-green-900 rounded-lg">
                                                <Target class="h-5 w-5 text-green-600 dark:text-green-400" />
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <h4 class="font-semibold text-lg mb-1">GitHub Issue</h4>
                                                <p class="text-sm text-muted-foreground">
                                                    View the specific issue to resolve
                                                </p>
                                            </div>
                                        </div>

                                        <a
                                            v-if="isValidGitHubUrl(bounty.issue?.url || '')"
                                            :href="bounty.issue?.url || '#'"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm font-medium bg-green-600 text-white rounded-lg hover:bg-green-700 transition-all duration-200 group-hover:scale-105"
                                        >
                                            <ExternalLink class="h-4 w-4" />
                                            View Issue
                                        </a>
                                        <div v-else class="flex items-center justify-center gap-2 w-full px-4 py-3 text-sm bg-gray-100 dark:bg-gray-800 text-gray-500 rounded-lg">
                                            <span>Invalid Issue URL</span>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Owner Information -->
                    <div>
                        <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                            <UserIcon class="h-5 w-5" />
                            Bounty Owner
                        </h3>

                        <Card>
                            <CardContent class="p-4">
                                <div class="flex items-center gap-4">
                                    <Avatar class="h-12 w-12">
                                        <AvatarImage
                                            :src="ownerInfo.avatar || ''"
                                            :alt="ownerInfo.name"
                                        />
                                        <AvatarFallback class="text-lg">
                                            {{ ownerInfo.name.charAt(0).toUpperCase() }}
                                        </AvatarFallback>
                                    </Avatar>

                                    <div class="flex-1">
                                        <div class="flex items-center gap-2">
                                            <h4 class="font-semibold">{{ ownerInfo.name }}</h4>
                                            <Badge variant="outline" class="text-xs">
                                                @{{ ownerInfo.nickname }}
                                            </Badge>
                                        </div>
                                        <p class="text-sm text-muted-foreground">Repository Owner</p>
                                    </div>

                                    <!-- Contact Owner Button -->
                                    <Link :href="`/users/${ownerInfo.id}`">
                                        <Button variant="outline" size="sm" class="flex items-center gap-2">
                                            <UserIcon class="h-4 w-4" />
                                            View Profile
                                        </Button>
                                    </Link>
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </CardContent>
            </Card>

            <!-- Back to Dashboard -->
            <div class="flex justify-center">
                <Link href="/dashboard">
                    <Button variant="outline" class="flex items-center gap-2">
                        ← Back to Dashboard
                    </Button>
                </Link>
            </div>
        </div>
    </AppLayout>
</template>
