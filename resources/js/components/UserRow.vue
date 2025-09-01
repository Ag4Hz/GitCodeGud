<script setup lang="ts">
import { computed } from 'vue'
import { useInitials } from '@/composables/useInitials'

type User = {
    id: number
    nickname?: string | null
    avatar?: string | null
    name?: string | null
}

const { user, active } = defineProps<{ user: User; active?: boolean }>()
const { getInitials } = useInitials()

const initials = computed(() => getInitials(user.name || user.nickname || ''))
</script>

<template>
    <li
        :class="[
      'flex items-center gap-3 rounded-md px-3 py-2 select-none cursor-pointer',
      active
        ? 'bg-gray-100/60 text-white dark:bg-white/10'
        : 'text-gray-900 dark:text-gray-100 hover:bg-gray-100/60 dark:hover:bg-white/10'
    ]"
    >
        <img
            v-if="user.avatar"
            :src="user.avatar"
            :alt="user.nickname || user.name || 'avatar'"
            class="h-10 w-10 rounded-full object-cover"
        />
        <div
            v-else
            class="grid h-10 w-10 place-items-center rounded-full bg-neutral-200 text-[15px] font-semibold text-neutral-800 dark:bg-neutral-700 dark:text-neutral-100"
            aria-hidden="true"
        >
            {{ initials }}
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm font-medium leading-tight">
                {{ user.nickname || user.name }}
            </p>
            <p class="truncate text-xs text-gray-700 dark:text-muted-foreground">
                {{ user.name }}
            </p>

        </div>
    </li>
</template>
