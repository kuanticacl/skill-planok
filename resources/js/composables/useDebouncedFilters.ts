import { router } from '@inertiajs/vue3';
import { watch } from 'vue';
import type { Ref } from 'vue';

/** Recarga la página (solo las props indicadas) cuando cambian los filtros. */
export function useDebouncedFilters(
    url: string,
    filters: Ref<Record<string, string | number | null | undefined>>,
    only: string[],
    delay = 300,
) {
    let timer: ReturnType<typeof setTimeout> | undefined;

    watch(
        filters,
        () => {
            clearTimeout(timer);
            timer = setTimeout(() => {
                const query = Object.fromEntries(
                    Object.entries(filters.value).filter(
                        ([, v]) => v !== '' && v !== null && v !== undefined,
                    ),
                );
                router.get(url, query, {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                    only,
                });
            }, delay);
        },
        { deep: true },
    );
}
