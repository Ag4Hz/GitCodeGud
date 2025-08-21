<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Calendar, DollarSign, ExternalLink, Target } from 'lucide-vue-next';

interface Bounty {
    id: number;
    title: string;
    description: string;
    reward_xp: number;
    status: 'open' | 'closed';
    created_at: string;
    updated_at: string;
    issue: {
        url: string;
        repo: {
            url: string;
        };
    };
    submissions_count?: number;
}

interface Props {
    bounties: {
        data: Bounty[];
        total: number;
        current_page: number;
        last_page: number;
    };
}

const props = withDefaults(defineProps<Props>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
});

const getStatusColor = (status: string) => {
    return status === 'open'
        ? 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
        : 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const totalRewardXP = computed(() => {
    return props.bounties.data.reduce((sum, bounty) => sum + bounty.reward_xp, 0);
});

const openBounties = computed(() => {
    return props.bounties.data.filter(bounty => bounty.status === 'open');
});

const closedBounties = computed(() => {
    return props.bounties.data.filter(bounty => bounty.status === 'closed');
});
</script>

<template>
    <Card>
        <CardHeader>
            <div>
                <CardTitle class="flex items-center gap-2">
                    <Target class="h-5 w-5" />
                    My Bounties
                </CardTitle>
                <CardDescription>
                    View your bounty campaigns and track their progress
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4 mb-6">
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <Target class="h-4 w-4 text-blue-600" />
                        <span class="text-sm font-medium">Total Bounties</span>
                    </div>
                    <p class="text-2xl font-bold">{{ bounties.total }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-4 w-4 rounded-full bg-green-600"></div>
                        <span class="text-sm font-medium">Open</span>
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ openBounties.length }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-4 w-4 rounded-full bg-gray-600"></div>
                        <span class="text-sm font-medium">Closed</span>
                    </div>
                    <p class="text-2xl font-bold text-gray-600">{{ closedBounties.length }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSign class="h-4 w-4 text-yellow-600" />
                        <span class="text-sm font-medium">Total Reward XP</span>
                    </div>
                    <p class="text-2xl font-bold text-yellow-600">{{ totalRewardXP }}</p>
                </div>
            </div>

            <!-- Bounties List -->
            <div v-if="bounties.data.length > 0" class="space-y-4">
                <div
                    v-for="bounty in bounties.data"
                    :key="bounty.id"
                    class="border rounded-lg p-4 hover:bg-accent/50 transition-colors"
                >
                    <div class="space-y-3">
                        <!-- Title and Status -->
                        <div class="flex items-center gap-3 flex-wrap">
                            <h3 class="font-medium text-lg">{{ bounty.title }}</h3>
                            <Badge :class="getStatusColor(bounty.status)" class="text-xs">
                                {{ bounty.status.toUpperCase() }}
                            </Badge>
                        </div>

                        <!-- Description -->
                        <p v-if="bounty.description" class="text-muted-foreground line-clamp-2">
                            {{ bounty.description }}
                        </p>

                        <!-- Repository and Issue Links -->
                        <div class="flex items-center gap-4 text-sm">
                            <a
                                :href="bounty.issue.repo.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-blue-600 hover:underline flex items-center gap-1"
                            >
                                <ExternalLink class="h-3 w-3" />
                                Repository
                            </a>
                            <a
                                :href="bounty.issue.url"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-blue-600 hover:underline flex items-center gap-1"
                            >
                                <ExternalLink class="h-3 w-3" />
                                Issue
                            </a>
                        </div>

                        <!-- Metadata -->
                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                            <span class="flex items-center gap-1">
                                <DollarSign class="h-3 w-3" />
                                {{ bounty.reward_xp }} XP
                            </span>
                            <span class="flex items-center gap-1">
                                <Calendar class="h-3 w-3" />
                                Created {{ formatDate(bounty.created_at) }}
                            </span>
                            <span v-if="bounty.submissions_count && bounty.submissions_count > 0" class="flex items-center gap-1">
                                <Target class="h-3 w-3" />
                                {{ bounty.submissions_count }} submission{{ bounty.submissions_count !== 1 ? 's' : '' }}
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="bounties.last_page > 1" class="flex justify-center mt-6">
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="bounties.current_page > 1"
                            :href="route('profile.show', { page: bounties.current_page - 1 })"
                            class="px-3 py-2 text-sm border rounded hover:bg-accent"
                        >
                            Previous
                        </Link>
                        <span class="text-sm text-muted-foreground px-3">
                            Page {{ bounties.current_page }} of {{ bounties.last_page }}
                        </span>
                        <Link
                            v-if="bounties.current_page < bounties.last_page"
                            :href="route('profile.show', { page: bounties.current_page + 1 })"
                            class="px-3 py-2 text-sm border rounded hover:bg-accent"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <Target class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold mb-2">No bounties yet</h3>
                <p class="text-muted-foreground">
                    Create your first bounty above to start incentivizing contributions to your projects.
                </p>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
