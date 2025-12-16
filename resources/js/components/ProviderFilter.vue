<script setup lang="ts">
import { Button } from '@/components/ui/button/index.js';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu/index.js';
import { ProviderOption } from '@/types/bounty';
import { ChevronDown } from 'lucide-vue-next';
import { computed } from 'vue';

interface Props {
    modelValue: string;
    providers: ProviderOption[];
    placeholder?: string;
    dropdownPosition?: 'top' | 'right' | 'bottom' | 'left';
}
const props = withDefaults(defineProps<Props>(), {
    modelValue: '',
    providers: () => [] as ProviderOption[],
    placeholder: 'All providers',
    dropdownPosition: undefined,
});

const emit = defineEmits<{
    'update:modelValue': [value: string];
}>();

const selectedProvider = computed(() => props.modelValue);

const selectedProviderName = computed(() => {
    const provider = props.providers.find((p) => p.value === props.modelValue);
    return provider ? provider.name : '';
});

const handleProviderSelect = (provider: string) => {
    emit('update:modelValue', provider);
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
                    {{ selectedProviderName || placeholder }}
                    <ChevronDown class="h-4 w-4 opacity-50" />
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent
                :side="dropdownPosition"
                class="w-48 rounded-[10px] border border-gray-200 bg-white/40 backdrop-blur-sm backdrop-saturate-150 sm:backdrop-blur-sm dark:border-white/10 dark:bg-white/5 dark:text-gray-200"
            >
                <DropdownMenuItem @click="handleProviderSelect('')">
                    {{ placeholder }}
                </DropdownMenuItem>
                <DropdownMenuItem
                    v-for="provider in providers"
                    :key="provider.value"
                    @click="handleProviderSelect(provider.value)"
                    :class="{ 'light:bg-white/50 dark:bg-white/20': selectedProvider === provider.value }"
                >
                    {{ provider.name }} ({{ provider.count }})
                </DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
