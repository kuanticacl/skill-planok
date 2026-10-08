<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import DataCard from '@/components/DataCard.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { index, store, update } from '@/routes/users';
import type { RoleOption, UserRow } from '@/types';

const props = defineProps<{ user: UserRow | null; roles: RoleOption[] }>();

const isEdit = computed(() => !!props.user);

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Usuarios', href: index() },
        ],
    },
});

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    job_title: props.user?.job_title ?? '',
    role_id: props.user?.role_id ?? (props.roles[0]?.id ?? ''),
    is_active: props.user?.is_active ?? true,
    password: '',
    password_confirmation: '',
});

const submit = () => {
    if (props.user) {
        form.submit(update(props.user.id), { preserveScroll: true });
    } else {
        form.submit(store(), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Editar usuario' : 'Nuevo usuario'" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="isEdit ? `Editar a ${user?.name}` : 'Nuevo usuario'"
            description="Define los datos de acceso y el rol que determina qué puede ver y hacer."
        />

        <form class="max-w-3xl" @submit.prevent="submit">
            <DataCard class="p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField label="Nombre completo" for="name" :error="form.errors.name" required>
                        <Input id="name" v-model="form.name" autocomplete="off" />
                    </FormField>
                    <FormField label="Correo electrónico" for="email" :error="form.errors.email" required>
                        <Input id="email" v-model="form.email" type="email" autocomplete="off" />
                    </FormField>
                    <FormField label="Teléfono" for="phone" :error="form.errors.phone">
                        <Input id="phone" v-model="form.phone" placeholder="+56 9 1234 5678" />
                    </FormField>
                    <FormField label="Cargo" for="job_title" :error="form.errors.job_title">
                        <Input id="job_title" v-model="form.job_title" placeholder="Ej: Ejecutivo comercial" />
                    </FormField>
                    <FormField label="Rol" for="role_id" :error="form.errors.role_id" required
                        :hint="roles.find((r) => r.id === Number(form.role_id))?.description ?? undefined">
                        <NativeSelect id="role_id" v-model="form.role_id" :disabled="isEdit && $page.props.auth.user.id === user?.id">
                            <option v-for="r in roles" :key="r.id" :value="r.id">{{ r.name }}</option>
                        </NativeSelect>
                    </FormField>
                    <FormField label="Estado" :error="form.errors.is_active">
                        <label class="flex h-9 items-center gap-3 text-sm">
                            <Switch
                                :model-value="form.is_active"
                                :disabled="isEdit && $page.props.auth.user.id === user?.id"
                                @update:model-value="(v: boolean) => (form.is_active = v)"
                            />
                            {{ form.is_active ? 'Activo: puede ingresar' : 'Inactivo: acceso bloqueado' }}
                        </label>
                    </FormField>
                    <FormField
                        :label="isEdit ? 'Nueva contraseña' : 'Contraseña'"
                        for="password"
                        :error="form.errors.password"
                        :required="!isEdit"
                        :hint="isEdit ? 'Déjala en blanco para mantener la actual.' : undefined"
                    >
                        <Input id="password" v-model="form.password" type="password" autocomplete="new-password" />
                    </FormField>
                    <FormField label="Confirmar contraseña" for="password_confirmation">
                        <Input id="password_confirmation" v-model="form.password_confirmation" type="password" autocomplete="new-password" />
                    </FormField>
                </div>

                <div class="mt-6 flex items-center justify-end gap-2 border-t pt-5">
                    <Button variant="outline" as-child>
                        <Link :href="index()">Cancelar</Link>
                    </Button>
                    <Button type="submit" :disabled="form.processing">
                        <Spinner v-if="form.processing" />
                        {{ isEdit ? 'Guardar cambios' : 'Crear usuario' }}
                    </Button>
                </div>
            </DataCard>
        </form>
    </div>
</template>
