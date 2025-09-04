<script setup lang="ts">
import { ref, computed, watch, onMounted, onUnmounted } from 'vue'
import { Badge } from '@/components/ui/badge'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import InputError from '@/components/InputError.vue'
import { Search, Loader2, X, AlertCircle, MessageCircle, Info } from 'lucide-vue-next'
import { router } from '@inertiajs/vue3'

interface Repository {
    id: number
    name: string
    full_name: string
    description: string
    url: string
    language: string
    updated_at: string
    open_issues_count: number
}

interface Issue {
    id: number
    number: number
    title: string
    body: string
    url: string
    state: string
    created_at: string
    updated_at: string
    user: {
        login: string
        avatar_url: string
    }
    labels: Array<{
        name: string
        color: string
    }>
    comments: number
}

interface Props {
    form: any
}

const props = defineProps<Props>()
const emit = defineEmits<{
    updateForm: [field: string, value: any]
}>()

const clearForm = () => {
    selectedRepository.value = null
    selectedIssue.value = null
    repositorySearchQuery.value = ''
    issueSearchQuery.value = ''
    repositories.value = []
    issues.value = []
    repositoryCurrentPage.value = 1
    repositoryHasMorePages.value = true
    issueCurrentPage.value = 1
    issueHasMorePages.value = true
    showRepositoryDropdown.value = false
    showIssueDropdown.value = false
}

defineExpose({
    clearForm
})


const repositories = ref<Repository[]>([])
const issues = ref<Issue[]>([])
const repositorySearchQuery = ref('')
const issueSearchQuery = ref('')
const selectedRepository = ref<Repository | null>(null)
const selectedIssue = ref<Issue | null>(null)
const repositoryLoading = ref(false)
const issueLoading = ref(false)
const showRepositoryDropdown = ref(false)
const showIssueDropdown = ref(false)
const repositoryCurrentPage = ref(1)
const repositoryHasMorePages = ref(true)
const issueCurrentPage = ref(1)
const issueHasMorePages = ref(true)

const repositoryScrollContainer = ref<HTMLElement | null>(null)
const issueScrollContainer = ref<HTMLElement | null>(null)

let repositorySearchTimeout: number | null = null

const filteredIssues = computed(() => {
    if (!issueSearchQuery.value.trim()) return issues.value
    const query = issueSearchQuery.value.toLowerCase().trim()
    return issues.value.filter(issue =>
        issue.title.toLowerCase().includes(query) ||
        issue.number.toString().includes(query) ||
        (issue.body && issue.body.toLowerCase().includes(query)) ||
        issue.user.login.toLowerCase().includes(query)
    )
})

const updateFormField = (field: string, value: any) => {
    emit('updateForm', field, value)
}

const debouncedSearchRepositories = () => {
    if (repositorySearchTimeout) {
        clearTimeout(repositorySearchTimeout)
    }

    repositorySearchTimeout = setTimeout(() => {
        searchRepositories(true)
    }, 300)
}

const handleRepositoryFocus = () => {
    if (repositories.value.length > 0) {
        showRepositoryDropdown.value = true
    }
}

const handleRepositoryScroll = (event: Event) => {
    const target = event.target as HTMLElement
    const { scrollTop, scrollHeight, clientHeight } = target

    if (scrollHeight - scrollTop - clientHeight < 50 &&
        !repositoryLoading.value &&
        repositoryHasMorePages.value) {
        loadMoreRepositories()
    }
}

const handleIssueScroll = (event: Event) => {
    const target = event.target as HTMLElement
    const { scrollTop, scrollHeight, clientHeight } = target

    if (scrollHeight - scrollTop - clientHeight < 50 &&
        !issueLoading.value &&
        issueHasMorePages.value) {
        loadMoreIssues()
    }
}

const searchRepositories = async (reset: boolean = false) => {
    if (!repositorySearchQuery.value.trim()) {
        showRepositoryDropdown.value = false
        repositories.value = []
        repositoryCurrentPage.value = 1
        repositoryHasMorePages.value = true
        return
    }

    if (reset) {
        repositories.value = []
        repositoryCurrentPage.value = 1
        repositoryHasMorePages.value = true
    }

    showRepositoryDropdown.value = true
    repositoryLoading.value = true

    try {
        router.visit(route('bounty.search-repositories'), {
            method: 'get',
            data: {
                query: repositorySearchQuery.value,
                page: repositoryCurrentPage.value
            },
            only: ['repositories'],
            preserveState: true,
            preserveScroll: true,
            replace: true,
            onSuccess: (page: any) => {
                const newRepos = page.props.repositories || []

                if (reset) {
                    repositories.value = newRepos
                } else {
                    repositories.value = [...repositories.value, ...newRepos]
                }

                repositoryHasMorePages.value = newRepos.length >= 50
            },
            onFinish: () => {
                repositoryLoading.value = false
            }
        })
    } catch (error) {
        console.error('Failed to search repositories:', error)
        repositoryLoading.value = false
    }
}

const loadMoreRepositories = async () => {
    if (repositoryLoading.value || !repositoryHasMorePages.value) return

    repositoryCurrentPage.value += 1
    await searchRepositories(false)
}

const searchIssues = async (reset: boolean = false) => {
    if (!selectedRepository.value) return

    if (reset) {
        issues.value = []
        issueCurrentPage.value = 1
        issueHasMorePages.value = true
    }

    showIssueDropdown.value = true
    issueLoading.value = true

    const [owner, repo] = selectedRepository.value.full_name.split('/')

    router.visit(route('bounty.repository-issues', { owner, repo }), {
        method: 'get',
        data: {
            page: issueCurrentPage.value
        },
        only: ['issues'],
        preserveState: true,
        preserveScroll: true,
        replace: true,
        onSuccess: (page: any) => {
            const newIssues = page.props.issues || []

            if (reset) {
                issues.value = newIssues
            } else {
                issues.value = [...issues.value, ...newIssues]
            }
            issueHasMorePages.value = newIssues.length >= 50
        },
        onFinish: () => {
            issueLoading.value = false
        }
    })
}

const loadMoreIssues = async () => {
    if (issueLoading.value || !issueHasMorePages.value) return

    issueCurrentPage.value += 1
    await searchIssues(false)
}

const selectRepository = (repository: Repository) => {
    selectedRepository.value = repository
    repositorySearchQuery.value = repository.name
    showRepositoryDropdown.value = false

    clearIssue()
    updateFormField('repository_full_name', repository.full_name)
    searchIssues(true)
}

const selectIssue = (issue: Issue) => {
    selectedIssue.value = issue
    issueSearchQuery.value = `#${issue.number} - ${issue.title}`
    showIssueDropdown.value = false
    updateFormField('issue_number', issue.number)

    if (!props.form.title) {
        updateFormField('title', `Fix: ${issue.title}`)
    }

    if (!props.form.description && issue.body) {
        const truncatedBody = issue.body.length > 500
            ? issue.body.substring(0, 500) + '...'
            : issue.body
        updateFormField('description', `Related to GitHub issue: ${issue.url}\n\n${truncatedBody}`)
    }
}

const clearRepository = () => {
    selectedRepository.value = null
    repositorySearchQuery.value = ''
    repositories.value = []
    repositoryCurrentPage.value = 1
    repositoryHasMorePages.value = true
    clearIssue()
    updateFormField('repository_full_name', '')
    showRepositoryDropdown.value = false
}

const clearIssue = () => {
    selectedIssue.value = null
    issueSearchQuery.value = ''
    issues.value = []
    issueCurrentPage.value = 1
    issueHasMorePages.value = true
    updateFormField('issue_number', '')
    showIssueDropdown.value = false
}

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric'
    })
}

const handleClickOutside = (event: MouseEvent) => {
    const target = event.target as HTMLElement
    if (!target.closest('.relative')) {
        showRepositoryDropdown.value = false
        showIssueDropdown.value = false
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside)
})

onUnmounted(() => {
    document.removeEventListener('click', handleClickOutside)
    if (repositorySearchTimeout) {
        clearTimeout(repositorySearchTimeout)
    }
})

watch(() => props.form.errors, (errors) => {
    if (errors.repository_full_name) {
        showRepositoryDropdown.value = false
    }
    if (errors.issue_number) {
        showIssueDropdown.value = false
    }
}, { deep: true })

watch(issueSearchQuery, (newValue, oldValue) => {
    console.log('issueSearchQuery changed from', oldValue, 'to', newValue)
})

watch(filteredIssues, (newValue) => {
    console.log('filteredIssues updated, length:', newValue.length)
})

watch(showIssueDropdown, (newValue) => {
    console.log('showIssueDropdown changed to:', newValue)
})
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
                    <Search class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                </div>

                <!-- Repository Dropdown with Infinite Scroll -->
                <div
                    v-if="showRepositoryDropdown && (repositories.length > 0 || repositoryLoading)"
                    class="absolute z-50 mt-1 w-full rounded-md border bg-popover shadow-lg"
                >
                    <div v-if="repositoryLoading && repositories.length === 0" class="p-3 text-center">
                        <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                        <p class="text-sm text-muted-foreground">Loading repositories...</p>
                    </div>
                    <div v-else-if="repositories.length === 0 && !repositoryLoading" class="p-3 text-center">
                        <p class="text-sm text-muted-foreground">No repositories found</p>
                    </div>
                    <div
                        v-else
                        class="max-h-60 overflow-y-auto"
                        ref="repositoryScrollContainer"
                        @scroll="handleRepositoryScroll"
                    >
                        <button
                            v-for="repo in repositories"
                            :key="repo.id"
                            type="button"
                            class="flex w-full items-start gap-3 p-3 text-left hover:bg-accent transition-colors"
                            @click="selectRepository(repo)"
                        >
                            <div class="mt-1 flex-shrink-0">
                                <div
                                    class="h-2 w-2 rounded-full bg-green-500"
                                    :title="repo.language"
                                ></div>
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium">{{ repo.name }}</span>
                                    <Badge v-if="repo.language" variant="secondary" class="text-xs">
                                        {{ repo.language }}
                                    </Badge>
                                </div>
                                <p v-if="repo.description" class="text-sm text-muted-foreground line-clamp-2">
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
                        </button>

                        <!-- Loading more repositories -->
                        <div v-if="repositoryLoading && repositories.length > 0" class="p-3 text-center">
                            <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                            <p class="text-xs text-muted-foreground">Loading more repositories...</p>
                        </div>

                        <!-- End of results -->
                        <div v-else-if="repositoryHasMorePages === false && repositories.length > 0" class="p-2 text-center">
                            <p class="text-xs text-muted-foreground">No more repositories</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Repository Display -->
            <div v-if="selectedRepository" class="rounded-lg border bg-accent/10 p-3">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div
                            class="h-2 w-2 rounded-full bg-green-500"
                            :title="selectedRepository.language"
                        ></div>
                        <div>
                            <div class="flex items-center gap-2">
                                <span class="font-medium">{{ selectedRepository.name }}</span>
                                <Badge v-if="selectedRepository.language" variant="secondary" class="text-xs">
                                    {{ selectedRepository.language }}
                                </Badge>
                            </div>
                            <p v-if="selectedRepository.description" class="text-sm text-muted-foreground">
                                {{ selectedRepository.description }}
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
        <div v-if="selectedRepository" class="space-y-2">
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
                    <Search class="absolute right-3 top-1/2 h-4 w-4 -translate-y-1/2 text-gray-400" />
                </div>

                <!-- Issues Dropdown with Infinite Scroll -->
                <div
                    v-if="showIssueDropdown && (filteredIssues.length > 0 || issueLoading)"
                    class="absolute z-40 mt-1 w-full rounded-md border bg-popover shadow-lg"
                >
                    <div v-if="issueLoading && issues.length === 0" class="p-3 text-center">
                        <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                        <p class="text-sm text-muted-foreground">Loading issues...</p>
                    </div>
                    <div v-else-if="filteredIssues.length === 0 && !issueLoading" class="p-3 text-center">
                        <p class="text-sm text-muted-foreground">
                            {{ issueSearchQuery.trim() ? 'No matching issues found' : 'No open issues found' }}
                        </p>
                    </div>
                    <div
                        v-else
                        class="max-h-60 overflow-y-auto"
                        ref="issueScrollContainer"
                        @scroll="handleIssueScroll"
                    >
                        <button
                            v-for="issue in filteredIssues"
                            :key="issue.id"
                            type="button"
                            class="flex w-full items-start gap-3 p-3 text-left hover:bg-accent transition-colors"
                            @click="selectIssue(issue)"
                        >
                            <div class="mt-1 flex-shrink-0">
                                <AlertCircle class="h-4 w-4 text-green-600" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div class="flex items-start gap-2">
                                    <span class="font-medium text-sm">#{{ issue.number }}</span>
                                    <span class="text-sm flex-1">{{ issue.title }}</span>
                                </div>
                                <p v-if="issue.body" class="text-sm text-muted-foreground line-clamp-2 mt-1">
                                    {{ issue.body }}
                                </p>
                                <div class="mt-2 flex items-center gap-4 text-xs text-muted-foreground">
                                    <span>{{ issue.user.login }}</span>
                                    <span class="flex items-center gap-1">
                                        <MessageCircle class="h-3 w-3" />
                                        {{ issue.comments }}
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
                        </button>

                        <!-- Loading more issues -->
                        <div v-if="issueLoading && issues.length > 0" class="p-3 text-center">
                            <Loader2 class="mx-auto h-4 w-4 animate-spin" />
                            <p class="text-xs text-muted-foreground">Loading more issues...</p>
                        </div>

                        <!-- End of results -->
                        <div v-else-if="issueHasMorePages === false && issues.length > 0" class="p-2 text-center">
                            <p class="text-xs text-muted-foreground">No more issues</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Selected Issue Display -->
            <div v-if="selectedIssue" class="rounded-lg border bg-accent/10 p-3">
                <div class="flex items-start justify-between">
                    <div class="flex items-start gap-3 flex-1 min-w-0">
                        <AlertCircle class="mt-1 h-4 w-4 text-green-600 flex-shrink-0" />
                        <div class="min-w-0 flex-1">
                            <div class="flex items-start gap-2">
                                <span class="font-medium">#{{ selectedIssue.number }}</span>
                                <span class="text-sm flex-1">{{ selectedIssue.title }}</span>
                            </div>
                            <p v-if="selectedIssue.body" class="text-sm text-muted-foreground line-clamp-2 mt-1">
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
                    <Button @click="clearIssue" variant="ghost" size="sm" class="flex-shrink-0 ml-2">
                        <X class="h-4 w-4" />
                    </Button>
                </div>
            </div>
            <InputError :message="form.errors.issue_number" />
        </div>

        <!-- Quick Actions -->
        <div v-if="selectedRepository && !selectedIssue && !issueLoading" class="rounded-lg border border-blue-200 bg-blue-50 p-4 dark:border-blue-800 dark:bg-blue-950">
            <div class="flex items-center gap-3">
                <Info class="h-5 w-5 text-blue-600 dark:text-blue-400 flex-shrink-0" />
                <div>
                    <h4 class="font-medium text-blue-800 dark:text-blue-200">Select an Issue</h4>
                    <p class="text-sm text-blue-700 dark:text-blue-300">
                        Choose an open issue from <strong>{{ selectedRepository.name }}</strong> to create a bounty for.
                        {{ issues.length > 0 ? `${issues.length} open issues available.` : 'No open issues found.' }}
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
