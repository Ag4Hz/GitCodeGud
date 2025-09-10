<script setup lang="ts">
import type { HTMLAttributes } from 'vue'
import { cn } from '@/lib/utils'
import { useVModel } from '@vueuse/core'
import { ComboboxInput } from '@headlessui/vue';

const props = defineProps<{
  defaultValue?: string | number
  modelValue?: string | number
  class?: HTMLAttributes['class']
}>()

const emits = defineEmits<{
  (e: 'update:modelValue', payload: string | number): void
}>()

const modelValue = useVModel(props, 'modelValue', emits, {
  passive: true,
  defaultValue: props.defaultValue,
})
</script>

<template>
  <input
    v-model="modelValue"
    data-slot="input"
    :class="cn(
        'w-full rounded-2xl border bg-white/40 border-gray-200 pr-3 pl-12 text-sm py-2 lg:py-4 md:py-4 sm:py-3  sm:pr-4 sm:pl-12 sm:text-base sm:backdrop-blur-2xl md:pr-6 md:pl-14 md:text-base dark:border-white/10 dark:bg-white/5 dark:text-gray-100',
       'focus-visible:outline-none',
        'focus-visible:border-green-500 focus-visible:ring-2 focus-visible:ring-green-500/50',
        'dark:focus-visible:border-ring dark:focus-visible:ring-ring/50',
        'aria-invalid:border-destructive aria-invalid:ring-2 aria-invalid:ring-destructive/40',
        'dark:aria-invalid:border-destructive dark:aria-invalid:ring-destructive/40',
      props.class,
    )"
  >
</template>
class=""
