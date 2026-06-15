<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import {
    AlertTriangle,
    History,
    LogIn,
    Lock,
    Shield,
    UserRound,
} from '@lucide/vue';
// import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { store } from '@/routes/login';

defineOptions({
    layout: {
        title: 'Masuk Aman',
        description: 'Silakan masukkan ID pengguna dan kata sandi Anda',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Masuk" />

    <div
        v-if="status"
        class="rounded-lg border border-blue-300/30 bg-blue-500/10 p-4 text-sm font-medium text-blue-800 dark:border-cyan-300/20 dark:bg-cyan-300/10 dark:text-cyan-100"
    >
        {{ status }}
    </div>

    <Form
        :action="store.url()"
        method="post"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="space-y-7"
    >
        <div
            v-if="errors.id_user || errors.password"
            class="flex items-start gap-3 rounded-lg border border-red-300/50 bg-red-500/10 p-4 text-sm text-red-700 dark:border-red-300/30 dark:text-red-100"
        >
            <AlertTriangle class="mt-0.5 size-5 shrink-0 text-red-600 dark:text-red-200" />
            <p>Verifikasi gagal. Mohon periksa kembali ID pengguna dan kata sandi Anda.</p>
        </div>

        <div class="space-y-5">
            <div class="space-y-2">
                <Label
                    for="id_user"
                    class="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase dark:text-slate-400"
                    >ID User</Label
                >
                <div class="group relative">
                    <UserRound
                        class="absolute top-1/2 left-4 size-5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-blue-600 dark:text-slate-500 dark:group-focus-within:text-cyan-200"
                    />
                    <Input
                        id="id_user"
                        name="id_user"
                        required
                        autofocus
                        :tabindex="1"
                        autocomplete="username"
                        placeholder="Nomor ID / nama pengguna"
                        class="h-12 rounded-lg border-sky-200 bg-white/80 pr-4 pl-12 text-slate-950 shadow-sm shadow-blue-900/5 placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-500/25 dark:border-white/10 dark:bg-white/4 dark:text-slate-100 dark:shadow-none dark:placeholder:text-slate-600 dark:focus-visible:border-cyan-300 dark:focus-visible:ring-cyan-300/35"
                    />
                </div>
                <!-- <InputError :message="errors.id_user" /> -->
            </div>

            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <Label
                        for="password"
                        class="text-xs font-semibold tracking-[0.16em] text-slate-500 uppercase dark:text-slate-400"
                        >Kata Sandi</Label
                    >
                </div>
                <div class="group relative">
                    <Lock
                        class="absolute top-1/2 left-4 z-10 size-5 -translate-y-1/2 text-slate-400 transition-colors group-focus-within:text-blue-600 dark:text-slate-500 dark:group-focus-within:text-cyan-200"
                    />
                    <PasswordInput
                        id="password"
                        name="password"
                        required
                        :tabindex="2"
                        autocomplete="current-password"
                        placeholder="Kata sandi"
                        class="h-12 rounded-lg border-sky-200 bg-white/80 pr-12 pl-12 text-slate-950 shadow-sm shadow-blue-900/5 placeholder:text-slate-400 focus-visible:border-blue-500 focus-visible:ring-blue-500/25 dark:border-white/10 dark:bg-white/4 dark:text-slate-100 dark:shadow-none dark:placeholder:text-slate-600 dark:focus-visible:border-cyan-300 dark:focus-visible:ring-cyan-300/35"
                    />
                </div>
                <!-- <InputError :message="errors.password" /> -->
            </div>

            <div class="flex items-center justify-between">
                <Label
                    for="remember"
                    class="flex cursor-pointer items-center gap-3 text-sm text-slate-600 transition-colors hover:text-slate-950 dark:text-slate-400 dark:hover:text-slate-200"
                >
                    <Checkbox
                        id="remember"
                        name="remember"
                        :tabindex="3"
                        class="size-5 rounded border-sky-300 bg-white/80 data-[state=checked]:border-blue-600 data-[state=checked]:bg-blue-600 data-[state=checked]:text-white focus-visible:ring-blue-500/25 dark:border-white/15 dark:bg-white/4 dark:data-[state=checked]:border-cyan-300 dark:data-[state=checked]:bg-cyan-300 dark:data-[state=checked]:text-slate-950 dark:focus-visible:ring-cyan-300/35"
                    />
                    <span>Ingat perangkat ini</span>
                </Label>
            </div>

            <Button
                type="submit"
                class="h-14 w-full rounded-lg bg-blue-600 text-base font-semibold text-white shadow-[0_4px_24px_rgba(37,99,235,0.20)] transition-transform hover:bg-blue-500 active:scale-[0.98] dark:bg-cyan-300 dark:text-slate-950 dark:shadow-[0_4px_24px_rgba(0,194,255,0.22)] dark:hover:bg-cyan-200"
                :tabindex="4"
                :disabled="processing"
                data-test="login-button"
            >
                <Spinner v-if="processing" />
                <span>Masuk ke Beranda</span>
                <LogIn class="size-5" />
            </Button>
        </div>

        <div
            class="grid grid-cols-3 gap-3 border-t border-sky-200 pt-5 text-slate-500 dark:border-white/10"
        >
            <div class="flex items-center justify-center gap-1.5">
                <Shield class="size-4" />
                <span class="text-[10px] font-semibold tracking-widest uppercase"
                    >Aman</span
                >
            </div>
            <div class="flex items-center justify-center gap-1.5">
                <Lock class="size-4" />
                <span class="text-[10px] font-semibold tracking-widest uppercase"
                    >256-Bit</span
                >
            </div>
            <div class="flex items-center justify-center gap-1.5">
                <History class="size-4" />
                <span class="text-[10px] font-semibold tracking-widest uppercase"
                    >Tercatat</span
                >
            </div>
        </div>
    </Form>
</template>
