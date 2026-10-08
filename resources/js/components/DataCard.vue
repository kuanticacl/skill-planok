<script setup lang="ts">
import { cn } from '@/lib/utils';
import type { HTMLAttributes } from 'vue';

defineProps<{ class?: HTMLAttributes['class'] }>();
</script>

<template>
    <div
        :class="
            cn(
                'overflow-hidden rounded-2xl border bg-card shadow-sm shadow-black/[0.03]',
                $props.class,
            )
        "
    >
        <slot />
    </div>
</template>
