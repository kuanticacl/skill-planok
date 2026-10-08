<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { useVModel } from "@vueuse/core"
import { ChevronDown } from "@lucide/vue"
import { cn } from "@/lib/utils"

defineOptions({ inheritAttrs: false })

const props = defineProps<{
  class?: HTMLAttributes["class"]
  modelValue?: string | number | null
}>()

const emits = defineEmits<{
  (e: "update:modelValue", payload: string | number | null): void
}>()

const modelValue = useVModel(props, "modelValue", emits, { passive: true })
</script>

<template>
  <div class="relative">
    <select
      v-model="modelValue"
      v-bind="$attrs"
      data-slot="native-select"
      :class="cn('border-input dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 aria-invalid:border-destructive h-9 w-full appearance-none rounded-md border bg-transparent pr-8 pl-3 text-sm shadow-xs outline-none focus-visible:ring-[3px] disabled:cursor-not-allowed disabled:opacity-50', props.class)"
    >
      <slot />
    </select>
    <ChevronDown class="text-muted-foreground pointer-events-none absolute top-1/2 right-2.5 size-4 -translate-y-1/2" />
  </div>
</template>
