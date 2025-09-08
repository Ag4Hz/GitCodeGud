<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
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
}

defineProps<Props>();
const { formatDate } = useDateFormatter();

const rejectDialogOpen = ref(false);
const selectedSubmission = ref<Submission | null>(null);

const updateStatus = (submissionId: number, status: 'accepted' | 'rejected') => {
    const form = useForm({
        status: status,
    });

    form.patch(`/submissions/${submissionId}/status`, {
        preserveScroll: true,
        onSuccess: () => {
            console.log(`Submission ${status} successfully`);
            rejectDialogOpen.value = false;
            selectedSubmission.value = null;
        },
    });
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
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
        case 'rejected':
            return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200';
        default:
            return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200';
    }
};
</script>

<template>
    <AppLayout>
        <Head title="Manage Submissions" />
        <div class="px-4 py-6">
            <div class="mx-auto max-w-6xl space-y-6">
                <!-- Header -->
                <div class="flex items-center justify-between">
                    <div>
                        <h1 class="text-2xl font-bold">Manage Submissions</h1>
                        <p class="text-muted-foreground">{{ bounty.title }}</p>
                    </div>
                    <Link :href="`/bounties/${bounty.id}`">
                        <Button variant="outline" class="flex items-center gap-2">
                            <ArrowLeft class="h-4 w-4" />
                            Back to Bounty
                        </Button>
                    </Link>
                </div>

                <!-- Submissions List -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Users class="h-5 w-5" />
                            Submissions ({{ submissions.data.length }})
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <div v-if="submissions.data.length > 0" class="space-y-4">
                            <div v-for="submission in submissions.data" :key="submission.id" class="rounded-lg border p-6">
                                <div class="flex items-start justify-between">
                                    <div class="flex items-center gap-4">
                                        <Avatar class="h-12 w-12">
                                            <AvatarImage :src="`https://github.com/${submission.user.nickname}.png`" />
                                            <AvatarFallback>{{ submission.user.name.charAt(0) }}</AvatarFallback>
                                        </Avatar>
                                        <div>
                                            <h3 class="font-semibold">{{ submission.user.name }}</h3>
                                            <p class="text-sm text-muted-foreground">@{{ submission.user.nickname }}</p>
                                            <div class="mt-2 flex items-center gap-2">
                                                <a
                                                    :href="submission.pr_url"
                                                    target="_blank"
                                                    class="flex items-center gap-1 text-sm text-blue-600 hover:underline"
                                                >
                                                    <ExternalLink class="h-3 w-3" />
                                                    View Pull Request
                                                </a>
                                                <span class="text-sm text-muted-foreground">
                                                    • Submitted {{ formatDate(submission.created_at) }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Badge :class="getStatusColor(submission.status)" class="flex items-center gap-1">
                                            <component :is="getStatusIcon(submission.status)" class="h-3 w-3" />
                                            {{ submission.status.toUpperCase() }}
                                        </Badge>

                                        <!-- Action buttons for pending submissions -->
                                        <div v-if="submission.status === 'pending'" class="flex gap-2">
                                            <Button
                                                @click="updateStatus(submission.id, 'accepted')"
                                                size="sm"
                                                class="bg-green-600 text-white hover:bg-green-700"
                                            >
                                                <CheckCircle class="mr-1 h-4 w-4" />
                                                Accept & Award XP
                                            </Button>

                                            <Dialog v-model:open="rejectDialogOpen">
                                                <DialogTrigger asChild>
                                                    <Button @click="openRejectDialog(submission)" size="sm" variant="destructive">
                                                        <XCircle class="mr-1 h-4 w-4" />
                                                        Reject
                                                    </Button>
                                                </DialogTrigger>
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
                                        </div>

                                        <!-- Status info for processed submissions -->
                                        <div v-else-if="submission.status === 'accepted'" class="text-sm font-medium text-green-600">XP Awarded!</div>
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
    </AppLayout>
</template>
