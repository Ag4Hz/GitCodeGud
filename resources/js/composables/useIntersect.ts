import { onMounted, onUnmounted, watch, type Ref } from 'vue';

export function useIntersect(
    elRef: Ref<Element | null>,
    callback: () => void,
    options: IntersectionObserverInit = {}
): void {
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) callback();
        });
    }, options);

    onMounted(() => {
        if (elRef.value) observer.observe(elRef.value);
    });

    watch(elRef, (el) => {
        if (el) observer.observe(el);
    });

    onUnmounted(() => observer.disconnect());
}
