<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import type { InvoiceRow } from '@/types/billing';

const props = defineProps<{ invoice: InvoiceRow | null }>();
const emit = defineEmits<{ close: []; done: [] }>();

const form = useForm({ pdf: null as File | null, number: '', issue: true });
watch(() => props.invoice, (i) => { form.reset(); form.clearErrors(); form.number = i?.number ?? ''; form.issue = i?.status === 'scheduled'; });

const submit = () => {
    if (!props.invoice) return;
    form.post(`/billing/invoices/${props.invoice.id}/pdf`, { forceFormData: true, preserveScroll: true, onSuccess: () => emit('done') });
};
</script>

<template>
    <Dialog :open="!!invoice" @update:open="(v: boolean) => !v && emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>Adjuntar factura (PDF)</DialogTitle><DialogDescription>{{ invoice?.concept }}</DialogDescription></DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <FormField label="Archivo PDF" for="pf-file" required :error="form.errors.pdf"><input id="pf-file" type="file" accept="application/pdf" required class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary" @change="(e: Event) => (form.pdf = (e.target as HTMLInputElement).files?.[0] ?? null)" /></FormField>
                <FormField label="N° de factura" for="pf-number" :error="form.errors.number"><Input id="pf-number" v-model="form.number" placeholder="F-1024" /></FormField>
                <label v-if="invoice?.status === 'scheduled'" class="flex items-start gap-3 rounded-xl border p-3 text-sm"><Switch :model-value="form.issue" @update:model-value="(v: boolean) => (form.issue = v)" /><span><strong>Emitir ahora</strong><span class="block text-xs text-muted-foreground">Pasa a «por pagar» y el cliente la ve en su portal.</span></span></label>
                <DialogFooter class="gap-2"><Button type="button" variant="outline" @click="emit('close')">Cancelar</Button><Button type="submit" :disabled="form.processing || !form.pdf"><Spinner v-if="form.processing" /> Guardar</Button></DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
