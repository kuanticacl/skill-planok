<script setup lang="ts">
import { router, usePage } from '@inertiajs/vue3';
import { ArrowUp, Check, ExternalLink, Sparkles, Trash2, X } from '@lucide/vue';
import { computed, nextTick, onMounted, ref, watch } from 'vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { HttpError, sendJson } from '@/lib/http';
import { renderMarkdown } from '@/lib/miniMarkdown';
import { cn } from '@/lib/utils';

type Action = { tool: string; label: string; url: string | null; ok: boolean };
type Msg = { role: 'user' | 'assistant'; content: string; actions?: Action[]; error?: boolean };

const page = usePage();
const storeKey = computed(() => `crm-agent:${(page.props.auth as { user?: { id?: number } } | undefined)?.user?.id ?? 'u'}`);

const open = ref(false);
const input = ref('');
const busy = ref(false);
const messages = ref<Msg[]>([]);
const available = ref<boolean | null>(null);
const scroller = ref<HTMLElement | null>(null);
const field = ref<HTMLTextAreaElement | null>(null);

const suggestions = [
    'Resume cómo vamos: leads por etapa y propuestas por estado',
    '¿En qué estado está la última propuesta que creé?',
    'Crea la propuesta con este texto: …',
    'Registra un seguimiento: llamé a … y quedamos en hablar el viernes',
];

onMounted(() => {
    try {
        messages.value = JSON.parse(localStorage.getItem(storeKey.value) ?? '[]');
    } catch {
        messages.value = [];
    }
});
watch(
    messages,
    (m) => {
        try {
            localStorage.setItem(storeKey.value, JSON.stringify(m.slice(-30)));
        } catch {
            /* sin almacenamiento: no se persiste */
        }
    },
    { deep: true },
);

const scrollDown = () => nextTick(() => scroller.value?.scrollTo({ top: scroller.value.scrollHeight, behavior: 'smooth' }));

const toggle = async () => {
    open.value = !open.value;
    if (open.value) {
        scrollDown();
        nextTick(() => field.value?.focus());
        if (available.value === null) {
            try {
                available.value = (await sendJson<{ available: boolean }>('GET', '/agent/status')).available;
            } catch {
                available.value = true;
            }
        }
    }
};

const resize = () => {
    const el = field.value;
    if (!el) return;
    el.style.height = 'auto';
    el.style.height = Math.min(el.scrollHeight, 180) + 'px';
};

const send = async (text?: string) => {
    const content = (text ?? input.value).trim();
    if (!content || busy.value) return;
    input.value = '';
    nextTick(resize);
    messages.value.push({ role: 'user', content });
    busy.value = true;
    scrollDown();
    try {
        const history = messages.value.filter((m) => !m.error).slice(-24).map(({ role, content }) => ({ role, content }));
        const res = await sendJson<{ reply: string; actions: Action[] }>('POST', '/agent/chat', { messages: history });
        messages.value.push({ role: 'assistant', content: res.reply, actions: res.actions });
        // Si el Agent creó o cambió algo, refresca la página actual para que se vea.
        if (res.actions?.some((a) => a.ok && /^(create|add|move|update)_/.test(a.tool))) router.reload();
    } catch (e) {
        const msg = e instanceof HttpError ? (e.body?.message ?? `Error ${e.status}`) : 'No se pudo conectar con el Agent.';
        messages.value.push({ role: 'assistant', content: msg, error: true });
    } finally {
        busy.value = false;
        scrollDown();
        nextTick(() => field.value?.focus());
    }
};

const onKey = (e: KeyboardEvent) => {
    if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
        e.preventDefault();
        send();
    }
};

const clear = () => {
    messages.value = [];
};
const visit = (url: string) => {
    const u = new URL(url, window.location.origin);
    if (u.origin === window.location.origin) router.visit(u.pathname + u.search);
    else window.open(url, '_blank', 'noopener');
};
</script>

<template>
    <div class="fixed bottom-4 right-4 z-50 flex flex-col items-end gap-3 print:hidden">
        <Transition
            enter-active-class="transition duration-150 ease-out"
            enter-from-class="translate-y-2 opacity-0"
            leave-active-class="transition duration-100 ease-in"
            leave-to-class="translate-y-2 opacity-0"
        >
            <section
                v-if="open"
                class="flex h-[min(640px,calc(100dvh-6rem))] w-[min(420px,calc(100vw-2rem))] flex-col overflow-hidden rounded-2xl border bg-background shadow-2xl"
                aria-label="Agent"
            >
                <header class="flex items-center gap-2 border-b bg-muted/40 px-4 py-3">
                    <span class="flex size-8 items-center justify-center rounded-full bg-primary text-primary-foreground"><Sparkles class="size-4" /></span>
                    <div class="min-w-0 flex-1">
                        <div class="text-sm font-semibold leading-tight">Agent</div>
                        <div class="text-xs text-muted-foreground">Pregunta o pide cualquier cosa del CRM</div>
                    </div>
                    <Button v-if="messages.length" variant="ghost" size="icon-sm" title="Nueva conversación" @click="clear"><Trash2 /></Button>
                    <Button variant="ghost" size="icon-sm" title="Cerrar" @click="open = false"><X /></Button>
                </header>

                <div ref="scroller" class="flex-1 space-y-3 overflow-y-auto px-4 py-4">
                    <div v-if="available === false" class="rounded-xl border border-amber-300 bg-amber-50 p-3 text-sm text-amber-900 dark:bg-amber-950/30 dark:text-amber-200">
                        Aún no hay un proveedor de IA configurado. Agrégalo en <strong>Inteligencia artificial → Proveedores</strong> para usar el Agent.
                    </div>

                    <div v-if="!messages.length" class="space-y-3">
                        <p class="text-sm text-muted-foreground">
                            Puedo buscar leads y clientes, crear propuestas desde un texto, registrar seguimientos, mover leads de etapa y decirte el estado de una propuesta.
                        </p>
                        <div class="flex flex-col gap-2">
                            <button
                                v-for="s in suggestions"
                                :key="s"
                                class="rounded-xl border px-3 py-2 text-left text-sm transition hover:bg-muted"
                                @click="s.endsWith('…') ? ((input = s.replace('…', '')), field?.focus()) : send(s)"
                            >
                                {{ s }}
                            </button>
                        </div>
                    </div>

                    <div v-for="(m, i) in messages" :key="i" :class="cn('flex', m.role === 'user' ? 'justify-end' : 'justify-start')">
                        <div :class="cn('max-w-[88%] space-y-2 rounded-2xl px-3.5 py-2.5 text-sm leading-relaxed', m.role === 'user' ? 'whitespace-pre-wrap bg-primary text-primary-foreground' : m.error ? 'border border-destructive/40 bg-destructive/5 text-destructive' : 'bg-muted')">
                            <template v-if="m.role === 'user'">{{ m.content }}</template>
                            <div v-else class="space-y-0.5 [&_a]:cursor-pointer" v-html="renderMarkdown(m.content)" @click.prevent="(e) => { const a = (e.target as HTMLElement).closest('a'); if (a) visit(a.getAttribute('href') || '') }" />
                            <ul v-if="m.actions?.length" class="space-y-1 border-t border-foreground/10 pt-2 text-xs">
                                <li v-for="(a, j) in m.actions" :key="j" class="flex items-start gap-1.5">
                                    <Check v-if="a.ok" class="mt-0.5 size-3.5 shrink-0 text-brand-green" />
                                    <X v-else class="mt-0.5 size-3.5 shrink-0 text-destructive" />
                                    <span class="min-w-0 flex-1">{{ a.label }}</span>
                                    <button v-if="a.url" class="shrink-0 text-primary" title="Abrir" @click="visit(a.url)"><ExternalLink class="size-3.5" /></button>
                                </li>
                            </ul>
                        </div>
                    </div>

                    <div v-if="busy" class="flex items-center gap-2 text-sm text-muted-foreground"><Spinner class="size-4" /> Trabajando en ello…</div>
                </div>

                <form class="flex items-end gap-2 border-t p-3" @submit.prevent="send()">
                    <textarea
                        ref="field"
                        v-model="input"
                        rows="1"
                        class="max-h-[180px] min-h-10 flex-1 resize-none rounded-xl border bg-background px-3 py-2 text-sm outline-none focus:ring-2 focus:ring-primary/40"
                        placeholder="Escribe o pega un texto…  (Enter envía, Shift+Enter salto de línea)"
                        :disabled="busy || available === false"
                        @input="resize"
                        @keydown="onKey"
                    />
                    <Button type="submit" size="icon" class="rounded-full" :disabled="busy || !input.trim() || available === false" title="Enviar"><ArrowUp /></Button>
                </form>
            </section>
        </Transition>

        <button
            class="flex items-center gap-2 rounded-full bg-primary px-4 py-3 text-sm font-semibold text-primary-foreground shadow-lg transition hover:scale-105 hover:shadow-xl focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-primary/50"
            :aria-expanded="open"
            aria-label="Abrir Agent"
            @click="toggle"
        >
            <Sparkles v-if="!open" class="size-4" /><X v-else class="size-4" />
            Agent
        </button>
    </div>
</template>
