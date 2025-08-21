<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import { Head, router } from '@inertiajs/vue3';
import { ref } from 'vue';
import type { BreadcrumbItem } from '@/types';

type UserRow = { id: number; name: string; nickname?: string | null };

const props = defineProps<{
    users: UserRow[];
    filters?: { q?: string };
}>();

const query = ref(props.filters?.q ?? '');

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];

const submitSearch = () => {
    router.get(
        '/dashboard',
        { q: query.value },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['users', 'filters'],
        },
    );
};
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
            <form class="flex items-center gap-2" @submit.prevent="submitSearch">
                <input v-model="query" type="search" placeholder="Search..." class="w-full rounded-lg border px-3 py-2 md:w-96" />
                <button type="submit" class="rounded-lg border px-4 py-2">Search</button>
            </form>

            <div v-if="props.users.length" class="divide-y rounded-lg border">
                <div v-for="u in props.users" :key="u.id" class="flex items-center justify-between px-4 py-2">
                    <div>
                        <div class="font-medium">{{ u.nickname }}</div>
                        <div v-if="u.name" class="text-sm opacity-70">{{ u.name }}</div>
                    </div>
                </div>
            </div>
            <p v-else class="opacity-70">No matches.</p>

            <NavFooter :items="contactLinks" />
        </div>
    </AppLayout>
</template>
