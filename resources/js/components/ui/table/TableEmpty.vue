<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"
import TableCell from "./TableCell.vue"
import TableRow from "./TableRow.vue"

const props = withDefaults(defineProps<{ colspan?: number; class?: HTMLAttributes["class"] }>(), { colspan: 1 })
</script>

<template>
  <TableRow class="hover:bg-transparent">
    <TableCell :colspan="props.colspan" :class="cn('text-muted-foreground h-24 text-center', props.class)">
      <slot>Sin resultados.</slot>
    </TableCell>
  </TableRow>
</template>
