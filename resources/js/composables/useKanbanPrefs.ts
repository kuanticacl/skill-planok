import { useStorage } from '@vueuse/core';

export type KanbanPrefs = {
    density: 'comfortable' | 'compact';
    show: {
        source: boolean;
        campaign: boolean;
        tags: boolean;
        value: boolean;
        followup: boolean;
        custom: boolean;
        stageTime: boolean;
    };
    collapsed: number[];
};

const defaults: KanbanPrefs = {
    density: 'comfortable',
    show: { source: true, campaign: true, tags: true, value: true, followup: true, custom: true, stageTime: true },
    collapsed: [],
};

/** Preferencias del tablero (densidad, qué mostrar en la tarjeta y columnas colapsadas), por navegador. */
export function useKanbanPrefs() {
    return useStorage<KanbanPrefs>('qb-kanban-prefs', defaults, undefined, { mergeDefaults: true });
}
