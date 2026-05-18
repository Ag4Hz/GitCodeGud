<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
</script>

<template>
    <div class="flex aspect-square size-10 lg:size-15 items-center justify-center rounded-md p-1 ...">
        <AppLogoIcon class="size-4 lg:size-5 fill-current text-white dark:text-black" />
    </div>
    <div class="ml-1 grid flex-1 text-left text-sm">
        <img src="@/../assets/title.svg" alt="Logo title" class="mt-3 h-7 lg:h-10 w-auto" />
    </div>
</template>
