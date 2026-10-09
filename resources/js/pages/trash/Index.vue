<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { RotateCcw, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { cn } from '@/lib/utils';
import { index, restore } from '@/routes/trash';
import type { Paginated } from '@/types';

type Item = { id: number; title: string; subtitle: string; deleted_at: string | null; related: Record<string, number> };
const props = defineProps<{
    type: string;
    tabs: { key: string; label: string; count: number }[];
    items: Paginated<Item>;
}>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Papelera', href: index() }] } });

const fmt = (d: string | null) => (d ? new Intl.DateTimeFormat('es-CL', { dateStyle: 'medium', timeStyle: 'short' }).format(new Date(d)) : '—');
const toRestore = ref<Item | null>(null);
const relatedText = computed(() => {
    const r = Object.entries(toRestore.value?.related ?? {}).filter(([, n]) => n > 0).map(([k, n]) => `${n} ${n === 1 ? k.replace(/s$/, '').replace('propuesta', 'propuesta') : k}`);
    return r.length ? ` También se restaurarán ${r.join(' y ')} que se eliminaron con él.` : '';
});
const doRestore = () => {
    if (!toRestore.value) return;
    router.post(restore({ type: props.type, id: toRestore.value.id }).url, {}, { preserveScroll: true, onFinish: () => (toRestore.value = null) });
};
</script>

<template>
    <Head title="Papelera" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Papelera" description="Lo que se elimina queda aquí. Puedes restaurarlo con sus datos relacionados." />

        <div class="flex flex-wrap gap-2">
            <Link
                v-for="t in tabs"
                :key="t.key"
                :href="index({ query: { type: t.key } }).url"
                :class="cn('inline-flex items-center gap-2 rounded-full border px-4 py-1.5 text-sm transition', t.key === type ? 'border-primary bg-accent text-accent-foreground' : 'hover:bg-muted')"
                preserve-scroll
            >
                {{ t.label }} <Badge variant="secondary">{{ t.count }}</Badge>
            </Link>
        </div>

        <div v-if="!items.data.length" class="flex flex-col items-center gap-2 rounded-2xl border border-dashed py-16 text-muted-foreground">
            <Trash2 class="size-8" />
            La papelera de esta sección está vacía.
        </div>

        <div v-else class="rounded-2xl border bg-card">
            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Elemento</TableHead>
                        <TableHead>Eliminado</TableHead>
                        <TableHead class="w-32 text-right" />
                    </TableRow>
                </TableHeader>
                <TableBody>
                    <TableRow v-for="i in items.data" :key="i.id">
                        <TableCell>
                            <div class="font-medium">{{ i.title }}</div>
                            <div v-if="i.subtitle" class="text-xs text-muted-foreground">{{ i.subtitle }}</div>
                        </TableCell>
                        <TableCell class="text-muted-foreground">{{ fmt(i.deleted_at) }}</TableCell>
                        <TableCell class="text-right">
                            <Button variant="outline" size="sm" @click="toRestore = i"><RotateCcw /> Restaurar</Button>
                        </TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <Pagination :paginator="items" />
        </div>
    </div>

    <ConfirmDialog
        :open="!!toRestore"
        title="Restaurar"
        :description="`Se restaurará «${toRestore?.title}».${relatedText}`"
        confirm-label="Restaurar"
        :destructive="false"
        @update:open="(v: boolean) => !v && (toRestore = null)"
        @confirm="doRestore"
    />
</template>
