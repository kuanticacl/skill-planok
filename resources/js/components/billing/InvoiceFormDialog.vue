<script setup lang="ts">
import { useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { money } from '@/lib/billingUi';
import type { InvoiceRow } from '@/types/billing';

const props = defineProps<{
    invoice: InvoiceRow | null;
    defaults?: { client_id?: number | null; client_service_id?: number | null } | null;
    clients: { id: number; name: string }[];
    services: { id: number; name: string; client_id: number; currency: string; price: number }[];
    taxRate: number;
}>();
const open = defineModel<boolean>('open', { default: false });

const blank = () => ({
    client_id: (props.defaults?.client_id ?? '') as number | string,
    client_service_id: (props.defaults?.client_service_id ?? '') as number | string,
    number: '', concept: '', period_start: '', period_end: '', issue_date: '', due_date: new Date(Date.now() + 10 * 86400000).toISOString().slice(0, 10),
    currency: 'CLP', amount_net: '', tax_rate: String(props.taxRate), auto_remind: false, payment_link: '', notes: '', pdf: null as File | null, issue: false,
});
const form = useForm(blank());
const fileInput = ref<HTMLInputElement | null>(null);

watch(open, (o) => {
    if (!o) return;
    form.reset();
    form.clearErrors();
    const i = props.invoice;
    Object.assign(form, i
        ? {
              client_id: i.client_id, client_service_id: i.client_service_id ?? '', number: i.number ?? '', concept: i.concept, period_start: i.period_start ?? '', period_end: i.period_end ?? '',
              issue_date: i.issue_date ?? '', due_date: i.due_date, currency: i.currency, amount_net: String(i.amount_net), tax_rate: String(i.tax_rate), auto_remind: i.auto_remind,
              payment_link: i.payment_link ?? '', notes: i.notes ?? '', pdf: null, issue: false,
          }
        : blank());
    if (fileInput.value) fileInput.value.value = '';
});

const clientServices = computed(() => props.services.filter((s) => s.client_id === Number(form.client_id)));

const fromService = () => {
    const s = props.services.find((x) => x.id === Number(form.client_service_id));
    if (!s) return;
    form.currency = s.currency;
    form.amount_net = String(s.price);
    form.concept = form.concept || s.name;
};

const total = computed(() => {
    const net = Number(form.amount_net);
    if (!Number.isFinite(net) || form.amount_net === '') return null;
    const t = net * (1 + (Number(form.tax_rate) || 0) / 100);
    return form.currency === 'UF' ? Math.round(t * 100) / 100 : Math.round(t);
});

const onFile = (e: Event) => (form.pdf = (e.target as HTMLInputElement).files?.[0] ?? null);

const submit = () => {
    const url = props.invoice ? `/billing/invoices/${props.invoice.id}` : '/billing/invoices';
    form.transform((d) => ({ ...d, ...(props.invoice ? { _method: 'put' } : {}), issue: d.pdf ? d.issue : false }))
        .post(url, { forceFormData: true, preserveScroll: true, onSuccess: () => (open.value = false) });
};
</script>

<template>
    <Dialog v-model:open="open">
        <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-2xl">
            <DialogHeader>
                <DialogTitle>{{ invoice ? 'Editar cobro' : 'Nuevo cobro' }}</DialogTitle>
                <DialogDescription>La factura la emites en tu sistema de facturación: aquí registras el cobro y adjuntas su PDF.</DialogDescription>
            </DialogHeader>

            <form class="grid gap-4 sm:grid-cols-2" @submit.prevent="submit">
                <FormField label="Empresa" for="inv-client" required :error="form.errors.client_id">
                    <NativeSelect id="inv-client" v-model="form.client_id" :disabled="!!invoice"><option value="">Selecciona…</option><option v-for="c in clients" :key="c.id" :value="c.id">{{ c.name }}</option></NativeSelect>
                </FormField>
                <FormField label="Servicio" for="inv-service" hint="Opcional." :error="form.errors.client_service_id">
                    <NativeSelect id="inv-service" v-model="form.client_service_id" :disabled="!form.client_id" @update:model-value="fromService"><option value="">Sin servicio</option><option v-for="s in clientServices" :key="s.id" :value="s.id">{{ s.name }}</option></NativeSelect>
                </FormField>
                <FormField label="Concepto" for="inv-concept" required class="sm:col-span-2" :error="form.errors.concept"><Input id="inv-concept" v-model="form.concept" placeholder="Diseño web · 50% de anticipo" /></FormField>

                <FormField label="Moneda" for="inv-cur"><NativeSelect id="inv-cur" v-model="form.currency"><option value="CLP">Pesos (CLP)</option><option value="UF">UF</option></NativeSelect></FormField>
                <FormField label="Monto neto" for="inv-net" required :error="form.errors.amount_net"><Input id="inv-net" v-model="form.amount_net" type="number" min="0" :step="form.currency === 'UF' ? 0.01 : 1" /></FormField>
                <FormField label="IVA %" for="inv-tax" :error="form.errors.tax_rate"><Input id="inv-tax" v-model="form.tax_rate" type="number" min="0" max="100" step="0.01" /></FormField>
                <div class="flex items-end pb-1 text-sm"><span class="text-muted-foreground">Total a cobrar:&nbsp;</span><strong class="text-base tabular-nums">{{ total === null ? '—' : money(total, form.currency) }}</strong></div>

                <FormField label="N° de factura" for="inv-number" hint="Folio del documento emitido." :error="form.errors.number"><Input id="inv-number" v-model="form.number" placeholder="F-1024" /></FormField>
                <FormField label="Vencimiento" for="inv-due" required :error="form.errors.due_date"><Input id="inv-due" v-model="form.due_date" type="date" /></FormField>
                <FormField label="Período desde" for="inv-ps" :error="form.errors.period_start"><Input id="inv-ps" v-model="form.period_start" type="date" /></FormField>
                <FormField label="Período hasta" for="inv-pe" :error="form.errors.period_end"><Input id="inv-pe" v-model="form.period_end" type="date" /></FormField>
                <FormField label="Enlace de pago" for="inv-link" class="sm:col-span-2" hint="Opcional (Flow, Webpay, transferencia…)." :error="form.errors.payment_link"><Input id="inv-link" v-model="form.payment_link" type="url" placeholder="https://" /></FormField>

                <FormField label="PDF de la factura" for="inv-pdf" class="sm:col-span-2" :hint="invoice?.has_pdf ? `Ya hay un PDF adjunto (${invoice.pdf_name}). Elige otro para reemplazarlo.` : 'Máx. 10 MB.'" :error="form.errors.pdf">
                    <input id="inv-pdf" ref="fileInput" type="file" accept="application/pdf" class="block w-full text-sm file:mr-3 file:rounded-full file:border-0 file:bg-primary/10 file:px-4 file:py-2 file:text-sm file:font-medium file:text-primary" @change="onFile" />
                </FormField>
                <label v-if="form.pdf && (!invoice || invoice.status === 'scheduled')" class="flex items-start gap-3 rounded-xl border p-3 text-sm sm:col-span-2">
                    <Switch :model-value="form.issue" @update:model-value="(v: boolean) => (form.issue = v)" />
                    <span><strong>Emitir ahora</strong><span class="block text-xs text-muted-foreground">Queda «por pagar» y el cliente la ve en su portal.</span></span>
                </label>
                <label class="flex items-start gap-3 rounded-xl border p-3 text-sm sm:col-span-2">
                    <Switch :model-value="form.auto_remind" @update:model-value="(v: boolean) => (form.auto_remind = v)" />
                    <span><strong>Recordatorios automáticos</strong><span class="block text-xs text-muted-foreground">Envía correos antes y después del vencimiento mientras esté por pagar. Si es un cobro único, déjalo apagado y usa «Enviar cobro».</span></span>
                </label>
                <FormField label="Notas internas" for="inv-notes" class="sm:col-span-2" :error="form.errors.notes"><Textarea id="inv-notes" v-model="form.notes" rows="2" /></FormField>

                <DialogFooter class="gap-2 sm:col-span-2">
                    <Button type="button" variant="outline" @click="open = false">Cancelar</Button>
                    <Button type="submit" :disabled="form.processing"><Spinner v-if="form.processing" /> {{ invoice ? 'Guardar' : 'Registrar cobro' }}</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
