<script setup lang="ts">
import { Clock, FileSignature, Flame, Mail, MessageCircle, Phone, Tag, TriangleAlert } from '@lucide/vue';
import { computed } from 'vue';
import SourceIcon from '@/components/SourceIcon.vue';
import UserInitials from '@/components/UserInitials.vue';
import type { KanbanPrefs } from '@/composables/useKanbanPrefs';
import { timeAgo } from '@/lib/format';
import { daysSince, followUpClass, followUpInfo, formatAmountShort, formatMoneyShort, gradeMeta, priorityMeta, whatsappUrl } from '@/lib/leadUi';
import { cn } from '@/lib/utils';
import type { LeadCard } from '@/types';

const props = defineProps<{
    lead: LeadCard;
    prefs: KanbanPrefs;
    selected: boolean;
    selecting: boolean;
    closed: boolean;
}>();
const emit = defineEmits<{ open: []; select: [] }>();

const compact = computed(() => props.prefs.density === 'compact');
const followUp = computed(() => (props.closed ? null : followUpInfo(props.lead.next_follow_up_at)));
const inStage = computed(() => daysSince(props.lead.stage_changed_at));
const idleDays = computed(() => daysSince(props.lead.last_activity_at ?? props.lead.created_at));
const stale = computed(() => !props.closed && idleDays.value >= 7);
const priority = computed(() => priorityMeta[props.lead.priority]);
const wa = computed(() => whatsappUrl(props.lead.phone));
const stripe = computed(() => (props.lead.priority === 'urgent' || props.lead.priority === 'high' ? priority.value.color : null));
</script>

<template>
    <article
        tabindex="0"
        role="button"
        :aria-label="`Abrir ${lead.full_name}`"
        :class="
            cn(
                'group relative shrink-0 cursor-grab overflow-hidden rounded-xl border bg-card shadow-sm shadow-black/[0.04] transition outline-none hover:border-primary/40 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring active:cursor-grabbing',
                selected && 'border-primary ring-2 ring-primary/60',
                compact ? 'p-2.5' : 'p-3',
            )
        "
        @click="selecting ? emit('select') : emit('open')"
        @keydown.enter.self="emit('open')"
        @keydown.space.self.prevent="emit('select')"
    >
        <span v-if="stripe" class="absolute inset-y-0 left-0 w-1" :style="{ backgroundColor: stripe }" />

        <div class="flex items-start gap-2">
            <button
                type="button"
                :class="cn('mt-0.5 flex size-4 shrink-0 items-center justify-center rounded border transition', selected ? 'border-primary bg-primary text-white' : 'border-input bg-background opacity-0 group-hover:opacity-100', (selecting || selected) && 'opacity-100')"
                :aria-label="selected ? 'Quitar de la selección' : 'Seleccionar'"
                @click.stop="emit('select')"
            >
                <svg v-if="selected" viewBox="0 0 12 12" class="size-3" fill="none" stroke="currentColor" stroke-width="2"><path d="M2.5 6.5l2.5 2.5 4.5-5" /></svg>
            </button>
            <div class="min-w-0 flex-1">
                <p class="flex items-center gap-1 truncate text-sm leading-tight font-semibold">
                    <Flame v-if="lead.priority === 'urgent'" class="size-3.5 shrink-0 text-destructive" />
                    <span class="truncate">{{ lead.full_name }}</span>
                </p>
                <p v-if="lead.company || lead.job_title" class="mt-0.5 truncate text-xs text-muted-foreground">
                    {{ [lead.job_title, lead.company].filter(Boolean).join(' · ') }}
                </p>
            </div>
            <span v-if="lead.score !== null && lead.score_grade && !closed" class="mt-0.5 inline-flex shrink-0 items-center gap-1 rounded-full px-1.5 py-0.5 text-[10px] leading-none font-bold text-white" :style="{ backgroundColor: gradeMeta[lead.score_grade].color }" :title="`Puntaje ${lead.score}/100 · ${gradeMeta[lead.score_grade].label} · perfil ${lead.profile_completeness ?? 0}% completo`">{{ lead.score }}</span>
            <UserInitials :name="lead.assignee?.name" />
        </div>

        <template v-if="!compact">
            <div v-if="(prefs.show.source && lead.source) || (prefs.show.campaign && lead.utm_campaign)" class="mt-2 flex flex-wrap items-center gap-1.5">
                <span v-if="prefs.show.source && lead.source" class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium text-white [&_svg]:size-3" :style="{ backgroundColor: lead.source.color }">
                    <SourceIcon :name="lead.source.icon" />{{ lead.source.name }}
                </span>
                <span v-if="prefs.show.campaign && lead.utm_campaign" class="inline-flex max-w-full items-center gap-1 truncate rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground" :title="`Campaña: ${lead.utm_campaign}`">
                    <Tag class="size-3 shrink-0" /><span class="truncate">{{ lead.utm_campaign }}</span>
                </span>
            </div>

            <div v-if="prefs.show.tags && lead.tags.length" class="mt-1.5 flex flex-wrap gap-1">
                <span v-for="t in lead.tags.slice(0, 3)" :key="t" class="rounded bg-accent px-1.5 py-0.5 text-[10px] font-medium text-accent-foreground">{{ t }}</span>
                <span v-if="lead.tags.length > 3" class="rounded bg-muted px-1.5 py-0.5 text-[10px] text-muted-foreground">+{{ lead.tags.length - 3 }}</span>
            </div>

            <dl v-if="prefs.show.custom && lead.custom.length" class="mt-2 grid gap-0.5 text-[11px]">
                <div v-for="c in lead.custom" :key="c.label" class="flex gap-1"><dt class="text-muted-foreground">{{ c.label }}:</dt><dd class="truncate font-medium">{{ c.value }}</dd></div>
            </dl>
        </template>

        <div v-if="lead.proposal" class="mt-2 flex items-center gap-1.5 rounded-lg border px-2 py-1 text-[11px]" :title="`Propuesta ${lead.proposal.number} · ${lead.proposal.status_label}${lead.proposal.count > 1 ? ' (' + lead.proposal.count + ' propuestas)' : ''}`">
            <FileSignature class="size-3.5 shrink-0 text-primary" />
            <span class="font-medium">{{ (lead.proposal.total_net && formatAmountShort(lead.proposal.total_net, lead.proposal.currency)) || 'Propuesta' }}</span>
            <span class="rounded-full px-1.5 py-px font-semibold text-white" :style="{ backgroundColor: lead.proposal.status_color }">{{ lead.proposal.status_label }}</span>
            <span v-if="lead.proposal.count > 1" class="text-muted-foreground">+{{ lead.proposal.count - 1 }}</span>
        </div>

        <div v-if="(prefs.show.value && lead.estimated_value) || (prefs.show.followup && followUp)" class="mt-2 flex flex-wrap items-center gap-1.5">
            <span v-if="prefs.show.value && lead.estimated_value" class="rounded-full bg-brand-green/10 px-2 py-0.5 text-[11px] font-semibold text-brand-green" :title="lead.estimated_currency === 'UF' ? `≈ ${formatMoneyShort(lead.estimated_value)}` : undefined">{{ lead.estimated_currency === 'UF' ? formatAmountShort(lead.estimated_amount, 'UF') : formatMoneyShort(lead.estimated_value) }}</span>
            <span v-if="prefs.show.followup && followUp" :class="cn('inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium', followUpClass[followUp.state])">
                <TriangleAlert v-if="followUp.state === 'overdue'" class="size-3" />{{ followUp.label }}
            </span>
        </div>

        <div class="mt-2.5 flex items-center justify-between text-[11px] text-muted-foreground">
            <span class="flex items-center gap-2">
                <span v-if="prefs.show.stageTime && !closed" class="inline-flex items-center gap-1" :class="inStage >= 7 && 'text-[#C23F00]'" :title="`En esta etapa desde hace ${inStage} días`">
                    <Clock class="size-3" />{{ inStage === 0 ? 'hoy' : `${inStage} d` }}
                </span>
                <span v-else>{{ timeAgo(lead.created_at) }}</span>
                <span v-if="stale" class="rounded bg-[#FFA165]/20 px-1 text-[#9A4B00]" :title="`Sin actividad hace ${idleDays} días`">{{ idleDays }} d sin actividad</span>
            </span>
            <span class="flex items-center gap-1.5 opacity-60 transition group-hover:opacity-100">
                <a v-if="lead.phone" :href="`tel:${lead.phone}`" title="Llamar" class="hover:text-primary" @click.stop><Phone class="size-3.5" /></a>
                <a v-if="wa" :href="wa" target="_blank" rel="noopener" title="WhatsApp" class="hover:text-[#25D366]" @click.stop><MessageCircle class="size-3.5" /></a>
                <a v-if="lead.email" :href="`mailto:${lead.email}`" title="Correo" class="hover:text-primary" @click.stop><Mail class="size-3.5" /></a>
            </span>
        </div>
    </article>
</template>
