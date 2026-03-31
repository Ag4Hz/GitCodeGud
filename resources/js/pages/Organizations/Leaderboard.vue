<script setup lang="ts">
import LanguageFilter from '@/components/LanguageFilter.vue';
import LeaderboardTable from '@/components/LeaderboardTable.vue';
import Pagination from '@/components/Pagination.vue';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type Dir = 'asc' | 'desc';

type Provider = {
    provider: string;
    provider_username: string;
};

type User = {
    id: number;
    nickname: string;
    avatar: string;
    name: string;
    xp: number;
    skill_xp: number;
    level: number;
    rank: number;
    providers?: Provider[];
};

type LeaderboardUsers = { data: User[]; links: any[]; total: number };

type Organization = {
    id: number;
    name: string;
    slug: string;
};

type PageProps = {
    organization: Organization;
    leaderboardUsers: LeaderboardUsers;
    sort?: { dir?: Dir };
    filters?: { language?: string; provider?: string };
    availableLanguages?: string[];
    selected?: { dir?: Dir; skill_id?: number | null };
};

const props = withDefaults(defineProps<PageProps>(), {
    sort: () => ({ dir: 'desc' as Dir }),
    filters: () => ({ language: '', provider: '' }),
    availableLanguages: () => [],
});

const sortDir = ref<Dir>(props.sort.dir ?? 'desc');
const localSelectedLanguage = ref<string>(props.filters.language ?? '');
const localSelectedProvider = ref<string>(props.filters.provider ?? '');

const providerOptions = ['GitHub', 'GitLab', 'Bitbucket'];

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Organizations', href: '/organizations' },
    { title: props.organization.name, href: `/organizations/${props.organization.id}` },
    { title: 'Leaderboard', href: `/organizations/${props.organization.id}/leaderboard` },
]);

const totalUsers = computed(() => props.leaderboardUsers.total ?? 0);

watch(localSelectedLanguage, (val) => {
    router.reload({
        data: {
            language: val || undefined,
            provider: localSelectedProvider.value || undefined,
            dir: sortDir.value,
            skill_id: props.selected?.skill_id ?? undefined,
            page: 1,
        },
    });
});

watch(localSelectedProvider, (val) => {
    router.reload({
        data: {
            provider: val || undefined,
            language: localSelectedLanguage.value || undefined,
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
            provider: localSelectedProvider.value || undefined,
            skill_id: props.selected?.skill_id ?? undefined,
            page: 1,
        },
    });
};
</script>

<template>
    <Head :title="`${organization.name} Leaderboard`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
            <div class="mb-6 flex items-end justify-between gap-4">
                <h1 class="text-2xl font-semibold tracking-tight text-gray-900 md:text-3xl dark:text-gray-100">
                    {{ organization.name }} Leaderboard
                </h1>
            </div>

            <div class="mb-6 flex flex-wrap items-center justify-between gap-4">
                <div class="flex flex-wrap items-center gap-3">
                    <div class="sm:w-48">
                        <LanguageFilter v-model="localSelectedLanguage" :languages="availableLanguages" placeholder="All Languages" />
                    </div>
                    <div class="sm:w-48">
                        <LanguageFilter
                            v-model="localSelectedProvider"
                            :languages="providerOptions"
                            placeholder="All Providers"
                        />
                    </div>
                </div>

                <div>
                    <DropdownMenu>
                        <DropdownMenuTrigger
                            class="w-full rounded-[10px] border border-gray-200 bg-white/40 px-3 py-2 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-500 dark:hover:bg-white/10"
                        >
                            Sort: {{ sortDir }}
                        </DropdownMenuTrigger>
                        <DropdownMenuContent align="end" class="bg-white backdrop-blur-xl dark:bg-white/6">
                            <DropdownMenuItem @click="changeSort('asc')">Ascending</DropdownMenuItem>
                            <DropdownMenuItem @click="changeSort('desc')">Descending</DropdownMenuItem>
                        </DropdownMenuContent>
                    </DropdownMenu>
                </div>
            </div>

            <LeaderboardTable
                :users="props.leaderboardUsers"
                :selected-language="props.filters?.language || ''"
                :sort-dir="sortDir"
                :total="totalUsers"
            />
            <Pagination :links="props.leaderboardUsers.links" />
        </div>
    </AppLayout>
</template>
