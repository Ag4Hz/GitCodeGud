<script setup lang="ts">
import { Dialog, DialogClose, DialogContent, DialogTrigger, DialogTitle, DialogDescription } from '@/components/ui/dialog';
import { Users } from 'lucide-vue-next';
import { onMounted, ref, watch } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

const props = defineProps<{
    followers: {
        data: { id: number; nickname: string }[];
        next_page_url: string | null;
    };
    count?: number
}>();

const items = ref([...props.followers.data]);
const initialUrl = usePage().url;

const loadMoreItems = () => {
    if (!props.followers.next_page_url) {
        return;
    }

    router.get(
        props.followers.next_page_url,
        {},
        {
            only: ['followers'],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                window.history.replaceState({}, '', initialUrl);
                items.value = [...items.value, ...props.followers.data];
            },
        },
    );
};

const observer = new IntersectionObserver(
    (entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                loadMoreItems();
            }
        });
    },
    {
        rootMargin: '0px 0px 150px 0px',
    },
);

const landmark = ref<HTMLElement | null>(null);

onMounted(() => {
    if (landmark.value) observer.observe(landmark.value);
});
watch(landmark, (el) => {
    if (el) observer.observe(el);
});

</script>

<template>
    <Dialog>
        <DialogTrigger>
            <button class="flex cursor-pointer items-center gap-1 text-sm font-medium">
                <Users class="h-4 w-4 text-green-700" />
                <span class="inline-flex items-center gap-1.5">
                  <span>{{ count }}</span>
                  <span>Followers</span>
                </span>
            </button>
        </DialogTrigger>

        <DialogContent>
            <DialogTitle>Followers</DialogTitle>
            <DialogDescription />
            <ul class="max-h-64 overflow-y-auto">
                <li v-for="follower in items" :key="follower.id">
                    {{ follower.nickname }}
                </li>
                <li ref="landmark" class="h-8"></li>
            </ul>

            <DialogClose>
                <button>Close</button>
            </DialogClose>
        </DialogContent>
    </Dialog>
</template>
