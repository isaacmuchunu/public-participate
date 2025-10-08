<script setup lang="ts">
import StatusBadge from '@/components/StatusBadge.vue';
import ScreenReaderAnnouncement from '@/components/ScreenReaderAnnouncement.vue';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import { useBillFiltering } from '@/composables/useBillFiltering';
import { usePagination } from '@/composables/usePagination';
import { useI18n } from '@/composables/useI18n';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { useInfiniteScroll } from '@vueuse/core';
import { computed, ref, watch } from 'vue';

interface BillSummary {
    simplified_summary_en: string | null;
    simplified_summary_sw: string | null;
    key_clauses: string[];
    generated_at: string | null;
}

interface BillCreator {
    id: number;
    name: string;
}

interface BillItem {
    id: number;
    title: string;
    bill_number: string;
    type: string;
    house: string;
    status: string;
    sponsor: string | null;
    submissions_count: number;
    participation_end_date: string | null;
    tags: string[] | null;
    summary?: BillSummary | null;
    creator?: BillCreator | null;
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

interface BillFiltersProps {
    status?: string;
    house?: string;
    tag?: string;
    search?: string;
}

interface Props {
    bills: PaginatedBills;
    filters: BillFiltersProps;
}

const props = defineProps<Props>();
const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: t('bills.title'),
        href: billRoutes.index().url,
    },
];

const {
    filters,
    hasActiveFilters,
    applyFilters,
    resetFilters,
} = useBillFiltering(props.filters, {
    defaultRoute: billRoutes.index,
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

const fallbackLabel = (value: string) => value.replace(/_/g, ' ');

const statusLabel = (status: string) => {
    const key = `bills.status.${status}`;
    const translated = t(key);
    return translated === key ? fallbackLabel(status) : translated;
};

const houseLabel = (house: string) => {
    const key = `bills.house.${house}`;
    const translated = t(key);
    return translated === key ? fallbackLabel(house) : translated;
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

const prefetchBill = (billId: number) => {
    router.visit(billRoutes.show({ bill: billId }).url, {
        only: ['bill'],
        preserveState: true,
        preserveScroll: true,
        onBefore: () => false,
    });
};
</script>

<template>
    <Head :title="t('bills.title')" />

    <PublicLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-12 md:px-6">
            <header class="flex flex-col gap-3 text-emerald-900">
                <h1 class="text-3xl font-semibold leading-tight">{{ t('bills.title') }}</h1>
                <p class="max-w-2xl text-base text-emerald-800/80">
                    Review current legislation, understand its public impact, and raise your voice before participation windows close.
                </p>
            </header>

            <section class="rounded-2xl border border-emerald-100/70 bg-white/80 p-6 shadow-sm backdrop-blur">
                <ScreenReaderAnnouncement :message="filterAnnouncement" priority="polite" />
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitFilters">
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
                        <label for="status" class="text-sm font-semibold text-emerald-900">Status</label>
                        <select
                            id="status"
                            v-model="filters.status"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 outline-none transition focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
                        >
                            <option value="all">All statuses</option>
                            <option value="draft">Draft</option>
                            <option value="gazetted">Gazetted</option>
                            <option value="open_for_participation">Open for participation</option>
                            <option value="closed">Closed</option>
                            <option value="committee_review">Committee review</option>
                            <option value="passed">Passed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="house" class="text-sm font-semibold text-emerald-900">House</label>
                        <select
                            id="house"
                            v-model="filters.house"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 outline-none transition focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
                        >
                            <option value="all">All houses</option>
                            <option value="national_assembly">National Assembly</option>
                            <option value="senate">Senate</option>
                            <option value="both">Joint sittings</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="tag" class="text-sm font-semibold text-emerald-900">Tag</label>
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

                    <div class="flex items-end gap-2 md:col-span-4">
                        <Button
                            type="submit"
                            class="h-11 rounded-full bg-emerald-600 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700"
                        >
                            {{ t('common.filter') }}
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
                        <div class="ml-auto text-sm text-emerald-800/80">
                            {{ filterAnnouncement }}
                        </div>
                    </div>
                </form>
            </section>

            <section class="flex-1">
                <div v-if="hasResults" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="bill in billItems"
                        :key="bill.id"
                        class="flex h-full flex-col gap-4 rounded-2xl border border-emerald-100/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"
                    >
                        <header class="flex flex-col gap-1">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="text-lg font-semibold text-emerald-900">{{ bill.title }}</h2>
                                <StatusBadge :status="bill.status" :label="statusLabel(bill.status)" />
                            </div>
                            <p class="text-sm text-emerald-800/70">Bill {{ bill.bill_number }} • {{ houseLabel(bill.house) }}</p>
                        </header>

                        <p v-if="bill.summary?.simplified_summary_en" class="line-clamp-3 text-sm text-emerald-800/80">
                            {{ bill.summary.simplified_summary_en }}
                        </p>

                        <div class="mt-auto flex flex-wrap items-center gap-2">
                            <span v-for="tag in bill.tags ?? []" :key="tag" class="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700">
                                {{ tag }}
                            </span>
                        </div>

                        <footer class="flex items-center justify-between gap-3">
                            <div class="flex flex-col text-xs text-emerald-800/70">
                                <span>
                                    {{
                                        bill.submissions_count === 1
                                            ? t('accessibility.submissionCountSingular')
                                            : t('accessibility.submissionCount', { count: bill.submissions_count })
                                    }}
                                </span>
                                <span v-if="bill.participation_end_date"> Participation closes {{ bill.participation_end_date }} </span>
                            </div>

                            <Link
                                :href="billRoutes.show({ bill: bill.id }).url"
                                class="text-sm font-semibold text-emerald-700 hover:text-emerald-900"
                                @mouseenter="prefetchBill(bill.id)"
                            >
                                {{ t('bills.view_details') }}
                            </Link>
                        </footer>
                    </article>
                </div>

                <div
                    v-else
                    class="flex min-h-[200px] items-center justify-center rounded-2xl border border-dashed border-emerald-200 bg-white/90 p-10 text-center text-emerald-800/70"
                >
                    <div>
                        <p class="font-medium">{{ t('bills.no_bills') }}</p>
                        <p class="mt-2 text-sm">{{ t('bills.no_bills_description') }}</p>
                    </div>
                </div>
            </section>

            <!-- Infinite scroll trigger -->
            <div v-if="hasResults" ref="loadMoreRef" class="flex h-20 items-center justify-center">
                <div v-if="hasNextPage" class="flex items-center gap-2 text-sm text-emerald-700">
                    <span class="h-4 w-4 animate-spin rounded-full border-2 border-emerald-700 border-t-transparent" aria-hidden="true"></span>
                    <span>{{ t('common.loading') }}</span>
                </div>
                <div v-else class="text-sm text-emerald-700/70">
                    {{ t('bills.all_loaded') }}
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
