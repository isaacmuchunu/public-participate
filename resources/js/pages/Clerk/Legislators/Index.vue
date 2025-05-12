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
    DialogTrigger,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Checkbox } from '@/components/ui/checkbox';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed, reactive, ref, watch } from 'vue';
import {
    Ban,
    CalendarClock,
    CheckCircle2,
    EllipsisVertical,
    MailCheck,
    PencilLine,
    PhoneForwarded,
    RefreshCcw,
    UserPlus,
} from 'lucide-vue-next';
import * as legislatorRoutes from '@/routes/clerk/legislators';

interface LegislatorStatus {
    is_suspended: boolean;
    suspended_at: string | null;
    is_verified: boolean;
}

interface LegislatorInvitation {
    sent_at: string | null;
    expires_at: string | null;
    status: string;
    token: string | null;
}

interface Legislator {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    legislative_house: 'national_assembly' | 'senate';
    county: string | null;
    constituency: string | null;
    status: LegislatorStatus;
    invitation: LegislatorInvitation;
    invited_by?: {
        id: number;
        name: string;
        email: string;
    } | null;
    last_active_at: string | null;
    created_at: string;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface LegislatorCollectionMeta {
    from?: number | null;
    to?: number | null;
    total?: number;
}

interface Props {
    legislators: {
        data: Legislator[];
        links: PaginationLink[];
        meta?: LegislatorCollectionMeta;
    };
    filters: {
        house?: string | null;
        status?: string | null;
        search?: string | null;
    };
    metrics: {
        total: number;
        active: number;
        pending: number;
        suspended: number;
        expiring: number;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Clerk workspace', href: '/dashboard' },
    { title: 'Legislators', href: legislatorRoutes.index().url },
];

const filterForm = reactive({
    house: props.filters?.house ?? 'all',
    status: props.filters?.status ?? 'all',
    search: props.filters?.search ?? '',
});

const metricCards = computed(() => [
    {
        key: 'total',
        label: 'Total onboarded',
        value: props.metrics.total ?? 0,
        helper: 'Across both houses',
    },
    {
        key: 'active',
        label: 'Activated accounts',
        value: props.metrics.active ?? 0,
        helper: 'Verified & signed in',
    },
    {
        key: 'pending',
        label: 'Pending activation',
        value: props.metrics.pending ?? 0,
        helper: 'Awaiting account setup',
    },
    {
        key: 'expiring',
        label: 'Expiring invitations',
        value: props.metrics.expiring ?? 0,
        helper: 'Expiring within 3 days',
    },
]);

const hasLegislators = computed(() => props.legislators.data.length > 0);

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

function invitationStatusBadge(status: string): string {
    switch (status) {
        case 'active':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300';
        case 'pending':
            return 'bg-amber-100 text-amber-700 dark:bg-amber-500/15 dark:text-amber-300';
        case 'expired':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300';
        case 'suspended':
            return 'bg-slate-200 text-slate-700 dark:bg-slate-500/20 dark:text-slate-200';
        default:
            return 'bg-muted text-muted-foreground';
    }
}

function invitationStatusLabel(status: string): string {
    return status.replace(/_/g, ' ');
}

function houseLabel(house: Legislator['legislative_house']): string {
    return house === 'senate' ? 'Senate' : 'National Assembly';
}

const paginationLabel = (label: string) => label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');

const resultSummary = computed(() => {
    const meta = props.legislators.meta;

    if (!meta) {
        return null;
    }

    const from = meta.from ?? (hasLegislators.value ? 1 : 0);
    const to = meta.to ?? props.legislators.data.length;
    const total = meta.total ?? props.legislators.data.length;

    return { from, to, total };
});

const defaultInvitationMessage = `Habari mheshimiwa,\n\nYou have been invited to onboard onto the Public Participation System portal. Activate your account within 7 days to access upcoming committee briefs and share highlights.\n\nAsante.`;

const invitationDialogOpen = ref(false);

const invitationFormDefaults = () => ({
    name: '',
    email: '',
    phone: '',
    legislative_house: 'national_assembly' as Legislator['legislative_house'],
    county: '',
    constituency: '',
    invitation_message: defaultInvitationMessage,
    expires_in_days: 7,
});

const invitationForm = useForm(invitationFormDefaults());

watch(
    () => invitationDialogOpen.value,
    (isOpen) => {
        if (!isOpen) {
            invitationForm.defaults(invitationFormDefaults());
            invitationForm.reset();
            invitationForm.clearErrors();
        }
    },
);

function handleInvitationSubmit(): void {
    invitationForm
        .transform((data) => ({
            ...data,
            phone: data.phone ? data.phone : null,
            county: data.county ? data.county : null,
            constituency: data.constituency ? data.constituency : null,
            invitation_message: data.invitation_message ? data.invitation_message : null,
            expires_in_days: data.expires_in_days ? Number(data.expires_in_days) : null,
        }))
        .post(legislatorRoutes.store().url, {
            preserveScroll: true,
            onSuccess: () => {
                invitationForm.defaults(invitationFormDefaults());
                invitationForm.reset();
                invitationDialogOpen.value = false;
            },
        });
}

const selectedLegislator = ref<Legislator | null>(null);

const editDialogOpen = ref(false);

const editFormDefaults = () => ({
    name: '',
    email: '',
    phone: '',
    county: '',
    constituency: '',
    legislative_house: 'national_assembly' as Legislator['legislative_house'],
    reset_invitation: false,
    invitation_message: '',
    expires_in_days: 7,
});

const editForm = useForm(editFormDefaults());

function openEditDialog(legislator: Legislator): void {
    selectedLegislator.value = legislator;

    editForm.defaults({
        name: legislator.name ?? '',
        email: legislator.email ?? '',
        phone: legislator.phone ?? '',
        county: legislator.county ?? '',
        constituency: legislator.constituency ?? '',
        legislative_house: legislator.legislative_house,
        reset_invitation: false,
        invitation_message: defaultInvitationMessage,
        expires_in_days: 7,
    });

    editForm.reset();
    editForm.clearErrors();
    editDialogOpen.value = true;
}

watch(
    () => editDialogOpen.value,
    (isOpen) => {
        if (!isOpen) {
            selectedLegislator.value = null;
            editForm.defaults(editFormDefaults());
            editForm.reset();
            editForm.clearErrors();
        }
    },
);

function handleEditSubmit(): void {
    if (!selectedLegislator.value) {
        return;
    }

    editForm
        .transform((data) => ({
            ...data,
            phone: data.phone ? data.phone : null,
            county: data.county ? data.county : null,
            constituency: data.constituency ? data.constituency : null,
            invitation_message: data.reset_invitation && data.invitation_message ? data.invitation_message : null,
            expires_in_days: data.reset_invitation && data.expires_in_days ? Number(data.expires_in_days) : null,
        }))
        .patch(legislatorRoutes.update({ legislator: selectedLegislator.value.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                editDialogOpen.value = false;
            },
        });
}

const resendDialogOpen = ref(false);

const resendFormDefaults = () => ({
    invitation_message: defaultInvitationMessage,
    expires_in_days: 7,
});

const resendForm = useForm(resendFormDefaults());

function openResendDialog(legislator: Legislator): void {
    selectedLegislator.value = legislator;
    resendForm.defaults(resendFormDefaults());
    resendForm.reset();
    resendForm.clearErrors();
    resendDialogOpen.value = true;
}

watch(
    () => resendDialogOpen.value,
    (isOpen) => {
        if (!isOpen) {
            selectedLegislator.value = null;
            resendForm.defaults(resendFormDefaults());
            resendForm.reset();
            resendForm.clearErrors();
        }
    },
);

function handleResendSubmit(): void {
    if (!selectedLegislator.value) {
        return;
    }

    resendForm
        .transform((data) => ({
            ...data,
            invitation_message: data.invitation_message ? data.invitation_message : null,
            expires_in_days: data.expires_in_days ? Number(data.expires_in_days) : null,
        }))
        .post(legislatorRoutes.resend({ legislator: selectedLegislator.value.id }).url, {
            preserveScroll: true,
            onSuccess: () => {
                resendDialogOpen.value = false;
            },
        });
}

function suspendLegislator(legislator: Legislator): void {
    if (!window.confirm(`Suspend ${legislator.name}? Their access will be paused immediately.`)) {
        return;
    }

    router.delete(legislatorRoutes.destroy({ legislator: legislator.id }).url, {
        preserveScroll: true,
    });
}

function restoreLegislator(legislator: Legislator): void {
    router.patch(legislatorRoutes.restore({ legislator: legislator.id }).url, {}, {
        preserveScroll: true,
    });
}

function submitFilters(): void {
    const query: Record<string, string> = {};

    if (filterForm.house && filterForm.house !== 'all') {
        query.house = filterForm.house;
    }

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.search) {
        query.search = filterForm.search;
    }

    router.get(
        legislatorRoutes.index.url({ query }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
}

function resetFilters(): void {
    filterForm.house = 'all';
    filterForm.status = 'all';
    filterForm.search = '';
    submitFilters();
}
</script>

<template>
    <Head title="Legislators" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
                <div class="space-y-2">
                    <h1 class="text-3xl font-semibold tracking-tight text-foreground">Legislator onboarding</h1>
                    <p class="text-sm text-muted-foreground">
                        Manage invitations, track activation progress, and keep parliamentary contacts up to date.
                    </p>
                </div>

                <Dialog v-model:open="invitationDialogOpen">
                    <DialogTrigger as-child>
                        <Button class="self-start">
                            <UserPlus class="mr-2 h-4 w-4" />
                            Invite legislator
                        </Button>
                    </DialogTrigger>
                    <DialogContent class="sm:max-w-xl">
                        <DialogHeader>
                            <DialogTitle>Invite a legislator</DialogTitle>
                            <DialogDescription>
                                Send an onboarding link with optional personalised messaging and activation deadline.
                            </DialogDescription>
                        </DialogHeader>
                        <form class="grid gap-4" @submit.prevent="handleInvitationSubmit">
                            <div class="grid gap-2">
                                <Label for="invite-name">Full name</Label>
                                <Input id="invite-name" v-model="invitationForm.name" type="text" placeholder="Hon. Jane Mwangi" />
                                <InputError :message="invitationForm.errors.name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="invite-email">Email</Label>
                                <Input id="invite-email" v-model="invitationForm.email" type="email" placeholder="jane.mwangi@parliament.go.ke" />
                                <InputError :message="invitationForm.errors.email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="invite-phone">Phone (optional)</Label>
                                <Input id="invite-phone" v-model="invitationForm.phone" type="tel" placeholder="0712 000 000" />
                                <InputError :message="invitationForm.errors.phone" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="invite-house">House assignment</Label>
                                <select
                                    id="invite-house"
                                    v-model="invitationForm.legislative_house"
                                    class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="national_assembly">National Assembly</option>
                                    <option value="senate">Senate</option>
                                </select>
                                <InputError :message="invitationForm.errors.legislative_house" />
                            </div>
                            <div class="grid gap-2 md:grid-cols-2">
                                <div class="grid gap-2">
                                    <Label for="invite-county">County (optional)</Label>
                                    <Input id="invite-county" v-model="invitationForm.county" type="text" placeholder="Nairobi" />
                                    <InputError :message="invitationForm.errors.county" />
                                </div>
                                <div class="grid gap-2">
                                    <Label for="invite-constituency">Constituency (optional)</Label>
                                    <Input id="invite-constituency" v-model="invitationForm.constituency" type="text" placeholder="Westlands" />
                                    <InputError :message="invitationForm.errors.constituency" />
                                </div>
                            </div>
                            <div class="grid gap-2">
                                <Label for="invite-message">Invitation message</Label>
                                <textarea
                                    id="invite-message"
                                    v-model="invitationForm.invitation_message"
                                    rows="4"
                                    class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                ></textarea>
                                <InputError :message="invitationForm.errors.invitation_message" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="invite-expiry">Expiry (days)</Label>
                                <Input id="invite-expiry" v-model="invitationForm.expires_in_days" type="number" min="1" max="30" />
                                <InputError :message="invitationForm.errors.expires_in_days" />
                            </div>
                            <DialogFooter class="mt-2">
                                <Button type="button" variant="ghost" @click="invitationDialogOpen = false">Cancel</Button>
                                <Button type="submit" :disabled="invitationForm.processing">
                                    <MailCheck class="mr-2 h-4 w-4" />
                                    Send invitation
                                </Button>
                            </DialogFooter>
                        </form>
                    </DialogContent>
                </Dialog>
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
                <form class="grid gap-4 md:grid-cols-[1fr_1fr_auto]" @submit.prevent="submitFilters">
                    <div class="grid gap-2">
                        <Label for="filter-house">House</Label>
                        <select
                            id="filter-house"
                            v-model="filterForm.house"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All houses</option>
                            <option value="national_assembly">National Assembly</option>
                            <option value="senate">Senate</option>
                        </select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="filter-status">Status</Label>
                        <select
                            id="filter-status"
                            v-model="filterForm.status"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All statuses</option>
                            <option value="active">Activated</option>
                            <option value="pending">Pending</option>
                            <option value="expired">Expired</option>
                            <option value="suspended">Suspended</option>
                        </select>
                    </div>

                    <div class="grid gap-2 md:col-span-1 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                        <div class="grid gap-2">
                            <Label for="filter-search">Quick search</Label>
                            <Input
                                id="filter-search"
                                v-model="filterForm.search"
                                type="search"
                                placeholder="Search by name, email, or constituency"
                            />
                        </div>
                        <div class="flex items-center gap-2 md:justify-end">
                            <Button type="submit">Filter</Button>
                            <Button type="button" variant="outline" @click="resetFilters">Reset</Button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card shadow-sm dark:border-sidebar-border">
                <header class="flex items-center justify-between gap-3 border-b border-sidebar-border/60 px-4 py-3 text-sm text-muted-foreground dark:border-sidebar-border">
                    <p v-if="resultSummary" class="text-xs uppercase tracking-wide">
                        Showing {{ resultSummary.from }} - {{ resultSummary.to }} of {{ resultSummary.total }} legislators
                    </p>
                    <p v-else class="text-xs uppercase tracking-wide">Legislator directory</p>
                </header>

                <div v-if="hasLegislators" class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-sidebar-border/60 text-sm dark:divide-sidebar-border">
                        <thead class="text-left text-xs uppercase tracking-wide text-muted-foreground">
                            <tr>
                                <th scope="col" class="px-4 py-3 font-medium">Legislator</th>
                                <th scope="col" class="px-4 py-3 font-medium">Jurisdiction</th>
                                <th scope="col" class="px-4 py-3 font-medium">Status</th>
                                <th scope="col" class="px-4 py-3 font-medium">Invitation</th>
                                <th scope="col" class="px-4 py-3 font-medium">Activity</th>
                                <th scope="col" class="px-4 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-sidebar-border/40 dark:divide-sidebar-border">
                            <tr v-for="legislator in props.legislators.data" :key="legislator.id" class="align-top">
                                <td class="px-4 py-4">
                                    <div class="space-y-1">
                                        <div class="font-medium text-foreground">{{ legislator.name }}</div>
                                        <div class="text-xs text-muted-foreground">{{ legislator.email }}</div>
                                        <div v-if="legislator.phone" class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <PhoneForwarded class="h-3.5 w-3.5" />
                                            {{ legislator.phone }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-col gap-1">
                                        <span class="inline-flex w-max rounded-full bg-muted px-2 py-0.5 text-xs font-medium text-muted-foreground">
                                            {{ houseLabel(legislator.legislative_house) }}
                                        </span>
                                        <span class="text-xs text-muted-foreground">
                                            {{ legislator.county || 'County TBD' }}
                                            <span v-if="legislator.constituency"> · {{ legislator.constituency }}</span>
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span
                                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="legislator.status.is_verified
                                                ? 'bg-emerald-500/10 text-emerald-500'
                                                : 'bg-amber-500/10 text-amber-500'"
                                        >
                                            <CheckCircle2 class="h-3.5 w-3.5" />
                                            {{ legislator.status.is_verified ? 'Verified' : 'Unverified' }}
                                        </span>
                                        <span
                                            v-if="legislator.status.is_suspended"
                                            class="inline-flex rounded-full bg-slate-500/10 px-2 py-0.5 text-xs font-medium text-slate-500"
                                        >
                                            <Ban class="mr-1 h-3.5 w-3.5" /> Suspended
                                        </span>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="space-y-1">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium capitalize"
                                            :class="invitationStatusBadge(legislator.invitation.status)"
                                        >
                                            {{ invitationStatusLabel(legislator.invitation.status) }}
                                        </span>
                                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <MailCheck class="h-3.5 w-3.5" />
                                            Sent {{ formatDate(legislator.invitation.sent_at) }}
                                        </div>
                                        <div class="flex items-center gap-1 text-xs text-muted-foreground">
                                            <CalendarClock class="h-3.5 w-3.5" />
                                            Expires {{ formatDate(legislator.invitation.expires_at) }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-4 py-4">
                                    <div class="space-y-1 text-xs text-muted-foreground">
                                        <p>Last seen: {{ formatDate(legislator.last_active_at) }}</p>
                                        <p>Invited: {{ formatDate(legislator.created_at) }}</p>
                                    </div>
                                </td>
                                <td class="px-4 py-4 text-right">
                                    <DropdownMenu>
                                        <DropdownMenuTrigger as-child>
                                            <Button variant="ghost" size="icon" class="h-8 w-8">
                                                <EllipsisVertical class="h-4 w-4" />
                                            </Button>
                                        </DropdownMenuTrigger>
                                        <DropdownMenuContent align="end" class="w-52">
                                            <DropdownMenuItem :as-child="true">
                                                <button type="button" class="flex w-full items-center gap-2" @click="openEditDialog(legislator)">
                                                    <PencilLine class="h-4 w-4" />
                                                    Edit profile
                                                </button>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem :as-child="true">
                                                <button type="button" class="flex w-full items-center gap-2" @click="openResendDialog(legislator)">
                                                    <MailCheck class="h-4 w-4" />
                                                    Resend invitation
                                                </button>
                                            </DropdownMenuItem>
                                            <DropdownMenuSeparator />
                                            <DropdownMenuItem
                                                v-if="!legislator.status.is_suspended"
                                                :as-child="true"
                                                variant="destructive"
                                            >
                                                <button type="button" class="flex w-full items-center gap-2" @click="suspendLegislator(legislator)">
                                                    <Ban class="h-4 w-4" />
                                                    Suspend access
                                                </button>
                                            </DropdownMenuItem>
                                            <DropdownMenuItem v-else :as-child="true">
                                                <button type="button" class="flex w-full items-center gap-2" @click="restoreLegislator(legislator)">
                                                    <RefreshCcw class="h-4 w-4" />
                                                    Reinstate access
                                                </button>
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
                    <p class="text-sm">No legislators match these filters yet.</p>
                    <p class="text-xs">Try adjusting the filters or inviting a new legislator.</p>
                </div>
            </section>

            <nav v-if="hasLegislators && props.legislators.links.length > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="link in props.legislators.links"
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

        <Dialog v-model:open="editDialogOpen">
            <DialogContent class="sm:max-w-xl">
                <DialogHeader>
                    <DialogTitle>Edit legislator details</DialogTitle>
                    <DialogDescription>Update contact information or resend the onboarding link.</DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="handleEditSubmit">
                    <div class="grid gap-2">
                        <Label for="edit-name">Full name</Label>
                        <Input id="edit-name" v-model="editForm.name" type="text" />
                        <InputError :message="editForm.errors.name" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-email">Email</Label>
                        <Input id="edit-email" v-model="editForm.email" type="email" />
                        <InputError :message="editForm.errors.email" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-phone">Phone</Label>
                        <Input id="edit-phone" v-model="editForm.phone" type="tel" />
                        <InputError :message="editForm.errors.phone" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="edit-house">House</Label>
                        <select
                            id="edit-house"
                            v-model="editForm.legislative_house"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="national_assembly">National Assembly</option>
                            <option value="senate">Senate</option>
                        </select>
                        <InputError :message="editForm.errors.legislative_house" />
                    </div>
                    <div class="grid gap-2 md:grid-cols-2">
                        <div class="grid gap-2">
                            <Label for="edit-county">County</Label>
                            <Input id="edit-county" v-model="editForm.county" type="text" />
                            <InputError :message="editForm.errors.county" />
                        </div>
                        <div class="grid gap-2">
                            <Label for="edit-constituency">Constituency</Label>
                            <Input id="edit-constituency" v-model="editForm.constituency" type="text" />
                            <InputError :message="editForm.errors.constituency" />
                        </div>
                    </div>
                    <div class="rounded-lg border border-dashed border-sidebar-border/60 p-4 dark:border-sidebar-border">
                        <div class="flex items-start gap-3">
                            <Checkbox id="reset-invite" v-model:checked="editForm.reset_invitation" class="mt-1" />
                            <div class="space-y-1">
                                <Label for="reset-invite" class="cursor-pointer">Send new invitation link</Label>
                                <p class="text-xs text-muted-foreground">
                                    Generates a fresh token and dispatches a new email to the legislator.
                                </p>
                            </div>
                        </div>
                        <div v-if="editForm.reset_invitation" class="mt-4 grid gap-3">
                            <div class="grid gap-2">
                                <Label for="edit-message">Invitation message</Label>
                                <textarea
                                    id="edit-message"
                                    v-model="editForm.invitation_message"
                                    rows="4"
                                    class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                ></textarea>
                                <InputError :message="editForm.errors.invitation_message" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="edit-expiry">Expiry (days)</Label>
                                <Input id="edit-expiry" v-model="editForm.expires_in_days" type="number" min="1" max="30" />
                                <InputError :message="editForm.errors.expires_in_days" />
                            </div>
                        </div>
                    </div>
                    <DialogFooter class="mt-2">
                        <Button type="button" variant="ghost" @click="editDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="editForm.processing">
                            <PencilLine class="mr-2 h-4 w-4" />
                            Save changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="resendDialogOpen">
            <DialogContent class="sm:max-w-lg">
                <DialogHeader>
                    <DialogTitle>Resend invitation</DialogTitle>
                    <DialogDescription>Follow up with a new activation window and personalised message.</DialogDescription>
                </DialogHeader>
                <form class="grid gap-4" @submit.prevent="handleResendSubmit">
                    <div class="grid gap-2">
                        <Label for="resend-message">Message</Label>
                        <textarea
                            id="resend-message"
                            v-model="resendForm.invitation_message"
                            rows="4"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        ></textarea>
                        <InputError :message="resendForm.errors.invitation_message" />
                    </div>
                    <div class="grid gap-2">
                        <Label for="resend-expiry">Expiry (days)</Label>
                        <Input id="resend-expiry" v-model="resendForm.expires_in_days" type="number" min="1" max="30" />
                        <InputError :message="resendForm.errors.expires_in_days" />
                    </div>
                    <DialogFooter>
                        <Button type="button" variant="ghost" @click="resendDialogOpen = false">Cancel</Button>
                        <Button type="submit" :disabled="resendForm.processing">
                            <MailCheck class="mr-2 h-4 w-4" />
                            Resend now
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </AppLayout>
</template>
