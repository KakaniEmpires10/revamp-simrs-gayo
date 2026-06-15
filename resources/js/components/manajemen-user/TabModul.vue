<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { KeyRound, ShieldCheck, UsersRound } from '@lucide/vue';
import { computed } from 'vue';
import { index as userAccessIndex } from '@/actions/App/Http/Controllers/ManajemenUser/AksesUserController';
import { index as userGroupIndex } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
import { useCurrentUrl } from '@/composables/useCurrentUrl';

const props = defineProps<{
    permissionHref?: string;
}>();

const { isCurrentUrl } = useCurrentUrl();

const items = computed(() => [
    {
        title: 'User Group',
        href: userGroupIndex(),
        icon: ShieldCheck,
        active: isCurrentUrl(userGroupIndex().url),
    },
    {
        title: 'Permission Group',
        href: props.permissionHref ?? userGroupIndex(),
        icon: KeyRound,
        active: props.permissionHref ? isCurrentUrl(props.permissionHref) : false,
        disabled: !props.permissionHref,
    },
    {
        title: 'User Akses',
        href: userAccessIndex(),
        icon: UsersRound,
        active: isCurrentUrl(userAccessIndex().url),
    },
]);
</script>

<template>
    <nav class="flex flex-wrap gap-2">
        <Link
            v-for="item in items"
            :key="item.title"
            :href="item.href"
            class="inline-flex h-9 items-center gap-2 rounded-md border px-3 text-sm font-medium transition-colors"
            :class="[
                item.active
                    ? 'border-primary bg-primary text-primary-foreground'
                    : 'border-border bg-background text-muted-foreground hover:bg-muted hover:text-foreground',
                item.disabled ? 'pointer-events-none opacity-50' : '',
            ]"
        >
            <component :is="item.icon" class="size-4" />
            {{ item.title }}
        </Link>
    </nav>
</template>
