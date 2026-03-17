<script setup lang="ts">
import { Button } from '@/components/ui/button';

const props = defineProps<{
    syncingProvider: string | null;
    connectedProviders: string[];
}>();

const emit = defineEmits<{
    (e: 'sync', provider: 'github' | 'gitlab' | 'bitbucket'): void;
}>();

const allProviders = [
    {
        key: 'github',
        label: 'GitHub',
        svg: `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20"><path
            fill-rule="evenodd"
            d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
            clip-rule="evenodd"
        ></path></svg>`,
    },
    {
        key: 'gitlab',
        label: 'GitLab',
        svg: `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M23.955 13.587l-1.342-4.135-2.664-8.189a.455.455 0 0 0-.867 0L16.418 9.45H7.582L4.919 1.263a.455.455 0 0 0-.867 0L1.388 9.452.045 13.587a.924.924 0 0 0 .331 1.023L12 23.054l11.624-8.443a.92.92 0 0 0 .331-1.024"></path></svg>`,
    },
     {
         key: 'bitbucket',
         label: 'Bitbucket',
         svg: `<svg class="h-4 w-4" fill="currentColor" viewBox="0 0 24 24"><path d="M.778 1.211a.768.768 0 0 0-.768.892l3.263 19.811c.084.5.515.868 1.022.873H19.95a.772.772 0 0 0 .77-.646l3.27-20.03a.768.768 0 0 0-.768-.891zM14.52 15.528H9.522L8.17 8.464h7.561z"></path></svg>`,
     },
];

const isConnected = (key: string) => {
    return props.connectedProviders?.includes(key);
};

const isSyncing = (key: string) => props.syncingProvider === key;

const buttonLabel = (providerKey: string, providerLabel: string) => {
    if (isSyncing(providerKey)) {
        return `Syncing...`;
    }

    return `Sync from ${providerLabel}`;
};

const handleClick = (key: string) => {
    if (!isConnected(key) || isSyncing(key)) return;
    emit('sync', key as 'github' | 'gitlab' | 'bitbucket');
};
</script>

<template>
    <div class="flex flex-wrap gap-2">
        <Button
            v-for="provider in allProviders"
            :key="provider.key"
            type="button"
            class="inline-flex items-center justify-center gap-2 w-[170px] text-sm"
            variant="button"
            :disabled="!isConnected(provider.key) || isSyncing(provider.key)"
            @click="handleClick(provider.key)"
        >
            <span v-html="provider.svg" />
            <span>{{ buttonLabel(provider.key, provider.label) }}</span>
        </Button>
    </div>
</template>

