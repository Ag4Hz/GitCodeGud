<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Leaderboard',
        href: '/leaderboard',
    },
];
</script>

<template>
    <Head title="Leaderboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>alma</div>
    </AppLayout>
</template>
