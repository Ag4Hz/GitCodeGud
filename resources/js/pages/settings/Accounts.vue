<script setup lang="ts">

import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';

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
    },
    {
        id: 'gitlab',
        name: 'GitLab',
        logo: '/assets/images/gitlab.svg',
        connectUrl: '/auth/gitlab/redirect',
    },
    {
        id: 'bitbucket',
        name: 'Bitbucket',
        logo: '/assets/images/bitbucket.svg',
        connectUrl: '/auth/bitbucket/redirect',
    },
];

const disconnecting = ref<string | null>(null);

const isConnected = (providerId: string) => {
    return props.linkedProviders.find((p) => p.provider === providerId);
};

const handleDisconnect = (providerId: string) => {
    if (!props.canDisconnect) {
        alert('Cannot disconnect your last provider');
        return;
    }

    if (confirm(`Are you sure you want to disconnect your ${providerId} account?`)) {
        disconnecting.value = providerId;
        router.delete(route('accounts.disconnect', providerId), {
            preserveScroll: true,
            onFinish: () => {
                disconnecting.value = null;
            },
        });
    }
};

const handleConnect = (connectUrl: string) => {
    window.location.href = connectUrl;
};

</script>

<template>
    <div class="space-y-6">
        <Card v-for="provider in providers" :key="provider.id" class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
            <CardHeader>
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-8 w-8 items-center justify-center">
                            <img :src="provider.logo" :alt="provider.name" class="h-full w-full object-contain" />
                        </div>
                        <div>
                            <CardTitle>{{ provider.name }}</CardTitle>
                            <CardDescription v-if="!isConnected(provider.id)">
                                Connect your {{ provider.name }} account
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
                            <AvatarFallback>{{ isConnected(provider.id)!.nickname?.[0]?.toUpperCase() || '?' }}</AvatarFallback>
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
    </div>
</template>

<style scoped></style>
