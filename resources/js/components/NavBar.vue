<script setup lang="ts">
import { computed, ref } from 'vue';

const theme = {
    hover: '#386641',
    secondary: '#6A994E',
    primary: '#9EF1DF',
    accent: '#A7C957',
    text: '#F2E8CF',
    border: '#CDD993',
    transparent: 'rgba(0, 0, 0, 0.1)'
}

const loginBtnHovered = ref(false);

const loginBtnStyle = computed(() => ({
    backgroundColor: loginBtnHovered.value ? theme.hover : theme.secondary,
    border: `1px solid ${theme.border}`,
    color: theme.text,
    transition: 'all 200ms ease-in-out'
}));
</script>

<template>
    <nav class="flex items-center justify-between p-4 shadow-lg"
    :style="{ backgroundColor: theme.primary }">
        <!-- Logo Section -->
        <div class="flex items-center space-x-3" @click="$inertia.visit('/')">
            <div class="w-15 h-15 rounded-full flex items-center justify-center">
                <!-- Load in logo from assets directory -->
                <img src="@/../assets/logo.svg" alt="Logo" class="w-15 h-15">
            </div>
            <img src="@/../assets/title.svg" alt="Logo Text" class="h-15 mt-3">
        </div>

        <!-- Login Button Section -->
        <div class="flex items-center space-x-3">
            <button
                class="px-4 py-2 rounded-md mt-3"
                :style="loginBtnStyle"
                @mouseenter="loginBtnHovered = true"
                @mouseleave="loginBtnHovered = false"
                @click="$inertia.visit('/login')"
            >
                Sign in with GitHub
            </button>
        </div>
    </nav>
</template>

<style scoped>

</style>
