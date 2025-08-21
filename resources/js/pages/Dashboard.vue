<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import { ref } from 'vue';

const search = ref('');

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">

            <input type="text" placeholder="asd.." v-model="search" />

            <NavFooter :items="contactLinks" />
        </div>
    </AppLayout>
</template>

