<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

const props = defineProps<{
    links: PaginationLink[];
}>();
</script>

<template>
    <div class="mt-6 flex justify-center space-x-1">
        <Link
            v-for="(link, index) in props.links"
            :key="index"
            :href="link?.url ?? '#'"
            class="rounded border px-3 py-1 text-sm transition-colors"
            :class="[
                'border-gray-300 dark:border-white/10',
                link.active ? 'bg-green-700 text-white' : 'bg-white text-gray-700 dark:bg-white/5 dark:text-gray-200',
                link.active ? '' : 'hover:bg-gray-100 dark:hover:bg-white/10',
                link.url ? '' : 'pointer-events-none opacity-50',
            ]"
        >
            <span v-html="link.label"></span>
        </Link>
    </div>
</template>
