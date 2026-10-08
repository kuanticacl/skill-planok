<script setup lang="ts">
import { Head, router, useForm } from '@inertiajs/vue3';
import { Pencil, Search, Trash2, Upload } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Table, TableBody, TableCell, TableEmpty, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import { useDebouncedFilters } from '@/composables/useDebouncedFilters';
import { sendJson } from '@/lib/http';
import { destroy, importMethod as importRoute, index, show, update } from '@/routes/lists';
import { destroy as destroyEntry } from '@/routes/lists/entries';
import type { Paginated } from '@/types';
import { toast } from 'vue-sonner';

type Entry = { id: number; email: string; name: string | null; data: Record<string, string> | null; suppressed: boolean };
const props = defineProps<{ list: { id: number; name: string; description: string | null; entries_count: number }; entries: Paginated<Entry>; filters: { q?: string } }>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Audiencias', href: index() }] } });

const filters = ref({ q: props.filters.q ?? '' });
useDebouncedFilters(show(props.list.id).url, filters, ['entries', 'filters']);

// importar
const importOpen = ref(false);
const text = ref('');
const busy = ref(false);
const doImport = async () => {
    busy.value = true;
    try {
        const r = await sendJson<{ added: number; updated: number; invalid: number }>('POST', importRoute(props.list.id).url, { text: text.value });
        toast.success(`${r.added} nuevos · ${r.updated} actualizados${r.invalid ? ` · ${r.invalid} inválidos` : ''}`);
        importOpen.value = false;
        text.value = '';
        router.reload({ only: ['list', 'entries'] });
    } catch {
        toast.error('No se pudo importar. Revisa el formato.');
    } finally {
        busy.value = false;
    }
};
const onFile = async (e: Event) => {
    const f = (e.target as HTMLInputElement).files?.[0];
    if (f) text.value = await f.text();
};

// editar lista
const editOpen = ref(false);
const form = useForm({ name: props.list.name, description: props.list.description ?? '' });
const save = () => form.submit(update(props.list.id), { onSuccess: () => (editOpen.value = false) });

const confirmDelete = ref(false);
const removeEntry = (e: Entry) => router.delete(destroyEntry({ list: props.list.id, entry: e.id }).url, { preserveScroll: true });
</script>

<template>
    <Head :title="list.name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="list.name" :description="`${list.entries_count.toLocaleString('es-CL')} contactos${list.description ? ' · ' + list.description : ''}`">
            <template #actions>
                <Button variant="outline" @click="editOpen = true"><Pencil /> Editar</Button>
                <Button variant="outline" class="text-destructive hover:text-destructive" @click="confirmDelete = true"><Trash2 /> Eliminar</Button>
                <Button @click="importOpen = true"><Upload /> Importar contactos</Button>
            </template>
        </PageHeader>

        <DataCard>
            <div class="border-b p-4"><div class="relative max-w-sm"><Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" /><Input v-model="filters.q" placeholder="Buscar contacto…" class="pl-9" /></div></div>
            <Table>
                <TableHeader><TableRow class="hover:bg-transparent"><TableHead>Correo</TableHead><TableHead>Nombre</TableHead><TableHead>Datos extra</TableHead><TableHead /></TableRow></TableHeader>
                <TableBody>
                    <TableEmpty v-if="!entries.data.length" :colspan="4">Sin contactos. Usa «Importar contactos».</TableEmpty>
                    <TableRow v-for="e in entries.data" :key="e.id">
                        <TableCell class="font-medium">{{ e.email }} <Badge v-if="e.suppressed" class="ml-1 border-transparent bg-destructive/10 text-destructive">Excluido</Badge></TableCell>
                        <TableCell>{{ e.name ?? '—' }}</TableCell>
                        <TableCell class="text-xs text-muted-foreground">{{ e.data ? Object.entries(e.data).map(([k, v]) => `${k}: ${v}`).join(' · ') : '—' }}</TableCell>
                        <TableCell><div class="flex justify-end"><Button variant="ghost" size="icon-sm" class="text-destructive hover:text-destructive" @click="removeEntry(e)"><Trash2 /></Button></div></TableCell>
                    </TableRow>
                </TableBody>
            </Table>
            <Pagination :paginator="entries" />
        </DataCard>
    </div>

    <Dialog v-model:open="importOpen">
        <DialogContent class="sm:max-w-xl">
            <DialogHeader>
                <DialogTitle>Importar contactos</DialogTitle>
                <DialogDescription>Pega filas desde Excel o un CSV: <code>correo, nombre</code>. Si la primera fila tiene encabezados (<code>email, nombre, empresa, proyecto…</code>), las columnas extra quedan disponibles como variables en las plantillas. Las direcciones excluidas (bajas/rebotes) no recibirán correos.</DialogDescription>
            </DialogHeader>
            <Textarea v-model="text" rows="10" class="font-mono text-xs" placeholder="email,nombre,empresa&#10;ana@ejemplo.cl,Ana Pérez,Cities&#10;beto@ejemplo.cl,Beto Soto,Bricsa" />
            <div class="flex items-center justify-between"><label class="cursor-pointer text-sm text-primary hover:underline">o sube un archivo .csv<input type="file" accept=".csv,.txt" class="hidden" @change="onFile" /></label><span class="text-xs text-muted-foreground">{{ text.split('\n').filter((l) => l.trim()).length }} filas</span></div>
            <DialogFooter class="gap-2"><Button variant="outline" @click="importOpen = false">Cancelar</Button><Button :disabled="busy || !text.trim()" @click="doImport"><Spinner v-if="busy" /> Importar</Button></DialogFooter>
        </DialogContent>
    </Dialog>

    <Dialog v-model:open="editOpen">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>Editar lista</DialogTitle></DialogHeader>
            <form class="grid gap-4" @submit.prevent="save">
                <Input v-model="form.name" required /><Input v-model="form.description" placeholder="Descripción" />
                <DialogFooter class="gap-2"><Button type="button" variant="outline" @click="editOpen = false">Cancelar</Button><Button type="submit" :disabled="form.processing">Guardar</Button></DialogFooter>
            </form>
        </DialogContent>
    </Dialog>

    <ConfirmDialog v-model:open="confirmDelete" title="Eliminar lista" :description="`Se eliminará «${list.name}» con sus ${list.entries_count} contactos. No afecta a los boletines ya enviados.`" confirm-label="Eliminar" @confirm="router.delete(destroy(list.id).url)" />
</template>
