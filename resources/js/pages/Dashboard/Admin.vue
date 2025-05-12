<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Dialog, DialogContent, DialogDescription, DialogFooter, DialogHeader, DialogTitle, DialogTrigger } from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    Activity,
    AlertCircle,
    ArrowUpRight,
    BarChart3,
    BellRing,
    ClipboardList,
    FilePieChart,
    Loader2,
    Megaphone,
    ShieldCheck,
    UserPlus,
    Users,
    X,
} from 'lucide-vue-next';
import admin from '@/routes/admin';
import * as bills from '@/routes/bills';
import * as submissions from '@/routes/submissions';
import clerk from '@/routes/clerk';

interface MetricsResource {
    users: {
        total: number;
        byRole: Record<string, number>;
        newThisWeek: number;
        pendingInvitations: number;
    };
    bills: {
        total: number;
        open: number;
        drafts: number;
        underReview: number;
    };
    submissions: {
        total: number;
        pending: number;
        reviewed: number;
        escalated: number;
    };
    sessions: {
        active: number;
    };
}

interface DailySubmissionPoint {
    date: string;
    total: number;
}

interface RecentUserResource {
    id: number;
    name: string;
    email: string;
    role: string;
    created_at: string;
    is_verified: boolean;
    last_active_at: string | null;
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
        email: string;
    } | null;
}

interface RecentSessionResource {
    id: number;
    device: string | null;
    ip_address: string | null;
    last_activity_at: string | null;
    user: {
        id: number;
        name: string;
        email: string;
        role: string;
    } | null;
}

interface AlertResource {
    id: number;
    title: string;
    message: string;
    severity: 'info' | 'warning' | 'critical';
    href?: string;
    published_at?: string | null;
}

interface ShortcutResource {
    key: string;
    title: string;
    description: string;
    href: string;
}

interface AdminResourceConfig {
    legislative_houses: string[];
    default_invitation_message: string;
    alert_severities: Array<'info' | 'warning' | 'critical'>;
}

interface Props {
    metrics: MetricsResource;
    dailySubmissions: DailySubmissionPoint[];
    recentUsers: RecentUserResource[];
    recentBills: RecentBillResource[];
    recentSubmissions: RecentSubmissionResource[];
    recentSessions: RecentSessionResource[];
    systemAlerts: AlertResource[];
    managementShortcuts: ShortcutResource[];
    adminResources: AdminResourceConfig;
}

const props = defineProps<Props>();

const inviteDialogOpen = ref(false);
const alertDialogOpen = ref(false);

const adminResources = computed(() => props.adminResources);
const severityOptions = computed(() => adminResources.value.alert_severities ?? []);
const houseOptions = computed(() => adminResources.value.legislative_houses ?? []);

const alertFormDefaults = () => ({
    title: '',
    message: '',
    severity: severityOptions.value[0] ?? 'info',
    action_url: '',
    expires_at: '',
});

const alertForm = useForm(alertFormDefaults());

const invitationFormDefaults = () => ({
    name: '',
    email: '',
    phone: '',
    legislative_house: houseOptions.value[0] ?? 'national_assembly',
    county: '',
    constituency: '',
    invitation_message: adminResources.value.default_invitation_message ?? '',
    expires_in_days: 7,
});

const invitationForm = useForm(invitationFormDefaults());

const dismissingAlertIds = ref<number[]>([]);

watch(alertDialogOpen, (isOpen) => {
    if (! isOpen) {
        alertForm.defaults(alertFormDefaults());
        alertForm.reset();
        alertForm.clearErrors();
    }
});

watch(inviteDialogOpen, (isOpen) => {
    if (! isOpen) {
        invitationForm.defaults(invitationFormDefaults());
        invitationForm.reset();
        invitationForm.clearErrors();
    }
});

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Administration',
        href: '/dashboard',
    },
];

const roleLabels: Record<string, string> = {
    citizen: 'Citizens',
    clerk: 'Clerks',
    admin: 'Administrators',
    mp: 'MPs',
    senator: 'Senators',
};

const metricCards = computed(() => [
    {
        title: 'Total users',
        value: props.metrics.users.total,
        description: `+${props.metrics.users.newThisWeek} joined this week`,
        icon: Users,
    },
    {
        title: 'Active sessions',
        value: props.metrics.sessions.active,
        description: 'Tracked within the last 12 hours',
        icon: ShieldCheck,
    },
    {
        title: 'Open consultations',
        value: props.metrics.bills.open,
        description: `${props.metrics.bills.total} bills across all statuses`,
        icon: ClipboardList,
    },
    {
        title: 'Submissions received',
        value: props.metrics.submissions.total,
        description: `${props.metrics.submissions.pending} awaiting review`,
        icon: BarChart3,
    },
]);

const roleBreakdown = computed(() =>
    Object.entries(props.metrics.users.byRole ?? {}).map(([role, total]) => ({
        role,
        total,
        label: roleLabels[role] ?? role,
    }))
);

const submissionTrend = computed(() => {
    const max = Math.max(0, ...props.dailySubmissions.map((point) => point.total));

    return props.dailySubmissions.map((point) => ({
        ...point,
        ratio: max > 0 ? point.total / max : 0,
    }));
});

const billHighlights = computed(() => [
    {
        label: 'Drafts in circulation',
        value: props.metrics.bills.drafts,
    },
    {
        label: 'In committee review',
        value: props.metrics.bills.underReview,
    },
]);

const submissionBreakdown = computed(() => [
    {
        label: 'Pending review',
        value: props.metrics.submissions.pending,
    },
    {
        label: 'Reviewed',
        value: props.metrics.submissions.reviewed,
    },
    {
        label: 'Escalated',
        value: props.metrics.submissions.escalated,
    },
]);

const upcomingDeadlines = computed(() =>
    [...props.recentBills]
        .filter((bill) => bill.participation_end_date)
        .sort((first, second) => {
            const firstDate = new Date(first.participation_end_date ?? '').getTime();
            const secondDate = new Date(second.participation_end_date ?? '').getTime();

            return firstDate - secondDate;
        })
        .slice(0, 3)
);

const dateFormatter = computed(() => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium', timeStyle: 'short' }));
const shortDateFormatter = computed(() => new Intl.DateTimeFormat('en-KE', { month: 'short', day: 'numeric' }));

function formatDate(value: string | null | undefined): string {
    if (! value) {
        return '—';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '—' : dateFormatter.value.format(parsed);
}

function formatShortDate(value: string): string {
    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? value : shortDateFormatter.value.format(parsed);
}

function severityClasses(severity: AlertResource['severity']): string {
    return (
        {
            warning: 'border-amber-200 bg-amber-50 text-amber-900',
            info: 'border-sky-200 bg-sky-50 text-sky-900',
            critical: 'border-rose-200 bg-rose-50 text-rose-900',
        }[severity] ?? 'border-muted bg-muted/40 text-foreground'
    );
}

function severityIcon(severity: AlertResource['severity']) {
    return (
        {
            critical: AlertCircle,
            warning: BellRing,
            info: Megaphone,
        }[severity] ?? Megaphone
    );
}

function roleBadge(role: string): string {
    return (
        {
            admin: 'bg-purple-50 text-purple-700',
            clerk: 'bg-sky-50 text-sky-700',
            mp: 'bg-emerald-50 text-emerald-700',
            senator: 'bg-amber-50 text-amber-700',
        }[role] ?? 'bg-muted text-muted-foreground'
    );
}

const legislatorIndexRoute = computed(() => clerk.legislators.index().url);

function handleAlertSubmit(): void {
    alertForm
        .transform((data) => ({
            ...data,
            action_url: data.action_url ? data.action_url : null,
            expires_at: data.expires_at ? data.expires_at : null,
        }))
        .post(admin.systemAlerts.store().url, {
        preserveScroll: true,
        onSuccess: () => {
            alertForm.defaults(alertFormDefaults());
            alertForm.reset();
            alertDialogOpen.value = false;
        },
        });
}

function handleInviteSubmit(): void {
    invitationForm
        .transform((data) => ({
            ...data,
            phone: data.phone ? data.phone : null,
            county: data.county ? data.county : null,
            constituency: data.constituency ? data.constituency : null,
            invitation_message: data.invitation_message ? data.invitation_message : null,
            expires_in_days: data.expires_in_days ? Number(data.expires_in_days) : null,
        }))
        .post(clerk.legislators.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                invitationForm.defaults(invitationFormDefaults());
                invitationForm.reset();
                inviteDialogOpen.value = false;
            },
        });
}

function dismissSystemAlert(alertId: number): void {
    if (dismissingAlertIds.value.includes(alertId)) {
        return;
    }

    dismissingAlertIds.value = [...dismissingAlertIds.value, alertId];

    router.delete(admin.systemAlerts.destroy({ systemAlert: alertId }).url, {
        preserveScroll: true,
        onFinish: () => {
            dismissingAlertIds.value = dismissingAlertIds.value.filter((id) => id !== alertId);
        },
    });
}

function isDismissingAlert(alertId: number): boolean {
    return dismissingAlertIds.value.includes(alertId);
}
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Admin control centre" />

        <div class="space-y-10">
            <header class="flex flex-wrap items-end justify-between gap-4">
                <div>
                    <h1 class="text-3xl font-semibold tracking-tight text-foreground">Government participation control centre</h1>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Monitor national participation health, oversee critical workflows, and safeguard civic engagement data.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-2">
                    <Link
                        :href="legislatorIndexRoute"
                        class="inline-flex items-center rounded-md border border-border/80 px-3 py-2 text-xs font-medium text-foreground hover:bg-muted/50"
                    >
                        Manage invitations
                        <ArrowUpRight class="ml-1 h-3.5 w-3.5" />
                    </Link>
                    <Link
                        :href="submissions.index().url"
                        class="inline-flex items-center rounded-md bg-primary px-3 py-2 text-xs font-medium text-primary-foreground shadow-sm hover:bg-primary/90"
                    >
                        View participation analytics
                        <FilePieChart class="ml-1 h-3.5 w-3.5" />
                    </Link>
                </div>
            </header>

            <section class="grid gap-4 xl:grid-cols-[1.4fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Administrative action centre</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-5 text-sm text-muted-foreground">
                        <p class="text-muted-foreground">
                            Publish system-wide guidance, onboard legislators, and triage urgent participation tasks without leaving the dashboard.
                        </p>

                        <div class="grid gap-3 md:grid-cols-2">
                            <Dialog v-model:open="alertDialogOpen">
                                <DialogTrigger as-child>
                                    <Button variant="outline" class="justify-start">
                                        <Megaphone class="h-4 w-4" />
                                        Publish system alert
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-xl">
                                    <DialogHeader>
                                        <DialogTitle>Publish a system alert</DialogTitle>
                                        <DialogDescription>
                                            Share time-sensitive guidance with all platform operators. Alerts appear immediately on the admin dashboard.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form class="space-y-4" @submit.prevent="handleAlertSubmit">
                                        <div class="space-y-2">
                                            <Label for="alert-title">Title</Label>
                                            <Input id="alert-title" v-model="alertForm.title" type="text" placeholder="Pending invitation follow-up" />
                                            <InputError :message="alertForm.errors.title" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="alert-message">Message</Label>
                                            <textarea
                                                id="alert-message"
                                                v-model="alertForm.message"
                                                rows="4"
                                                class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                placeholder="Remind clerks to follow up with pending legislative invitations."
                                            />
                                            <InputError :message="alertForm.errors.message" />
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="alert-severity">Severity</Label>
                                                <select
                                                    id="alert-severity"
                                                    v-model="alertForm.severity"
                                                    class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                >
                                                    <option v-for="option in severityOptions" :key="option" :value="option">
                                                        {{ option.charAt(0).toUpperCase() + option.slice(1) }}
                                                    </option>
                                                </select>
                                                <InputError :message="alertForm.errors.severity" />
                                            </div>

                                            <div class="space-y-2">
                                                <Label for="alert-expires">Expires at</Label>
                                                <Input
                                                    id="alert-expires"
                                                    v-model="alertForm.expires_at"
                                                    type="datetime-local"
                                                    placeholder="2025-10-12T18:00"
                                                />
                                                <InputError :message="alertForm.errors.expires_at" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="alert-link">Action link (optional)</Label>
                                            <Input id="alert-link" v-model="alertForm.action_url" type="url" placeholder="https://participation.gov/reports/weekly" />
                                            <InputError :message="alertForm.errors.action_url" />
                                        </div>

                                        <DialogFooter class="flex items-center justify-end gap-2 pt-2">
                                            <Button type="button" variant="ghost" @click="alertDialogOpen = false">Cancel</Button>
                                            <Button type="submit" :disabled="alertForm.processing">
                                                <Loader2 v-if="alertForm.processing" class="h-4 w-4 animate-spin" />
                                                Publish alert
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>

                            <Dialog v-model:open="inviteDialogOpen">
                                <DialogTrigger as-child>
                                    <Button class="justify-start">
                                        <UserPlus class="h-4 w-4" />
                                        Invite legislator
                                    </Button>
                                </DialogTrigger>
                                <DialogContent class="sm:max-w-xl">
                                    <DialogHeader>
                                        <DialogTitle>Invite a legislator</DialogTitle>
                                        <DialogDescription>
                                            Generate and send an activation email directly from the platform.
                                        </DialogDescription>
                                    </DialogHeader>
                                    <form class="space-y-4" @submit.prevent="handleInviteSubmit">
                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="invite-name">Full name</Label>
                                                <Input id="invite-name" v-model="invitationForm.name" type="text" placeholder="Hon. Jane Doe" />
                                                <InputError :message="invitationForm.errors.name" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="invite-email">Work email</Label>
                                                <Input id="invite-email" v-model="invitationForm.email" type="email" placeholder="jane.doe@parliament.go.ke" />
                                                <InputError :message="invitationForm.errors.email" />
                                            </div>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="invite-phone">Phone (optional)</Label>
                                                <Input id="invite-phone" v-model="invitationForm.phone" type="tel" placeholder="07xx xxx xxx" />
                                                <InputError :message="invitationForm.errors.phone" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="invite-house">House</Label>
                                                <select
                                                    id="invite-house"
                                                    v-model="invitationForm.legislative_house"
                                                    class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                >
                                                    <option v-for="house in houseOptions" :key="house" :value="house">
                                                        {{ house === 'national_assembly' ? 'National Assembly' : 'Senate' }}
                                                    </option>
                                                </select>
                                                <InputError :message="invitationForm.errors.legislative_house" />
                                            </div>
                                        </div>

                                        <div class="grid gap-4 md:grid-cols-2">
                                            <div class="space-y-2">
                                                <Label for="invite-county">County</Label>
                                                <Input id="invite-county" v-model="invitationForm.county" type="text" placeholder="Nairobi" />
                                                <InputError :message="invitationForm.errors.county" />
                                            </div>
                                            <div class="space-y-2">
                                                <Label for="invite-constituency">Constituency</Label>
                                                <Input id="invite-constituency" v-model="invitationForm.constituency" type="text" placeholder="Westlands" />
                                                <InputError :message="invitationForm.errors.constituency" />
                                            </div>
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="invite-message">Invitation message</Label>
                                            <textarea
                                                id="invite-message"
                                                v-model="invitationForm.invitation_message"
                                                rows="4"
                                                class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                placeholder="Greetings, join the participation platform to collaborate on upcoming bills."
                                            />
                                            <InputError :message="invitationForm.errors.invitation_message" />
                                        </div>

                                        <div class="space-y-2">
                                            <Label for="invite-expiry">Expires in (days)</Label>
                                            <Input id="invite-expiry" v-model="invitationForm.expires_in_days" type="number" min="1" max="30" />
                                            <InputError :message="invitationForm.errors.expires_in_days" />
                                        </div>

                                        <DialogFooter class="flex items-center justify-end gap-2 pt-2">
                                            <Button type="button" variant="ghost" @click="inviteDialogOpen = false">Cancel</Button>
                                            <Button type="submit" :disabled="invitationForm.processing">
                                                <Loader2 v-if="invitationForm.processing" class="h-4 w-4 animate-spin" />
                                                Send invitation
                                            </Button>
                                        </DialogFooter>
                                    </form>
                                </DialogContent>
                            </Dialog>
                        </div>

                        <p class="text-xs text-muted-foreground">
                            Activity recorded here is auditable via the system log. Alerts automatically expire when the configured deadline passes.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Upcoming participation deadlines</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <ul v-if="upcomingDeadlines.length" class="space-y-3 text-sm">
                            <li v-for="bill in upcomingDeadlines" :key="bill.id" class="flex items-start justify-between gap-3 rounded-lg border border-border/70 p-3">
                                <div>
                                    <Link :href="bills.show({ bill: bill.id }).url" class="font-medium text-foreground hover:underline">
                                        {{ bill.title }}
                                    </Link>
                                    <p class="text-xs text-muted-foreground">
                                        Bill No. {{ bill.bill_number }} · {{ bill.house?.replace('_', ' ') ?? '—' }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center rounded-full bg-primary/10 px-3 py-1 text-xs font-medium text-primary">
                                    {{ formatDate(bill.participation_end_date) }}
                                </span>
                            </li>
                        </ul>
                        <p v-else class="text-sm text-muted-foreground">
                            No deadlines in the next fortnight. Monitor bills to ensure participation windows remain visible to the public.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <Card v-for="card in metricCards" :key="card.title" class="border-border/80">
                    <CardHeader class="flex flex-row items-center justify-between pb-2">
                        <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ card.title }}</p>
                        <component :is="card.icon" class="h-5 w-5 text-primary" />
                    </CardHeader>
                    <CardContent>
                        <p class="text-3xl font-semibold text-foreground">{{ card.value }}</p>
                        <p class="text-xs text-muted-foreground">{{ card.description }}</p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.2fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">National user composition</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="entry in roleBreakdown" :key="entry.role" class="space-y-1">
                            <div class="flex items-center justify-between text-sm">
                                <div class="flex items-center gap-2">
                                    <span class="font-medium text-foreground">{{ entry.label }}</span>
                                    <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-xs font-medium', roleBadge(entry.role)]">
                                        {{ entry.role }}
                                    </span>
                                </div>
                                <span class="text-sm font-medium text-foreground">{{ entry.total }}</span>
                            </div>
                            <div class="h-2 rounded-full bg-muted">
                                <div
                                    class="h-2 rounded-full bg-primary transition-all"
                                    :style="{ width: `${props.metrics.users.total ? (entry.total / props.metrics.users.total) * 100 : 0}%` }"
                                />
                            </div>
                        </div>
                        <p class="text-xs text-muted-foreground">
                            {{ props.metrics.users.pendingInvitations }} invitations awaiting activation across the legislature.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Submission trend (14 days)</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div
                            v-for="point in submissionTrend"
                            :key="point.date"
                            class="flex items-center gap-3 text-sm"
                        >
                            <span class="w-16 shrink-0 text-xs text-muted-foreground">{{ formatShortDate(point.date) }}</span>
                            <div class="h-2 flex-1 rounded-full bg-muted">
                                <div class="h-2 rounded-full bg-primary" :style="{ width: `${Math.max(point.ratio * 100, 4)}%` }" />
                            </div>
                            <span class="w-10 text-right text-sm font-medium text-foreground">{{ point.total }}</span>
                        </div>
                        <p v-if="!submissionTrend.length" class="text-sm text-muted-foreground">
                            Submission activity will appear as citizens begin sending feedback.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.2fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Participation pipeline</CardTitle>
                    </CardHeader>
                    <CardContent class="grid gap-6 md:grid-cols-2">
                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Submission status</p>
                            <ul class="space-y-2 text-sm text-muted-foreground">
                                <li
                                    v-for="item in submissionBreakdown"
                                    :key="item.label"
                                    class="flex items-center justify-between rounded-md bg-muted/40 px-3 py-2"
                                >
                                    <span class="font-medium text-foreground">{{ item.label }}</span>
                                    <span>{{ item.value }}</span>
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Bill lifecycle</p>
                            <ul class="space-y-2 text-sm text-muted-foreground">
                                <li
                                    v-for="highlight in billHighlights"
                                    :key="highlight.label"
                                    class="flex items-center justify-between rounded-md bg-muted/30 px-3 py-2"
                                >
                                    <span class="font-medium text-foreground">{{ highlight.label }}</span>
                                    <span>{{ highlight.value }}</span>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>

            <Card class="border-border/80">
                <CardHeader>
                    <CardTitle class="text-lg">System alerts</CardTitle>
                </CardHeader>
                <CardContent class="space-y-4">
                    <div
                        v-for="alert in systemAlerts"
                        :key="alert.id"
                        :class="['rounded-lg border px-4 py-3 text-sm', severityClasses(alert.severity)]"
                    >
                        <div class="flex flex-col gap-3">
                            <div class="flex items-start justify-between gap-4">
                                <div class="space-y-1">
                                    <p class="flex items-center gap-2 text-sm font-medium text-foreground">
                                        <component :is="severityIcon(alert.severity)" class="h-4 w-4" />
                                        {{ alert.title }}
                                    </p>
                                    <p class="text-xs text-muted-foreground/90">{{ alert.message }}</p>
                                </div>
                                <div class="flex items-center gap-2">
                                    <span v-if="alert.published_at" class="text-[11px] uppercase tracking-wide text-muted-foreground">
                                        {{ formatDate(alert.published_at) }}
                                    </span>
                                    <Button
                                        variant="ghost"
                                        size="sm"
                                        class="h-8 px-2 text-xs text-muted-foreground hover:text-foreground"
                                        :disabled="isDismissingAlert(alert.id)"
                                        @click="dismissSystemAlert(alert.id)"
                                    >
                                        <Loader2 v-if="isDismissingAlert(alert.id)" class="h-3.5 w-3.5 animate-spin" />
                                        <X v-else class="h-3.5 w-3.5" />
                                        <span class="sr-only">Dismiss alert</span>
                                    </Button>
                                </div>
                            </div>
                            <div class="flex flex-wrap items-center gap-3 text-xs">
                                <span class="inline-flex items-center rounded-full bg-white/30 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-foreground">
                                    {{ alert.severity }}
                                </span>
                                <Link
                                    v-if="alert.href"
                                    :href="alert.href"
                                    class="inline-flex items-center text-xs font-medium text-primary hover:underline"
                                >
                                    Review
                                    <ArrowUpRight class="ml-1 h-3.5 w-3.5" />
                                </Link>
                            </div>
                        </div>
                    </div>
                    <p v-if="!systemAlerts.length" class="text-sm text-muted-foreground">
                        The platform is running smoothly. New alerts will appear here when action is required.
                    </p>
                </CardContent>
            </Card>
            </section>

            <section class="grid gap-6 xl:grid-cols-[1.4fr_1fr]">
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
                                <tr v-for="submission in recentSubmissions" :key="submission.id" class="hover:bg-muted/30">
                                    <td class="px-6 py-4 font-mono text-xs text-foreground">{{ submission.tracking_id }}</td>
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
                                        Submission activity will populate once citizens share their views.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Management shortcuts</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div
                            v-for="shortcut in managementShortcuts"
                            :key="shortcut.key"
                            class="rounded-lg border border-border/70 p-4"
                        >
                            <p class="text-sm font-medium text-foreground">{{ shortcut.title }}</p>
                            <p class="text-xs text-muted-foreground">{{ shortcut.description }}</p>
                            <Link :href="shortcut.href" class="mt-2 inline-flex items-center text-xs font-medium text-primary hover:underline">
                                Open workspace
                                <ArrowUpRight class="ml-1 h-3.5 w-3.5" />
                            </Link>
                        </div>
                        <p v-if="!managementShortcuts.length" class="text-sm text-muted-foreground">
                            Link your most-used administrative workflows here for quicker access.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Latest accounts</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead class="bg-muted/60 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-3">Name</th>
                                    <th class="px-6 py-3">Role</th>
                                    <th class="px-6 py-3">Created</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="user in recentUsers" :key="user.id" class="hover:bg-muted/30">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-foreground">{{ user.name }}</p>
                                        <p class="text-xs text-muted-foreground">{{ user.email }}</p>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium capitalize', roleBadge(user.role)]">
                                            {{ user.role.replace('_', ' ') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(user.created_at) }}</td>
                                </tr>
                                <tr v-if="!recentUsers.length">
                                    <td colspan="3" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                        New accounts will surface here when administrators onboard fresh users.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Recent admin sessions</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4 text-sm">
                        <div
                            v-for="session in recentSessions"
                            :key="session.id"
                            class="rounded-lg border border-border/70 p-4"
                        >
                            <p class="text-sm font-medium text-foreground">{{ session.user?.name ?? 'Unknown user' }}</p>
                            <p class="text-xs text-muted-foreground">{{ session.user?.email ?? '—' }}</p>
                            <div class="mt-3 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                <span class="inline-flex items-center gap-1">
                                    <ShieldCheck class="h-3.5 w-3.5 text-primary" />
                                    {{ session.device ?? 'Unknown device' }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <Activity class="h-3.5 w-3.5 text-primary" />
                                    {{ formatDate(session.last_activity_at) }}
                                </span>
                                <span class="inline-flex items-center gap-1">
                                    <BellRing class="h-3.5 w-3.5 text-primary" />
                                    {{ session.ip_address ?? '—' }}
                                </span>
                            </div>
                        </div>
                        <p v-if="!recentSessions.length" class="text-sm text-muted-foreground">
                            No recent admin sessions recorded. Session activity will appear once administrators sign in.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section>
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Latest bill publications</CardTitle>
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
                                <tr v-for="bill in recentBills" :key="bill.id" class="hover:bg-muted/30">
                                    <td class="px-6 py-4">
                                        <Link :href="bills.show({ bill: bill.id }).url" class="font-medium text-foreground hover:underline">
                                            {{ bill.title }}
                                        </Link>
                                        <p class="text-xs text-muted-foreground">Bill No. {{ bill.bill_number }}</p>
                                        <p class="text-xs text-muted-foreground">Created by {{ bill.creator?.name ?? 'Unknown' }}</p>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ bill.house?.replace('_', ' ') ?? '—' }}</td>
                                    <td class="px-6 py-4 capitalize text-muted-foreground">{{ bill.status.replace('_', ' ') }}</td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(bill.participation_end_date) }}</td>
                                </tr>
                                <tr v-if="!recentBills.length">
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                        Newly published bills will appear here. Coordinate with clerks to maintain publication cadence.
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
