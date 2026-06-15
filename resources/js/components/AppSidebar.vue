<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { FileSearchCorner, LayoutDashboard, MessageCircleWarning, MonitorCog } from '@lucide/vue';
import {
    Ambulance,
    ArrowRightLeft,
    BedDouble,
    ChevronRight,
    CalendarClock,
    Cable,
    ClipboardList,
    ClipboardPlus,
    IdCard,
    ShieldCheck,
    Stethoscope,
    UserRoundSearch,
    UsersRound,
} from '@lucide/vue';
import { computed } from 'vue';
import { index as doctorAccountIndex } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunDokterController';
import { index as staffAccountIndex } from '@/actions/App/Http/Controllers/ManajemenPegawai/AkunPetugasController';
import { index as practiceScheduleIndex } from '@/actions/App/Http/Controllers/ManajemenPegawai/JadwalPraktekController';
import { index as userAccessIndex } from '@/actions/App/Http/Controllers/ManajemenUser/AksesUserController';
import { index as userGroupIndex } from '@/actions/App/Http/Controllers/ManajemenUser/GrupUserController';
import { index as patientRegistrationIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DaftarPasienController';
import { index as patientDataIndex } from '@/actions/App/Http/Controllers/Pendaftaran/DataPasienController';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { index as rmeIgdIndex } from '@/routes/rme/igd';
import { index as rmeRawatInapIndex } from '@/routes/rme/rawat-inap';
import { index as rmeRawatJalanIndex } from '@/routes/rme/rawat-jalan';
import { index as rmeRujukanInternalIndex } from '@/routes/rme/rujukan-internal';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuAction,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
    useSidebar,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const { isCurrentUrl, isCurrentOrParentUrl } = useCurrentUrl();
const { state } = useSidebar();

// Computed: apakah sidebar sedang dalam mode collapsed (icon only)
const isCollapsed = computed(() => state.value === 'collapsed');

const mainNavItems: NavItem[] = [
    {
        title: 'Beranda',
        href: dashboard(),
        icon: LayoutDashboard,
    },
];

interface NavGroup {
    title: string;
    href: string;
    icon: any;
    parentPath: string;
    items: NavItem[];
}

const navGroups: NavGroup[] = [
    {
        title: 'Pendaftaran',
        href: '#',
        icon: ClipboardPlus,
        parentPath: '/pendaftaran',
        items: [
            {
                title: 'Daftar Pasien',
                href: patientRegistrationIndex(),
                icon: ClipboardPlus,
            },
            {
                title: 'Data Pasien',
                href: patientDataIndex(),
                icon: UserRoundSearch,
            },
        ],
    },
    {
        title: 'RME',
        href: '#',
        icon: ClipboardList,
        parentPath: '/rme',
        items: [
            {
                title: 'Rawat Jalan',
                href: rmeRawatJalanIndex(),
                icon: Stethoscope,
            },
            {
                title: 'Rawat Inap',
                href: rmeRawatInapIndex(),
                icon: BedDouble,
            },
            {
                title: 'IGD',
                href: rmeIgdIndex(),
                icon: Ambulance,
            },
            {
                title: 'Rujukan Internal',
                href: rmeRujukanInternalIndex(),
                icon: ArrowRightLeft,
            },
        ],
    },
    {
        title: 'Manajemen Pegawai',
        href: '#',
        icon: IdCard,
        parentPath: '/manajemen-pegawai',
        items: [
            {
                title: 'Akun Dokter',
                href: doctorAccountIndex(),
                icon: Stethoscope,
            },
            {
                title: 'Akun Petugas',
                href: staffAccountIndex(),
                icon: IdCard,
            },
            {
                title: 'Jadwal Praktek',
                href: practiceScheduleIndex(),
                icon: CalendarClock,
            },
        ],
    },
    {
        title: 'Manajemen User',
        href: '#',
        icon: UsersRound,
        parentPath: '/manajemen-user',
        items: [
            {
                title: 'User Group',
                href: userGroupIndex(),
                icon: ShieldCheck,
            },
            {
                title: 'User Akses',
                href: userAccessIndex(),
                icon: UsersRound,
            },
        ],
    },
    {
        title: 'Integrasi Eksternal',
        href: '#',
        icon: Cable,
        parentPath: '/integrasi-eksternal',
        items: [
            {
                title: 'BPJS',
                href: bpjsIndex(),
                icon: Cable,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Pengaturan Aplikasi',
        href: '#',
        icon: MonitorCog,
    },
    {
        title: 'Feedback & Laporan Bug',
        href: '#',
        icon: MessageCircleWarning,
    },
    {
        title: 'Dokumentasi Aplikasi',
        href: '#',
        icon: FileSearchCorner,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain :items="mainNavItems" />

            <SidebarMenu class="px-2">
                <template v-for="group in navGroups" :key="group.title">

                    <!-- ============================================================ -->
                    <!-- MODE COLLAPSED: Tampilkan DropdownMenu sebagai popover       -->
                    <!-- ============================================================ -->
                    <template v-if="isCollapsed">
                        <SidebarMenuItem>
                            <DropdownMenu>
                                <DropdownMenuTrigger as-child>
                                    <SidebarMenuButton :is-active="isCurrentOrParentUrl(group.parentPath)"
                                        :tooltip="group.title">
                                        <component :is="group.icon" />
                                        <span>{{ group.title }}</span>
                                    </SidebarMenuButton>
                                </DropdownMenuTrigger>

                                <DropdownMenuContent side="right" align="start" class="min-w-48">
                                    <DropdownMenuLabel class="text-xs text-muted-foreground font-medium">
                                        {{ group.title }}
                                    </DropdownMenuLabel>
                                    <DropdownMenuSeparator />

                                    <DropdownMenuItem v-for="item in group.items" :key="item.title" as-child
                                        :class="isCurrentUrl(item.href) ? 'bg-accent text-accent-foreground' : ''">
                                        <Link :href="item.href" class="flex items-center gap-2 w-full">
                                            <component :is="item.icon" class="size-4 shrink-0" />
                                            <span>{{ item.title }}</span>
                                        </Link>
                                    </DropdownMenuItem>
                                </DropdownMenuContent>
                            </DropdownMenu>
                        </SidebarMenuItem>
                    </template>

                    <!-- ============================================================ -->
                    <!-- MODE EXPANDED: Collapsible biasa dengan submenu              -->
                    <!-- ============================================================ -->
                    <template v-else>
                        <Collapsible as-child :default-open="isCurrentOrParentUrl(group.parentPath)"
                            class="group/collapsible">
                            <SidebarMenuItem>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuButton :is-active="isCurrentOrParentUrl(group.parentPath)"
                                        :tooltip="group.title">
                                        <component :is="group.icon" />
                                        <span>{{ group.title }}</span>
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleTrigger as-child>
                                    <SidebarMenuAction>
                                        <ChevronRight
                                            class="transition-transform group-data-[state=open]/collapsible:rotate-90" />
                                        <span class="sr-only">Buka menu {{ group.title }}</span>
                                    </SidebarMenuAction>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        <SidebarMenuSubItem v-for="item in group.items" :key="item.title">
                                            <SidebarMenuSubButton as-child :is-active="isCurrentUrl(item.href)">
                                                <Link :href="item.href">
                                                    <component :is="item.icon" />
                                                    <span>{{ item.title }}</span>
                                                </Link>
                                            </SidebarMenuSubButton>
                                        </SidebarMenuSubItem>
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    </template>

                </template>
            </SidebarMenu>
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
