<script setup lang="ts">
import { SidebarGroup, SidebarGroupContent, SidebarMenu, SidebarMenuButton, SidebarMenuItem } from '@/components/ui/sidebar';
import { type NavItem } from '@/types';

interface Props {
    items: NavItem[];
    class?: string;
}

defineProps<Props>();
</script>

<template>
    <div class="flex gap-4">
        <SidebarGroup :class="`group-data-[collapsible=icon]:p-0 ${$props.class || ''}`">
            <SidebarGroupContent>
                <SidebarMenu>
                    <!-- Iterate over the items and create a menu item for each -->
                    <SidebarMenuItem v-for="item in items" :key="item.title">
                        <SidebarMenuButton class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100" as-child>
                            <a :href="item.href" target="_blank" rel="noopener noreferrer">
                                <component :is="item.icon" />
                                <span>{{ item.title }}</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>

        <SidebarGroup>
            <SidebarGroupContent>
                <SidebarMenu>
                    <!-- FAQ Section -->
                    <SidebarMenuItem>
                        <SidebarMenuButton class="text-neutral-600 hover:text-neutral-800 dark:text-neutral-300 dark:hover:text-neutral-100" as-child>
                            <a href="/faq">
                                <span>FAQ</span>
                            </a>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarGroupContent>
        </SidebarGroup>
    </div>
</template>
