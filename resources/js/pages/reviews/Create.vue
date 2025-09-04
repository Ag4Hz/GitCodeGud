
<script setup lang="ts">
import { Head, useForm } from '@inertiajs/vue3';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';

interface Props {
    users: Array<{
        id: number;
        name: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Create Review',
        href: '/reviews/create',
    },
];

const form = useForm({
    reviewee_id: '',
    comment: '',
});

const submit = () => {
    form.post('/reviews');
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Create Review" />
        <form @submit.prevent="submit">
            <div>
                <label for="reviewee_id">User to Review</label>
                <select
                    v-model="form.reviewee_id"
                    name="reviewee_id"
                    id="reviewee_id"
                    required
                >
                    <option value="">Select a user</option>
                    <option
                        v-for="user in props.users"
                        :key="user.id"
                        :value="user.id"
                    >
                        {{ user.name }}
                    </option>
                </select>
            </div>

            <div>
                <label for="comment">Comment</label>
                <textarea
                    v-model="form.comment"
                    name="comment"
                    id="comment"
                    rows="4"
                    required
                    placeholder="Write your review..."
                ></textarea>
            </div>

            <Button type="submit" :disabled="form.processing">
                {{ form.processing ? 'Creating...' : 'Create Review' }}
            </Button>
        </form>
    </AppLayout>
</template>
