<script setup lang="ts">
import LanguageFilter from '@/components/LanguageFilter.vue';
import NavFooter from '@/components/NavFooter.vue';
import PopularBountiesPanel from '@/components/PopularBountiesPanel.vue';
import ProviderFilter from '@/components/ProviderFilter.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { contactLinks } from '@/composables/contactLinks';
import { useProviderUtils } from '@/composables/useProviderUtils';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { BountyStatus, ProviderOption, type Bounty, type BountyPagination } from '@/types/bounty';
import { Head, router } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import { Calendar, DollarSign, Eye, Loader2, Lock, Search, Target } from 'lucide-vue-next';

type Provider = {
    provider: string;
    provider_username: string;
};

type User = { id: number; nickname: string; avatar: string; name: string; providers?: Provider[] };

type PageProps = AppPageProps<{
    bounties?: BountyPagination;
    availableLanguages?: string[];
    availableProviders?: ProviderOption[];
    userOrganizations?: OrgOption[];
    filters?: {
        search?: string;
        bounty_search?: string;
        language?: string;
        provider?: string;
        organization?: string;
    };
    userFilters?: { search?: string };
    users?: { data?: User[] };
    popularBounties?: PopularBounty[];
    trendingBounties?: PopularBounty[];
}>;

type OrgOption = { id: number; name: string };

type PopularBounty = Bounty & {
    popularity_score?: number;
};

const props = withDefaults(defineProps<PageProps>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
    availableLanguages: () => [],
    availableProviders: () => [],
    userFilters: () => ({ search: '' }),
    users: () => ({ data: [] }),
    popularBounties: () => [],
    trendingBounties: () => [],
    userOrganizations: () => [],
    filters: () => ({ search: '', language: '', provider: '', organization: '' }),
});

const searchBountyQuery = computed(() => {
    return props.filters?.search ?? '';
});
const selectedBountyLanguage = computed(() => {
    return props.filters?.language ?? '';
});
const selectedBountyProvider = computed(() => {
    return props.filters?.provider ?? '';
});
const selectedProviderDisplay = computed(() => {
    const provider = props.availableProviders.find((p) => p.value === localSelectedProvider.value || p.name === localSelectedProvider.value);
    return provider ? provider.name : localSelectedProvider.value;
});

const isBountySearching = ref(false);

const localSearchQuery = ref(searchBountyQuery.value);
const localSelectedLanguage = ref(selectedBountyLanguage.value);
const localSelectedProvider = ref(selectedBountyProvider.value);

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const debounce = <T extends (...args: any[]) => void>(func: T, wait: number): ((...args: Parameters<T>) => void) => {
    let timeout: number;
    return function executedFunction(...args: Parameters<T>) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = window.setTimeout(later, wait);
    };
};


const localSelectedOrganization = ref(props.filters?.organization ?? '');

const selectedOrganizationDisplay = computed(() => {
    const org = (props.userOrganizations ?? []).find((o) => o.name === localSelectedOrganization.value);
    return org ? org.name : localSelectedOrganization.value;
});

// Debounced search function to avoid too many requests
const debouncedBountySearch = debounce(() => {
    const params = new URLSearchParams(window.location.search);

    params.delete('search');
    params.delete('language');
    params.delete('provider');
    params.delete('page');
    params.delete('organization');

    if (localSearchQuery.value.trim()) {
        params.set('search', localSearchQuery.value.trim());
    }

    if (localSelectedLanguage.value) {
        params.set('language', localSelectedLanguage.value);
    }

    if (localSelectedProvider.value) {
        params.set('provider', localSelectedProvider.value);
    }

    if (localSelectedOrganization.value) {
        params.set('organization', localSelectedOrganization.value);
    }

    const queryString = params.toString();
    const url = queryString ? `${route('dashboard')}?${queryString}` : route('dashboard');

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
        only: ['bounties'],
        onStart: () => {
            isBountySearching.value = true;
        },
        onFinish: () => {
            isBountySearching.value = false;
        },
    });
}, 300);

watch([localSearchQuery, localSelectedLanguage], () => {
    debouncedBountySearch();
});

watch(searchBountyQuery, (newValue) => {
    localSearchQuery.value = newValue;
});

watch(selectedBountyLanguage, (newValue) => {
    localSelectedLanguage.value = newValue;
});

watch(localSelectedProvider, () => {
    debouncedBountySearch();
});

watch(localSelectedOrganization, () => { debouncedBountySearch(); });

const clearBountyFilters = () => {
    localSearchQuery.value = '';
    localSelectedLanguage.value = '';
    localSelectedProvider.value = '';
    localSelectedOrganization.value = '';

    router.visit(route('dashboard'), {
        preserveState: false,
        preserveScroll: true,
    });
};

const formatDate = (dateString: string) => {
    return new Date(dateString).toLocaleDateString('en-US', {
        year: 'numeric',
        month: 'short',
        day: 'numeric',
    });
};

const { getProviderConfig, getProviderBorderColor } = useProviderUtils();

const getStatusColor = (status: BountyStatus) => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'bg-green-100 text-green-800 dark:bg-green-900/60 dark:text-green-200';
        case BountyStatus.CLOSED:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
        default:
            return 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200';
    }
};

const getStatusDisplayText = (status: BountyStatus): string => {
    switch (status) {
        case BountyStatus.OPEN:
            return 'OPEN';
        case BountyStatus.CLOSED:
            return 'CLOSED';
        default:
            return String(status).toUpperCase();
    }
};

const navigateToBounty = (bounty: Bounty) => {
    router.visit(route('bounties.show', { bounty: bounty.id }));
};

const navigateToBountyPage = (page: number) => {
    const params = new URLSearchParams();

    if (localSearchQuery.value.trim()) {
        params.set('bounty_search', localSearchQuery.value.trim());
    }

    if (localSelectedLanguage.value) {
        params.set('language', localSelectedLanguage.value);
    }

    params.set('page', page.toString());

    const queryString = params.toString();
    const url = queryString ? `${route('dashboard')}?${queryString}` : route('dashboard');

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
    });
};

const hasActiveBountyFilters = computed(() => {
    return localSearchQuery.value.trim() !== '' || localSelectedLanguage.value !== '' || localSelectedProvider.value != '' || localSelectedOrganization.value !== '';
});

</script>

<template>
    <Head title="Dashboard - Find Bounties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <!-- Bounty Search and Grid Section -->
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Top Section - Popular and Trending Bounties -->
                <div class="mt-12 mb-12 w-full space-y-6">
                    <!-- Popular Bounties Section -->
                    <div
                        :class="[
                            'grid grid-cols-1 gap-6',
                            trendingBounties && trendingBounties.length > 0 ? 'lg:grid-cols-2' : 'justify-items-center lg:grid-cols-1',
                        ]"
                    >
                        <!-- Popular Bounties -->
                        <PopularBountiesPanel :bounties="popularBounties" title="🔥 Popular Bounties" />

                        <!-- Trending Bounties -->
                        <PopularBountiesPanel
                            v-if="trendingBounties && trendingBounties.length > 0"
                            :bounties="trendingBounties"
                            title="📈 Trending This Week"
                        />

                        <div
                            v-else
                            class="flex items-center justify-center rounded-lg border-2 border-dashed border-gray-200 p-8 dark:border-gray-700"
                        >
                            <div class="text-center">
                                <Target class="mx-auto mb-4 h-12 w-12 text-gray-400" />
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">More Coming Soon</h3>
                                <p class="text-gray-500 dark:text-gray-400">New bounties are added regularly</p>
                            </div>
                        </div>
                    </div>

                    <div class="mx-auto my-28 max-w-2xl text-center">
                        <h1 class="mb-2 text-3xl font-bold tracking-tight">Find Open Bounties</h1>
                        <p class="text-muted-foreground">
                            Discover rewarding development opportunities and earn XP by contributing to open source projects.
                        </p>
                    </div>

                    <!-- Search and Filters -->
                    <div class="mx-auto w-full max-w-4xl space-y-4">
                        <!-- Search Bar -->
                        <div class="relative flex-1">
                            <Search class="absolute top-1/2 left-4 z-10 h-5 w-5 -translate-y-1/2 text-gray-400 dark:text-gray-500" />

                            <Input
                                v-model="localSearchQuery"
                                placeholder="Search bounties by title, description, or repository..."
                            />

                            <img
                                src="/assets/images/sitting.png"
                                alt="sitting bug"
                                class="absolute -top-34 right-0 z-50 w-48"
                            />
                        </div>

                        <!-- Filters Row -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Language Filter -->
                            <div class="sm:w-48">
                                <LanguageFilter v-model="localSelectedLanguage" :languages="availableLanguages" placeholder="All Languages" />
                            </div>
                            <!-- Provider Filter -->
                            <div class="sm:w-48">
                                <ProviderFilter v-model="localSelectedProvider" :providers="availableProviders!" placeholder="All Providers" />
                            </div>
                            <div v-if="userOrganizations && userOrganizations.length > 0" class="sm:w-48">
                                <LanguageFilter
                                    v-model="localSelectedOrganization"
                                    :languages="(userOrganizations ?? []).map(o => o.name)"
                                    placeholder="All Organizations"
                                />
                            </div>

                            <Button v-if="hasActiveBountyFilters" @click="clearBountyFilters" variant="button" size="default" class="rounded-xl">
                                Clear Filters
                            </Button>
                        </div>

                        <!-- Active Filters Display -->
                        <div v-if="hasActiveBountyFilters" class="flex flex-wrap gap-2">
                            <Badge v-if="localSearchQuery.trim()" variant="custom" class="flex items-center gap-1">
                                Search: "{{ localSearchQuery.trim() }}"
                            </Badge>
                            <Badge v-if="localSelectedLanguage" variant="custom" class="flex items-center gap-1">
                                Language: {{ localSelectedLanguage }}
                            </Badge>
                            <Badge v-if="localSelectedProvider" variant="custom" class="flex items-center gap-1">
                                Provider: {{ selectedProviderDisplay }}
                            </Badge>
                            <Badge v-if="localSelectedOrganization" variant="custom" class="flex items-center gap-1">
                                Organization: {{ selectedOrganizationDisplay }}
                            </Badge>
                        </div>
                    </div>

                    <!-- Loading State -->
                    <div v-if="isBountySearching" class="flex items-center justify-center py-8">
                        <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                        <span class="ml-2 text-muted-foreground">Searching bounties...</span>
                    </div>

                    <!-- All Bounties Section -->
                    <div v-else-if="bounties && bounties.data && bounties.data.length > 0">
                        <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-2">
                            <Card
                                v-for="bounty in bounties.data"
                                :key="bounty.id"
                                :class="[
                                    'min-h-[238px] min-w-0 cursor-pointer border border-l-4 bg-white/40 backdrop-blur-xl transition-all transition-colors hover:-translate-y-1 hover:shadow-lg dark:bg-white/5',
                                    getProviderBorderColor(bounty.issue.provider),
                                ]"
                                @click="navigateToBounty(bounty)"
                            >
                                <CardHeader class="pb-3">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div class="flex min-w-0 flex-1 items-center gap-2 overflow-hidden">
                                            <svg
                                                v-if="bounty.issue.provider === 'github'"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                :class="getProviderConfig(bounty.issue?.provider).color"
                                                class="h-6 w-6 flex-shrink-0"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M12 0c-6.626 0-12 5.373-12 12 0 5.302 3.438 9.8 8.207 11.387.599.111.793-.261.793-.577v-2.234c-3.338.726-4.033-1.416-4.033-1.416-.546-1.387-1.333-1.756-1.333-1.756-1.089-.745.083-.729.083-.729 1.205.084 1.839 1.237 1.839 1.237 1.07 1.834 2.807 1.304 3.492.997.107-.775.418-1.305.762-1.604-2.665-.305-5.467-1.334-5.467-5.931 0-1.311.469-2.381 1.236-3.221-.124-.303-.535-1.524.117-3.176 0 0 1.008-.322 3.301 1.23.957-.266 1.983-.399 3.003-.404 1.02.005 2.047.138 3.006.404 2.291-1.552 3.297-1.23 3.297-1.23.653 1.653.242 2.874.118 3.176.77.84 1.235 1.911 1.235 3.221 0 4.609-2.807 5.624-5.479 5.921.43.372.823 1.102.823 2.222v3.293c0 .319.192.694.801.576 4.765-1.589 8.199-6.086 8.199-11.386 0-6.627-5.373-12-12-12z"
                                                />
                                            </svg>

                                            <svg
                                                v-else-if="bounty.issue.provider === 'gitlab'"
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                :class="getProviderConfig(bounty.issue?.provider).color"
                                                class="h-6 w-6 flex-shrink-0"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M2.39 9.73L12 22l9.61-12.27a.7.7 0 0 0-.25-.97L19.07 7 16.7 1.27a.7.7 0 0 0-1.32 0L12 7.33 8.62 1.27a.7.7 0 0 0-1.32 0L4.93 7 2.64 8.76a.7.7 0 0 0-.25.97Z"
                                                />
                                            </svg>

                                            <svg
                                                v-else
                                                xmlns="http://www.w3.org/2000/svg"
                                                viewBox="0 0 24 24"
                                                :class="getProviderConfig(bounty.issue?.provider).color"
                                                class="h-6 w-6 flex-shrink-0"
                                                fill="currentColor"
                                            >
                                                <path
                                                    d="M2.4 3A1.3 1.3 0 0 0 1.1 4.5l2.7 15.9c.1.5.6.9 1.2.9h13a1.3 1.3 0 0 0 1.2-1.1l2.7-15.7A1.3 1.3 0 0 0 20.7 3H2.4zm9.6 12.3H9.3l-.9-6.6h7.2l-.9 6.6h-2.7z"
                                                />
                                            </svg>
                                            <h3 class="line-clamp-2 min-w-0 break-all text-lg leading-tight font-semibold">
                                                {{ bounty.title }}
                                            </h3>
                                        </div>
                                        <div class="flex flex-wrap items-center gap-1.5">
                                            <Badge
                                                :class="getProviderConfig(bounty.issue.provider).badgeColor"
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            >
                                                {{ getProviderConfig(bounty.issue.provider).name }}
                                            </Badge>
                                            <Badge
                                                :class="getStatusColor(bounty.status)"
                                                class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium"
                                            >
                                                {{ getStatusDisplayText(bounty.status) }}
                                            </Badge>
                                            <Badge
                                                v-if="bounty.organization_id"
                                                variant="secondary"
                                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                            >
                                                <Lock class="h-3 w-3" />
                                                Private
                                            </Badge>
                                        </div>
                                    </div>
                                </CardHeader>
                                <CardContent class="flex flex-1 flex-col space-y-4">
                                    <!-- Short Description -->
                                    <p v-if="bounty.description" class="text-sm break-all text-muted-foreground">
                                        {{ bounty.description.length > 100 ? bounty.description.substring(0, 100) + '...' : bounty.description }}
                                    </p>

                                    <!-- Languages -->
                                    <div v-if="bounty.languages && bounty.languages.length > 0" class="flex flex-wrap gap-1">
                                        <Badge v-for="language in bounty.languages.slice(0, 3)" :key="language" variant="outline" class="text-xs">
                                            {{ language }}
                                        </Badge>
                                        <Badge v-if="bounty.languages.length > 3" variant="outline" class="text-xs">
                                            +{{ bounty.languages.length - 3 }}
                                        </Badge>
                                    </div>

                                    <!-- Metadata with views -->
                                    <div class="mt-auto flex items-center justify-between text-sm">
                                        <div class="flex items-center gap-3">
                                            <div class="flex items-center gap-1 font-medium text-yellow-700">
                                                <DollarSign class="h-4 w-4" />
                                                {{ bounty.reward_xp }} XP
                                            </div>
                                            <!-- Views megjelenítése -->
                                            <div v-if="bounty.views && bounty.views > 0" class="flex items-center gap-1 text-muted-foreground">
                                                <Eye class="h-3 w-3" />
                                                {{ bounty.views }}
                                            </div>
                                            <!-- Submissions count ha van -->
                                            <div
                                                v-if="bounty.submissions_count && bounty.submissions_count > 0"
                                                class="flex items-center gap-1 text-muted-foreground"
                                            >
                                                <Target class="h-3 w-3" />
                                                {{ bounty.submissions_count }}
                                            </div>
                                        </div>

                                        <!-- Created Date -->
                                        <span class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <Calendar class="h-3 w-3" />
                                            {{ formatDate(bounty.created_at) }}
                                        </span>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>
                    </div>

                    <!-- Empty State -->
                    <div v-else class="py-12 text-center">
                        <Target class="mx-auto mb-6 h-16 w-16 text-muted-foreground" />
                        <h3 class="mb-2 text-xl font-semibold">
                            {{ hasActiveBountyFilters ? 'No bounties found' : 'No bounties available' }}
                        </h3>
                        <p class="mb-4 text-muted-foreground">
                            {{
                                hasActiveBountyFilters
                                    ? 'Try adjusting your search terms or filters to find bounties.'
                                    : 'There are no open bounties at the moment. Check back later!'
                            }}
                        </p>
                        <Button v-if="hasActiveBountyFilters" @click="clearBountyFilters" variant="outline"> Clear all filters </Button>
                    </div>

                    <!-- Pagination -->
                    <div v-if="bounties && bounties.last_page > 1" class="mt-8 flex justify-center">
                        <div class="flex flex-shrink-0 flex-wrap items-center gap-1.5">
                            <Button
                                v-if="bounties.current_page > 1"
                                variant="button"
                                size="sm"
                                @click="navigateToBountyPage(bounties.current_page - 1)"
                            >
                                Previous
                            </Button>
                            <span class="px-3 text-sm text-muted-foreground"> Page {{ bounties.current_page }} of {{ bounties.last_page }} </span>
                            <Button
                                v-if="bounties.current_page < bounties.last_page"
                                variant="button"
                                size="sm"
                                @click="navigateToBountyPage(bounties.current_page + 1)"
                            >
                                Next
                            </Button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <NavFooter :items="contactLinks" />
        </div>
    </AppLayout>
</template>

<style scoped>
.line-clamp-2 {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

.line-clamp-3 {
    display: -webkit-box;
    -webkit-line-clamp: 3;
    -webkit-box-orient: vertical;
    overflow: hidden;
}
</style>
