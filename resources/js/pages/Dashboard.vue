<script setup lang="ts">
import LanguageFilter from '@/components/LanguageFilter.vue';
import NavFooter from '@/components/NavFooter.vue';
import PopularBountiesPanel from '@/components/PopularBountiesPanel.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { BountyStatus, type Bounty, type BountyPagination } from '@/types/bounty';
import { Head, router } from '@inertiajs/vue3';
import { Calendar, DollarSign, Eye, Loader2, Search, Target } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
type User = { id: number; nickname: string; avatar: string; name: string };

type PageProps = AppPageProps<{
    bounties?: BountyPagination;
    availableLanguages?: string[];
    filters?: {
        search?: string;
        bounty_search?: string;
        language?: string;
    };
    userFilters?: { search?: string };
    users?: { data?: User[] };
    popularBounties?: PopularBounty[];
    trendingBounties?: PopularBounty[];
}>;

type PopularBounty = Bounty & {
    popularity_score?: number;
};

const props = withDefaults(defineProps<PageProps>(), {
    bounties: () => ({ data: [], total: 0, current_page: 1, last_page: 1 }),
    availableLanguages: () => [],
    filters: () => ({ search: '', language: '' }),
    userFilters: () => ({ search: '' }),
    users: () => ({ data: [] }),
    popularBounties: () => [],
    trendingBounties: () => [],
});

const searchBountyQuery = computed(() => {
    return props.filters?.search ?? '';
});
const selectedBountyLanguage = computed(() => {
    return props.filters?.language ?? '';
});

const isBountySearching = ref(false);

const localSearchQuery = ref(searchBountyQuery.value);
const localSelectedLanguage = ref(selectedBountyLanguage.value);

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

// Debounced search function to avoid too many requests
const debouncedBountySearch = debounce(() => {
    const params = new URLSearchParams(window.location.search);

    params.delete('search');
    params.delete('language');
    params.delete('page');

    if (localSearchQuery.value.trim()) {
        params.set('search', localSearchQuery.value.trim());
    }

    if (localSelectedLanguage.value) {
        params.set('language', localSelectedLanguage.value);
    }

    if (localSearchQuery.value.trim()) {
        params.set('search', localSearchQuery.value.trim());
    }

    const queryString = params.toString();
    const url = queryString ? `${route('dashboard')}?${queryString}` : route('dashboard');

    router.visit(url, {
        preserveState: true,
        preserveScroll: true,
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

const clearBountyFilters = () => {
    router.visit(route('dashboard'), {
        preserveState: true,
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
    return localSearchQuery.value.trim() !== '' || localSelectedLanguage.value !== '';
});
</script>

<template>
    <Head title="Dashboard - Find Bounties" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div>
            <!-- User Search at the top -->

            <!-- Bounty Search and Grid Section -->
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Top Section - Popular and Trending Bounties -->
                <div class="w-full space-y-6 mt-12 mb-12">
                    <!-- Header -->


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
                            <Input v-model="localSearchQuery" placeholder="Search bounties by title, description, or repository..." />
                        </div>

                        <!-- Filters Row -->
                        <div class="flex flex-wrap items-center gap-3">
                            <!-- Language Filter -->
                            <div class="sm:w-48">
                                <LanguageFilter v-model="localSelectedLanguage" :languages="availableLanguages" placeholder="All Languages" />
                            </div>

                            <!-- Clear Filters Button -->
                            <!--                        <Button v-if="hasActiveBountyFilters" @click="clearBountyFilters" variant="outline" size="default" class="sm:w-auto">-->
                            <!--                            Clear Filters-->
                            <!--                        </Button>-->
                        </div>

                        <!-- Active Filters Display -->
                        <div v-if="hasActiveBountyFilters" class="flex flex-wrap gap-2">
                            <!--                        <Badge v-if="localSearchQuery.trim()" variant="secondary" class="flex items-center gap-1">-->
                            <!--                            Search: "{{ localSearchQuery.trim() }}"-->
                            <!--                        </Badge>-->
                            <Badge v-if="localSelectedLanguage" variant="secondary" class="flex items-center gap-1">
                                Language: {{ localSelectedLanguage }}
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
                        <div class="mb-2">
<!--                            <h2 class="text-xl font-semibold">Bounties</h2>-->
<!--                            <p class="text-muted-foreground">Browse all available bounties</p>-->
                        </div>

                        <div class="grid gap-6 md:grid-cols-1 lg:grid-cols-2">
                            <Card
                                v-for="bounty in bounties.data"
                                :key="bounty.id"
                                class="cursor-pointer min-h-[238px] border border-white/10 border-l-4 border-l-green-800 transition-all hover:-translate-y-1 hover:shadow-lg  bg-white/40 backdrop-blur-xl  dark:bg-white/5"
                                @click="navigateToBounty(bounty)"
                            >
                                <CardHeader class="pb-3">
                                    <div class="flex items-start justify-between gap-2">
                                        <h3 class="line-clamp-2 text-lg leading-tight font-semibold">
                                            {{ bounty.title }}
                                        </h3>
                                        <Badge :class="getStatusColor(bounty.status)" class="inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium">
                                            {{ getStatusDisplayText(bounty.status) }}
                                        </Badge>
                                    </div>
                                </CardHeader>
                                <CardContent class="flex flex-1 flex-col space-y-4">
                                    <!-- Short Description -->
                                    <p v-if="bounty.description" class="text-sm text-muted-foreground">
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
                        <div class="flex items-center gap-2">
                            <Button
                                v-if="bounties.current_page > 1"
                                variant="outline"
                                size="sm"
                                @click="navigateToBountyPage(bounties.current_page - 1)"
                            >
                                Previous
                            </Button>
                            <span class="px-3 text-sm text-muted-foreground"> Page {{ bounties.current_page }} of {{ bounties.last_page }} </span>
                            <Button
                                v-if="bounties.current_page < bounties.last_page"
                                variant="outline"
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
