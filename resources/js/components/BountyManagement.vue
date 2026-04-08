<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { usePluralization } from '@/composables/usePluralization';
import { BountyStatus, type Bounty, type BountyPagination } from '@/types/bounty';
import { Link, router, useForm } from '@inertiajs/vue3';
import { Archive, Calendar, CheckCircle, DollarSign, Edit2, ExternalLink, Eye, EyeOff, Loader2, RotateCcw, Save, Target, X } from 'lucide-vue-next';
import { computed, ref } from 'vue';

interface Props {
    bounties: BountyPagination;
    canEditBounties?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
    canEditBounties: true,
});

const { formatCount } = usePluralization();

const editingBounty = ref<number | null>(null);
const optimisticUpdates = ref<{ [key: number]: boolean }>({});
const successMessages = ref<{ [key: number]: string }>({});
const showDeletedBounties = ref(false);

const activeBounties = computed(() => {
    return props.bounties.data.filter((bounty) => !bounty.deleted_at);
});

const deletedBounties = computed(() => {
    return props.bounties.data.filter((bounty) => bounty.deleted_at);
});

const currentBounties = computed(() => {
    return showDeletedBounties.value ? deletedBounties.value : activeBounties.value;
});

const getStatusColor = (status: BountyStatus) => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200';
        case BountyStatus.CLOSED:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700/70 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700/70 dark:text-gray-200';
    }
};

const getStatusDisplayText = (status: BountyStatus): string => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'OPEN';
        case BountyStatus.CLOSED:
            return 'CLOSED';
    }
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const getProviderName = (provider: string): string => {
    const names: Record<string, string> = {
        github: 'GitHub',
        gitlab: 'GitLab',
        bitbucket: 'Bitbucket',
        jira: 'Bitbucket',
    };
    return names[provider] || provider;
};

const getProviderBadgeColor = (provider: string): string => {
    const colors: Record<string, string> = {
        github: 'bg-gray-100 text-gray-800 dark:bg-gray-800 dark:text-gray-100',
        gitlab: 'bg-orange-100 text-orange-800 dark:bg-orange-900/60 dark:text-orange-200',
        bitbucket: 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
        jira: 'bg-blue-100 text-blue-800 dark:bg-blue-900/60 dark:text-blue-200',
    };
    return colors[provider] || colors.github;
};

const totalRewardXP = computed(() => {
    return activeBounties.value.reduce((sum, bounty) => sum + bounty.reward_xp, 0);
});

const openBounties = computed(() => {
    return activeBounties.value.filter((bounty) => bounty.status === BountyStatus.OPEN);
});

const closedBounties = computed(() => {
    return activeBounties.value.filter((bounty) => bounty.status === BountyStatus.CLOSED);
});

const bountyStats = computed(() => ({
    total: activeBounties.value.length,
    open: openBounties.value.length,
    closed: closedBounties.value.length,
    archived: deletedBounties.value.length,
    totalRewardXP: totalRewardXP.value,
}));

// Edit form setup
const editForm = useForm({
    title: '',
    description: '',
    reward_xp: 50,
});

const deleteForm = useForm({});
const restoreForm = useForm({});

// Edit functionality
const startEdit = (bounty: Bounty) => {
    editingBounty.value = bounty.id;
    editForm.reset();
    editForm.title = bounty.title;
    editForm.description = bounty.description;
    editForm.reward_xp = bounty.reward_xp;

    delete successMessages.value[bounty.id];
    delete optimisticUpdates.value[bounty.id];
};

const cancelEdit = () => {
    editingBounty.value = null;
    editForm.reset();
    editForm.clearErrors();
};

const saveEdit = (bounty: Bounty) => {
    delete successMessages.value[bounty.id];

    // Show optimistic update immediately
    optimisticUpdates.value[bounty.id] = true;
    successMessages.value[bounty.id] = 'Bounty updated successfully!';

    editForm.patch(route('bounties.update', bounty.id), {
        preserveScroll: true,
        onSuccess: () => {
            editingBounty.value = null;
            editForm.reset();
            // Clean up temporary state immediately
            delete optimisticUpdates.value[bounty.id];
            delete successMessages.value[bounty.id];

            router.reload({ only: ['bounties'] });
        },
        onError: () => {
            delete optimisticUpdates.value[bounty.id];
            delete successMessages.value[bounty.id];
        },
    });
};

const hasChanges = (bounty: Bounty) => {
    return editForm.title !== bounty.title || editForm.description !== bounty.description || editForm.reward_xp !== bounty.reward_xp;
};

const softDeleteBounty = (bounty: Bounty) => {
    if (confirm('Are you sure you want to archive this bounty? It will be hidden from public listings but can be restored later.')) {
        deleteForm.delete(route('bounties.destroy', bounty.id), {
            preserveScroll: true,
            onSuccess: () => {
                router.reload({ only: ['bounties'] });
            },
            onError: (errors) => {
                console.error('Delete request failed:', errors);
                if (errors.general) {
                    alert(errors.general);
                }
            },
        });
    }
};

const restoreBounty = (bounty: Bounty) => {
    restoreForm.patch(route('bounties.restore', bounty.id), {
        preserveScroll: true,
        onSuccess: () => {
            router.reload({ only: ['bounties'] });
        },
        onError: (errors) => {
            console.error('Restore request failed:', errors);
            if (errors.general) {
                alert(errors.general);
            }
        },
    });
};
</script>

<template>
    <Card class="gap-0 rounded-2xl border border-gray-200 bg-white/40 p-0 shadow-sm backdrop-blur-xl dark:border-white/10 dark:bg-white/5">
        <CardHeader class="gap-0 rounded-t-2xl bg-white/40 px-2 py-3 backdrop-blur-xl sm:px-5 md:px-6 dark:bg-white/5">
            <div>
                <CardTitle class="flex items-center gap-2">
                    <Target class="h-5 w-5" />
                    My Bounties
                </CardTitle>
                <CardDescription> View and manage your bounty campaigns </CardDescription>
            </div>
        </CardHeader>
        <CardContent class="my-8">
            <!-- Stats Overview -->
            <div class="mb-6 grid grid-cols-1 gap-4 md:grid-cols-5">
                <div class="rounded-2xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="mb-2 flex items-center gap-2">
                        <Target class="h-4 w-4 text-purple-600" />
                        <span class="text-sm font-medium">Active</span>
                    </div>
                    <p class="text-2xl font-bold text-purple-600">{{ bountyStats.total }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="mb-2 flex items-center gap-2">
                        <div class="h-4 w-4 rounded-full bg-green-800"></div>
                        <span class="text-sm font-medium">Open</span>
                    </div>
                    <p class="text-2xl font-bold text-green-800">{{ bountyStats.open }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="mb-2 flex items-center gap-2">
                        <div class="h-4 w-4 rounded-full bg-gray-600"></div>
                        <span class="text-sm font-medium">Closed</span>
                    </div>
                    <p class="text-2xl font-bold text-gray-600">{{ bountyStats.closed }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="mb-2 flex items-center gap-2">
                        <Archive class="h-4 w-4 text-red-800" />
                        <span class="text-sm font-medium">Archived</span>
                    </div>
                    <p class="text-2xl font-bold text-red-800">{{ bountyStats.archived }}</p>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white/40 p-4 dark:border-white/10 dark:bg-white/5">
                    <div class="mb-2 flex items-center gap-2">
                        <DollarSign class="h-4 w-4 text-yellow-700" />
                        <span class="text-sm font-medium">Total XP</span>
                    </div>
                    <p class="text-2xl font-bold text-yellow-700">{{ bountyStats.totalRewardXP }}</p>
                </div>
            </div>

            <!-- Toggle View -->
            <div class="mb-6 flex flex-col gap-2 sm:flex-row">
                <Button
                    @click="showDeletedBounties = false"
                    :variant="!showDeletedBounties ? 'secondaryButton' : 'button'"
                    size="sm"
                    class="flex items-center py-4"
                >
                    <Eye class="h-4 w-4" />
                    Active Bounties ({{ bountyStats.total }})
                </Button>
                <Button
                    @click="showDeletedBounties = true"
                    :variant="showDeletedBounties ? 'secondaryButton' : 'button'"
                    size="sm"
                    class="flex items-center gap-2"
                >
                    <Archive class="h-4 w-4" />
                    Archived Bounties ({{ bountyStats.archived }})
                </Button>
            </div>

            <!-- Bounties List -->
            <div v-if="currentBounties.length > 0" class="space-y-4">
                <div
                    v-for="bounty in currentBounties"
                    :key="bounty.id"
                    class="bg-whie/40 rounded-2xl border p-4 transition-colors dark:bg-white/5"
                    :class="[
                        editingBounty === bounty.id
                            ? 'border-primary bg-accent/30'
                            : 'rounded-2xl border border-gray-200 bg-white/40 hover:bg-white/90 dark:border-white/10 dark:bg-white/5 dark:hover:bg-white/10',
                        bounty.deleted_at ? 'border-gray-300 bg-gray-50 dark:bg-gray-800/50' : '',
                    ]"
                >
                    <!-- Success Message -->
                    <div
                        v-if="successMessages[bounty.id]"
                        class="mb-4 rounded-lg border border-green-200 bg-green-50 p-3 dark:border-green-800 dark:bg-green-950"
                    >
                        <div class="flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ successMessages[bounty.id] }}</p>
                        </div>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <!-- Bounty Content -->
                        <div class="min-w-0 flex-1 space-y-3">
                            <!-- Edit Mode -->
                            <div v-if="editingBounty === bounty.id && !bounty.deleted_at" class="space-y-4">
                                <!-- Title Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-title">Title *</Label>
                                    <Input
                                        id="edit-title"
                                        v-model="editForm.title"
                                        type="text"
                                        required
                                        maxlength="255"
                                        class="h-12 w-full sm:pl-4 md:pl-4 lg:pl-4"
                                        :class="editForm.errors.reward_xp && 'border-red-500'"
                                        :disabled="editForm.processing"
                                    />
                                    <p class="text-xs text-muted-foreground">{{ editForm.title.length }}/255</p>
                                    <InputError :message="editForm.errors.title" />
                                </div>

                                <!-- Description Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-description">Description *</Label>
                                    <textarea
                                        id="edit-description"
                                        v-model="editForm.description"
                                        required
                                        maxlength="2000"
                                        rows="4"
                                        placeholder="Detailed description of what needs to be done..."
                                        class="flex min-h-[80px] w-full rounded-2xl border border-gray-200 bg-white/40 p-4 text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 placeholder:text-muted-foreground focus-visible:border-green-500 focus-visible:ring-2 focus-visible:ring-green-500/50 focus-visible:outline-none sm:backdrop-blur-2xl dark:border-white/10 dark:bg-white/10 dark:text-gray-100 dark:focus-visible:border-ring dark:focus-visible:ring-ring/50"
                                        :class="editForm.errors.description && 'border-red-800 focus-visible:ring-red-800'"
                                        :disabled="editForm.processing"
                                    />
                                    <p class="text-xs text-muted-foreground">{{ editForm.description.length }}/2000</p>
                                    <InputError :message="editForm.errors.description" />
                                </div>

                                <!-- Reward XP Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-reward">Reward (XP) *</Label>
                                    <div class="flex items-center gap-2">
                                        <Input
                                            id="edit-reward"
                                            v-model.number="editForm.reward_xp"
                                            type="number"
                                            min="1"
                                            max="1000"
                                            required
                                            class="h-12 w-32"
                                            :class="editForm.errors.reward_xp && 'border-red-500'"
                                            :disabled="editForm.processing"
                                        />
                                        <span class="text-sm text-muted-foreground">XP (1-1000)</span>
                                    </div>
                                    <InputError :message="editForm.errors.reward_xp" />
                                </div>

                                <!-- Connected Issue Info (Read-only in edit) -->
                                <div class="space-y-1">
                                    <Label>Connected GitHub Issue</Label>
                                    <div class="rounded-lg border bg-gray-50 p-3 text-sm dark:bg-purple-900/30">
                                        <div class="flex items-center gap-2">
                                            <ExternalLink class="h-3 w-3" />
                                            <a :href="bounty.issue.url" target="_blank" class="break-all text-purple-600 hover:underline">
                                                {{ bounty.issue.url }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                <div class="flex gap-2 pt-4">
                                    <Button
                                        @click="saveEdit(bounty)"
                                        :disabled="editForm.processing || !hasChanges(bounty)"
                                        size="sm"
                                        class="min-w-[80px]"
                                    >
                                        <span v-if="editForm.processing" class="flex items-center gap-1">
                                            <Loader2 class="h-3 w-3 animate-spin" />
                                            Saving
                                        </span>
                                        <span v-else class="flex items-center gap-1">
                                            <Save class="h-3 w-3" />
                                            Save
                                        </span>
                                    </Button>
                                    <Button @click="cancelEdit" variant="outline" size="sm" :disabled="editForm.processing">
                                        <X class="h-3 w-3" />
                                        Cancel
                                    </Button>
                                </div>
                            </div>

                            <!-- View Mode -->
                            <div v-else>
                                <!-- Title and Status -->
                                <div class="flex flex-wrap items-center gap-3">
                                    <h3 class="text-lg font-medium" :class="{ 'text-gray-500': bounty.deleted_at }">
                                        {{ bounty.title }}
                                    </h3>

                                    <!-- Provider Badge -->
                                    <Badge
                                        :class="getProviderBadgeColor(bounty.issue?.provider || 'github')"
                                        class="text-xs"
                                    >
                                        {{ getProviderName(bounty.issue?.provider || 'github') }}
                                    </Badge>

                                    <Badge v-if="bounty.deleted_at" variant="secondary" class="text-xs"> Archived </Badge>
                                    <Badge
                                        v-else
                                        :class="getStatusColor(bounty.status)"
                                        class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                    >
                                        {{ getStatusDisplayText(bounty.status) }}
                                    </Badge>
                                </div>

                                <!-- Description -->
                                <p
                                    v-if="bounty.description"
                                    class="line-clamp-2 text-muted-foreground"
                                    :class="{ 'text-gray-400': bounty.deleted_at }"
                                >
                                    {{ bounty.description }}
                                </p>

                                <!-- Repository and Issue Links -->
                                <div class="flex items-center gap-4 text-sm">
                                    <a
                                        :href="bounty.issue.repo.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-1 text-purple-700/70 dark:text-purple-300/70 dark:hover:text-white"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        Repository
                                    </a>
                                    <a
                                        :href="bounty.issue.url"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                        class="flex items-center gap-1 text-purple-700/70 dark:text-purple-300/70 dark:hover:text-white"
                                    >
                                        <ExternalLink class="h-3 w-3" />
                                        Issue
                                    </a>
                                </div>

                                <!-- Metadata -->
                                <div class="flex items-center gap-4 text-sm text-muted-foreground">
                                    <span class="flex items-center gap-1">
                                        <DollarSign class="h-3 w-3" />
                                        {{ bounty.reward_xp }} XP
                                    </span>
                                    <span class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        {{ bounty.deleted_at ? 'Archived' : 'Created' }}:
                                        {{ formatDate(bounty.deleted_at || bounty.created_at) }}
                                    </span>
                                    <span v-if="optimisticUpdates[bounty.id]" class="flex items-center gap-1 text-green-600">
                                        <CheckCircle class="h-3 w-3" />
                                        Just updated
                                    </span>
                                    <span v-else-if="bounty.updated_at !== bounty.created_at && !bounty.deleted_at" class="flex items-center gap-1">
                                        <Calendar class="h-3 w-3" />
                                        Updated {{ formatDate(bounty.updated_at) }}
                                    </span>
                                    <span v-if="bounty.submissions_count && bounty.submissions_count > 0" class="flex items-center gap-1">
                                        <Target class="h-3 w-3" />
                                        {{ formatCount(bounty.submissions_count, 'submission') }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Action Buttons -->
                        <div v-if="props.canEditBounties" class="flex flex-shrink-0 gap-2">
                            <!-- Archived Bounty Actions -->
                            <template v-if="bounty.deleted_at">
                                <Button
                                    @click="restoreBounty(bounty)"
                                    :disabled="restoreForm.processing"
                                    variant="button"
                                    size="sm"
                                    class="flex items-center gap-1"
                                >
                                    <RotateCcw class="h-3 w-3" />
                                    Restore
                                </Button>
                            </template>

                            <!-- Active Bounty Actions -->
                            <template v-else>
                                <!-- View Mode Buttons -->
                                <div v-if="editingBounty !== bounty.id" class="flex gap-2">
                                    <Button @click="startEdit(bounty)" variant="button" size="sm" class="flex items-center gap-1">
                                        <Edit2 class="h-3 w-3" />
                                        Edit
                                    </Button>
                                    <Button
                                        @click="softDeleteBounty(bounty)"
                                        :disabled="deleteForm.processing"
                                        variant="redButton"
                                        size="sm"
                                        class="text-red-600"
                                    >
                                        <EyeOff class="h-3 w-3" />
                                        Archive
                                    </Button>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <!-- Pagination -->
                <div v-if="props.bounties.last_page > 1" class="mt-6 flex justify-center">
                    <div class="flex items-center gap-2">
                        <Link
                            v-if="props.bounties.current_page > 1"
                            :href="route('profile.show', { page: props.bounties.current_page - 1 })"
                            class="rounded border px-3 py-2 text-sm hover:bg-accent"
                        >
                            Previous
                        </Link>
                        <span class="px-3 text-sm text-muted-foreground">
                            Page {{ props.bounties.current_page }} of {{ props.bounties.last_page }}
                        </span>
                        <Link
                            v-if="props.bounties.current_page < props.bounties.last_page"
                            :href="route('profile.show', { page: props.bounties.current_page + 1 })"
                            class="rounded border px-3 py-2 text-sm hover:bg-accent"
                        >
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="py-12 text-center">
                <Archive v-if="showDeletedBounties" class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                <Target v-else class="mx-auto mb-4 h-12 w-12 text-muted-foreground" />
                <h3 class="mb-2 text-lg font-semibold">
                    {{ showDeletedBounties ? 'No archived bounties' : 'No active bounties yet' }}
                </h3>
                <p class="text-muted-foreground">
                    {{
                        showDeletedBounties
                            ? 'Your archived bounties will appear here.'
                            : 'Create your first bounty above to start incentivizing contributions to your projects.'
                    }}
                </p>
            </div>
        </CardContent>
    </Card>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
