<script setup lang="ts">
import { blockIcons } from '@/lib/blockIcons';
import { BLOCKS } from '@/lib/emailBuilder';
import type { BlockType } from '@/lib/emailBuilder';

const emit = defineEmits<{ add: [BlockType] }>();
const order: BlockType[] = ['header', 'heading', 'text', 'image', 'button', 'columns', 'list', 'divider', 'spacer', 'social', 'footer', 'html'];
</script>

<template>
    <div class="grid grid-cols-2 gap-2">
        <button
            v-for="t in order"
            :key="t"
            type="button"
            :title="BLOCKS[t].description"
            class="flex flex-col items-center gap-1.5 rounded-xl border bg-card px-2 py-3 text-center transition hover:-translate-y-0.5 hover:border-primary/50 hover:shadow-sm"
            @click="emit('add', t)"
        >
            <component :is="blockIcons[t]" class="size-5 text-primary" />
            <span class="text-xs font-medium">{{ BLOCKS[t].label }}</span>
        </button>
    </div>
</template>
