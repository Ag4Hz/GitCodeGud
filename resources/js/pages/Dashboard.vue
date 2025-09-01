<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';
import NavFooter from '@/components/NavFooter.vue';
import { contactLinks } from '@/composables/contactLinks';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Calendar, DollarSign, Search, Target, Loader2, ChevronDown } from 'lucide-vue-next';
import { BountyStatus, type BountyPagination, type Bounty } from '@/types/bounty';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head } from '@inertiajs/vue3';
import UserSearch from '@/components/UserSearch.vue';
import PopularBountiesPanel from '@/components/PopularBountiesPanel.vue';
import { Eye } from 'lucide-vue-next';

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
            return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200';
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
            <div class="relative mx-auto mt-10 mb-16 w-full max-w-md">
                <UserSearch
                    :filters="props.userFilters"
                    :results="props.users"
                />
            </div>

            <!-- Bounty Search and Grid Section -->
            <div class="flex h-full flex-1 flex-col gap-6 rounded-xl p-4 mb-96">

                <!-- Top Section - Popular and Trending Bounties -->
                <div class="w-full space-y-6">
                    <!-- Header -->
                    <div class="text-center max-w-2xl mx-auto">
                        <h1 class="text-3xl font-bold tracking-tight mb-2">Find Open Bounties</h1>
                        <p class="text-muted-foreground">
                            Discover rewarding development opportunities and earn XP by contributing to open source projects.
                        </p>
                    </div>

                    <!-- Popular Bounties Section -->
                        <div :class="[
                    'grid grid-cols-1 gap-6',
                    (trendingBounties && trendingBounties.length > 0)
                      ? 'lg:grid-cols-2'
                      : 'lg:grid-cols-1 justify-items-center'
                  ]">

                    <!-- Popular Bounties -->
                        <PopularBountiesPanel
                            :bounties="popularBounties"
                            title="🔥 Popular Bounties"
                        />

                        <!-- Trending Bounties -->
                        <PopularBountiesPanel
                            v-if="trendingBounties && trendingBounties.length > 0"
                            :bounties="trendingBounties"
                            title="📈 Trending This Week"
                        />

                        <div v-else class="flex items-center justify-center border-2 border-dashed border-gray-200 rounded-lg p-8 dark:border-gray-700">
                            <div class="text-center">
                                <Target class="mx-auto h-12 w-12 text-gray-400 mb-4" />
                                <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100">More Coming Soon</h3>
                                <p class="text-gray-500 dark:text-gray-400">New bounties are added regularly</p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="border-t border-gray-200 dark:border-gray-700"></div>

                <!-- Search and Filters -->
                <Card class="w-full max-w-4xl mx-auto">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Search class="h-5 w-5" />
                            Search & Filter Bounties
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div class="flex flex-col sm:flex-row gap-4">
                            <!-- Search Bar -->
                            <div class="flex-1 relative">
                                <Search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                                <Input
                                    v-model="localSearchQuery"
                                    placeholder="Search bounties by title, description, or repository..."
                                    class="pl-10 pr-4"
                                />
                            </div>

                            <!-- Language Filter -->
                            <div class="sm:w-48">
                                <DropdownMenu>
                                    <DropdownMenuTrigger as-child>
                                        <Button variant="outline" class="w-full justify-between">
                                            {{ localSelectedLanguage || 'All Languages' }}
                                            <ChevronDown class="h-4 w-4 opacity-50" />
                                        </Button>
                                    </DropdownMenuTrigger>
                                    <DropdownMenuContent class="w-48">
                                        <DropdownMenuItem
                                            @click="localSelectedLanguage = ''"
                                            :class="{ 'bg-accent': !localSelectedLanguage }"
                                        >
                                            All Languages
                                        </DropdownMenuItem>
                                        <DropdownMenuItem
                                            v-for="language in availableLanguages"
                                            :key="language"
                                            @click="localSelectedLanguage = language"
                                            :class="{ 'bg-accent': localSelectedLanguage === language }"
                                        >
                                            {{ language }}
                                        </DropdownMenuItem>
                                    </DropdownMenuContent>
                                </DropdownMenu>
                            </div>

                            <!-- Clear Filters Button -->
                            <Button
                                v-if="hasActiveBountyFilters"
                                @click="clearBountyFilters"
                                variant="outline"
                                size="default"
                                class="sm:w-auto"
                            >
                                Clear Filters
                            </Button>
                        </div>

                        <!-- Active Filters Display -->
                        <div v-if="hasActiveBountyFilters" class="flex flex-wrap gap-2">
                            <Badge v-if="localSearchQuery.trim()" variant="secondary" class="flex items-center gap-1">
                                Search: "{{ localSearchQuery.trim() }}"
                            </Badge>
                            <Badge v-if="localSelectedLanguage" variant="secondary" class="flex items-center gap-1">
                                Language: {{ localSelectedLanguage }}
                            </Badge>
                        </div>
                    </CardContent>
                </Card>

                <!-- Loading State -->
                <div v-if="isBountySearching" class="flex justify-center items-center py-8">
                    <Loader2 class="h-8 w-8 animate-spin text-muted-foreground" />
                    <span class="ml-2 text-muted-foreground">Searching bounties...</span>
                </div>

                <div v-else-if="bounties && bounties.data && bounties.data.length > 0">
                    <div class="mb-6">
                        <h2 class="text-xl font-semibold">All Bounties</h2>
                        <p class="text-muted-foreground">Browse all available bounties</p>
                    </div>

                    <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                        <Card
                            v-for="bounty in bounties.data"
                            :key="bounty.id"
                            class="cursor-pointer transition-all hover:shadow-lg hover:-translate-y-1 border-l-4 border-l-green-500"
                            @click="navigateToBounty(bounty)"
                        >
                            <CardHeader class="pb-3">
                                <div class="flex items-start justify-between gap-2">
                                    <h3 class="font-semibold line-clamp-2 text-lg leading-tight">
                                        {{ bounty.title }}
                                    </h3>
                                    <Badge :class="getStatusColor(bounty.status)" class="flex-shrink-0 text-xs">
                                        {{ getStatusDisplayText(bounty.status) }}
                                    </Badge>
                                </div>
                            </CardHeader>
                            <CardContent class="space-y-4">
                                <!-- Short Description -->
                                <p v-if="bounty.description" class="text-sm text-muted-foreground">
                                    {{ bounty.description.length > 100 ? bounty.description.substring(0, 100) + '...' : bounty.description }}
                                </p>

                                <!-- Languages -->
                                <div v-if="bounty.languages && bounty.languages.length > 0" class="flex flex-wrap gap-1">
                                    <Badge
                                        v-for="language in bounty.languages.slice(0, 3)"
                                        :key="language"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        {{ language }}
                                    </Badge>
                                    <Badge
                                        v-if="bounty.languages.length > 3"
                                        variant="outline"
                                        class="text-xs"
                                    >
                                        +{{ bounty.languages.length - 3 }}
                                    </Badge>
                                </div>

                                <!-- Metadata with views -->
                                <div class="flex items-center justify-between text-sm">
                                    <div class="flex items-center gap-3">
                                        <div class="flex items-center gap-1 text-yellow-600 font-medium">
                                            <DollarSign class="h-4 w-4" />
                                            {{ bounty.reward_xp }} XP
                                        </div>
                                        <!-- Views megjelenítése -->
                                        <div v-if="bounty.views && bounty.views > 0" class="flex items-center gap-1 text-muted-foreground">
                                            <Eye class="h-3 w-3" />
                                            {{ bounty.views }}
                                        </div>
                                        <!-- Submissions count ha van -->
                                        <div v-if="bounty.submissions_count && bounty.submissions_count > 0" class="flex items-center gap-1 text-muted-foreground">
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
                <div v-else class="text-center py-12">
                    <Target class="mx-auto h-16 w-16 text-muted-foreground mb-6" />
                    <h3 class="text-xl font-semibold mb-2">
                        {{ hasActiveBountyFilters ? 'No bounties found' : 'No bounties available' }}
                    </h3>
                    <p class="text-muted-foreground mb-4">
                        {{
                            hasActiveBountyFilters
                                ? 'Try adjusting your search terms or filters to find bounties.'
                                : 'There are no open bounties at the moment. Check back later!'
                        }}
                    </p>
                    <Button v-if="hasActiveBountyFilters" @click="clearBountyFilters" variant="outline">
                        Clear all filters
                    </Button>
                </div>

                <!-- Pagination -->
                <div v-if="bounties && bounties.last_page > 1" class="flex justify-center mt-8">
                    <div class="flex items-center gap-2">
                        <Button
                            v-if="bounties.current_page > 1"
                            variant="outline"
                            size="sm"
                            @click="navigateToBountyPage(bounties.current_page - 1)"
                        >
                            Previous
                        </Button>
                        <span class="text-sm text-muted-foreground px-3">
                            Page {{ bounties.current_page }} of {{ bounties.last_page }}
                        </span>
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
