<script setup lang="ts">
import { computed } from 'vue';
import { invoiceStatus, serviceStatus } from '@/lib/billingUi';
import { cn } from '@/lib/utils';

const props = defineProps<{ kind: 'invoice' | 'service'; status: string }>();
const meta = computed(() => (props.kind === 'invoice' ? invoiceStatus : serviceStatus)[props.status] ?? { label: props.status, cls: 'bg-muted text-muted-foreground' });
</script>

<template>
    <span :class="cn('inline-flex items-center rounded-full px-2.5 py-0.5 text-xs font-medium whitespace-nowrap', meta.cls)">{{ meta.label }}</span>
</template>
