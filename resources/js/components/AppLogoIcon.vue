<script setup lang="ts">
import type { HTMLAttributes } from 'vue';

defineOptions({
    inheritAttrs: false,
});

type Props = {
    className?: HTMLAttributes['class'];
};

defineProps<Props>();
</script>

<template>
    <!-- Isotipo provisional «eC» de ECORTESCL -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 24 24"
        :class="className"
        v-bind="$attrs"
    >
        <text x="12" y="17.5" text-anchor="middle" font-family="Asap, ui-sans-serif, system-ui, sans-serif" font-weight="700" font-size="17" letter-spacing="-0.8" fill="currentColor">eC</text>
    </svg>
</template>
