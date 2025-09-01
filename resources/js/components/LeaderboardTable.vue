<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3'
import { useXP } from '@/composables/useXP'
import UserRow from '@/components/UserRow.vue'
defineProps({
    users: { type: Object, required: true },
})

const { formatXP } = useXP()

const goToUser = (id: number) => {
    router.visit(`/users/${id}`, { preserveScroll: true })
}
</script>

<template>
    <div class="overflow-x-auto rounded-2xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
        <table class="w-full text-xs sm:text-sm md:text-base min-w-[320px] sm:min-w-[560px] md:min-w-[760px] lg:min-w-[900px]">
            <thead class="bg-white/40 backdrop-blur md:sticky md:top-0 md:z-10 dark:bg-white/10">
            <tr>
                <th class="px-3 py-2 text-center font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">Position</th>
                <th class="px-3 py-2 text-left font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">User</th>
                <th class="px-3 py-2 text-right font-medium text-gray-600 sm:px-4 sm:py-3 md:px-6 md:py-4 dark:text-gray-300">XP</th>
            </tr>
            </thead>

            <tbody class="divide-y divide-gray-200 dark:divide-white/10">
            <tr
                v-for="user in users.data"
                :key="user.id"
                class="cursor-pointer transition-colors hover:bg-white/50 focus-within:bg-gray-50 dark:hover:bg-white/5 dark:focus-within:bg-white/5"
                role="link"
                tabindex="0"
                :aria-label="`Open ${user.nickname || user.name} profile`"
                @click="goToUser(user.id)"
                @keydown.enter.prevent="goToUser(user.id)"
            >
                <td class="px-3 py-2 text-center tabular-nums whitespace-nowrap sm:px-4 sm:py-3 md:px-6 md:py-4 text-gray-900 dark:text-gray-100">
                    {{ user.rank }}
                </td>

                <td class="px-3 py-2 sm:px-4 sm:py-3 md:px-6 md:py-4">
                    <Link :href="`/users/${user.id}`" class="block focus:outline-none" @click.stop>
                        <ul class="m-0 list-none p-0">
                            <UserRow
                                :user="{ id: user.id, nickname: user.nickname, avatar: user.avatar, name: user.name }"
                                :active="false"
                                class="!px-0 !py-0 !rounded-none hover:!bg-transparent dark:hover:!bg-transparent"
                            />
                        </ul>
                    </Link>
                </td>

                <td class="px-3 py-2 text-right font-semibold tabular-nums whitespace-nowrap sm:px-4 sm:py-3 md:px-6 md:py-4 text-gray-900 dark:text-gray-100">
                    {{ formatXP(user.xp) }}
                </td>
            </tr>
            </tbody>
        </table>
    </div>
</template>
