<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { useInitials } from '@/composables/useInitials';
import { router } from '@inertiajs/vue3';
import { UserX } from 'lucide-vue-next';

interface MemberPivot {
    role: 'owner' | 'member';
    joined_at: string;
}

interface OrgMember {
    id: number;
    name: string;
    nickname: string;
    avatar?: {
        url: string;
        provider: string;
        label: string;
    };
    pivot: MemberPivot;
}

interface Props {
    members: OrgMember[];
    organizationId: number;
    isOwner: boolean;
}

const props = defineProps<Props>();
const { getInitials } = useInitials();

const getAvatarUrl = (avatar: OrgMember['avatar']): string => {
    return avatar?.url ?? '';
};

const removeMember = (memberId: number) => {
    router.delete(route('organizations.members.remove', { organization: props.organizationId, user: memberId }), {
        preserveScroll: true,
    });
};
</script>

<template>
    <div class="space-y-3">
        <div
            v-for="member in members"
            :key="member.id"
            class="flex items-center justify-between rounded-lg border border-gray-200 bg-white/40 p-3 dark:border-white/10 dark:bg-white/5"
        >
            <div class="flex items-center gap-3">
                <Avatar class="h-10 w-10">
                    <AvatarImage :src="getAvatarUrl(member.avatar)" :alt="member.nickname" />
                    <AvatarFallback>{{ getInitials(member.name) }}</AvatarFallback>
                </Avatar>
                <a :href="`/users/${member.id}`" class="hover:underline">
                    <p class="text-sm font-medium">{{ member.nickname }}</p>
                    <p class="text-xs text-muted-foreground">{{ member.name }}</p>
                </a>
            </div>

            <div class="flex items-center gap-2">
                <Badge :variant="member.pivot.role === 'owner' ? 'default' : 'secondary'">
                    {{ member.pivot.role === 'owner' ? 'Owner' : 'Member' }}
                </Badge>
                <Button
                    v-if="isOwner && member.pivot.role !== 'owner'"
                    variant="ghost"
                    size="sm"
                    class="text-destructive hover:text-destructive"
                    @click="removeMember(member.id)"
                >
                    <UserX class="h-4 w-4" />
                </Button>
            </div>
        </div>

        <p v-if="members.length === 0" class="py-4 text-center text-sm text-muted-foreground">
            No members yet.
        </p>
    </div>
</template>
