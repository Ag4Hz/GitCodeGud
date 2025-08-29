<script setup lang="ts">
import { computed } from 'vue';
import { router } from '@inertiajs/vue3';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { TrendingUp} from 'lucide-vue-next';

enum BountyStatus {
    OPEN = 'open',
    CLOSED = 'closed'
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
    title: 'Popular Bounties'
});


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
    return [...props.bounties]
        .sort((a, b) => (b.popularity_score ?? 0) - (a.popularity_score ?? 0))
        .slice(0, 8);
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

        <CardContent class="p-4 space-y-0">
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
                            <div class="flex items-start gap-3 flex-1 min-w-0">
                                <!-- Popularity Rank -->
                                <div class="flex h-6 w-6 items-center justify-center rounded-full bg-orange-100 text-xs font-bold text-orange-800 dark:bg-orange-900 dark:text-orange-200 flex-shrink-0">
                                    {{ index + 1 }}
                                </div>

                                <!-- Bounty Info -->
                                <div class="flex-1 min-w-0 space-y-1">
                                    <div class="flex items-center gap-2 flex-wrap">
                                        <h4 class="font-medium text-sm line-clamp-1 group-hover:text-blue-600 transition-colors dark:group-hover:text-blue-400">
                                            {{ bounty.title }}
                                        </h4>
                                        <span :class="`px-2 py-1 text-xs rounded-md flex-shrink-0 ${getStatusColor(bounty.status)}`">
                                            {{ bounty.status.toUpperCase() }}
                                        </span>
                                    </div>

                                    <!-- Languagee-->
                                    <div v-if="bounty.languages && bounty.languages.length > 0" class="flex flex-wrap gap-1">
                                        <Badge
                                            v-for="language in bounty.languages.slice(0, 2)"
                                            :key="language"
                                            variant="outline"
                                            class="text-xs"
                                        >
                                            {{ language }}
                                        </Badge>
                                        <span v-if="bounty.languages.length > 2" class="text-xs text-gray-500 dark:text-gray-400">
                                            +{{ bounty.languages.length - 2 }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
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
