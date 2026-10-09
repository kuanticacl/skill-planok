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
    <!-- Isotipo de ECORTESCL: dos pares de chevrones (toma el color actual) -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="0 0 32 32"
        :class="className"
        v-bind="$attrs"
    >
        <g fill="none" stroke="currentColor" stroke-width="2.4">
            <path d="M12.8 8.3 8 13.8l5.1 5.4M16.6 8.3l-4.8 5.5 5.1 5.4M16 13.4l5.1 5.4-5.1 5.4M19.5 13.4l5.1 5.4-5.1 5.4" />
        </g>
    </svg>
</template>
