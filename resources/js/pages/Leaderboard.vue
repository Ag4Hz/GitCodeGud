<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

defineProps({
    "users": {
        type: Object,
        required: true,
    }
});
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="[{ title: 'Leaderboard', href: '/leaderboard' }]">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight md:text-3xl">Leaderboard</h1>
                <div class="text-sm text-muted-foreground">Total users: {{ users.data.length }}</div>
            </div>

            <div class="overflow-x-auto rounded-2xl border">
                <table class="w-full min-w-[900px] text-sm md:text-base">
                    <thead class="bg-gray-50/80 backdrop-blur md:sticky md:top-0 md:z-10">
                    <tr>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 md:px-6 md:py-4">Position</th>
                        <th class="px-4 py-3 text-left font-medium text-gray-600 md:px-6 md:py-4">User</th>
                        <th class="px-4 py-3 text-right font-medium text-gray-600 md:px-6 md:py-4">XP</th>
                    </tr>
                    </thead>
                    <tbody class="divide-y">
                    <tr v-for="user in users.data" :key="user.id" class="transition-colors hover:bg-gray-50">
                        <td class="px-4 py-3 whitespace-nowrap md:px-6 md:py-4">
                            {{ user.rank }}
                        </td>
                        <td class="px-4 py-3 md:px-6 md:py-4">
                            <div class="flex items-center gap-3">
                                <div class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs md:text-sm">
                                    {{ (user.name || 'U').slice(0, 1).toUpperCase() }}
                                </div>
                                <span class="max-w-[320px] truncate font-medium md:max-w-none">
                                        {{ user.name }}
                                    </span>
                            </div>
                        </td>
                        <td class="px-4 py-3 text-right font-semibold tabular-nums md:px-6 md:py-4">
                            {{ user.xp.toLocaleString() }}
                        </td>
                    </tr>
                    </tbody>
                </table>

            </div>
        </div>
    </AppLayout>
</template>
