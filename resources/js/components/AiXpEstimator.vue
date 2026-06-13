<template>
    <div class="space-y-3">
        <button
            type="button"
            :disabled="!issueTitle || loading"
            class="inline-flex items-center gap-2 rounded-md border border-gray-200 bg-white/40 px-3 py-1.5 text-sm font-medium shadow-sm backdrop-blur-xl transition-colors hover:bg-gray-100/60 disabled:cursor-not-allowed disabled:opacity-40 dark:border-white/10 dark:bg-white/5 dark:text-gray-100 dark:hover:bg-white/10"
            @click="runEstimate"
        >
            <svg
                v-if="loading"
                class="h-3.5 w-3.5 animate-spin text-green-600"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
            >
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" />
                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v8H4z" />
            </svg>
            <span v-else class="text-green-600">✦</span>
            AI Estimate
        </button>

        <div
            v-if="result"
            class="rounded-2xl border border-gray-200 bg-white/40 p-4 backdrop-blur-xl dark:border-white/10 dark:bg-white/5"
        >
            <div class="mb-3 flex items-center justify-between">
                <span
                    class="rounded-full px-2.5 py-0.5 text-xs font-semibold capitalize"
                    :class="{
                        'bg-gray-100 text-gray-600 dark:bg-white/10 dark:text-gray-300': result.complexity === 'trivial',
                        'bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400': result.complexity === 'easy',
                        'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400': result.complexity === 'medium',
                        'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400': result.complexity === 'hard',
                        'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400': result.complexity === 'expert',
                    }"
                >{{ result.complexity }}</span>
                <button
                    type="button"
                    class="text-xs text-gray-400 hover:text-gray-600 dark:text-gray-500 dark:hover:text-gray-300"
                    @click="result = null"
                >✕</button>
            </div>

            <div class="mb-3 flex items-center gap-2">
                <span class="w-8 text-right text-xs text-muted-foreground">{{ result.min }}</span>
                <div class="relative h-1.5 flex-1 rounded-full bg-gray-200 dark:bg-white/10">
                    <div
                        class="absolute left-0 top-0 h-full rounded-full bg-green-600 transition-all duration-300"
                        :style="{ width: fillPct + '%' }"
                    />
                    <span
                        class="absolute -top-5 rounded border border-gray-200 bg-white px-1.5 py-px text-xs font-semibold text-green-700 -translate-x-1/2 dark:border-white/10 dark:bg-white/10 dark:text-green-400"
                        :style="{ left: fillPct + '%' }"
                    >{{ result.suggested }} XP</span>
                </div>
                <span class="w-8 text-xs text-muted-foreground">{{ result.max }}</span>
            </div>

            <p class="mb-3 text-xs text-gray-500 dark:text-gray-400">{{ result.reasoning }}</p>

            <div class="flex flex-wrap gap-2">
                <button
                    type="button"
                    class="rounded-md border border-gray-200 bg-white/40 px-3 py-1.5 text-xs font-medium shadow-sm backdrop-blur-xl hover:bg-gray-100/60 dark:border-white/10 dark:bg-white/5 dark:text-white dark:hover:bg-white/10"
                    @click="apply(result.suggested)"
                >Apply {{ result.suggested }} XP</button>
                <button
                    v-if="result.min !== result.suggested"
                    type="button"
                    class="rounded-md border border-gray-200 bg-white/40 px-3 py-1.5 text-xs text-gray-600 shadow-sm backdrop-blur-xl hover:bg-gray-100/60 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                    @click="apply(result.min)"
                >{{ result.min }}</button>
                <button
                    v-if="result.max !== result.suggested"
                    type="button"
                    class="rounded-md border border-gray-200 bg-white/40 px-3 py-1.5 text-xs text-gray-600 shadow-sm backdrop-blur-xl hover:bg-gray-100/60 dark:border-white/10 dark:bg-white/5 dark:text-gray-300 dark:hover:bg-white/10"
                    @click="apply(result.max)"
                >{{ result.max }}</button>
            </div>
        </div>

        <p v-if="error" class="text-xs text-red-600 dark:text-red-400">{{ error }}</p>
    </div>
</template>

<script setup lang="ts">
import { computed, ref, watch } from 'vue';

interface EstimateResult {
    min: number;
    max: number;
    suggested: number;
    complexity: 'trivial' | 'easy' | 'medium' | 'hard' | 'expert';
    reasoning: string;
}

const props = defineProps({
    issueTitle: { type: String, default: '' },
    issueBody: { type: String, default: '' },
    issueUrl: { type: String, default: '' },
    repoFullName: { type: String, default: '' },
    provider: { type: String, default: 'github' },
    modelValue: { type: [Number, String], default: '' },
});

const emit = defineEmits(['update:modelValue']);

const loading = ref(false);
const result = ref<EstimateResult | null>(null);
const error = ref('');

const fillPct = computed(() => {
    if (!result.value) return 50;
    const { min, max, suggested } = result.value;
    return Math.round(((suggested - min) / Math.max(max - min, 1)) * 80 + 10);
});

function xsrfToken(): string {
    const match = document.cookie.match(/XSRF-TOKEN=([^;]+)/);
    return match ? decodeURIComponent(match[1]) : '';
}

async function runEstimate() {
    loading.value = true;
    error.value = '';
    result.value = null;

    try {
        const resp = await fetch('/api/bounties/estimate-xp', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-XSRF-TOKEN': xsrfToken(),
                Accept: 'application/json',
            },
            body: JSON.stringify({
                issue_title: props.issueTitle,
                issue_body: props.issueBody,
                issue_url: props.issueUrl,
                repo_full_name: props.repoFullName,
                provider: props.provider,
            }),
        });

        if (!resp.ok) {
            const body = await resp.json().catch(() => ({}));
            error.value = body.message || 'AI estimation failed. Please try again later.';
            return;
        }

        result.value = await resp.json();
    } catch {
        error.value = 'AI estimation failed. Please try again later.';
    } finally {
        loading.value = false;
    }
}

function apply(val: number) {
    emit('update:modelValue', val);
    result.value = null;
}

watch(
    () => props.issueTitle,
    () => {
        result.value = null;
        error.value = '';
    },
);
</script>
