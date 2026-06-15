<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    DatabaseZap,
    LockKeyhole,
    ServerCog,
    ShieldCheck,
} from '@lucide/vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { dashboard } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div
        class="relative grid min-h-dvh overflow-hidden bg-sky-50 text-slate-950 selection:bg-primary selection:text-primary-foreground lg:grid-cols-[1.15fr_0.85fr] dark:bg-[#0b1020] dark:text-slate-100"
    >
        <div
            class="relative hidden min-h-dvh overflow-hidden border-r border-sky-200 bg-sky-100 lg:flex dark:border-cyan-300/10 dark:bg-[#07111f]"
        >
            <div class="auth-medical-grid absolute inset-0 opacity-70 dark:opacity-55" />
            <div class="auth-scan-line absolute inset-x-0 top-0 h-28 opacity-60 dark:opacity-100" />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_20%_18%,rgba(117,186,251,0.30),transparent_28%),radial-gradient(circle_at_86%_72%,rgba(44,88,238,0.16),transparent_32%),linear-gradient(135deg,rgba(240,249,255,0.86),rgba(219,234,254,0.94))] dark:bg-[radial-gradient(circle_at_20%_18%,rgba(117,186,251,0.28),transparent_28%),radial-gradient(circle_at_86%_72%,rgba(44,88,238,0.24),transparent_32%),linear-gradient(135deg,rgba(7,17,31,0.74),rgba(4,9,18,0.94))]"
            />
            <div
                class="absolute top-24 right-20 h-72 w-72 rounded-full border border-sky-300/30 bg-sky-300/20 blur-2xl dark:border-cyan-300/10 dark:bg-cyan-300/5"
            />
            <div
                class="absolute bottom-16 left-24 h-80 w-80 rounded-full border border-blue-500/10 bg-blue-500/15 blur-3xl dark:border-blue-500/10 dark:bg-blue-500/10"
            />

            <div
                class="relative z-10 flex min-h-dvh w-full flex-col justify-between p-10 xl:p-12"
            >
                <Link
                    :href="dashboard()"
                    class="flex items-center gap-4"
                >
                    <div
                        class="flex size-12 items-center justify-center rounded bg-white/70 ring-1 ring-sky-300/60 shadow-[0_0_28px_rgba(37,99,235,0.18)] dark:bg-cyan-300/10 dark:ring-cyan-300/25 dark:shadow-[0_0_28px_rgba(0,194,255,0.25)]"
                    >
                        <AppLogoIcon class="size-10 object-contain" />
                    </div>
                    <div>
                        <h1
                            class="text-xl leading-7 font-semibold tracking-tight text-blue-700 dark:text-cyan-200"
                        >
                            SIMRS Gayo Lues
                        </h1>
                        <p
                            class="text-xs leading-4 font-semibold tracking-[0.22em] text-slate-500 uppercase dark:text-cyan-100/55"
                        >
                            Pusat Layanan Kesehatan
                        </p>
                    </div>
                </Link>

                <div class="max-w-xl space-y-8">
                    <div
                        class="inline-flex items-center gap-2 rounded-full border border-cyan-300/20 bg-cyan-300/10 px-3 py-1"
                    >
                        <span
                            class="size-2 rounded-full bg-blue-600 shadow-[0_0_16px_rgba(37,99,235,0.55)] dark:bg-cyan-300 dark:shadow-[0_0_16px_rgba(117,209,255,0.9)]"
                        />
                        <span
                            class="text-xs leading-4 font-semibold tracking-[0.16em] text-blue-700 uppercase dark:text-cyan-200"
                        >
                            Sistem Siap - Terenkripsi
                        </span>
                    </div>

                    <div class="space-y-4">
                        <h2
                            class="text-4xl leading-tight font-bold tracking-tight text-slate-950 xl:text-5xl dark:text-white"
                        >
                            Sistem Informasi Manajemen Rumah Sakit Terpadu
                        </h2>
                        <p class="max-w-md text-base leading-7 text-slate-600 dark:text-slate-300">
                            Akses aman untuk alur pasien, data klinis, dan
                            administrasi pelayanan di RSUD Gayo Lues.
                        </p>
                    </div>

                    <div class="grid max-w-lg grid-cols-2 gap-4 pt-2">
                        <div
                            class="border-l-2 border-blue-500/45 bg-white/60 p-4 shadow-sm shadow-blue-900/5 dark:border-cyan-300/35 dark:bg-white/[0.04] dark:shadow-none"
                        >
                            <p
                                class="text-xs leading-4 font-semibold tracking-[0.16em] text-slate-500 uppercase dark:text-slate-400"
                            >
                                Status Layanan
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-blue-700 dark:text-cyan-200">
                                99.98%
                            </p>
                        </div>
                        <div
                            class="border-l-2 border-blue-500/45 bg-white/60 p-4 shadow-sm shadow-blue-900/5 dark:border-cyan-300/35 dark:bg-white/[0.04] dark:shadow-none"
                        >
                            <p
                                class="text-xs leading-4 font-semibold tracking-[0.16em] text-slate-500 uppercase dark:text-slate-400"
                            >
                                Modul Aktif
                            </p>
                            <p class="mt-1 text-2xl font-semibold text-blue-700 dark:text-cyan-200">
                                42 Unit
                            </p>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-3 gap-3 text-slate-500 dark:text-slate-400">
                    <div class="flex items-center gap-2">
                        <ShieldCheck class="size-4 text-blue-600 dark:text-cyan-200" />
                        <span
                            class="text-[11px] leading-4 font-semibold tracking-[0.16em] uppercase"
                            >Aman</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <DatabaseZap class="size-4 text-blue-600 dark:text-cyan-200" />
                        <span
                            class="text-[11px] leading-4 font-semibold tracking-[0.16em] uppercase"
                            >Terenkripsi</span
                        >
                    </div>
                    <div class="flex items-center gap-2">
                        <ServerCog class="size-4 text-blue-600 dark:text-cyan-200" />
                        <span
                            class="text-[11px] leading-4 font-semibold tracking-[0.16em] uppercase"
                            >Tercatat</span
                        >
                    </div>
                </div>
            </div>
        </div>

        <div
            class="relative flex min-h-dvh items-center justify-center bg-sky-50 px-6 py-10 lg:px-10 dark:bg-[#0d1324]"
        >
            <div
                class="auth-medical-grid absolute inset-0 opacity-30 lg:hidden"
            />
            <div
                class="absolute inset-0 bg-[radial-gradient(circle_at_top,rgba(117,186,251,0.22),transparent_34%),linear-gradient(180deg,rgba(240,249,255,0.96),rgba(219,234,254,0.9))] dark:bg-[radial-gradient(circle_at_top,rgba(117,186,251,0.18),transparent_34%),linear-gradient(180deg,rgba(13,19,36,0.94),rgba(6,10,20,0.98))]"
            />
            <Link
                :href="dashboard()"
                class="absolute top-7 left-1/2 z-10 flex -translate-x-1/2 items-center justify-center lg:hidden"
            >
                <AppLogoIcon class="size-14 object-contain" />
            </Link>
            <div
                class="relative z-10 w-full max-w-[400px] space-y-8"
            >
                <div class="space-y-2 text-center lg:text-left">
                    <div
                        class="mb-6 hidden items-center gap-3 text-blue-700 lg:flex dark:text-cyan-200"
                    >
                        <LockKeyhole class="size-5" />
                        <span
                            class="text-xs font-semibold tracking-[0.18em] uppercase"
                            >Akses Terlindungi</span
                        >
                    </div>
                    <h1
                        class="text-3xl leading-10 font-bold tracking-tight text-slate-950 dark:text-white"
                        v-if="title"
                    >
                        {{ title }}
                    </h1>
                    <p class="text-base leading-6 text-slate-600 dark:text-slate-400" v-if="description">
                        {{ description }}
                    </p>
                </div>
                <slot />
            </div>
        </div>
    </div>
</template>
