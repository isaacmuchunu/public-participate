<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import {
    ArrowLeft,
    Download,
    MapPin,
    NotebookPen,
    Sparkles,
    Trash2,
    Users,
} from 'lucide-vue-next';
import type { BreadcrumbItem } from '@/types';
import * as legislatorBillRoutes from '@/routes/legislator/bills';
import * as billHighlightRoutes from '@/routes/legislator/bills/highlights';
import * as highlightRoutes from '@/routes/legislator/highlights';
import * as submissionRoutes from '@/routes/submissions';

interface BillSummary {
    simplified_summary_en?: string | null;
    simplified_summary_sw?: string | null;
    key_clauses?: string[] | null;
    generation_method?: string | null;
    generated_at?: string | null;
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
    submission_stats: Record<string, number>;
    highlights_count?: number | null;
    summary?: BillSummary | null;
    updated_at: string | null;
}

interface SubmissionResource {
    id: number;
    tracking_id: string;
    submitter_name?: string | null;
    submitter_email?: string | null;
    submitter_phone?: string | null;
    submitter_county?: string | null;
    submission_type: string;
    status: string;
    content?: string | null;
    created_at: string | null;
}

interface SubmissionCollection {
    data: SubmissionResource[];
    links: { label: string; url: string | null; active: boolean }[];
    meta?: {
        from?: number | null;
        to?: number | null;
        total?: number | null;
    };
}

interface HighlightResource {
    id: number;
    title: string;
    clause_reference?: string | null;
    excerpt?: string | null;
    note?: string | null;
    highlighted_at?: string | null;
    submission?: {
        id: number;
        tracking_id: string;
        submission_type: string;
        submitter_name?: string | null;
        content?: string | null;
    } | null;
}

interface AggregationResource {
    byType: Record<string, number>;
    byStatus: Record<string, number>;
    byCounty: Record<string, number>;
}

interface Filters {
    status?: string | null;
    type?: string | null;
    county?: string | null;
}

interface AvailableFilters {
    status: string[];
    type: string[];
    counties: string[];
}

interface Props {
    bill: BillResource;
    submissions: SubmissionCollection;
    aggregation: AggregationResource;
    highlights: HighlightResource[];
    filters: Filters;
    availableFilters: AvailableFilters;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Dashboard', href: '/dashboard' },
    { title: 'Bill workspaces', href: legislatorBillRoutes.index().url },
    { title: props.bill.title, href: legislatorBillRoutes.show({ bill: props.bill.id }).url },
];

const filterForm = reactive({
    status: props.filters?.status ?? 'all',
    type: props.filters?.type ?? 'all',
    county: props.filters?.county ?? 'all',
});

const dateFormatter = computed(() =>
    new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }),
);

const shortDateFormatter = computed(() => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium' }));

function formatDate(value: string | null | undefined, withTime = false): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    if (Number.isNaN(parsed.getTime())) {
        return '—';
    }

    return withTime ? dateFormatter.value.format(parsed) : shortDateFormatter.value.format(parsed);
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

function submitFilters(): void {
    const query: Record<string, string> = {};

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.type && filterForm.type !== 'all') {
        query.type = filterForm.type;
    }

    if (filterForm.county && filterForm.county !== 'all') {
        query.county = filterForm.county;
    }

    router.get(
        legislatorBillRoutes.show.url({ bill: props.bill.id, query }),
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
    filterForm.type = 'all';
    filterForm.county = 'all';
    submitFilters();
}

const paginationLabel = (label: string) => label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');

const highlightDialogOpen = ref(false);

interface HighlightFormShape {
    title: string;
    clause_reference: string;
    excerpt: string;
    note: string;
    submission_id: number | null;
}

const highlightFormDefaults = (submission?: SubmissionResource): HighlightFormShape => ({
    title: submission?.submitter_name ? `Follow-up: ${submission.submitter_name}` : '',
    clause_reference: '',
    excerpt: submission?.content ? submission.content.slice(0, 500) : '',
    note: '',
    submission_id: submission ? submission.id : null,
});

const highlightForm = useForm<HighlightFormShape>(highlightFormDefaults());

function openHighlightDialog(submission?: SubmissionResource): void {
    const defaults = highlightFormDefaults(submission);
    highlightForm.defaults(defaults);
    highlightForm.reset();
    highlightForm.clearErrors();
    highlightDialogOpen.value = true;
}

watch(
    () => highlightDialogOpen.value,
    (isOpen) => {
        if (!isOpen) {
            highlightForm.defaults(highlightFormDefaults());
            highlightForm.reset();
            highlightForm.clearErrors();
        }
    },
);

function handleHighlightSubmit(): void {
    highlightForm
        .transform((data) => ({
            ...data,
            submission_id: data.submission_id ? Number(data.submission_id) : null,
        }))
        .post(billHighlightRoutes.store({ bill: props.bill.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                highlightDialogOpen.value = false;
            },
        });
}

function deleteHighlight(highlight: HighlightResource): void {
    if (!window.confirm('Remove this highlight?')) {
        return;
    }

    router.delete(highlightRoutes.destroy({ highlight: highlight.id }).url, {
        preserveScroll: true,
    });
}

const hasSubmissions = computed(() => props.submissions.data.length > 0);

const statusBreakdown = computed(() =>
    Object.entries(props.aggregation.byStatus ?? {})
        .map(([status, total]) => ({
            status,
            total,
            label: statusLabel(status),
        }))
        .sort((a, b) => b.total - a.total),
);

const typeBreakdown = computed(() =>
    Object.entries(props.aggregation.byType ?? {})
        .map(([type, total]) => ({
            type,
            total,
            label: type.replace(/_/g, ' '),
        }))
        .sort((a, b) => b.total - a.total),
);

const countyBreakdown = computed(() =>
    Object.entries(props.aggregation.byCounty ?? {})
        .map(([county, total]) => ({ county, total }))
        .sort((a, b) => b.total - a.total),
);

const summaryParagraphs = computed(() =>
    (props.bill.summary?.simplified_summary_en ?? '')
        .split('\n')
        .map((paragraph) => paragraph.trim())
        .filter((paragraph) => paragraph.length > 0),
);

function submissionSnippet(content: string | null | undefined): string {
    if (!content) {
        return 'No excerpt provided.';
    }

    if (content.length <= 160) {
        return content;
    }

    return `${content.slice(0, 157)}…`;
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head :title="`${props.bill.title} · Bill workspace`" />

        <div class="flex flex-1 flex-col gap-6 p-6">
            <div class="flex flex-wrap items-center gap-3">
                <Link
                    :href="legislatorBillRoutes.index().url"
                    class="inline-flex items-center gap-2 text-sm font-medium text-muted-foreground hover:text-primary"
                >
                    <ArrowLeft class="h-4 w-4" />
                    Back to bills
                </Link>
            </div>

            <section class="rounded-2xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                    <div class="space-y-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <h1 class="text-3xl font-semibold tracking-tight text-foreground">{{ props.bill.title }}</h1>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                {{ props.bill.bill_number }}
                            </span>
                        </div>
                        <p v-if="props.bill.description" class="max-w-3xl text-sm text-muted-foreground">
                            {{ props.bill.description }}
                        </p>
                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="statusBadge(props.bill.status)"
                            >
                                {{ statusLabel(props.bill.status) }}
                            </span>
                            <span class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium capitalize text-muted-foreground">
                                {{ props.bill.house.replace('_', ' ') }}
                            </span>
                            <span v-if="props.bill.committee" class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                Committee: {{ props.bill.committee }}
                            </span>
                            <span v-if="props.bill.sponsor" class="rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                Sponsor: {{ props.bill.sponsor }}
                            </span>
                        </div>
                        <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                            <span>
                                Participation window:
                                <strong class="text-foreground">{{ formatDate(props.bill.participation_start_date) }}</strong>
                                –
                                <strong class="text-foreground">{{ formatDate(props.bill.participation_end_date) }}</strong>
                            </span>
                            <span>
                                Last updated {{ formatDate(props.bill.updated_at, true) }}
                            </span>
                            <span>
                                Submissions received: {{ props.bill.submission_stats.total ?? 0 }}
                            </span>
                        </div>
                        <div v-if="props.bill.tags?.length" class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                            <span class="font-medium text-foreground">Tags:</span>
                            <span
                                v-for="tag in props.bill.tags"
                                :key="tag"
                                class="rounded-full bg-muted px-2 py-0.5"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-col gap-2">
                        <Link
                            :href="legislatorBillRoutes.report({ bill: props.bill.id }).url"
                            class="inline-flex items-center gap-2 text-sm font-medium text-primary hover:underline"
                        >
                            <Download class="h-4 w-4" />
                            Download participation report
                        </Link>
                        <Button variant="outline" class="justify-start" @click="openHighlightDialog()">
                            <Sparkles class="mr-2 h-4 w-4" />
                            Save new highlight
                        </Button>
                    </div>
                </div>
            </section>

            <section v-if="summaryParagraphs.length || props.bill.summary?.key_clauses?.length" class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <Card class="border-sidebar-border/60 shadow-sm dark:border-sidebar-border">
                    <CardHeader class="pb-4">
                        <CardTitle class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <NotebookPen class="h-5 w-5 text-primary" />
                            Summary for briefings
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-muted-foreground">
                        <p v-for="paragraph in summaryParagraphs" :key="paragraph">{{ paragraph }}</p>
                    </CardContent>
                </Card>

                <Card class="border-sidebar-border/60 shadow-sm dark:border-sidebar-border">
                    <CardHeader class="pb-4">
                        <CardTitle class="flex items-center gap-2 text-base font-semibold text-foreground">
                            <Sparkles class="h-5 w-5 text-primary" />
                            Key clauses flagged
                        </CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-muted-foreground">
                        <ul v-if="props.bill.summary?.key_clauses?.length" class="list-disc space-y-2 pl-5 marker:text-primary">
                            <li v-for="clause in props.bill.summary.key_clauses" :key="clause">
                                {{ clause }}
                            </li>
                        </ul>
                        <p v-else>No AI-generated clauses available yet.</p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]">
                <div class="rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <header class="flex flex-wrap items-center justify-between gap-3 border-b border-sidebar-border/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                        <h2 class="text-sm font-semibold text-foreground">Submission insights</h2>
                        <div class="flex gap-6 text-xs">
                            <span>
                                Pending
                                <strong class="ml-1 text-foreground">{{ props.bill.submission_stats.pending ?? 0 }}</strong>
                            </span>
                            <span>
                                Aggregated
                                <strong class="ml-1 text-foreground">{{ props.bill.submission_stats.aggregated ?? 0 }}</strong>
                            </span>
                            <span>
                                Total
                                <strong class="ml-1 text-foreground">{{ props.bill.submission_stats.total ?? 0 }}</strong>
                            </span>
                        </div>
                    </header>
                    <div class="grid gap-4 p-4 md:grid-cols-3">
                        <Card class="border-none bg-muted/40 shadow-none">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-xs font-medium text-muted-foreground">By status</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <p v-for="item in statusBreakdown" :key="item.status" class="flex items-center justify-between">
                                    <span class="capitalize">{{ item.label }}</span>
                                    <span class="font-medium text-foreground">{{ item.total }}</span>
                                </p>
                                <p v-if="!statusBreakdown.length" class="text-xs text-muted-foreground">No status data yet.</p>
                            </CardContent>
                        </Card>
                        <Card class="border-none bg-muted/40 shadow-none">
                            <CardHeader class="pb-2">
                                <CardTitle class="text-xs font-medium text-muted-foreground">By submission type</CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <p v-for="item in typeBreakdown" :key="item.type" class="flex items-center justify-between">
                                    <span class="capitalize">{{ item.label }}</span>
                                    <span class="font-medium text-foreground">{{ item.total }}</span>
                                </p>
                                <p v-if="!typeBreakdown.length" class="text-xs text-muted-foreground">No submission types logged.</p>
                            </CardContent>
                        </Card>
                        <Card class="border-none bg-muted/40 shadow-none">
                            <CardHeader class="pb-2">
                                <CardTitle class="flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                    <MapPin class="h-3.5 w-3.5" />
                                    Top counties
                                </CardTitle>
                            </CardHeader>
                            <CardContent class="space-y-2 text-sm">
                                <p v-for="item in countyBreakdown" :key="item.county" class="flex items-center justify-between">
                                    <span>{{ item.county }}</span>
                                    <span class="font-medium text-foreground">{{ item.total }}</span>
                                </p>
                                <p v-if="!countyBreakdown.length" class="text-xs text-muted-foreground">County data not available.</p>
                            </CardContent>
                        </Card>
                    </div>
                </div>

                <div class="rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <header class="flex items-center justify-between gap-3 border-b border-sidebar-border/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                        <h2 class="text-sm font-semibold text-foreground">Saved highlights</h2>
                        <Button variant="ghost" size="sm" class="h-8 px-3" @click="openHighlightDialog()">
                            <Sparkles class="mr-2 h-4 w-4" />
                            New highlight
                        </Button>
                    </header>
                    <div class="space-y-3 p-4">
                        <article
                            v-for="highlight in props.highlights"
                            :key="highlight.id"
                            class="rounded-lg border border-sidebar-border/60 p-4 text-sm shadow-sm dark:border-sidebar-border"
                        >
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <h3 class="text-sm font-semibold text-foreground">{{ highlight.title }}</h3>
                                    <p v-if="highlight.clause_reference" class="mt-1 text-xs uppercase tracking-wide text-muted-foreground">
                                        {{ highlight.clause_reference }}
                                    </p>
                                </div>
                                <button class="text-muted-foreground hover:text-destructive" type="button" @click="deleteHighlight(highlight)">
                                    <Trash2 class="h-4 w-4" />
                                </button>
                            </div>
                            <p v-if="highlight.excerpt" class="mt-3 text-muted-foreground">{{ highlight.excerpt }}</p>
                            <p v-if="highlight.note" class="mt-2 text-xs text-foreground">{{ highlight.note }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span>
                                    Saved {{ formatDate(highlight.highlighted_at ?? null, true) }}
                                </span>
                                <span v-if="highlight.submission">
                                    Linked submission:
                                    <Link
                                        :href="submissionRoutes.show({ submission: highlight.submission.id }).url"
                                        class="font-medium text-primary"
                                    >
                                        {{ highlight.submission.tracking_id }}
                                    </Link>
                                </span>
                            </div>
                        </article>

                        <p v-if="!props.highlights.length" class="text-xs text-muted-foreground">
                            You have not added any highlights yet. Capture critical clauses or citizen stories to revisit during committee deliberations.
                        </p>
                    </div>
                </div>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <header class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold text-foreground">Citizen submissions</h2>
                        <p class="text-sm text-muted-foreground">
                            Filter participation by status, submission type, or county to curate feedback for committee action.
                        </p>
                    </div>

                    <form class="grid gap-3 md:grid-cols-3 md:items-end" @submit.prevent="submitFilters">
                        <div class="grid gap-1">
                            <Label for="submission-status">Status</Label>
                            <select
                                id="submission-status"
                                v-model="filterForm.status"
                                class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="all">All statuses</option>
                                <option v-for="status in props.availableFilters.status" :key="status" :value="status">
                                    {{ statusLabel(status) }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-1">
                            <Label for="submission-type">Type</Label>
                            <select
                                id="submission-type"
                                v-model="filterForm.type"
                                class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="all">All types</option>
                                <option v-for="type in props.availableFilters.type" :key="type" :value="type">
                                    {{ type.replace(/_/g, ' ') }}
                                </option>
                            </select>
                        </div>

                        <div class="grid gap-1">
                            <Label for="submission-county">County</Label>
                            <select
                                id="submission-county"
                                v-model="filterForm.county"
                                class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="all">All counties</option>
                                <option v-for="county in props.availableFilters.counties" :key="county" :value="county">
                                    {{ county }}
                                </option>
                            </select>
                        </div>

                        <div class="col-span-full flex items-center justify-end gap-2">
                            <Button type="submit">Apply filters</Button>
                            <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                        </div>
                    </form>
                </header>

                <div v-if="hasSubmissions" class="mt-4 overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/60 text-sm dark:divide-sidebar-border">
                        <thead class="text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">Tracking ID</th>
                                <th scope="col" class="px-4 py-3 font-medium">Submitter</th>
                                <th scope="col" class="px-4 py-3 font-medium">Type</th>
                                <th scope="col" class="px-4 py-3 font-medium">Status</th>
                                <th scope="col" class="px-4 py-3 font-medium">Submitted</th>
                                <th scope="col" class="px-4 py-3 font-medium">Excerpt</th>
                                <th scope="col" class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/40 dark:divide-sidebar-border">
                            <tr v-for="submission in props.submissions.data" :key="submission.id" class="align-top">
                                <td class="px-4 py-3 font-medium text-foreground">
                                    <Link :href="submissionRoutes.show({ submission: submission.id }).url" class="text-primary hover:underline">
                                        {{ submission.tracking_id }}
                                    </Link>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    <div class="space-y-1">
                                        <p class="text-sm text-foreground">{{ submission.submitter_name ?? 'Anonymous citizen' }}</p>
                                        <p v-if="submission.submitter_email">{{ submission.submitter_email }}</p>
                                        <p v-if="submission.submitter_county">County: {{ submission.submitter_county }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-xs capitalize text-muted-foreground">
                                    {{ submission.submission_type.replace(/_/g, ' ') }}
                                </td>
                                <td class="px-4 py-3">
                                    <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium" :class="statusBadge(submission.status)">
                                        {{ statusLabel(submission.status) }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ formatDate(submission.created_at, true) }}
                                </td>
                                <td class="px-4 py-3 text-xs text-muted-foreground">
                                    {{ submissionSnippet(submission.content ?? null) }}
                                </td>
                                <td class="px-4 py-3 text-right text-xs">
                                    <Button variant="ghost" size="sm" class="h-8 px-3" @click="openHighlightDialog(submission)">
                                        <Sparkles class="mr-2 h-4 w-4" />
                                        Highlight
                                    </Button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div v-else class="mt-6 flex flex-col items-center justify-center gap-2 px-6 py-10 text-center text-muted-foreground">
                    <Users class="h-10 w-10 text-muted-foreground/60" />
                    <p class="text-sm">No submissions match these filters yet.</p>
                    <p class="text-xs">Adjust the filters or encourage constituents to send feedback before the deadline.</p>
                </div>

                <nav v-if="hasSubmissions && props.submissions.links.length > 1" class="mt-6 flex items-center justify-center gap-2">
                    <Link
                        v-for="link in props.submissions.links"
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
            </section>
        </div>

        <Dialog v-model:open="highlightDialogOpen">
            <DialogContent class="sm:max-w-2xl">
                <DialogHeader>
                    <DialogTitle>Save legislator highlight</DialogTitle>
                    <DialogDescription>
                        Capture this insight for quick reference during committee briefings or follow-up sessions.
                    </DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="handleHighlightSubmit">
                    <div class="grid gap-2">
                        <Label for="highlight-title">Title</Label>
                        <Input id="highlight-title" v-model="highlightForm.title" type="text" placeholder="Citizen concern on clause 12" />
                        <InputError :message="highlightForm.errors.title" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="highlight-clause">Clause reference (optional)</Label>
                        <Input id="highlight-clause" v-model="highlightForm.clause_reference" type="text" placeholder="Clause 12(1)(b)" />
                        <InputError :message="highlightForm.errors.clause_reference" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="highlight-excerpt">Excerpt (optional)</Label>
                        <textarea
                            id="highlight-excerpt"
                            v-model="highlightForm.excerpt"
                            rows="4"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Copy the relevant section from the submission or bill for context."
                        ></textarea>
                        <InputError :message="highlightForm.errors.excerpt" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="highlight-note">Personal note (optional)</Label>
                        <textarea
                            id="highlight-note"
                            v-model="highlightForm.note"
                            rows="3"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Add instructions for committee or staff follow-up."
                        ></textarea>
                        <InputError :message="highlightForm.errors.note" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="highlight-submission">Link to submission (optional)</Label>
                        <select
                            id="highlight-submission"
                            v-model="highlightForm.submission_id"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option :value="null">No linked submission</option>
                            <option v-for="submission in props.submissions.data.slice(0, 20)" :key="submission.id" :value="submission.id">
                                {{ submission.tracking_id }} · {{ submission.submitter_name ?? 'Citizen' }}
                            </option>
                        </select>
                        <InputError :message="highlightForm.errors.submission_id" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="highlightDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="highlightForm.processing">
                            <Sparkles class="mr-2 h-4 w-4" />
                            Save highlight
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
