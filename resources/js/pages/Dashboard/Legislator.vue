<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    ArrowUpRight,
    BarChart3,
    CalendarClock,
    CalendarDays,
    FileText,
    MessageSquare,
    PieChart,
    ShieldCheck,
    Sparkles,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import * as bills from '@/routes/bills';
import * as sessions from '@/routes/sessions';

interface BillResource {
    id: number;
    title: string;
    number: string;
    house: string;
    participation_end_date: string | null;
}

interface BillMetrics {
    total: number;
    pending: number;
    reviewed: number;
}

interface SummaryResource {
    bill: BillResource;
    metrics: BillMetrics;
    aiSummary: {
        headline: string;
        body: string;
    };
}

interface Props {
    house: string | null;
    topBills: Array<{
        id: number;
        title: string;
        bill_number: string;
        participation_end_date: string | null;
        house: string;
        submissions_count?: number;
    }>;
    summaries: SummaryResource[];
    submissionBreakdown: Record<string, number>;
    clauseHighlights: Array<{
        bill_id: number;
        bill_title: string;
        clause: string;
        house: string;
        deadline: string | null;
    }>;
    reportLinks: Array<{
        bill_id: number;
        title: string;
        url: string;
    }>;
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const formatter = computed(() => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium' }));

function kpi(label: string, value: number, accent: string) {
    return { label, value, accent };
}

const kpis = computed(() => {
    const totalFeedback = props.topBills.reduce((total, bill) => total + (bill.submissions_count ?? 0), 0);

    return [
        kpi('Bills under your house', props.topBills.length, 'text-primary'),
        kpi('Total feedback received', totalFeedback, 'text-emerald-500'),
        kpi('Average per bill', props.topBills.length ? Math.round(totalFeedback / props.topBills.length) : 0, 'text-sky-500'),
    ];
});

function formatDate(value: string | null | undefined) {
    if (! value) {
        return '—';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '—' : formatter.value.format(parsed);
}

const statusLabels: Record<string, string> = {
    pending: 'Pending review',
    reviewed: 'Reviewed',
    submitted: 'Submitted',
    rejected: 'Rejected',
};

const breakdownItems = computed(() =>
    Object.entries(props.submissionBreakdown ?? {})
        .map(([status, total]) => ({
            status,
            total,
            label: statusLabels[status] ?? status.replace(/_/g, ' '),
        }))
        .sort((a, b) => b.total - a.total)
);

const totalResponses = computed(() => breakdownItems.value.reduce((carry, item) => carry + item.total, 0));

const engagementRoadmap = computed(() => {
    const items = props.topBills.slice(0, 4).map((bill) => ({
        key: `bill-${bill.id}`,
        title: bill.title,
        deadline: formatDate(bill.participation_end_date),
        house: bill.house?.replace('_', ' ') ?? '—',
        href: bills.show({ bill: bill.id }).url,
    }));

    if (! items.length) {
        items.push(
            {
                key: 'briefings',
                title: 'Set up constituency briefings',
                deadline: 'Align with upcoming plenary session',
                house: props.house?.replace('_', ' ') ?? 'national assembly',
                href: bills.index().url,
            },
            {
                key: 'sessions',
                title: 'Review device sessions',
                deadline: 'Ensure only authorised staff retain access',
                house: 'Security',
                href: sessions.index().url,
            }
        );
    }

    return items;
});

const stakeholderSignals = computed(() => {
    const highlights = props.clauseHighlights.slice(0, 4).map((highlight) => ({
        key: `${highlight.bill_id}-${highlight.clause}`,
        title: highlight.bill_title,
        clause: highlight.clause,
        deadline: formatDate(highlight.deadline),
        href: bills.show({ bill: highlight.bill_id }).url,
    }));

    if (! highlights.length) {
        highlights.push({
            key: 'reports',
            title: 'Collect committee priorities',
            clause: 'Document concerns raised during ward consultations this week.',
            deadline: 'Align with committee calendar',
            href: bills.index().url,
        });
    }

    return highlights;
});

const actionItems = [
    {
        title: 'Share top themes with committee secretariat',
        description: 'Summarise citizen sentiment for deliberations in the next sitting.',
        icon: MessageSquare,
    },
    {
        title: 'Confirm stakeholder outreach schedule',
        description: 'Coordinate with county offices for targeted hearings before deadlines lapse.',
        icon: CalendarDays,
    },
    {
        title: 'Audit device access list',
        description: 'Remove accounts for transitioning staff to protect bill workspaces.',
        icon: ShieldCheck,
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Legislator dashboard" />

        <div class="space-y-10">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-foreground">Legislative insights</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Monitor participation data for the {{ props.house?.replace('_', ' ') ?? 'national assembly' }} and prioritise bills requiring attention.
                    </p>
                </div>

                <Link :href="bills.index().url" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                    View full bill register
                    <ArrowUpRight class="ml-1 h-4 w-4" />
                </Link>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <Card v-for="item in kpis" :key="item.label" class="border-border/80">
                    <CardHeader class="space-y-1">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ item.label }}</p>
                        <CardTitle class="text-3xl font-semibold" :class="item.accent">{{ item.value }}</CardTitle>
                    </CardHeader>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.7fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Clause highlights from citizens</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="highlight in props.clauseHighlights"
                            :key="`${highlight.bill_id}-${highlight.clause}`"
                            class="rounded-lg border border-border/70 p-4"
                        >
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-2 text-xs uppercase tracking-wide text-muted-foreground">
                                <span class="inline-flex items-center gap-2 font-semibold text-primary">
                                    <Sparkles class="h-4 w-4" />
                                    {{ highlight.bill_title }}
                                </span>
                                <span>{{ highlight.house?.replace('_', ' ') ?? '—' }}</span>
                            </div>
                            <p class="text-sm text-muted-foreground">{{ highlight.clause }}</p>
                            <div class="mt-3 flex items-center justify-between text-xs text-muted-foreground">
                                <span>
                                    Deadline: <span class="font-medium text-foreground">{{ formatDate(highlight.deadline) }}</span>
                                </span>
                                <Link :href="bills.show({ bill: highlight.bill_id }).url" class="inline-flex items-center font-medium text-primary hover:underline">
                                    View bill
                                    <ArrowUpRight class="ml-1 h-3 w-3" />
                                </Link>
                            </div>
                        </div>
                        <p v-if="!props.clauseHighlights.length" class="text-sm text-muted-foreground">Clause-specific analysis will appear here as soon as submissions are processed.</p>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Submission snapshot</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-muted-foreground">
                        <div class="space-y-3">
                            <div class="flex items-center justify-between text-xs uppercase tracking-wide text-muted-foreground">
                                <span class="inline-flex items-center gap-2 text-foreground">
                                    <PieChart class="h-4 w-4 text-primary" />
                                    Status distribution
                                </span>
                                <span class="font-semibold text-foreground">{{ totalResponses }} total</span>
                            </div>
                            <ul class="space-y-2">
                                <li v-for="item in breakdownItems" :key="item.status" class="flex items-center justify-between rounded-md bg-muted/60 px-3 py-2">
                                    <span class="font-medium text-foreground">{{ item.label }}</span>
                                    <span class="text-xs text-muted-foreground">{{ item.total }}</span>
                                </li>
                            </ul>
                            <p v-if="!breakdownItems.length">No submissions have been recorded for your active bills yet.</p>
                        </div>

                        <div class="space-y-3">
                            <div class="flex items-center gap-2 text-xs uppercase tracking-wide text-muted-foreground">
                                <FileText class="h-4 w-4 text-primary" />
                                Quick reports
                            </div>
                            <ul class="space-y-2">
                                <li v-for="report in props.reportLinks" :key="report.bill_id">
                                    <Link :href="report.url" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                                        {{ report.title }}
                                        <ArrowUpRight class="ml-1 h-3 w-3" />
                                    </Link>
                                </li>
                            </ul>
                            <p v-if="!props.reportLinks.length" class="text-sm">Report links will appear when bills in your house gather feedback.</p>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Engagement roadmap</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-muted-foreground">
                        <div v-for="item in engagementRoadmap" :key="item.key" class="rounded-lg border border-border/70 p-4">
                            <div class="flex items-center justify-between gap-4">
                                <div>
                                    <p class="font-medium text-foreground">{{ item.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ item.house }}</p>
                                </div>
                                <Link :href="item.href" class="inline-flex items-center text-xs font-medium text-primary hover:underline">
                                    Open brief
                                    <ArrowUpRight class="ml-1 h-3 w-3" />
                                </Link>
                            </div>
                            <p class="mt-2 text-xs text-primary">Deadline: {{ item.deadline }}</p>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Stakeholder signals</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-muted-foreground">
                        <div v-for="signal in stakeholderSignals" :key="signal.key" class="rounded-lg bg-muted/50 p-4">
                            <div class="mb-2 flex items-center justify-between gap-4">
                                <span class="inline-flex items-center gap-2 text-xs uppercase tracking-wide text-primary">
                                    <Users class="h-4 w-4" />
                                    {{ signal.title }}
                                </span>
                                <span class="text-xs text-muted-foreground">Due {{ signal.deadline }}</span>
                            </div>
                            <p>{{ signal.clause }}</p>
                            <Link :href="signal.href" class="mt-3 inline-flex items-center text-xs font-medium text-primary hover:underline">
                                View clause
                                <ArrowUpRight class="ml-1 h-3 w-3" />
                            </Link>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.7fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Bills receiving the most public input</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="bill in props.summaries" :key="bill.bill.id" class="rounded-lg border border-border/70 p-4">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div>
                                    <p class="text-base font-medium text-foreground">{{ bill.bill.title }}</p>
                                    <p class="text-xs text-muted-foreground">
                                        Bill No. {{ bill.bill.number }} · Deadline {{ formatDate(bill.bill.participation_end_date) }}
                                    </p>
                                </div>
                                <Link :href="bills.show({ bill: bill.bill.id }).url" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                                    View bill
                                    <ArrowUpRight class="ml-1 h-4 w-4" />
                                </Link>
                            </div>

                            <div class="mt-4 grid gap-3 rounded-md bg-muted/50 p-4 sm:grid-cols-3">
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <BarChart3 class="h-4 w-4 text-primary" />
                                    <span class="font-semibold text-foreground">{{ bill.metrics.total }}</span>
                                    Total responses
                                </div>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <MessageSquare class="h-4 w-4 text-amber-500" />
                                    <span class="font-semibold text-foreground">{{ bill.metrics.pending }}</span>
                                    Pending review
                                </div>
                                <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                    <CalendarClock class="h-4 w-4 text-emerald-500" />
                                    <span class="font-semibold text-foreground">{{ bill.metrics.reviewed }}</span>
                                    Reviewed
                                </div>
                            </div>

                            <div class="mt-4 space-y-1 rounded-md bg-primary/5 p-4 text-sm">
                                <p class="text-xs font-semibold uppercase tracking-wide text-primary">AI summary</p>
                                <p class="font-medium text-foreground">{{ bill.aiSummary.headline }}</p>
                                <p class="text-muted-foreground">{{ bill.aiSummary.body }}</p>
                            </div>
                        </div>

                        <p v-if="!props.summaries.length" class="text-sm text-muted-foreground">
                            Participation data will appear once bills in your house begin receiving submissions.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Engagement action centre</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-muted-foreground">
                        <ul class="space-y-3">
                            <li v-for="task in actionItems" :key="task.title" class="flex items-start gap-2 rounded-lg bg-muted/40 p-3">
                                <component :is="task.icon" class="mt-0.5 h-4 w-4 text-primary" />
                                <span>
                                    <span class="block font-medium text-foreground">{{ task.title }}</span>
                                    <span class="text-xs">{{ task.description }}</span>
                                </span>
                            </li>
                        </ul>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
