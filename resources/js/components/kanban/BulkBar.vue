<script setup lang="ts">
import { Flag, Tag, Trash2, UserRound, X, ArrowRightLeft } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import type { StageRef, UserOption } from '@/types';

defineProps<{
    count: number;
    stages: StageRef[];
    users: UserOption[];
    priorities: Record<string, string>;
    can: { move: boolean; assign: boolean; update: boolean; delete: boolean };
    busy: boolean;
}>();
const emit = defineEmits<{
    clear: [];
    action: [{ action: string; stage_id?: number; assigned_to?: number | null; priority?: string; tag?: string }];
}>();

const tag = ref('');
const reset = (e: Event) => ((e.target as HTMLSelectElement).value = '');
</script>

<template>
    <div class="fixed inset-x-0 bottom-4 z-40 flex justify-center px-4">
        <div class="flex max-w-full flex-wrap items-center gap-2 rounded-2xl border bg-card px-4 py-3 shadow-xl shadow-black/15">
            <span class="mr-1 text-sm font-semibold">{{ count }} seleccionado{{ count === 1 ? '' : 's' }}</span>

            <label v-if="can.move" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <ArrowRightLeft class="size-4" />
                <NativeSelect model-value="" class="w-40" :disabled="busy" @change="(e: Event) => { const v = (e.target as HTMLSelectElement).value; if (v) emit('action', { action: 'move', stage_id: Number(v) }); reset(e); }">
                    <option value="">Mover a…</option>
                    <option v-for="s in stages" :key="s.id" :value="s.id">{{ s.name }}</option>
                </NativeSelect>
            </label>

            <label v-if="can.assign" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <UserRound class="size-4" />
                <NativeSelect model-value="" class="w-40" :disabled="busy" @change="(e: Event) => { const v = (e.target as HTMLSelectElement).value; if (v) emit('action', { action: 'assign', assigned_to: v === 'none' ? null : Number(v) }); reset(e); }">
                    <option value="">Asignar a…</option>
                    <option value="none">Sin asignar</option>
                    <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                </NativeSelect>
            </label>

            <label v-if="can.update" class="flex items-center gap-1.5 text-xs text-muted-foreground">
                <Flag class="size-4" />
                <NativeSelect model-value="" class="w-36" :disabled="busy" @change="(e: Event) => { const v = (e.target as HTMLSelectElement).value; if (v) emit('action', { action: 'priority', priority: v }); reset(e); }">
                    <option value="">Prioridad…</option>
                    <option v-for="(label, key) in priorities" :key="key" :value="key">{{ label }}</option>
                </NativeSelect>
            </label>

            <form v-if="can.update" class="flex items-center gap-1.5" @submit.prevent="tag.trim() && (emit('action', { action: 'add_tag', tag: tag.trim() }), (tag = ''))">
                <Tag class="size-4 text-muted-foreground" />
                <Input v-model="tag" class="h-9 w-32" placeholder="+ etiqueta" :disabled="busy" />
            </form>

            <Button v-if="can.delete" variant="outline" size="sm" class="text-destructive hover:text-destructive" :disabled="busy" @click="emit('action', { action: 'delete' })"><Trash2 /> Eliminar</Button>
            <Button variant="ghost" size="icon-sm" title="Limpiar selección" @click="emit('clear')"><X /></Button>
        </div>
    </div>
</template>
