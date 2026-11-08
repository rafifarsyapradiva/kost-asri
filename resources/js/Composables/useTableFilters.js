import { ref, watch } from 'vue';
import { router } from '@inertiajs/vue3';

export function useTableFilters(routeName, initialFilters = {}, debounceMs = 300) {
    const filters = ref({ ...initialFilters });
    let debounceTimer = null;

    const applyFilters = () => {
        const query = {};
        for (const [key, value] of Object.entries(filters.value)) {
            if (value !== '' && value !== null && value !== undefined) {
                query[key] = value;
            }
        }

        router.get(
            routeName,
            query,
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            }
        );
    };

    watch(
        filters,
        () => {
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                applyFilters();
            }, debounceMs);
        },
        { deep: true }
    );

    const resetFilters = () => {
        filters.value = { ...initialFilters };
        applyFilters();
    };

    return {
        filters,
        applyFilters,
        resetFilters,
    };
}

