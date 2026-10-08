<script setup lang="ts">
import { Copy } from '@lucide/vue';
import { toast } from 'vue-sonner';
import { Input } from '@/components/ui/input';
import type { VarMeta } from '@/types';

defineProps<{ detected: string[]; system: { key: string; description: string }[] }>();
const meta = defineModel<Record<string, VarMeta>>({ required: true });

const copy = async (key: string) => {
    try {
        await navigator.clipboard.writeText(`{{ ${key} }}`);
        toast.success(`{{ ${key} }} copiado`);
    } catch {
        toast.error('No se pudo copiar');
    }
};
const row = (key: string): VarMeta => (meta.value[key] ??= { label: key, default: '', sample: '' });
</script>

<template>
    <div class="grid gap-5">
        <div>
            <h4 class="mb-1 text-sm font-semibold">Variables de esta plantilla</h4>
            <p class="mb-3 text-xs text-muted-foreground">Se detectan solas cuando escribes <code v-pre>{{ nombre }}</code> en el asunto o el contenido. El <em>valor de ejemplo</em> se usa en la vista previa y en el envío de prueba; el <em>valor por defecto</em> se usa si la API no envía la variable.</p>
            <p v-if="!detected.length" class="rounded-xl border border-dashed p-4 text-center text-xs text-muted-foreground">Aún no hay variables. Prueba con <code v-pre>{{ first_name }}</code>.</p>
            <div v-for="k in detected" :key="k" class="mb-3 grid gap-1.5 rounded-xl border bg-card p-3">
                <div class="flex items-center justify-between">
                    <code class="text-xs font-semibold text-primary">{{ k }}</code>
                    <button type="button" class="text-muted-foreground hover:text-foreground" title="Copiar" @click="copy(k)"><Copy class="size-3.5" /></button>
                </div>
                <label class="text-[11px] text-muted-foreground">Valor de ejemplo</label>
                <Input :model-value="row(k).sample" class="h-8" placeholder="Ej: María" @update:model-value="(v) => (row(k).sample = String(v))" />
                <label class="text-[11px] text-muted-foreground">Valor por defecto (si no llega)</label>
                <Input :model-value="row(k).default" class="h-8" placeholder="Opcional" @update:model-value="(v) => (row(k).default = String(v))" />
            </div>
        </div>

        <div>
            <h4 class="mb-2 text-sm font-semibold">Variables del sistema</h4>
            <ul class="grid gap-1.5">
                <li v-for="s in system" :key="s.key" class="flex items-center justify-between gap-2 rounded-lg px-2 py-1.5 text-xs hover:bg-muted">
                    <span><code class="font-semibold">{{ s.key }}</code><br /><span class="text-muted-foreground">{{ s.description }}</span></span>
                    <button type="button" class="text-muted-foreground hover:text-foreground" title="Copiar" @click="copy(s.key)"><Copy class="size-3.5" /></button>
                </li>
            </ul>
        </div>

        <div class="rounded-xl bg-muted/60 p-3 text-xs leading-relaxed text-muted-foreground">
            <p class="mb-1 font-semibold text-foreground">Sintaxis</p>
            <p><code v-pre>{{ x | default:"Hola" }}</code> valor por defecto</p>
            <p><code v-pre>{{ x | upper }}</code> · <code>lower</code> · <code>capitalize</code> · <code>money</code> · <code>date:"d/m/Y"</code></p>
            <p><code v-pre>{{#if x}}…{{else}}…{{/if}}</code> condicional</p>
            <p><code v-pre>{{#each lista}}{{ this.campo }}{{/each}}</code> bucle</p>
        </div>
    </div>
</template>
