import type { VisitOptions } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

interface BillFilters {
    status?: string;
    house?: string;
    tag?: string;
    search?: string;
}

type RouteQueryOptions = {
    query?: Record<string, string>;
    mergeQuery?: Record<string, string>;
};

type RouteFactory = (options?: RouteQueryOptions) => { url: string };

interface UseBillFilteringOptions {
    /** Optional default route factory to apply filters against */
    defaultRoute?: RouteFactory;
    /** Default visit options when applying filters */
    visitOptions?: Partial<VisitOptions>;
    /** Automatically apply filters when reset is called */
    autoApplyOnReset?: boolean;
}

const DEFAULT_VISIT_OPTIONS: Partial<VisitOptions> = {
    method: 'get',
    preserveState: true,
    preserveScroll: true,
    replace: true,
};

export function useBillFiltering(initialFilters: BillFilters = {}, options: UseBillFilteringOptions = {}) {
    const { defaultRoute, visitOptions, autoApplyOnReset = true } = options;

    const filters = reactive({
        status: initialFilters.status ?? 'all',
        house: initialFilters.house ?? 'all',
        tag: initialFilters.tag ?? 'all',
        search: initialFilters.search ?? '',
    });

    const hasActiveFilters = computed(() => {
        return filters.status !== 'all' || filters.house !== 'all' || filters.tag !== 'all' || filters.search !== '';
    });

    const buildQuery = () => {
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

        return query;
    };

    const resolveRouteUrl = (factory?: RouteFactory, query?: Record<string, string>) => {
        if (!factory) {
            return null;
        }

        const result = factory({ query });
        return result?.url ?? null;
    };

    const applyFilters = (factory?: RouteFactory, overrideVisitOptions?: Partial<VisitOptions>) => {
        const routeFactory = factory ?? defaultRoute;
        const query = buildQuery();
        const url = resolveRouteUrl(routeFactory, query);

        if (!url) {
            console.warn('useBillFiltering: No route provided to applyFilters.');
            return;
        }

        router.visit(url, {
            ...DEFAULT_VISIT_OPTIONS,
            ...visitOptions,
            ...overrideVisitOptions,
        });
    };

    const resetFilters = (factory?: RouteFactory) => {
        filters.status = 'all';
        filters.house = 'all';
        filters.tag = 'all';
        filters.search = '';

        if (autoApplyOnReset) {
            applyFilters(factory ?? defaultRoute);
        }
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
        buildQuery,
    };
}
