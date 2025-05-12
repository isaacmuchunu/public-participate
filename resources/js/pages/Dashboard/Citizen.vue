<script setup lang="ts">
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/AppLayout.vue';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    AlertTriangle,
    Bell,
    CalendarCheck,
    CheckCircle2,
    ChevronRight,
    Clock,
    Compass,
    ExternalLink,
    FileText,
    HelpCircle,
    LifeBuoy,
    Mail,
    MapPin,
    MessageCircle,
    MessageSquare,
    Phone,
    PlayCircle,
    ShieldCheck,
    Sparkles,
} from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import type { Component } from 'vue';
import * as bills from '@/routes/bills';
import * as sessions from '@/routes/sessions';
import * as profile from '@/routes/profile';

interface BillSummary {
    id: number;
    title: string;
    bill_number: string;
    participation_end_date: string | null;
    house: string;
    submissions_count?: number;
}

interface SubmissionResource {
    id: number;
    status: string;
    tracking_id: string;
    created_at: string;
    bill: {
        id: number;
        title: string;
        bill_number: string;
    } | null;
}

interface NotificationResource {
    type: string;
    message: string;
    severity: 'info' | 'warning' | 'success' | string;
}

interface StatsResource {
    openBills: number;
    totalSubmissions: number;
    pendingReviews: number;
}

interface DeadlineResource {
    id: number;
    title: string;
    participation_end_date: string | null;
    bill_number: string;
}

interface HighlightResource {
    bill_id: number;
    bill_title: string;
    excerpt: string;
}

interface ResourceShortcut {
    key: string;
    title: string;
    description: string;
    href: string;
    label?: string | null;
}

interface SupportSchedule {
    days: string[];
    start: string;
    end: string;
    timezone: string;
    timezone_label?: string | null;
}

interface SupportChannelResource {
    key: string;
    type: 'phone' | 'email' | 'chat' | 'whatsapp' | 'portal';
    title: string;
    contact: string;
    description: string;
    link: string;
    response_time?: string | null;
    languages?: string[];
    schedule?: SupportSchedule | null;
    notes?: string | null;
}

interface KnowledgeResource {
    key: string;
    title: string;
    description: string;
    href: string;
    format: string;
    category?: string | null;
    external?: boolean;
}

interface CommunityClinic {
    key: string;
    title: string;
    starts_at: string;
    duration: string;
    channel: string;
    registration_url: string;
    language?: string | null;
}

interface FaqResource {
    question: string;
    answer: string;
}

interface Props {
    openBills: BillSummary[];
    recentSubmissions: SubmissionResource[];
    notifications: NotificationResource[];
    stats: StatsResource;
    upcomingDeadlines: DeadlineResource[];
    topicHighlights: HighlightResource[];
    resourceShortcuts: ResourceShortcut[];
    supportChannels: SupportChannelResource[];
    knowledgeBase: KnowledgeResource[];
    communityClinics: CommunityClinic[];
    faqs: FaqResource[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Dashboard',
        href: dashboardUrl(),
    },
];

function dashboardUrl(): string {
    return '/dashboard';
}

const dateFormatter = computed(() => new Intl.DateTimeFormat('en-KE', { dateStyle: 'medium' }));

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
            submitted: 'bg-blue-50 text-blue-700',
            pending: 'bg-amber-50 text-amber-700',
            reviewed: 'bg-emerald-50 text-emerald-700',
            rejected: 'bg-rose-50 text-rose-700',
        }[normalised] ?? 'bg-slate-100 text-slate-700'
    );
}

function statusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

function badgeIcon(severity: string) {
    return severity === 'warning' ? AlertTriangle : Bell;
}

const participationSearch = ref('');

function submitParticipationSearch() {
    router.get(
        bills.participate().url,
        { search: participationSearch.value || undefined },
        {
            preserveScroll: true,
        }
    );
}

const statItems = computed(() => [
    {
        label: 'Bills open for comments',
        value: props.stats.openBills,
        helper: 'Actively receiving submissions',
    },
    {
        label: 'Submissions you have made',
        value: props.stats.totalSubmissions,
        helper: 'Across all bills and clauses',
    },
    {
        label: 'Awaiting review',
        value: props.stats.pendingReviews,
        helper: 'Monitored by parliamentary clerks',
    },
]);

const participationPlan = computed(() => {
    const items = props.openBills.slice(0, 3).map((bill) => ({
        key: `bill-${bill.id}`,
        title: bill.title,
        description: `Deadline ${formatDate(bill.participation_end_date)}`,
        href: bills.show({ bill: bill.id }).url,
    }));

    if (items.length < 3) {
        items.push(
            {
                key: 'profile',
                title: 'Complete your civic profile',
                description: 'Strengthen your personal details to speed up submission verification.',
                href: profile.edit().url,
            },
            {
                key: 'sessions',
                title: 'Review device sessions',
                description: 'Remove any unfamiliar logins to keep your account secure.',
                href: sessions.index().url,
            }
        );
    }

    return items.slice(0, 4);
});

const now = ref(new Date());
let clockTimer: number | undefined;

onMounted(() => {
    if (typeof window === 'undefined') {
        return;
    }

    clockTimer = window.setInterval(() => {
        now.value = new Date();
    }, 60000);
});

onBeforeUnmount(() => {
    if (typeof window !== 'undefined' && clockTimer) {
        window.clearInterval(clockTimer);
    }
});

function isInternalLink(href: string): boolean {
    return href.startsWith('/') && ! href.startsWith('//');
}

const shortcutIconMap: Record<string, Component> = {
    start_submission: MessageSquare,
    track_submission: Compass,
    manage_sessions: ShieldCheck,
    update_profile: Sparkles,
};

const shortcutItems = computed(() =>
    props.resourceShortcuts.map((shortcut) => ({
        ...shortcut,
        icon: shortcutIconMap[shortcut.key] ?? HelpCircle,
        isInternal: isInternalLink(shortcut.href),
    }))
);

const knowledgeEntries = computed(() =>
    props.knowledgeBase.map((resource) => {
        const isVideo = resource.format.toLowerCase().includes('video');

        return {
            ...resource,
            icon: isVideo ? PlayCircle : FileText,
            isExternal: resource.external ?? ! isInternalLink(resource.href),
        };
    })
);

type ChannelStatus = {
    label: string;
    badgeClass: string;
};

const supportChannelIconMap: Record<string, Component> = {
    phone: Phone,
    email: Mail,
    chat: MessageCircle,
    whatsapp: MessageCircle,
    portal: LifeBuoy,
};

function parseMinutes(value: string): number {
    const [hours, minutes] = value.split(':').map((segment) => Number.parseInt(segment, 10));

    if (Number.isNaN(hours)) {
        return 0;
    }

    return hours * 60 + (Number.isNaN(minutes) ? 0 : minutes);
}

function formatDayRange(days: string[]): string {
    if (! days.length) {
        return 'Daily';
    }

    if (days.length >= 7) {
        return 'Daily';
    }

    const order = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];
    const indices = days
        .map((day) => order.indexOf(day))
        .filter((index) => index !== -1)
        .sort((a, b) => a - b);

    const sequential = indices.every((value, index) => index === 0 || value === indices[index - 1] + 1);

    if (sequential && indices.length) {
        const first = order[indices[0]];
        const last = order[indices[indices.length - 1]];

        return `${first} – ${last}`;
    }

    return days.join(', ');
}

function timezoneDate(timezone: string): Date {
    return new Date(now.value.toLocaleString('en-US', { timeZone: timezone }));
}

function computeChannelStatus(channel: SupportChannelResource): ChannelStatus {
    const schedule = channel.schedule;

    if (! schedule) {
        return {
            label: 'Available anytime',
            badgeClass: 'bg-emerald-50 text-emerald-700',
        };
    }

    const timezone = schedule.timezone || 'Africa/Nairobi';
    const current = timezoneDate(timezone);
    const weekday = current
        .toLocaleString('en-US', { timeZone: timezone, weekday: 'short' })
        .slice(0, 3);
    const days = schedule.days ?? [];

    if (days.length && ! days.includes(weekday)) {
        return {
            label: 'Offline today',
            badgeClass: 'bg-slate-100 text-slate-600',
        };
    }

    const currentMinutes = current.getHours() * 60 + current.getMinutes();
    const start = parseMinutes(schedule.start);
    const end = parseMinutes(schedule.end);

    if (currentMinutes < start) {
        return {
            label: `Opens at ${schedule.start}`,
            badgeClass: 'bg-amber-50 text-amber-700',
        };
    }

    if (currentMinutes >= start && currentMinutes <= end) {
        return {
            label: 'Open now',
            badgeClass: 'bg-emerald-50 text-emerald-700',
        };
    }

    return {
        label: 'Closed',
        badgeClass: 'bg-slate-100 text-slate-600',
    };
}

function formatSchedule(schedule: SupportSchedule): string {
    const dayRange = formatDayRange(schedule.days ?? []);
    const timezoneLabel = schedule.timezone_label ? ` ${schedule.timezone_label}` : '';

    return `${dayRange} · ${schedule.start} – ${schedule.end}${timezoneLabel}`;
}

function channelActionLabel(type: SupportChannelResource['type']): string {
    return (
        {
            phone: 'Call',
            email: 'Email',
            chat: 'Message',
            whatsapp: 'Message',
            portal: 'Open',
        }[type] ?? 'Open'
    );
}

const supportChannelItems = computed(() =>
    props.supportChannels.map((channel) => {
        const icon = supportChannelIconMap[channel.type] ?? LifeBuoy;
        const status = computeChannelStatus(channel);

        return {
            ...channel,
            icon,
            status,
            scheduleLabel: channel.schedule ? formatSchedule(channel.schedule) : 'Available anytime',
            actionLabel: channelActionLabel(channel.type),
            languagesLabel: channel.languages?.join(' • ') ?? null,
            openInNewTab: channel.link.startsWith('http'),
        };
    })
);

const clinicDateFormatter = computed(
    () =>
        new Intl.DateTimeFormat('en-KE', {
            weekday: 'short',
            month: 'short',
            day: 'numeric',
        })
);

const clinicTimeFormatter = computed(
    () =>
        new Intl.DateTimeFormat('en-KE', {
            hour: '2-digit',
            minute: '2-digit',
        })
);

const clinicItems = computed(() =>
    props.communityClinics.map((clinic) => {
        const date = new Date(clinic.starts_at);
        const valid = ! Number.isNaN(date.getTime());

        return {
            ...clinic,
            dateLabel: valid ? clinicDateFormatter.value.format(date) : 'Schedule to be confirmed',
            timeLabel: valid ? clinicTimeFormatter.value.format(date) : null,
        };
    })
);

const faqItems = computed(() => props.faqs);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Citizen dashboard" />

        <div class="space-y-10">
            <section class="space-y-6">
                <div class="flex flex-wrap items-end justify-between gap-4">
                    <div>
                        <h1 class="text-3xl font-semibold tracking-tight text-foreground">Karibu tena.</h1>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Explore open bills, follow your submissions, and stay informed on participation timelines.
                        </p>
                    </div>
                    <Link :href="bills.participate()" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                        View participation hub
                        <ChevronRight class="ml-1 h-4 w-4" />
                    </Link>
                </div>

                <div class="grid gap-4 md:grid-cols-3">
                    <Card v-for="stat in statItems" :key="stat.label" class="border-border/80">
                        <CardHeader class="space-y-1">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">{{ stat.label }}</p>
                            <CardTitle class="text-3xl font-semibold text-foreground">{{ stat.value }}</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <p class="text-xs text-muted-foreground">{{ stat.helper }}</p>
                        </CardContent>
                    </Card>
                </div>

                <Card class="border-border/80">
                    <CardContent class="flex flex-col gap-4 p-6 md:flex-row md:items-center md:justify-between">
                        <div>
                            <p class="text-sm font-semibold text-foreground">Find a bill by keyword or ministry</p>
                            <p class="text-xs text-muted-foreground">Search the participation hub to dive into clauses that matter to you.</p>
                        </div>
                        <form class="flex w-full max-w-sm items-center gap-2" @submit.prevent="submitParticipationSearch">
                            <div class="relative flex-1">
                                <Compass class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                                <input
                                    v-model="participationSearch"
                                    type="search"
                                    name="search"
                                    class="h-10 w-full rounded-md border border-border/70 bg-background pl-9 pr-3 text-sm focus:border-primary focus:outline-none focus:ring-2 focus:ring-primary/40"
                                    placeholder="Search housing, finance, education..."
                                />
                            </div>
                            <button type="submit" class="inline-flex h-10 items-center rounded-md bg-primary px-4 text-sm font-medium text-primary-foreground transition hover:bg-primary/90">
                                Explore
                            </button>
                        </form>
                    </CardContent>
                </Card>

                <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
                    <Card v-for="bill in openBills" :key="bill.id" class="border-border/80">
                        <CardHeader class="space-y-2">
                            <CardTitle class="line-clamp-2 text-base">{{ bill.title }}</CardTitle>
                            <p class="text-sm text-muted-foreground">Bill No. {{ bill.bill_number }}</p>
                        </CardHeader>
                        <CardContent class="space-y-4">
                            <div class="flex items-center gap-2 text-sm text-muted-foreground">
                                <CalendarCheck class="h-4 w-4" />
                                Deadline: <span class="font-medium text-foreground">{{ formatDate(bill.participation_end_date) }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs text-muted-foreground">
                                <span class="uppercase tracking-wide">House: {{ bill.house?.replace('_', ' ') ?? 'N/A' }}</span>
                                <span>{{ bill.submissions_count ?? 0 }} submissions</span>
                            </div>
                            <Link :href="bills.show({ bill: bill.id }).url" class="inline-flex items-center text-sm font-medium text-primary hover:underline">
                                Review bill details
                                <ChevronRight class="ml-1 h-4 w-4" />
                            </Link>
                        </CardContent>
                    </Card>
                    <Card v-if="!openBills.length" class="border-dashed border-border/80 p-8 text-center text-sm text-muted-foreground">
                        No open bills at the moment. Check back soon for new participation opportunities.
                    </Card>
                </div>
            </section>

            <section class="grid gap-6 lg:grid-cols-2">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Upcoming deadlines</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-3">
                        <div v-for="deadline in upcomingDeadlines" :key="deadline.id" class="flex items-center justify-between rounded-md bg-muted/50 px-4 py-3 text-sm">
                            <div>
                                <p class="font-medium text-foreground">{{ deadline.title }}</p>
                                <p class="text-xs text-muted-foreground">Bill No. {{ deadline.bill_number }}</p>
                            </div>
                            <div class="text-right text-xs text-muted-foreground">
                                <p class="font-semibold text-foreground">{{ formatDate(deadline.participation_end_date) }}</p>
                                <p>Submission closes soon</p>
                            </div>
                        </div>
                        <p v-if="!upcomingDeadlines.length" class="text-sm text-muted-foreground">
                            You have no deadlines approaching within the next few days. Keep exploring open bills to participate early.
                        </p>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Clauses citizens are discussing</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="highlight in topicHighlights" :key="`${highlight.bill_id}-${highlight.excerpt}`" class="rounded-md bg-muted/50 p-4 text-sm">
                            <div class="mb-2 flex items-center gap-2 text-xs uppercase tracking-wide text-primary">
                                <Sparkles class="h-4 w-4" />
                                {{ highlight.bill_title }}
                            </div>
                            <p class="text-muted-foreground">
                                {{ highlight.excerpt }}
                            </p>
                            <Link :href="bills.show({ bill: highlight.bill_id }).url" class="mt-3 inline-flex items-center text-xs font-medium text-primary hover:underline">
                                Read this clause
                                <ChevronRight class="ml-1 h-3 w-3" />
                            </Link>
                        </div>
                        <p v-if="!topicHighlights.length" class="text-sm text-muted-foreground">
                            Clause highlights will appear here once bill summaries include detailed annotations.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[2fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Recent submissions</CardTitle>
                    </CardHeader>
                    <CardContent class="p-0">
                        <table class="min-w-full divide-y divide-border text-sm">
                            <thead class="bg-muted/60 text-left text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th class="px-6 py-3">Bill</th>
                                    <th class="px-6 py-3">Tracking ID</th>
                                    <th class="px-6 py-3">Status</th>
                                    <th class="px-6 py-3">Submitted</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-border">
                                <tr v-for="submission in recentSubmissions" :key="submission.id" class="hover:bg-muted/40">
                                    <td class="px-6 py-4">
                                        <p class="font-medium text-foreground">{{ submission.bill?.title ?? 'Unknown bill' }}</p>
                                        <p class="text-xs text-muted-foreground">Bill No. {{ submission.bill?.bill_number ?? '—' }}</p>
                                    </td>
                                    <td class="px-6 py-4 font-mono text-xs">{{ submission.tracking_id }}</td>
                                    <td class="px-6 py-4">
                                        <span :class="['inline-flex items-center rounded-full px-2.5 py-1 text-xs font-medium', statusBadge(submission.status)]">
                                            {{ statusLabel(submission.status) }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 text-muted-foreground">{{ formatDate(submission.created_at) }}</td>
                                </tr>
                                <tr v-if="!recentSubmissions.length">
                                    <td colspan="4" class="px-6 py-12 text-center text-sm text-muted-foreground">
                                        You have not submitted any feedback yet. Participate in a bill to see your history here.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Notifications</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="notification in notifications" :key="notification.message" class="flex items-start gap-3 rounded-lg bg-muted/60 p-4 text-sm">
                            <component :is="badgeIcon(notification.severity)" class="mt-0.5 h-5 w-5 text-muted-foreground" />
                            <p class="text-muted-foreground">{{ notification.message }}</p>
                        </div>
                        <p v-if="!notifications.length" class="text-sm text-muted-foreground">
                            You are all caught up! Important updates will appear here.
                        </p>
                    </CardContent>
                </Card>
            </section>

            <section class="grid gap-6 lg:grid-cols-[1.6fr_1fr]">
                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Participation checklist</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-4">
                        <div v-for="item in participationPlan" :key="item.key" class="flex items-start gap-3 rounded-lg border border-border/70 p-4 text-sm">
                            <CheckCircle2 class="mt-0.5 h-5 w-5 text-primary" />
                            <div>
                                <Link :href="item.href" class="font-medium text-foreground hover:underline">
                                    {{ item.title }}
                                </Link>
                                <p class="text-xs text-muted-foreground">{{ item.description }}</p>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border-border/80">
                    <CardHeader>
                        <CardTitle class="text-lg">Resources & support</CardTitle>
                    </CardHeader>
                    <CardContent class="space-y-6 text-sm text-muted-foreground">
                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Guided actions</p>
                            <ul class="space-y-3">
                                <li v-for="shortcut in shortcutItems" :key="shortcut.key" class="rounded-lg bg-muted/50 p-3">
                                    <Link v-if="shortcut.isInternal" :href="shortcut.href" class="flex items-start gap-3 text-left">
                                        <component :is="shortcut.icon" class="mt-0.5 h-4 w-4 text-primary" />
                                        <span>
                                            <span class="block font-medium text-foreground">{{ shortcut.title }}</span>
                                            <span class="text-xs text-muted-foreground">{{ shortcut.description }}</span>
                                            <span v-if="shortcut.label" class="mt-2 inline-flex rounded-full bg-primary/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-primary">
                                                {{ shortcut.label }}
                                            </span>
                                        </span>
                                    </Link>
                                    <a
                                        v-else
                                        :href="shortcut.href"
                                        class="flex items-start gap-3 text-left"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        <component :is="shortcut.icon" class="mt-0.5 h-4 w-4 text-primary" />
                                        <span>
                                            <span class="block font-medium text-foreground">{{ shortcut.title }}</span>
                                            <span class="text-xs text-muted-foreground">{{ shortcut.description }}</span>
                                            <span v-if="shortcut.label" class="mt-2 inline-flex rounded-full bg-primary/15 px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide text-primary">
                                                {{ shortcut.label }}
                                            </span>
                                        </span>
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div v-if="knowledgeEntries.length" class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Knowledge base</p>
                            <ul class="space-y-3">
                                <li v-for="resource in knowledgeEntries" :key="resource.key" class="rounded-lg border border-border/70 p-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div class="flex flex-1 items-start gap-3">
                                            <component :is="resource.icon" class="mt-0.5 h-4 w-4 text-primary" />
                                            <div>
                                                <p class="font-medium text-foreground">{{ resource.title }}</p>
                                                <p class="text-xs text-muted-foreground">{{ resource.description }}</p>
                                                <div class="mt-2 flex flex-wrap items-center gap-x-2 text-[11px] uppercase tracking-wide text-primary/80">
                                                    <span v-if="resource.category">{{ resource.category }}</span>
                                                    <span v-if="resource.category" class="hidden sm:inline">•</span>
                                                    <span>{{ resource.format }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <Link v-if="!resource.isExternal" :href="resource.href" class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border/70 text-muted-foreground transition hover:border-primary hover:text-primary">
                                            <ExternalLink class="h-4 w-4" />
                                        </Link>
                                        <a
                                            v-else
                                            :href="resource.href"
                                            class="inline-flex h-9 w-9 items-center justify-center rounded-full border border-border/70 text-muted-foreground transition hover:border-primary hover:text-primary"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                        >
                                            <ExternalLink class="h-4 w-4" />
                                        </a>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div v-if="clinicItems.length" class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Community support clinics</p>
                            <ul class="space-y-3">
                                <li v-for="clinic in clinicItems" :key="clinic.key" class="rounded-lg border border-dashed border-primary/30 p-3">
                                    <div class="flex flex-col gap-2">
                                        <p class="font-medium text-foreground">{{ clinic.title }}</p>
                                        <div class="flex flex-wrap items-center gap-2 text-xs text-muted-foreground">
                                            <CalendarCheck class="h-4 w-4" />
                                            <span>{{ clinic.dateLabel }}</span>
                                            <span v-if="clinic.timeLabel">• {{ clinic.timeLabel }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <Clock class="h-4 w-4" />
                                            <span>{{ clinic.duration }}</span>
                                        </div>
                                        <div class="flex items-center gap-2 text-xs text-muted-foreground">
                                            <MapPin class="h-4 w-4" />
                                            <span>{{ clinic.channel }}</span>
                                        </div>
                                        <p v-if="clinic.language" class="text-xs text-muted-foreground">Language: {{ clinic.language }}</p>
                                    </div>
                                    <a
                                        :href="clinic.registration_url"
                                        class="mt-3 inline-flex items-center gap-1 text-xs font-medium text-primary hover:underline"
                                        target="_blank"
                                        rel="noopener noreferrer"
                                    >
                                        Reserve your seat
                                        <ChevronRight class="h-3 w-3" />
                                    </a>
                                </li>
                            </ul>
                        </div>

                        <div class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Talk to us</p>
                            <ul class="space-y-3">
                                <li v-for="channel in supportChannelItems" :key="channel.key" class="rounded-lg bg-muted/40 p-3">
                                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                                        <div class="flex flex-1 items-start gap-3">
                                            <component :is="channel.icon" class="mt-0.5 h-4 w-4 text-primary" />
                                            <div>
                                                <p class="font-medium text-foreground">{{ channel.title }}</p>
                                                <p class="text-xs text-muted-foreground">{{ channel.description }}</p>
                                                <p class="mt-1 text-xs font-medium text-primary">{{ channel.contact }}</p>
                                                <p v-if="channel.languagesLabel" class="text-[11px] text-muted-foreground">Languages: {{ channel.languagesLabel }}</p>
                                                <p class="text-[11px] text-muted-foreground">{{ channel.scheduleLabel }}</p>
                                                <p v-if="channel.response_time" class="text-[11px] text-muted-foreground">Response: {{ channel.response_time }}</p>
                                            </div>
                                        </div>
                                        <div class="flex items-start gap-3 sm:flex-col sm:items-end sm:gap-2">
                                            <span :class="['inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-semibold uppercase tracking-wide', channel.status.badgeClass]">
                                                {{ channel.status.label }}
                                            </span>
                                            <a
                                                :href="channel.link"
                                                class="inline-flex items-center rounded-md border border-primary/50 px-3 py-1 text-xs font-medium text-primary transition hover:bg-primary/10"
                                                :target="channel.openInNewTab ? '_blank' : undefined"
                                                :rel="channel.openInNewTab ? 'noopener noreferrer' : undefined"
                                            >
                                                {{ channel.actionLabel }}
                                            </a>
                                        </div>
                                    </div>
                                </li>
                            </ul>
                        </div>

                        <div v-if="faqItems.length" class="space-y-3">
                            <p class="text-xs uppercase tracking-wide text-muted-foreground">Quick answers</p>
                            <ul class="space-y-3">
                                <li v-for="faq in faqItems" :key="faq.question" class="rounded-lg bg-muted/30 p-3">
                                    <p class="font-medium text-foreground">{{ faq.question }}</p>
                                    <p class="mt-1 text-xs text-muted-foreground">{{ faq.answer }}</p>
                                </li>
                            </ul>
                        </div>
                    </CardContent>
                </Card>
            </section>
        </div>
    </AppLayout>
</template>
