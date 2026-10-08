<script setup lang="ts">
import RichTextEditor from '@/components/RichTextEditor.vue';
import { AlignCenter, AlignLeft, AlignRight } from '@lucide/vue';
import ColorField from '@/components/email/ColorField.vue';
import ImageField from '@/components/email/ImageField.vue';
import VariableField from '@/components/email/VariableField.vue';
import { Input } from '@/components/ui/input';
import { NativeSelect } from '@/components/ui/native-select';
import { Switch } from '@/components/ui/switch';
import { BLOCKS } from '@/lib/emailBuilder';
import type { Block } from '@/lib/emailBuilder';
import { cn } from '@/lib/utils';

defineProps<{ block: Block; variables: string[]; system: string[] }>();
</script>

<template>
    <div class="grid gap-4">
        <div v-for="f in BLOCKS[block.type].fields" :key="f.key" class="grid gap-1.5">
            <label v-if="f.type !== 'toggle'" class="text-xs font-medium text-muted-foreground">{{ f.label }}</label>

            <ImageField v-if="f.type === 'image'" v-model="block.props[f.key]" :variables="variables" :system="system" :allow-variables="f.variables" />
            <ColorField v-else-if="f.type === 'color'" v-model="block.props[f.key]" />
            <div v-else-if="f.type === 'align'" class="flex gap-1">
                <button
                    v-for="a in [['left', AlignLeft], ['center', AlignCenter], ['right', AlignRight]] as const"
                    :key="a[0]"
                    type="button"
                    :class="cn('flex size-9 items-center justify-center rounded-md border transition', block.props[f.key] === a[0] ? 'border-primary bg-accent text-accent-foreground' : 'hover:bg-muted')"
                    @click="block.props[f.key] = a[0]"
                >
                    <component :is="a[1]" class="size-4" />
                </button>
            </div>
            <label v-else-if="f.type === 'toggle'" class="flex items-center justify-between gap-3 text-sm">
                {{ f.label }}
                <Switch :model-value="!!block.props[f.key]" @update:model-value="(v: boolean) => (block.props[f.key] = v)" />
            </label>
            <NativeSelect v-else-if="f.type === 'select'" v-model="block.props[f.key]">
                <option v-for="o in f.options" :key="o.value" :value="o.value">{{ o.label }}</option>
            </NativeSelect>
            <Input v-else-if="f.type === 'number'" v-model.number="block.props[f.key]" type="number" :min="f.min" :max="f.max" step="any" />
            <RichTextEditor v-else-if="f.type === 'rich'" v-model="block.props[f.key]" :variables="variables" :system="system" :min-height="130" />
            <VariableField v-else-if="f.variables" v-model="block.props[f.key]" :multiline="f.type === 'textarea'" :variables="variables" :system="system" :rows="f.type === 'textarea' ? 5 : undefined" />
            <Input v-else v-model="block.props[f.key]" :type="f.type === 'url' ? 'url' : 'text'" :placeholder="f.type === 'url' ? 'https://…' : ''" />

            <p v-if="f.hint" class="text-[11px] text-muted-foreground">{{ f.hint }}</p>
        </div>
    </div>
</template>
