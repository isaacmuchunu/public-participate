<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';

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

interface Props {
    bills: {
        data: BillItem[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        status?: string;
        house?: string;
        tag?: string;
        search?: string;
    };
}

const props = defineProps<Props>();
const { t } = useI18n();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Bills',
        href: billRoutes.index().url,
    },
];

const filterForm = reactive({
    status: props.filters?.status ?? 'all',
    house: props.filters?.house ?? 'all',
    tag: props.filters?.tag ?? 'all',
    search: props.filters?.search ?? '',
});

const hasResults = computed(() => props.bills.data.length > 0);

const loadMoreRef = ref<HTMLElement | null>(null);

useInfiniteScroll(
    loadMoreRef,
    () => {
        const nextLink = props.bills.links.find((link) => link.label.includes('Next'));
        if (nextLink && nextLink.url) {
            router.visit(nextLink.url, {
                preserveState: true,
                preserveScroll: true,
                only: ['bills'],
                onSuccess: () => {
                    // Bills will be merged automatically with Inertia.js merge props
                },
            });
        }
    },
    { distance: 200 },
);

const submitFilters = () => {
    const query: Record<string, string> = {};

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.house && filterForm.house !== 'all') {
        query.house = filterForm.house;
    }

    if (filterForm.tag && filterForm.tag !== 'all') {
        query.tag = filterForm.tag;
    }

    if (filterForm.search) {
        query.search = filterForm.search;
    }

    router.get(
        billRoutes.index.url({
            query,
        }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.status = 'all';
    filterForm.house = 'all';
    filterForm.tag = 'all';
    filterForm.search = '';

    submitFilters();
};

const statusBadgeClasses = (status: string) => {
    switch (status) {
        case 'open_for_participation':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
        case 'closed':
        case 'rejected':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300';
        case 'passed':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatLabel = (value: string) => value.split('_').join(' ');

const paginationLabel = (label: string) => label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
</script>

<template>
    <Head title="Bills" />

    <PublicLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-12 md:px-6">
            <header class="flex flex-col gap-3 text-emerald-900">
                <h1 class="text-3xl leading-tight font-semibold">{{ t('bills.title') }}</h1>
                <p class="max-w-2xl text-base text-emerald-800/80">
                    Review current legislation, understand its public impact, and raise your voice before participation windows close.
                </p>
            </header>

            <section class="rounded-2xl border border-emerald-100/70 bg-white/80 p-6 shadow-sm backdrop-blur">
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitFilters">
                    <div class="space-y-2">
                        <label for="search" class="text-sm font-semibold text-emerald-900">Search</label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            type="search"
                            placeholder="Search by title or number"
                            class="h-11 rounded-lg border border-emerald-200/80 bg-white/80 text-emerald-900"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="status" class="text-sm font-semibold text-emerald-900">Status</label>
                        <select
                            id="status"
                            v-model="filterForm.status"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 transition outline-none focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
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
                            v-model="filterForm.house"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 transition outline-none focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
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
                            v-model="filterForm.tag"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 transition outline-none focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
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
                            Apply filters
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-11 rounded-full border-emerald-200 text-sm font-medium text-emerald-700 hover:border-emerald-400 hover:text-emerald-800"
                            @click="resetFilters"
                        >
                            Reset
                        </Button>
                        <div class="ml-auto flex items-center gap-2 text-sm text-emerald-800/80">
                            <span>{{ props.bills.from ?? 0 }}-{{ props.bills.to ?? 0 }}</span>
                            <span>of</span>
                            <span>{{ props.bills.total }}</span>
                        </div>
                    </div>
                </form>
            </section>

            <section class="flex-1">
                <div v-if="hasResults" class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="bill in props.bills.data"
                        :key="bill.id"
                        class="flex h-full flex-col gap-4 rounded-2xl border border-emerald-100/70 bg-white/90 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"
                    >
                        <header class="flex flex-col gap-1">
                            <div class="flex items-center justify-between gap-4">
                                <h2 class="text-lg font-semibold text-emerald-900">{{ bill.title }}</h2>
                                <span class="rounded-full px-3 py-1 text-xs font-medium capitalize" :class="statusBadgeClasses(bill.status)">
                                    {{ formatLabel(bill.status) }}
                                </span>
                            </div>
                            <p class="text-sm text-emerald-800/70">Bill {{ bill.bill_number }} • {{ formatLabel(bill.house) }}</p>
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
                                <span>Submissions: {{ bill.submissions_count }}</span>
                                <span v-if="bill.participation_end_date"> Participation closes {{ bill.participation_end_date }} </span>
                            </div>

                            <Link
                                :href="billRoutes.show({ bill: bill.id }).url"
                                class="text-sm font-semibold text-emerald-700 hover:text-emerald-900"
                            >
                                View details
                            </Link>
                        </footer>
                    </article>
                </div>

                <div
                    v-else
                    class="flex min-h-[200px] items-center justify-center rounded-2xl border border-dashed border-emerald-200 bg-white/90 p-10 text-center text-emerald-800/70"
                >
                    <div>
                        <p class="font-medium">No bills found</p>
                        <p class="mt-2 text-sm">Adjust your filters or check back later for newly published bills.</p>
                    </div>
                </div>
            </section>

            <nav v-if="hasResults && props.bills.links.length > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="link in props.bills.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded-full px-4 py-2 text-sm transition',
                        link.active ? 'bg-emerald-600 text-white shadow-sm' : 'text-emerald-700 hover:bg-emerald-50',
                        !link.url && 'pointer-events-none opacity-50',
                    ]"
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </nav>

            <!-- Infinite scroll trigger -->
            <div v-if="hasResults" ref="loadMoreRef" class="flex h-20 items-center justify-center">
                <div v-if="props.bills.links.find((link) => link.label.includes('Next'))" class="animate-pulse text-sm text-muted-foreground">
                    Loading more bills...
                </div>
            </div>
        </div>
    </PublicLayout>
</template>
