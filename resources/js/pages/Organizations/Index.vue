<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { useProviderUtils } from '@/composables/useProviderUtils';
import { Building2, Crown, Plus, Users, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
    github_repo: string | null;
    gitlab_repo: string | null;
    bitbucket_repo: string | null;
    pivot: MemberPivot;
}

const providerIcons: Record<string, { label: string; svg: string; color: string }> = {
    github: {
        label: 'GitHub',
        color: 'text-gray-400',
        svg: `<svg fill="currentColor" viewBox="0 0 24 24" class="h-3.5 w-3.5"><path d="M12 .297c-6.63 0-12 5.373-12 12 0 5.303 3.438 9.8 8.205 11.385.6.113.82-.258.82-.577 0-.285-.01-1.04-.015-2.04-3.338.724-4.042-1.61-4.042-1.61C4.422 18.07 3.633 17.7 3.633 17.7c-1.087-.744.084-.729.084-.729 1.205.084 1.838 1.236 1.838 1.236 1.07 1.835 2.809 1.305 3.495.998.108-.776.417-1.305.76-1.605-2.665-.3-5.466-1.332-5.466-5.93 0-1.31.465-2.38 1.235-3.22-.135-.303-.54-1.523.105-3.176 0 0 1.005-.322 3.3 1.23.96-.267 1.98-.399 3-.405 1.02.006 2.04.138 3 .405 2.28-1.552 3.285-1.23 3.285-1.23.645 1.653.24 2.873.12 3.176.765.84 1.23 1.91 1.23 3.22 0 4.61-2.805 5.625-5.475 5.92.42.36.81 1.096.81 2.22 0 1.606-.015 2.896-.015 3.286 0 .315.21.69.825.57C20.565 22.092 24 17.592 24 12.297c0-6.627-5.373-12-12-12"/></svg>`,
    },
    gitlab: {
        label: 'GitLab',
        color: 'text-orange-400',
        svg: `<svg fill="currentColor" viewBox="0 0 24 24" class="h-3.5 w-3.5"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189a.455.455 0 0 0-.867 0L16.418 9.45H7.582L4.919 1.263a.455.455 0 0 0-.867 0L1.388 9.452.045 13.587a.924.924 0 0 0 .331 1.023L12 23.054l11.624-8.443a.92.92 0 0 0 .331-1.024"/></svg>`,
    },
    bitbucket: {
        label: 'Bitbucket',
        color: 'text-blue-400',
        svg: `<svg fill="currentColor" viewBox="0 0 24 24" class="h-3.5 w-3.5"><path d="M.778 1.211a.768.768 0 0 0-.768.892l3.263 19.811c.084.5.515.868 1.022.873H19.95a.772.772 0 0 0 .77-.646l3.27-20.03a.768.768 0 0 0-.768-.891zM14.52 15.528H9.522L8.17 8.464h7.561z"/></svg>`,
    },
};

const { getProviderBorderColor } = useProviderUtils();

const getOrgPrimaryProvider = (org: Organization): string => {
    if (org.github_repo) return 'github';
    if (org.gitlab_repo) return 'gitlab';
    if (org.bitbucket_repo) return 'bitbucket';
    return 'github';
};

const getOrgProviders = (org: Organization) => {
    const providers = [];
    if (org.github_repo) providers.push('github');
    if (org.gitlab_repo) providers.push('gitlab');
    if (org.bitbucket_repo) providers.push('bitbucket');
    return providers;
};

interface Props {
    organizations: Organization[];
}

defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Organizations', href: '/organizations' },
];

const showCreateForm = ref(false);

const createForm = useForm({
    name: '',
    slug: '',
    github_repo: '',
    gitlab_repo: '',
    bitbucket_repo: '',
});

const hasAtLeastOneRepo = computed(() => {
    return createForm.github_repo.trim() !== ''
        || createForm.gitlab_repo.trim() !== ''
        || createForm.bitbucket_repo.trim() !== '';
});

const submitCreate = () => {
    createForm.post(route('organizations.store'), {
        onSuccess: () => {
            createForm.reset();
            showCreateForm.value = false;
        },
    });
};

const generateSlug = () => {
    createForm.slug = createForm.name
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-|-$/g, '');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Organizations" />
        <div>
            <div class="mx-auto max-w-4xl space-y-4 px-4 py-8 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between">
                    <h1 class="text-2xl font-semibold tracking-tight text-gray-900 dark:text-gray-100">Organizations</h1>
                    <Button variant="button" size="sm" class="flex items-center gap-2" @click="showCreateForm = !showCreateForm">
                        <component :is="showCreateForm ? X : Plus" class="h-4 w-4" />
                        {{ showCreateForm ? 'Cancel' : 'New Organization' }}
                    </Button>
                </div>

                <!-- Create Form -->
                <Card v-if="showCreateForm" class="rounded-xl border border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardContent class="p-5">
                        <form @submit.prevent="submitCreate" class="space-y-4">
                            <!-- Name -->
                            <div class="space-y-2">
                                <Label for="org-name">Organization Name *</Label>
                                <Input
                                    id="org-name"
                                    v-model="createForm.name"
                                    placeholder="e.g. Acme Corp"
                                    @input="generateSlug"
                                    required
                                />
                                <p v-if="createForm.errors.name" class="text-sm text-destructive">{{ createForm.errors.name }}</p>
                            </div>

                            <!-- Slug -->
                            <div class="space-y-2">
                                <Label for="org-slug">Slug *</Label>
                                <Input
                                    id="org-slug"
                                    v-model="createForm.slug"
                                    placeholder="e.g. acme-corp"
                                    required
                                />
                                <p v-if="createForm.errors.slug" class="text-sm text-destructive">{{ createForm.errors.slug }}</p>
                            </div>

                            <!-- Repo section -->
                            <div class="rounded-xl border border-gray-200 p-4 space-y-4 dark:border-white/10">
                                <div class="flex items-center gap-2">
                                    <Building2 class="h-4 w-4 text-purple-400" />
                                    <p class="text-sm font-medium">Repository Access <span class="text-destructive">*</span></p>
                                </div>
                                <p class="text-xs text-muted-foreground">At least one repository is required. Members will automatically receive access when they accept the invitation.</p>

                                <!-- Repo error -->
                                <p v-if="(createForm.errors as any).repo" class="text-sm text-destructive">{{ (createForm.errors as any).repo }}</p>

                                <!-- GitHub -->
                                <div class="space-y-2">
                                    <Label for="org-github-repo">GitHub Repository</Label>
                                    <Input
                                        id="org-github-repo"
                                        v-model="createForm.github_repo"
                                        placeholder="e.g. owner/repo-name"
                                    />
                                    <p v-if="createForm.errors.github_repo" class="text-sm text-destructive">{{ createForm.errors.github_repo }}</p>
                                </div>

                                <!-- GitLab -->
                                <div class="space-y-2">
                                    <Label for="org-gitlab-repo">GitLab Repository</Label>
                                    <Input
                                        id="org-gitlab-repo"
                                        v-model="createForm.gitlab_repo"
                                        placeholder="e.g. owner/repo-name"
                                    />
                                    <p v-if="createForm.errors.gitlab_repo" class="text-sm text-destructive">{{ createForm.errors.gitlab_repo }}</p>
                                </div>

                                <!-- Bitbucket -->
                                <div class="space-y-2">
                                    <Label for="org-bitbucket-repo">Bitbucket Repository</Label>
                                    <Input
                                        id="org-bitbucket-repo"
                                        v-model="createForm.bitbucket_repo"
                                        placeholder="e.g. workspace/repo-name"
                                    />
                                    <p v-if="createForm.errors.bitbucket_repo" class="text-sm text-destructive">{{ createForm.errors.bitbucket_repo }}</p>
                                </div>
                            </div>

                            <!-- Warning if no repo -->
                            <p v-if="!hasAtLeastOneRepo" class="text-xs text-amber-500">
                                Please provide at least one repository.
                            </p>

                            <div class="flex gap-3">
                                <Button type="submit" variant="button" :disabled="createForm.processing || !hasAtLeastOneRepo">
                                    {{ createForm.processing ? 'Creating...' : 'Create Organization' }}
                                </Button>
                            </div>
                        </form>
                    </CardContent>
                </Card>

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
                        <Card :class="[
                                'rounded-xl border border-l-4 bg-white/40 transition-colors hover:bg-white/60 dark:bg-white/5 dark:hover:bg-white/10',
                                getProviderBorderColor(getOrgPrimaryProvider(org)),
                            ]">
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
                                    <div class="flex items-center gap-1.5">
                                        <span
                                            v-for="p in getOrgProviders(org)"
                                            :key="p"
                                            :title="providerIcons[p].label"
                                            :class="['flex items-center justify-center', providerIcons[p].color]"
                                            v-html="providerIcons[p].svg"
                                        />
                                    </div>
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
