<script setup lang="ts">
import UserRow from '@/components/UserRow.vue';
import { useDateFormatter } from '@/composables/useDateFormatter';
import { useInfiniteScroll } from '@/composables/useInfiniteScroll';
import { useIntersect } from '@/composables/useIntersect';
import { type ReviewsPayload } from '@/types/review';
import { router } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
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
const { formatDate } = useDateFormatter();
const goToProfile = (user: { id: number }) => router.visit(`/users/${user.id}`);

useIntersect(landmark, loadMoreItems, { rootMargin: '0px 0px 150px 0px' });
</script>

<template>
    <div class="space-y-4">
        <div v-if="!items.length" class="text-sm text-gray-500 dark:text-gray-400">No reviews yet.</div>

        <div v-for="review in items" :key="review.id" class="rounded-xl border hover:bg-white/90 dark:hover:bg-white/10 border-gray-200 bg-white/50 p-4 dark:border-white/10 dark:bg-white/5">
            <div @click="goToProfile(review.reviewer)" class="cursor-pointer">
                <UserRow :user="review.reviewer" class="!rounded-none !px-0 !py-0 hover:!bg-transparent dark:hover:!bg-transparent" />
            </div>

            <div class="mt-1 flex items-center gap-1" :title="review.rating + '/5'">
                <Star
                    v-for="i in 5"
                    :key="i"
                    class="h-4 w-4"
                    :class="{
                        'fill-yellow-400 text-yellow-400': i <= review.rating,
                        'text-gray-300 dark:text-gray-600': i > review.rating,
                    }"
                />
            </div>

            <p v-if="review.comment" class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                {{ review.comment }}
            </p>

            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ formatDate(review.created_at) }}
            </div>
        </div>

        <div ref="landmark" aria-hidden="true"></div>
    </div>
</template>
