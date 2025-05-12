<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/vue3';
import {
    AlertTriangle,
    CalendarClock,
    CheckCircle2,
    ClipboardList,
    FileText,
    Layers,
    ShieldCheck,
    UserCheck,
    Users,
} from 'lucide-vue-next';
import { computed } from 'vue';
import * as bills from '@/routes/bills';
import * as sessions from '@/routes/sessions';
import * as submissions from '@/routes/submissions';

interface MetricsResource {
    total_bills: number;
    open_bills: number;
    needs_review: number;
}

interface RecentBillResource {
    id: number;
    title: string;
    bill_number: string;
    status: string;
    house: string;
    participation_end_date: string | null;
    creator?: {
        id: number;
        name: string;
    } | null;
}

interface RecentSubmissionResource {
    id: number;
    tracking_id: string;
    status: string;
    created_at: string;
    bill: {
        id: number;
        title: string;
        bill_number: string;
    } | null;
    user: {
        id: number;
        name: string;
    } | null;
}

interface Props {
    billMetrics: MetricsResource;
    submissionMetrics: Record<string, number>;
    submissionTypes: Record<string, number>;
    userMetrics: {
        pending_citizens: number;
        legislator_invites: number;
    };
    recentBills: RecentBillResource[];
    recentSubmissions: RecentSubmissionResource[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: '/dashboard',
    },
];

const cards = computed(() => [
    {
        title: 'Total bills',
        value: props.billMetrics.total_bills,
        icon: Layers,
        description: `${props.billMetrics.open_bills} currently open for participation`,
    },
    {
        title: 'Needs review',
        value: props.billMetrics.needs_review,
        icon: ClipboardList,
        description: 'Bills awaiting committee action',
    },
    {
        title: 'Active submissions',
        value: props.submissionMetrics.pending ?? 0,
        icon: ShieldCheck,
        description: `${props.submissionMetrics.reviewed ?? 0} reviewed so far`,
    },
]);

const statusBreakdown = computed(() => Object.entries(props.submissionMetrics ?? {}));

const typeBreakdown = computed(() => Object.entries(props.submissionTypes ?? {}));

const verificationAlerts = computed(() => [
    {
        label: 'Citizens awaiting verification',
        value: props.userMetrics.pending_citizens,
        icon: Users,
        description: 'Verify identities before publishing feedback',
    },
    {
        label: 'Legislators pending onboarding',
        value: props.userMetrics.legislator_invites,
        icon: UserCheck,
        description: 'Send follow-ups on account activation',
    },
]);

const dateFormatter = computed(() => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeStyle: 'short' }));

function formatDate(value: string | null | undefined): string {
    if (! value) {
        return '—';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '—' : dateFormatter.value.format(parsed);
}

function statusBadge(status: string): string {
    const normalised = status.toLowerCase();

    return (
        {
            draft: 'bg-slate-100 text-slate-700',
            open: 'bg-sky-50 text-sky-700',
            committee_review: 'bg-amber-50 text-amber-700',
            closed: 'bg-emerald-50 text-emerald-700',
        }[normalised] ?? 'bg-muted text-foreground'
    );
}

const operationalReminders = computed(() => [
    {
        key: 'review-assign',
        title: 'Assign reviewers to pending bills',
        description: `${props.billMetrics.needs_review} bills awaiting committee sign-off`,
        href: submissions.index().url,
    },
    {
        key: 'session-audit',
        title: 'Audit staff device sessions',
        description: 'Review logins and revoke any stale devices each Friday.',
        href: sessions.index().url,
    },
    {
        key: 'calendar-sync',
        title: 'Sync committee calendar',
        description: 'Confirm hearing dates before public deadlines close.',
        href: bills.index().url,
    },
]);

const dataIntegrityChecklist = [
    {
        key: 'duplicates',
        title: 'Check for duplicate submissions',
        description: 'Flag repeated tracking IDs before exporting reports.',
    },
    {
        key: 'attachments',
        title: 'Verify attachment formats',
        description: 'Ensure PDF minutes or annexes are legible prior to publication.',
    },
    {
        key: 'contact',
        title: 'Validate contact information',
        description: 'Reach out to pending citizen accounts for missing phone numbers.',
    },
];
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Clerk dashboard" />

        <div class="space-y-10">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-foreground">Administration overview</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Coordinate public participation, assign reviews, and keep submissions processing on schedule.
                    </p>
                </div>
                <Link :href="bills.create().url" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                    Publish new bill
                    <FileText class="ml-1 h-4 w-4" />
                </Link>
            </header>

            <section class="grid gap-4 md:grid-cols-3">
                <Card v-for="card in cards" :key="card.title" class="border-border/80">
                    <CardHeader class="flex flex-row items-start justify-between">
                        <div>
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ card.title }}</p>
                            <CardTitle class="text-3xl font-semibold text-foreground">{{ card.value }}</CardTitle>
                        </div>
                        <component :is="card.icon" class="h-5 w-5 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-sm text-muted-foreground">{{ card.description }}</p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.5fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Recent bills</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead class="bg-muted/60 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-3">Bill</th>
                                    <th class="px-6 py-3">House</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Deadline</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="bill in recentBills" :key="bill.id" class="hover:bg-muted/40">
                                    <td class="px-6 py-4">
                                        <Link :href="bills.show({ bill: bill.id }).url" class="font-medium text-foreground hover:underline">
                                            {{ bill.title }}
                                        </Link>
                                        <p class="text-xs text-muted-foreground">Bill No. {{ bill.bill_number }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ bill.house?.replace('_', ' ') ?? '—' }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', statusBadge(bill.status)]">
                                            {{ bill.status.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(bill.participation_end_date) }}</td>
                                </tr>
                                <tr v-if="!recentBills.length">
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                        Newly published bills will appear here once created.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Submission pipeline</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6">
                        <div>
                            <p class="mb-3 text-xs uppercase tracking-wide text-muted-foreground">By status</p>
                            <ul class="space-y-2 text-sm text-muted-foreground">
                                <li v-for="[status, total] in statusBreakdown" :key="status" class="flex items-center justify-between rounded-md bg-muted/50 px-3 py-2">
                                    <span class="font-medium capitalize text-foreground">{{ status.replace('_', ' ') }}</span>
                                    <span>{{ total }}</span>
                                </li>
                                <li v-if="!statusBreakdown.length" class="text-sm text-muted-foreground">
                                    Submission metrics will populate as feedback is received.
                                </li>
                            </ul>
                        </div>

                        <div>
                            <p class="mb-3 text-xs uppercase tracking-wide text-muted-foreground">By submission type</p>
                            <ul class="space-y-2 text-sm text-muted-foreground">
                                <li v-for="[type, total] in typeBreakdown" :key="type" class="flex items-center justify-between rounded-md bg-muted/40 px-3 py-2">
                                    <span class="font-medium capitalize text-foreground">{{ type.replace('_', ' ') }}</span>
                                    <span>{{ total }}</span>
                                </li>
                                <li v-if="!typeBreakdown.length" class="text-sm text-muted-foreground">
                                    Submission type analysis will be available once citizens indicate their preferred format.
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Account follow-ups</p>
                            <div v-for="alert in verificationAlerts" :key="alert.label" class="flex items-center justify-between rounded-md border border-border/60 px-3 py-3">
                                <div>
                                    <p class="flex items-center gap-2 text-sm font-medium text-foreground">
                                        <component :is="alert.icon" class="h-4 w-4 text-primary" />
                                        {{ alert.label }}
                                    </p>
                                    <p class="text-xs text-muted-foreground">{{ alert.description }}</p>
                                </div>
                                <span class="text-lg font-semibold text-foreground">{{ alert.value }}</span>
                            </div>
                        </div>

                        <div class="rounded-md bg-primary/5 p-3 text-xs text-primary">
                            Refresh reviewer assignments every Monday to keep workflows aligned with committee expectations.
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Operations playbook</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm text-muted-foreground">
                        <div v-for="item in operationalReminders" :key="item.key" class="rounded-lg border border-border/70 p-4">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <p class="font-medium text-foreground">{{ item.title }}</p>
                                    <p class="text-xs text-muted-foreground">{{ item.description }}</p>
                                </div>
                                <Link :href="item.href" class="inline-flex items-center text-xs font-medium text-primary hover:underline">
                                    Open
                                    <CalendarClock class="ml-1 h-3 w-3" />
                                </Link>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Data integrity checklist</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3 text-sm text-muted-foreground">
                        <ul class="space-y-3">
                            <li v-for="item in dataIntegrityChecklist" :key="item.key" class="flex items-start gap-3 rounded-lg bg-muted/40 p-3">
                                <CheckCircle2 class="mt-0.5 h-4 w-4 text-primary" />
                                <span>
                                    <span class="block font-medium text-foreground">{{ item.title }}</span>
                                    <span class="text-xs">{{ item.description }}</span>
                                </span>
                            </li>
                        </ul>
                        <div class="flex items-start gap-3 rounded-lg border border-amber-200 bg-amber-50/70 p-3 text-xs text-amber-800">
                            <AlertTriangle class="mt-0.5 h-4 w-4" />
                            <span>
                                Escalate anomalies immediately to the Secretariat channel to keep audit trails clean.
                            </span>
                        </div>
                    </CardContent>
                </Card>
            </section>

            <section>
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Recent submissions</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead class="bg-muted/60 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-3">Tracking ID</th>
                                    <th class="px-6 py-3">Bill</th>
                                    <th class="px-6 py-3">Citizen</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Received</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="submission in recentSubmissions" :key="submission.id" class="hover:bg-muted/40">
                                    <td class="px-6 py-4 font-mono text-xs">{{ submission.tracking_id }}</td>
                                    <td class="px-6 py-4">
                                        <Link :href="submissions.show({ submission: submission.id }).url" class="font-medium text-foreground hover:underline">
                                            {{ submission.bill?.title ?? 'Unknown bill' }}
                                        </Link>
                                        <p class="text-xs text-muted-foreground">Bill No. {{ submission.bill?.bill_number ?? '—' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ submission.user?.name ?? 'Anonymous' }}</td>
                                    <td class="px-6 py-4 capitalize text-muted-foreground">{{ submission.status.replace('_', ' ') }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(submission.created_at) }}</td>
                                </tr>
                                <tr v-if="!recentSubmissions.length">
                                    <td colspan="5" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                        No submissions yet. Encourage citizens to participate to populate this feed.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
