<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    showXP?: boolean;
    clickable?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    showXP: true,
    clickable: true,
});

const { getInitials } = useInitials();

const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');

const userLevel = computed(() => {
    if (props.user.level) return props.user.level;
    if (props.user.total_xp) {
        const xp = props.user.total_xp;
        if (xp < 1000) return 1;
        if (xp < 5000) return 2;
        if (xp < 15000) return 3;
        if (xp < 30000) return 4;
        if (xp < 60000) return 5;
        if (xp < 120000) return 6;
        if (xp < 250000) return 7;
        if (xp < 400000) return 8;
        return 9;
    }
    return 1;
});

const formatXP = computed(() => {
    const xp = props.user.total_xp || 0;
    if (xp >= 1000) {
        return `${(xp / 1000).toFixed(1)}k`;
    }
    return xp.toString();
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="relative">
            <component
                :is="clickable ? Link : 'div'"
                :href="clickable ? '/profile' : undefined"
                class="block"
                :class="{ 'cursor-pointer hover:opacity-80 transition-opacity': clickable }"
            >
                <Avatar class="h-8 w-8 overflow-hidden rounded-lg">
                    <AvatarImage v-if="showAvatar" :src="user.avatar!" :alt="user.name" />
                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                        {{ getInitials(user.name) }}
                    </AvatarFallback>
                </Avatar>
            </component>
            <Tooltip v-if="showXP">
                <TooltipTrigger as-child>
                    <div class="absolute -bottom-1 -right-1 flex items-center justify-center">
                        <Badge variant="secondary"
                            class="h-5 min-w-5 px-1 text-xs font-bold bg-orange-500 text-white border-2 border-white dark:border-gray-900 shadow-sm cursor-help"
                            :aria-label="`Level ${userLevel}, ${user.total_xp || 0} experience points`"
                        >
                            {{ userLevel }}
                        </Badge>
                    </div>
                </TooltipTrigger>
                <TooltipContent side="top" align="center">
                    <p>Level {{ userLevel }} • {{ user.total_xp || 0 }} XP</p>
                </TooltipContent>
            </Tooltip>
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">{{ user.name }}</span>
            <div class="flex items-center gap-2">
                <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                <span v-if="showXP && user.total_xp" class="text-xs text-muted-foreground">
                    {{ formatXP }} XP
                </span>
            </div>
        </div>
    </div>
</template>
