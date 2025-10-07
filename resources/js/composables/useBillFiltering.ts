import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface BillFilters {
    status?: string;
    house?: string;
    tag?: string;
    search?: string;
}

export function useBillFiltering(initialFilters: BillFilters = {}) {
    const filters = reactive({
        status: initialFilters.status ?? 'all',
        house: initialFilters.house ?? 'all',
        tag: initialFilters.tag ?? 'all',
        search: initialFilters.search ?? '',
    });

    const hasActiveFilters = computed(() => {
        return filters.status !== 'all' || filters.house !== 'all' || filters.tag !== 'all' || filters.search !== '';
    });

    const applyFilters = (routeName: string = 'bills.index') => {
        const query: Record<string, string> = {};

        if (filters.status && filters.status !== 'all') {
            query.status = filters.status;
        }

        if (filters.house && filters.house !== 'all') {
            query.house = filters.house;
        }

        if (filters.tag && filters.tag !== 'all') {
            query.tag = filters.tag;
        }

        if (filters.search) {
            query.search = filters.search;
        }

        router.get(`/${routeName}`, query, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    };

    const resetFilters = () => {
        filters.status = 'all';
        filters.house = 'all';
        filters.tag = 'all';
        filters.search = '';
    };

    const setFilter = (key: keyof BillFilters, value: string) => {
        filters[key] = value;
    };

    return {
        filters,
        hasActiveFilters,
        applyFilters,
        resetFilters,
        setFilter,
    };
}
