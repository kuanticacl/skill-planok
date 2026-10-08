<script setup lang="ts">
import { Copy, GripVertical, Trash2 } from '@lucide/vue';
import draggable from 'vuedraggable';
import { blockIcons } from '@/lib/blockIcons';
import { BLOCKS } from '@/lib/emailBuilder';
import type { Block, BlockType } from '@/lib/emailBuilder';
import { cn } from '@/lib/utils';

const blocks = defineModel<Block[]>({ required: true });
defineProps<{ selectedId: string | null }>();
const emit = defineEmits<{ select: [string]; duplicate: [Block]; remove: [Block] }>();

const summary = (b: Block) => {
    const p = b.props;
    const text = p.text ?? p.label ?? p.title ?? p.brandText ?? p.collection ?? p.company ?? '';
    return String(text).replace(/\s+/g, ' ').slice(0, 42);
};
</script>

<template>
    <draggable v-model="blocks" item-key="id" handle=".bl-handle" :animation="150" ghost-class="opacity-40" class="flex flex-col gap-1.5">
        <template #item="{ element: b }">
            <div
                :class="cn('group flex cursor-pointer items-center gap-2 rounded-xl border bg-card px-2 py-2 transition hover:border-primary/40', selectedId === b.id && 'border-primary bg-accent/50 ring-1 ring-primary/40')"
                @click="emit('select', b.id)"
            >
                <GripVertical class="bl-handle size-4 shrink-0 cursor-grab text-muted-foreground" />
                <component :is="blockIcons[b.type as BlockType]" class="size-4 shrink-0 text-primary" />
                <div class="min-w-0 flex-1">
                    <p class="text-xs font-semibold">{{ BLOCKS[b.type as BlockType].label }}</p>
                    <p class="truncate text-[11px] text-muted-foreground">{{ summary(b) || '—' }}</p>
                </div>
                <div class="flex shrink-0 gap-0.5 opacity-0 transition group-hover:opacity-100">
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-muted hover:text-foreground" title="Duplicar" @click.stop="emit('duplicate', b)"><Copy class="size-3.5" /></button>
                    <button type="button" class="rounded p-1 text-muted-foreground hover:bg-destructive/10 hover:text-destructive" title="Eliminar" @click.stop="emit('remove', b)"><Trash2 class="size-3.5" /></button>
                </div>
            </div>
        </template>
    </draggable>
</template>
