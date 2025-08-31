import { ref } from 'vue';
import { router, usePage } from '@inertiajs/vue3';

export function useInfiniteScroll(propName: string) {
    const page = usePage() as any;
    const value = () => page.props[propName] as { data: any[]; next_page_url?: string | null };
    const items = ref(value().data ?? []);

    const initialUrl = page.url;

    const loadMoreItems = () => {
        const v = value();
        if (!v.next_page_url) return;

        router.get(
            v.next_page_url,
            {},
            {
                only: [propName],
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    window.history.replaceState({}, '', initialUrl);
                    items.value = [...items.value, ...value().data];
                },
            },
        );
    };

    return { items, loadMoreItems };
}
