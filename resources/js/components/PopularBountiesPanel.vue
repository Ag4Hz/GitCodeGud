<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { Calendar, DollarSign, Eye, Target, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';

enum BountyStatus {
    OPEN = 'open',
    CLOSED = 'closed',
}

interface Bounty {
    id: number;
    title: string;
    description: string;
    reward_xp: number;
    views?: number;
    status: BountyStatus;
    created_at: string;
    updated_at: string;
    deleted_at: string | null;
    languages?: string[];
    issue: {
        url: string;
        repo: {
            url: string;
        };
    };
    submissions_count?: number;
    popularity_score?: number;
}

interface Props {
    bounties: Bounty[];
    title?: string;
}

const props = withDefaults(defineProps<Props>(), {
    bounties: () => [],
    title: 'Popular Bounties',
});

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const getStatusColor = (status: BountyStatus) => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case BountyStatus.CLOSED:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const navigateToBounty = (bounty: Bounty) => {
    router.visit(route('bounties.show', { bounty: bounty.id }));
};

const sortedBounties = computed(() => {
    return [...props.bounties].sort((a, b) => (b.popularity_score ?? 0) - (a.popularity_score ?? 0)).slice(0, 8);
});
</script>

<template>
    <Card v-if="props.bounties.length > 0" class="h-fit">
        <CardHeader class="pb-3">
            <CardTitle class="flex items-center gap-2 text-lg">
                <TrendingUp class="h-5 w-5 text-orange-500" />
                {{ props.title }}
            </CardTitle>
        </CardHeader>

        <CardContent class="space-y-0 p-4">
            <div class="max-h-96 overflow-y-auto pr-2">
                <div class="space-y-2">
                    <div
                        v-for="(bounty, index) in sortedBounties"
                        :key="bounty.id"
                        class="group cursor-pointer rounded-lg border border-transparent p-3 transition-all hover:border-gray-200 hover:bg-gray-50 dark:hover:border-gray-600 dark:hover:bg-gray-700/50"
                        @click="navigateToBounty(bounty)"
                    >
                        <div class="flex items-start justify-between gap-3">
                            <!-- Rank and Content -->
                            <div class="flex min-w-0 flex-1 items-start gap-3">
                                <!-- Popularity Rank -->
                                <div
                                    class="flex h-6 w-6 flex-shrink-0 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-800 dark:bg-orange-900 dark:text-orange-200"
                                >
                                    {{ index + 1 }}
                                </div>

                                <!-- Bounty Info -->
                                <div class="min-w-0 flex-1 space-y-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4
                                            class="line-clamp-1 text-sm font-medium transition-colors group-hover:text-blue-600 dark:group-hover:text-blue-400"
                                        >
                                            {{ bounty.title }}
                                        </h4>
                                        <span :class="`flex-shrink-0 rounded-md px-2 py-1 text-xs ${getStatusColor(bounty.status)}`">
                                            {{ bounty.status.toUpperCase() }}
                                        </span>
                                    </div>

                                    <!-- Languages-->
                                    <div v-if="bounty.languages && bounty.languages.length > 0" class="flex flex-wrap gap-1">
                                        <Badge v-for="language in bounty.languages.slice(0, 2)" :key="language" variant="outline" class="text-xs">
                                            {{ language }}
                                        </Badge>
                                        <span v-if="bounty.languages.length > 2" class="text-xs text-gray-500 dark:text-gray-400">
                                            +{{ bounty.languages.length - 2 }}
                                        </span>
                                    </div>

                                    <!-- Stats Row -->
                                    <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-gray-400">
                                        <div class="flex items-center gap-1 text-yellow-600 dark:text-yellow-500">
                                            <DollarSign class="h-3 w-3" />
                                            {{ bounty.reward_xp }} XP
                                        </div>
                                        <div v-if="bounty.views && bounty.views > 0" class="flex items-center gap-1">
                                            <Eye class="h-3 w-3" />
                                            {{ bounty.views }}
                                        </div>
                                        <div v-if="bounty.submissions_count && bounty.submissions_count > 0" class="flex items-center gap-1">
                                            <Target class="h-3 w-3" />
                                            {{ bounty.submissions_count }}
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(bounty.created_at) }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Popularity Score -->
                            <div class="flex flex-shrink-0 flex-col items-end text-right">
                                <div class="text-xs font-medium text-orange-600 dark:text-orange-400">{{ bounty.popularity_score ?? 0 }} pts</div>
                                <div class="text-xs text-gray-500 dark:text-gray-400">popularity</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-if="props.bounties.length === 0" class="py-8 text-center text-gray-500 dark:text-gray-400">
                <TrendingUp class="mx-auto mb-2 h-8 w-8 opacity-50" />
                <p class="text-sm">No popular bounties yet</p>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
.line-clamp-1 {
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
