<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { Button } from '@/components/ui/button'

const props = defineProps<{
revieweeId: number
}>()

const form = useForm({
reviewee_id: props.revieweeId,
comment: '',
})

const submit = () => {
    form.post(route('reviews.store'), {
        onSuccess: () => form.reset('comment'),
    })
}
</script>

<template>
    <form @submit.prevent="submit" class="space-y-3">
        <div>
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
            <p v-if="form.errors.comment" class="text-sm text-red-600 mt-1">
                {{ form.errors.comment }}
            </p>
            <p v-if="form.errors.reviewee_id" class="text-sm text-red-600 mt-1">
                {{ form.errors.reviewee_id }}
            </p>
        </div>

        <Button type="submit" :disabled="form.processing">
            {{ form.processing ? 'Creating...' : 'Create Review' }}
        </Button>
    </form>
</template>
