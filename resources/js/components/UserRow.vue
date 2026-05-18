<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { useInitials } from '@/composables/useInitials';
import { useProviderUtils } from '@/composables/useProviderUtils';
import { Crown } from 'lucide-vue-next';
import { computed } from 'vue';

type Provider = {
    provider: string;
    provider_username: string;
};

type User = {
    id: number;
    nickname?: string | null;
    avatar?: {
        url: string;
        provider: string;
        label: string;
    } | null;
    name?: string | null;
    providers?: Provider[];
};

const { user, active, rank, orderDirection, total } = defineProps<{
    user: User;
    active?: boolean;
    rank?: number;
    orderDirection?: 'asc' | 'desc';
    total?: number;
}>();

const showCrown = computed(() => {
    if (!rank || !total) return false;

    return orderDirection === 'asc' ? rank > (total ?? 0) - 3 : rank <= 3;
});

const { getInitials } = useInitials();
const { getProviderConfig } = useProviderUtils();

const initials = computed(() => getInitials(user.name || user.nickname || ''));

const providers = computed(() => {
    if (!user.providers || user.providers.length === 0) return [];

    return user.providers
        .filter((p) => p.provider !== 'atlassian')
        .map((p) => ({
            provider: p.provider,
            username: p.provider_username,
            config: getProviderConfig(p.provider),
        }));
});

</script>

<template>
    <li
        :class="[
            'mt-2 flex cursor-pointer items-center gap-3 rounded-md px-3 py-3' + ' select-none',
            active
                ? 'bg-gray-100/60 text-black dark:bg-white/10 dark:text-white'
                : 'text-gray-900 hover:bg-white/50 dark:text-gray-100 dark:hover:bg-white/10',
        ]"
    >
        <div class="relative">
            <Avatar class="h-10 w-10 flex-shrink-0">
                <AvatarImage :src="user.avatar?.url || ''" :alt="user.nickname || user.name || 'avatar'" />
                <AvatarFallback class="bg-neutral-200 text-[15px] font-semibold text-neutral-800 dark:bg-neutral-700 dark:text-neutral-100">
                    {{ initials }}
                </AvatarFallback>
            </Avatar>
            <span
                v-if="showCrown"
                class="absolute -top-1 -right-1 grid h-5 w-5 place-items-center rounded-full ring-2 ring-white backdrop-blur dark:ring-neutral-900"
                :class="{
                    'bg-yellow-400/90 text-yellow-950': orderDirection === 'desc' ? rank === 1 : total ? rank === total : 0,
                    'bg-gray-300/90 text-gray-900': orderDirection === 'desc' ? rank === 2 : total ? rank === total - 1 : 0,
                    'bg-amber-500/90 text-amber-950': orderDirection === 'desc' ? rank === 3 : total ? rank === total - 2 : 0,
                }"
                aria-label="Top 3"
            >
                <Crown class="h-3 w-3" aria-hidden="true" />
            </span>
        </div>

        <div class="min-w-0 flex-1">
            <p class="truncate text-sm leading-tight font-medium">@{{ user.nickname }}</p>
            <p class="truncate text-xs text-gray-700 dark:text-muted-foreground">
                {{ user.name }}
            </p>
            <div v-if="providers.length > 0" class="mt-1 flex flex-wrap gap-1">
                <Badge
                    v-for="provider in providers"
                    :key="provider.provider"
                    variant="secondary"
                    class="h-5 gap-1 px-1.5 text-[10px] font-medium"
                    :class="provider.config.badgeColor"
                >
                    <component :is="provider.config.icon" class="h-3 w-3" />
                    <span>{{ provider.config.name }}</span>
                </Badge>
            </div>
        </div>
    </li>
</template>
