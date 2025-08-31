<script setup lang="ts">
import { Dialog, DialogClose, DialogContent, DialogTrigger, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Users } from 'lucide-vue-next';

import { useInfiniteScroll} from '@/composables/useInfiniteScroll';
import { useIntersect } from '@/composables/useIntersect';
import { ref } from 'vue';

const props = defineProps<{
    propName: string;
    title?: string
    count?: number
}>();

const { items, loadMoreItems} = useInfiniteScroll(props.propName);

const landmark = ref<HTMLElement | null>(null);

useIntersect(landmark, loadMoreItems, {
    rootMargin: '0px 0px 150px 0px',
});

</script>

<template>
    <Dialog>
        <DialogTrigger>
            <button class="flex cursor-pointer items-center gap-1 text-sm font-medium">
                <Users class="h-4 w-4 text-green-700" />
                <span class="inline-flex items-center gap-1.5">
                  <span>{{ count }}</span>
                  <span>{{ title }}</span>
                </span>
            </button>
        </DialogTrigger>

        <DialogContent>
            <DialogTitle>{{ title }}</DialogTitle>
            <DialogDescription />
            <ul class="max-h-64 overflow-y-auto">
                <li v-for="item in items" :key="item.id">
                    {{ item.nickname }}
                </li>
                <li ref="landmark" class="h-8"></li>
            </ul>

            <DialogClose>
                <button>Close</button>
            </DialogClose>
        </DialogContent>
    </Dialog>
</template>
