<script setup lang="ts">
import { Upload, X } from '@lucide/vue';
import { ref } from 'vue';
import { toast } from 'vue-sonner';
import VariableField from '@/components/email/VariableField.vue';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';
import { media } from '@/routes/templates';

defineProps<{ variables?: string[]; system?: string[]; allowVariables?: boolean }>();
const model = defineModel<string>({ default: '' });

const busy = ref(false);
const input = ref<HTMLInputElement | null>(null);

const upload = async (e: Event) => {
    const file = (e.target as HTMLInputElement).files?.[0];
    if (!file) return;
    busy.value = true;
    try {
        const xsrf = decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
        const body = new FormData();
        body.append('file', file);
        const res = await fetch(media().url, { method: 'POST', body, credentials: 'same-origin', headers: { Accept: 'application/json', 'X-XSRF-TOKEN': xsrf } });
        if (!res.ok) throw new Error(String(res.status));
        model.value = (await res.json()).url;
        toast.success('Imagen subida');
    } catch {
        toast.error('No se pudo subir la imagen (máx. 3 MB; jpg, png, gif o webp).');
    } finally {
        busy.value = false;
        if (input.value) input.value.value = '';
    }
};
</script>

<template>
    <div class="grid gap-2">
        <VariableField v-if="allowVariables" v-model="model" :variables="variables" :system="system" placeholder="https://…/imagen.jpg" />
        <input v-else v-model="model" class="h-9 w-full rounded-md border border-input bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50" placeholder="https://…/imagen.jpg" />
        <div class="flex items-center gap-2">
            <Button type="button" variant="outline" size="sm" :disabled="busy" @click="input?.click()"><Spinner v-if="busy" /><Upload v-else /> Subir imagen</Button>
            <Button v-if="model" type="button" variant="ghost" size="sm" @click="model = ''"><X /> Quitar</Button>
            <input ref="input" type="file" accept="image/png,image/jpeg,image/gif,image/webp" class="hidden" @change="upload" />
        </div>
        <img v-if="model && !model.includes('{{')" :src="model" alt="" class="max-h-24 rounded-md border object-contain" />
    </div>
</template>
