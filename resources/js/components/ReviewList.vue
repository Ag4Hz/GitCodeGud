<script setup lang="ts">
import { Link } from '@inertiajs/vue3'
import UserRow from '@/components/UserRow.vue'
import { type ReviewsPayload } from '@/types/review'

withDefaults(defineProps<{ reviews: ReviewsPayload }>(), {
    reviews: () => ({
        data: [],
        current_page: 1,
        last_page: 1,
        next_page_url: null,
        prev_page_url: null
    })
})

const goToProfile = (user: { id: number }) => {
    window.location.href = `/users/${user.id}`
}
</script>



<template>
    <div class="space-y-4">
        <div v-if="!reviews.data?.length" class="text-sm text-gray-500 dark:text-gray-400">
            No reviews yet.
        </div>

        <div
            v-for="review in reviews.data"
            :key="review.id"
            class="rounded-xl border border-gray-200 bg-white/50 p-4 dark:border-white/10 dark:bg-white/5"
        >
            <div @click="goToProfile(review.reviewer)" class="cursor-pointer">
                <UserRow
                    :user="review.reviewer"
                    class="!px-0 !py-0 !rounded-none hover:!bg-transparent dark:hover:!bg-transparent"
                />
            </div>

            <p v-if="review.comment" class="mt-2 text-sm text-gray-700 dark:text-gray-300">
                {{ review.comment }}
            </p>

            <div class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                {{ new Date(review.created_at).toLocaleDateString() }}
            </div>
        </div>

        <div v-if="reviews.last_page > 1" class="flex items-center justify-center gap-4 pt-2">
            <Link
                v-if="reviews.prev_page_url"
                :href="reviews.prev_page_url"
                preserve-scroll
                class="text-sm underline hover:no-underline"
            >
                Previous
            </Link>

            <span class="text-sm text-gray-600 dark:text-gray-300">
                Page {{ reviews.current_page }} of {{ reviews.last_page }}
            </span>

            <Link
                v-if="reviews.next_page_url"
                :href="reviews.next_page_url"
                preserve-scroll
                class="text-sm underline hover:no-underline"
            >
                Next
            </Link>
        </div>
    </div>
</template>
