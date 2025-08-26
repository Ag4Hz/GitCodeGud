<script setup lang="ts">
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';

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
</script>

<template>
    <Head title="Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="mx-auto max-w-6xl space-y-6">
                <!-- Header -->
                <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                    <div class="flex flex-col space-y-1.5 p-6">
                        <h3 class="text-2xl font-semibold leading-none tracking-tight flex items-center gap-2">
                            <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                            Admin Dashboard
                        </h3>
                        <p class="text-sm text-muted-foreground">
                            Monitor XP distribution and user statistics
                        </p>
                    </div>
                </div>

                <!-- XP Statistics -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Users -->
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                        <div class="flex flex-row items-center justify-between space-y-0 p-6 pb-2">
                            <h3 class="tracking-tight text-sm font-medium">Total Users</h3>
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <div class="p-6 pt-0">
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_users) }}</div>
                            <p class="text-xs text-muted-foreground">
                                {{ formatNumber(props.xpStats.users_with_xp) }} with XP earned
                            </p>
                        </div>
                    </div>

                    <!-- Total XP Distributed -->
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                        <div class="flex flex-row items-center justify-between space-y-0 p-6 pb-2">
                            <h3 class="tracking-tight text-sm font-medium">Total XP Distributed</h3>
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                            </svg>
                        </div>
                        <div class="p-6 pt-0">
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_xp_distributed) }}</div>
                            <p class="text-xs text-muted-foreground">
                                Avg: {{ formatNumber(props.xpStats.average_xp) }} per active user
                            </p>
                        </div>
                    </div>

                    <!-- Highest XP -->
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                        <div class="flex flex-row items-center justify-between space-y-0 p-6 pb-2">
                            <h3 class="tracking-tight text-sm font-medium">Highest XP</h3>
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z" />
                            </svg>
                        </div>
                        <div class="p-6 pt-0">
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.highest_xp) }}</div>
                            <p class="text-xs text-muted-foreground">
                                Top performer
                            </p>
                        </div>
                    </div>

                    <!-- Level Distribution -->
                    <div class="rounded-lg border bg-card text-card-foreground shadow-sm">
                        <div class="flex flex-row items-center justify-between space-y-0 p-6 pb-2">
                            <h3 class="tracking-tight text-sm font-medium">Level Distribution</h3>
                            <svg class="h-4 w-4 text-muted-foreground" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z" />
                            </svg>
                        </div>
                        <div class="p-6 pt-0">
                            <div class="space-y-1 max-h-24 overflow-y-auto">
                                <div
                                    v-for="(count, level) in props.xpStats.level_distribution"
                                    :key="level"
                                    class="flex items-center justify-between text-sm"
                                >
                                    <span class="flex items-center gap-1">
                                        <span class="inline-flex items-center rounded-md border px-2 py-1 text-xs font-semibold">
                                            {{ level }}
                                        </span>
                                        Level {{ level }}
                                    </span>
                                    <span class="font-medium">{{ count }}</span>
                                </div>
                                <div v-if="Object.keys(props.xpStats.level_distribution).length === 0" class="text-center text-muted-foreground py-2">
                                    No users with XP yet
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
