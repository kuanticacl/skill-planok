<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Label } from '@/components/ui/label';

defineProps<{
    label: string;
    for?: string;
    error?: string;
    hint?: string;
    required?: boolean;
}>();
</script>

<template>
    <div class="grid gap-2">
        <Label :for="$props.for" class="gap-1">
            {{ label }}
            <span v-if="required" class="text-primary">*</span>
        </Label>
        <slot />
        <p v-if="hint && !error" class="text-xs text-muted-foreground">
            {{ hint }}
        </p>
        <InputError :message="error" />
    </div>
</template>
