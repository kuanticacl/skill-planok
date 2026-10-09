<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

defineProps<{
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <!-- Wordmark provisional de ECORTESCL (tipografía Asap). Reemplazar por el logo oficial en public/brand. -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 120 24"
        role="img"
        aria-label="ECORTESCL"
        :class="$props.class"
        v-bind="$attrs"
    >
        <text x="0" y="19" font-family="Asap, ui-sans-serif, system-ui, sans-serif" font-weight="700" font-size="22" letter-spacing="-0.9" fill="currentColor">eCortes<tspan fill="var(--color-primary, #2563eb)">CL</tspan></text>
    </svg>
</template>
