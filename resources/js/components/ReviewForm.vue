<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useForm } from '@inertiajs/vue3';
import { Star } from 'lucide-vue-next';
import { ref } from 'vue';

const props = defineProps<{
    revieweeId: number;
}>();

const form = useForm({
    reviewee_id: props.revieweeId,
    comment: '',
    rating: 0,
});

const submit = () => {
    form.post(route('reviews.store'), {
        onSuccess: () => {
            form.reset('comment', 'rating');
            hoverRating.value = 0;
        },
    });
};

const hoverRating = ref(0);
const setRating = (value: number) => {
    form.rating = value;
};
</script>

<template>
    <form @submit.prevent="submit" class="space-y-3">
        <div>
            <div class="mb-4 flex items-center gap-1">
                <template v-for="star in 5" :key="star">
                    <Star
                        @click="setRating(star)"
                        @mouseover="hoverRating = star"
                        @mouseleave="hoverRating = 0"
                        class="h-7 w-7 cursor-pointer transition-colors"
                        :class="[hoverRating >= star || form.rating >= star ? 'fill-yellow-400 text-yellow-400' : 'text-gray-300 dark:text-gray-600']"
                    />
                </template>
            </div>

            <label for="comment" class="block text-sm font-medium">Leave a review</label>
            <textarea
                v-model="form.comment"
                id="comment"
                name="comment"
                rows="4"
                required
                placeholder="Write your review..."
                class="mt-1 block w-full rounded-md border border-gray-300 p-2"
            />
            <p v-if="form.errors.comment" class="mt-1 text-sm text-red-600">
                {{ form.errors.comment }}
            </p>
            <p v-if="form.errors.reviewee_id" class="mt-1 text-sm text-red-600">
                {{ form.errors.reviewee_id }}
            </p>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Creating...' : 'Create Review' }}
        </Button>
    </form>
</template>
