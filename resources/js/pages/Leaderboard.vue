<script setup lang="ts">
import LeaderboardTable from '@/components/LeaderboardTable.vue';
import Pagination from '@/components/Pagination.vue';
import UserSearch from '@/components/UserSearch.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head } from '@inertiajs/vue3';

type User = { id: number; nickname: string; avatar: string; name: string };
type PaginationLink = { url: string | null; label: string; active: boolean };
type LeaderboardUsers = { data: User[]; links: PaginationLink[] };
type UsersSearchPayload = { data?: User[] };

type PageProps = {
    userFilters?: { search?: string };
    users?: UsersSearchPayload;
    leaderboardUsers: LeaderboardUsers
};

const props = withDefaults(defineProps<PageProps>(), {
    userFilters: () => ({ search: '' }),
    users: () => ({ data: [] }),
});

</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="[{ title: 'Leaderboard', href: '/leaderboard' }]">
        <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl dark:text-gray-100">Leaderboard</h1>
            </div>

            <div>
                <UserSearch :filters="props.userFilters" :results="props.users" />
            </div>

            <h2 class="mt-28 mb-6 text-center text-2xl font-bold tracking-tight text-gray-900 dark:text-gray-100 sm:text-3xl md:text-4xl">
                Hunt bugs. Fix code. Earn XP.
            </h2>
            <p class="mb-28 text-center text-base text-gray-600 dark:text-gray-300 sm:text-lg md:text-xl">
                Welcome to the battleground where developers rise and legends are made.
            </p>

            <LeaderboardTable :users="props.leaderboardUsers" />
            <Pagination :links="props.leaderboardUsers.links" />

        </div>
    </AppLayout>
</template>
