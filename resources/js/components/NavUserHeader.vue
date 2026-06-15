<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import {
    Avatar,
    AvatarFallback,
    AvatarImage,
} from '@/components/ui/avatar';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import UserMenuContent from '@/components/UserMenuContent.vue';

const page = usePage();
const user = computed(() => page.props.auth.user);

const userInitials = computed(() => {
    if (!user.value?.name) {
        return '?';
    }

    return user.value.name
        .split(' ')
        .map((n: string) => n[0])
        .slice(0, 2)
        .join('')
        .toUpperCase();
});
</script>

<template>
    <div class="hidden md:block">
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <button
                    class="flex items-center gap-2 rounded-full p-1 transition-colors duration-300 hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-ring data-[state=open]:bg-accent">
                    <Avatar class="size-9 border-2 border-muted">
                        <!-- <AvatarImage :src="user?.avatar ?? ''" :alt="user?.name ?? 'User'" /> -->
                        <AvatarImage src="https://cdn.vectorstock.com/i/500p/22/63/a-simple-black-silhouette-of-man-in-suit-vector-55942263.jpg" />
                        <AvatarFallback class="text-xs font-semibold">
                            {{ userInitials }}
                        </AvatarFallback>
                    </Avatar>

                    <!-- <span class="max-w-30 truncate text-sm font-medium">
                        {{ user?.name }}
                    </span> -->
                </button>
            </DropdownMenuTrigger>

            <DropdownMenuContent class="min-w-56 rounded-lg" align="end" :side-offset="8">
                <UserMenuContent :user="user" />
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>