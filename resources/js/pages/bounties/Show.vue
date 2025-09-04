<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { type Bounty } from '@/types/bounty';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { Calendar, Code, DollarSign, ExternalLink, GitBranch, MessageSquare, Tag, Target, User as UserIcon, Users } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
        pr_url?: string;
    }>;
}

interface CommentsData {
    data: any[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
    links: Array<{
        url: string | null;
        label: string;
        active: boolean;
    }>;
}

interface Props {
    bounty: BountyWithDetails;
    comments?: CommentsData | null;
    canUserSubmit?: boolean;
    userSubmission?: {
        id: number;
        status: string;
        pr_url: string;
        created_at: string;
    } | null;
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = page.props.auth?.user as User;

const loadingComments = ref(false);

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

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

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

const submissionStatusVariant = computed(() => {
    if (!props.userSubmission) return 'outline';
    switch (props.userSubmission.status) {
        case 'pending': return 'secondary';
        case 'accepted': return 'default';
        case 'rejected': return 'destructive';
        default: return 'outline';
    }
});

const submissionStatusText = computed(() => {
    if (!props.userSubmission) return '';
    switch (props.userSubmission.status) {
        case 'pending': return 'Pending Review';
        case 'accepted': return 'Accepted';
        case 'rejected': return 'Rejected';
        default: return 'Unknown';
    }
});

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
        const pathParts = url.pathname.split('/').filter((part) => part);
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
    return currentUser && props.bounty.status === 'open' && currentUser.id !== ownerInfo.value.id && !props.userSubmission;
});

const refreshComments = () => {
    loadingComments.value = true;
    router.reload({
        only: ['comments'],
        onFinish: () => {
            loadingComments.value = false;
        },
    });
};

const hasComments = computed(() => {
    return props.comments && props.comments.data && props.comments.data.length > 0;
});

const shouldShowPagination = computed(() => {
    return props.comments && props.comments.links && props.comments.links.length > 3;
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="bounty.title" />

        <div class="px-4 py-6">
            <div class="mx-auto max-w-4xl space-y-6">
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
                            <div class="flex flex-wrap gap-2">
                                <Link v-if="canUserSubmit" :href="`/bounties/${bounty.id}/submit`" as="button">
                                    <Button class="flex items-center gap-2">
                                        <Target class="h-4 w-4" />
                                        Submit Solution
                                    </Button>
                                </Link>

                                <div v-else-if="userSubmission" class="flex items-center gap-2">
                                    <Badge :variant="submissionStatusVariant" class="capitalize">
                                        {{ submissionStatusText }}
                                    </Badge>
                                </div>

                                <!-- Bounty Owner Actions - Only show for bounty owner -->
                                <Button
                                    v-if="bounty.issue.repo.user_id === currentUser?.id && bounty.submissions && bounty.submissions.length > 0"
                                    variant="outline"
                                    :href="`/bounties/${bounty.id}/submissions`"
                                    class="flex items-center gap-2"
                                >
                                    <Users class="h-4 w-4" />
                                    Manage Submissions ({{ bounty.submissions.length }})
                                </Button>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="space-y-6">
                        <!-- User Submission Info (if exists) -->
                        <div v-if="userSubmission" class="border-l-4 border-l-blue-500 bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                            <div class="flex items-start justify-between">
                                <div>
                                    <h4 class="font-semibold text-blue-800 dark:text-blue-200 mb-1">Your Submission</h4>
                                    <p class="text-sm text-blue-700 dark:text-blue-300 mb-2">
                                        Status: {{ submissionStatusText }}
                                    </p>
                                    <div class="flex items-center gap-2">
                                        <a
                                            :href="userSubmission.pr_url"
                                            target="_blank"
                                            class="text-sm text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            View Pull Request
                                        </a>
                                        <span class="text-sm text-muted-foreground">
                                            • Submitted {{ formatDate(userSubmission.created_at) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                                <Target class="h-5 w-5" />
                                Bounty Description
                            </h3>
                            <div class="prose prose-sm max-w-none">
                                <div class="bg-gray-50 dark:bg-gray-900 border rounded-lg p-4">
                                    <p v-if="bounty.description && bounty.description.trim()"
                                       class="text-muted-foreground leading-relaxed whitespace-pre-wrap">
                                        {{ bounty.description }}
                                    </p>
                                    <p v-else class="text-muted-foreground italic">
                                        No description provided for this bounty.
                                    </p>
                                </div>
                            </div>
                        </div>

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

                        <!-- Technical Details -->
                        <div>
                            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                                <Tag class="h-5 w-5" />
                                Technical Details
                            </h3>

                            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                                <!-- Languages -->
                                <Card>
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Code class="h-4 w-4" />
                                            <span class="font-medium">Languages</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge
                                                v-for="language in bounty.languages"
                                                :key="language"
                                                variant="secondary"
                                                class="text-xs"
                                            >
                                                {{ language }}
                                            </Badge>
                                            <div v-if="!bounty.languages || bounty.languages.length === 0" class="text-sm text-muted-foreground">
                                                Not specified
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Created Date -->
                                <Card>
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Calendar class="h-4 w-4" />
                                            <span class="font-medium">Created</span>
                                        </div>
                                        <p class="text-sm">{{ formatDate(bounty.created_at) }}</p>
                                    </CardContent>
                                </Card>

                                <!-- Submissions Count -->
                                <Card>
                                    <CardContent class="p-4">
                                        <div class="flex items-center gap-2 mb-2">
                                            <Users class="h-4 w-4" />
                                            <span class="font-medium">Submissions</span>
                                        </div>
                                        <p class="text-sm">{{ bounty.submissions?.length || 0 }} submission(s)</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- GitHub Comments Section -->
                        <div>
                            <h3 class="text-lg font-semibold mb-4 flex items-center gap-2">
                                <MessageSquare class="h-5 w-5" />
                                GitHub Comments
                                <span v-if="comments?.total && comments.total > 0" class="text-sm text-muted-foreground">
                                    ({{ comments.total }})
                                </span>
                            </h3>

                            <!-- Loading State -->
                            <div v-if="loadingComments" class="flex items-center justify-center py-8">
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <div class="h-4 w-4 animate-spin rounded-full border-b-2 border-current"></div>
                                    <span>Loading comments...</span>
                                </div>
                            </div>

                            <!-- No Comments -->
                            <div v-else-if="!hasComments" class="text-center py-8">
                                <Card>
                                    <CardContent class="p-6">
                                        <MessageSquare class="mx-auto h-12 w-12 text-muted-foreground mb-3" />
                                        <p class="text-muted-foreground">No comments yet on this GitHub issue.</p>
                                        <a
                                            v-if="isValidGitHubUrl(bounty.issue?.url || '')"
                                            :href="bounty.issue?.url || '#'"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="inline-flex items-center gap-1 text-sm text-blue-600 hover:underline mt-2"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            Add a comment on GitHub
                                        </a>
                                    </CardContent>
                                </Card>
                            </div>

                            <!-- Comments List -->
                            <div v-else class="space-y-4">
                                <Card
                                    v-for="comment in comments?.data || []"
                                    :key="comment.id"
                                    class="hover:shadow-md transition-shadow"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-start gap-3">
                                            <!-- User Avatar -->
                                            <Avatar class="h-10 w-10 flex-shrink-0">
                                                <AvatarImage
                                                    :src="comment.user?.avatar_url || ''"
                                                    :alt="comment.user?.login || 'User'"
                                                />
                                                <AvatarFallback>
                                                    {{ (comment.user?.login || 'U').charAt(0).toUpperCase() }}
                                                </AvatarFallback>
                                            </Avatar>

                                            <!-- Comment Content -->
                                            <div class="flex-1 min-w-0">
                                                <!-- Comment Header -->
                                                <div class="flex items-center gap-2 mb-2">
                                                    <span class="font-semibold text-sm">{{ comment.user?.login || 'Unknown User' }}</span>
                                                    <Badge variant="outline" class="text-xs">
                                                        GitHub User
                                                    </Badge>
                                                    <span class="text-xs text-muted-foreground">
                                                        {{ formatDate(comment.created_at) }}
                                                    </span>
                                                    <a
                                                        :href="comment.html_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="ml-auto text-xs text-blue-600 hover:underline flex items-center gap-1"
                                                    >
                                                        <ExternalLink class="h-3 w-3" />
                                                        View on GitHub
                                                    </a>
                                                </div>

                                                <!-- Comment Body -->
                                                <div class="prose prose-sm max-w-none">
                                                    <div class="bg-gray-50 dark:bg-gray-900 border rounded-lg p-3">
                                                        <p class="text-sm leading-relaxed whitespace-pre-wrap break-words">
                                                            {{ comment.body || 'No content' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Comment Actions -->
                                                <div class="flex items-center gap-4 mt-2 text-xs text-muted-foreground">
                                                    <span v-if="comment.updated_at !== comment.created_at">
                                                        Edited {{ formatDate(comment.updated_at) }}
                                                    </span>
                                                    <span v-if="comment.reactions?.total_count > 0">
                                                        {{ comment.reactions.total_count }} reactions
                                                    </span>
                                                </div>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Pagination Component -->
                                <div v-if="shouldShowPagination">
                                    <Pagination :links="comments!.links" />
                                </div>

                                <!-- Refresh Button -->
                                <div class="text-center pt-4">
                                    <Button @click="refreshComments" variant="outline" size="sm" class="flex items-center gap-2 mx-auto" :disabled="loadingComments">
                                        <MessageSquare class="h-4 w-4" />
                                        Refresh Comments
                                    </Button>
                                </div>
                            </div>
                        </div>

                        <!-- Submissions List (if any exist) -->
                        <div v-if="bounty.submissions && bounty.submissions.length > 0">
                            <h3 class="text-lg font-semibold mb-3 flex items-center gap-2">
                                <Users class="h-5 w-5" />
                                Submissions ({{ bounty.submissions.length }})
                            </h3>

                            <div class="space-y-3">
                                <Card
                                    v-for="submission in bounty.submissions"
                                    :key="submission.id"
                                    class="hover:shadow-md transition-shadow"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <Avatar class="h-8 w-8">
                                                    <AvatarImage
                                                        :src="`https://github.com/${submission.user.nickname}.png`"
                                                        :alt="submission.user.name"
                                                    />
                                                    <AvatarFallback>
                                                        {{ submission.user.name?.charAt(0)?.toUpperCase() || 'U' }}
                                                    </AvatarFallback>
                                                </Avatar>

                                                <div>
                                                    <p class="font-medium">{{ submission.user.name }}</p>
                                                    <p class="text-sm text-muted-foreground">@{{ submission.user.nickname }}</p>
                                                </div>
                                            </div>

                                            <div class="flex items-center gap-3">
                                                <a
                                                    v-if="submission.pr_url"
                                                    :href="submission.pr_url"
                                                    target="_blank"
                                                    class="text-sm text-blue-600 hover:text-blue-800 transition-colors flex items-center gap-1"
                                                >
                                                    <ExternalLink class="h-3 w-3" />
                                                    PR
                                                </a>
                                                <Badge
                                                    :variant="submission.status === 'accepted' ? 'default' : 'secondary'"
                                                    class="text-xs"
                                                >
                                                    {{ submission.status.toUpperCase() }}
                                                </Badge>
                                                <span class="text-sm text-muted-foreground">
                                                    {{ formatDate(submission.created_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
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
        </div>
    </AppLayout>
</template>
