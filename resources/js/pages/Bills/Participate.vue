<script setup lang="ts">
import ScreenReaderAnnouncement from '@/components/ScreenReaderAnnouncement.vue';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import { useBillFiltering } from '@/composables/useBillFiltering';
import { usePagination } from '@/composables/usePagination';
import { useI18n } from '@/composables/useI18n';
import AppLayout from '@/layouts/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { useInfiniteScroll } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

interface BillSummary {
    simplified_summary_en: string | null;
    key_clauses: string[];
}

interface BillItem {
    id: number;
    title: string;
    bill_number: string;
    description: string;
    participation_end_date: string | null;
    submissions_count: number;
    tags: string[] | null;
    summary?: BillSummary | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface PaginatedBills {
    data: BillItem[];
    links: PaginationLink[];
    total: number;
    from: number | null;
    to: number | null;
    current_page?: number;
    last_page?: number;
    per_page?: number;
}

interface Props {
    bills: PaginatedBills;
    filters: {
        tag?: string;
        search?: string;
    };
}

const props = defineProps<Props>();
const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    { title: t('bills.title'), href: billRoutes.index().url },
    { title: t('bills.participate'), href: billRoutes.participate().url },
];

const {
    filters,
    hasActiveFilters,
    applyFilters,
    resetFilters,
} = useBillFiltering(props.filters, {
    defaultRoute: billRoutes.participate,
    visitOptions: {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        only: ['bills'],
    },
});

const {
    hasNextPage,
    nextPageUrl,
    navigateToUrl,
    isLoading: isPaginating,
    from,
    to,
    total,
} = usePagination(() => props.bills, {
    preserveScroll: true,
    only: ['bills'],
    replace: true,
});

const billItems = ref<BillItem[]>([...props.bills.data]);

watch(
    () => props.bills,
    (newValue, oldValue) => {
        const currentPage = newValue.current_page ?? 1;

        if (!oldValue || (oldValue.current_page ?? 1) > currentPage || currentPage <= 1) {
            billItems.value = [...newValue.data];
            return;
        }

        const existingIds = new Set(billItems.value.map((bill) => bill.id));
        const appended = newValue.data.filter((bill) => !existingIds.has(bill.id));

        billItems.value = [...billItems.value, ...appended];
    },
    { deep: true },
);

const hasResults = computed(() => billItems.value.length > 0);

const loadMoreRef = ref<HTMLElement | null>(null);

useInfiniteScroll(
    loadMoreRef,
    () => {
        if (isPaginating.value || !hasNextPage.value) {
            return;
        }

        const url = nextPageUrl.value;

        if (url) {
            navigateToUrl(url);
        }
    },
    { distance: 200 },
);

const submitFilters = () => {
    applyFilters();
};

const handleResetFilters = () => {
    resetFilters();
};

const prefetchBill = (billId: number) => {
    router.visit(billRoutes.show({ bill: billId }).url, {
        only: ['bill'],
        preserveState: true,
        preserveScroll: true,
        onBefore: () => false,
    });
};

const filterAnnouncement = computed(() => {
    if (!total.value || total.value === 0) {
        return t('common.no_results');
    }

    const fromValue = from.value ?? 0;
    const toValue = to.value ?? 0;

    return t('common.showing', {
        from: fromValue,
        to: toValue,
        total: total.value,
    });
});

const page = usePage<{ auth: { user: User | null } }>();
const layoutComponent = computed(() => (page.props.auth?.user ? AppLayout : PublicLayout));

const submissionCountLabel = (count: number) => {
    if (count === 1) {
        return t('accessibility.submissionCountSingular');
    }

    return t('accessibility.submissionCount', { count });
};

const participationClosesLabel = (date: string | null) => {
    if (!date) {
        return t('common.tbd');
    }

    return t('bills.closes_on', { date });
};
</script>

<template>
    <Head :title="t('bills.participate')" />

    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-12 md:px-6">
            <header class="rounded-3xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-400 p-8 text-white shadow-lg">
                <h1 class="text-3xl font-semibold">Participate in public bills</h1>
                <p class="mt-3 max-w-3xl text-base text-white/90">
                    Share your expertise or lived experience on live legislation. Every submission is reviewed by the parliamentary clerks before
                    committee reports are drafted.
                </p>
            </header>

            <section class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <ScreenReaderAnnouncement :message="filterAnnouncement" priority="polite" />
                <form class="grid gap-4 md:grid-cols-[2fr_1fr_auto]" @submit.prevent="submitFilters">
                    <div class="space-y-2">
                        <label for="search" class="text-sm font-semibold text-emerald-900">{{ t('common.search') }}</label>
                        <Input
                            id="search"
                            v-model="filters.search"
                            type="search"
                            :placeholder="t('accessibility.searchBills')"
                            class="h-11 rounded-lg border border-emerald-200/80 bg-white/80 text-emerald-900"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="tag" class="text-sm font-semibold text-emerald-900">Filter by tag</label>
                        <select
                            id="tag"
                            v-model="filters.tag"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 outline-none transition focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
                        >
                            <option value="all">All tags</option>
                            <option value="governance">Governance</option>
                            <option value="health">Health</option>
                            <option value="education">Education</option>
                            <option value="agriculture">Agriculture</option>
                            <option value="economy">Economy</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <Button
                            type="submit"
                            class="h-11 rounded-full bg-emerald-600 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            {{ t('common.search') }}
                        </Button>
                        <Button
                            v-if="hasActiveFilters"
                            type="button"
                            variant="outline"
                            class="h-11 rounded-full border-emerald-200 text-sm font-medium text-emerald-700 hover:border-emerald-400 hover:text-emerald-800"
                            @click="handleResetFilters"
                        >
                            {{ t('common.reset') }}
                        </Button>
                    </div>
                </form>
                <p class="mt-4 text-sm text-emerald-800/80">
                    {{ filterAnnouncement }}
                </p>
            </section>

            <section class="flex-1">
                <div v-if="hasResults" class="grid gap-5 md:grid-cols-2">
                    <article
                        v-for="bill in billItems"
                        :key="bill.id"
                        class="flex h-full flex-col gap-4 rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"
                    >
                        <header class="space-y-1">
                            <h2 class="text-xl font-semibold text-emerald-900">{{ bill.title }}</h2>
                            <p class="text-sm text-emerald-800/70">Bill {{ bill.bill_number }}</p>
                        </header>

                        <p class="text-sm text-emerald-800/80">
                            {{ bill.summary?.simplified_summary_en ?? bill.description }}
                        </p>

                        <ul v-if="bill.summary?.key_clauses?.length" class="space-y-2 text-sm text-emerald-800/80">
                            <li v-for="clause in bill.summary.key_clauses" :key="clause" class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                <span>{{ clause }}</span>
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2">
                            <span v-for="tag in bill.tags ?? []" :key="tag" class="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700">
                                {{ tag }}
                            </span>
                        </div>

                        <footer class="mt-auto flex flex-col gap-3 rounded-xl bg-emerald-50/70 p-4 text-sm text-emerald-800/80">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-emerald-900">{{ submissionCountLabel(bill.submissions_count) }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-emerald-900">{{ participationClosesLabel(bill.participation_end_date) }}</span>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="billRoutes.show({ bill: bill.id }).url"
                                    class="text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline"
                                    @mouseenter="prefetchBill(bill.id)"
                                >
                                    {{ t('bills.view_details') }}
                                </Link>
                                <Link
                                    :href="submissionRoutes.create.url({ query: { bill_id: bill.id } })"
                                    class="inline-flex rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    {{ t('bills.submit_feedback') }}
                                </Link>
                            </div>
                        </footer>
                    </article>
                </div>

                <div
                    v-else
                    class="flex min-h-[200px] flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200 bg-white/95 p-10 text-center text-emerald-800/70"
                >
                    <p class="font-medium">{{ t('bills.no_bills') }}</p>
                    <p class="mt-2 text-sm">{{ t('bills.no_bills_description') }}</p>
                </div>
            </section>

            <!-- Infinite scroll trigger (replaces pagination) -->
            <div v-if="hasResults" ref="loadMoreRef" class="flex h-20 items-center justify-center">
                <div v-if="hasNextPage" class="flex items-center gap-2 text-sm text-emerald-700">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-700 border-t-transparent" aria-hidden="true"></span>
                    <span>{{ t('common.loading') }}</span>
                </div>
                <div v-else class="text-sm text-emerald-600/70">{{ t('bills.all_loaded') }}</div>
            </div>
        </div>
    </component>
</template>
