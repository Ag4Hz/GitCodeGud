<script setup lang="ts">
import UserRow from '@/components/UserRow.vue';
import { useXP } from '@/composables/useXP';
import { Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
type User = {
    id: number;
    nickname: string;
    avatar: string;
    name: string;
    xp: number;
    skill_xp?: number | null;
};

const props = withDefaults(
    defineProps<{
        users: { data: User[]; links?: any[] };
        selectedLanguage?: string;
    }>(),
    {
        selectedLanguage: '',
    },
);

const { formatXP } = useXP();
const goToUser = (id: number) => {
    router.visit(`/users/${id}`, { preserveScroll: true });
};

const showLevel = computed(() => !!props.selectedLanguage);
const xpHeader = computed(() => (showLevel.value ? `XP${props.selectedLanguage ? ` (${props.selectedLanguage})` : ''}` : 'XP'));

const displayXP = (user: User) => (showLevel.value ? formatXP(user.skill_xp ?? 0) : formatXP(user.xp));
</script>

<template>
    <div class="mx-auto w-full max-w-7xl overflow-x-auto rounded-2xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
        <table class="w-full table-fixed text-xs sm:text-sm md:text-base">
            <thead class="bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
                <tr>
                    <th class="w-[15%] px-3 py-2 text-center font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">Position</th>
                    <th class="w-[60%] px-3 py-2 text-left font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">User</th>
                    <th class="w-[30%] px-3 py-2 text-right font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">{{ xpHeader }}</th>
                </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
                <tr
                    v-for="user in users.data"
                    :key="user.id"
                    class="cursor-pointer transition-colors focus-within:bg-gray-50 hover:bg-white/50 dark:focus-within:bg-white/5 dark:hover:bg-white/5"
                    role="link"
                    tabindex="0"
                    :aria-label="`Open ${user.nickname || user.name} profile`"
                    @click="goToUser(user.id)"
                    @keydown.enter.prevent="goToUser(user.id)"
                >
                    <td class="px-3 py-2 text-center whitespace-nowrap text-gray-900 tabular-nums sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-100">
                        {{ (user as any).rank }}
                    </td>

                    <td class="min-w-0 px-3 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4">
                        <Link :href="`/users/${user.id}`" class="block min-w-0 focus:outline-none" @click.stop>
                            <div class="min-w-0 truncate">
                                <ul class="m-0 list-none p-0">
                                    <UserRow
                                        :user="{ id: user.id, nickname: user.nickname, avatar: user.avatar, name: user.name }"
                                        :active="false"
                                        class="!rounded-none !px-0 !py-0 hover:!bg-transparent dark:hover:!bg-transparent"
                                    />
                                </ul>
                            </div>
                        </Link>
                    </td>

                    <td
                        class="px-3 py-2 text-right font-semibold whitespace-nowrap text-gray-900 tabular-nums sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-100"
                    >
                        {{ displayXP(user) }}
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
