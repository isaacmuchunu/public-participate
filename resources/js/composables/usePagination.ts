import { router } from '@inertiajs/vue3';
import { computed, ref, type Ref } from 'vue';

/**
 * Pagination link structure from Laravel paginator
 */
export interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

/**
 * Paginated data structure from Laravel
 */
export interface PaginatedData<T = any> {
    data: T[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

/**
 * Options for pagination behavior
 */
export interface UsePaginationOptions {
    /**
     * Whether to preserve the current page state during navigation
     * @default true
     */
    preserveState?: boolean;

    /**
     * Whether to preserve scroll position during pagination
     * @default false
     */
    preserveScroll?: boolean;

    /**
     * Properties to reload when paginating (Inertia partial reloads)
     * @default undefined (reload all)
     */
    only?: string[];

    /**
     * Whether to replace the history state instead of pushing
     * @default true
     */
    replace?: boolean;
}

/**
 * Composable for handling pagination with Inertia.js
 *
 * @description
 * Provides a reusable pagination interface that works seamlessly with Laravel's
 * pagination and Inertia.js. Handles page navigation, loading states, and
 * provides computed properties for pagination metadata.
 *
 * @example
 * ```typescript
 * const { goToPage, nextPage, previousPage, isLoading, hasNextPage } = usePagination(
 *   () => props.bills,
 *   { preserveScroll: true }
 * )
 * ```
 *
 * @param dataRef - Reactive reference to paginated data or getter function
 * @param options - Configuration options for pagination behavior
 * @returns Pagination utilities and state
 */
export function usePagination<T = any>(dataRef: Ref<PaginatedData<T>> | (() => PaginatedData<T>), options: UsePaginationOptions = {}) {
    const { preserveState = true, preserveScroll = false, only, replace = true } = options;

    const isLoading = ref(false);

    // Get paginated data (support both ref and getter function)
    const paginatedData = computed(() => {
        return typeof dataRef === 'function' ? dataRef() : dataRef.value;
    });

    // Pagination metadata
    const currentPage = computed(() => paginatedData.value.current_page ?? 1);
    const lastPage = computed(() => paginatedData.value.last_page ?? 1);
    const total = computed(() => paginatedData.value.total);
    const from = computed(() => paginatedData.value.from);
    const to = computed(() => paginatedData.value.to);
    const perPage = computed(() => paginatedData.value.per_page ?? 15);

    // Navigation state
    const hasNextPage = computed(() => {
        const nextLink = paginatedData.value.links.find((link) => link.label.toLowerCase().includes('next'));
        return !!(nextLink && nextLink.url);
    });

    const hasPreviousPage = computed(() => {
        const prevLink = paginatedData.value.links.find((link) => link.label.toLowerCase().includes('prev'));
        return !!(prevLink && prevLink.url);
    });

    const nextPageUrl = computed(() => {
        const nextLink = paginatedData.value.links.find((link) => link.label.toLowerCase().includes('next'));
        return nextLink?.url;
    });

    const previousPageUrl = computed(() => {
        const prevLink = paginatedData.value.links.find((link) => link.label.toLowerCase().includes('prev'));
        return prevLink?.url;
    });

    /**
     * Navigate to a specific page URL
     *
     * @param url - The pagination URL to navigate to
     */
    const navigateToUrl = (url: string | null) => {
        if (!url) {
            return;
        }

        isLoading.value = true;

        router.visit(url, {
            preserveState,
            preserveScroll,
            only,
            replace,
            onFinish: () => {
                isLoading.value = false;
            },
        });
    };

    /**
     * Navigate to the next page
     */
    const nextPage = () => {
        navigateToUrl(nextPageUrl.value);
    };

    /**
     * Navigate to the previous page
     */
    const previousPage = () => {
        navigateToUrl(previousPageUrl.value);
    };

    /**
     * Navigate to a specific page number
     *
     * @param pageNumber - The page number to navigate to
     */
    const goToPage = (pageNumber: number) => {
        const link = paginatedData.value.links.find((link) => {
            const label = link.label.replace(/&laquo;|&raquo;/g, '').trim();
            return label === String(pageNumber);
        });

        if (link && link.url) {
            navigateToUrl(link.url);
        }
    };

    /**
     * Format pagination label (removes HTML entities)
     *
     * @param label - Raw label from Laravel pagination
     * @returns Formatted label
     */
    const formatLabel = (label: string): string => {
        return label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
    };

    /**
     * Get visible page numbers for pagination UI
     *
     * @param maxVisible - Maximum number of page links to show (default: 7)
     * @returns Array of page numbers to display
     */
    const visiblePages = (maxVisible = 7): number[] => {
        const total = lastPage.value;
        const current = currentPage.value;

        if (total <= maxVisible) {
            return Array.from({ length: total }, (_, i) => i + 1);
        }

        const half = Math.floor(maxVisible / 2);
        let start = Math.max(1, current - half);
        const end = Math.min(total, start + maxVisible - 1);

        if (end - start + 1 < maxVisible) {
            start = Math.max(1, end - maxVisible + 1);
        }

        return Array.from({ length: end - start + 1 }, (_, i) => start + i);
    };

    /**
     * Get pagination summary text
     *
     * @returns Formatted summary (e.g., "Showing 1-15 of 100")
     */
    const paginationSummary = computed(() => {
        const fromValue = from.value ?? 0;
        const toValue = to.value ?? 0;
        const totalValue = total.value;

        if (totalValue === 0) {
            return 'No results';
        }

        return `Showing ${fromValue}-${toValue} of ${totalValue}`;
    });

    return {
        // State
        isLoading,

        // Metadata
        currentPage,
        lastPage,
        total,
        from,
        to,
        perPage,
        paginationSummary,

        // Navigation state
        hasNextPage,
        hasPreviousPage,
        nextPageUrl,
        previousPageUrl,

        // Methods
        nextPage,
        previousPage,
        goToPage,
        navigateToUrl,
        formatLabel,
        visiblePages,

        // Raw data
        links: computed(() => paginatedData.value.links),
        data: computed(() => paginatedData.value.data),
    };
}
