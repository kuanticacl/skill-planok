<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import BrandLogo from '@/components/BrandLogo.vue';
</script>

<template>
    <!-- Expandido: logo completo. Colapsado: isotipo -->
    <span class="flex items-center group-data-[collapsible=icon]:hidden">
        <BrandLogo class="h-7 w-[8.4rem] shrink-0 text-white" />
    </span>
    <span
        class="hidden items-center justify-center group-data-[collapsible=icon]:flex"
    >
        <span class="flex size-8 items-center justify-center rounded-lg bg-[#64d989] text-white"><AppLogoIcon class="size-6" /></span>
    </span>
    <span
        class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-primary uppercase group-data-[collapsible=icon]:hidden"
    >
        CRM
    </span>
</template>
