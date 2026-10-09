<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import FormField from '@/components/FormField.vue';
import { Button } from '@/components/ui/button';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle } from '@/components/ui/dialog';
import { NativeSelect } from '@/components/ui/native-select';

/** Eliminar algo con datos relacionados: transferirlos a otro registro o enviarlos a la papelera. */
const props = defineProps<{
    open: boolean;
    title: string;
    summary: string; // qué datos relacionados hay (ej. «2 leads y 1 propuesta»)
    targets: { id: number; name: string }[]; // posibles destinos de la transferencia
    transferLabel: string; // ej. «Transferirlos a otro origen»
}>();
const emit = defineEmits<{ 'update:open': [boolean]; confirm: [{ transfer_to: number | null }] }>();

const mode = ref<'transfer' | 'trash'>('transfer');
const target = ref<number | null>(null);
watch(
    () => props.open,
    (o) => {
        if (!o) return;
        mode.value = props.targets.length ? 'transfer' : 'trash';
        target.value = props.targets[0]?.id ?? null;
    },
);
const canConfirm = computed(() => mode.value === 'trash' || !!target.value);
const confirm = () => emit('confirm', { transfer_to: mode.value === 'transfer' ? target.value : null });
</script>

<template>
    <Dialog :open="open" @update:open="(v: boolean) => emit('update:open', v)">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>Tiene {{ summary }} relacionados. ¿Qué hacemos con ellos?</DialogDescription>
            </DialogHeader>
            <div class="grid gap-3 text-sm">
                <label v-if="targets.length" class="flex cursor-pointer items-start gap-3 rounded-xl border p-3" :class="mode === 'transfer' ? 'border-primary bg-accent/40' : ''">
                    <input v-model="mode" type="radio" value="transfer" class="mt-1" />
                    <span class="grid flex-1 gap-2">
                        <span class="font-medium">{{ transferLabel }}</span>
                        <FormField v-if="mode === 'transfer'" label="Destino">
                            <NativeSelect v-model="target">
                                <option v-for="t in targets" :key="t.id" :value="t.id">{{ t.name }}</option>
                            </NativeSelect>
                        </FormField>
                    </span>
                </label>
                <label class="flex cursor-pointer items-start gap-3 rounded-xl border p-3" :class="mode === 'trash' ? 'border-destructive bg-destructive/5' : ''">
                    <input v-model="mode" type="radio" value="trash" class="mt-1" />
                    <span>
                        <span class="font-medium">Enviarlos a la papelera</span>
                        <span class="block text-muted-foreground">Se eliminan junto con su historial; quedan guardados en la papelera.</span>
                    </span>
                </label>
            </div>
            <DialogFooter class="gap-2">
                <Button variant="outline" @click="emit('update:open', false)">Cancelar</Button>
                <Button :variant="mode === 'trash' ? 'destructive' : 'default'" :disabled="!canConfirm" @click="confirm">
                    {{ mode === 'transfer' ? 'Transferir y eliminar' : 'Eliminar todo' }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
