import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';
import type { Platform } from '@/types/ui';

export function usePlatform() {
    const page = usePage();

    const platform = computed(() => page.props.platform as Platform);
    const isTouch = computed(() => platform.value === 'touch');
    const isDesktop = computed(() => platform.value === 'desktop');

    return { isTouch, isDesktop, platform };
}
