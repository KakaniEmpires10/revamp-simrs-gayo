<script setup lang="ts">
import { Link, setLayoutProps } from '@inertiajs/vue3';
import { ArrowLeft, Clock3 } from '@lucide/vue';
import { index as bpjsIndex } from '@/actions/App/Modules/Bpjs/Http/Controllers/BpjsDashboardController';
import { ambilIconBpjs } from '@/components/integrasi-eksternal/bpjs/petaIcon';
import type { BpjsSubmenuItem } from '@/components/integrasi-eksternal/bpjs/tipe';
import PageHeader from '@/components/shared/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent } from '@/components/ui/card';

const props = defineProps<{
    menu: BpjsSubmenuItem;
}>();

setLayoutProps({
    breadcrumbs: [
        { title: 'Integrasi Eksternal', href: bpjsIndex() },
        { title: 'BPJS', href: bpjsIndex() },
        { title: props.menu.title, href: props.menu.href },
    ],
});
</script>

<template>
    <div class="space-y-5">
        <PageHeader
            :title="menu.title"
            :description="menu.description"
        >
            <template #actions>
                <Button variant="outline" as-child>
                    <Link :href="bpjsIndex()">
                        <ArrowLeft class="size-4" />
                        Kembali
                    </Link>
                </Button>
            </template>
        </PageHeader>

        <Card>
            <CardContent class="flex flex-col gap-4 p-6 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-start gap-4">
                    <div class="flex size-11 shrink-0 items-center justify-center rounded-md bg-primary/10 text-primary">
                        <component :is="ambilIconBpjs(menu.icon)" class="size-5" />
                    </div>
                    <div class="space-y-2">
                        <Badge :variant="menu.badgeVariant" rounded="md">
                            {{ menu.badge }}
                        </Badge>
                        <p class="max-w-2xl text-sm leading-6 text-muted-foreground">
                            Modul ini sudah masuk struktur menu BPJS dan akan dimigrasikan setelah fondasi pendaftaran siap.
                        </p>
                    </div>
                </div>
                <Badge variant="soft-warning" rounded="md">
                    <Clock3 class="size-3" />
                    Menunggu Migrasi
                </Badge>
            </CardContent>
        </Card>
    </div>
</template>
