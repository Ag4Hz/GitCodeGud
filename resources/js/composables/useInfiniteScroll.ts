import { router, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

type ListProp = { data: any[]; next_page_url?: string | null };

export function useInfiniteScroll(propName: string) {
    const page = usePage() as any;

    const source = computed<ListProp | undefined>(() => page.props?.[propName] as ListProp | undefined);

    const items = ref<any[]>(source.value?.data ?? []);
    const initialUrl = page.url;
    const isPaginating = ref(false);

    watch(
        () => source.value,
        (val) => {
            if (!isPaginating.value) items.value = val?.data ?? [];
        },
    );

    const loadMoreItems = () => {
        const next_page_url = source.value?.next_page_url;
        if (!next_page_url) return;

        isPaginating.value = true;

        router.get(
            next_page_url,
            {},
            {
                only: [propName],
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    window.history.replaceState({}, '', initialUrl);
                    items.value = [...items.value, ...(source.value?.data ?? [])];
                },
                onFinish: () => {
                    isPaginating.value = false;
                },
            },
        );
    };

    return { items, loadMoreItems };
}
