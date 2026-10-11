<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { money } from '@/lib/billingUi';
import type { InvoiceRow } from '@/types/billing';

const props = defineProps<{ invoice: InvoiceRow | null }>();
const emit = defineEmits<{ close: []; done: [] }>();

const form = useForm({ payment_method: 'Transferencia', payment_reference: '', paid_at: new Date().toISOString().slice(0, 10) });
watch(() => props.invoice, () => form.reset());

const submit = () => {
    if (!props.invoice) return;
    form.post(`/billing/invoices/${props.invoice.id}/pay`, { preserveScroll: true, onSuccess: () => emit('done') });
};
</script>

<template>
    <Dialog :open="!!invoice" @update:open="(v: boolean) => !v && emit('close')">
        <DialogContent class="sm:max-w-md">
            <DialogHeader><DialogTitle>Marcar como pagada</DialogTitle><DialogDescription>{{ invoice?.number ?? '' }} {{ invoice?.concept }} · {{ invoice ? money(invoice.amount_total, invoice.currency) : '' }}</DialogDescription></DialogHeader>
            <form class="grid gap-4" @submit.prevent="submit">
                <FormField label="Fecha de pago" for="pd-date" :error="form.errors.paid_at"><Input id="pd-date" v-model="form.paid_at" type="date" /></FormField>
                <FormField label="Medio de pago" for="pd-method" :error="form.errors.payment_method">
                    <NativeSelect id="pd-method" v-model="form.payment_method"><option>Transferencia</option><option>Webpay</option><option>Flow</option><option>MercadoPago</option><option>Tarjeta</option><option>Efectivo / cheque</option><option>Otro</option></NativeSelect>
                </FormField>
                <FormField label="Referencia / comprobante" for="pd-ref" hint="Opcional: N° de operación." :error="form.errors.payment_reference"><Input id="pd-ref" v-model="form.payment_reference" /></FormField>
                <DialogFooter class="gap-2"><Button type="button" variant="outline" @click="emit('close')">Cancelar</Button><Button type="submit" :disabled="form.processing"><Spinner v-if="form.processing" /> Confirmar pago</Button></DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
