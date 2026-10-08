<script setup lang="ts">
import { ArrowDownUp, Eye, ListFilter, Search, X } from '@lucide/vue';
import { computed } from 'vue';
import { Button } from '@/components/ui/button';
import {
    DropdownMenu,
    DropdownMenuCheckboxItem,
    DropdownMenuContent,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { useKanbanPrefs } from '@/composables/useKanbanPrefs';
import { priorityMeta } from '@/lib/leadUi';

export type Filters = {
    q: string;
    period: string;
    sort: string;
    sources: string[];
    assignees: string[];
    priorities: string[];
    tag: string;
    overdue: boolean;
    no_followup: boolean;
};

const props = defineProps<{
    periods: Record<string, string>;
    sorts: Record<string, string>;
    priorities: Record<string, string>;
    sources: { id: number; name: string; color: string }[];
    users: { id: number; name: string }[];
    tags: string[];
    canViewAll: boolean;
}>();

const f = defineModel<Filters>({ required: true });
const prefs = useKanbanPrefs();

const activeCount = computed(
    () =>
        f.value.sources.length + f.value.assignees.length + f.value.priorities.length + (f.value.tag ? 1 : 0) + (f.value.overdue ? 1 : 0) + (f.value.no_followup ? 1 : 0),
);
const dirty = computed(() => activeCount.value > 0 || f.value.q || f.value.period !== 'all');

const toggleIn = (key: 'sources' | 'assignees' | 'priorities', value: string) => {
    const list = f.value[key];
    f.value = { ...f.value, [key]: list.includes(value) ? list.filter((x) => x !== value) : [...list, value] };
};
const set = <K extends keyof Filters>(key: K, value: Filters[K]) => (f.value = { ...f.value, [key]: value });
const clear = () => (f.value = { q: '', period: 'all', sort: f.value.sort, sources: [], assignees: [], priorities: [], tag: '', overdue: false, no_followup: false });
const keep = (e: Event) => e.preventDefault();

const showOptions: [keyof typeof prefs.value.show, string][] = [
    ['source', 'Origen'],
    ['campaign', 'Campaña (UTM)'],
    ['tags', 'Etiquetas'],
    ['value', 'Valor estimado'],
    ['followup', 'Próximo seguimiento'],
    ['stageTime', 'Tiempo en etapa'],
    ['custom', 'Campos personalizados'],
];
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <div class="relative w-full sm:w-64">
            <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
            <Input :model-value="f.q" placeholder="Buscar nombre, correo, empresa…" class="pl-9" @update:model-value="(v) => set('q', String(v))" />
        </div>

        <NativeSelect :model-value="f.period" class="w-44" @update:model-value="(v) => set('period', String(v))">
            <option v-for="(label, key) in periods" :key="key" :value="key">{{ label }}</option>
        </NativeSelect>

        <!-- Filtros -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button variant="outline">
                    <ListFilter /> Filtros
                    <span v-if="activeCount" class="ml-1 rounded-full bg-primary px-1.5 text-[11px] font-semibold text-primary-foreground">{{ activeCount }}</span>
                </Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="start" class="max-h-[70vh] w-64 overflow-y-auto">
                <DropdownMenuLabel>Alertas</DropdownMenuLabel>
                <DropdownMenuCheckboxItem :model-value="f.overdue" @update:model-value="(v) => set('overdue', !!v)" @select="keep">Seguimiento vencido</DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem :model-value="f.no_followup" @update:model-value="(v) => set('no_followup', !!v)" @select="keep">Sin seguimiento programado</DropdownMenuCheckboxItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Origen</DropdownMenuLabel>
                <DropdownMenuCheckboxItem v-for="s in sources" :key="s.id" :model-value="f.sources.includes(String(s.id))" @update:model-value="toggleIn('sources', String(s.id))" @select="keep">
                    <span class="size-2 rounded-full" :style="{ backgroundColor: s.color }" />{{ s.name }}
                </DropdownMenuCheckboxItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Prioridad</DropdownMenuLabel>
                <DropdownMenuCheckboxItem v-for="(label, key) in priorities" :key="key" :model-value="f.priorities.includes(String(key))" @update:model-value="toggleIn('priorities', String(key))" @select="keep">
                    <span class="size-2 rounded-full" :style="{ backgroundColor: priorityMeta[key]?.color }" />{{ label }}
                </DropdownMenuCheckboxItem>
                <template v-if="canViewAll">
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel>Responsable</DropdownMenuLabel>
                    <DropdownMenuCheckboxItem :model-value="f.assignees.includes('none')" @update:model-value="toggleIn('assignees', 'none')" @select="keep">Sin asignar</DropdownMenuCheckboxItem>
                    <DropdownMenuCheckboxItem v-for="u in users" :key="u.id" :model-value="f.assignees.includes(String(u.id))" @update:model-value="toggleIn('assignees', String(u.id))" @select="keep">{{ u.name }}</DropdownMenuCheckboxItem>
                </template>
                <template v-if="tags.length">
                    <DropdownMenuSeparator />
                    <DropdownMenuLabel>Etiqueta</DropdownMenuLabel>
                    <DropdownMenuCheckboxItem v-for="t in tags" :key="t" :model-value="f.tag === t" @update:model-value="(v) => set('tag', v ? t : '')" @select="keep">{{ t }}</DropdownMenuCheckboxItem>
                </template>
            </DropdownMenuContent>
        </DropdownMenu>

        <!-- Orden -->
        <label class="flex items-center gap-1.5 text-muted-foreground" title="Ordenar tarjetas dentro de cada columna">
            <ArrowDownUp class="size-4" />
            <NativeSelect :model-value="f.sort" class="w-44" @update:model-value="(v) => set('sort', String(v))">
                <option v-for="(label, key) in sorts" :key="key" :value="key">{{ label }}</option>
            </NativeSelect>
        </label>

        <!-- Vista -->
        <DropdownMenu>
            <DropdownMenuTrigger as-child><Button variant="outline" size="icon" title="Vista de las tarjetas"><Eye /></Button></DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="w-60">
                <DropdownMenuLabel>Densidad</DropdownMenuLabel>
                <DropdownMenuCheckboxItem :model-value="prefs.density === 'comfortable'" @update:model-value="prefs.density = 'comfortable'" @select="keep">Cómoda</DropdownMenuCheckboxItem>
                <DropdownMenuCheckboxItem :model-value="prefs.density === 'compact'" @update:model-value="prefs.density = 'compact'" @select="keep">Compacta</DropdownMenuCheckboxItem>
                <DropdownMenuSeparator />
                <DropdownMenuLabel>Mostrar en la tarjeta</DropdownMenuLabel>
                <DropdownMenuCheckboxItem v-for="[key, label] in showOptions" :key="key" :model-value="prefs.show[key]" @update:model-value="(v) => (prefs.show[key] = !!v)" @select="keep">{{ label }}</DropdownMenuCheckboxItem>
            </DropdownMenuContent>
        </DropdownMenu>

        <Button v-if="dirty" variant="ghost" size="sm" @click="clear"><X /> Limpiar</Button>
    </div>
</template>
