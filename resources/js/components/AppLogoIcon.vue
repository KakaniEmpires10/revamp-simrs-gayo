<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <img
        src="/logo.png"
        alt="RSUD Gayo Lues SIMRS"
        :class="className"
        v-bind="$attrs"
    />
</template>
