<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Mail, Phone, Tag } from '@lucide/vue';
import SourceIcon from '@/components/SourceIcon.vue';
import UserInitials from '@/components/UserInitials.vue';
import { timeAgo } from '@/lib/format';
import { show } from '@/routes/leads';
import type { LeadCard } from '@/types';

defineProps<{ lead: LeadCard }>();
</script>

<template>
    <article
        class="group cursor-grab rounded-xl border bg-card p-3 shadow-sm shadow-black/[0.04] transition hover:border-primary/40 hover:shadow-md active:cursor-grabbing"
    >
        <div class="flex items-start justify-between gap-2">
            <div class="min-w-0">
                <Link
                    :href="show(lead.id)"
                    class="block truncate text-sm font-semibold hover:text-primary"
                    draggable="false"
                >
                    {{ lead.full_name }}
                </Link>
                <p v-if="lead.company || lead.job_title" class="truncate text-xs text-muted-foreground">
                    {{ [lead.job_title, lead.company].filter(Boolean).join(' · ') }}
                </p>
            </div>
            <UserInitials :name="lead.assignee?.name" />
        </div>

        <div class="mt-2 flex flex-wrap items-center gap-1.5">
            <span
                v-if="lead.source"
                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium text-white [&_svg]:size-3"
                :style="{ backgroundColor: lead.source.color }"
            >
                <SourceIcon :name="lead.source.icon" />{{ lead.source.name }}
            </span>
            <span
                v-if="lead.utm_campaign"
                class="inline-flex max-w-full items-center gap-1 truncate rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground"
                :title="`Campaña: ${lead.utm_campaign}`"
            >
                <Tag class="size-3 shrink-0" /><span class="truncate">{{ lead.utm_campaign }}</span>
            </span>
        </div>

        <dl v-if="lead.custom.length" class="mt-2 grid gap-0.5 text-[11px]">
            <div v-for="c in lead.custom" :key="c.label" class="flex gap-1">
                <dt class="text-muted-foreground">{{ c.label }}:</dt>
                <dd class="truncate font-medium">{{ c.value }}</dd>
            </div>
        </dl>

        <div class="mt-2.5 flex items-center justify-between text-[11px] text-muted-foreground">
            <span>{{ timeAgo(lead.created_at) }}</span>
            <span class="flex items-center gap-2">
                <Mail v-if="lead.email" class="size-3.5" :title="lead.email" />
                <Phone v-if="lead.phone" class="size-3.5" :title="lead.phone" />
            </span>
        </div>
    </article>
</template>
