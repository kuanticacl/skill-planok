<script setup lang="ts">
import { Link, router, usePage } from '@inertiajs/vue3';
import { FileText, Home, LogOut, Package, Receipt, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import BrandLogo from '@/components/BrandLogo.vue';
import { Toaster } from '@/components/ui/sonner';
import { cn } from '@/lib/utils';

const page = usePage();
const user = computed(() => page.props.auth.user);
const path = computed(() => page.url.split('?')[0]);

const nav = [
    { label: 'Inicio', href: '/portal', icon: Home, exact: true },
    { label: 'Propuestas', href: '/portal/propuestas', icon: FileText },
    { label: 'Servicios', href: '/portal/servicios', icon: Package },
    { label: 'Facturas', href: '/portal/facturas', icon: Receipt },
    { label: 'Mi cuenta', href: '/portal/cuenta', icon: UserRound },
];
const active = (n: (typeof nav)[number]) => (n.exact ? path.value === n.href : path.value.startsWith(n.href));
const logout = () => router.post('/logout');
</script>

<template>
    <div class="min-h-svh bg-muted/40">
        <header class="sticky top-0 z-30 bg-sidebar text-sidebar-foreground shadow-sm">
            <div class="mx-auto flex h-16 max-w-6xl items-center gap-4 px-4 md:px-6">
                <Link href="/portal" class="flex items-center gap-3">
                    <BrandLogo class="h-7 w-[8.4rem] shrink-0 text-white" />
                    <span class="hidden rounded-full bg-white/10 px-2.5 py-0.5 text-[10px] font-semibold tracking-wide uppercase sm:inline">Portal de clientes</span>
                </Link>

                <nav class="ml-6 hidden items-center gap-1 md:flex" aria-label="Principal">
                    <Link v-for="n in nav" :key="n.href" :href="n.href" prefetch :class="cn('inline-flex items-center gap-2 rounded-full px-3.5 py-2 text-sm font-medium transition-colors', active(n) ? 'bg-white/15 text-white' : 'text-white/70 hover:bg-white/10 hover:text-white')"><component :is="n.icon" class="size-4" />{{ n.label }}</Link>
                </nav>

                <div class="ml-auto flex items-center gap-3">
                    <span class="hidden max-w-48 truncate text-sm text-white/80 lg:block">{{ user.name }}</span>
                    <button type="button" class="inline-flex items-center gap-2 rounded-full px-3 py-2 text-sm text-white/70 transition-colors hover:bg-white/10 hover:text-white" title="Cerrar sesión" @click="logout"><LogOut class="size-4" /><span class="hidden sm:inline">Salir</span></button>
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-6xl px-4 pt-6 pb-28 md:px-6 md:pb-12"><slot /></main>

        <!-- Navegación inferior en móvil -->
        <nav class="fixed inset-x-0 bottom-0 z-30 border-t bg-card md:hidden" aria-label="Principal">
            <ul class="grid grid-cols-5">
                <li v-for="n in nav" :key="n.href">
                    <Link :href="n.href" :class="cn('flex flex-col items-center gap-0.5 py-2.5 text-[11px] font-medium', active(n) ? 'text-primary' : 'text-muted-foreground')"><component :is="n.icon" class="size-5" />{{ n.label }}</Link>
                </li>
            </ul>
        </nav>
        <Toaster />
    </div>
</template>
