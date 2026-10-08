<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = defineProps<{ class?: HTMLAttributes["class"] }>()
</script>

<template>
  <tfoot data-slot="table-footer" :class="cn('bg-muted/50 border-t font-medium', props.class)">
    <slot />
  </tfoot>
</template>
