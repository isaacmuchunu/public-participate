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
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import {
    Ban,
    CalendarClock,
    CheckCircle2,
    EllipsisVertical,
    LoaderCircle,
    RefreshCcw,
    ShieldQuestion,
    UserCheck,
} from 'lucide-vue-next';
import * as citizenRoutes from '@/routes/clerk/citizens';
import * as submissionRoutes from '@/routes/submissions';

interface Citizen {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    county: string | null;
    constituency: string | null;
    is_verified: boolean;
    suspended_at: string | null;
    created_at: string;
    last_active_at: string | null;
    submissions_count?: number;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface CitizenCollectionMeta {
    from?: number | null;
    to?: number | null;
    total?: number;
}

interface RecentSubmission {
    id: number;
    tracking_id: string;
    submission_type: string;
    created_at: string;
    bill: {
        id: number | null;
        title: string | null;
    } | null;
    citizen: {
        id: number;
        name: string;
        email: string;
    } | null;
}

interface Props {
    citizens: {
        data: Citizen[];
        links: PaginationLink[];
        meta?: CitizenCollectionMeta;
    };
    filters: {
        status?: string | null;
        county?: string | null;
        search?: string | null;
    };
    metrics: {
        total: number;
        verified: number;
        unverified: number;
        suspended: number;
    };
    counties: string[];
    recentSubmissions: RecentSubmission[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clerk workspace', href: '/dashboard' },
    { title: 'Citizens', href: citizenRoutes.index().url },
];

const filterForm = reactive({
    status: props.filters?.status ?? 'all',
    county: props.filters?.county ?? 'all',
    search: props.filters?.search ?? '',
});

const metricCards = computed(() => [
    {
        key: 'total',
        label: 'Registered citizens',
        value: props.metrics.total ?? 0,
        helper: 'Overall portal users',
    },
    {
        key: 'verified',
        label: 'Verified profiles',
        value: props.metrics.verified ?? 0,
        helper: 'Cleared for submissions',
    },
    {
        key: 'unverified',
        label: 'Pending verification',
        value: props.metrics.unverified ?? 0,
        helper: 'Require follow-up checks',
    },
    {
        key: 'suspended',
        label: 'Suspended accounts',
        value: props.metrics.suspended ?? 0,
        helper: 'Temporarily blocked',
    },
]);

const hasCitizens = computed(() => props.citizens.data.length > 0);

const dateFormatter = computed(
    () =>
        new Intl.DateTimeFormat('en-KE', {
            dateStyle: 'medium',
            timeStyle: 'short',
        }),
);

function formatDate(value: string | null | undefined): string {
    if (!value) {
        return '—';
    }

    const parsed = new Date(value);

    return Number.isNaN(parsed.getTime()) ? '—' : dateFormatter.value.format(parsed);
}

const paginationLabel = (label: string) => label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');

const resultSummary = computed(() => {
    const meta = props.citizens.meta;

    if (!meta) {
        return null;
    }

    const from = meta.from ?? (hasCitizens.value ? 1 : 0);
    const to = meta.to ?? props.citizens.data.length;
    const total = meta.total ?? props.citizens.data.length;

    return { from, to, total };
});

const statusDialogOpen = ref(false);
const selectedCitizen = ref<Citizen | null>(null);
const pendingAction = ref<'verify' | 'unverify' | 'suspend' | 'restore' | null>(null);

const statusFormDefaults = () => ({
    action: '',
    reason: '',
});

const statusForm = useForm(statusFormDefaults());

function openStatusDialog(citizen: Citizen, action: 'verify' | 'unverify' | 'suspend' | 'restore'): void {
    selectedCitizen.value = citizen;
    pendingAction.value = action;
    statusForm.defaults({ action, reason: '' });
    statusForm.reset();
    statusForm.clearErrors();
    statusDialogOpen.value = true;
}

watch(
    () => statusDialogOpen.value,
    (isOpen) => {
        if (!isOpen) {
            selectedCitizen.value = null;
            pendingAction.value = null;
            statusForm.defaults(statusFormDefaults());
            statusForm.reset();
            statusForm.clearErrors();
        }
    },
);

const actionLabel = computed(() => {
    switch (pendingAction.value) {
        case 'verify':
            return 'Verify citizen';
        case 'unverify':
            return 'Mark as unverified';
        case 'suspend':
            return 'Suspend account';
        case 'restore':
            return 'Restore access';
        default:
            return 'Update status';
    }
});

const actionDescription = computed(() => {
    switch (pendingAction.value) {
        case 'verify':
            return 'Grants the citizen full access to submit and track participation.';
        case 'unverify':
            return 'Revokes verification while keeping the account active.';
        case 'suspend':
            return 'Temporarily blocks access to submissions and account features.';
        case 'restore':
            return 'Re-enables access after a suspension period.';
        default:
            return '';
    }
});

function submitStatus(): void {
    if (!selectedCitizen.value || !pendingAction.value) {
        return;
    }

    statusForm
        .transform((data) => ({
            ...data,
            action: pendingAction.value,
            reason: data.reason ? data.reason : null,
        }))
        .patch(citizenRoutes.update({ citizen: selectedCitizen.value.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                statusDialogOpen.value = false;
            },
        });
}

function citizenStatusClasses(citizen: Citizen): string {
    if (citizen.suspended_at) {
        return 'bg-rose-500/10 text-rose-500';
    }

    return citizen.is_verified ? 'bg-emerald-500/10 text-emerald-500' : 'bg-amber-500/10 text-amber-500';
}

function citizenStatusLabel(citizen: Citizen): string {
    if (citizen.suspended_at) {
        return 'Suspended';
    }

    return citizen.is_verified ? 'Verified' : 'Unverified';
}

function submitFilters(): void {
    const query: Record<string, string> = {};

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.county && filterForm.county !== 'all') {
        query.county = filterForm.county;
    }

    if (filterForm.search) {
        query.search = filterForm.search;
    }

    router.get(
        citizenRoutes.index.url({ query }),
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
    filterForm.county = 'all';
    filterForm.search = '';
    submitFilters();
}

const actionOptions = (citizen: Citizen) => {
    const options: Array<{ key: 'verify' | 'unverify' | 'suspend' | 'restore'; label: string }> = [];

    if (citizen.is_verified) {
        options.push({ key: 'unverify', label: 'Mark as unverified' });
    } else {
        options.push({ key: 'verify', label: 'Verify citizen' });
    }

    if (citizen.suspended_at) {
        options.push({ key: 'restore', label: 'Restore access' });
    } else {
        options.push({ key: 'suspend', label: 'Suspend account' });
    }

    return options;
};
</script>

<template>
    <Head title="Citizens" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-2">
                <h1 class="text-3xl font-semibold tracking-tight text-foreground">Citizen registry</h1>
                <p class="text-sm text-muted-foreground">
                    Oversee participation accounts, complete verification reviews, and respond to emerging support needs.
                </p>
            </header>

            <section class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                <article
                    v-for="card in metricCards"
                    :key="card.key"
                    class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm transition hover:border-primary/40 dark:border-sidebar-border"
                >
                    <p class="text-sm font-medium text-muted-foreground">{{ card.label }}</p>
                    <p class="mt-2 text-3xl font-semibold text-foreground">{{ card.value }}</p>
                    <p class="mt-1 text-xs text-muted-foreground">{{ card.helper }}</p>
                </article>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <form class="grid gap-4 md:grid-cols-4 md:items-end" @submit.prevent="submitFilters">
                    <div class="grid gap-2">
                        <Label for="status">Verification status</Label>
                        <select
                            id="status"
                            v-model="filterForm.status"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All citizens</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>
                    <div class="grid gap-2">
                        <Label for="county">County</Label>
                        <select
                            id="county"
                            v-model="filterForm.county"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All counties</option>
                            <option v-for="county in props.counties" :key="county" :value="county">{{ county }}</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <div class="grid gap-2">
                            <Label for="search">Search</Label>
                            <Input id="search" v-model="filterForm.search" type="search" placeholder="Name, email, phone, or ID number" />
                        </div>
                    </div>
                    <div class="col-span-full flex items-center justify-end gap-2 md:col-span-4">
                        <Button type="submit">Apply filters</Button>
                        <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                    </div>
                </form>
            </section>

            <section class="grid gap-6 lg:grid-cols-[minmax(0,1fr)_300px] xl:grid-cols-[minmax(0,1fr)_360px]">
                <div class="rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                    <header class="flex items-center justify-between gap-3 border-b border-sidebar-border/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                        <p v-if="resultSummary" class="text-xs uppercase tracking-wide">
                            Showing {{ resultSummary.from }} - {{ resultSummary.to }} of {{ resultSummary.total }} citizens
                        </p>
                        <p v-else class="text-xs uppercase tracking-wide">Citizen directory</p>
                    </header>

                    <div v-if="hasCitizens" class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-sidebar-border/60 text-sm dark:divide-sidebar-border">
                            <thead class="text-left text-xs uppercase tracking-wide text-muted-foreground">
                                <tr>
                                    <th scope="col" class="px-4 py-3 font-medium">Citizen</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Location</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Participation</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Status</th>
                                    <th scope="col" class="px-4 py-3 font-medium">Activity</th>
                                    <th scope="col" class="px-4 py-3 font-medium text-right">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-sidebar-border/40 dark:divide-sidebar-border">
                                <tr v-for="citizen in props.citizens.data" :key="citizen.id" class="align-top">
                                    <td class="px-4 py-4">
                                        <div class="space-y-1">
                                            <div class="font-medium text-foreground">{{ citizen.name }}</div>
                                            <div class="text-xs text-muted-foreground">{{ citizen.email }}</div>
                                            <div v-if="citizen.phone" class="text-xs text-muted-foreground">{{ citizen.phone }}</div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-muted-foreground">
                                        <p>{{ citizen.county ?? 'County TBD' }}</p>
                                        <p v-if="citizen.constituency" class="mt-0.5">{{ citizen.constituency }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-muted-foreground">
                                        <p class="font-medium text-foreground">{{ citizen.submissions_count ?? 0 }} submissions</p>
                                        <p>Joined {{ formatDate(citizen.created_at) }}</p>
                                    </td>
                                    <td class="px-4 py-4">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="citizenStatusClasses(citizen)"
                                        >
                                            <CheckCircle2 v-if="citizen.is_verified && !citizen.suspended_at" class="h-3.5 w-3.5" />
                                            <ShieldQuestion v-else-if="!citizen.is_verified && !citizen.suspended_at" class="h-3.5 w-3.5" />
                                            <Ban v-else class="h-3.5 w-3.5" />
                                            {{ citizenStatusLabel(citizen) }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-4 text-xs text-muted-foreground">
                                        <p>Last active: {{ formatDate(citizen.last_active_at) }}</p>
                                    </td>
                                    <td class="px-4 py-4 text-right">
                                        <DropdownMenu>
                                            <DropdownMenuTrigger as-child>
                                                <Button variant="ghost" size="icon" class="h-8 w-8">
                                                    <EllipsisVertical class="h-4 w-4" />
                                                </Button>
                                            </DropdownMenuTrigger>
                                            <DropdownMenuContent align="end" class="w-48">
                                                <DropdownMenuItem
                                                    v-for="option in actionOptions(citizen)"
                                                    :key="option.key"
                                                    :as-child="true"
                                                >
                                                    <button
                                                        type="button"
                                                        class="flex w-full items-center gap-2"
                                                        @click="openStatusDialog(citizen, option.key)"
                                                    >
                                                        <UserCheck v-if="option.key === 'verify'" class="h-4 w-4" />
                                                        <ShieldQuestion v-else-if="option.key === 'unverify'" class="h-4 w-4" />
                                                        <Ban v-else-if="option.key === 'suspend'" class="h-4 w-4" />
                                                        <RefreshCcw v-else class="h-4 w-4" />
                                                        {{ option.label }}
                                                    </button>
                                                </DropdownMenuItem>
                                                <DropdownMenuSeparator />
                                                <DropdownMenuItem :as-child="true">
                                                    <Link
                                                        class="flex w-full items-center gap-2"
                                                        :href="submissionRoutes.index.url({ query: { citizen_id: citizen.id.toString() } })"
                                                    >
                                                        <CalendarClock class="h-4 w-4" />
                                                        View submissions
                                                    </Link>
                                                </DropdownMenuItem>
                                            </DropdownMenuContent>
                                        </DropdownMenu>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div
                        v-else
                        class="flex min-h-[200px] flex-col items-center justify-center gap-2 px-6 py-10 text-center text-muted-foreground"
                    >
                        <p class="text-sm">No citizens match these filters yet.</p>
                        <p class="text-xs">Adjust the filters or invite residents to onboard through outreach.</p>
                    </div>
                </div>

                <aside class="flex flex-col gap-4">
                    <div class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                        <h2 class="text-sm font-semibold text-foreground">Recent submissions</h2>
                        <p class="mt-1 text-xs text-muted-foreground">Latest activity from verified and pending citizens.</p>
                        <ul class="mt-4 space-y-3">
                            <li v-for="submission in props.recentSubmissions" :key="submission.id" class="rounded-lg bg-muted/50 p-3 text-xs">
                                <div class="flex items-center justify-between gap-2 text-foreground">
                                    <span class="font-medium">{{ submission.tracking_id }}</span>
                                    <span class="uppercase tracking-wide text-muted-foreground">{{ submission.submission_type }}</span>
                                </div>
                                <p class="mt-1 text-muted-foreground">
                                    {{ submission.bill?.title ?? 'Bill pending association' }}
                                </p>
                                <p class="mt-1 text-muted-foreground">{{ formatDate(submission.created_at) }}</p>
                                <Link
                                    :href="submissionRoutes.show({ submission: submission.id }).url"
                                    class="mt-2 inline-flex items-center gap-1 text-[11px] font-medium text-primary hover:underline"
                                >
                                    View details
                                </Link>
                            </li>
                        </ul>
                        <p v-if="props.recentSubmissions.length === 0" class="mt-2 text-xs text-muted-foreground">
                            No submissions logged in the past few days.
                        </p>
                    </div>
                </aside>
            </section>

            <nav v-if="hasCitizens && props.citizens.links.length > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="link in props.citizens.links"
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

        <Dialog v-model:open="statusDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>{{ actionLabel }}</DialogTitle>
                    <DialogDescription>{{ actionDescription }}</DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="submitStatus">
                    <div>
                        <p class="text-sm font-medium text-foreground">{{ selectedCitizen?.name }}</p>
                        <p class="text-xs text-muted-foreground">{{ selectedCitizen?.email }}</p>
                    </div>
                    <div class="grid gap-2">
                        <Label for="action-reason">Internal note (optional)</Label>
                        <textarea
                            id="action-reason"
                            v-model="statusForm.reason"
                            rows="4"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Add context for other clerks handling this citizen's account"
                        ></textarea>
                        <InputError :message="statusForm.errors.reason" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="statusDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="statusForm.processing">
                            <LoaderCircle v-if="statusForm.processing" class="mr-2 h-4 w-4 animate-spin" />
                            Confirm action
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
