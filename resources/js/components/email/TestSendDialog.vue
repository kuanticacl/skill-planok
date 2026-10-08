<script setup lang="ts">
import { CheckCircle2, TriangleAlert } from '@lucide/vue';
import { ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { HttpError, sendJson } from '@/lib/http';
import { test } from '@/routes/templates';

const props = defineProps<{
    open: boolean;
    defaultTo: string;
    payload: () => { subject: string; html: string; preheader: string; variables: Record<string, string>; template_id: number | null };
}>();
const emit = defineEmits<{ 'update:open': [boolean] }>();

const to = ref(props.defaultTo);
const busy = ref(false);
const result = ref<{ ok: boolean; text: string } | null>(null);

watch(() => props.open, (o) => {
    if (o) {
        result.value = null;
        to.value = to.value || props.defaultTo;
    }
});

const send = async () => {
    busy.value = true;
    result.value = null;
    try {
        const res = await sendJson<{ provider: string; missing: string[] }>('POST', test().url, { to: to.value, ...props.payload() });
        result.value = {
            ok: true,
            text: res.provider === 'log'
                ? 'Modo de prueba: el correo NO se envió realmente (no hay API key de Resend). Quedó registrado en el log.'
                : `Enviado a ${to.value}. Revisa tu bandeja (y spam).${res.missing.length ? ` Variables sin valor: ${res.missing.join(', ')}.` : ''}`,
        };
    } catch (e) {
        const body = e instanceof HttpError ? (e.body as { error?: string; message?: string } | null) : null;
        result.value = { ok: false, text: body?.error ?? body?.message ?? 'No se pudo enviar.' };
    } finally {
        busy.value = false;
    }
};
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Enviar correo de prueba</DialogTitle>
                <DialogDescription>Se envía el contenido actual del editor (aunque no esté guardado) con los valores de ejemplo de las variables.</DialogDescription>
            </DialogHeader>
            <form class="grid gap-4" @submit.prevent="send">
                <FormField label="Enviar a" for="test-to"><Input id="test-to" v-model="to" type="email" required /></FormField>
                <p v-if="result" :class="['flex items-start gap-2 rounded-xl p-3 text-sm', result.ok ? 'bg-brand-green/10 text-brand-green' : 'bg-destructive/10 text-destructive']">
                    <CheckCircle2 v-if="result.ok" class="mt-0.5 size-4 shrink-0" /><TriangleAlert v-else class="mt-0.5 size-4 shrink-0" />{{ result.text }}
                </p>
                <DialogFooter class="gap-2">
                    <Button type="button" variant="outline" @click="emit('update:open', false)">Cerrar</Button>
                    <Button type="submit" :disabled="busy || !to"><Spinner v-if="busy" /> Enviar prueba</Button>
                </DialogFooter>
            </form>
        </DialogContent>
    </Dialog>
</template>
