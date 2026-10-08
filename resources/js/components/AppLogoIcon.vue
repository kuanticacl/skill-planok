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
    <!-- Isotipo "Q" de Quiebre -->
    <svg
        xmlns="http://www.w3.org/2000/svg"
        viewBox="1 2.6 20.2 20.2"
        :class="className"
        v-bind="$attrs"
    >
        <path
            fill="currentColor"
            d="M12.819,3.305h-3.165c-4.405.012-7.968,3.588-7.965,7.993v3.175c-.002,4.404,3.561,7.979,7.965,7.991h3.165c1.504,0,2.977-.428,4.246-1.235l1.178,1.181,2.388-2.4-1.156-1.161c.855-1.301,1.309-2.824,1.308-4.38v-3.171c.003-4.404-3.56-7.98-7.964-7.993M9.654,19.156c-2.578-.015-4.662-2.108-4.665-4.686v-3.172c.002-2.579,2.086-4.673,4.665-4.687h3.165c2.581.012,4.668,2.106,4.67,4.687v3.175c-.004,2.58-2.089,4.672-4.669,4.685l-3.166-.002Z"
        />
    </svg>
</template>
