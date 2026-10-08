import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

/**
 * Permisos del usuario autenticado (compartidos desde HandleInertiaRequests).
 * can('leads.create')            -> ¿tiene el permiso?
 * canAny('users.view', 'roles.view') -> ¿tiene al menos uno?
 */
export function usePermissions() {
    const page = usePage();
    const permissions = computed(() => new Set(page.props.auth.permissions ?? []));

    const can = (permission: string): boolean => permissions.value.has(permission);
    const canAny = (...list: string[]): boolean => list.some(can);

    return { can, canAny, permissions };
}
