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
    <div class="sm:w-48">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline" class="w-full justify-between">
                    {{ selectedLanguage || placeholder }}
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent class="w-48">
                <DropdownMenuItem @click="handleLanguageSelect('')" :class="{ 'bg-accent': !selectedLanguage }">
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
