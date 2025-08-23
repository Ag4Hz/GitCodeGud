<script setup lang="ts">
import { Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import InputError from '@/components/InputError.vue';
import { Calendar, DollarSign, ExternalLink, Target, Edit2, Save, X, CheckCircle, Loader2, EyeOff, RotateCcw, Eye, Archive } from 'lucide-vue-next';
import { BountyStatus, type BountyPagination, type Bounty } from '@/types/bounty';
import { usePluralization } from '@/composables/usePluralization';

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
const optimisticUpdates = ref<{[key: number]: boolean}>({});
const successMessages = ref<{[key: number]: string}>({});
const showDeletedBounties = ref(false);

const activeBounties = computed(() => {
    return props.bounties.data.filter(bounty => !bounty.deleted_at);
});

const deletedBounties = computed(() => {
    return props.bounties.data.filter(bounty => bounty.deleted_at);
});

const currentBounties = computed(() => {
    return showDeletedBounties.value ? deletedBounties.value : activeBounties.value;
});

const getStatusColor = (status: BountyStatus) => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case BountyStatus.CLOSED:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
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

const totalRewardXP = computed(() => {
    return activeBounties.value.reduce((sum, bounty) => sum + bounty.reward_xp, 0);
});

const openBounties = computed(() => {
    return activeBounties.value.filter(bounty => bounty.status === BountyStatus.OPEN);
});

const closedBounties = computed(() => {
    return activeBounties.value.filter(bounty => bounty.status === BountyStatus.CLOSED);
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
        },
        onError: () => {
            delete optimisticUpdates.value[bounty.id];
            delete successMessages.value[bounty.id];
        },
    });
};

const hasChanges = (bounty: Bounty) => {
    return editForm.title !== bounty.title ||
        editForm.description !== bounty.description ||
        editForm.reward_xp !== bounty.reward_xp;
};

const softDeleteBounty = (bounty: Bounty) => {
    if (confirm('Are you sure you want to archive this bounty? It will be hidden from public listings but can be restored later.')) {
        deleteForm.delete(route('bounties.destroy', bounty.id), {
            preserveScroll: false,
            onSuccess: () => {},
            onError: (errors) => {
                console.error('Delete request failed:', errors);
                if (errors.general) {
                    alert(errors.general);
                }
            }
        });
    }
};

const restoreBounty = (bounty: Bounty) => {
    restoreForm.patch(route('bounties.restore', bounty.id), {
        preserveScroll: false,
        onSuccess: () => {},
        onError: (errors) => {
            console.error('Restore request failed:', errors);
            if (errors.general) {
                alert(errors.general);
            }
        }
    });
};
</script>

<template>
    <Card>
        <CardHeader>
            <div>
                <CardTitle class="flex items-center gap-2">
                    <Target class="h-5 w-5" />
                    My Bounties
                </CardTitle>
                <CardDescription>
                    View and manage your bounty campaigns
                </CardDescription>
            </div>
        </CardHeader>
        <CardContent>
            <!-- Stats Overview -->
            <div class="grid grid-cols-1 md:grid-cols-5 gap-4 mb-6">
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <Target class="h-4 w-4 text-blue-600" />
                        <span class="text-sm font-medium">Active</span>
                    </div>
                    <p class="text-2xl font-bold">{{ bountyStats.total }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-4 w-4 rounded-full bg-green-600"></div>
                        <span class="text-sm font-medium">Open</span>
                    </div>
                    <p class="text-2xl font-bold text-green-600">{{ bountyStats.open }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <div class="h-4 w-4 rounded-full bg-gray-600"></div>
                        <span class="text-sm font-medium">Closed</span>
                    </div>
                    <p class="text-2xl font-bold text-gray-600">{{ bountyStats.closed }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <Archive class="h-4 w-4 text-orange-600" />
                        <span class="text-sm font-medium">Archived</span>
                    </div>
                    <p class="text-2xl font-bold text-orange-600">{{ bountyStats.archived }}</p>
                </div>
                <div class="p-4 border rounded-lg bg-card">
                    <div class="flex items-center gap-2 mb-2">
                        <DollarSign class="h-4 w-4 text-yellow-600" />
                        <span class="text-sm font-medium">Total Reward XP</span>
                    </div>
                    <p class="text-2xl font-bold text-yellow-600">{{ bountyStats.totalRewardXP }}</p>
                </div>
            </div>

            <!-- Toggle View -->
            <div class="flex gap-2 mb-6">
                <Button
                    @click="showDeletedBounties = false"
                    :variant="!showDeletedBounties ? 'default' : 'outline'"
                    size="sm"
                    class="flex items-center gap-2"
                >
                    <Eye class="h-4 w-4" />
                    Active Bounties ({{ bountyStats.total }})
                </Button>
                <Button
                    @click="showDeletedBounties = true"
                    :variant="showDeletedBounties ? 'default' : 'outline'"
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
                    class="border rounded-lg p-4 transition-colors"
                    :class="[
                        editingBounty === bounty.id ? 'bg-accent/30 border-primary' : 'hover:bg-accent/50',
                        bounty.deleted_at ? 'bg-gray-50 dark:bg-gray-800/50 border-gray-300' : ''
                    ]"
                >
                    <!-- Success Message -->
                    <div v-if="successMessages[bounty.id]" class="mb-4 p-3 rounded-lg bg-green-50 border border-green-200 dark:bg-green-950 dark:border-green-800">
                        <div class="flex items-center gap-2">
                            <CheckCircle class="h-4 w-4 text-green-600 dark:text-green-400" />
                            <p class="text-sm font-medium text-green-800 dark:text-green-200">{{ successMessages[bounty.id] }}</p>
                        </div>
                    </div>

                    <div class="flex items-start justify-between gap-4">
                        <!-- Bounty Content -->
                        <div class="flex-1 min-w-0 space-y-3">
                            <!-- Edit Mode -->
                            <div v-if="editingBounty === bounty.id && !bounty.deleted_at" class="space-y-4">
                                <!-- Title Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-title">Title *</Label>
                                    <Input id="edit-title" v-model="editForm.title" type="text" required maxlength="255"
                                           :class="editForm.errors.title && 'border-red-500'"
                                           :disabled="editForm.processing"
                                    />
                                    <p class="text-xs text-muted-foreground">{{ editForm.title.length }}/255</p>
                                    <InputError :message="editForm.errors.title" />
                                </div>

                                <!-- Description Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-description">Description *</Label>
                                    <textarea id="edit-description" v-model="editForm.description" required maxlength="2000" rows="4"
                                              placeholder="Detailed description of what needs to be done..."
                                              class="flex min-h-[60px] w-full rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-sm placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-ring disabled:cursor-not-allowed disabled:opacity-50 resize-y"
                                              :class="editForm.errors.description && 'border-red-500 focus-visible:ring-red-500'"
                                              :disabled="editForm.processing"
                                    />
                                    <p class="text-xs text-muted-foreground">{{ editForm.description.length }}/2000</p>
                                    <InputError :message="editForm.errors.description" />
                                </div>

                                <!-- Reward XP Edit -->
                                <div class="space-y-1">
                                    <Label for="edit-reward">Reward (XP) *</Label>
                                    <div class="flex items-center gap-2">
                                        <Input id="edit-reward" v-model.number="editForm.reward_xp" type="number" min="1" max="1000" required class="w-32"
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
                                    <div class="p-3 rounded-lg bg-gray-50 dark:bg-gray-900 border text-sm">
                                        <div class="flex items-center gap-2">
                                            <ExternalLink class="h-3 w-3" />
                                            <a :href="bounty.issue.url" target="_blank" class="text-blue-600 hover:underline">
                                                {{ bounty.issue.url }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- View Mode -->
                            <div v-else>
                                <!-- Title and Status -->
                                <div class="flex items-center gap-3 flex-wrap">
                                    <h3 class="font-medium text-lg" :class="{ 'text-gray-500': bounty.deleted_at }">
                                        {{ bounty.title }}
                                    </h3>
                                    <Badge v-if="bounty.deleted_at" variant="secondary" class="text-xs">
                                        Archived
                                    </Badge>
                                    <Badge v-else :class="getStatusColor(bounty.status)" class="text-xs">
                                        {{ getStatusDisplayText(bounty.status) }}
                                    </Badge>
                                </div>

                                <!-- Description -->
                                <p v-if="bounty.description" class="text-muted-foreground line-clamp-2" :class="{ 'text-gray-400': bounty.deleted_at }">
                                    {{ bounty.description }}
                                </p>

                                <!-- Repository and Issue Links -->
                                <div class="flex items-center gap-4 text-sm">
                                    <a :href="bounty.issue.repo.url" target="_blank" rel="noopener noreferrer"
                                       class="text-blue-600 hover:underline flex items-center gap-1">
                                        <ExternalLink class="h-3 w-3" />
                                        Repository
                                    </a>
                                    <a :href="bounty.issue.url" target="_blank" rel="noopener noreferrer" class="text-blue-600 hover:underline flex items-center gap-1">
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
                        <div v-if="props.canEditBounties" class="flex gap-2 flex-shrink-0">
                            <!-- Archived Bounty Actions -->
                            <template v-if="bounty.deleted_at">
                                <Button
                                    @click="restoreBounty(bounty)"
                                    :disabled="restoreForm.processing"
                                    variant="outline"
                                    size="sm"
                                    class="flex items-center gap-1"
                                >
                                    <RotateCcw class="h-3 w-3" />
                                    Restore
                                </Button>
                            </template>

                            <!-- Active Bounty Actions -->
                            <template v-else>
                                <!-- Edit Mode Buttons -->
                                <div v-if="editingBounty === bounty.id" class="flex gap-2">
                                    <Button @click="saveEdit(bounty)" :disabled="editForm.processing || !hasChanges(bounty)" size="sm" class="min-w-[80px]">
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
                                <!-- View Mode Buttons -->
                                <div v-else class="flex gap-2">
                                    <Button @click="startEdit(bounty)" variant="outline" size="sm" class="flex items-center gap-1">
                                        <Edit2 class="h-3 w-3" />
                                        Edit
                                    </Button>
                                    <Button
                                        @click="softDeleteBounty(bounty)"
                                        :disabled="deleteForm.processing"
                                        variant="outline"
                                        size="sm"
                                        class="text-red-600 hover:text-red-700 hover:bg-red-50"
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
                <div v-if="props.bounties.last_page > 1" class="flex justify-center mt-6">
                    <div class="flex items-center gap-2">
                        <Link v-if="props.bounties.current_page > 1" :href="route('profile.show', { page: props.bounties.current_page - 1 })" class="px-3 py-2 text-sm border rounded hover:bg-accent">
                            Previous
                        </Link>
                        <span class="text-sm text-muted-foreground px-3">
                            Page {{ props.bounties.current_page }} of {{ props.bounties.last_page }}
                        </span>
                        <Link v-if="props.bounties.current_page < props.bounties.last_page" :href="route('profile.show', { page: props.bounties.current_page + 1 })"
                              class="px-3 py-2 text-sm border rounded hover:bg-accent">
                            Next
                        </Link>
                    </div>
                </div>
            </div>

            <!-- Empty State -->
            <div v-else class="text-center py-12">
                <Archive v-if="showDeletedBounties" class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <Target v-else class="mx-auto h-12 w-12 text-muted-foreground mb-4" />
                <h3 class="text-lg font-semibold mb-2">
                    {{ showDeletedBounties ? 'No archived bounties' : 'No active bounties yet' }}
                </h3>
                <p class="text-muted-foreground">
                    {{ showDeletedBounties ? 'Your archived bounties will appear here.' : 'Create your first bounty above to start incentivizing contributions to your projects.' }}
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
