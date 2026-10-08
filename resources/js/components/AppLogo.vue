<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import QuiebreLogo from '@/components/QuiebreLogo.vue';
</script>

<template>
    <!-- Expandido: wordmark. Colapsado: isotipo Q -->
    <QuiebreLogo
        class="h-5 w-auto text-foreground group-data-[collapsible=icon]:hidden"
    />
    <AppLogoIcon
        class="hidden size-6 text-primary group-data-[collapsible=icon]:block"
    />
    <span
        class="ml-auto rounded-full bg-primary/10 px-2 py-0.5 text-[10px] font-semibold tracking-wide text-primary uppercase group-data-[collapsible=icon]:hidden"
    >
        CRM
    </span>
</template>
