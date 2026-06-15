import type { Page } from '@inertiajs/core';
import { usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { toast } from 'vue-sonner';
import type { FeedbackMode, FlashToast } from '@/types/ui';

type FeedbackState = FlashToast & {
    open: boolean;
};

const alertState = reactive<FeedbackState>({
    open: false,
    type: 'info',
    message: '',
});

function normalizedMode(mode: unknown): FeedbackMode {
    return mode === 'toast' ? 'toast' : 'alert';
}

export const feedbackProps = ['flash', 'feedbackMode'] as const;

export function feedbackOnly<T extends string>(
    props: readonly T[],
): Array<T | (typeof feedbackProps)[number]> {
    return Array.from(new Set([...props, ...feedbackProps]));
}

export function feedbackToastFromPage(page: Page): FlashToast | undefined {
    return (page.props.flash as { toast?: FlashToast } | undefined)?.toast;
}

export function isFeedbackSuccess(page: Page): boolean {
    return feedbackToastFromPage(page)?.type === 'success';
}

export function showFeedback(data: FlashToast, mode: unknown = 'alert'): void {
    if (normalizedMode(mode) === 'toast') {
        toast[data.type](data.message);

        return;
    }

    alertState.type = data.type;
    alertState.message = data.message;
    alertState.open = true;
}

export function useFeedbackAlertState(): FeedbackState {
    return alertState;
}

export function useFeedback() {
    const page = usePage();
    const feedbackMode = computed(() => normalizedMode(page.props.feedbackMode));

    function dispatch(type: FlashToast['type'], message: string): void {
        showFeedback({ type, message }, feedbackMode.value);
    }

    return {
        feedbackMode,
        success: (message: string) => dispatch('success', message),
        info: (message: string) => dispatch('info', message),
        warning: (message: string) => dispatch('warning', message),
        error: (message: string) => dispatch('error', message),
    };
}
