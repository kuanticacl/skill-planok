<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Globe, LayoutDashboard, ShieldCheck, UserCog } from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { usePermissions } from '@/composables/usePermissions';
import { dashboard } from '@/routes';
import { index as roles } from '@/routes/roles';
import { index as users } from '@/routes/users';
import type { NavItem } from '@/types';

type NavGroup = { label: string; items: (NavItem & { permission?: string })[] };

const { can } = usePermissions();

const groups: NavGroup[] = [
    {
        label: 'Comercial',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutDashboard,
                permission: 'dashboard.view',
            },
        ],
    },
    {
        label: 'Administración',
        items: [
            { title: 'Usuarios', href: users(), icon: UserCog, permission: 'users.view' },
            { title: 'Roles y permisos', href: roles(), icon: ShieldCheck, permission: 'roles.view' },
        ],
    },
];

const visibleGroups = computed(() =>
    groups
        .map((g) => ({
            ...g,
            items: g.items.filter((i) => !i.permission || can(i.permission)),
        }))
        .filter((g) => g.items.length),
);

const footerNavItems: NavItem[] = [
    { title: 'quiebre.cl', href: 'https://www.quiebre.cl', icon: Globe },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <NavMain
                v-for="g in visibleGroups"
                :key="g.label"
                :label="g.label"
                :items="g.items"
            />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
