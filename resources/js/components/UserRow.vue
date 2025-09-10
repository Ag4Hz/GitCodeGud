<script setup lang="ts">
import { useInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { computed } from 'vue';

type User = {
    id: number;
    nickname?: string | null;
    avatar?: string | null;
    name?: string | null;
};

const { user, active } = defineProps<{ user: User; active?: boolean }>();
const { getInitials } = useInitials();

const initials = computed(() => getInitials(user.name || user.nickname || ''));
</script>

<template>
    <li
        :class="[
            'flex cursor-pointer items-center gap-3 rounded-md px-3 py-2 select-none',
            active ? 'bg-gray-100/60 text-white dark:bg-white/10' : 'text-gray-900 hover:bg-gray-100/60 dark:text-gray-100 dark:hover:bg-white/10',
        ]"
    >
        <Avatar class="h-10 w-10 flex-shrink-0">
            <AvatarImage :src="user.avatar || ''" :alt="user.nickname || user.name || 'avatar'" />
            <AvatarFallback class="bg-neutral-200 text-[15px] font-semibold text-neutral-800 dark:bg-neutral-700 dark:text-neutral-100">
                {{ initials }}
            </AvatarFallback>
        </Avatar>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm leading-tight font-medium">
                {{ user.nickname || user.name }}
            </p>
            <p class="truncate text-xs text-gray-700 dark:text-muted-foreground">
                {{ user.name }}
            </p>
        </div>
    </li>
</template>
