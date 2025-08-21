<script setup lang="ts">
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Combobox, ComboboxInput, ComboboxOption, ComboboxOptions } from '@headlessui/vue';
import { MagnifyingGlassIcon } from '@heroicons/vue/24/solid';
import { Head } from '@inertiajs/vue3';

defineProps({
    users: {
        type: Object,
        required: true,
    },
});

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Dashboard', href: '/dashboard' }];
</script>

<template>
    <Head title="Dashboard" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <div class="relative mx-auto w-full max-w-md mt-10 mb-10">
                <Combobox>
                    <div class="relative">
                        <MagnifyingGlassIcon
                            class="pointer-events-none absolute top-1/2 left-4 size-5 -translate-y-1/2 text-gray-400 dark:text-gray-500"
                            aria-hidden="true"
                        />
                        <ComboboxInput
                            class="h-12 w-full rounded-xl border border-gray-200 bg-white pr-4 pl-11 text-base text-gray-900 ring-2 ring-transparent transition outline-none placeholder:text-gray-400 focus:border-green-500 focus:ring-green-200 sm:text-sm dark:border-white/10 dark:bg-gray-900 dark:text-white dark:placeholder:text-gray-500"
                            placeholder="Search..."
                        />
                    </div>

                    <ComboboxOptions
                        class="absolute z-50 mt-2 max-h-72 w-full overflow-y-auto rounded-xl border border-gray-200 bg-white p-2 text-sm shadow-xl ring-1 ring-black/5 dark:border-white/10 dark:bg-gray-900 dark:text-gray-200"
                    >
                        <ComboboxOption v-for="user in users.data" :key="user.id" as="template" v-slot="{ active }">
                            <li
                                :class="[
                                    'cursor-default rounded-lg px-3 py-2 transition select-none',
                                    active ? 'bg-green-600 text-white' : 'text-gray-900 dark:text-gray-100',
                                ]"
                            >
                                {{ user.name }}
                            </li>
                        </ComboboxOption>
                    </ComboboxOptions>
                </Combobox>
            </div>

            <NavFooter :items="contactLinks" />
        </div>
    </AppLayout>
</template>
