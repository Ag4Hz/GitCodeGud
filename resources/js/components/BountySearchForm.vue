<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { router } from '@inertiajs/vue3';
import { AlertCircle, Info, Loader2, MessageCircle, Search, X } from 'lucide-vue-next';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
interface Repository {
    id: number;
    name: string;
    full_name: string;
    description: string;
    url: string;
    language: string;
    updated_at: string;
    open_issues_count: number;
}

interface Issue {
    id: number;
    number: number;
    title: string;
    body: string;
    url: string;
    state: string;
    created_at: string;
    updated_at: string;
    user: {
        login: string;
        avatar_url: string;
    };
    labels: Array<{
        name: string;
        color: string;
    }>;
    comments: number;
}

interface Props {
    form: any;
    repositories?: Repository[];
    issues?: Issue[];
    repositoryQuery?: string;
    selectedRepository?: string;
}

const props = withDefaults(defineProps<Props>(), {
    repositories: () => [],
    issues: () => [],
    repositoryQuery: '',
    selectedRepository: '',
});

const emit = defineEmits<{
    updateForm: [field: string, value: any];
}>();

const clearForm = () => {
    selectedRepo.value = null;
    selectedIssue.value = null;
    repositorySearchQuery.value = '';
    issueSearchQuery.value = '';
    showRepositoryDropdown.value = false;
    showIssueDropdown.value = false;
};

defineExpose({
    clearForm,
});

const repositorySearchQuery = ref(props.repositoryQuery);
const issueSearchQuery = ref('');
const selectedRepo = ref<Repository | null>(null);
const selectedIssue = ref<Issue | null>(null);
const repositoryLoading = ref(false);
const issueLoading = ref(false);
const showRepositoryDropdown = ref(false);
const showIssueDropdown = ref(false);

let repositorySearchTimeout: number | null = null;

if (props.selectedRepository && props.repositories.length > 0) {
    const found = props.repositories.find((repo) => repo.full_name === props.selectedRepository);
    if (found) {
        selectedRepo.value = found;
        repositorySearchQuery.value = found.name;
    }
}

const filteredIssues = computed(() => {
    if (!issueSearchQuery.value.trim()) return props.issues;
    const query = issueSearchQuery.value.toLowerCase().trim();
    return props.issues.filter(
        (issue) =>
            issue.title.toLowerCase().includes(query) ||
            issue.number.toString().includes(query) ||
            (issue.body && issue.body.toLowerCase().includes(query)) ||
            issue.user.login.toLowerCase().includes(query),
    );
});

const updateFormField = (field: string, value: any) => {
    emit('updateForm', field, value);
};

const debouncedSearchRepositories = () => {
    if (repositorySearchTimeout) {
        clearTimeout(repositorySearchTimeout);
    }

    repositorySearchTimeout = setTimeout(() => {
        searchRepositories();
    }, 500);
};

const handleRepositoryFocus = () => {
    if (props.repositories.length > 0) {
        showRepositoryDropdown.value = true;
    }
};

const searchRepositories = () => {
    if (!repositorySearchQuery.value.trim()) {
        showRepositoryDropdown.value = false;
        return;
    }

    showRepositoryDropdown.value = true;
    repositoryLoading.value = true;

    router.visit(route('bounty.search-repositories'), {
        method: 'get',
        data: {
            query: repositorySearchQuery.value,
        },
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            repositoryLoading.value = false;
            showRepositoryDropdown.value = true;
        },
    });
};

const searchIssues = () => {
    if (!selectedRepo.value) return;

    issueLoading.value = true;
    const [owner, repo] = selectedRepo.value.full_name.split('/');

    router.visit(route('bounty.repository-issues', { owner, repo }), {
        method: 'get',
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            issueLoading.value = false;
            showIssueDropdown.value = true;
        },
    });
};

const selectRepository = (repository: Repository) => {
    selectedRepo.value = repository;
    repositorySearchQuery.value = repository.name;
    showRepositoryDropdown.value = false;

    clearIssue();
    updateFormField('repository_full_name', repository.full_name);
    searchIssues();
};

const selectIssue = (issue: Issue) => {
    selectedIssue.value = issue;
    issueSearchQuery.value = `#${issue.number} - ${issue.title}`;
    showIssueDropdown.value = false;
    updateFormField('issue_number', issue.number);

    if (!props.form.title) {
        updateFormField('title', `Fix: ${issue.title}`);
    }

    if (!props.form.description && issue.body) {
        const truncatedBody = issue.body.length > 500 ? issue.body.substring(0, 500) + '...' : issue.body;
        updateFormField('description', `Related to GitHub issue: ${issue.url}\n\n${truncatedBody}`);
    }
};

const clearRepository = () => {
    selectedRepo.value = null;
    repositorySearchQuery.value = '';
    clearIssue();
    updateFormField('repository_full_name', '');
    showRepositoryDropdown.value = false;

    // Clear search results
    router.visit(route('bounties.create'), {
        preserveState: true,
        preserveScroll: true,
        replace: true,
    });
};

const clearIssue = () => {
    selectedIssue.value = null;
    issueSearchQuery.value = '';
    updateFormField('issue_number', '');
    showIssueDropdown.value = false;
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
    });
};

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement;
    if (!target.closest('.relative')) {
        showRepositoryDropdown.value = false;
        showIssueDropdown.value = false;
    }
};

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside);
    if (repositorySearchTimeout) {
        clearTimeout(repositorySearchTimeout);
    }
});

watch(
    () => props.form.errors,
    (errors) => {
        if (errors.repository_full_name) {
            showRepositoryDropdown.value = false;
        }
        if (errors.issue_number) {
            showIssueDropdown.value = false;
        }
    },
    { deep: true },
);

// Watch for new repositories from backend
watch(
    () => props.repositories,
    (newRepos) => {
        if (newRepos.length > 0 && repositorySearchQuery.value.trim()) {
            showRepositoryDropdown.value = true;
        }
    },
    { immediate: true },
);

// Watch for new issues from backend
watch(
    () => props.issues,
    (newIssues) => {
        if (newIssues.length > 0 && selectedRepo.value) {
            showIssueDropdown.value = true;
        }
    },
    { immediate: true },
);
</script>

<template>
    <div class="space-y-6">
        <!-- Repository Selection -->
        <div class="space-y-2">
            <Label>Select Repository *</Label>
            <div class="relative">
                <div class="relative">
                    <Input
                        v-model="repositorySearchQuery"
                        placeholder="Type to search your repositories..."
                        :class="[form.errors.repository_full_name && 'border-red-500 focus-visible:ring-red-500']"
                        @input="debouncedSearchRepositories"
                        @focus="handleRepositoryFocus"
                    />
                    <Search class="absolute top-1/2 left-4 z-10 h-5 w-5 -translate-y-1/2 text-gray-400 dark:text-gray-500" />
                </div>

                <!-- Repository Dropdown -->
                <div
                    v-if="showRepositoryDropdown && (props.repositories.length > 0 || repositoryLoading)"
                    class="bg-white-10 mt-[2px] rounded-2xl border text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 sm:backdrop-blur-2xl dark:bg-white/10"
                >
                    <div v-if="repositoryLoading" class="p-3 text-center">
                        <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                        <p class="text-sm text-muted-foreground">Loading repositories...</p>
                    </div>
                    <div v-else-if="props.repositories.length === 0" class="p-3 text-center">
                        <p class="text-sm text-muted-foreground">No repositories found</p>
                    </div>

                    <div v-else class="max-h-60 overflow-y-auto">
                        <button
                            v-for="repo in props.repositories"
                            :key="repo.id"
                            type="button"
                            class="flex w-full items-start gap-3 text-left"
                            @click="selectRepository(repo)"
                        >
                            <div
                                class="px-items-start flex min-h-[80px] w-full gap-3 rounded-2xl p-2 text-gray-900 hover:bg-white/90 dark:text-gray-100 dark:hover:bg-white/10"
                            >
                                <div class="mt-1 ml-3 h-2 w-2 rounded-full bg-green-500" :title="repo.language"></div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">{{ repo.name }}</span>
                                        <Badge v-if="repo.language" variant="secondary" class="text-xs">
                                            {{ repo.language }}
                                        </Badge>
                                    </div>

                                    <p v-if="repo.description" class="line-clamp-2 text-sm text-muted-foreground">
                                        {{ repo.description }}
                                    </p>

                                    <div class="mt-1 flex items-center gap-4 text-xs text-muted-foreground">
                                        <span class="flex items-center gap-1">
                                            <AlertCircle class="h-3 w-3" />
                                            {{ repo.open_issues_count }} issues
                                        </span>
                                        <span>{{ formatDate(repo.updated_at) }}</span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selected Repository Display -->
            <div v-if="selectedRepo" class="rounded-2xl border border-gray-300 p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="h-2 w-2 rounded-full bg-green-500" :title="selectedRepo.language"></div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ selectedRepo.name }}</span>
                                <Badge v-if="selectedRepo.language" variant="secondary" class="text-xs">
                                    {{ selectedRepo.language }}
                                </Badge>
                            </div>
                            <p v-if="selectedRepo.description" class="text-sm text-muted-foreground">
                                {{ selectedRepo.description }}
                            </p>
                        </div>
                    </div>
                    <Button @click="clearRepository" variant="ghost" size="sm">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <InputError :message="form.errors.repository_full_name" />
        </div>

        <!-- Issue Selection -->
        <div v-if="selectedRepo" class="space-y-2">
            <Label>Select Issue *</Label>
            <div class="relative">
                <div class="relative">
                    <Input
                        v-model="issueSearchQuery"
                        placeholder="Search issues in the selected repository..."
                        :class="[form.errors.issue_number && 'border-red-500 focus-visible:ring-red-500']"
                        @focus="showIssueDropdown = true"
                        @input="showIssueDropdown = true"
                    />
                    <Search class="absolute top-1/2 right-3 h-4 w-4 -translate-y-1/2 text-gray-400" />
                </div>

                <!-- Issues Dropdown -->

                <div
                    v-if="showIssueDropdown && (filteredIssues.length > 0 || issueLoading)"
                    class="mt-[2px] rounded-2xl border bg-white/40 text-sm font-medium shadow-sm backdrop-blur-xl backdrop-saturate-150 sm:backdrop-blur-2xl dark:bg-white/10"
                >
                    <div v-if="issueLoading" class="p-3 text-center">
                        <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                        <p class="text-sm text-muted-foreground">Loading issues...</p>
                    </div>

                    <div v-else-if="filteredIssues.length === 0" class="p-3 text-center">
                        <p class="text-sm text-muted-foreground">
                            {{ issueSearchQuery.trim() ? 'No matching issues found' : 'No open issues found' }}
                        </p>
                    </div>

                    <div v-else class="max-h-60 overflow-y-auto">
                        <button
                            v-for="issue in filteredIssues"
                            :key="issue.id"
                            type="button"
                            class="flex w-full items-start gap-3 text-left"
                            @click="selectIssue(issue)"
                        >
                            <div
                                class="flex min-h-[80px] w-full gap-3 rounded-2xl p-2 text-gray-900 hover:bg-white/80 dark:text-gray-100 dark:hover:bg-white/10"
                            >
                                <div class="mt-1 ml-3 flex-shrink-0">
                                    <AlertCircle class="h-3 w-3 text-green-600" />
                                </div>

                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium">#{{ issue.number }}</span>
                                        <span class="truncate font-medium">{{ issue.title }}</span>
                                    </div>

                                    <p v-if="issue.body" class="line-clamp-2 text-sm text-muted-foreground">
                                        {{ issue.body }}
                                    </p>

                                    <div class="mt-1 flex items-center gap-4 text-xs text-muted-foreground">
                                        <span>{{ issue.user.login }}</span>
                                        <span class="flex items-center gap-1">
                                            <MessageCircle class="h-3 w-3" />
                                            {{ issue.comments }} comments
                                        </span>
                                        <span>{{ formatDate(issue.updated_at) }}</span>
                                    </div>

                                    <div v-if="issue.labels.length > 0" class="mt-2 flex flex-wrap gap-1">
                                        <span
                                            v-for="label in issue.labels.slice(0, 3)"
                                            :key="label.name"
                                            class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                            :style="`background-color: #${label.color}20; color: #${label.color}`"
                                        >
                                            {{ label.name }}
                                        </span>
                                        <span v-if="issue.labels.length > 3" class="text-xs text-muted-foreground">
                                            +{{ issue.labels.length - 3 }} more
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Selected Issue Display -->
            <div v-if="selectedIssue" class="rounded-2xl border border-gray-300 p-3">
                <div class="flex items-start justify-between">
                    <div class="flex min-w-0 flex-1 items-start gap-3">
                        <AlertCircle class="mt-1 h-4 w-4 flex-shrink-0 text-green-600" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-2">
                                <span class="font-medium">#{{ selectedIssue.number }}</span>
                                <span class="flex-1 text-sm">{{ selectedIssue.title }}</span>
                            </div>
                            <p v-if="selectedIssue.body" class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                {{ selectedIssue.body }}
                            </p>
                            <div v-if="selectedIssue.labels.length > 0" class="mt-2 flex flex-wrap gap-1">
                                <span
                                    v-for="label in selectedIssue.labels.slice(0, 5)"
                                    :key="label.name"
                                    class="inline-flex items-center rounded-full px-2 py-1 text-xs font-medium"
                                    :style="`background-color: #${label.color}20; color: #${label.color}`"
                                >
                                    {{ label.name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    <Button @click="clearIssue" variant="ghost" size="sm" class="ml-2 flex-shrink-0">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <InputError :message="form.errors.issue_number" />
        </div>

        <!-- Quick Actions -->
        <div
            v-if="selectedRepo && !selectedIssue && !issueLoading"
            class="rounded-lg border border-purple-200 bg-blue-50 p-4 dark:border-purple-800 dark:bg-purple-950"
        >
            <div class="flex items-center gap-3">
                <Info class="h-5 w-5 flex-shrink-0 text-purple-600 dark:text-purple-400" />
                <div>
                    <h4 class="font-medium text-purple-800 dark:text-purple-200">Select an Issue</h4>
                    <p class="text-sm text-purple-700 dark:text-purple-300">
                        Choose an open issue from <strong>{{ selectedRepo.name }}</strong> to create a bounty for.
                        {{ props.issues.length > 0 ? `${props.issues.length} open issues available.` : 'No open issues found.' }}
                    </p>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
