<script setup lang="ts">
import { Button } from '@/components/ui/button/index.js';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu/index.js';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    modelValue: string;
    languages: string[];
    placeholder?: string;
}
const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    languages: () => [],
    placeholder: 'All Languages',
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selectedLanguage = computed(() => props.modelValue);
const handleLanguageSelect = (language: string) => {
    emit('update:modelValue', language);
};
</script>

<template>
    <div>
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button
                    variant="outline"
                    class="w-full rounded-[10px] border border-gray-200 bg-white/40 text-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-500 dark:hover:bg-white/10"
                >
                    {{ selectedLanguage || placeholder }}
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                class="w-48 rounded-[10px] border border-gray-200 bg-white/40 backdrop-blur-sm backdrop-saturate-150 sm:backdrop-blur-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
            >
                <DropdownMenuItem @click="handleLanguageSelect('')" :class="{ '': !selectedLanguage }">
                    {{ placeholder }}
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="language in languages"
                    :key="language"
                    @click="handleLanguageSelect(language)"
                    :class="{ 'bg-accent': selectedLanguage === language }"
                >
                    {{ language }}
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
