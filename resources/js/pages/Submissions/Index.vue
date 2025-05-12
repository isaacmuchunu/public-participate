<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import Button from '@/components/ui/button/Button.vue';

interface SubmissionUser {
    id: number;
    name: string;
    email: string;
}

interface SubmissionBill {
    id: number;
    title: string;
    bill_number: string;
}

interface SubmissionItem {
    id: number;
    tracking_id: string;
    status: string;
    submission_type: string;
    language: string;
    created_at: string;
    user?: SubmissionUser | null;
    reviewer?: SubmissionUser | null;
    bill: SubmissionBill;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    submissions: {
        data: SubmissionItem[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        status?: string;
        type?: string;
        bill_id?: string;
    };
}

const props = defineProps<Props>();

const page = usePage<AppPageProps>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bills', href: billRoutes.index().url },
    { title: 'Submissions', href: submissionRoutes.index().url },
];

const filterForm = reactive({
    status: props.filters?.status ?? 'all',
    type: props.filters?.type ?? 'all',
    bill_id: props.filters?.bill_id ?? 'all',
});

const hasResults = computed(() => props.submissions.data.length > 0);

const availableBills = computed(() => {
    const unique = new Map<number, SubmissionBill>();

    props.submissions.data.forEach((submission) => {
        if (!unique.has(submission.bill.id)) {
            unique.set(submission.bill.id, submission.bill);
        }
    });

    return Array.from(unique.values());
});

const submitFilters = () => {
    const query: Record<string, string> = {};

    if (filterForm.status && filterForm.status !== 'all') {
        query.status = filterForm.status;
    }

    if (filterForm.type && filterForm.type !== 'all') {
        query.type = filterForm.type;
    }

    if (filterForm.bill_id && filterForm.bill_id !== 'all') {
        query.bill_id = filterForm.bill_id;
    }

    router.get(
        submissionRoutes.index.url({ query }),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.status = 'all';
    filterForm.type = 'all';
    filterForm.bill_id = 'all';
    submitFilters();
};

const badgeClass = (status: string) => {
    switch (status) {
        case 'included':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
        case 'rejected':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300';
        case 'reviewed':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const formatLabel = (value: string) => value.split('_').join(' ');

const isClerk = computed(() => page.props.auth.user.role !== 'citizen');

const paginationLabel = (label: string) =>
    label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
</script>

<template>
    <Head title="Submissions" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-2">
                <h1 class="text-3xl font-semibold text-foreground">Public submissions</h1>
                <p class="text-sm text-muted-foreground">
                    Monitor citizen feedback, review outstanding submissions, and track engagement per bill.
                </p>
            </header>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <form class="grid gap-4 md:grid-cols-4" @submit.prevent="submitFilters">
                    <div class="space-y-2">
                        <label for="status" class="text-sm font-medium text-foreground">Status</label>
                        <select
                            id="status"
                            v-model="filterForm.status"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All statuses</option>
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="included">Included</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="type" class="text-sm font-medium text-foreground">Submission type</label>
                        <select
                            id="type"
                            v-model="filterForm.type"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All types</option>
                            <option value="support">Support</option>
                            <option value="oppose">Oppose</option>
                            <option value="amend">Amend</option>
                            <option value="neutral">Neutral</option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label for="bill" class="text-sm font-medium text-foreground">Bill</label>
                        <select
                            id="bill"
                            v-model="filterForm.bill_id"
                            class="border-input text-foreground dark:bg-input/30 h-10 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="all">All bills</option>
                            <option v-for="bill in availableBills" :key="bill.id" :value="bill.id.toString()">
                                {{ bill.title }}
                            </option>
                        </select>
                    </div>

                    <div class="space-y-2">
                        <label class="text-sm font-medium text-transparent">Actions</label>
                        <div class="flex items-center gap-2">
                            <Button type="submit" class="h-10">Filter</Button>
                            <Button type="button" variant="outline" class="h-10" @click="resetFilters">Reset</Button>
                        </div>
                    </div>
                </form>
            </section>

            <section class="flex-1">
                <div v-if="hasResults" class="space-y-4">
                    <article
                        v-for="submission in props.submissions.data"
                        :key="submission.id"
                        class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm dark:border-sidebar-border"
                    >
                        <header class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-3">
                                <span class="rounded-full px-3 py-1 text-xs font-medium" :class="badgeClass(submission.status)">
                                    {{ formatLabel(submission.status) }}
                                </span>
                                <span class="rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
                                    {{ formatLabel(submission.submission_type) }} • {{ submission.language.toUpperCase() }}
                                </span>
                            </div>
                            <span class="text-xs text-muted-foreground">{{ submission.created_at }}</span>
                        </header>

                        <div class="mt-3 flex flex-col gap-2 text-sm text-muted-foreground">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="text-foreground">Bill:</span>
                                <Link
                                    :href="billRoutes.show({ bill: submission.bill.id }).url"
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    {{ submission.bill.title }}
                                </Link>
                                <span class="text-muted-foreground">({{ submission.bill.bill_number }})</span>
                            </div>
                            <div class="flex flex-wrap items-center gap-2 text-xs">
                                <span>Submitted by {{ submission.user?.name ?? 'Citizen' }}</span>
                                <span>•</span>
                                <span>Tracking ID: {{ submission.tracking_id }}</span>
                                <span v-if="submission.reviewer">• Reviewed by {{ submission.reviewer.name }}</span>
                            </div>
                        </div>

                        <footer class="mt-4 flex items-center justify-between">
                            <Link
                                :href="submissionRoutes.show({ submission: submission.id }).url"
                                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                            >
                                View submission
                            </Link>
                            <span v-if="isClerk" class="text-xs text-muted-foreground">Manage status from the detail view</span>
                        </footer>
                    </article>
                </div>

                <div v-else class="flex min-h-[200px] items-center justify-center rounded-xl border border-dashed border-sidebar-border/60 bg-card p-10 text-center text-muted-foreground dark:border-sidebar-border">
                    <p>No submissions match these filters.</p>
                </div>
            </section>

            <nav v-if="hasResults && props.submissions.links.length > 1" class="flex items-center justify-center gap-2">
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
        </div>
    </AppLayout>
</template>
