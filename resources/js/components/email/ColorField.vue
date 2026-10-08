<script setup lang="ts">
import { X } from '@lucide/vue';
import { brandColors } from '@/lib/sourceIcons';

const model = defineModel<string>({ default: '' });
</script>

<template>
    <div class="grid gap-2">
        <div class="flex items-center gap-2">
            <label class="relative size-9 shrink-0 cursor-pointer overflow-hidden rounded-md border" :style="{ backgroundColor: model || 'transparent' }">
                <input type="color" :value="model || '#ffffff'" class="absolute inset-0 size-full cursor-pointer opacity-0" @input="(e) => (model = (e.target as HTMLInputElement).value.toUpperCase())" />
            </label>
            <input v-model="model" maxlength="7" placeholder="#RRGGBB" class="h-9 w-28 rounded-md border border-input bg-transparent px-3 font-mono text-sm uppercase outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" />
            <button v-if="model" type="button" class="text-muted-foreground hover:text-foreground" title="Quitar color" @click="model = ''"><X class="size-4" /></button>
        </div>
        <div class="flex flex-wrap gap-1.5">
            <button v-for="c in [...brandColors, '#FFFFFF', '#F4F4F4']" :key="c" type="button" class="size-5 rounded-full border" :style="{ backgroundColor: c }" :title="c" @click="model = c.toUpperCase()" />
        </div>
    </div>
</template>
