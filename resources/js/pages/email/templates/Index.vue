<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Copy, FileCode2, Mail, Pencil, Plus, Search, Trash2 } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import Pagination from '@/components/Pagination.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useDebouncedFilters } from '@/composables/useDebouncedFilters';
import { usePermissions } from '@/composables/usePermissions';
import { timeAgo } from '@/lib/format';
import { create, destroy, duplicate, edit, index } from '@/routes/templates';
import type { Paginated, TemplateRow } from '@/types';

defineOptions({ layout: { breadcrumbs: [{ title: 'Plantillas', href: index() }] } });

const props = defineProps<{ templates: Paginated<TemplateRow>; filters: { q?: string; category?: string }; categories: Record<string, string> }>();
const { can } = usePermissions();

const filters = ref({ q: props.filters.q ?? '', category: props.filters.category ?? '' });
useDebouncedFilters(index().url, filters, ['templates', 'filters']);

const toDelete = ref<TemplateRow | null>(null);
const confirmDelete = () => {
    if (!toDelete.value) return;
    router.delete(destroy(toDelete.value.id).url, { preserveScroll: true, onFinish: () => (toDelete.value = null) });
};
const dup = (t: TemplateRow) => router.post(duplicate(t.id).url);
</script>

<template>
    <Head title="Plantillas de email" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Plantillas de email" description="Diseña correos reutilizables para boletines y para enviarlos por API o automatizaciones.">
            <template #actions>
                <Button v-if="can('templates.manage')" as-child><Link :href="create()"><Plus /> Nueva plantilla</Link></Button>
            </template>
        </PageHeader>

        <div class="flex flex-col gap-3 md:flex-row">
            <div class="relative flex-1 md:max-w-sm">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="filters.q" placeholder="Buscar por nombre, identificador o asunto…" class="pl-9" />
            </div>
            <NativeSelect v-model="filters.category" class="md:w-64">
                <option value="">Todos los tipos</option>
                <option v-for="(label, key) in categories" :key="key" :value="key">{{ label }}</option>
            </NativeSelect>
        </div>

        <p v-if="!templates.data.length" class="rounded-2xl border border-dashed bg-card py-14 text-center text-sm text-muted-foreground">
            <Mail class="mx-auto mb-2 size-7" />Aún no hay plantillas que coincidan.
        </p>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <article v-for="t in templates.data" :key="t.id" class="flex flex-col gap-3 rounded-2xl border bg-card p-5 shadow-sm shadow-black/[0.03] transition hover:shadow-md" :class="!t.is_active && 'opacity-60'">
                <div class="flex items-start justify-between gap-3">
                    <div class="min-w-0">
                        <Link :href="edit(t.id)" class="block truncate font-semibold hover:text-primary">{{ t.name }}</Link>
                        <code class="text-[11px] text-muted-foreground">{{ t.slug }}</code>
                    </div>
                    <Badge :class="t.category === 'marketing' ? 'border-transparent bg-primary/10 text-primary' : 'border-transparent bg-brand-blue/10 text-brand-blue'">{{ t.category === 'marketing' ? 'Marketing' : 'Transaccional' }}</Badge>
                </div>
                <p class="line-clamp-2 min-h-10 text-sm text-muted-foreground">{{ t.subject }}</p>
                <div class="flex flex-wrap gap-1.5">
                    <span v-for="v in t.variables.slice(0, 5)" :key="v" class="rounded bg-muted px-1.5 py-0.5 font-mono text-[10px] text-muted-foreground">{{ v }}</span>
                    <span v-if="t.variables.length > 5" class="text-[10px] text-muted-foreground">+{{ t.variables.length - 5 }}</span>
                    <span v-if="!t.variables.length" class="text-[11px] text-muted-foreground">Sin variables</span>
                </div>
                <div class="mt-auto flex items-center justify-between border-t pt-3 text-xs text-muted-foreground">
                    <span class="flex items-center gap-3">
                        <span title="Correos enviados con esta plantilla">{{ t.sent_count }} envíos</span>
                        <span v-if="t.editor === 'html'" class="flex items-center gap-1"><FileCode2 class="size-3.5" /> HTML</span>
                        <span>{{ timeAgo(t.updated_at) }}</span>
                    </span>
                    <span class="flex gap-0.5">
                        <Button variant="ghost" size="icon-sm" as-child :title="can('templates.manage') ? 'Editar' : 'Ver'"><Link :href="edit(t.id)"><Pencil /></Link></Button>
                        <Button v-if="can('templates.manage')" variant="ghost" size="icon-sm" title="Duplicar" @click="dup(t)"><Copy /></Button>
                        <Button v-if="can('templates.manage')" variant="ghost" size="icon-sm" class="text-destructive hover:text-destructive" title="Eliminar" @click="toDelete = t"><Trash2 /></Button>
                    </span>
                </div>
            </article>
        </div>
        <Pagination :paginator="templates" />
    </div>

    <ConfirmDialog :open="!!toDelete" title="Eliminar plantilla" :description="`Se eliminará «${toDelete?.name}». Los boletines ya enviados conservan su contenido; las automatizaciones que la usen dejarán de enviar.`" confirm-label="Eliminar" @update:open="(v: boolean) => !v && (toDelete = null)" @confirm="confirmDelete" />
</template>
