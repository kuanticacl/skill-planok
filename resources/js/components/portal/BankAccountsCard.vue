<script setup lang="ts">
import { Check, Copy, Landmark } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';

export type PortalBank = { accounts: { bank: string; account_type: string; number: string; holder: string; tax_id: string; email: string }[]; note: string };
defineProps<{ bank: PortalBank }>();

const copied = ref<string | null>(null);
const copy = async (key: string, value: string) => {
    try { await navigator.clipboard.writeText(value); copied.value = key; setTimeout(() => (copied.value = null), 1800); } catch { /* sin permiso de portapapeles */ }
};
</script>

<template>
    <section v-if="bank.accounts.length" class="grid gap-3">
        <h2 class="flex items-center gap-2 font-semibold"><Landmark class="size-4" /> Paga por transferencia</h2>
        <div class="grid gap-3 md:grid-cols-2">
            <div v-for="(a, i) in bank.accounts" :key="i" class="rounded-2xl border bg-card p-4 shadow-sm shadow-black/[0.03]">
                <p class="font-semibold">{{ a.bank }} <span class="font-normal text-muted-foreground">· {{ a.account_type }}</span></p>
                <dl class="mt-3 grid gap-2 text-sm">
                    <div class="flex items-center justify-between gap-2"><div class="min-w-0"><dt class="text-xs text-muted-foreground">N° de cuenta</dt><dd class="font-medium tabular-nums">{{ a.number }}</dd></div>
                        <Button variant="ghost" size="icon-sm" :title="'Copiar número de cuenta'" @click="copy(`n${i}`, a.number)"><Check v-if="copied === `n${i}`" class="text-brand-green" /><Copy v-else /></Button></div>
                    <div v-if="a.holder"><dt class="text-xs text-muted-foreground">Titular</dt><dd>{{ a.holder }}</dd></div>
                    <div v-if="a.tax_id" class="flex items-center justify-between gap-2"><div><dt class="text-xs text-muted-foreground">RUT</dt><dd>{{ a.tax_id }}</dd></div>
                        <Button variant="ghost" size="icon-sm" title="Copiar RUT" @click="copy(`r${i}`, a.tax_id)"><Check v-if="copied === `r${i}`" class="text-brand-green" /><Copy v-else /></Button></div>
                    <div v-if="a.email"><dt class="text-xs text-muted-foreground">Enviar comprobante a</dt><dd class="break-all">{{ a.email }}</dd></div>
                </dl>
            </div>
        </div>
        <p v-if="bank.note" class="text-sm text-muted-foreground">{{ bank.note }}</p>
    </section>
</template>
