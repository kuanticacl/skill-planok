<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight } from '@lucide/vue';
import { computed } from 'vue';
import { cn } from '@/lib/utils';
import type { Paginated } from '@/types';

const props = defineProps<{ paginator: Paginated<unknown> }>();

const items = computed(() =>
    props.paginator.links.map((link, i, all) => ({
        ...link,
        kind: i === 0 ? 'prev' : i === all.length - 1 ? 'next' : 'page',
    })),
);
</script>

<template>
    <div
        v-if="paginator.last_page > 1"
        class="flex flex-col items-center justify-between gap-3 border-t px-4 py-3 text-sm text-muted-foreground sm:flex-row"
    >
        <span>
            Mostrando {{ paginator.from }}–{{ paginator.to }} de
            {{ paginator.total }}
        </span>
        <nav class="flex items-center gap-1">
            <template v-for="(item, i) in items" :key="i">
                <component
                    :is="item.url ? Link : 'span'"
                    :href="item.url ?? undefined"
                    preserve-scroll
                    :class="
                        cn(
                            'inline-flex h-8 min-w-8 items-center justify-center rounded-full px-2.5 text-sm transition-colors',
                            item.active
                                ? 'bg-primary font-semibold text-primary-foreground'
                                : item.url
                                  ? 'hover:bg-accent hover:text-accent-foreground'
                                  : 'opacity-40',
                        )
                    "
                >
                    <ChevronLeft v-if="item.kind === 'prev'" class="size-4" />
                    <ChevronRight
                        v-else-if="item.kind === 'next'"
                        class="size-4"
                    />
                    <span v-else v-html="item.label" />
                </component>
            </template>
        </nav>
    </div>
</template>
