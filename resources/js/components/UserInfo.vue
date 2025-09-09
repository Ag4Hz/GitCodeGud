<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Tooltip, TooltipContent, TooltipTrigger } from '@/components/ui/tooltip';
import { useInitials } from '@/composables/useInitials';
import { useXP } from '@/composables/useXP';
import type { User } from '@/types';
import { Link } from '@inertiajs/vue3';
import { computed } from 'vue';

interface Props {
    user: User;
    showEmail?: boolean;
    showXP?: boolean;
    showDescription?: boolean;
    clickable?: boolean;
    descriptionMaxLength?: number;
}

const props = withDefaults(defineProps<Props>(), {
    showEmail: false,
    showXP: true,
    showDescription: true,
    clickable: true,
    descriptionMaxLength: 100,
});

const { getInitials } = useInitials();
const { getUserXP } = useXP();

const showAvatar = computed(() => props.user.avatar && props.user.avatar !== '');
const userXPData = computed(() => getUserXP.value(props.user));

const truncatedDescription = computed(() => {
    if (!props.user.description) return '';

    if (props.user.description.length <= props.descriptionMaxLength) {
        return props.user.description;
    }

    return props.user.description.substring(0, props.descriptionMaxLength).trim() + '...';
});

const isDescriptionTruncated = computed(() => {
    return props.user.description && props.user.description.length > props.descriptionMaxLength;
});
</script>

<template>
    <div class="flex items-center gap-3">
        <div class="relative">
            <component
                :is="clickable ? Link : 'div'"
                :href="clickable ? '/profile' : undefined"
                class="block"
                :class="{ 'cursor-pointer transition-opacity hover:opacity-80': clickable }"
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
                    <div class="absolute -right-1 -bottom-1 flex items-center justify-center">
                        <Badge
                            variant="secondary"
                            class="h-5 min-w-5 cursor-help border-2 border-white bg-orange-500 px-1 text-xs font-bold text-white shadow-sm dark:border-gray-900"
                            :aria-label="`Level ${userXPData.level}, ${userXPData.totalXP} experience points`"
                        >
                            {{ userXPData.level }}
                        </Badge>
                    </div>
                </TooltipTrigger>
                <TooltipContent side="top" align="center">
                    <p>Level {{ userXPData.level }} • {{ userXPData.totalXP }} XP</p>
                </TooltipContent>
            </Tooltip>
        </div>
        <div class="grid flex-1 text-left text-sm leading-tight">
            <span class="truncate font-medium">{{ user.name }}</span>
            <div class="flex items-center gap-2">
                <span v-if="showEmail" class="truncate text-xs text-muted-foreground">{{ user.email }}</span>
                <span v-if="showXP && userXPData.totalXP" class="text-xs text-muted-foreground"> {{ userXPData.formattedXP }} XP </span>
            </div>
            <div v-if="showDescription && user.description" class="mt-1">
                <Tooltip v-if="isDescriptionTruncated">
                    <TooltipTrigger as-child>
                        <span class="block text-xs text-muted-foreground italic cursor-help">
                            "{{ truncatedDescription }}"
                        </span>
                    </TooltipTrigger>
                    <TooltipContent side="bottom" align="start" class="max-w-48 break-words">
                        <p class="whitespace-normal text-wrap">"{{ user.description }}"</p>
                    </TooltipContent>
                </Tooltip>
                <span v-else class="block text-xs text-muted-foreground italic">
                    "{{ user.description }}"
                </span>
            </div>
        </div>
    </div>
</template>
