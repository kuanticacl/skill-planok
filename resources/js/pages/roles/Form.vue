<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Lock } from '@lucide/vue';
import { computed } from 'vue';
import DataCard from '@/components/DataCard.vue';
import FormField from '@/components/FormField.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Spinner } from '@/components/ui/spinner';
import { Textarea } from '@/components/ui/textarea';
import { usePermissions } from '@/composables/usePermissions';
import { index, store, update } from '@/routes/roles';
import type { PermissionGroup } from '@/types';

const props = defineProps<{
    role: {
        id: number;
        name: string;
        description: string | null;
        is_system: boolean;
        permissions: string[];
    } | null;
    groups: PermissionGroup[];
}>();

defineOptions({
    layout: {
        breadcrumbs: [
            { title: 'Roles y permisos', href: index() },
        ],
    },
});

const { can } = usePermissions();
const isEdit = computed(() => !!props.role);
const readonly = computed(
    () => !!props.role?.is_system || (isEdit.value && !can('roles.update')),
);

const form = useForm({
    name: props.role?.name ?? '',
    description: props.role?.description ?? '',
    permissions: [...(props.role?.permissions ?? [])] as string[],
});

const has = (key: string) => form.permissions.includes(key);
const toggle = (key: string, on: boolean) => {
    form.permissions = on
        ? [...new Set([...form.permissions, key])]
        : form.permissions.filter((k) => k !== key);
};
const groupAll = (g: PermissionGroup) => g.permissions.every((p) => has(p.key));
const groupSome = (g: PermissionGroup) =>
    !groupAll(g) && g.permissions.some((p) => has(p.key));
const toggleGroup = (g: PermissionGroup, on: boolean) => {
    const keys = g.permissions.map((p) => p.key);
    form.permissions = on
        ? [...new Set([...form.permissions, ...keys])]
        : form.permissions.filter((k) => !keys.includes(k));
};

const submit = () => {
    if (props.role) {
        form.submit(update(props.role.id), { preserveScroll: true });
    } else {
        form.submit(store(), { preserveScroll: true });
    }
};
</script>

<template>
    <Head :title="isEdit ? 'Editar rol' : 'Nuevo rol'" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            :title="isEdit ? `Rol: ${role?.name}` : 'Nuevo rol'"
            description="Marca los permisos que tendrán los usuarios con este rol."
        />

        <div
            v-if="role?.is_system"
            class="flex items-center gap-2 rounded-xl border border-primary/30 bg-accent px-4 py-3 text-sm text-accent-foreground"
        >
            <Lock class="size-4" />
            Este es un rol de sistema con acceso total: no se puede modificar.
        </div>

        <form class="flex max-w-4xl flex-col gap-6" @submit.prevent="submit">
            <DataCard class="p-6">
                <div class="grid gap-5 sm:grid-cols-2">
                    <FormField label="Nombre del rol" for="name" :error="form.errors.name" required>
                        <Input id="name" v-model="form.name" :disabled="readonly" placeholder="Ej: Gerente comercial" />
                    </FormField>
                    <FormField label="Descripción" for="description" :error="form.errors.description">
                        <Textarea id="description" v-model="form.description" :disabled="readonly" rows="1" class="min-h-9" />
                    </FormField>
                </div>
            </DataCard>

            <div class="grid gap-4 md:grid-cols-2">
                <DataCard v-for="g in groups" :key="g.key">
                    <div class="flex items-center justify-between border-b bg-muted/40 px-4 py-3">
                        <h3 class="font-semibold">{{ g.label }}</h3>
                        <label class="flex items-center gap-2 text-xs text-muted-foreground">
                            <Checkbox
                                :model-value="groupAll(g) ? true : groupSome(g) ? 'indeterminate' : false"
                                :disabled="readonly"
                                @update:model-value="(v) => toggleGroup(g, v === true)"
                            />
                            Todos
                        </label>
                    </div>
                    <ul class="divide-y">
                        <li v-for="p in g.permissions" :key="p.key">
                            <label class="flex cursor-pointer items-center gap-3 px-4 py-2.5 text-sm hover:bg-muted/30">
                                <Checkbox
                                    :model-value="has(p.key)"
                                    :disabled="readonly"
                                    @update:model-value="(v) => toggle(p.key, v === true)"
                                />
                                <span class="flex-1">{{ p.label }}</span>
                                <code class="text-[11px] text-muted-foreground">{{ p.key }}</code>
                            </label>
                        </li>
                    </ul>
                </DataCard>
            </div>
            <p v-if="form.errors.permissions" class="text-sm text-red-600">{{ form.errors.permissions }}</p>

            <div class="flex items-center justify-end gap-2">
                <Button variant="outline" as-child>
                    <Link :href="index()">{{ readonly ? 'Volver' : 'Cancelar' }}</Link>
                </Button>
                <Button v-if="!readonly" type="submit" :disabled="form.processing">
                    <Spinner v-if="form.processing" />
                    {{ isEdit ? 'Guardar cambios' : 'Crear rol' }}
                </Button>
            </div>
        </form>
    </div>
</template>
