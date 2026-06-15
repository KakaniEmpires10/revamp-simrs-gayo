<script setup lang="ts">
import { Bell } from '@lucide/vue';
import { ref, computed } from 'vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
    DropdownMenuLabel,
    DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu';

// Tipe notifikasi
interface Notification {
    id: number;
    title: string;
    message: string;
    time: string;
    read: boolean;
    type: 'info' | 'warning' | 'success';
}

// Contoh data Ã¢â‚¬â€ ganti dengan data dari API/props sesuai kebutuhan
const notifications = ref<Notification[]>([
    {
        id: 1,
        title: 'Pasien Baru Terdaftar',
        message: 'Ahmad Fauzi berhasil didaftarkan ke poli umum.',
        time: '2 menit lalu',
        read: false,
        type: 'success',
    },
    {
        id: 2,
        title: 'Jadwal Operasi',
        message: 'Operasi dijadwalkan besok pukul 08.00 WIB.',
        time: '1 jam lalu',
        read: false,
        type: 'warning',
    },
    {
        id: 3,
        title: 'Laporan Bulanan',
        message: 'Laporan bulan Mei sudah tersedia untuk diunduh.',
        time: '3 jam lalu',
        read: true,
        type: 'info',
    },
]);

const unreadCount = computed(() => notifications.value.filter((n) => !n.read).length);

function markAllRead() {
    notifications.value.forEach((n) => (n.read = true));
}

function markRead(id: number) {
    const notif = notifications.value.find((n) => n.id === id);

    if (notif) {
        notif.read = true;
    }
}

const typeColor: Record<Notification['type'], string> = {
    info: 'bg-blue-500',
    warning: 'bg-amber-500',
    success: 'bg-emerald-500',
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                class="relative flex size-9 items-center justify-center rounded-full transition-colors hover:bg-accent focus:outline-none focus-visible:ring-2 focus-visible:ring-ring"
                aria-label="Notifikasi">
                <Bell class="size-[18px]" />
                <!-- Badge unread -->
                <span v-if="unreadCount > 0"
                    class="absolute right-1.5 top-1.5 flex size-4 items-center justify-center rounded-full bg-red-500 text-[10px] font-bold leading-none text-white">
                    {{ unreadCount > 9 ? '9+' : unreadCount }}
                </span>
            </button>
        </DropdownMenuTrigger>

        <DropdownMenuContent align="end" class="w-80 rounded-lg" :side-offset="8">
            <!-- Header -->
            <div class="flex items-center justify-between px-3 py-2">
                <DropdownMenuLabel class="p-0 text-sm font-semibold">
                    Notifikasi
                    <span v-if="unreadCount > 0"
                        class="ml-1.5 inline-flex size-5 items-center justify-center rounded-full bg-primary text-[11px] font-bold text-primary-foreground">
                        {{ unreadCount }}
                    </span>
                </DropdownMenuLabel>
                <button v-if="unreadCount > 0" @click.stop="markAllRead"
                    class="text-xs text-muted-foreground transition-colors hover:text-foreground">
                    Tandai semua dibaca
                </button>
            </div>

            <DropdownMenuSeparator />

            <!-- List notifikasi -->
            <div class="max-h-72 overflow-y-auto">
                <template v-if="notifications.length > 0">
                    <DropdownMenuItem v-for="notif in notifications" :key="notif.id" @click="markRead(notif.id)"
                        class="flex cursor-pointer items-start gap-3 px-3 py-2.5 focus:bg-accent"
                        :class="!notif.read ? 'bg-accent/40' : ''">
                        <!-- Dot tipe -->
                        <span class="mt-1.5 size-2 shrink-0 rounded-full" :class="typeColor[notif.type]" />
                        <div class="flex-1 space-y-0.5 overflow-hidden">
                            <p class="truncate text-sm font-medium leading-snug">
                                {{ notif.title }}
                            </p>
                            <p class="line-clamp-2 text-xs text-muted-foreground">
                                {{ notif.message }}
                            </p>
                            <p class="text-[11px] text-muted-foreground/70">
                                {{ notif.time }}
                            </p>
                        </div>
                        <!-- Unread dot -->
                        <span v-if="!notif.read" class="mt-2 size-1.5 shrink-0 rounded-full bg-primary" />
                    </DropdownMenuItem>
                </template>

                <div v-else class="px-3 py-8 text-center text-sm text-muted-foreground">
                    Tidak ada notifikasi
                </div>
            </div>

            <DropdownMenuSeparator />

            <!-- Footer -->
            <DropdownMenuItem as="a" href="/notifications"
                class="cursor-pointer justify-center text-xs text-muted-foreground hover:text-foreground">
                Lihat semua notifikasi
            </DropdownMenuItem>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
