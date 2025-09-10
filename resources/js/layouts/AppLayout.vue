<script setup lang="ts">
import Toast from '@/components/Toast.vue';
import AppLayout from '@/layouts/app/AppHeaderLayout.vue';
import type { BreadcrumbItemType } from '@/types';
import BugBackground from '@/components/BugBackground.vue';

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
