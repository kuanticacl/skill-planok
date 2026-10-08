<script setup lang="ts">
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Switch } from '@/components/ui/switch';
import { Textarea } from '@/components/ui/textarea';
import type { CustomFieldDef } from '@/types';

const props = defineProps<{ field: CustomFieldDef; id: string }>();
const model = defineModel<unknown>({ required: true });

const inputType = computed(
    () =>
        ({ number: 'number', email: 'email', phone: 'tel', url: 'url', date: 'date' })[
            props.field.type as string
        ] ?? 'text',
);
const str = computed({
    get: () => (model.value ?? '') as string | number,
    set: (v) => (model.value = v === '' ? null : v),
});
</script>

<template>
    <Textarea v-if="field.type === 'textarea'" :id="id" v-model="str" rows="3" />
    <NativeSelect v-else-if="field.type === 'select'" :id="id" v-model="str as string">
        <option value="">— Seleccionar —</option>
        <option v-for="o in field.options ?? []" :key="o" :value="o">{{ o }}</option>
    </NativeSelect>
    <label v-else-if="field.type === 'checkbox'" class="flex h-9 items-center gap-3 text-sm">
        <Switch :model-value="!!model" @update:model-value="(v: boolean) => (model = v)" />
        {{ model ? 'Sí' : 'No' }}
    </label>
    <Input v-else :id="id" v-model="str" :type="inputType" />
</template>
