<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataCard from '@/components/DataCard.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import { index, store, update } from '@/routes/clients';
import type { ClientRow } from '@/types';

const props = defineProps<{ client: ClientRow | null }>();
const isEdit = computed(() => !!props.client);

defineOptions({ layout: { breadcrumbs: [{ title: 'Clientes', href: index() }] } });

const form = useForm({
    name: props.client?.name ?? '',
    legal_name: props.client?.legal_name ?? '',
    tax_id: props.client?.tax_id ?? '',
    email: props.client?.email ?? '',
    phone: props.client?.phone ?? '',
    website: props.client?.website ?? '',
    address: props.client?.address ?? '',
    city: props.client?.city ?? '',
    notes: props.client?.notes ?? '',
    is_active: props.client?.is_active ?? true,
});

const submit = () => {
    props.client ? form.submit(update(props.client.id)) : form.submit(store());
};
</script>

<template>
    <Head :title="isEdit ? 'Editar cliente' : 'Nuevo cliente'" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="isEdit ? `Editar ${client?.name}` : 'Nuevo cliente'" description="Datos de la empresa o inmobiliaria." />

        <form class="max-w-3xl" @submit.prevent="submit">
            <DataCard class="p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField label="Nombre comercial" for="name" :error="form.errors.name" required>
                        <Input id="name" v-model="form.name" />
                    </FormField>
                    <FormField label="Razón social" for="legal_name" :error="form.errors.legal_name">
                        <Input id="legal_name" v-model="form.legal_name" />
                    </FormField>
                    <FormField label="RUT" for="tax_id" :error="form.errors.tax_id">
                        <Input id="tax_id" v-model="form.tax_id" placeholder="76.123.456-7" />
                    </FormField>
                    <FormField label="Sitio web" for="website" :error="form.errors.website">
                        <Input id="website" v-model="form.website" placeholder="https://" />
                    </FormField>
                    <FormField label="Correo" for="email" :error="form.errors.email">
                        <Input id="email" v-model="form.email" type="email" />
                    </FormField>
                    <FormField label="Teléfono" for="phone" :error="form.errors.phone">
                        <Input id="phone" v-model="form.phone" />
                    </FormField>
                    <FormField label="Dirección" for="address" :error="form.errors.address">
                        <Input id="address" v-model="form.address" />
                    </FormField>
                    <FormField label="Ciudad" for="city" :error="form.errors.city">
                        <Input id="city" v-model="form.city" />
                    </FormField>
                    <FormField label="Notas" for="notes" :error="form.errors.notes" class="sm:col-span-2">
                        <Textarea id="notes" v-model="form.notes" rows="3" />
                    </FormField>
                    <label class="flex items-center gap-3 text-sm sm:col-span-2">
                        <Switch :model-value="form.is_active" @update:model-value="(v: boolean) => (form.is_active = v)" />
                        Cliente activo
                    </label>
                </div>
                <div class="mt-6 flex items-center justify-end gap-2 border-t pt-5">
                    <Button variant="outline" as-child><Link :href="index()">Cancelar</Link></Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" /> {{ isEdit ? 'Guardar cambios' : 'Crear cliente' }}
                    </Button>
                </div>
            </DataCard>
        </form>
    </div>
</template>
