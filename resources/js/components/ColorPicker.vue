<script setup lang="ts">
import { Check } from '@lucide/vue';
import { brandColors } from '@/lib/sourceIcons';

const model = defineModel<string>({ required: true });
</script>

<template>
    <div class="flex flex-wrap items-center gap-2">
        <button
            v-for="c in brandColors"
            :key="c"
            type="button"
            class="flex size-7 items-center justify-center rounded-full ring-offset-2 ring-offset-background transition hover:scale-110"
            :class="model.toLowerCase() === c.toLowerCase() ? 'ring-2 ring-foreground/70' : ''"
            :style="{ backgroundColor: c }"
            :title="c"
            @click="model = c"
        >
            <Check v-if="model.toLowerCase() === c.toLowerCase()" class="size-4 text-white" />
        </button>
        <label
            class="relative flex size-7 cursor-pointer items-center justify-center overflow-hidden rounded-full border border-dashed text-[10px] text-muted-foreground"
            title="Color personalizado"
        >
            +
            <input
                v-model="model"
                type="color"
                class="absolute inset-0 size-full cursor-pointer opacity-0"
            />
        </label>
    </div>
</template>
