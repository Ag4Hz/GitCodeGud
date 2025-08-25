<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import UserSearch from '@/components/UserSearch.vue';

type User = { id: number; nickname: string; avatar: string; name: string };

type DashboardProps = AppPageProps<{
    filters: { search?: string };
    results?: { data?: User[] };
}>;

const props = defineProps<DashboardProps>();

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="relative mx-auto mt-10 mb-96 w-full max-w-md">
            <UserSearch
                :filters="props.filters"
                :results="props.results ?? { data: [] }"
                placeholder="Looking for a buddy?"
            />
        </div>

        <NavFooter :items="contactLinks" />
    </AppLayout>
</template>
