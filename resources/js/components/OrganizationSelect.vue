<script setup lang="ts">
import { Building2, Check, ChevronDown } from 'lucide-vue-next';
import { onMounted, onUnmounted, ref } from 'vue';

interface Organization {
    id: number;
    name: string;
}

interface Props {
    modelValue: number | null;
    organizations: Organization[];
}

const props = defineProps<Props>();
const emit = defineEmits<{ 'update:modelValue': [value: number | null] }>();

const isOpen = ref(false);
const dropdownRef = ref<HTMLElement | null>(null);

const selectedOrg = () => props.organizations.find((o) => o.id === props.modelValue) ?? null;

const select = (value: number | null) => {
    emit('update:modelValue', value);
    isOpen.value = false;
};

const handleClickOutside = (e: MouseEvent) => {
    if (dropdownRef.value && !dropdownRef.value.contains(e.target as Node)) {
        isOpen.value = false;
    }
};

onMounted(() => document.addEventListener('mousedown', handleClickOutside));
onUnmounted(() => document.removeEventListener('mousedown', handleClickOutside));
</script>

<template>
    <div ref="dropdownRef" class="relative">
        <!-- Trigger -->
        <button
            type="button"
            class="flex h-10 w-full items-center justify-between rounded-2xl border border-gray-200 bg-white/40 px-3 py-2 text-sm font-medium text-gray-900 shadow-sm backdrop-blur-xl backdrop-saturate-150 dark:border-white/10 dark:bg-white/10 dark:text-gray-100"
            @click="isOpen = !isOpen"
        >
            <div class="flex items-center gap-2">
                <Building2 class="h-4 w-4 text-muted-foreground" />
                <span>{{ selectedOrg() ? selectedOrg()!.name : 'Public (no organization)' }}</span>
            </div>
            <ChevronDown class="h-4 w-4 text-gray-400 transition-transform" :class="{ 'rotate-180': isOpen }" />
        </button>

        <!-- Dropdown -->
        <div
            v-if="isOpen"
            class="absolute z-50 mt-1 w-full rounded-2xl border border-gray-200 bg-white/90 text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 dark:border-white/10 dark:bg-gray-900/90"
        >
            <!-- Public option -->
            <button
                type="button"
                class="flex w-full items-center gap-3 rounded-t-2xl px-4 py-3 text-left text-gray-900 hover:bg-white/90 dark:text-gray-100 dark:hover:bg-white/10"
                @click="select(null)"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-100 dark:bg-white/10">
                    <Building2 class="h-4 w-4 text-muted-foreground" />
                </div>
                <div class="flex-1">
                    <p class="font-medium">Public (no organization)</p>
                    <p class="text-xs text-muted-foreground">Visible to everyone</p>
                </div>
                <Check v-if="modelValue === null" class="h-4 w-4 text-green-500" />
            </button>

            <!-- Org options -->
            <button
                v-for="org in organizations"
                :key="org.id"
                type="button"
                class="flex w-full items-center gap-3 px-4 py-3 text-left text-gray-900 last:rounded-b-2xl hover:bg-white/90 dark:text-gray-100 dark:hover:bg-white/10"
                @click="select(org.id)"
            >
                <div class="flex h-8 w-8 items-center justify-center rounded-lg bg-purple-500/20">
                    <Building2 class="h-4 w-4 text-purple-400" />
                </div>
                <div class="flex-1">
                    <p class="font-medium">{{ org.name }}</p>
                    <p class="text-xs text-muted-foreground">Only org members can see this</p>
                </div>
                <Check v-if="modelValue === org.id" class="h-4 w-4 text-green-500" />
            </button>
        </div>
    </div>
</template>
