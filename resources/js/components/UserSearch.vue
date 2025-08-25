<script setup lang="ts">
import { Combobox, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/solid';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type User = { id: number; nickname: string; avatar: string; name: string };

const props = defineProps<{
    filters?: { search?: string };
    results?: { data?: User[] };
    placeholder?: string;
}>();

const search = ref(props.filters?.search ?? '');

const selectedUser = ref<User | null>(null);
const users = computed<User[]>(() => props.results?.data ?? []);

watch(selectedUser, (u) => {
    if (u) router.visit(`/users/${u.id}`);
});

watch(
    search,
    () => {
        router.reload({
            data: { search: search.value || null },
            reset: ['results'],
        });
    },
);
</script>

<template>
    <div class="relative mx-auto mt-10 mb-96 w-full max-w-md">
        <Combobox v-model="selectedUser" nullable>
            <div class="relative">
                <MagnifyingGlassIcon
                    class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                    aria-hidden="true"
                />
                <ComboboxInput
                    :value="search"
                    @input="(e: InputEvent) => (search = (e.target as HTMLInputElement).value)"
                    class="w-full rounded-md border px-12 py-2 focus:border-green-600 focus:ring-2 focus:ring-green-600 focus:outline-none"
                    placeholder="Looking for a buddy?"
                />
            </div>

            <ComboboxOptions
                class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 p-2 text-sm shadow-xl ring-1 ring-black/5 dark:border-white/10 dark:bg-white/10 dark:text-gray-200"
            >
                <template v-if="users.length > 0">
                    <ComboboxOption v-for="user in users" :key="user.id" :value="user" as="template" v-slot="{ active }">
                        <li
                            :class="[
                                'flex cursor-pointer items-center space-x-3 rounded-lg px-3 py-2 transition select-none',
                                active ? 'bg-green-600 text-white' : 'text-gray-900 dark:text-gray-100',
                            ]"
                        >
                            <img v-if="user.avatar" :src="user.avatar" alt="avatar" class="h-8 w-8 rounded-full object-cover" />

                            <div class="flex flex-col">
                                <span class="font-medium">{{ user.nickname }}</span>
                                <span class="text-sm text-gray-500 dark:text-gray-300">{{ user.name }}</span>
                            </div>
                        </li>
                    </ComboboxOption>
                </template>
                <template v-else>
                    <div class="px-3 py-2 text-gray-500 dark:text-gray-300">No buddies found</div>
                </template>
            </ComboboxOptions>
        </Combobox>
    </div>
</template>
