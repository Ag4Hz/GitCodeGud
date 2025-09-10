<script setup lang="ts">
import BugBackground from '@/components/BugBackground.vue';
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';

interface Props {
    breadcrumbs?: BreadcrumbItemType[];
}

withDefaults(defineProps<Props>(), {
    breadcrumbs: () => [],
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <BugBackground />
        <slot />
        <Toast />
    </AppLayout>
</template>
