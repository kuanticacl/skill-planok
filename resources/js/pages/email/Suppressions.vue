<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Plus, RotateCcw, Search } from '@lucide/vue';
import { ref } from 'vue';
import DataCard from '@/components/DataCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { useDebouncedFilters } from '@/composables/useDebouncedFilters';
import { formatDateTime } from '@/lib/format';
import { destroy, index, store } from '@/routes/suppressions';
import type { Paginated } from '@/types';

type Row = { id: number; email: string; reason: string; note: string | null; created_at: string };
const props = defineProps<{ suppressions: Paginated<Row>; filters: { q?: string; reason?: string }; reasons: Record<string, string> }>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Email · Bajas y rebotes', href: index() }] } });

const filters = ref({ q: props.filters.q ?? '', reason: props.filters.reason ?? '' });
useDebouncedFilters(index().url, filters, ['suppressions', 'filters']);

const form = useForm({ email: '', note: '' });
const add = () => form.submit(store(), { preserveScroll: true, onSuccess: () => form.reset() });
const restore = (r: Row) => window.confirm(`¿Reactivar ${r.email}? Volverá a recibir correos.`) && router.delete(destroy(r.id).url, { preserveScroll: true });

const tone: Record<string, string> = {
    unsubscribed: 'bg-muted text-muted-foreground',
    bounced: 'bg-destructive/10 text-destructive',
    complained: 'bg-[#FFA165]/20 text-[#9A4B00]',
    manual: 'bg-brand-blue/10 text-brand-blue',
};
</script>

<template>
    <Head title="Bajas y rebotes" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Bajas y rebotes" description="Direcciones excluidas de todos los envíos: bajas voluntarias, rebotes permanentes, reclamos de spam y exclusiones manuales." />

        <DataCard class="p-4">
            <form class="flex flex-wrap items-end gap-3" @submit.prevent="add">
                <div class="grid gap-1.5"><label class="text-xs font-medium text-muted-foreground">Excluir una dirección</label><Input v-model="form.email" type="email" placeholder="persona@dominio.cl" class="w-64" required /></div>
                <div class="grid gap-1.5"><label class="text-xs font-medium text-muted-foreground">Nota (opcional)</label><Input v-model="form.note" placeholder="Motivo" class="w-56" /></div>
                <Button type="submit" :disabled="form.processing"><Plus /> Excluir</Button>
                <p v-if="form.errors.email" class="text-sm text-red-600">{{ form.errors.email }}</p>
            </form>
        </DataCard>

        <DataCard>
            <div class="flex flex-col gap-3 border-b p-4 md:flex-row">
                <div class="relative flex-1"><Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="filters.q" placeholder="Buscar correo…" class="pl-9" /></div>
                <NativeSelect v-model="filters.reason" class="md:w-56"><option value="">Todos los motivos</option><option v-for="(l, k) in reasons" :key="k" :value="k">{{ l }}</option></NativeSelect>
            </div>
            <Table>
                <TableHeader><TableRow class="hover:bg-transparent"><TableHead>Correo</TableHead><TableHead>Motivo</TableHead><TableHead>Nota</TableHead><TableHead>Fecha</TableHead><TableHead class="text-right">Acción</TableHead></TableRow></TableHeader>
                <TableBody>
                    <TableEmpty v-if="!suppressions.data.length" :colspan="5">No hay direcciones excluidas.</TableEmpty>
                    <TableRow v-for="r in suppressions.data" :key="r.id">
                        <TableCell class="font-medium">{{ r.email }}</TableCell>
                        <TableCell><Badge :class="['border-transparent', tone[r.reason]]">{{ reasons[r.reason] }}</Badge></TableCell>
                        <TableCell class="text-muted-foreground">{{ r.note ?? '—' }}</TableCell>
                        <TableCell class="text-muted-foreground">{{ formatDateTime(r.created_at) }}</TableCell>
                        <TableCell><div class="flex justify-end"><Button variant="ghost" size="sm" @click="restore(r)"><RotateCcw /> Reactivar</Button></div></TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <Pagination :paginator="suppressions" />
        </DataCard>
    </div>
</template>
