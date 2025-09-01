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

            <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                <UserSearch :filters="props.userFilters" :results="props.users" />
            </div>

            <LeaderboardTable :users="props.leaderboardUsers" />
            <Pagination :links="props.leaderboardUsers.links" />

        </div>
    </AppLayout>
</template>
