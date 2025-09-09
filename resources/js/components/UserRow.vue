<script setup lang="ts">
import { useInitials } from '@/composables/useInitials';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Crown } from 'lucide-vue-next';
import { computed } from 'vue';
type User = {
    id: number;
    nickname?: string | null;
    avatar?: string | null;
    name?: string | null;
};

const { user, active, rank } = defineProps<{ user: User; active?: boolean; rank?: number }>();
const { getInitials } = useInitials();

const initials = computed(() => getInitials(user.name || user.nickname || ''));
</script>

<template>
    <li
        :class="[
            'flex cursor-pointer items-center gap-3 rounded-md px-3 py-3 mt-2' +
             ' select-none',
            active ? 'bg-gray-100/60 text-white dark:bg-white/10' : 'text-gray-900 hover:bg-gray-100/60 dark:text-gray-100 dark:hover:bg-white/10',
        ]"
    >
        <div class="relative">
            <img v-if="user.avatar" :src="user.avatar" :alt="user.nickname || user.name || 'avatar'" class="h-10 w-10 rounded-full object-cover" />
            <div
                v-else
                class="grid h-10 w-10 place-items-center rounded-full bg-neutral-200 text-[15px] font-semibold text-neutral-800 dark:bg-neutral-700 dark:text-neutral-100"
                aria-hidden="true"
            >
                {{ initials }}
            </div>

            <span
                v-if="rank && rank <= 3"
                class="absolute -top-1 -right-1 grid h-5 w-5 place-items-center rounded-full ring-2 ring-white backdrop-blur dark:ring-neutral-900"
                :class="{
                    'bg-yellow-400/90 text-yellow-950': rank === 1,
                    'bg-gray-300/90 text-gray-900': rank === 2,
                    'bg-amber-500/90 text-amber-950': rank === 3,
                }"
                aria-label="Top 3"
            >
                <Crown class="h-3 w-3" aria-hidden="true" />
            </span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm leading-tight font-medium">
                @{{ user.nickname}}
            </p>
            <p class="truncate text-xs text-gray-700 dark:text-muted-foreground">
                {{ user.name }}
            </p>
        </div>
    </li>
</template>
