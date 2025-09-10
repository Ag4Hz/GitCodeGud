<script setup lang="ts">
import FollowModal from '@/components/FollowModal.vue';
import ReviewForm from '@/components/ReviewForm.vue';
import ReviewList from '@/components/ReviewList.vue';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { useToast } from '@/composables/useToast';
import { useXP } from '@/composables/useXP';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { type ReviewsPayload } from '@/types/review';
import { getXPSyncMessage } from '@/utils/toastMessages';
import { Head, router } from '@inertiajs/vue3';
import { Code, Database, MessageSquareMore, Settings, Star, Target, Trophy, Zap } from 'lucide-vue-next';
import { computed, ref } from 'vue';

const { success: showSuccess, error: showError } = useToast();

interface UserWithXP extends Omit<User, 'skills'> {
    total_xp: number;
    level: number;
    current_level_xp: number;
    next_level_xp: number;
    progress_percentage: number;
    skills: Array<{
        skill_name: string;
        type: string;
        xp: number;
        level: number;
    }>;
    followers_count: number;
    followings_count: number;
}

interface Props {
    user: UserWithXP;
    isOwner?: boolean;
    isFollowing?: boolean;
    profileUserId: number;
    followings: {
        data: { id: number; nickname: string }[];
        next_page_url: string | null;
    };
    followers: {
        data: { id: number; nickname: string }[];
        next_page_url: string | null;
        avatar: string | null;
    };
    canReview?: boolean;
    reviews: ReviewsPayload;
    ratingAvg: number;
}

const props = withDefaults(defineProps<Props>(), {
    isFollowing: false,
    followers: Object,
    reviews: () => ({ data: [], current_page: 1, last_page: 1, next_page_url: null, prev_page_url: null }),
});

const isOwner = computed(() => props.isOwner);

const { getInitials } = useInitials();
const { formatXP } = useXP();

const syncing = ref(false);
const followingBusy = ref(false);

const breadcrumbItems = computed<BreadcrumbItem[]>(() => [
    {
        title: isOwner.value ? 'My Profile' : `${props.user.name}'s Profile`,
        href: isOwner.value ? '/profile' : `/users/${props.user.id}`,
    },
]);

const levelProgress = computed(() => {
    return {
        currentLevelXP: props.user.current_level_xp ?? 0,
        nextLevelXP: props.user.next_level_xp ?? 0,
        progressXP: props.user.total_xp,
        totalNeeded: (props.user.next_level_xp ?? 0) - (props.user.current_level_xp ?? 0),
        percentage: props.user.progress_percentage ?? 0,
    };
});

const skillsByType = computed(() => {
    if (!props.user.skills?.length) return {};

    const grouped = props.user.skills.reduce(
        (acc, skill) => {
            const type = skill.type || 'other';
            (acc[type] = acc[type] || []).push(skill);
            return acc;
        },
        {} as Record<string, typeof props.user.skills>,
    );

    Object.values(grouped).forEach((skills) => {
        skills.sort((a, b) => b.xp - a.xp);
    });

    return grouped;
});

const typeConfig = {
    language: { name: 'Programming Languages', icon: Code },
    framework: { name: 'Frameworks', icon: Settings },
    tool: { name: 'Tools', icon: Settings },
    database: { name: 'Databases', icon: Database },
    other: { name: 'Other', icon: Star },
};

const getTypeIcon = (type: string) => {
    const config = typeConfig[type as keyof typeof typeConfig] || typeConfig.other;
    return config.icon;
};

const getTypeDisplayName = (type: string) => {
    const config = typeConfig[type as keyof typeof typeConfig] || typeConfig.other;
    return config.name;
};

const syncGitHubSkills = () => {
    syncing.value = true;
    router.post(
        '/profile/sync-github-skills',
        {},
        {
            onFinish: () => (syncing.value = false),
            onSuccess: () => {
                showSuccess(getXPSyncMessage('success', 'sync', 'en'));
            },
            onError: () => {
                showError(getXPSyncMessage('error', 'sync', 'en'));
            },
        },
    );
};

function follow() {
    if (followingBusy.value) return;
    followingBusy.value = true;
    router.post(
        route('users.follow', props.user.id),
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                followingBusy.value = false;
            },
            onSuccess: () => {
                router.reload({ only: ['followers', 'followings', 'user'] });
            },
        },
    );
}

function unfollow() {
    if (followingBusy.value) return;
    followingBusy.value = true;
    router.delete(route('users.unfollow', props.user.id), {
        preserveScroll: true,
        onFinish: () => {
            followingBusy.value = false;
        },
        onSuccess: () => {
            router.reload({ only: ['followers', 'followings', 'user'] });
        },
    });
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head :title="isOwner ? 'My Profile' : `${user.name}'s Profile`" />
        <div class="px-4 py-6">
            <div class="mx-auto max-w-4xl space-y-6 px-4 sm:px-6 lg:px-8">
                <!-- Profile Header -->
                <Card class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <Avatar class="overflow-hidden rounded-full sm:h-15 sm:w-15 md:h-18 md:w-18 lg:h-22 lg:w-22">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <!-- Large XP Level Badge -->
                                <div class="absolute -right-1 -bottom-1 flex items-center justify-center">
                                    <Badge
                                        class="h-6 min-w-6 border-3 border-white bg-purple-600 px-2 text-sm font-bold text-white shadow-lg dark:border-gray-900"
                                    >
                                        {{ user.level }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="flex-1">
                                <CardTitle class="text-xs sm:text-sm md:text-lg lg:text-2xl">
                                    {{ user.name }}
                                </CardTitle>

                                <CardDescription class="text-[11px] sm:text-sm md:text-base lg:text-base">
                                    {{ user.email }}
                                </CardDescription>

                                <div class="mt-2 flex flex-wrap items-center gap-4">
                                    <div class="flex items-center gap-1 text-[11px] font-medium sm:text-sm md:text-base">
                                        <Trophy class="h-4 w-4 text-orange-500" />
                                        Level {{ user.level }}
                                    </div>

                                    <div class="flex items-center gap-1 text-[11px] font-medium sm:text-sm md:text-base">
                                        <Zap class="h-4 w-4 text-purple-600" />
                                        {{ formatXP(user.total_xp) }} XP
                                    </div>

                                    <div class="flex gap-2 text-[10px] sm:gap-3 sm:text-sm md:text-base lg:gap-4">
                                        <FollowModal prop-name="followers" title="Followers" :count="user.followers_count" />
                                        <FollowModal prop-name="followings" title="Followings" :count="user.followings_count" />
                                    </div>
                                </div>
                            </div>

                            <div v-if="!isOwner" class="mt-3 flex items-center gap-2">
                                <Button
                                    v-if="!isFollowing"
                                    type="button"
                                    class="rounded-md bg-green-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-green-700 dark:bg-green-500 dark:hover:bg-green-600"
                                    :loading="followingBusy"
                                    :disabled="followingBusy"
                                    @click="follow"
                                >
                                    Follow
                                </Button>

                                <Button
                                    v-else
                                    type="button"
                                    class="rounded-md bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-900 hover:bg-gray-300 dark:bg-gray-700 dark:text-gray-100 dark:hover:bg-gray-600"
                                    :loading="followingBusy"
                                    :disabled="followingBusy"
                                    @click="unfollow"
                                >
                                    Unfollow
                                </Button>
                            </div>
                        </div>

                        <div class="mt-2 ml-4 flex items-center gap-1 text-lg font-medium text-gray-800 dark:text-gray-200">
                            <Star class="h-4 w-4 text-yellow-500" />
                            <span> {{ Number(props.ratingAvg ?? 0).toFixed(1) }} </span>
                        </div>

                    </CardHeader>
                </Card>

                <!-- XP Progress -->
                <Card class="relative overflow-hidden border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Level Progress
                        </CardTitle>
                        <CardDescription>
                            {{ formatXP(levelProgress.progressXP) }} / {{ formatXP(levelProgress.totalNeeded) }} XP to next level
                        </CardDescription>
                    </CardHeader>

                    <img
                        src="/assets/images/bugs.png"
                        alt="bug"
                        class="pointer-events-none select-none absolute bottom-4 right-12 w-[164px] h-auto origin-bottom-right drop-shadow-[0_-10px_15px_rgba(0,0,0,0.2)] dark:drop-shadow-[0_-10px_15px_rgba(255,255,255,0.1)]"
                    />

                    <CardContent>
                        <div class="space-y-2">
                            <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-3 rounded-full bg-purple-600 transition-all duration-300 ease-in-out"
                                    :style="`width: ${levelProgress.percentage}%`"
                                ></div>
                            </div>
                            <div class="flex justify-between text-xs text-muted-foreground">
                                <span>Level {{ user.level }}</span>
                                <span v-if="user.level < 9">{{ levelProgress.percentage }}%</span>
                                <span v-if="user.level < 9">Level {{ user.level + 1 }}</span>
                                <span v-else>Max Level</span>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- Skills Section -->
                <div class="space-y-6">
                    <!-- Skills organized by categories -->
                    <Card v-if="user.skills && user.skills.length > 0" class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                        <CardHeader>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Star class="h-5 w-5" />
                                        Skills & Experience
                                    </CardTitle>
                                    <CardDescription>{{
                                        isOwner ? 'Sync your skills from GitHub repositories!' : `${user.name} hasn't earned any skills yet.`
                                    }}</CardDescription>
                                </div>
                                <Button
                                    v-if="isOwner"
                                    @click="syncGitHubSkills"
                                    :disabled="syncing"
                                    class="inline-flex items-center gap-2"
                                    variant="button"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent class="space-y-6">
                            <!-- Skill Categories -->
                            <div v-for="(skills, type) in skillsByType" :key="type" class="space-y-3">
                                <div class="rounded-lg border-gray-200 p-4 dark:border-white/10">
                                    <div class="mb-4 flex items-center gap-2">
                                        <component :is="getTypeIcon(type)" class="h-5 w-5 text-purple-600" />
                                        <h3 class="text-lg font-semibold">{{ getTypeDisplayName(type) }}</h3>
                                        <Badge variant="custom" class="ml-auto rounded-lg py-1">
                                            {{ skills.length }} {{ skills.length === 1 ? 'skill' : 'skills' }}
                                        </Badge>
                                    </div>
                                    <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                        <div
                                            v-for="skill in skills"
                                            :key="skill.skill_name"
                                            class="flex items-center justify-between rounded-lg border border-gray-200 p-3 transition-colors hover:bg-white/90 dark:border-white/10 dark:hover:bg-white/10"
                                        >
                                            <div class="min-w-0 flex-1">
                                                <h4 class="truncate font-medium">{{ skill.skill_name }}</h4>
                                                <p class="text-sm text-muted-foreground">{{ formatXP(skill.xp) }} XP</p>
                                            </div>
                                            <Badge
                                                variant="secondary"
                                                class="ml-2 bg-blue-100 font-semibold text-purple-800 dark:bg-purple-900 dark:text-purple-200"
                                            >
                                                L{{ skill.level }}
                                            </Badge>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- No Skills Message with GitHub Sync -->
                    <Card v-else class="border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                        <CardHeader>
                            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <CardTitle class="flex items-center gap-2">
                                        <Star class="h-5 w-5" />
                                        Skills & Experience
                                    </CardTitle>
                                    <CardDescription>Sync your skills from GitHub repositories!</CardDescription>
                                </div>
                                <Button
                                    v-if="isOwner"
                                    @click="syncGitHubSkills"
                                    :disabled="syncing"
                                    class="inline-flex items-center gap-2"
                                    variant="button"
                                >
                                    <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                        <path
                                            fill-rule="evenodd"
                                            d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                            clip-rule="evenodd"
                                        ></path>
                                    </svg>
                                    {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent>
                            <div class="py-8 text-center text-muted-foreground">
                                <Star class="mx-auto mb-4 h-12 w-12 opacity-50" />
                                <p>
                                    {{
                                        isOwner
                                            ? 'No skills earned yet. Sync from GitHub or complete your first bounty to start earning XP!'
                                            : `${user.name} hasn't completed any bounties yet.`
                                    }}
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card v-if="!isOwner && canReview" class="rounded-xl border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MessageSquareMore class="h-5 w-5" />
                            Write a review for {{ user.name }}
                        </CardTitle>
                        <CardDescription> Share your experience collaborating with {{ user.nickname }} on an issue! </CardDescription>
                    </CardHeader>

                    <ReviewForm :reviewee-id="user.id" class="px-6 pb-6" />
                    <ReviewList :reviews="props.reviews" class="px-6" />
                </Card>

                <Card v-else class="rounded-xl border-gray-200 bg-white/40 dark:border-white/10 dark:bg-white/5">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <MessageSquareMore class="h-5 w-5" />
                            {{ isOwner ? 'Your reviews' : `${user.name}'s reviews` }}
                        </CardTitle>

                        <CardDescription v-if="!isOwner && !canReview">
                            You can write a review after at least one submission has been shared between you and {{ user.name }}.
                        </CardDescription>
                    </CardHeader>

                    <ReviewList :reviews="props.reviews" class="px-6" />
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
