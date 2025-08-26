<script setup lang="ts">
import Heading from '@/components/Heading.vue';
import Icon from '@/components/Icon.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import { type BreadcrumbItem } from '@/types';
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

interface XPStats {
    total_users: number;
    users_with_xp: number;
    total_xp_distributed: number;
    average_xp: number;
    highest_xp: number;
    level_distribution: Record<number, number>;
}

interface XPConfig {
    base_xp: number;
    bonus_multiplier: number;
    skill_weights: Record<string, number>;
    level_thresholds: Record<number, number>;
}

interface Props {
    xpStats?: XPStats;
    xpConfig?: XPConfig;
}

const props = withDefaults(defineProps<Props>(), {
    xpStats: () => ({
        total_users: 0,
        users_with_xp: 0,
        total_xp_distributed: 0,
        average_xp: 0,
        highest_xp: 0,
        level_distribution: {},
    }),
    xpConfig: () => ({
        base_xp: 100,
        bonus_multiplier: 1.5,
        skill_weights: {},
        level_thresholds: { 1: 0 },
    }),
});

// XP Settings editing state
const editingXPSettings = ref(false);
const editableBaseXP = ref(0);
const editableBonusMultiplier = ref(0);
const editingXPField = ref<'base_xp' | 'bonus_multiplier' | null>(null);

// Threshold editing state
const editingThresholds = ref(false);
const editableThresholds = ref<number[]>([]);
const editingIndex = ref<number | null>(null);

// Skill weights editing state
const editingSkillWeights = ref(false);
const editableSkillWeights = ref<SkillWeight[]>([]);
const editingSkillIndex = ref<number | null>(null);

const xpSettingsForm = useForm({
    base_xp: 100,
    bonus_multiplier: 1.5,
});
const thresholdForm = useForm({
    thresholds: [] as number[],
});
const skillWeightsForm = useForm({
    skillWeights: [] as SkillWeight[],
});

const formatNumber = (num: number) => {
    return new Intl.NumberFormat().format(Math.round(num));
};

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Admin',
        href: '/admin',
    },
];

const sortedLevels = computed(() => {
    return Object.entries(props.xpStats.level_distribution).sort(([a], [b]) => Number(a) - Number(b));
});
const sortedLevelThresholds = computed(() => {
    return Object.entries(props.xpConfig.level_thresholds).sort(([a], [b]) => Number(a) - Number(b));
});

const sortedSkillWeights = computed(() => {
    return Object.entries(props.xpConfig.skill_weights).sort(([a], [b]) => a.localeCompare(b));
});

// XP Settings functions
const startEditingXPSettings = () => {
    editingXPSettings.value = true;
    editableBaseXP.value = props.xpConfig.base_xp;
    editableBonusMultiplier.value = props.xpConfig.bonus_multiplier;
};

const saveXPSettings = () => {
    xpSettingsForm.base_xp = editableBaseXP.value;
    xpSettingsForm.bonus_multiplier = editableBonusMultiplier.value;
    xpSettingsForm.post(route('admin.xp-settings.update'), {
        onSuccess: () => {
            editingXPSettings.value = false;
            editingXPField.value = null;
        },
    });
};

const cancelEditingXPSettings = () => {
    editingXPSettings.value = false;
    editingXPField.value = null;
    editableBaseXP.value = 0;
    editableBonusMultiplier.value = 0;
};

const startEditingXPField = (field: 'base_xp' | 'bonus_multiplier') => {
    editingXPField.value = field;
};

const finishEditingXPField = () => {
    editingXPField.value = null;
};

// Threshold Editing Methods
const startEditingThresholds = () => {
    editingThresholds.value = true;
    editableThresholds.value = Object.values(props.xpConfig.level_thresholds).map(Number);
};

const addNewThreshold = () => {
    const lastThreshold = Math.max(...editableThresholds.value);
    editableThresholds.value.push(lastThreshold + 1000);
};

const removeThreshold = (index: number) => {
    if (editableThresholds.value.length > 1) {
        editableThresholds.value.splice(index, 1);
    }
};

const saveThresholds = () => {
    thresholdForm.thresholds = [...editableThresholds.value];
    thresholdForm.post(route('admin.thresholds.update'), {
        onSuccess: () => {
            editingThresholds.value = false;
            editingIndex.value = null;
        },
    });
};

const cancelEditing = () => {
    editingThresholds.value = false;
    editingIndex.value = null;
    editableThresholds.value = [];
};

const startEditingValue = (index: number) => {
    editingIndex.value = index;
};

const finishEditingValue = () => {
    editingIndex.value = null;
};

// Skill weights functions
const startEditingSkillWeights = () => {
    editingSkillWeights.value = true;
    editableSkillWeights.value = Object.entries(props.xpConfig.skill_weights).map(([skill_name, multiplier]) => ({
        skill_name,
        multiplier: Number(multiplier),
    }));
};

const addNewSkillWeight = () => {
    editableSkillWeights.value.push({
        skill_name: 'new_skill',
        multiplier: 1.0,
    });
};

const removeSkillWeight = (index: number) => {
    if (editableSkillWeights.value.length > 1) {
        editableSkillWeights.value.splice(index, 1);
    }
};

const saveSkillWeights = () => {
    skillWeightsForm.skillWeights = [...editableSkillWeights.value];
    skillWeightsForm.post(route('admin.skill-weights.update'), {
        onSuccess: () => {
            editingSkillWeights.value = false;
            editingSkillIndex.value = null;
        },
    });
};

const cancelEditingSkillWeights = () => {
    editingSkillWeights.value = false;
    editingSkillIndex.value = null;
    editableSkillWeights.value = [];
};

const startEditingSkillValue = (index: number) => {
    editingSkillIndex.value = index;
};

const finishEditingSkillValue = () => {
    editingSkillIndex.value = null;
};
</script>

<template>
    <Head title="Admin" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="px-4 py-6">
            <div class="mx-auto max-w-6xl space-y-6">
                <!-- Header -->
                <Card>
                    <CardHeader>
                        <CardTitle class="flex items-center gap-2">
                            <Icon name="chart-bar" class="h-6 w-6" />
                            <Heading title="Admin Dashboard" />
                        </CardTitle>
                        <p class="text-sm text-muted-foreground">Monitor XP distribution and user statistics</p>
                    </CardHeader>
                </Card>

                <!-- XP Statistics -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Total Users -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total Users</CardTitle>
                            <Icon name="users" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_users) }}</div>
                            <p class="text-xs text-muted-foreground">{{ formatNumber(props.xpStats.users_with_xp) }} with XP earned</p>
                        </CardContent>
                    </Card>

                    <!-- Total XP Distributed -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Total XP Distributed</CardTitle>
                            <Icon name="zap" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.total_xp_distributed) }}</div>
                            <p class="text-xs text-muted-foreground">Avg: {{ formatNumber(props.xpStats.average_xp) }} per active user</p>
                        </CardContent>
                    </Card>

                    <!-- Highest XP -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Highest XP</CardTitle>
                            <Icon name="star" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div class="text-2xl font-bold">{{ formatNumber(props.xpStats.highest_xp) }}</div>
                            <p class="text-xs text-muted-foreground">Top performer</p>
                        </CardContent>
                    </Card>

                    <!-- Level Distribution -->
                    <Card>
                        <CardHeader class="flex flex-row items-center justify-between space-y-0 pb-2">
                            <CardTitle class="text-sm font-medium">Level Distribution</CardTitle>
                            <Icon name="list" class="h-4 w-4 text-muted-foreground" />
                        </CardHeader>
                        <CardContent>
                            <div
                                class="scrollbar-thin scrollbar-track-transparent scrollbar-thumb-muted-foreground/20 hover:scrollbar-thumb-muted-foreground/40 max-h-32 overflow-y-auto"
                            >
                                <div v-if="Object.keys(props.xpStats.level_distribution).length > 0" class="space-y-2 pr-2">
                                    <div v-for="[level, count] in sortedLevels" :key="level" class="flex items-center justify-between text-sm">
                                        <span class="flex items-center gap-2">
                                            <Badge variant="outline" class="flex h-6 w-8 items-center justify-center p-0 text-xs font-semibold">
                                                {{ level }}
                                            </Badge>
                                            <span>Level {{ level }}</span>
                                        </span>
                                        <Badge variant="secondary" class="font-medium">
                                            {{ count }}
                                        </Badge>
                                    </div>
                                </div>

                                <div v-else class="py-4 text-center text-muted-foreground">
                                    <Icon name="cube" class="mx-auto mb-2 h-6 w-6 opacity-50" />
                                    <p class="text-xs">No users with XP yet</p>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <!-- Fine tuning -->
                <!-- XP Configuration -->
                <div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                    <!-- XP Settings -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Icon name="settings" class="h-5 w-5" />
                                XP Settings
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <!-- Display Mode -->
                            <div v-if="!editingXPSettings" class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Base XP</span>
                                    <Badge variant="outline" class="font-mono">
                                        {{ formatNumber(props.xpConfig.base_xp) }}
                                    </Badge>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Bonus Multiplier</span>
                                    <Badge variant="outline" class="font-mono"> {{ props.xpConfig.bonus_multiplier }}x </Badge>
                                </div>
                            </div>

                            <!-- Edit Mode -->
                            <div v-else class="space-y-4">
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Base XP</span>
                                    <div class="flex items-center gap-1">
                                        <input
                                            v-if="editingXPField === 'base_xp'"
                                            v-model.number="editableBaseXP"
                                            @blur="finishEditingXPField"
                                            @keyup.enter="finishEditingXPField"
                                            class="h-6 w-20 rounded border px-1 text-center font-mono text-xs"
                                            type="number"
                                            min="1"
                                            max="10000"
                                            autofocus
                                        />
                                        <Badge
                                            v-else
                                            variant="outline"
                                            class="cursor-pointer font-mono hover:bg-secondary/80"
                                            @click="startEditingXPField('base_xp')"
                                        >
                                            {{ formatNumber(editableBaseXP) }}
                                        </Badge>
                                    </div>
                                </div>
                                <div class="flex items-center justify-between">
                                    <span class="text-sm font-medium">Bonus Multiplier</span>
                                    <div class="flex items-center gap-1">
                                        <input
                                            v-if="editingXPField === 'bonus_multiplier'"
                                            v-model.number="editableBonusMultiplier"
                                            @blur="finishEditingXPField"
                                            @keyup.enter="finishEditingXPField"
                                            class="h-6 w-20 rounded border px-1 text-center font-mono text-xs"
                                            type="number"
                                            min="0.1"
                                            max="10"
                                            step="0.1"
                                            autofocus
                                        />
                                        <Badge
                                            v-else
                                            variant="outline"
                                            class="cursor-pointer font-mono hover:bg-secondary/80"
                                            @click="startEditingXPField('bonus_multiplier')"
                                        >
                                            {{ editableBonusMultiplier }}x
                                        </Badge>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div v-if="!editingXPSettings" class="border-t pt-2">
                                <Button variant="outline" size="sm" class="w-full" @click="startEditingXPSettings">
                                    <Icon name="edit" class="mr-2 h-4 w-4" />
                                    Modify XP Settings
                                </Button>
                            </div>

                            <div v-else class="border-t pt-2">
                                <div class="flex gap-2">
                                    <Button variant="default" size="sm" class="flex-1" @click="saveXPSettings" :disabled="xpSettingsForm.processing">
                                        <Icon name="check" class="mr-2 h-4 w-4" />
                                        Save
                                    </Button>
                                    <Button variant="outline" size="sm" class="flex-1" @click="cancelEditingXPSettings">
                                        <Icon name="x" class="mr-2 h-4 w-4" />
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Level Thresholds -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Icon name="trending-up" class="h-5 w-5" />
                                Level Thresholds
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                class="scrollbar-thin scrollbar-track-transparent scrollbar-thumb-muted-foreground/20 hover:scrollbar-thumb-muted-foreground/40 max-h-48 overflow-y-auto"
                            >
                                <!-- Display Mode -->
                                <div v-if="!editingThresholds" class="space-y-2 pr-2">
                                    <div
                                        v-for="[level, threshold] in sortedLevelThresholds"
                                        :key="level"
                                        class="flex items-center justify-between text-sm"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Badge variant="outline" class="flex h-6 w-8 items-center justify-center p-0 text-xs font-semibold">
                                                {{ level }}
                                            </Badge>
                                            <span>Level {{ level }}</span>
                                        </span>
                                        <Badge variant="secondary" class="font-mono"> {{ formatNumber(Number(threshold)) }} XP </Badge>
                                    </div>
                                </div>

                                <!-- Edit Mode -->
                                <div v-else class="space-y-2 pr-2">
                                    <div
                                        v-for="(threshold, index) in editableThresholds"
                                        :key="index"
                                        class="flex items-center justify-between gap-2 text-sm"
                                    >
                                        <span class="flex items-center gap-2">
                                            <Badge variant="outline" class="flex h-6 w-8 items-center justify-center p-0 text-xs font-semibold">
                                                {{ index + 1 }}
                                            </Badge>
                                            <span>Level {{ index + 1 }}</span>
                                        </span>
                                        <div class="flex items-center gap-1">
                                            <input
                                                v-if="editingIndex === index"
                                                v-model.number="editableThresholds[index]"
                                                @blur="finishEditingValue"
                                                @keyup.enter="finishEditingValue"
                                                class="h-6 w-20 rounded border px-1 text-center font-mono text-xs"
                                                type="number"
                                                min="0"
                                                autofocus
                                            />
                                            <Badge
                                                v-else
                                                variant="secondary"
                                                class="cursor-pointer font-mono hover:bg-secondary/80"
                                                @click="startEditingValue(index)"
                                            >
                                                {{ formatNumber(threshold) }} XP
                                            </Badge>
                                            <Button
                                                v-if="editableThresholds.length > 1"
                                                variant="ghost"
                                                size="sm"
                                                class="h-6 w-6 p-0 hover:bg-destructive hover:text-destructive-foreground"
                                                @click="removeThreshold(index)"
                                            >
                                                <Icon name="x" class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div v-if="!editingThresholds" class="border-t pt-2">
                                <Button variant="outline" size="sm" class="w-full" @click="startEditingThresholds">
                                    <Icon name="edit" class="mr-2 h-4 w-4" />
                                    Modify Thresholds
                                </Button>
                            </div>

                            <div v-else class="space-y-2 border-t pt-2">
                                <Button variant="outline" size="sm" class="w-full" @click="addNewThreshold">
                                    <Icon name="plus" class="mr-2 h-4 w-4" />
                                    Add Level
                                </Button>
                                <div class="flex gap-2">
                                    <Button variant="default" size="sm" class="flex-1" @click="saveThresholds" :disabled="thresholdForm.processing">
                                        <Icon name="check" class="mr-2 h-4 w-4" />
                                        Save
                                    </Button>
                                    <Button variant="outline" size="sm" class="flex-1" @click="cancelEditing">
                                        <Icon name="x" class="mr-2 h-4 w-4" />
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>

                    <!-- Skill Weights -->
                    <Card>
                        <CardHeader>
                            <CardTitle class="flex items-center gap-2 text-base">
                                <Icon name="code" class="h-5 w-5" />
                                Skill Weights
                            </CardTitle>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div
                                class="scrollbar-thin scrollbar-track-transparent scrollbar-thumb-muted-foreground/20 hover:scrollbar-thumb-muted-foreground/40 max-h-48 overflow-y-auto"
                            >
                                <!-- Display Mode -->
                                <div v-if="!editingSkillWeights" class="space-y-2 pr-2">
                                    <div v-if="Object.keys(props.xpConfig.skill_weights).length > 0">
                                        <div
                                            v-for="[skill, weight] in sortedSkillWeights"
                                            :key="skill"
                                            class="flex items-center justify-between text-sm"
                                        >
                                            <span class="font-medium capitalize">{{ skill }}</span>
                                            <Badge variant="secondary" class="font-mono"> {{ weight }}x </Badge>
                                        </div>
                                    </div>
                                    <div v-else class="py-4 text-center text-muted-foreground">
                                        <Icon name="code" class="mx-auto mb-2 h-6 w-6 opacity-50" />
                                        <p class="text-xs">No skills configured</p>
                                    </div>
                                </div>

                                <!-- Edit Mode -->
                                <div v-else class="space-y-2 pr-2">
                                    <div
                                        v-for="(skillWeight, index) in editableSkillWeights"
                                        :key="index"
                                        class="flex items-center justify-between gap-2 text-sm"
                                    >
                                        <div class="flex flex-1 items-center gap-2">
                                            <input
                                                v-model="skillWeight.skill_name"
                                                class="h-6 flex-1 rounded border px-1 text-xs"
                                                type="text"
                                                placeholder="Skill name"
                                            />
                                        </div>
                                        <div class="flex items-center gap-1">
                                            <input
                                                v-if="editingSkillIndex === index"
                                                v-model.number="skillWeight.multiplier"
                                                @blur="finishEditingSkillValue"
                                                @keyup.enter="finishEditingSkillValue"
                                                class="h-6 w-16 rounded border px-1 text-center font-mono text-xs"
                                                type="number"
                                                min="0"
                                                max="10"
                                                step="0.1"
                                                autofocus
                                            />
                                            <Badge
                                                v-else
                                                variant="secondary"
                                                class="cursor-pointer font-mono hover:bg-secondary/80"
                                                @click="startEditingSkillValue(index)"
                                            >
                                                {{ skillWeight.multiplier }}x
                                            </Badge>
                                            <Button
                                                v-if="editableSkillWeights.length > 1"
                                                variant="ghost"
                                                size="sm"
                                                class="h-6 w-6 p-0 hover:bg-destructive hover:text-destructive-foreground"
                                                @click="removeSkillWeight(index)"
                                            >
                                                <Icon name="x" class="h-3 w-3" />
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Action Buttons -->
                            <div v-if="!editingSkillWeights" class="border-t pt-2">
                                <Button variant="outline" size="sm" class="w-full" @click="startEditingSkillWeights">
                                    <Icon name="edit" class="mr-2 h-4 w-4" />
                                    Modify Skill Weights
                                </Button>
                            </div>

                            <div v-else class="space-y-2 border-t pt-2">
                                <Button variant="outline" size="sm" class="w-full" @click="addNewSkillWeight">
                                    <Icon name="plus" class="mr-2 h-4 w-4" />
                                    Add Skill
                                </Button>
                                <div class="flex gap-2">
                                    <Button
                                        variant="default"
                                        size="sm"
                                        class="flex-1"
                                        @click="saveSkillWeights"
                                        :disabled="skillWeightsForm.processing"
                                    >
                                        <Icon name="check" class="mr-2 h-4 w-4" />
                                        Save
                                    </Button>
                                    <Button variant="outline" size="sm" class="flex-1" @click="cancelEditingSkillWeights">
                                        <Icon name="x" class="mr-2 h-4 w-4" />
                                        Cancel
                                    </Button>
                                </div>
                            </div>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
