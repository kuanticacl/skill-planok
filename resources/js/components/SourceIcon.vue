<script setup lang="ts">
import { Globe } from '@lucide/vue';
import { computed } from 'vue';
import { sourceIcons } from '@/lib/sourceIcons';

const props = defineProps<{ name?: string | null }>();
const icon = computed(() => sourceIcons[props.name ?? ''] ?? Globe);
</script>

<template>
    <component :is="icon" />
</template>
