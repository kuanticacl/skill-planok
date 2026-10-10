<script setup lang="ts">
import { computed, ref } from 'vue';

type Series = { key: string; label: string; color: string; values: number[] };
const props = withDefaults(
    defineProps<{
        labels: string[]; // una etiqueta por barra (YYYY-MM-DD)
        series: Series[]; // se apilan de abajo hacia arriba
        format?: (n: number) => string;
        height?: number;
        chartLabel: string;
    }>(),
    { format: (n: number) => String(n), height: 190 },
);

const W = 720;
const padL = 46;
const padR = 8;
const padT = 10;
const padB = 24;
const hover = ref<number | null>(null);

const totals = computed(() => props.labels.map((_, i) => props.series.reduce((s, x) => s + (x.values[i] ?? 0), 0)));
const niceMax = computed(() => {
    const max = Math.max(...totals.value, 0);
    if (max <= 0) return 1;
    const pow = 10 ** Math.floor(Math.log10(max));
    const n = max / pow;
    return (n <= 1 ? 1 : n <= 2 ? 2 : n <= 5 ? 5 : 10) * pow;
});
const ticks = computed(() => [0, 0.25, 0.5, 0.75, 1].map((f) => niceMax.value * f));
const plotH = computed(() => props.height - padT - padB);
const slot = computed(() => (W - padL - padR) / Math.max(1, props.labels.length));
const barW = computed(() => Math.max(2, slot.value * 0.68));
const y = (v: number) => padT + plotH.value - (v / niceMax.value) * plotH.value;
const x = (i: number) => padL + i * slot.value + (slot.value - barW.value) / 2;

const bars = computed(() =>
    props.labels.map((_, i) => {
        let acc = 0;
        return props.series.map((s) => {
            const v = s.values[i] ?? 0;
            const top = y(acc + v);
            const h = y(acc) - top;
            acc += v;
            return { key: s.key, color: s.color, x: x(i), y: top, h: Math.max(0, h), v };
        });
    }),
);

const every = computed(() => Math.max(1, Math.ceil(props.labels.length / 8)));
const short = (d: string) => `${d.slice(8, 10)}/${d.slice(5, 7)}`;
const fmtDay = (d: string) => new Intl.DateTimeFormat('es-CL', { weekday: 'short', day: 'numeric', month: 'short' }).format(new Date(d + 'T12:00:00'));
</script>

<template>
    <div class="relative">
        <svg :viewBox="`0 0 ${W} ${height}`" class="w-full" role="img" :aria-label="chartLabel" @mouseleave="hover = null">
            <g class="text-muted-foreground">
                <g v-for="t in ticks" :key="t">
                    <line :x1="padL" :x2="W - padR" :y1="y(t)" :y2="y(t)" stroke="currentColor" stroke-opacity="0.15" />
                    <text :x="padL - 6" :y="y(t) + 3.5" text-anchor="end" font-size="10" fill="currentColor">{{ format(t) }}</text>
                </g>
                <template v-for="(l, i) in labels" :key="l">
                    <text v-if="i % every === 0" :x="x(i) + barW / 2" :y="height - 7" text-anchor="middle" font-size="10" fill="currentColor">{{ short(l) }}</text>
                </template>
            </g>
            <g v-for="(col, i) in bars" :key="i">
                <rect :x="padL + i * slot" :y="padT" :width="slot" :height="plotH" fill="transparent" @mouseenter="hover = i" />
                <rect v-for="b in col" :key="b.key" :x="b.x" :y="b.y" :width="barW" :height="b.h" :fill="b.color" rx="1.5" :opacity="hover === null || hover === i ? 1 : 0.55" pointer-events="none" />
            </g>
        </svg>
        <div
            v-if="hover !== null"
            class="pointer-events-none absolute top-0 z-10 min-w-36 rounded-lg border bg-popover px-3 py-2 text-xs text-popover-foreground shadow-md"
            :style="{ left: `clamp(0px, calc(${((x(hover) + barW / 2) / W) * 100}% - 70px), calc(100% - 150px))` }"
        >
            <div class="mb-1 font-medium capitalize">{{ fmtDay(labels[hover]) }}</div>
            <div v-for="s in series" :key="s.key" class="flex items-center justify-between gap-3">
                <span class="flex items-center gap-1.5"><span class="size-2 rounded-sm" :style="{ background: s.color }" />{{ s.label }}</span>
                <span class="font-medium tabular-nums">{{ format(s.values[hover] ?? 0) }}</span>
            </div>
        </div>
        <div class="mt-1 flex flex-wrap gap-x-4 gap-y-1 text-xs text-muted-foreground">
            <span v-for="s in series" :key="s.key" class="flex items-center gap-1.5"><span class="size-2.5 rounded-sm" :style="{ background: s.color }" />{{ s.label }}</span>
        </div>
    </div>
</template>
