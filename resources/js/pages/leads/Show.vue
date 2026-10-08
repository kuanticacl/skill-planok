<script setup lang="ts">
import { Head, Link, router } from '@inertiajs/vue3';
import { Pencil, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import LeadDetails from '@/components/leads/LeadDetails.vue';
import LeadPanel from '@/components/leads/LeadPanel.vue';
import PageHeader from '@/components/PageHeader.vue';
import { Button } from '@/components/ui/button';
import { destroy, edit, index } from '@/routes/leads';
import type { PanelData } from '@/types';

const props = defineProps<PanelData>();
defineOptions({ layout: { breadcrumbs: [{ title: 'Leads', href: index() }] } });

const data = computed<PanelData>(() => ({
    lead: props.lead,
    customFields: props.customFields,
    notes: props.notes,
    activities: props.activities,
    stages: props.stages,
    users: props.users,
    priorities: props.priorities,
    followUpTypes: props.followUpTypes,
    proposals: props.proposals,
    scoring: props.scoring,
    ai: props.ai,
    can: props.can,
}));

const confirmDelete = ref(false);
const deleteLead = () => router.delete(destroy(props.lead.id).url);
// El panel guarda por fetch; aquí solo se recarga la ficha.
const reload = () => router.reload();
</script>

<template>
    <Head :title="lead.full_name" />

    <div class="flex flex-col gap-6 p-4 md:p-6">
        <PageHeader :title="lead.full_name" :description="[lead.job_title, lead.company].filter(Boolean).join(' · ') || undefined">
            <template #actions>
                <Button v-if="can.update" variant="outline" as-child><Link :href="edit(lead.id)"><Pencil /> Editar</Link></Button>
                <Button v-if="can.delete" variant="outline" class="text-destructive hover:text-destructive" @click="confirmDelete = true"><Trash2 /> Eliminar</Button>
            </template>
        </PageHeader>

        <LeadPanel :data="data" layout="page" @changed="reload">
            <template #details><LeadDetails :data="data" /></template>
        </LeadPanel>
    </div>

    <ConfirmDialog v-model:open="confirmDelete" title="Eliminar lead" :description="`Se eliminará «${lead.full_name}» con su historial.`" confirm-label="Eliminar" @confirm="deleteLead" />
</template>
