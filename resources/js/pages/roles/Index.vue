<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Lock, Pencil, Plus, ShieldCheck, Trash2, Users } from '@lucide/vue';
import { ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { usePermissions } from '@/composables/usePermissions';
import { create, destroy, edit, index } from '@/routes/roles';
import type { RoleRow } from '@/types';

defineOptions({
    layout: { breadcrumbs: [{ title: 'Roles y permisos', href: index() }] },
});

defineProps<{ roles: RoleRow[]; totalPermissions: number }>();

const { can } = usePermissions();
const toDelete = ref<RoleRow | null>(null);

const confirmDelete = () => {
    if (!toDelete.value) return;
    router.delete(destroy(toDelete.value.id).url, {
        preserveScroll: true,
        onFinish: () => (toDelete.value = null),
    });
};
</script>

<template>
    <Head title="Roles y permisos" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader
            title="Roles y permisos"
            description="Un rol agrupa permisos. Cada usuario tiene un rol que define lo que puede ver y hacer en el CRM."
        >
            <template #actions>
                <Button v-if="can('roles.create')" as-child>
                    <Link :href="create()"><Plus /> Nuevo rol</Link>
                </Button>
            </template>
        </PageHeader>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            <div
                v-for="role in roles"
                :key="role.id"
                class="flex flex-col gap-4 rounded-2xl border bg-card p-5 shadow-sm shadow-black/[0.03]"
            >
                <div class="flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div
                            class="flex size-10 items-center justify-center rounded-xl bg-accent text-accent-foreground"
                        >
                            <ShieldCheck class="size-5" />
                        </div>
                        <div>
                            <h3 class="font-semibold">{{ role.name }}</h3>
                            <Badge v-if="role.is_system" variant="outline" class="mt-1">
                                <Lock /> Sistema
                            </Badge>
                        </div>
                    </div>
                    <div class="flex gap-1">
                        <Button
                            v-if="can('roles.view')"
                            variant="ghost"
                            size="icon-sm"
                            as-child
                            :title="role.is_system || !can('roles.update') ? 'Ver permisos' : 'Editar'"
                        >
                            <Link :href="edit(role.id)"><Pencil /></Link>
                        </Button>
                        <Button
                            v-if="can('roles.delete') && !role.is_system"
                            variant="ghost"
                            size="icon-sm"
                            class="text-destructive hover:text-destructive"
                            title="Eliminar"
                            @click="toDelete = role"
                        >
                            <Trash2 />
                        </Button>
                    </div>
                </div>
                <p class="min-h-10 text-sm text-muted-foreground">
                    {{ role.description || 'Sin descripción.' }}
                </p>
                <div class="flex items-center justify-between border-t pt-3 text-sm">
                    <span class="flex items-center gap-1.5 text-muted-foreground">
                        <Users class="size-4" />
                        {{ role.users_count }}
                        {{ role.users_count === 1 ? 'usuario' : 'usuarios' }}
                    </span>
                    <span class="font-medium text-primary">
                        {{ role.permissions_count }}/{{ totalPermissions }} permisos
                    </span>
                </div>
            </div>
        </div>
    </div>

    <ConfirmDialog
        :open="!!toDelete"
        title="Eliminar rol"
        :description="`Se eliminará el rol ${toDelete?.name}. Esta acción no se puede deshacer.`"
        confirm-label="Eliminar"
        @update:open="(v: boolean) => !v && (toDelete = null)"
        @confirm="confirmDelete"
    />
</template>
