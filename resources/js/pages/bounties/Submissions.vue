<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useDateFormatter } from '@/composables/useDateFormatter';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowLeft, CheckCircle, Clock, ExternalLink, Users, XCircle } from 'lucide-vue-next';
import { ref } from 'vue';

interface User {
    id: number;
    name: string;
    nickname: string;
}

interface Submission {
    id: number;
    status: string;
    pr_url: string;
    created_at: string;
    user: User;
}

interface Bounty {
    id: number;
    title: string;
    reward_xp?: number;
}

interface Props {
    bounty: Bounty;
    submissions: {
        data: Submission[];
        links?: any;
        meta?: any;
    };
    acceptedCount: number;
}

const props = defineProps<Props>();
const { formatDate } = useDateFormatter();

const rejectDialogOpen = ref(false);
const acceptDialogOpen = ref(false);
const selectedSubmission = ref<Submission | null>(null);
const pendingAcceptId = ref<number | null>(null);

const updateStatus = (submissionId: number, status: 'accepted' | 'rejected') => {
    const form = useForm({
        status: status,
    });

    form.patch(`/submissions/${submissionId}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            rejectDialogOpen.value = false;
            acceptDialogOpen.value = false;
            selectedSubmission.value = null;
            pendingAcceptId.value = null;
        },
    });
};

const handleAcceptClick = (submissionId: number) => {
    if (props.acceptedCount > 0) {
        pendingAcceptId.value = submissionId;
        acceptDialogOpen.value = true;
    } else {
        updateStatus(submissionId, 'accepted');
    }
};

const confirmAccept = () => {
    if (pendingAcceptId.value !== null) {
        updateStatus(pendingAcceptId.value, 'accepted');
    }
};

const openRejectDialog = (submission: Submission) => {
    selectedSubmission.value = submission;
    rejectDialogOpen.value = true;
};

const handleReject = () => {
    if (selectedSubmission.value) {
        updateStatus(selectedSubmission.value.id, 'rejected');
    }
};

const getStatusIcon = (status: string) => {
    switch (status) {
        case 'accepted':
            return CheckCircle;
        case 'rejected':
            return XCircle;
        default:
            return Clock;
    }
};

const getStatusColor = (status: string) => {
    switch (status) {
        case 'accepted':
            return 'bg-green-100/30 text-green-800 dark:bg-green-900/30 dark:text-green-200';
        case 'rejected':
            return 'bg-red-100/30 text-red-800 dark:bg-red-900/30 dark:text-red-200';
        default:
            return 'bg-yellow-100/30 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-200';
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Manage Submissions" />
        <div class="px-2 py-6 sm:px-4">
            <div class="mx-auto max-w-4xl space-y-6">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between w-full min-w-0">
                    <div class="min-w-0 flex-1">
                        <h1 class="text-2xl font-bold truncate">Manage Submissions</h1>
                        <p class="text-muted-foreground truncate">{{ bounty.title }}</p>
                    </div>
                    <Link :href="`/bounties/${bounty.id}`" class="shrink-0">
                        <Button variant="outline" class="flex items-center gap-2 w-full sm:w-auto">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Bounty
                        </Button>
                    </Link>
                </div>

                <Card class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5 overflow-hidden">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Submissions ({{ submissions.data.length }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="p-3 sm:p-6">
                        <div v-if="submissions.data.length > 0" class="space-y-4">
                            <div v-for="submission in submissions.data" :key="submission.id" class="rounded-lg border p-4 sm:p-6 bg-white/50 dark:bg-black/10">
                                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between w-full min-w-0">

                                    <div class="flex items-start gap-4 min-w-0 flex-1">
                                        <Avatar class="h-12 w-12 shrink-0">
                                            <AvatarImage :src="`https://github.com/${submission.user.nickname}.png`" />
                                            <AvatarFallback>{{ submission.user.name.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div class="min-w-0 flex-1">
                                            <h3 class="font-semibold truncate">{{ submission.user.name }}</h3>
                                            <p class="text-sm text-muted-foreground truncate">@{{ submission.user.nickname }}</p>

                                            <div class="mt-2 flex flex-wrap items-center gap-x-2 gap-y-1 text-sm">
                                                <a
                                                    :href="submission.pr_url"
                                                    target="_blank"
                                                    class="flex items-center gap-1 text-purple-600 hover:underline shrink-0"
                                                >
                                                    <ExternalLink class="h-3 w-3" />
                                                    View Pull Request
                                                </a>
                                                <span class="text-muted-foreground hidden xs:inline">•</span>
                                                <span class="text-muted-foreground">
                                                    Submitted {{ formatDate(submission.created_at) }}
                                                </span>
                                            </div>
                                            <div class="mt-2 flex items-center gap-2">
                                                <Badge :class="getStatusColor(submission.status)" class="flex items-center gap-1">
                                                    <component :is="getStatusIcon(submission.status)" class="h-3 w-3" />
                                                    {{ submission.status.toUpperCase() }}
                                                </Badge>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-2 w-full sm:w-auto sm:justify-end shrink-0">
                                        <div v-if="submission.status === 'pending'" class="flex gap-2 w-full sm:w-auto">
                                            <Button
                                                @click="handleAcceptClick(submission.id)"
                                                size="sm"
                                                class="bg-green-600/30 text-white hover:bg-green-700/40 flex-1 sm:flex-initial"
                                            >
                                                <CheckCircle class="mr-1 h-4 w-4" />
                                                Accept & Award XP
                                            </Button>

                                            <Button
                                                @click="openRejectDialog(submission)"
                                                size="sm"
                                                variant="destructive"
                                                class="flex-1 sm:flex-initial"
                                            >
                                                <XCircle class="mr-1 h-4 w-4" />
                                                Reject
                                            </Button>
                                        </div>

                                        <div v-else-if="submission.status === 'accepted'" class="text-sm font-medium text-green-600 w-full text-center sm:text-right">
                                            XP Awarded!
                                        </div>
                                    </div>

                                </div>
                            </div>
                        </div>

                        <div v-else class="py-12 text-center">
                            <Users class="mx-auto mb-4 h-16 w-16 text-muted-foreground" />
                            <h3 class="mb-2 text-lg font-semibold">No submissions yet</h3>
                            <p class="text-muted-foreground">When contributors submit solutions, they'll appear here.</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>

        <Dialog v-model:open="acceptDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Additional XP Deduction</DialogTitle>
                    <DialogDescription>
                        This bounty already has an accepted submission. Accepting another one will deduct
                        an additional <strong>{{ bounty.reward_xp }} XP</strong> from your balance.
                        Are you sure you want to continue?
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-end space-x-2">
                    <Button variant="outline" size="sm" @click="acceptDialogOpen = false">Cancel</Button>
                    <Button size="sm" class="bg-green-600 text-white hover:bg-green-700" @click="confirmAccept">
                        Yes, accept
                    </Button>
                </div>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="rejectDialogOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Reject Submission</DialogTitle>
                    <DialogDescription>
                        Are you sure you want to reject this submission? The contributor will be able to resubmit
                        a new solution.
                    </DialogDescription>
                </DialogHeader>
                <div class="flex justify-end space-x-2">
                    <Button variant="outline" size="sm" @click="rejectDialogOpen = false"> Cancel </Button>
                    <Button variant="destructive" size="sm" @click="handleReject"> Reject Submission </Button>
                </div>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
