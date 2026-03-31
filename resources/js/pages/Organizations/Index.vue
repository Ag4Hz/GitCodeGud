<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Card, CardContent } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import { Building2, Crown, Users } from 'lucide-vue-next';

interface MemberPivot {
    role: 'owner' | 'member';
    joined_at: string;
}

interface Organization {
    id: number;
    name: string;
    slug: string;
    owner_id: number;
    members_count: number;
    pivot: MemberPivot;
}

interface Props {
    organizations: Organization[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: '/organizations' },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Organizations" />
        <div>
            <div class="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Organizations</h1>
                </div>

                <div v-if="organizations.length === 0" class="py-12 text-center text-muted-foreground">
                    You are not a member of any organization yet.
                </div>

                <div v-else class="space-y-3">
                    <Link
                        v-for="org in organizations"
                        :key="org.id"
                        :href="`/organizations/${org.id}`"
                        class="block"
                    >
                        <Card class="rounded-xl border border-gray-200 bg-white/40 transition-colors hover:bg-white/60 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10">
                            <CardContent class="flex items-center justify-between p-5">
                                <div class="flex items-center gap-4">
                                    <div class="flex h-10 w-10 items-center justify-center rounded-lg bg-purple-500/20">
                                        <Building2 class="h-5 w-5 text-purple-400" />
                                    </div>
                                    <div>
                                        <p class="font-semibold text-gray-900 dark:text-gray-100">{{ org.name }}</p>
                                        <p class="text-sm text-muted-foreground">@{{ org.slug }}</p>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <div class="flex items-center gap-1 text-sm text-muted-foreground">
                                        <Users class="h-4 w-4" />
                                        <span>{{ org.members_count }}</span>
                                    </div>
                                    <Badge v-if="org.pivot.role === 'owner'" variant="default" class="flex items-center gap-1">
                                        <Crown class="h-3 w-3" />
                                        Owner
                                    </Badge>
                                    <Badge v-else variant="secondary">Member</Badge>
                                </div>
                            </CardContent>
                        </Card>
                    </Link>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
