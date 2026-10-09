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
    <!-- Logo de ECORTESCL: isotipo verde + wordmark (el texto toma el color actual y la tipografía Open Sans) -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 150 32"
        role="img"
        aria-label="ECORTESCL"
        :class="$props.class"
        v-bind="$attrs"
    >
        <rect width="32" height="32" rx="8.6" fill="#64d989" />
        <g fill="none" stroke="#fff" stroke-width="2.1">
            <path d="M12.8 8.3 8 13.8l5.1 5.4M16.6 8.3l-4.8 5.5 5.1 5.4M16 13.4l5.1 5.4-5.1 5.4M19.5 13.4l5.1 5.4-5.1 5.4" />
        </g>
        <text x="40" y="23.5" font-family="'Open Sans', ui-sans-serif, system-ui, sans-serif" font-weight="700" font-size="22" letter-spacing="-0.7" fill="currentColor">eCortesCL</text>
    </svg>
</template>
