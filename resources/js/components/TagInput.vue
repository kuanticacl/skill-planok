<script setup lang="ts">
import { X } from '@lucide/vue';
import { ref } from 'vue';

defineProps<{ suggestions?: string[]; placeholder?: string }>();
const model = defineModel<string[]>({ default: () => [] });
const draft = ref('');
const listId = `tags-${Math.random().toString(36).slice(2, 8)}`;

const add = () => {
    const tag = draft.value.trim().replace(/,/g, '').slice(0, 30);
    if (tag && !model.value.includes(tag)) model.value = [...model.value, tag];
    draft.value = '';
};
const remove = (t: string) => (model.value = model.value.filter((x) => x !== t));
</script>

<template>
    <div
        class="flex min-h-9 flex-wrap items-center gap-1.5 rounded-md border border-input bg-transparent px-2 py-1 focus-within:border-ring focus-within:ring-[3px] focus-within:ring-ring/50"
    >
        <span
            v-for="t in model"
            :key="t"
            class="inline-flex items-center gap-1 rounded-full bg-accent px-2 py-0.5 text-xs font-medium text-accent-foreground"
        >
            {{ t }}
            <button type="button" class="hover:text-destructive" :aria-label="`Quitar ${t}`" @click="remove(t)"><X class="size-3" /></button>
        </span>
        <input
            v-model="draft"
            :list="listId"
            class="min-w-24 flex-1 bg-transparent text-sm outline-none placeholder:text-muted-foreground"
            :placeholder="model.length ? '' : (placeholder ?? 'Agregar etiqueta…')"
            @keydown.enter.prevent="add"
            @keydown.comma.prevent="add"
            @blur="add"
        />
        <datalist :id="listId">
            <option v-for="s in suggestions ?? []" :key="s" :value="s" />
        </datalist>
    </div>
</template>
