<script setup lang="ts">
import UserRow from '@/components/UserRow.vue';
import { Combobox, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/solid';
import { router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { PerfectScrollbar } from 'vue3-perfect-scrollbar';

type User = { id: number; nickname: string; avatar: string; name: string };

const props = withDefaults(
    defineProps<{
        filters?: { search?: string };
        results?: { data?: User[] };
        placeholder?: string;
    }>(),
    {
        filters: () => ({ search: '' }),
        results: () => ({ data: [] }),
        placeholder: 'Looking for a buddy?',
    },
);

const search = ref(props.filters?.search ?? '');

const selectedUser = ref<User | null>(null);
const users = computed<User[]>(() => props.results?.data ?? []);

watch(selectedUser, (u) => {
    if (u) router.visit(`/users/${u.id}`);
});

watch(search, () => {
    router.reload({
        data: { search_user: search.value || null },
        reset: ['users'],
    });
});
</script>

<template>
    <div class="relative mt-4 mb-4 w-full">
        <Combobox v-model="selectedUser" nullable>
            <div class="relative">
                <MagnifyingGlassIcon
                    class="pointer-events-none absolute top-1/2 left-4 z-10 h-5 w-5 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                    aria-hidden="true"
                />
                <ComboboxInput
                    :value="search"
                    @input="(e: InputEvent) => (search = (e.target as HTMLInputElement).value)"
                    class="w-full rounded-2xl border border-gray-200 bg-white/40 py-2 pr-3 pl-12 text-sm backdrop-blur-xl backdrop-saturate-150 focus:border-green-600 focus:ring-2 focus:ring-green-600 focus:outline-none sm:py-3 sm:pr-4 sm:pl-12 sm:text-base sm:backdrop-blur-2xl md:py-4 md:pr-6 md:pl-14 md:text-base dark:border-white/10 dark:bg-white/10 dark:text-gray-100"
                    placeholder="Looking for a buddy?"
                />
            </div>

            <ComboboxOptions
                class="p-x2 absolute right-0 left-0 z-50 mt-2 max-h-72 w-full rounded-xl border border-gray-200 bg-white/40 text-sm shadow-xl ring-1 ring-black/5 backdrop-blur-xl backdrop-saturate-150 sm:backdrop-blur-2xl dark:border-white/10 dark:bg-white/10 dark:text-gray-200"
            >
                <template v-if="users.length > 0">
                    <PerfectScrollbar class="max-h-72 w-full rounded-xl" :options="{ suppressScrollX: true }">
                    <ComboboxOption v-for="user in users" :key="user.id" :value="user" as="template" v-slot="{ active }">
                        <UserRow :user="user" :active="active" />
                    </ComboboxOption>
                    </PerfectScrollbar>
                </template>

                <div
                    v-else
                    class="flex min-h-[44px] items-center px-3 py-2 text-sm text-gray-500 sm:px-4 sm:py-3 sm:text-base md:px-6 md:py-4 md:text-base dark:text-gray-300"
                >
                    No buddies found
                </div>
            </ComboboxOptions>
        </Combobox>
    </div>
</template>
