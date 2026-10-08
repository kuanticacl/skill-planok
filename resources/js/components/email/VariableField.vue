<script setup lang="ts">
import { Braces } from '@lucide/vue';
import { ref } from 'vue';
import { Button } from '@/components/ui/button';
import { DropdownMenu, DropdownMenuContent, DropdownMenuItem, DropdownMenuLabel, DropdownMenuSeparator, DropdownMenuTrigger } from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import { Textarea } from '@/components/ui/textarea';

const props = defineProps<{ multiline?: boolean; variables?: string[]; system?: string[]; placeholder?: string; id?: string; rows?: number }>();
const model = defineModel<string>({ default: '' });
const el = ref<{ $el: HTMLElement } | null>(null);

/** Inserta {{ variable }} en la posición del cursor. */
const insert = (key: string) => {
    const node = (el.value?.$el ?? null) as HTMLInputElement | HTMLTextAreaElement | null;
    const token = `{{ ${key} }}`;
    const value = model.value ?? '';
    if (!node || node.selectionStart == null) {
        model.value = value + token;
        return;
    }
    const start = node.selectionStart;
    const end = node.selectionEnd ?? start;
    model.value = value.slice(0, start) + token + value.slice(end);
    requestAnimationFrame(() => {
        node.focus();
        node.setSelectionRange(start + token.length, start + token.length);
    });
};
</script>

<template>
    <div class="relative">
        <Textarea v-if="multiline" :id="id" ref="el" v-model="model" :rows="rows ?? 4" :placeholder="placeholder" class="pr-9" />
        <Input v-else :id="id" ref="el" v-model="model" :placeholder="placeholder" class="pr-9" />
        <DropdownMenu>
            <DropdownMenuTrigger as-child>
                <Button type="button" variant="ghost" size="icon-sm" class="absolute top-1 right-1 size-7 text-muted-foreground" title="Insertar variable"><Braces class="size-4" /></Button>
            </DropdownMenuTrigger>
            <DropdownMenuContent align="end" class="max-h-72 w-56 overflow-y-auto">
                <template v-if="variables?.length">
                    <DropdownMenuLabel>De esta plantilla</DropdownMenuLabel>
                    <DropdownMenuItem v-for="v in variables" :key="v" @select="insert(v)"><code class="text-xs">{{ v }}</code></DropdownMenuItem>
                    <DropdownMenuSeparator />
                </template>
                <DropdownMenuLabel>Del sistema</DropdownMenuLabel>
                <DropdownMenuItem v-for="v in system ?? []" :key="v" @select="insert(v)"><code class="text-xs">{{ v }}</code></DropdownMenuItem>
            </DropdownMenuContent>
        </DropdownMenu>
    </div>
</template>
