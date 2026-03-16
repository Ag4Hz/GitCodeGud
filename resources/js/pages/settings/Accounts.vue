<script setup lang="ts">
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { useToast } from '@/composables/useToast';
import AppLayout from '@/layouts/AppLayout.vue';
import { getAccountMessage } from '@/utils/toastMessages';
import { router, usePage } from '@inertiajs/vue3';
import { ref, watch } from 'vue';

const { success: showSuccess, error: showError } = useToast();

const openDialog = ref(false);
const providerToDisconnect = ref<{ id: string; name: string } | null>(null);

defineOptions({
    layout: AppLayout,
});

interface Provider {
    provider: string;
    provider_id: string;
    nickname: string;
    avatar: string;
}

interface Props {
    linkedProviders: Provider[];
    canDisconnect: boolean;
}

const props = defineProps<Props>();

const providers = [
    {
        id: 'github',
        name: 'GitHub',
        logo: '/assets/images/github.svg',
        connectUrl: '/auth/github/redirect',
        description: 'Connect to use GitHub repositories and issues.',
    },
    {
        id: 'gitlab',
        name: 'GitLab',
        logo: '/assets/images/gitlab.svg',
        connectUrl: '/auth/gitlab/redirect',
        description: 'Connect to use GitLab repositories and issues.',
    },
    {
        id: 'bitbucket',
        name: 'Bitbucket',
        logo: '/assets/images/bitbucket.svg',
        connectUrl: '/auth/bitbucket/redirect',
        description: 'Connect to use Bitbucket repositories.',
    },
    {
        id: 'jira',
        name: 'Jira',
        logo: '/assets/images/jira.svg',
        connectUrl: '/auth/jira/redirect',
        description: 'Required to create bounties on Bitbucket repositories (Jira is used for issue tracking).',
    },
];

const disconnecting = ref<string | null>(null);

const isConnected = (providerId: string) => {
    return props.linkedProviders.find((p) => p.provider === providerId);
};

const handleDisconnect = (providerId: string) => {
    if (!props.canDisconnect) {
        showError(getAccountMessage('error', 'last_provider', 'en'));
        return;
    }

    const provider = providers.find((p) => p.id === providerId);
    if (provider) {
        providerToDisconnect.value = { id: providerId, name: provider.name };
        openDialog.value = true;
    }
};

const confirmDisconnect = () => {
    if (!providerToDisconnect.value) return;

    disconnecting.value = providerToDisconnect.value.id;
    router.delete(route('accounts.disconnect', providerToDisconnect.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            showSuccess(getAccountMessage('success', 'disconnect', 'en'));
        },
        onError: () => {
            showError(getAccountMessage('error', 'disconnect', 'en'));
        },
        onFinish: () => {
            disconnecting.value = null;
            openDialog.value = false;
            providerToDisconnect.value = null;
        },
    });
};

const handleConnect = (connectUrl: string) => {
    window.location.href = connectUrl;
};

const page = usePage();
watch(
    () => page.props,
    (props: any) => {
        if (props.flash?.success) {
            showSuccess(props.flash.success);
        }
        if (props.flash?.error) {
            showError(props.flash.error);
        }
    },
    { deep: true },
);
</script>

<template>
    <div class="space-y-6">
        <Card
            v-for="provider in providers"
            :key="provider.id"
            class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5"
        >
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center">
                            <img :src="provider.logo" :alt="provider.name" class="h-full w-full object-contain" />
                        </div>
                        <div>
                            <CardTitle>{{ provider.name }}</CardTitle>
                            <CardDescription v-if="!isConnected(provider.id)">
                                {{ provider.description }}
                            </CardDescription>
                        </div>
                    </div>
                </div>
            </CardHeader>

            <CardContent>
                <div v-if="isConnected(provider.id)" class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <Avatar>
                            <AvatarImage :src="isConnected(provider.id)!.avatar || ''" />
                            <AvatarFallback>
                                {{ isConnected(provider.id)!.nickname?.[0]?.toUpperCase() || '?' }}
                            </AvatarFallback>
                        </Avatar>
                        <span class="font-medium">{{ isConnected(provider.id)!.nickname || 'Unknown' }}</span>
                    </div>

                    <Button
                        variant="destructive"
                        :disabled="!canDisconnect || disconnecting === provider.id"
                        @click="handleDisconnect(provider.id)"
                    >
                        {{ disconnecting === provider.id ? 'Disconnecting...' : 'Disconnect' }}
                    </Button>
                </div>

                <Button v-else variant="default" @click="handleConnect(provider.connectUrl)">
                    Connect {{ provider.name }}
                </Button>
            </CardContent>
        </Card>

        <!-- Confirmation Dialog -->
        <Dialog v-model:open="openDialog">
            <DialogContent
                class="fixed top-36 left-1/2 max-w-md -translate-x-1/2 rounded-xl bg-red-400/40 p-2 backdrop-blur dark:bg-red-900/40"
            >
                <DialogHeader>
                    <DialogTitle class="text-black dark:text-white">
                        Disconnect {{ providerToDisconnect?.name }} Account?
                    </DialogTitle>
                </DialogHeader>
                <DialogDescription>
                    <p class="text-sm text-muted-foreground">
                        Are you sure you want to disconnect your {{ providerToDisconnect?.name }} account? You can
                        reconnect it anytime.
                    </p>
                </DialogDescription>
                <DialogFooter class="flex justify-end gap-2">
                    <Button variant="secondary" @click="openDialog = false">Cancel</Button>
                    <Button variant="destructive" :disabled="disconnecting !== null" @click="confirmDisconnect">
                        {{ disconnecting ? 'Disconnecting...' : 'Disconnect' }}
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>
    </div>
</template>

<style scoped></style>
