import { computed, onMounted, onUnmounted, ref, toValue, watch } from 'vue';
import type { ComponentPublicInstance, MaybeRefOrGetter, Ref } from 'vue';

export type InfiniteScrollInstance = ComponentPublicInstance & {
    fetchNext?: () => void;
};

type UseManualInfiniteScrollOptions = {
    itemCount: MaybeRefOrGetter<number>;
    total: MaybeRefOrGetter<number>;
    loading?: MaybeRefOrGetter<boolean>;
    buffer?: number;
};

type UseManualInfiniteScrollReturn = {
    infiniteScroll: Ref<InfiniteScrollInstance | null>;
    isFetchingNext: Ref<boolean>;
    hasMore: Readonly<Ref<boolean>>;
    fetchNext: () => void;
    maybeFetchNext: () => void;
};

export function useScrollTabelManual(options: UseManualInfiniteScrollOptions): UseManualInfiniteScrollReturn {
    const infiniteScroll = ref<InfiniteScrollInstance | null>(null);
    const isFetchingNext = ref(false);
    const buffer = options.buffer ?? 180;

    const hasMore = computed(() => toValue(options.itemCount) < toValue(options.total));

    watch(
        () => toValue(options.itemCount),
        () => {
            isFetchingNext.value = false;
        },
    );

    function fetchNext(): void {
        if (toValue(options.loading) || isFetchingNext.value || !hasMore.value) {
            return;
        }

        isFetchingNext.value = true;
        infiniteScroll.value?.fetchNext?.();
    }

    function maybeFetchNext(): void {
        if (toValue(options.loading) || isFetchingNext.value || !hasMore.value) {
            return;
        }

        const scrollPosition = window.scrollY + window.innerHeight;
        const pageBottom = document.documentElement.scrollHeight;

        if (pageBottom - scrollPosition > buffer) {
            return;
        }

        fetchNext();
    }

    onMounted(() => {
        window.addEventListener('scroll', maybeFetchNext, { passive: true });
    });

    onUnmounted(() => {
        window.removeEventListener('scroll', maybeFetchNext);
    });

    return {
        infiniteScroll,
        isFetchingNext,
        hasMore,
        fetchNext,
        maybeFetchNext,
    };
}
