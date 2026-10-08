<script setup lang="ts">
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useDocumentVisibility, useIntervalFn } from '@vueuse/core';
import {
    AlarmClock,
    CalendarPlus,
    CircleCheckBig,
    CircleX,
    Layers,
    List,
    Percent,
    Plus,
    Sparkles,
    TrendingUp,
    Trophy,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import PageHeader from '@/components/PageHeader.vue';
import KanbanBoard from '@/components/kanban/KanbanBoard.vue';
import KanbanToolbar from '@/components/kanban/KanbanToolbar.vue';
import type { Filters } from '@/components/kanban/KanbanToolbar.vue';
import SourceIcon from '@/components/SourceIcon.vue';
import { Button } from '@/components/ui/button';
import { formatMoneyShort } from '@/lib/leadUi';
import { cn } from '@/lib/utils';
import { dashboard } from '@/routes';
import { create, index as leadsIndex } from '@/routes/leads';
import type { BoardColumn, StageRef, UserOption } from '@/types';

type SourceTile = { id: number; name: string; color: string; icon: string; is_active: boolean; count: number; pct: number };
type RawFilters = { period: string; sort: string; q: string | null; sources: string[]; assignees: string[]; priorities: string[]; tag: string | null; overdue: boolean; no_followup: boolean };

const props = defineProps<{
    filters: RawFilters;
    periods: Record<string, string>;
    sorts: Record<string, string>;
    priorities: Record<string, string>;
    stats: { total: number; today: number; open: number; won: number; lost: number; conversion: number; overdue: number; pipeline_value: number; won_value: number };
    sources: SourceTile[];
    board: BoardColumn[];
    columnPage: number;
    users: UserOption[];
    tags: string[];
    stages: (StageRef & { type: string })[];
    manualSourceId: number | null;
    can: { create: boolean; move: boolean; assign: boolean; update: boolean; delete: boolean; viewAll: boolean; email: boolean };
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Dashboard', href: dashboard() }] } });

const page = usePage();

const toLocal = (r: RawFilters): Filters => ({
    q: r.q ?? '', period: r.period, sort: r.sort, sources: [...r.sources], assignees: [...r.assignees],
    priorities: [...r.priorities], tag: r.tag ?? '', overdue: r.overdue, no_followup: r.no_followup,
});
const f = ref<Filters>(toLocal(props.filters));

const query = computed<Record<string, string>>(() => {
    const v = f.value;
    const q: Record<string, string> = {};
    if (v.period !== 'all') q.period = v.period;
    if (v.sort !== 'manual') q.sort = v.sort;
    if (v.q.trim()) q.q = v.q.trim();
    if (v.sources.length) q.sources = v.sources.join(',');
    if (v.assignees.length) q.assignees = v.assignees.join(',');
    if (v.priorities.length) q.priorities = v.priorities.join(',');
    if (v.tag) q.tag = v.tag;
    if (v.overdue) q.overdue = '1';
    if (v.no_followup) q.no_followup = '1';
    return q;
});

const ONLY = ['filters', 'stats', 'sources', 'board', 'tags'];
const reload = () => router.get(dashboard().url, query.value, { preserveState: true, preserveScroll: true, replace: true, only: ONLY });

let timer: ReturnType<typeof setTimeout>;
let first = true;
watch(f, () => {
    if (first) { first = false; }
    clearTimeout(timer);
    timer = setTimeout(reload, 300);
}, { deep: true });

// Actualización automática cada 60 s mientras la pestaña está visible.
const visibility = useDocumentVisibility();
useIntervalFn(() => { if (visibility.value === 'visible') reload(); }, 60_000);

const toggleSource = (id: number) => {
    const s = String(id);
    f.value = { ...f.value, sources: f.value.sources.length === 1 && f.value.sources[0] === s ? [] : [s] };
};

const tiles = computed(() => [
    { label: 'Leads', value: props.stats.total, icon: Layers, color: '#FF5300', hint: props.periods[props.filters.period] },
    { label: 'Nuevos hoy', value: props.stats.today, icon: CalendarPlus, color: '#1AA0E4' },
    { label: 'En gestión', value: props.stats.open, icon: Sparkles, color: '#6419DB' },
    { label: 'Concretados', value: props.stats.won, icon: CircleCheckBig, color: '#0D9F85' },
    { label: 'Descartados', value: props.stats.lost, icon: CircleX, color: '#8A8A8A' },
    { label: 'Conversión', value: `${props.stats.conversion}%`, icon: Percent, color: '#DF1E79' },
    { label: 'Seguimientos vencidos', value: props.stats.overdue, icon: AlarmClock, color: '#DC2626', action: () => (f.value = { ...f.value, overdue: !f.value.overdue }), active: f.value.overdue },
    { label: 'Valor en pipeline', value: formatMoneyShort(props.stats.pipeline_value) || '$0', icon: TrendingUp, color: '#4A8CFF' },
    { label: 'Ventas concretadas', value: formatMoneyShort(props.stats.won_value) || '$0', icon: Trophy, color: '#0D9F85' },
]);
</script>

<template>
    <Head title="Dashboard" />

    <div class="flex min-w-0 flex-col gap-6 p-4 md:p-6">
        <PageHeader title="Dashboard" description="Recuento de leads por origen y tablero de seguimiento comercial.">
            <template #actions>
                <Button variant="outline" as-child><Link :href="leadsIndex()"><List /> Ver lista</Link></Button>
                <Button v-if="can.create" as-child><Link :href="create()"><Plus /> Nuevo lead</Link></Button>
            </template>
        </PageHeader>

        <!-- Recuentos -->
        <div class="grid grid-cols-2 gap-3 md:grid-cols-3 xl:grid-cols-5">
            <component
                :is="t.action ? 'button' : 'div'"
                v-for="t in tiles"
                :key="t.label"
                :type="t.action ? 'button' : undefined"
                :class="cn('flex items-center gap-3 rounded-2xl border bg-card p-4 text-left shadow-sm shadow-black/[0.03]', t.action && 'transition hover:-translate-y-0.5 hover:shadow-md', t.active && 'ring-2 ring-destructive')"
                @click="t.action?.()"
            >
                <div class="flex size-10 shrink-0 items-center justify-center rounded-xl [&_svg]:size-5" :style="{ backgroundColor: t.color + '1A', color: t.color }">
                    <component :is="t.icon" />
                </div>
                <div class="min-w-0">
                    <p class="text-2xl leading-none font-bold tabular-nums">{{ t.value }}</p>
                    <p class="mt-1 truncate text-xs text-muted-foreground" :title="t.hint">{{ t.label }}</p>
                </div>
            </component>
        </div>

        <!-- Por origen -->
        <section class="flex flex-col gap-3">
            <div class="flex items-baseline justify-between">
                <h2 class="text-lg font-semibold">Leads por origen</h2>
                <p class="text-xs text-muted-foreground">Haz clic en un origen para filtrar el tablero.</p>
            </div>
            <div class="grid grid-cols-2 gap-3 md:grid-cols-4 xl:grid-cols-7">
                <button
                    v-for="s in sources"
                    :key="s.id"
                    type="button"
                    :class="cn('flex flex-col gap-2 rounded-2xl border bg-card p-3.5 text-left shadow-sm shadow-black/[0.03] transition hover:-translate-y-0.5 hover:shadow-md', f.sources.includes(String(s.id)) && 'ring-2 ring-primary', f.sources.length && !f.sources.includes(String(s.id)) && 'opacity-50')"
                    @click="toggleSource(s.id)"
                >
                    <div class="flex items-center gap-2">
                        <span class="flex size-7 items-center justify-center rounded-lg text-white [&_svg]:size-4" :style="{ backgroundColor: s.color }"><SourceIcon :name="s.icon" /></span>
                        <span class="truncate text-sm font-medium">{{ s.name }}</span>
                    </div>
                    <div class="flex items-end justify-between">
                        <span class="text-2xl leading-none font-bold tabular-nums">{{ s.count }}</span>
                        <span class="text-xs text-muted-foreground">{{ s.pct }}%</span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-muted"><div class="h-full rounded-full transition-all" :style="{ width: s.pct + '%', backgroundColor: s.color }" /></div>
                </button>
            </div>
        </section>

        <!-- Kanban -->
        <section class="flex min-w-0 flex-col gap-3">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <h2 class="text-lg font-semibold">Tablero</h2>
                <p v-if="can.move" class="text-xs text-muted-foreground">Arrastra para cambiar de etapa · clic en una tarjeta para abrirla · marca la casilla para acciones masivas</p>
            </div>
            <KanbanToolbar v-model="f" :periods="periods" :sorts="sorts" :priorities="priorities" :sources="sources" :users="users" :tags="tags" :can-view-all="can.viewAll" />
            <KanbanBoard
                :board="board"
                :stages="stages"
                :users="users"
                :tags="tags"
                :priorities="priorities"
                :query="query"
                :manual="f.sort === 'manual'"
                :page-size="columnPage"
                :manual-source-id="manualSourceId"
                :user-id="page.props.auth.user.id"
                :can="can"
                @changed="reload"
            />
        </section>
    </div>
</template>
