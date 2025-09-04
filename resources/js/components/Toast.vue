<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useToast } from '@/composables/useToast';

const { toasts, removeToast } = useToast();
</script>

<template>
    <div class="fixed top-20 left-1/2 z-50 flex -translate-x-1/2 flex-col content-center gap-2">
        <div
            v-for="toast in toasts"
            :key="toast.id"
            class="flex items-center justify-between rounded-lg px-4 py-2 text-white shadow-md"
            :class="{
                'bg-green-500': toast.type === 'success',
                'bg-red-500': toast.type === 'error',
                'bg-blue-500': toast.type === 'info',
                'bg-yellow-500': toast.type === 'warning',
            }"
        >
            <span>{{ toast.message }}</span>
            <Button class="ml-4 bg-transparent text-white" @click="removeToast(toast.id)">✕</Button>
        </div>
    </div>
</template>
