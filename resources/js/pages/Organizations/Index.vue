<script setup lang="ts">
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    pivot: MemberPivot;
}

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
