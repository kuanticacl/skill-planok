<script setup lang="ts">
import { router } from '@inertiajs/vue3';
import { ChevronDown } from '@lucide/vue';
import { ref, watch } from 'vue';
import draggable from 'vuedraggable';
import { toast } from 'vue-sonner';
import KanbanCard from '@/components/kanban/KanbanCard.vue';
import { sendJson } from '@/lib/http';
import { column } from '@/routes/kanban';
import { move } from '@/routes/leads';
import type { BoardColumn, LeadCard } from '@/types';

const props = defineProps<{
    board: BoardColumn[];
    canMove: boolean;
    filters: Record<string, string | null>;
    pageSize: number;
}>();

const emit = defineEmits<{ moved: [] }>();

// Copia local editable (las props son de solo lectura y reactivas).
const clone = (v: BoardColumn[]): BoardColumn[] => JSON.parse(JSON.stringify(v));
const columns = ref<BoardColumn[]>(clone(props.board));
watch(() => props.board, (v) => (columns.value = clone(v)));

type DragChange = {
    added?: { element: LeadCard; newIndex: number };
    moved?: { element: LeadCard; newIndex: number };
};

const onChange = async (col: BoardColumn, change: DragChange) => {
    const evt = change.added ?? change.moved;
    if (!evt) return;

    const lead = evt.element;
    const after = col.leads[evt.newIndex - 1];

    if (change.added) {
        // Ajuste inmediato de contadores: origen -1, destino +1.
        const from = columns.value.find((c) => c.id === lead.stage_id);
        if (from) from.total = Math.max(0, from.total - 1);
        col.total += 1;
        lead.stage_id = col.id;
    }

    try {
        await sendJson('PUT', move(lead.id).url, {
            stage_id: col.id,
            after_id: after?.id ?? null,
        });
        emit('moved');
    } catch {
        toast.error('No se pudo mover el lead. Se restauró el tablero.');
        router.reload({ only: ['board', 'stats', 'sources'] });
    }
};

const loading = ref<number | null>(null);
const loadMore = async (col: BoardColumn) => {
    loading.value = col.id;
    try {
        const query = new URLSearchParams(
            Object.entries(props.filters).filter(([, v]) => v) as [string, string][],
        );
        query.set('offset', String(col.leads.length));
        const data = await sendJson<{ leads: LeadCard[] }>('GET', `${column(col.id).url}?${query}`);
        const known = new Set(col.leads.map((l) => l.id));
        col.leads.push(...data.leads.filter((l) => !known.has(l.id)));
    } catch {
        toast.error('No se pudieron cargar más leads.');
    } finally {
        loading.value = null;
    }
};
</script>

<template>
    <div class="-mx-4 flex snap-x gap-4 overflow-x-auto px-4 pb-4 md:mx-0 md:px-0">
        <section
            v-for="col in columns"
            :key="col.id"
            class="flex max-h-[calc(100vh-14rem)] min-h-64 w-72 shrink-0 snap-start flex-col rounded-2xl bg-muted/60 ring-1 ring-border/60"
        >
            <header class="flex items-center gap-2 px-3.5 pt-3 pb-2">
                <span class="size-3 rounded-full" :style="{ backgroundColor: col.color }" />
                <h3 class="flex-1 truncate text-sm font-semibold">{{ col.name }}</h3>
                <span
                    class="rounded-full px-2 py-0.5 text-xs font-semibold text-white"
                    :style="{ backgroundColor: col.color }"
                >
                    {{ col.total }}
                </span>
            </header>
            <div class="mx-3.5 mb-2 h-0.5 rounded-full" :style="{ backgroundColor: col.color }" />

            <draggable
                v-model="col.leads"
                group="leads"
                item-key="id"
                :disabled="!canMove"
                :animation="160"
                ghost-class="opacity-30"
                drag-class="rotate-1"
                class="flex min-h-16 flex-1 flex-col gap-2 overflow-y-auto px-2.5 pb-2.5"
                @change="(c: DragChange) => onChange(col, c)"
            >
                <template #item="{ element }">
                    <KanbanCard :lead="element" />
                </template>
                <template #footer>
                    <p
                        v-if="!col.leads.length"
                        class="rounded-xl border border-dashed py-6 text-center text-xs text-muted-foreground"
                    >
                        Sin leads
                    </p>
                </template>
            </draggable>

            <button
                v-if="col.leads.length < col.total"
                type="button"
                class="mx-2.5 mb-2.5 flex items-center justify-center gap-1 rounded-xl py-2 text-xs font-medium text-primary hover:bg-accent disabled:opacity-60"
                :disabled="loading === col.id"
                @click="loadMore(col)"
            >
                <ChevronDown class="size-3.5" />
                Cargar {{ Math.min(pageSize, col.total - col.leads.length) }} más
                ({{ col.total - col.leads.length }} restantes)
            </button>
        </section>
    </div>
</template>
