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
            class="rounded border px-3 py-1 text-sm"
            :class="[
                link.active ? 'bg-blue-600 text-white' : 'bg-white text-gray-700',
                link.url ? '' : 'pointer-events-none opacity-50',
                'hover:bg-gray-100',
            ]"
        >
            <span v-html="link.label"></span>
        </Link>
    </div>
</template>
