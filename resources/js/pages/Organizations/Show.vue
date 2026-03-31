<script setup lang="ts">
import OrganizationInviteForm from '@/components/OrganizationInviteForm.vue';
import OrganizationMemberList from '@/components/OrganizationMemberList.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, Mail, Users, Trophy } from 'lucide-vue-next';
import { computed } from 'vue';
import { Link } from '@inertiajs/vue3';
interface MemberPivot {
    role: 'owner' | 'member';
    joined_at: string;
}

interface OrgMember extends User {
    pivot: MemberPivot;
}

interface Organization {
    id: number;
    name: string;
    slug: string;
    owner_id: number;
    owner: User;
}

interface Props {
    organization: Organization;
    members: OrgMember[];
}

const props = defineProps<Props>();
const page = usePage();
const currentUser = page.props.auth?.user as User;

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Organizations', href: '/organizations' },
    { title: props.organization.name, href: `/organizations/${props.organization.id}` },
]);

const isOwner = computed(() => currentUser?.id === props.organization.owner_id);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="organization.name" />
        <div>
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Org Header -->
                <Card class="rounded-xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <div class="flex items-center gap-3">
                            <Building2 class="h-8 w-8 text-muted-foreground" />
                            <div>
                                <CardTitle class="text-2xl">{{ organization.name }}</CardTitle>
                                <p class="text-sm text-muted-foreground">@{{ organization.slug }}</p>
                            </div>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="flex items-center gap-2 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                <span>{{ members.length }} {{ members.length === 1 ? 'member' : 'members' }}</span>
                            </div>
                            <Link :href="`/organizations/${organization.id}/leaderboard`" class="flex items-center gap-1 text-sm text-green-400 hover:underline">
                                <Trophy class="h-4 w-4" />
                                Leaderboard
                            </Link>
                        </div>
                    </CardContent>
                </Card>

                <!-- Invite Form (owner only) -->
                <Card v-if="isOwner" class="rounded-xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Mail class="h-5 w-5" />
                            Invite Member
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OrganizationInviteForm :organization-id="organization.id" />
                    </CardContent>
                </Card>

                <!-- Members -->
                <Card class="rounded-xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Members
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <OrganizationMemberList
                            :members="members"
                            :organization-id="organization.id"
                            :is-owner="isOwner"
                        />
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
