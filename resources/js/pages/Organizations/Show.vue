<script setup lang="ts">
import OrganizationMemberList from '@/components/OrganizationMemberList.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, usePage } from '@inertiajs/vue3';
import { Building2, Users } from 'lucide-vue-next';
import { computed } from 'vue';

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
    { title: 'Organizations', href: '/Organizations' },
    { title: props.organization.name, href: `/organizations/${props.organization.id}` },
]);

const isOwner = computed(() => currentUser?.id === props.organization.owner_id);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="organization.name" />

        <div class="mx-auto max-w-4xl space-y-6 p-6">
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
                        <Users class="h-4 w-4" />
                        <span>{{ members.length }} {{ members.length === 1 ? 'member' : 'members' }}</span>
                    </div>
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
    </AppLayout>
</template>
