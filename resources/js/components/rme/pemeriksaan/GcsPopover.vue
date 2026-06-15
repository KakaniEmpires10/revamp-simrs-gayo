<script setup lang="ts">
import { Brain, ChevronDown } from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover';

const gcs = defineModel<string>('gcs', { default: '' });
const kesadaran = defineModel<string>('kesadaran', { default: 'Compos Mentis' });

const open = ref(false);
const eye = ref(4);
const verbal = ref(5);
const motor = ref(6);
const gcsFormatLama = ref('');

const total = computed(() => eye.value + verbal.value + motor.value);
const gcsLabel = computed(() => `E${eye.value} V${verbal.value} M${motor.value}`);
const adaGcsFormatLama = computed(() => gcsFormatLama.value !== '');
const kesadaranOtomatis = computed(() => {
    if (total.value >= 14) {
        return 'Compos Mentis';
    }

    if (total.value >= 12) {
        return 'Somnolence';
    }

    if (total.value >= 4) {
        return 'Sopor';
    }

    return 'Coma';
});

function parseGcs(value: string): { eye: number; verbal: number; motor: number } | null {
    const match = value.trim().match(/^E([1-4])\s*V([1-5])\s*M([1-6])$/i);

    if (!match) {
        return null;
    }

    return {
        eye: Number(match[1]),
        verbal: Number(match[2]),
        motor: Number(match[3]),
    };
}

function sinkronkanGcs(): void {
    gcsFormatLama.value = '';
    gcs.value = gcsLabel.value;
    kesadaran.value = kesadaranOtomatis.value;
}

function pilihEye(value: number): void {
    gcsFormatLama.value = '';
    eye.value = value;
}

function pilihVerbal(value: number): void {
    gcsFormatLama.value = '';
    verbal.value = value;
}

function pilihMotor(value: number): void {
    gcsFormatLama.value = '';
    motor.value = value;
}

watch(
    gcs,
    (value) => {
        const normalized = value.trim();

        if (normalized === '') {
            sinkronkanGcs();

            return;
        }

        const parsed = parseGcs(normalized);

        if (!parsed) {
            gcsFormatLama.value = normalized;

            return;
        }

        gcsFormatLama.value = '';

        if (eye.value !== parsed.eye) {
            eye.value = parsed.eye;
        }

        if (verbal.value !== parsed.verbal) {
            verbal.value = parsed.verbal;
        }

        if (motor.value !== parsed.motor) {
            motor.value = parsed.motor;
        }

        kesadaran.value = kesadaranOtomatis.value;
    },
    { immediate: true },
);

watch([eye, verbal, motor], () => {
    if (adaGcsFormatLama.value) {
        return;
    }

    sinkronkanGcs();
});
</script>

<template>
    <Popover v-model:open="open">
        <PopoverTrigger as-child>
            <Button
                type="button"
                variant="outline"
                class="w-full justify-between bg-background"
            >
                <span class="inline-flex min-w-0 items-center gap-2">
                    <Brain class="size-4 text-muted-foreground" />
                    <span
                        v-if="adaGcsFormatLama"
                        class="truncate text-warning"
                    >
                        Format lama: {{ gcsFormatLama }}
                    </span>
                    <span
                        v-else
                        class="truncate"
                    >
                        {{ gcsLabel }} - {{ total }}
                    </span>
                </span>
                <ChevronDown class="size-4 text-muted-foreground" />
            </Button>
        </PopoverTrigger>
        <PopoverContent
            class="w-80 p-4"
            align="start"
        >
            <div class="space-y-4">
                <div class="flex items-center justify-between gap-3">
                    <div>
                        <p class="text-sm font-medium">Glasgow Coma Scale</p>
                        <p class="text-xs text-muted-foreground">Pilih nilai E, V, dan M.</p>
                    </div>
                    <Badge
                        :variant="adaGcsFormatLama ? 'soft-warning' : 'soft-primary'"
                        rounded="md"
                    >
                        {{ adaGcsFormatLama ? 'Perlu koreksi' : kesadaranOtomatis }}
                    </Badge>
                </div>

                <div
                    v-if="adaGcsFormatLama"
                    class="rounded-md border border-warning/30 bg-warning/10 p-3 text-xs leading-relaxed text-warning"
                >
                    Data lama berisi "{{ gcsFormatLama }}". Pilih nilai E, V, dan M sebelum menyimpan agar format GCS menjadi baku.
                </div>

                <div class="grid gap-3">
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-muted-foreground">Eye (E)</p>
                        <div class="grid grid-cols-4 gap-2">
                            <Button
                                v-for="value in [1, 2, 3, 4]"
                                :key="`e-${value}`"
                                type="button"
                                size="sm"
                                :variant="eye === value ? 'default' : 'outline'"
                                @click="pilihEye(value)"
                            >
                                {{ value }}
                            </Button>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-muted-foreground">Verbal (V)</p>
                        <div class="grid grid-cols-5 gap-2">
                            <Button
                                v-for="value in [1, 2, 3, 4, 5]"
                                :key="`v-${value}`"
                                type="button"
                                size="sm"
                                :variant="verbal === value ? 'default' : 'outline'"
                                @click="pilihVerbal(value)"
                            >
                                {{ value }}
                            </Button>
                        </div>
                    </div>
                    <div class="grid gap-2">
                        <p class="text-xs font-medium text-muted-foreground">Motor (M)</p>
                        <div class="grid grid-cols-6 gap-2">
                            <Button
                                v-for="value in [1, 2, 3, 4, 5, 6]"
                                :key="`m-${value}`"
                                type="button"
                                size="sm"
                                :variant="motor === value ? 'default' : 'outline'"
                                @click="pilihMotor(value)"
                            >
                                {{ value }}
                            </Button>
                        </div>
                    </div>
                </div>
            </div>
        </PopoverContent>
    </Popover>
</template>
