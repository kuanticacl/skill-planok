<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import {
    CalendarPlus,
    CircleCheckBig,
    CircleX,
    Layers,
    Percent,
    Plus,
    Search,
    Sparkles,
    X,
} from '@lucide/vue';
import { ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import KanbanBoard from '@/components/kanban/KanbanBoard.vue';
import SourceIcon from '@/components/SourceIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create } from '@/routes/leads';
import type { BoardColumn, UserOption } from '@/types';

type Filters = { period: string; source: string | null; assignee: string | null; q: string | null };
type SourceTile = { id: number; name: string; color: string; icon: string; is_active: boolean; count: number; pct: number };

const props = defineProps<{
    filters: Filters;
    periods: Record<string, string>;
    stats: { total: number; today: number; open: number; won: number; lost: number; conversion: number; win_rate_closed: number };
    sources: SourceTile[];
    board: BoardColumn[];
    columnPage: number;
    users: UserOption[];
    can: { create: boolean; move: boolean; viewAll: boolean };
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] } });

const period = ref(props.filters.period);
const assignee = ref(props.filters.assignee ?? '');
const q = ref(props.filters.q ?? '');
const source = ref(props.filters.source ?? '');

const apply = () => {
    const query = Object.fromEntries(
        Object.entries({ period: period.value, source: source.value, assignee: assignee.value, q: q.value }).filter(
            ([k, v]) => v !== '' && !(k === 'period' && v === 'all'),
        ),
    );
    router.get(dashboard().url, query, {
        preserveState: true,
        preserveScroll: true,
        replace: true,
        only: ['filters', 'stats', 'sources', 'board'],
    });
};

let timer: ReturnType<typeof setTimeout>;
watch([period, assignee, source], apply);
watch(q, () => {
    clearTimeout(timer);
    timer = setTimeout(apply, 350);
});

const toggleSource = (id: number) => {
    source.value = source.value === String(id) ? '' : String(id);
};
const clearFilters = () => {
    period.value = 'all';
    assignee.value = '';
    q.value = '';
    source.value = '';
};
const hasFilters = () => period.value !== 'all' || assignee.value || q.value || source.value;

// Tras mover una tarjeta se refrescan solo los contadores (el tablero ya está actualizado).
const refreshCounters = () => router.reload({ only: ['stats', 'sources'] });

const tiles = () => [
    { label: 'Leads', value: props.stats.total, icon: Layers, color: '#FF5300', hint: props.periods[props.filters.period] },
    { label: 'Nuevos hoy', value: props.stats.today, icon: CalendarPlus, color: '#1AA0E4', hint: 'Ingresados hoy' },
    { label: 'En gestión', value: props.stats.open, icon: Sparkles, color: '#6419DB', hint: 'Etapas en curso' },
    { label: 'Concretados', value: props.stats.won, icon: CircleCheckBig, color: '#0D9F85', hint: 'Ventas ganadas' },
    { label: 'Descartados', value: props.stats.lost, icon: CircleX, color: '#8A8A8A', hint: 'Sin avance' },
    { label: 'Conversión', value: `${props.stats.conversion}%`, icon: Percent, color: '#DF1E79', hint: 'Concretados / total' },
];
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-w-0 flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Dashboard" description="Recuento de leads por origen y tablero de seguimiento comercial.">
            <template #actions>
                <Button v-if="can.create" as-child>
                    <Link :href="create()"><Plus /> Nuevo lead</Link>
                </Button>
            </template>
        </PageHeader>

        <!-- Filtros -->
        <div class="flex flex-col gap-3 md:flex-row md:items-center">
            <div class="relative md:w-72">
                <Search class="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                <Input v-model="q" placeholder="Buscar lead…" class="pl-9" />
            </div>
            <NativeSelect v-model="period" class="md:w-48">
                <option v-for="(label, key) in periods" :key="key" :value="key">{{ label }}</option>
            </NativeSelect>
            <NativeSelect v-if="can.viewAll" v-model="assignee" class="md:w-52">
                <option value="">Todos los responsables</option>
                <option value="none">Sin asignar</option>
                <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
            </NativeSelect>
            <Button v-if="hasFilters()" variant="ghost" size="sm" @click="clearFilters"><X /> Limpiar filtros</Button>
        </div>

        <!-- Recuentos -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-6">
            <div
                v-for="t in tiles()"
                :key="t.label"
                class="flex items-center gap-3 rounded-2xl border bg-card p-4 shadow-sm shadow-black/[0.03]"
            >
                <div
                    class="flex size-10 shrink-0 items-center justify-center rounded-xl [&_svg]:size-5"
                    :style="{ backgroundColor: t.color + '1A', color: t.color }"
                >
                    <component :is="t.icon" />
                </div>
                <div class="min-w-0">
                    <p class="text-2xl leading-none font-bold tabular-nums">{{ t.value }}</p>
                    <p class="mt-1 truncate text-xs text-muted-foreground" :title="t.hint">{{ t.label }}</p>
                </div>
            </div>
        </div>

        <!-- Por origen -->
        <section class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-semibold">Leads por origen</h2>
                <p class="text-xs text-muted-foreground">Haz clic en un origen para filtrar el tablero.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-6">
                <button
                    v-for="s in sources"
                    :key="s.id"
                    type="button"
                    :class="
                        cn(
                            'flex flex-col gap-2 rounded-2xl border bg-card p-3.5 text-left shadow-sm shadow-black/[0.03] transition hover:-translate-y-0.5 hover:shadow-md',
                            source === String(s.id) && 'ring-2 ring-primary',
                            source && source !== String(s.id) && 'opacity-50',
                        )
                    "
                    @click="toggleSource(s.id)"
                >
                    <div class="flex items-center gap-2">
                        <span
                            class="flex size-7 items-center justify-center rounded-lg text-white [&_svg]:size-4"
                            :style="{ backgroundColor: s.color }"
                        >
                            <SourceIcon :name="s.icon" />
                        </span>
                        <span class="truncate text-sm font-medium">{{ s.name }}</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <span class="text-2xl leading-none font-bold tabular-nums">{{ s.count }}</span>
                        <span class="text-xs text-muted-foreground">{{ s.pct }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted">
                        <div class="h-full rounded-full transition-all" :style="{ width: s.pct + '%', backgroundColor: s.color }" />
                    </div>
                </button>
            </div>
        </section>

        <!-- Kanban -->
        <section class="flex min-w-0 flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-semibold">Tablero</h2>
                <p v-if="can.move" class="text-xs text-muted-foreground">Arrastra las tarjetas entre etapas.</p>
            </div>
            <KanbanBoard
                :board="board"
                :can-move="can.move"
                :filters="{ period: filters.period, source: filters.source, assignee: filters.assignee, q: filters.q }"
                :page-size="columnPage"
                @moved="refreshCounters"
            />
        </section>
    </div>
</template>
