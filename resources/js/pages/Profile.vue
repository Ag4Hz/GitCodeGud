<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { Avatar, AvatarFallback, AvatarImage } from '@/components/ui/avatar';
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { useInitials } from '@/composables/useInitials';
import { useXP } from '@/composables/useXP';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem, type User } from '@/types';
import { Trophy, Zap, Star, Target } from 'lucide-vue-next';
import { computed } from 'vue';

interface UserWithXP extends Omit<User, 'skills'> {
    total_xp: number;
    level: number;
    current_level_xp: number;
    next_level_xp: number;
    progress_percentage: number;
    skills: Array<{
        skill_name: string;
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
        percentage: props.user.progress_percentage ?? 0
    };
});
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
                                    <AvatarFallback class="rounded-lg bg-neutral-200 font-semibold text-black dark:bg-neutral-700 dark:text-white text-2xl">
                                        {{ getInitials(user.name) }}
                                    </AvatarFallback>
                                </Avatar>

                                <!-- Large XP Level Badge -->
                                <div class="absolute -bottom-2 -right-2 flex items-center justify-center">
                                    <Badge variant="secondary" class="h-8 min-w-8 px-2 text-sm font-bold bg-orange-500 text-white border-3 border-white dark:border-gray-900 shadow-lg">
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
                            <div class="w-full bg-gray-200 rounded-full h-3 dark:bg-gray-700">
                                <div class="bg-blue-600 h-3 rounded-full transition-all duration-300 ease-in-out" :style="`width: ${levelProgress.percentage}%`"></div>
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

                <!-- Skills -->
                <Card v-if="user.skills && user.skills.length > 0">
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Star class="h-5 w-5" />
                            Skills & Experience
                        </CardTitle>
                        <CardDescription>Your programming skills and experience points</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="grid gap-4 md:grid-cols-2">
                            <div v-for="skill in user.skills" :key="skill.skill_name" class="flex items-center justify-between rounded-lg border p-4">
                                <div>
                                    <h4 class="font-medium">{{ skill.skill_name }}</h4>
                                    <p class="text-sm text-muted-foreground">{{ formatXP(skill.xp) }} XP</p>
                                </div>
                                <Badge variant="outline" class="font-semibold">
                                    Level {{ skill.level }}
                                </Badge>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <!-- No Skills Message -->
                <Card v-else>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Star class="h-5 w-5" />
                            Skills & Experience
                        </CardTitle>
                        <CardDescription>Start contributing to projects to gain XP and unlock skills!</CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div class="text-center py-8 text-muted-foreground">
                            <Star class="h-12 w-12 mx-auto mb-4 opacity-50" />
                            <p>No skills earned yet. Complete your first bounty to start earning XP!</p>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
