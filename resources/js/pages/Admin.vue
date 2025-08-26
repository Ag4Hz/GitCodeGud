<script setup lang="ts">
import { computed } from 'vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Badge } from '@/components/ui/badge';
import Icon from '@/components/Icon.vue';
import Heading from '@/components/Heading.vue';

interface XPStats {
    total_users: number;
    users_with_xp: number;
    total_xp_distributed: number;
    average_xp: number;
    highest_xp: number;
    level_distribution: Record<number, number>;
}

interface Props {
    xpStats?: XPStats;
}

const props = withDefaults(defineProps<Props>(), {
    xpStats: () => ({
        total_users: 0,
        users_with_xp: 0,
        total_xp_distributed: 0,
        average_xp: 0,
        highest_xp: 0,
        level_distribution: {}
    })
});

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(Math.round(num));
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
];

const sortedLevels = computed(() => {
    return Object.entries(props.xpStats.level_distribution)
        .sort(([a], [b]) => Number(a) - Number(b));
});
</script>

<template>
    <Head title="Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="mx-auto max-w-6xl space-y-6">
                <!-- Header -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="chart-bar" class="h-6 w-6" />
                            <Heading title="Admin Dashboard" />
                        </CardTitle>
                        <p class="text-sm text-muted-foreground">
                            Monitor XP distribution and user statistics
                        </p>
                    </CardHeader>
                </Card>

                <!-- XP Statistics -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Users -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                            <Icon name="users" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_users) }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatNumber(props.xpStats.users_with_xp) }} with XP earned
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Total XP Distributed -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total XP Distributed</CardTitle>
                            <Icon name="zap" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_xp_distributed) }}</div>
                            <p class="text-xs text-muted-foreground">
                                Avg: {{ formatNumber(props.xpStats.average_xp) }} per active user
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Highest XP -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Highest XP</CardTitle>
                            <Icon name="star" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.highest_xp) }}</div>
                            <p class="text-xs text-muted-foreground">
                                Top performer
                            </p>
                        </CardContent>
                    </Card>

                    <!-- Level Distribution -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Level Distribution</CardTitle>
                            <Icon name="list" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="max-h-32 overflow-y-auto scrollbar-thin scrollbar-track-transparent scrollbar-thumb-muted-foreground/20 hover:scrollbar-thumb-muted-foreground/40">
                                <div v-if="Object.keys(props.xpStats.level_distribution).length > 0" class="space-y-2 pr-2">
                                    <div
                                        v-for="([level, count]) in sortedLevels"
                                        :key="level"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Badge variant="outline" class="w-8 h-6 p-0 flex items-center justify-center text-xs font-semibold">
                                                {{ level }}
                                            </Badge>
                                            <span>Level {{ level }}</span>
                                        </span>
                                        <Badge variant="secondary" class="font-medium">
                                            {{ count }}
                                        </Badge>
                                    </div>
                                </div>

                                <div v-else class="text-center text-muted-foreground py-4">
                                    <Icon name="cube" class="mx-auto h-6 w-6 mb-2 opacity-50" />
                                    <p class="text-xs">No users with XP yet</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Fine tune -->
                
            </div>
        </div>
    </AppLayout>
</template>
