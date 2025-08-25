<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import UserSearch from '@/components/UserSearch.vue';

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <div class="relative mx-auto mt-10 mb-96 w-full max-w-md">
                <UserSearch/>
            </div>

            <NavFooter :items="contactLinks" />
        </div>
    </AppLayout>
</template>
