<script setup lang="ts">
import LanguageFilter from '@/components/LanguageFilter.vue';
import LeaderboardTable from '@/components/LeaderboardTable.vue';
import Pagination from '@/components/Pagination.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import UserSearch from '@/components/UserSearch.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

type Dir = 'asc' | 'desc';

type User = { id: number; nickname: string; avatar: string; name: string; xp: number; skill_xp: number; level: number; rank: number };
type LeaderboardUsers = { data: User[]; links: any[] };
type UsersSearchPayload = { data?: User[] };

type PageProps = {
    userFilters?: { search?: string };
    users?: UsersSearchPayload;
    leaderboardUsers: LeaderboardUsers;
    sort?: { dir?: Dir };
    filters?: { language?: string };
    availableLanguages?: string[];
    selected?: { dir?: Dir; skill_id?: number | null };
};

const props = withDefaults(defineProps<PageProps>(), {
    userFilters: () => ({ search: '' }),
    users: () => ({ data: [] }),
    sort: () => ({ dir: 'desc' as Dir }),
    filters: () => ({ language: '' }),
    availableLanguages: () => [],
});

const sortDir = ref<Dir>(props.sort.dir ?? 'desc');
const localSelectedLanguage = ref<string>(props.filters.language ?? '');

watch(localSelectedLanguage, (val) => {
    router.reload({
        data: {
            language: val || undefined,
            dir: sortDir.value,
            skill_id: props.selected?.skill_id ?? undefined,
            page: 1,
        },
    });
});

const changeSort = (dir: Dir) => {
    sortDir.value = dir;
    router.reload({
        data: {
            dir,
            language: localSelectedLanguage.value || undefined,
            skill_id: props.selected?.skill_id ?? undefined,
            page: 1,
        },
    });
};
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="[{ title: 'Leaderboard', href: '/leaderboard' }]">
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl dark:text-gray-100">Leaderboard</h1>
            </div>

            <div>
                <UserSearch :filters="props.userFilters" :results="props.users" />
            </div>

            <h2 class="mt-28 mb-6 text-center text-2xl font-bold tracking-tight text-gray-900 sm:text-3xl md:text-4xl dark:text-gray-100">
                Hunt bugs. Fix code. Earn XP.
            </h2>
            <p class="mb-28 text-center text-base text-muted-foreground sm:text-lg md:text-xl">
                Welcome to the battleground where developers rise and legends are made.
            </p>

            <div class="mb-6 flex items-center justify-between gap-4">
                <div class="sm:w-48">
                    <LanguageFilter v-model="localSelectedLanguage" :languages="availableLanguages" placeholder="All Languages" />
                </div>

                <div>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="rounded-md border px-3 py-2 text-sm font-medium shadow-sm hover:bg-gray-100 dark:hover:bg-gray-800"
                        >
                            Sort: {{ sortDir }}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="bg-white dark:bg-gray-900">
                            <DropdownMenuItem @click="changeSort('asc')">Ascending</DropdownMenuItem>
                            <DropdownMenuItem @click="changeSort('desc')">Descending</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <LeaderboardTable :users="props.leaderboardUsers" :selected-language="props.filters?.language || ''" />
            <Pagination :links="props.leaderboardUsers.links" />
        </div>
    </AppLayout>
</template>
