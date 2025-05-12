<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import {
    BarChart3,
    CalendarClock,
    FileText,
    Layers,
    Search,
    Sparkles,
} from 'lucide-vue-next';
import * as legislatorBillRoutes from '@/routes/legislator/bills';

interface SubmissionStats {
    total?: number;
    pending?: number;
    reviewed?: number;
    aggregated?: number;
}

interface BillResource {
    id: number;
    title: string;
    bill_number: string;
    description?: string | null;
    committee?: string | null;
    sponsor?: string | null;
    status: string;
    house: string;
    type: string | null;
    participation_start_date: string | null;
    participation_end_date: string | null;
    is_open_for_participation?: boolean | null;
    tags?: string[] | null;
    submission_stats: SubmissionStats;
    highlights_count?: number | null;
    updated_at: string | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface BillsMeta {
    from?: number | null;
    to?: number | null;
    total?: number | null;
}

interface Props {
    bills: {
        data: BillResource[];
        links: PaginationLink[];
        meta?: BillsMeta;
    };
    filters: {
        status?: string | null;
        search?: string | null;
    };
    metrics: {
        open: number;
        closingSoon: number;
        recentlyClosed: number;
        highlights: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bill workspaces', href: legislatorBillRoutes.index().url },
];

const filterForm = reactive({
    status: props.filters?.status ?? 'all',
    search: props.filters?.search ?? '',
});

const cards = computed(() => [
    {
        key: 'open',
        title: 'Open for participation',
        value: props.metrics.open ?? 0,
        icon: Layers,
        helper: 'Including cross-house bills',
    },
    {
        key: 'closingSoon',
        title: 'Closing within 7 days',
        value: props.metrics.closingSoon ?? 0,
        icon: CalendarClock,
        helper: 'Prioritise follow-ups',
    },
    {
        key: 'recentlyClosed',
        title: 'Recently closed',
        value: props.metrics.recentlyClosed ?? 0,
        icon: FileText,
        helper: 'Awaiting committee wrap-up',
    },
    {
        key: 'highlights',
        title: 'Personal highlights',
        value: props.metrics.highlights ?? 0,
        icon: Sparkles,
        helper: 'Saved clauses across bills',
    },
]);

const hasBills = computed(() => props.bills.data.length > 0);

const dateFormatter = computed(() =>
    new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
    }),
);

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '—' : dateFormatter.value.format(parsed);
}

function statusBadge(status: string): string {
    switch (status) {
        case 'open_for_participation':
            return 'bg-emerald-500/10 text-emerald-500';
        case 'committee_review':
            return 'bg-amber-500/10 text-amber-500';
        case 'draft':
            return 'bg-slate-500/10 text-slate-500';
        case 'closed':
            return 'bg-rose-500/10 text-rose-500';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

function summaryRange(meta?: BillsMeta | null) {
    if (!meta) {
        return null;
    }

    const from = meta.from ?? (hasBills.value ? 1 : 0);
    const to = meta.to ?? props.bills.data.length;
    const total = meta.total ?? props.bills.data.length;

    return { from, to, total };
}

const paginationLabel = (label: string) => label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');

function submitFilters(): void {
    const query: Record<string, string> = {};

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.search) {
        query.search = filterForm.search;
    }

    router.get(
        legislatorBillRoutes.index.url({ query }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    filterForm.status = 'all';
    filterForm.search = '';
    submitFilters();
}

function submissionSummary(stats: SubmissionStats): string {
    const total = stats.total ?? 0;
    const pending = stats.pending ?? 0;
    const aggregated = stats.aggregated ?? 0;

    if (!total) {
        return 'No submissions yet';
    }

    if (pending === total) {
        return `${pending} awaiting review`;
    }

    if (aggregated) {
        return `${aggregated} aggregated of ${total}`;
    }

    return `${pending} pending of ${total}`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Bills" />

        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-2">
                <h1 class="text-3xl font-semibold tracking-tight text-foreground">Bill workspaces</h1>
                <p class="text-sm text-muted-foreground">
                    Track progress on public participation, monitor sentiment, and capture highlights for committee briefs.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="card in cards" :key="card.key" class="border-sidebar-border/60 shadow-sm dark:border-sidebar-border">
                    <CardHeader class="flex flex-row items-start justify-between gap-3 pb-3">
                        <div class="space-y-1">
                            <CardTitle class="text-sm font-medium text-muted-foreground">{{ card.title }}</CardTitle>
                            <p class="text-3xl font-semibold text-foreground">{{ card.value }}</p>
                        </div>
                        <component :is="card.icon" class="h-5 w-5 text-primary" />
                    </CardHeader>
                    <CardContent class="pt-0 text-xs text-muted-foreground">
                        {{ card.helper }}
                    </CardContent>
                </Card>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <form class="grid gap-4 md:grid-cols-[minmax(0,320px)_minmax(0,1fr)_auto] md:items-end" @submit.prevent="submitFilters">
                    <div class="grid gap-2">
                        <Label for="filter-status">Status</Label>
                        <select
                            id="filter-status"
                            v-model="filterForm.status"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All bills</option>
                            <option value="open">Open for participation</option>
                            <option value="draft">Draft</option>
                            <option value="closed">Closed</option>
                        </select>
                    </div>

                    <div class="grid gap-2 md:col-span-1">
                        <Label for="filter-search">Search</Label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="filter-search"
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Search by title or bill number"
                                class="pl-9"
                            />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 md:justify-end">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                <header class="flex items-center justify-between gap-3 border-b border-sidebar-border/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    <p v-if="summaryRange(props.bills.meta)" class="text-xs uppercase tracking-wide">
                        Showing
                        {{ summaryRange(props.bills.meta)?.from }} -
                        {{ summaryRange(props.bills.meta)?.to }}
                        of
                        {{ summaryRange(props.bills.meta)?.total }}
                        bills
                    </p>
                    <p v-else class="text-xs uppercase tracking-wide">Bill overview</p>
                </header>

                <div v-if="hasBills" class="divide-y divide-sidebar-border/60 dark:divide-sidebar-border">
                    <article
                        v-for="bill in props.bills.data"
                        :key="bill.id"
                        class="flex flex-col gap-4 px-4 py-5 md:flex-row md:items-start md:justify-between"
                    >
                        <div class="space-y-3">
                            <div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-lg font-semibold text-foreground">{{ bill.title }}</h2>
                                    <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                        {{ bill.bill_number }}
                                    </span>
                                </div>
                                <p v-if="bill.description" class="mt-1 line-clamp-2 text-sm text-muted-foreground">
                                    {{ bill.description }}
                                </p>
                            </div>

                            <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                <span
                                    class="inline-flex items-center gap-2 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="statusBadge(bill.status)"
                                >
                                    {{ statusLabel(bill.status) }}
                                </span>
                                <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium capitalize text-muted-foreground">
                                    {{ bill.house.replace('_', ' ') }}
                                </span>
                                <span v-if="bill.committee" class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                    Committee: {{ bill.committee }}
                                </span>
                                <span v-if="bill.sponsor" class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                    Sponsor: {{ bill.sponsor }}
                                </span>
                            </div>

                            <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span>Start: {{ formatDate(bill.participation_start_date) }}</span>
                                <span>Closes: {{ formatDate(bill.participation_end_date) }}</span>
                                <span>
                                    Highlights saved:
                                    <strong class="text-foreground">{{ bill.highlights_count ?? 0 }}</strong>
                                </span>
                                <span>
                                    Submissions: {{ bill.submission_stats.total ?? 0 }} ·
                                    {{ submissionSummary(bill.submission_stats) }}
                                </span>
                            </div>
                        </div>

                        <div class="flex flex-col gap-2 md:items-end">
                            <Link :href="legislatorBillRoutes.show({ bill: bill.id }).url" class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline">
                                View workspace
                                <BarChart3 class="h-4 w-4" />
                            </Link>
                            <Link
                                :href="legislatorBillRoutes.report({ bill: bill.id }).url"
                                class="inline-flex items-center gap-2 text-xs text-muted-foreground hover:text-primary"
                            >
                                Download participation report
                                <FileText class="h-3.5 w-3.5" />
                            </Link>
                        </div>
                    </article>
                </div>

                <div v-else class="flex flex-col items-center justify-center gap-2 px-6 py-16 text-center text-muted-foreground">
                    <p class="text-sm">No bills match these filters.</p>
                    <p class="text-xs">Adjust the search parameters or review cross-house drafts.</p>
                </div>
            </section>

            <nav v-if="hasBills && props.bills.links.length > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="link in props.bills.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded-md px-3 py-2 text-sm transition',
                        link.active
                            ? 'bg-primary text-primary-foreground'
                            : 'text-muted-foreground hover:bg-muted/60 hover:text-foreground',
                        !link.url && 'pointer-events-none opacity-50',
                    ]"
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </nav>
        </div>
    </AppLayout>
</template>
