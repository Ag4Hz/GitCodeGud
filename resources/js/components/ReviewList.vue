<script setup lang="ts">
import UserRow from '@/components/UserRow.vue';
import { useInfiniteScroll } from '@/composables/useInfiniteScroll';
import { useIntersect } from '@/composables/useIntersect';
import { type ReviewsPayload } from '@/types/review';
import { router } from '@inertiajs/vue3';
import { ref } from 'vue';

withDefaults(defineProps<{ reviews: ReviewsPayload }>(), {
    reviews: () => ({
        data: [],
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null,
    }),
});

const { items, loadMoreItems } = useInfiniteScroll('reviews');
const landmark = ref<HTMLElement | null>(null);
const goToProfile = (user: { id: number }) => router.visit(`/users/${user.id}`);

useIntersect(landmark, loadMoreItems, { rootMargin: '0px 0px 150px 0px' });
</script>

<template>
    <div class="space-y-4">
        <div v-if="!items.length" class="text-sm text-gray-500 dark:text-gray-400">No reviews yet.</div>

        <div v-for="review in items" :key="review.id" class="rounded-xl border border-gray-200 bg-white/50 p-4 dark:border-white/10 dark:bg-white/5">
            <div @click="goToProfile(review.reviewer)" class="cursor-pointer">
                <UserRow :user="review.reviewer" class="!rounded-none !px-0 !py-0 hover:!bg-transparent dark:hover:!bg-transparent" />
            </div>

            <p v-if="review.comment" class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                {{ review.comment }}
            </p>

            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ new Date(review.created_at).toLocaleDateString() }}
            </div>
        </div>

        <div ref="landmark" aria-hidden="true"></div>
    </div>
</template>
