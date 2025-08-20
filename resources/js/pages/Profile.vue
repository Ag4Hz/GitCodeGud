<script setup lang="ts">
import { Head, router } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { useXP } from '@/composables/useXP';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Trophy, Zap, Star, Target, Code, Database, Settings } from 'lucide-vue-next';
import { computed, ref } from 'vue';

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
}

interface Props {
    user: UserWithXP;
}

const props = defineProps<Props>();

const { getInitials } = useInitials();
const { formatXP } = useXP();

const syncing = ref(false);

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'My Profile',
        href: '/profile',
    },
];

const levelProgress = computed(() => {
    return {
        currentLevelXP: props.user.current_level_xp ?? 0,
        nextLevelXP: props.user.next_level_xp ?? 0,
        progressXP: props.user.total_xp - (props.user.current_level_xp ?? 0),
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
        },
    );
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="My Profile" />

        <div class="px-4 py-6">
            <div class="mx-auto max-w-4xl space-y-6">
                <!-- Profile Header -->
                <Card>
                    <CardHeader>
                        <div class="flex items-center gap-6">
                            <div class="relative">
                                <Avatar class="h-20 w-20 overflow-hidden rounded-lg">
                                    <AvatarImage v-if="user.avatar" :src="user.avatar" :alt="user.name" />
                                    <AvatarFallback
                                        class="rounded-lg bg-neutral-200 text-2xl font-semibold text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <!-- Large XP Level Badge -->
                                <div class="absolute -right-2 -bottom-2 flex items-center justify-center">
                                    <Badge
                                        variant="secondary"
                                        class="h-8 min-w-8 border-3 border-white bg-orange-500 px-2 text-sm font-bold text-white shadow-lg dark:border-gray-900"
                                    >
                                        {{ user.level }}
                                    </Badge>
                                </div>
                            </div>

                            <div class="flex-1">
                                <CardTitle class="text-2xl">{{ user.name }}</CardTitle>
                                <CardDescription class="text-base">{{ user.email }}</CardDescription>
                                <div class="mt-2 flex items-center gap-4">
                                    <div class="flex items-center gap-1 text-sm font-medium">
                                        <Trophy class="h-4 w-4 text-orange-500" />
                                        Level {{ user.level }}
                                    </div>
                                    <div class="flex items-center gap-1 text-sm font-medium">
                                        <Zap class="h-4 w-4 text-blue-500" />
                                        {{ formatXP(user.total_xp) }} XP
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardHeader>
                </Card>

                <!-- XP Progress -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Target class="h-5 w-5" />
                            Level Progress
                        </CardTitle>
                        <CardDescription>
                            {{ formatXP(levelProgress.progressXP) }} / {{ formatXP(levelProgress.totalNeeded) }} XP to next level
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="space-y-2">
                            <div class="h-3 w-full rounded-full bg-gray-200 dark:bg-gray-700">
                                <div
                                    class="h-3 rounded-full bg-blue-600 transition-all duration-300 ease-in-out"
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

                <!-- Skills organized by categories -->
                <Card v-if="user.skills && user.skills.length > 0">
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Star class="h-5 w-5" />
                                    Skills & Experience
                                </CardTitle>
                                <CardDescription>Your programming skills organized by category</CardDescription>
                            </div>
                            <button
                                @click="syncGitHubSkills"
                                :disabled="syncing"
                                class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:outline-none disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <!-- Skill Categories -->
                        <div v-for="(skills, type) in skillsByType" :key="type" class="space-y-3">
                            <div class="rounded-lg border p-4">
                                <div class="mb-4 flex items-center gap-2">
                                    <component :is="getTypeIcon(type)" class="h-5 w-5 text-blue-600" />
                                    <h3 class="text-lg font-semibold">{{ getTypeDisplayName(type) }}</h3>
                                    <Badge variant="outline" class="ml-auto">
                                        {{ skills.length }} {{ skills.length === 1 ? 'skill' : 'skills' }}
                                    </Badge>
                                </div>
                                <div class="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                                    <div
                                        v-for="skill in skills"
                                        :key="skill.skill_name"
                                        class="flex items-center justify-between rounded-lg border p-3 transition-colors hover:bg-muted/50"
                                    >
                                        <div class="min-w-0 flex-1">
                                            <h4 class="truncate font-medium">{{ skill.skill_name }}</h4>
                                            <p class="text-sm text-muted-foreground">{{ formatXP(skill.xp) }} XP</p>
                                        </div>
                                        <Badge
                                            variant="secondary"
                                            class="ml-2 bg-blue-100 font-semibold text-blue-800 dark:bg-blue-900 dark:text-blue-200"
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
                <Card v-else>
                    <CardHeader>
                        <div class="flex items-center justify-between">
                            <div>
                                <CardTitle class="flex items-center gap-2">
                                    <Star class="h-5 w-5" />
                                    Skills & Experience
                                </CardTitle>
                                <CardDescription>Sync your skills from GitHub repositories!</CardDescription>
                            </div>
                            <button
                                @click="syncGitHubSkills"
                                :disabled="syncing"
                                class="inline-flex items-center gap-2 rounded-md bg-gray-900 px-3 py-2 text-sm font-medium text-white transition-colors hover:bg-gray-800 focus:ring-2 focus:ring-gray-500 focus:outline-none disabled:opacity-50"
                            >
                                <svg class="h-4 w-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path
                                        fill-rule="evenodd"
                                        d="M10 0C4.477 0 0 4.484 0 10.017c0 4.425 2.865 8.18 6.839 9.504.5.092.682-.217.682-.483 0-.237-.008-.868-.013-1.703-2.782.605-3.369-1.343-3.369-1.343-.454-1.158-1.11-1.466-1.11-1.466-.908-.62.069-.608.069-.608 1.003.07 1.531 1.032 1.531 1.032.892 1.53 2.341 1.088 2.91.832.092-.647.35-1.088.636-1.338-2.22-.253-4.555-1.113-4.555-4.951 0-1.093.39-1.988 1.029-2.688-.103-.253-.446-1.272.098-2.65 0 0 .84-.27 2.75 1.026A9.564 9.564 0 0110 4.844c.85.004 1.705.115 2.504.337 1.909-1.296 2.747-1.027 2.747-1.027.546 1.379.203 2.398.1 2.651.64.7 1.028 1.595 1.028 2.688 0 3.848-2.339 4.695-4.566 4.942.359.31.678.921.678 1.856 0 1.338-.012 2.419-.012 2.747 0 .268.18.58.688.482A10.019 10.019 0 0020 10.017C20 4.484 15.522 0 10 0z"
                                        clip-rule="evenodd"
                                    ></path>
                                </svg>
                                {{ syncing ? 'Syncing...' : 'Sync from GitHub' }}
                            </button>
                        </div>
                    </CardHeader>
                    <CardContent>
                        <div class="py-8 text-center text-muted-foreground">
                            <Star class="mx-auto mb-4 h-12 w-12 opacity-50" />
                            <p>No skills earned yet. Sync from GitHub or complete your first bounty to start earning XP!</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
