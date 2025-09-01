<script setup lang="ts">
import Pagination from '@/components/Pagination.vue';
import LeaderboardTable from '@/components/LeaderboardTable.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

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

            <LeaderboardTable :users="users" />

            <Pagination :links="users.links" />
        </div>
    </AppLayout>
</template>
