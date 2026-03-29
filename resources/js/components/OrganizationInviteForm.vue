<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { useForm } from '@inertiajs/vue3';
import { Mail } from 'lucide-vue-next';

const props = defineProps<{
    organizationId: number;
}>();

const form = useForm({
    email: '',
});

const successMessage = ref('');

const submit = () => {
    successMessage.value = '';
    form.post(route('organizations.invite.store', { organization: props.organizationId }), {
        preserveScroll: true,
        onSuccess: () => {
            successMessage.value = `Invitation sent to ${form.email}`;
            form.reset();
        },
    });
};
</script>

<script lang="ts">
import { ref } from 'vue';
export default { name: 'OrganizationInviteForm' };
</script>

<template>
    <div class="space-y-4">
        <div class="space-y-2">
            <Label for="invite-email">Invite by email</Label>
            <div class="flex gap-2">
                <Input
                    id="invite-email"
                    v-model="form.email"
                    type="email"
                    placeholder="member@example.com"
                    :disabled="form.processing"
                    @keyup.enter="submit"
                />
                <Button :disabled="form.processing || !form.email" @click="submit">
                    <Mail class="mr-2 h-4 w-4" />
                    Send Invite
                </Button>
            </div>
        </div>

        <!-- Success message -->
        <p v-if="successMessage" class="text-sm text-green-600 dark:text-green-400">
            {{ successMessage }}
        </p>

        <!-- Error message -->
        <p v-if="form.errors.email" class="text-sm text-destructive">
            {{ form.errors.email }}
        </p>
    </div>
</template>
