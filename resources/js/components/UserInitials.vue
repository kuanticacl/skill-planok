<script setup lang="ts">
import { initials } from '@/lib/format';
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{ name?: string | null; class?: HTMLAttributes['class'] }>();
</script>

<template>
    <span
        :title="name ?? 'Sin asignar'"
        :class="
            cn(
                'inline-flex size-6 shrink-0 items-center justify-center rounded-full bg-accent text-[10px] font-semibold text-accent-foreground',
                !name && 'border border-dashed bg-transparent text-muted-foreground',
                $props.class,
            )
        "
    >
        {{ name ? initials(name) : '?' }}
    </span>
</template>
