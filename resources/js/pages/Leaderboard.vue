<script setup lang="ts">
import LeaderboardTable from '@/components/LeaderboardTable.vue';
import Pagination from '@/components/Pagination.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import UserSearch from '@/components/UserSearch.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';

type User = { id: number; nickname: string; avatar: string; name: string };
type PaginationLink = { url: string | null; label: string; active: boolean };
type LeaderboardUsers = { data: User[]; links: PaginationLink[] };
type UsersSearchPayload = { data?: User[] };

type PageProps = {
    userFilters?: { search?: string };
    users?: UsersSearchPayload;
    leaderboardUsers: LeaderboardUsers
    sort: { dir: string };
};

const props = defineProps<PageProps>();
const sortDir = ref<string>(props.sort.dir);

const changeSort = (dir: string) => {
    const next = dir === 'asc' || dir === 'desc' ? dir : 'desc';
    sortDir.value = next;
    router.reload({
        data: { dir: next },
        preserveUrl: true,
    });
};
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

            <h2 class="mt-28 mb-6 text-center text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl md:text-4xl dark:text-gray-100">
                Hunt bugs. Fix code. Earn XP.
            </h2>
            <p class="mb-28 text-center text-base text-gray-600 sm:text-lg md:text-xl dark:text-gray-300">
                Welcome to the battleground where developers rise and legends are made.
            </p>

            <div class="mb-6 flex justify-end">
                <DropdownMenu>
                    <DropdownMenuTrigger class="rounded-md border px-3 py-2 text-sm font-medium shadow-sm hover:bg-gray-100 dark:hover:bg-gray-800">
                        Sort: {{ sortDir }}
                    </DropdownMenuTrigger>
                    <DropdownMenuContent align="end" class="bg-white dark:bg-gray-900">
                        <DropdownMenuItem @click="changeSort('asc')">Asc</DropdownMenuItem>
                        <DropdownMenuItem @click="changeSort('desc')">Desc</DropdownMenuItem>
                    </DropdownMenuContent>
                </DropdownMenu>
            </div>

            <LeaderboardTable :users="props.leaderboardUsers" />
            <Pagination :links="props.leaderboardUsers.links" />
        </div>
    </AppLayout>
</template>
