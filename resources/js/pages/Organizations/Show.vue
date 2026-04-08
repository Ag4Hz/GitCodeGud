<script setup lang="ts">
import OrganizationInviteForm from '@/components/OrganizationInviteForm.vue';
import OrganizationMemberList from '@/components/OrganizationMemberList.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { Building2, Mail, Trophy, Users } from 'lucide-vue-next';
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
    github_repo?: string | null;
    gitlab_repo?: string | null;
    bitbucket_repo?: string | null;
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
                    <CardContent class="space-y-3">
                        <div class="flex items-center gap-4 text-sm text-muted-foreground">
                            <div class="flex items-center gap-2">
                                <Users class="h-4 w-4" />
                                <span>{{ members.length }} {{ members.length === 1 ? 'member' : 'members' }}</span>
                            </div>
                            <Link
                                :href="`/organizations/${organization.id}/leaderboard`"
                                class="flex items-center gap-1 text-sm text-green-400 hover:underline"
                            >
                                <Trophy class="h-4 w-4" />
                                Leaderboard
                            </Link>
                        </div>

                        <!-- Repo links -->
                        <div class="flex flex-wrap gap-3">
                            <a
                                v-if="organization.github_repo"
                                :href="`https://github.com/${organization.github_repo}`"
                                target="_blank"
                                class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-gray-900 dark:hover:text-gray-100"
                            >
                                <svg class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"/>
                                </svg>
                                {{ organization.github_repo }}
                            </a>
                            <a
                                v-if="organization.gitlab_repo"
                                :href="`https://gitlab.com/${organization.gitlab_repo}`"
                                target="_blank"
                                class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-orange-500"
                            >
                                <svg class="h-4 w-4 text-orange-500" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"/>
                                </svg>
                                {{ organization.gitlab_repo }}
                            </a>
                            <a
                                v-if="organization.bitbucket_repo"
                                :href="`https://bitbucket.org/${organization.bitbucket_repo}`"
                                target="_blank"
                                class="flex items-center gap-1.5 text-sm text-muted-foreground hover:text-blue-500"
                            >
                                <svg class="h-4 w-4 text-blue-500" viewBox="0 0 24 24" fill="currentColor">
                                    <path d="M.778 1.213c-.424-.023-.781.321-.744.745l3.189 19.528c.081.498.514.868 1.019.868h15.474c.379 0 .707-.274.764-.648l3.189-19.748c.037-.424-.32-.768-.744-.745H.778zm14.049 13.319H9.178l-1.108-5.817h7.863l-1.106 5.817z"/>
                                </svg>
                                {{ organization.bitbucket_repo }}
                            </a>
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
