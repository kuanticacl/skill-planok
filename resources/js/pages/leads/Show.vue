<script setup lang="ts">
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowRightLeft,
    Building2,
    CalendarDays,
    CircleDot,
    Globe,
    ListChecks,
    Lock,
    Mail,
    MapPin,
    MessageCircle,
    Monitor,
    Pencil,
    Phone,
    Sparkles,
    StickyNote,
    Trash2,
    UserCheck,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import ConfirmDialog from '@/components/ConfirmDialog.vue';
import DataCard from '@/components/DataCard.vue';
import PageHeader from '@/components/PageHeader.vue';
import SourceIcon from '@/components/SourceIcon.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Spinner } from '@/components/ui/spinner';
import { Switch } from '@/components/ui/switch';
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs';
import { Textarea } from '@/components/ui/textarea';
import UserInitials from '@/components/UserInitials.vue';
import { formatDateTime, timeAgo } from '@/lib/format';
import { assign, destroy, edit, index, move } from '@/routes/leads';
import { store as storeActivity } from '@/routes/leads/activities';
import { destroy as destroyNote, store as storeNote } from '@/routes/leads/notes';
import type { LeadCard, StageRef, UserOption } from '@/types';

type Lead = LeadCard & {
    first_name: string;
    last_name: string | null;
    message: string | null;
    client: { id: number; name: string } | null;
    stage: StageRef;
    utm: Record<string, string | null>;
    capture: Record<string, string | number | null>;
    meta: Record<string, unknown> | null;
};
type Note = { id: number; body: string; is_private: boolean; author: string; mine: boolean; created_at: string };
type Activity = { id: number; type: string; description: string | null; user: string | null; occurred_at: string };

const props = defineProps<{
    lead: Lead;
    customFields: { key: string; label: string; type: string; value: unknown; active: boolean }[];
    notes: Note[];
    activities: Activity[];
    stages: StageRef[];
    users: UserOption[];
    followUpTypes: Record<string, string>;
    can: { update: boolean; move: boolean; delete: boolean; note: boolean; assign: boolean };
}>();

defineOptions({ layout: { breadcrumbs: [{ title: 'Leads', href: index() }] } });

const stageId = ref(props.lead.stage.id);
const assignee = ref(props.lead.assignee?.id ?? '');

const changeStage = () => router.put(move(props.lead.id).url, { stage_id: stageId.value }, { preserveScroll: true });
const changeAssignee = () => router.put(assign(props.lead.id).url, { assigned_to: assignee.value || null }, { preserveScroll: true });

// Seguimiento y notas
const followUp = useForm({ type: 'call', description: '', occurred_at: '' });
const addFollowUp = () =>
    followUp.submit(storeActivity(props.lead.id), { preserveScroll: true, onSuccess: () => followUp.reset('description', 'occurred_at') });

const noteForm = useForm({ body: '', is_private: false });
const addNote = () => noteForm.submit(storeNote(props.lead.id), { preserveScroll: true, onSuccess: () => noteForm.reset() });
const removeNote = (n: Note) => router.delete(destroyNote({ lead: props.lead.id, note: n.id }).url, { preserveScroll: true });

const confirmDelete = ref(false);
const deleteLead = () => router.delete(destroy(props.lead.id).url);

const typeMeta: Record<string, { icon: typeof Phone; color: string }> = {
    created: { icon: Sparkles, color: '#FF5300' },
    stage_changed: { icon: ArrowRightLeft, color: '#6419DB' },
    assigned: { icon: UserCheck, color: '#1AA0E4' },
    updated: { icon: Pencil, color: '#8A8A8A' },
    call: { icon: Phone, color: '#0D9F85' },
    email: { icon: Mail, color: '#4A8CFF' },
    whatsapp: { icon: MessageCircle, color: '#25D366' },
    meeting: { icon: CalendarDays, color: '#DF1E79' },
    task: { icon: ListChecks, color: '#FFA165' },
    other: { icon: CircleDot, color: '#8A8A8A' },
};
const labelFor = (type: string) => props.followUpTypes[type];

const utmEntries = computed(() => Object.entries(props.lead.utm).filter(([, v]) => v));
const location = computed(() => [props.lead.capture.city, props.lead.capture.region, props.lead.capture.country].filter(Boolean).join(', '));
const mapUrl = computed(() =>
    props.lead.capture.latitude != null && props.lead.capture.longitude != null
        ? `https://www.openstreetmap.org/?mlat=${props.lead.capture.latitude}&mlon=${props.lead.capture.longitude}#map=12/${props.lead.capture.latitude}/${props.lead.capture.longitude}`
        : null,
);
const device = computed(() => {
    const ua = String(props.lead.capture.user_agent ?? '');
    if (!ua) return null;
    const kind = /mobile|iphone|android/i.test(ua) ? 'Móvil' : /ipad|tablet/i.test(ua) ? 'Tablet' : 'Escritorio';
    const browser = /edg\//i.test(ua) ? 'Edge' : /chrome|crios/i.test(ua) ? 'Chrome' : /firefox|fxios/i.test(ua) ? 'Firefox' : /safari/i.test(ua) ? 'Safari' : '';
    return [kind, browser].filter(Boolean).join(' · ');
});
const metaEntries = computed(() => Object.entries(props.lead.meta ?? {}));
const showValue = (type: string, v: unknown) => (type === 'checkbox' ? (v ? 'Sí' : 'No') : String(v));
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

        <!-- Etapa y responsable -->
        <div class="grid gap-3 sm:grid-cols-2 lg:max-w-2xl">
            <div class="rounded-2xl border bg-card p-3.5">
                <p class="mb-1.5 text-xs font-medium text-muted-foreground">Etapa</p>
                <div class="flex items-center gap-2">
                    <span class="size-3 shrink-0 rounded-full" :style="{ backgroundColor: stages.find((s) => s.id === stageId)?.color }" />
                    <NativeSelect v-model="stageId" :disabled="!can.move" class="flex-1" @update:model-value="changeStage">
                        <option v-for="s in stages" :key="s.id" :value="s.id">{{ s.name }}</option>
                    </NativeSelect>
                </div>
            </div>
            <div class="rounded-2xl border bg-card p-3.5">
                <p class="mb-1.5 text-xs font-medium text-muted-foreground">Responsable</p>
                <div class="flex items-center gap-2">
                    <UserInitials :name="lead.assignee?.name" class="size-7" />
                    <NativeSelect v-if="can.assign" v-model="assignee" class="flex-1" @update:model-value="changeAssignee">
                        <option value="">Sin asignar</option>
                        <option v-for="u in users" :key="u.id" :value="u.id">{{ u.name }}</option>
                    </NativeSelect>
                    <span v-else class="text-sm">{{ lead.assignee?.name ?? 'Sin asignar' }}</span>
                </div>
            </div>
        </div>

        <div class="grid gap-5 lg:grid-cols-3">
            <!-- Seguimiento y notas -->
            <Tabs default-value="followup" class="lg:col-span-2">
                <TabsList>
                    <TabsTrigger value="followup"><ListChecks /> Seguimiento</TabsTrigger>
                    <TabsTrigger value="notes"><StickyNote /> Notas ({{ notes.length }})</TabsTrigger>
                </TabsList>

                <TabsContent value="followup" class="mt-3 flex flex-col gap-4">
                    <DataCard v-if="can.note" class="p-4">
                        <form class="grid gap-3" @submit.prevent="addFollowUp">
                            <div class="grid gap-3 sm:grid-cols-[10rem_1fr_14rem]">
                                <NativeSelect v-model="followUp.type">
                                    <option v-for="(label, key) in followUpTypes" :key="key" :value="key">{{ label }}</option>
                                </NativeSelect>
                                <Input v-model="followUp.description" placeholder="¿Qué pasó? Ej: Llamé, pidió cotización para el lunes" />
                                <Input v-model="followUp.occurred_at" type="datetime-local" title="Fecha (por defecto, ahora)" />
                            </div>
                            <p v-if="followUp.errors.description" class="text-sm text-red-600">{{ followUp.errors.description }}</p>
                            <div class="flex justify-end">
                                <Button type="submit" size="sm" :disabled="followUp.processing || !followUp.description.trim()">
                                    <Spinner v-if="followUp.processing" /> Registrar seguimiento
                                </Button>
                            </div>
                        </form>
                    </DataCard>

                    <ol class="relative flex flex-col gap-4 border-l pl-6">
                        <li v-for="a in activities" :key="a.id" class="relative">
                            <span
                                class="absolute -left-[2.15rem] flex size-7 items-center justify-center rounded-full text-white ring-4 ring-background [&_svg]:size-3.5"
                                :style="{ backgroundColor: (typeMeta[a.type] ?? typeMeta.other).color }"
                            >
                                <component :is="(typeMeta[a.type] ?? typeMeta.other).icon" />
                            </span>
                            <p class="text-sm">
                                <span v-if="labelFor(a.type)" class="font-semibold">{{ labelFor(a.type) }}: </span>{{ a.description }}
                            </p>
                            <p class="text-xs text-muted-foreground">
                                {{ a.user ?? 'Sistema' }} · {{ formatDateTime(a.occurred_at) }} ({{ timeAgo(a.occurred_at) }})
                            </p>
                        </li>
                    </ol>
                </TabsContent>

                <TabsContent value="notes" class="mt-3 flex flex-col gap-4">
                    <DataCard v-if="can.note" class="p-4">
                        <form class="grid gap-3" @submit.prevent="addNote">
                            <Textarea v-model="noteForm.body" rows="3" placeholder="Escribe una nota interna sobre este lead…" />
                            <p v-if="noteForm.errors.body" class="text-sm text-red-600">{{ noteForm.errors.body }}</p>
                            <div class="flex items-center justify-between gap-3">
                                <label class="flex items-center gap-2.5 text-sm">
                                    <Switch :model-value="noteForm.is_private" @update:model-value="(v: boolean) => (noteForm.is_private = v)" />
                                    <Lock class="size-3.5 text-muted-foreground" /> Nota privada (solo yo la veo)
                                </label>
                                <Button type="submit" size="sm" :disabled="noteForm.processing || !noteForm.body.trim()">
                                    <Spinner v-if="noteForm.processing" /> Guardar nota
                                </Button>
                            </div>
                        </form>
                    </DataCard>

                    <p v-if="!notes.length" class="rounded-2xl border border-dashed bg-card py-8 text-center text-sm text-muted-foreground">Aún no hay notas.</p>
                    <article
                        v-for="n in notes"
                        :key="n.id"
                        class="rounded-2xl border p-4"
                        :class="n.is_private ? 'border-primary/30 bg-accent/50' : 'bg-card'"
                    >
                        <p class="text-sm whitespace-pre-line">{{ n.body }}</p>
                        <div class="mt-2 flex items-center justify-between text-xs text-muted-foreground">
                            <span class="flex items-center gap-1.5">
                                <Lock v-if="n.is_private" class="size-3 text-primary" />
                                {{ n.author }} · {{ timeAgo(n.created_at) }}
                                <em v-if="n.is_private" class="text-primary not-italic">· privada</em>
                            </span>
                            <Button v-if="n.mine || can.delete" variant="ghost" size="icon-sm" class="size-6 text-destructive hover:text-destructive" title="Eliminar nota" @click="removeNote(n)"><Trash2 /></Button>
                        </div>
                    </article>
                </TabsContent>
            </Tabs>

            <!-- Detalle -->
            <div class="flex flex-col gap-4">
                <DataCard class="p-4">
                    <h3 class="mb-3 text-sm font-semibold">Contacto</h3>
                    <ul class="grid gap-2.5 text-sm">
                        <li v-if="lead.email" class="flex items-center gap-2.5"><Mail class="size-4 text-muted-foreground" /><a :href="`mailto:${lead.email}`" class="truncate hover:text-primary">{{ lead.email }}</a></li>
                        <li v-if="lead.phone" class="flex items-center gap-2.5"><Phone class="size-4 text-muted-foreground" /><a :href="`tel:${lead.phone}`" class="hover:text-primary">{{ lead.phone }}</a></li>
                        <li v-if="lead.company" class="flex items-center gap-2.5"><Building2 class="size-4 text-muted-foreground" />{{ lead.company }}</li>
                        <li v-if="lead.client" class="flex items-center gap-2.5"><Building2 class="size-4 text-primary" /><span>Cliente: <Link :href="`/clients/${lead.client.id}`" class="font-medium hover:text-primary">{{ lead.client.name }}</Link></span></li>
                    </ul>
                    <p v-if="lead.message" class="mt-3 border-t pt-3 text-sm whitespace-pre-line text-muted-foreground">{{ lead.message }}</p>
                </DataCard>

                <DataCard class="p-4">
                    <h3 class="mb-3 text-sm font-semibold">Origen y campaña</h3>
                    <span
                        v-if="lead.source"
                        class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium text-white [&_svg]:size-3.5"
                        :style="{ backgroundColor: lead.source.color }"
                    ><SourceIcon :name="lead.source.icon" />{{ lead.source.name }}</span>
                    <dl v-if="utmEntries.length" class="mt-3 grid gap-1.5 text-sm">
                        <div v-for="[k, v] in utmEntries" :key="k" class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">{{ k }}</dt><dd class="truncate font-medium">{{ v }}</dd>
                        </div>
                    </dl>
                    <p v-else class="mt-3 text-xs text-muted-foreground">Sin parámetros UTM.</p>
                </DataCard>

                <DataCard class="p-4">
                    <h3 class="mb-3 text-sm font-semibold">Datos de captura</h3>
                    <ul class="grid gap-2.5 text-sm">
                        <li v-if="location" class="flex items-start gap-2.5"><MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" /><span>{{ location }}<a v-if="mapUrl" :href="mapUrl" target="_blank" rel="noopener" class="ml-1 text-xs text-primary hover:underline">ver mapa</a></span></li>
                        <li v-if="lead.capture.ip_address" class="flex items-center gap-2.5"><Globe class="size-4 shrink-0 text-muted-foreground" /><span>IP <code>{{ lead.capture.ip_address }}</code></span></li>
                        <li v-if="device" class="flex items-center gap-2.5" :title="String(lead.capture.user_agent)"><Monitor class="size-4 shrink-0 text-muted-foreground" />{{ device }}</li>
                        <li v-if="lead.capture.landing_url" class="break-all"><span class="text-xs text-muted-foreground">Landing</span><br />{{ lead.capture.landing_url }}</li>
                        <li v-if="lead.capture.referrer" class="break-all"><span class="text-xs text-muted-foreground">Referrer</span><br />{{ lead.capture.referrer }}</li>
                        <li v-if="!location && !lead.capture.ip_address && !device && !lead.capture.landing_url && !lead.capture.referrer" class="text-xs text-muted-foreground">Sin datos de captura (lead ingresado manualmente).</li>
                    </ul>
                    <dl v-if="metaEntries.length" class="mt-3 grid gap-1.5 border-t pt-3 text-xs">
                        <div v-for="[k, v] in metaEntries" :key="k" class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">{{ k }}</dt><dd class="max-w-[60%] truncate font-mono">{{ typeof v === 'object' ? JSON.stringify(v) : v }}</dd>
                        </div>
                    </dl>
                </DataCard>

                <DataCard v-if="customFields.length" class="p-4">
                    <h3 class="mb-3 text-sm font-semibold">Campos personalizados</h3>
                    <dl class="grid gap-2 text-sm">
                        <div v-for="f in customFields" :key="f.key" class="flex justify-between gap-3">
                            <dt class="text-muted-foreground">{{ f.label }}</dt>
                            <dd class="text-right font-medium">{{ f.value !== null && f.value !== '' ? showValue(f.type, f.value) : '—' }}</dd>
                        </div>
                    </dl>
                </DataCard>
            </div>
        </div>
    </div>

    <ConfirmDialog v-model:open="confirmDelete" title="Eliminar lead" :description="`Se eliminará «${lead.full_name}» con su historial.`" confirm-label="Eliminar" @confirm="deleteLead" />
</template>
