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
        link.active
          ? 'border-gray-300 bg-white/70 text-gray-900 dark:border-white/20 dark:bg-white/20 dark:text-gray-100'
          : '',
        link.url ? '' : 'pointer-events-none opacity-50',
      ]"
        >
            <span v-html="link.label" />
        </Link>
    </div>
</template>
