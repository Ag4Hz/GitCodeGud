<script setup lang="ts">
import { Link } from '@inertiajs/vue3'

defineProps<{
    reviews: {
        data: any[],
        current_page: number,
        last_page: number,
        next_page_url: string | null,
        prev_page_url: string | null
    }
}>()
</script>

<template>
    <div>
        <div v-if="!reviews.data.length">No reviews yet.</div>

        <ul v-else>
            <li v-for="r in reviews.data" :key="r.id">
                <div>From: {{ r.reviewer?.name ?? 'Unknown' }}</div>
                <div>Rating: {{ r.rating }}</div>
                <div>Comment: {{ r.comment || '(no comment)' }}</div>
                <div>Date: {{ new Date(r.created_at).toLocaleString() }}</div>
                <hr />
            </li>
        </ul>

        <div class="mt-4 flex gap-4">
            <Link v-if="reviews.prev_page_url" :href="reviews.prev_page_url" preserve-scroll>Prev</Link>
            <span>Page {{ reviews.current_page }} of {{ reviews.last_page }}</span>
            <Link v-if="reviews.next_page_url" :href="reviews.next_page_url" preserve-scroll>Next</Link>
        </div>
    </div>
</template>
