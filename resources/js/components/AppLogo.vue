<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import QuiebreLogo from '@/components/QuiebreLogo.vue';
</script>

<template>
    <!-- Expandido: wordmark. Colapsado: isotipo Q -->
    <span class="flex items-center group-data-[collapsible=icon]:hidden">
        <QuiebreLogo class="h-5 w-[6.25rem] shrink-0 text-foreground" />
    </span>
    <span
        class="hidden items-center justify-center group-data-[collapsible=icon]:flex"
    >
        <AppLogoIcon class="size-6 text-primary" />
    </span>
    <span
        class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-primary uppercase group-data-[collapsible=icon]:hidden"
    >
        CRM
    </span>
</template>
