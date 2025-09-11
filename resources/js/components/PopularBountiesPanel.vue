<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { router } from '@inertiajs/vue3';
import { Calendar, DollarSign, Eye, Target, TrendingUp } from 'lucide-vue-next';
import { computed } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';

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
            return 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200';
        case BountyStatus.CLOSED:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700/70 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700/70 dark:text-gray-200';
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
    <Card class="gap-0 rounded-2xl border-gray-200 bg-white/40 p-0 dark:border-white/10 dark:bg-white/5">
        <CardHeader class="rounded-t-2xl bg-white/40 px-2 py-4 backdrop-blur-xl sm:px-5 md:px-6 dark:bg-white/5">
            <CardTitle class="font-medium text-gray-600 dark:text-gray-300">
                {{ props.title }}
            </CardTitle>
        </CardHeader>

        <CardContent class="m-0 p-0">
            <PerfectScrollbar class="max-h-96 w-full rounded-xl" :options="{ suppressScrollX: true }">
                <div v-if="props.bounties.length > 0" class="relative">
                    <ul class="m-0 max-h-96 divide-y divide-gray-200 dark:divide-white/10">
                        <li
                            v-for="(bounty, index) in sortedBounties"
                            :key="bounty.id"
                            role="button"
                            tabindex="0"
                            @click="navigateToBounty(bounty)"
                            class="group flex items-stretch gap-5 px-3 py-3 transition-colors hover:bg-white/50 focus:bg-gray-50 focus:outline-none sm:px-4 sm:py-4 md:px-5 dark:hover:bg-white/5 dark:focus:bg-white/5"
                        >
                            <div
                                class="flex h-9 w-9 flex-shrink-0 items-center justify-center self-center rounded-xl border border-gray-200 bg-white/60 text-sm font-semibold text-gray-700 dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
                            >
                                {{ index + 1 }}
                            </div>

                            <div class="grid min-w-0 flex-1 grid-cols-1 items-start gap-2 sm:grid-cols-[1fr_auto]">
                                <div class="min-w-0">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h4 class="min-w-0 flex-1 truncate text-sm font-semibold text-gray-900 sm:text-base dark:text-gray-100">
                                            {{ bounty.title }}
                                        </h4>

                                        <span
                                            class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="getStatusColor(bounty.status)"
                                        >
                                            {{ bounty.status.toUpperCase() }}
                                        </span>
                                    </div>

                                    <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-2 text-xs sm:text-[13px] dark:text-gray-300">
                                        <div class="inline-flex items-center gap-1 text-yellow-700">
                                            <DollarSign class="h-4 w-4" />
                                            <span class="tabular-nums">{{ bounty.reward_xp }} XP</span>
                                        </div>

                                        <div v-if="bounty.views && bounty.views > 0" class="inline-flex items-center gap-1">
                                            <Eye class="h-4 w-4" />
                                            <span class="tabular-nums">{{ bounty.views }}</span>
                                        </div>

                                        <div v-if="bounty.submissions_count && bounty.submissions_count > 0" class="inline-flex items-center gap-1">
                                            <Target class="h-4 w-4" />
                                            <span class="tabular-nums">{{ bounty.submissions_count }}</span>
                                        </div>

                                        <div class="inline-flex items-center gap-1">
                                            <Calendar class="h-4 w-4" />
                                            <span>{{ formatDate(bounty.created_at) }}</span>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-col items-end justify-between sm:items-center sm:justify-center">
                                    <div
                                        class="rounded-xl border border-purple-200 bg-purple-50/70 px-3 py-1 text-xs font-semibold text-purple-800 dark:border-purple-300/20 dark:bg-purple-300/10 dark:text-purple-200"
                                    >
                                        {{ bounty.popularity_score ?? 0 }} pts
                                    </div>
                                    <div class="mt-1 text-[11px] tracking-wide text-purple-700/70 uppercase dark:text-purple-300/70">popularity</div>
                                </div>

                                <div class="mt-2 flex min-h-[24px] flex-wrap gap-1.5">
                                    <template v-if="bounty.languages && bounty.languages.length">
                                        <Badge
                                            v-for="language in bounty.languages.slice(0, 2)"
                                            :key="language"
                                            variant="outline"
                                            class="border-gray-300/70 text-xs dark:border-white/20"
                                        >
                                            {{ language }}
                                        </Badge>
                                        <span v-if="bounty.languages.length > 2" class="text-xs text-gray-600 dark:text-gray-300">
                                            {{ bounty.languages.length - 2 }}
                                        </span>
                                    </template>
                                </div>
                            </div>
                        </li>
                    </ul>
                </div>

                <div v-else class="flex flex-col items-center justify-center gap-3 px-6 py-16 text-center text-gray-600 dark:text-gray-300">
                    <div class="flex h-12 w-12 items-center justify-center rounded-2xl border border-dashed border-gray-300/70 dark:border-white/15">
                        <TrendingUp class="h-6 w-6" />
                    </div>
                    <p class="text-sm">No popular bounties yet</p>
                    <p class="max-w-sm text-xs text-gray-500 dark:text-gray-400">New bounties will show up here as they gain traction.</p>
                </div>
            </PerfectScrollbar>
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
