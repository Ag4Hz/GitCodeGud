<script setup lang="ts">
import { Dialog, DialogContent, DialogDescription, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Users } from 'lucide-vue-next';

import UserRow from '@/components/UserRow.vue';
import { useInfiniteScroll } from '@/composables/useInfiniteScroll';
import { useIntersect } from '@/composables/useIntersect';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps<{
    propName: string;
    title?: string;
    count?: number;
}>();

const { items, loadMoreItems } = useInfiniteScroll(props.propName);

const landmark = ref<HTMLElement | null>(null);
const goToProfile = (user: { id: number }) => router.visit(`/users/${user.id}`);

useIntersect(landmark, loadMoreItems, {
    rootMargin: '0px 0px 150px 0px',
});
</script>

<template>
    <Dialog>
        <DialogTrigger>
            <button class="flex cursor-pointer items-center gap-1 text-[10px] font-medium sm:text-sm md:text-base">
                <Users class="h-4 w-4 text-green-700" />
                <span class="inline-flex items-center gap-1.5">
                    <span>{{ count }}</span>
                    <span>{{ title }}</span>
                </span>
            </button>
        </DialogTrigger>

        <DialogContent
            class="w-full max-w-md rounded-xl border border-white/40 bg-white/30 p-2 text-sm shadow-xl ring-1 ring-black/5 backdrop-blur-xs dark:border-white/10 dark:bg-white/10 dark:text-gray-200"
        >
            <DialogTitle class="mt-2 flex justify-center text-sm font-semibold">{{ title }}</DialogTitle>
            <DialogDescription class="sr-only" />

            <ul class="mt-2 max-h-72 overflow-y-auto rounded-lg p-1">
                <UserRow v-for="user in items" :key="user.id" :user="user" @click="goToProfile(user)" />
                <li ref="landmark" class="h-8"></li>
            </ul>
        </DialogContent>
    </Dialog>
</template>
