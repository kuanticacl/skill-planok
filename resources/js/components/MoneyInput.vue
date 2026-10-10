<script setup lang="ts">
import { usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import { cn } from '@/lib/utils';

type Currency = 'CLP' | 'UF';

const props = defineProps<{ modelValue: string; currency: Currency; id?: string; disabled?: boolean; placeholder?: string }>();
const emit = defineEmits<{ 'update:modelValue': [string]; 'update:currency': [Currency]; commit: [] }>();

const page = usePage();
const uf = computed(() => (page.props.uf as { value: number; date: string } | null | undefined) ?? null);

const num = computed(() => (props.modelValue === '' ? null : Number(props.modelValue)));
const clp = new Intl.NumberFormat('es-CL', { maximumFractionDigits: 0 });
const ufFmt = new Intl.NumberFormat('es-CL', { minimumFractionDigits: 2, maximumFractionDigits: 2 });

/** Equivalente en la otra moneda con la UF de hoy. */
const equivalent = computed(() => {
    if (!uf.value || num.value === null || Number.isNaN(num.value)) return null;
    return props.currency === 'UF' ? `≈ $${clp.format(Math.round(num.value * uf.value.value))}` : `≈ UF ${ufFmt.format(num.value / uf.value.value)}`;
});

/** Al cambiar de moneda se convierte el monto ingresado (el valor del negocio no cambia). */
const choose = (c: Currency) => {
    if (c === props.currency || props.disabled) return;
    if (uf.value && num.value !== null && !Number.isNaN(num.value)) {
        const converted = c === 'UF' ? Math.round((num.value / uf.value.value) * 100) / 100 : Math.round(num.value * uf.value.value);
        emit('update:modelValue', String(converted));
    }
    emit('update:currency', c);
    emit('commit');
};
</script>

<template>
    <div>
        <div class="flex gap-2">
            <Input
                :id="id"
                :model-value="modelValue"
                type="number"
                min="0"
                :step="currency === 'UF' ? 0.01 : 1000"
                :disabled="disabled"
                :placeholder="placeholder ?? 'Sin definir'"
                class="min-w-0 flex-1"
                @update:model-value="emit('update:modelValue', String($event ?? ''))"
                @change="emit('commit')"
            />
            <div class="inline-flex shrink-0 rounded-xl border bg-muted/40 p-0.5" role="group" aria-label="Moneda">
                <button
                    v-for="c in (['CLP', 'UF'] as const)"
                    :key="c"
                    type="button"
                    :disabled="disabled || (c === 'UF' && !uf)"
                    :title="c === 'UF' && !uf ? 'UF no disponible por ahora' : undefined"
                    :class="cn('rounded-[10px] px-2.5 text-xs font-semibold transition-colors disabled:opacity-50', currency === c ? 'bg-primary text-primary-foreground shadow-sm' : 'text-muted-foreground hover:text-foreground')"
                    @click="choose(c)"
                >
                    {{ c }}
                </button>
            </div>
        </div>
        <p v-if="equivalent" class="mt-1 text-[11px] text-muted-foreground">{{ equivalent }} <span v-if="uf">· UF hoy ${{ clp.format(uf.value) }}</span></p>
    </div>
</template>
