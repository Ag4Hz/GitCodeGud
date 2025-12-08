<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useInitials } from '@/composables/useInitials';
import { useProviderUtils } from '@/composables/ useProviderUtils';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { type Bounty } from '@/types/bounty';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    Calendar,
    CheckCircle,
    Clock,
    Code,
    DollarSign,
    ExternalLink,
    GitBranch,
    MessageSquare,
    Tag,
    Target,
    User as UserIcon,
    Users,
    XCircle,
} from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface SubmissionType {
    id: number;
    status: string;
    user: User;
    created_at: string;
    pr_url?: string;
}

interface BountyWithDetails extends Bounty {
    issue: {
        url: string;
        provider: 'github' | 'gitlab' | 'bitbucket';
        description?: string;
        repo: {
            url: string;
            git_id: string;
            name?: string;
            user_id: number;
            user?: User;
        };
    };
    submissions: SubmissionType[];
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
        title: 'Bounty Details',
        href: `/bounties/${props.bounty.id}`,
    },
]);

const { formatDate } = useDateFormatter();
const { getInitials } = useInitials();
const { getProviderConfig, getProviderName, extractProviderFromUrl } = useProviderUtils();

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

const getSubmissionStatusIcon = (status: string) => {
    switch (status) {
        case 'accepted':
            return CheckCircle;
        case 'rejected':
            return XCircle;
        case 'pending':
        default:
            return Clock;
    }
};

const getSubmissionStatusColor = (status: string) => {
    switch (status) {
        case 'accepted':
            return 'bg-green-100/30 text-green-800 dark:bg-green-900/30 dark:text-green-200';
        case 'rejected':
            return 'bg-red-100/30 text-red-800 dark:bg-red-900/30 dark:text-red-200';
        case 'pending':
        default:
            return 'bg-yellow-100/30 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
    }
};

const submissionStatusVariant = computed(() => {
    if (!props.userSubmission) return 'outline';
    switch (props.userSubmission.status) {
        case 'pending':
            return 'secondary';
        case 'accepted':
            return 'default';
        case 'rejected':
            return 'destructive';
        default:
            return 'outline';
    }
});

const submissionStatusText = computed(() => {
    if (!props.userSubmission) return '';
    switch (props.userSubmission.status) {
        case 'pending':
            return 'Pending Review';
        case 'accepted':
            return 'Accepted';
        case 'rejected':
            return 'Rejected';
        default:
            return 'Unknown';
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
    return (
        currentUser &&
        props.bounty.status === 'open' &&
        currentUser.id !== ownerInfo.value.id &&
        (!props.userSubmission || props.userSubmission.status === 'rejected')
    );
});

const isBountyOwner = computed(() => {
    return !!(currentUser && props.bounty.issue.repo.user_id === currentUser.id);
});

const submissions = computed(() => props.bounty.submissions || []);

const hasSubmissions = computed(() => submissions.value.length > 0);

const submissionStats = computed(() => {
    const subs = submissions.value;
    return {
        total: subs.length,
        pending: subs.filter((s) => s.status === 'pending').length,
        accepted: subs.filter((s) => s.status === 'accepted').length,
        rejected: subs.filter((s) => s.status === 'rejected').length,
    };
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
                <Card
                    class="gap-0 rounded-2xl border border-gray-200 bg-white/40 p-0 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5"
                >
                    <CardHeader class="rounded-t-2xl bg-white/40 px-2 py-4 backdrop-blur-xl sm:px-5 md:px-6 dark:bg-white/5">
                        <div class="flex items-start justify-between">
                            <div class="space-y-2">
                                <div class="flex items-center gap-3">
                                    <!-- GitHub Icon -->
                                    <svg
                                        v-if="bounty.issue?.provider === 'github'"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        :class="getProviderConfig(bounty.issue?.provider).color"
                                        class="h-6 w-6 flex-shrink-0"
                                        fill="currentColor"
                                    >
                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                    </svg>

                                    <!-- GitLab Icon -->
                                    <svg
                                        v-else-if="bounty.issue?.provider === 'gitlab'"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        :class="getProviderConfig(bounty.issue?.provider).color"
                                        class="h-6 w-6 flex-shrink-0"
                                        fill="currentColor"
                                    >
                                        <path d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"/>
                                    </svg>

                                    <!-- Bitbucket Icon -->
                                    <svg
                                        v-else-if="bounty.issue?.provider === 'bitbucket'"
                                        xmlns="http://www.w3.org/2000/svg"
                                        viewBox="0 0 24 24"
                                        :class="getProviderConfig(bounty.issue?.provider).color"
                                        class="h-6 w-6 flex-shrink-0"
                                        fill="currentColor"
                                    >
                                        <path d="M2.4 3A1.3 1.3 0 0 0 1.1 4.5l2.7 15.9c.1.5.6.9 1.2.9h13a1.3 1.3 0 0 0 1.2-1.1l2.7-15.7A1.3 1.3 0 0 0 20.7 3H2.4zm9.6 12.3H9.3l-.9-6.6h7.2l-.9 6.6h-2.7z"/>
                                    </svg>

                                    <h1 class="text-2xl font-bold">{{ bounty.title }}</h1>
                                    <Badge :class="getStatusColor(bounty.status)" class="text-sm">
                                        {{ getStatusDisplayText(bounty.status) }}
                                    </Badge>
                                </div>
                                <Badge variant="custom" class="px-2 py-1 text-xs"> Will be awarded on acceptance </Badge>
                            </div>

                            <!-- Action Buttons -->
                            <div class="flex flex-wrap gap-2">
                                <Link v-if="canUserSubmit && !userSubmission" :href="`/bounties/${bounty.id}/submit`" as="button">
                                    <Button class="flex items-center gap-2">
                                        <Target class="h-4 w-4" />
                                        Submit Solution
                                    </Button>
                                </Link>

                                <Link
                                    v-else-if="canUserSubmit && userSubmission && userSubmission.status === 'rejected'"
                                    :href="`/bounties/${bounty.id}/submit`"
                                    as="button"
                                >
                                    <Button variant="destructive" class="flex items-center gap-2">
                                        <Target class="h-4 w-4" />
                                        Resubmit Solution
                                    </Button>
                                </Link>

                                <div v-else-if="userSubmission" class="flex items-center gap-2">
                                    <Badge :variant="submissionStatusVariant" class="capitalize">
                                        {{ submissionStatusText }}
                                    </Badge>
                                    <span v-if="userSubmission.status === 'accepted'" class="text-sm font-medium text-green-600"> XP Awarded! </span>
                                </div>

                                <!-- Bounty Owner Actions -->
                                <Link v-if="isBountyOwner && hasSubmissions" :href="`/bounties/${bounty.id}/submissions`">
                                    <Button variant="button" class="flex items-center gap-2">
                                        <Users class="h-4 w-4" />
                                        Manage Submissions ({{ submissions.length }})
                                    </Button>
                                </Link>
                            </div>
                        </div>
                    </CardHeader>

                    <CardContent class="mt-6 space-y-6">
                        <!-- Reward XP -->
                        <div class="flex animate-pulse items-center justify-center gap-1 text-2xl font-semibold text-green-600">
                            <DollarSign class="h-7 w-7" />
                            <span>{{ bounty.reward_xp }} XP Reward</span>
                        </div>

                        <div v-if="userSubmission" class="rounded-lg border-l-4 border-l-purple-500 bg-purple-50 p-4 dark:bg-purple-900/20">
                            <div class="flex items-start justify-between">
                                <div class="flex-1">
                                    <h4 class="mb-1 font-semibold text-purple-800 dark:text-purple-200">Your Submission</h4>
                                    <p class="mb-2 text-sm text-purple-700 dark:text-purple-300">Status: {{ submissionStatusText }}</p>
                                    <div class="mb-3 flex items-center gap-2">
                                        <component
                                            :is="getSubmissionStatusIcon(userSubmission.status)"
                                            class="h-4 w-4"
                                            :class="
                                                userSubmission.status === 'accepted'
                                                    ? 'text-green-600'
                                                    : userSubmission.status === 'rejected'
                                                      ? 'text-red-600'
                                                      : 'text-yellow-600'
                                            "
                                        />
                                        <span
                                            class="text-sm"
                                            :class="
                                                userSubmission.status === 'accepted'
                                                    ? 'text-green-700 dark:text-green-300'
                                                    : userSubmission.status === 'rejected'
                                                      ? 'text-red-700 dark:text-red-300'
                                                      : 'text-blue-700 dark:text-blue-300'
                                            "
                                        >
                                            Status: {{ submissionStatusText }}
                                        </span>
                                        <Badge
                                            v-if="userSubmission.status === 'accepted'"
                                            variant="secondary"
                                            class="bg-green-100 text-xs text-green-800"
                                        >
                                            {{ bounty.reward_xp }} XP Earned
                                        </Badge>
                                    </div>

                                    <div class="mb-3 flex items-center gap-2">
                                        <a
                                            :href="userSubmission.pr_url"
                                            target="_blank"
                                            class="flex items-center gap-1 text-sm text-purple-600 transition-colors hover:text-purple-800"
                                        >
                                            <ExternalLink class="h-3 w-3" />
                                            View Pull Request
                                        </a>
                                        <span class="text-sm text-muted-foreground"> • Submitted {{ formatDate(userSubmission.created_at) }} </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Submission Stats for Bounty Owner -->
                        <h3 class="mb-2 flex items-center gap-2 text-lg font-semibold">Submission Overview</h3>
                        <div
                            v-if="isBountyOwner && hasSubmissions"
                            class="w-full rounded-xl border border-gray-200 bg-white/40 p-6 dark:border-white/10 dark:bg-white/5"
                        >
                            <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                                <div class="text-center">
                                    <div class="text-2xl font-bold">{{ submissionStats.total }}</div>
                                    <div class="text-sm text-muted-foreground">Total</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-yellow-600">{{ submissionStats.pending }}</div>
                                    <div class="text-sm text-muted-foreground">Pending</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-green-600">{{ submissionStats.accepted }}</div>
                                    <div class="text-sm text-muted-foreground">Accepted</div>
                                </div>
                                <div class="text-center">
                                    <div class="text-2xl font-bold text-red-600">{{ submissionStats.rejected }}</div>
                                    <div class="text-sm text-muted-foreground">Rejected</div>
                                </div>
                            </div>
                        </div>

                        <!-- Description -->
                        <div>
                            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold">
                                <Target class="h-5 w-5" />
                                Bounty Description
                            </h3>
                            <div class="prose prose-sm max-w-none">
                                <div class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                    <p
                                        v-if="bounty.description && bounty.description.trim()"
                                        class="leading-relaxed whitespace-pre-wrap text-muted-foreground"
                                    >
                                        {{ bounty.description }}
                                    </p>
                                    <p v-else class="text-muted-foreground italic">No description provided for this bounty.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Repository and Issue Links -->
                        <div>
                            <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
                                <GitBranch class="h-5 w-5" />
                                Repository & Issue
                            </h3>

                            <div class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                                <!-- Repository Link -->
                                <Card
                                    class="group border-l-4 border-l-purple-500 bg-white/40 transition-all duration-300 hover:shadow-lg dark:bg-white/10"
                                >
                                    <CardContent class="p-6">
                                        <div class="space-y-4">
                                            <div class="flex items-start gap-3">
                                                <div class="rounded-lg bg-purple-100 p-2 dark:bg-purple-900">
                                                    <GitBranch class="h-5 w-5 text-purple-600 dark:text-purple-400" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="mb-1 text-lg font-semibold">Repository</h4>
                                                    <p class="truncate font-mono text-sm text-muted-foreground">
                                                        {{ getRepositoryName(bounty.issue?.repo?.url || '') }}
                                                    </p>
                                                </div>
                                            </div>

                                            <a
                                                v-if="isValidGitHubUrl(bounty.issue?.repo?.url || '')"
                                                :href="bounty.issue?.repo?.url || '#'"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-purple-600 px-4 py-3 text-sm font-medium text-white transition-all duration-200 group-hover:scale-105 hover:bg-purple-700"
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                                View Repository
                                            </a>
                                            <div
                                                v-else
                                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/40 px-4 py-3 text-sm text-gray-500 dark:bg-white/10"
                                            >
                                                <span>Invalid Repository URL</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <!-- Issue Link -->
                                <Card
                                    class="group border-l-4 border-l-green-800 bg-white/40 transition-all duration-300 hover:shadow-lg dark:bg-white/10"
                                >
                                    <CardContent class="p-6">
                                        <div class="space-y-4">
                                            <div class="flex items-start gap-3">
                                                <div class="rounded-lg bg-green-100 p-2 dark:bg-green-800">
                                                    <Target class="h-5 w-5 text-green-600 dark:text-green-400" />
                                                </div>
                                                <div class="min-w-0 flex-1">
                                                    <h4 class="mb-1 text-lg font-semibold">{{ getProviderName(bounty.issue?.provider) }} Issue</h4>
                                                    <p class="text-sm text-muted-foreground">View the specific issue to resolve</p>
                                                </div>
                                            </div>

                                            <a
                                                v-if="isValidGitHubUrl(bounty.issue?.url || '')"
                                                :href="bounty.issue?.url || '#'"
                                                target="_blank"
                                                rel="noopener noreferrer"
                                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-green-800 px-4 py-3 text-sm font-medium text-white transition-all duration-200 group-hover:scale-105 hover:bg-green-700"
                                            >
                                                <ExternalLink class="h-4 w-4" />
                                                View Issue on {{ getProviderName(bounty.issue?.provider) }}
                                            </a>
                                            <div
                                                v-else
                                                class="flex w-full items-center justify-center gap-2 rounded-lg bg-white/40 px-4 py-3 text-sm text-gray-500 dark:bg-white/10"
                                            >
                                                <span>Invalid Issue URL</span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Owner Information -->
                        <div>
                            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold">
                                <UserIcon class="h-5 w-5" />
                                Bounty Owner
                            </h3>

                            <Card class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                <CardContent class="p-4">
                                    <div class="flex items-center gap-4">
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage :src="ownerInfo.avatar || ''" :alt="ownerInfo.name" />
                                            <AvatarFallback class="text-lg">
                                                {{ getInitials(ownerInfo.name) }}
                                            </AvatarFallback>
                                        </Avatar>

                                        <div class="flex-1">
                                            <div class="flex items-center gap-2">
                                                <h4 class="font-semibold">{{ ownerInfo.name }}</h4>
                                                <Badge variant="outline" class="text-xs"> @{{ ownerInfo.nickname }} </Badge>
                                            </div>
                                            <p class="text-sm text-muted-foreground">Repository Owner</p>
                                        </div>

                                        <!-- Contact Owner Button -->
                                        <Link :href="`/users/${ownerInfo.id}`">
                                            <Button variant="button" size="sm" class="flex items-center gap-2">
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
                            <h3 class="mb-3 flex items-center gap-2 text-lg font-semibold">
                                <Tag class="h-5 w-5" />
                                Technical Details
                            </h3>

                            <div class="grid grid-cols-1 gap-4 lg:grid-cols-3">
                                <!-- Languages -->
                                <Card class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                    <CardContent class="p-4">
                                        <div class="mb-2 flex items-center gap-2">
                                            <Code class="h-4 w-4" />
                                            <span class="font-medium">Languages</span>
                                        </div>
                                        <div class="flex flex-wrap gap-1">
                                            <Badge v-for="language in bounty.languages" :key="language" variant="custom" class="px-2 py-1 text-xs">
                                                {{ language }}
                                            </Badge>
                                            <div v-if="!bounty.languages || bounty.languages.length === 0" class="text-sm text-muted-foreground">
                                                Not specified
                                            </div>
                                        </div>
                                        <p v-if="bounty.languages && bounty.languages.length > 0" class="mt-2 text-xs text-muted-foreground">
                                            XP will be distributed across these languages
                                        </p>
                                    </CardContent>
                                </Card>

                                <!-- Created Date -->
                                <Card class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                    <CardContent class="p-4">
                                        <div class="mb-2 flex items-center gap-2">
                                            <Calendar class="h-4 w-4" />
                                            <span class="font-medium">Created</span>
                                        </div>
                                        <p class="text-sm">{{ formatDate(bounty.created_at) }}</p>
                                    </CardContent>
                                </Card>

                                <!-- Submissions Count -->
                                <Card class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                    <CardContent class="p-4">
                                        <div class="mb-2 flex items-center gap-2">
                                            <Users class="h-4 w-4" />
                                            <span class="font-medium">Submissions</span>
                                        </div>
                                        <p class="text-sm">{{ submissions.length }} submission(s)</p>
                                    </CardContent>
                                </Card>
                            </div>
                        </div>

                        <!-- Submissions List (enhanced for preview) -->
                        <div v-if="hasSubmissions">
                            <div class="mb-3 flex items-center justify-between">
                                <h3 class="flex items-center gap-2 text-lg font-semibold">
                                    <Users class="h-5 w-5" />
                                    Recent Submissions ({{ submissions.length }})
                                </h3>
                                <Link v-if="isBountyOwner" :href="`/bounties/${bounty.id}/submissions`" class="flex items-center gap-1">
                                    <Button size="sm" variant="button" class="flex items-center gap-1">
                                        <Users class="h-3 w-3" />
                                        Manage All
                                    </Button>
                                </Link>
                            </div>

                            <div class="space-y-3">
                                <Card
                                    v-for="submission in submissions.slice(0, 3)"
                                    :key="submission.id"
                                    class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <!-- Provider Icon - Same as Dashboard -->
                                                <div class="flex-shrink-0">
                                                    <!-- GitHub Icon -->
                                                    <svg
                                                        v-if="extractProviderFromUrl(submission.pr_url) === 'github'"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        :class="getProviderConfig('github').color"
                                                        class="h-5 w-5"
                                                        fill="currentColor"
                                                    >
                                                        <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                                    </svg>

                                                    <!-- GitLab Icon -->
                                                    <svg
                                                        v-else-if="extractProviderFromUrl(submission.pr_url) === 'gitlab'"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        :class="getProviderConfig('gitlab').color"
                                                        class="h-5 w-5"
                                                        fill="currentColor"
                                                    >
                                                        <path d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"/>
                                                    </svg>

                                                    <!-- Bitbucket Icon -->
                                                    <svg
                                                        v-else-if="extractProviderFromUrl(submission.pr_url) === 'bitbucket'"
                                                        xmlns="http://www.w3.org/2000/svg"
                                                        viewBox="0 0 24 24"
                                                        :class="getProviderConfig('bitbucket').color"
                                                        class="h-5 w-5"
                                                        fill="currentColor"
                                                    >
                                                        <path d="M2.4 3A1.3 1.3 0 0 0 1.1 4.5l2.7 15.9c.1.5.6.9 1.2.9h13a1.3 1.3 0 0 0 1.2-1.1l2.7-15.7A1.3 1.3 0 0 0 20.7 3H2.4zm9.6 12.3H9.3l-.9-6.6h7.2l-.9 6.6h-2.7z"/>
                                                    </svg>
                                                </div>

                                                <Avatar class="h-8 w-8">
                                                    <AvatarImage :src="submission.user.avatar || ''" :alt="submission.user.name" />
                                                    <AvatarFallback>
                                                        {{ getInitials(submission.user.name || submission.user.nickname || '') }}
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
                                                    class="flex items-center gap-1 text-sm text-purple-600 transition-colors hover:text-purple-800"
                                                >
                                                    <ExternalLink class="h-3 w-3" />
                                                    {{ extractProviderFromUrl(submission.pr_url) === 'gitlab' ? 'MR' : 'PR' }}
                                                </a>
                                                <Badge :class="getSubmissionStatusColor(submission.status)" class="text-xs">
                                                    <component :is="getSubmissionStatusIcon(submission.status)" class="mr-1 h-3 w-3" />
                                                    {{ submission.status.toUpperCase() }}
                                                </Badge>
                                                <span class="text-sm text-muted-foreground">
                                                    {{ formatDate(submission.created_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </CardContent>
                                </Card>

                                <div v-if="submissions.length > 3" class="pt-2 text-center">
                                    <Link :href="`/bounties/${bounty.id}/submissions`">
                                        <Button variant="outline" size="sm" class="text-sm">
                                            View {{ submissions.length - 3 }} more submissions
                                        </Button>
                                    </Link>
                                </div>
                            </div>
                        </div>

                        <!-- GitHub Comments Section -->
                        <div>
                            <h3 class="mb-4 flex items-center gap-2 text-lg font-semibold">
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
                            <div v-else-if="!hasComments" class="py-8 text-center">
                                <Card class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                                    <CardContent class="p-6">
                                        <MessageSquare class="mx-auto mb-3 h-12 w-12 text-muted-foreground" />
                                        <p class="text-muted-foreground">No comments yet on this GitHub issue.</p>
                                        <a
                                            v-if="isValidGitHubUrl(bounty.issue?.url || '')"
                                            :href="bounty.issue?.url || '#'"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="mt-2 inline-flex items-center gap-1 text-sm text-purple-600 hover:underline"
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
                                    class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5"
                                >
                                    <CardContent class="p-4">
                                        <div class="flex items-start gap-3">
                                            <!-- User Avatar -->
                                            <Avatar class="h-10 w-10 flex-shrink-0">
                                                <AvatarImage :src="comment.user?.avatar_url || ''" :alt="comment.user?.login || 'User'" />
                                                <AvatarFallback>
                                                    {{ getInitials(comment.user?.login || '') }}
                                                </AvatarFallback>
                                            </Avatar>

                                            <!-- Comment Content -->
                                            <div class="min-w-0 flex-1">
                                                <!-- Comment Header -->
                                                <div class="mb-2 flex items-center gap-2">
                                                    <span class="text-sm font-semibold">{{ comment.user?.login || 'Unknown User' }}</span>
                                                    <Badge variant="outline" class="text-xs"> GitHub User </Badge>
                                                    <span class="text-xs text-muted-foreground">
                                                        {{ formatDate(comment.created_at) }}
                                                    </span>
                                                    <a
                                                        :href="comment.html_url"
                                                        target="_blank"
                                                        rel="noopener noreferrer"
                                                        class="ml-auto flex items-center gap-1 text-xs text-purple-600 hover:underline"
                                                    >
                                                        <ExternalLink class="h-3 w-3" />
                                                        View on GitHub
                                                    </a>
                                                </div>

                                                <!-- Comment Body -->
                                                <div class="prose prose-sm max-w-none">
                                                    <div
                                                        class="w-full rounded-xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5"
                                                    >
                                                        <p class="text-sm leading-relaxed break-words whitespace-pre-wrap">
                                                            {{ comment.body || 'No content' }}
                                                        </p>
                                                    </div>
                                                </div>

                                                <!-- Comment Actions -->
                                                <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
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
                                <div class="mb-6 pt-4 text-center">
                                    <Button
                                        @click="refreshComments"
                                        variant="button"
                                        size="sm"
                                        class="mx-auto flex items-center gap-2"
                                        :disabled="loadingComments"
                                    >
                                        <MessageSquare class="h-4 w-4" />
                                        Refresh Comments
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
