<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import { useXP } from '@/composables/useXP';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';
const { formatXP } = useXP();

defineProps({
    users: {
        type: Object,
        required: true,
    },
});
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="[{ title: 'Leaderboard', href: '/leaderboard' }]">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl dark:text-gray-100">Leaderboard</h1>
            </div>

            <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white dark:border-white/10 dark:bg-white/5">
                <table class="w-full min-w-[900px] text-sm md:text-base">
                    <thead class="bg-gray-50/80 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                        <tr>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 md:px-6 md:py-4 dark:text-gray-300">Position</th>
                            <th class="px-4 py-3 text-left font-medium text-gray-600 md:px-6 md:py-4 dark:text-gray-300">User</th>
                            <th class="px-4 py-3 text-right font-medium text-gray-600 md:px-6 md:py-4 dark:text-gray-300">XP</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                        <tr v-for="user in users.data" :key="user.id" class="transition-colors hover:bg-gray-50 dark:hover:bg-white/5">
                            <td class="px-4 py-3 whitespace-nowrap text-gray-900 md:px-6 md:py-4 dark:text-gray-100">
                                {{ user.rank }}
                            </td>
                            <td class="px-4 py-3 md:px-6 md:py-4">
                                <div class="flex items-center gap-3">
                                    <div
                                        class="flex h-8 w-8 items-center justify-center rounded-full bg-gray-200 text-xs text-gray-800 md:text-sm dark:bg-white/10 dark:text-gray-100"
                                    >
                                        {{ user.initial }}
                                    </div>
                                    <span class="max-w-[320px] truncate font-medium text-gray-900 md:max-w-none dark:text-gray-100">
                                        {{ user.name }}
                                    </span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-right font-semibold text-gray-900 tabular-nums md:px-6 md:py-4 dark:text-gray-100">
                                {{ formatXP(user.xp) }}
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <Pagination :links="users.links" />
        </div>
    </AppLayout>
</template>
