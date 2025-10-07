<script setup lang="ts">
import Button from '@/components/ui/button/Button.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SubmissionUser {
    id: number;
    name: string;
}

interface SubmissionItem {
    id: number;
    tracking_id: string;
    submission_type: string;
    status: string;
    created_at: string;
    content: string;
    user?: SubmissionUser | null;
}

interface BillSummary {
    simplified_summary_en: string | null;
    simplified_summary_sw: string | null;
    key_clauses: string[];
    generated_at: string | null;
}

interface BillDetail {
    id: number;
    title: string;
    bill_number: string;
    description: string;
    tags: string[] | null;
    type: string;
    house: string;
    status: string;
    sponsor: string | null;
    committee: string | null;
    gazette_date: string | null;
    participation_start_date: string | null;
    participation_end_date: string | null;
    pdf_path?: string | null;
    summary?: BillSummary | null;
    submissions: SubmissionItem[];
    submissions_count: number;
    views_count: number;
}

interface Props {
    bill: BillDetail;
    clauses: any; // Deferred from backend
    submissions: any; // Deferred from backend
    analytics: any; // Deferred from backend
    sentiment?: any; // Sentiment analysis data
    canEdit: boolean;
    canDelete: boolean;
}

const props = defineProps<Props>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Bills', href: billRoutes.index().url },
    { title: props.bill.title, href: billRoutes.show({ bill: props.bill.id }).url },
]);

const destroyForm = useForm({});

const handleDelete = () => {
    if (!confirm('Are you sure you want to delete this bill? This action cannot be undone.')) {
        return;
    }

    destroyForm.delete(billRoutes.destroy({ bill: props.bill.id }).url, {
        preserveScroll: true,
    });
};

const triggerSummary = () => {
    router.post(
        billRoutes.summary({ bill: props.bill.id }).url,
        {},
        {
            preserveScroll: true,
        },
    );
};

const statusLabel = (value: string) => value.split('_').join(' ');

const submissionStatusBadge = (status: string) => {
    switch (status) {
        case 'included':
            return 'bg-emerald-100 text-emerald-700';
        case 'rejected':
            return 'bg-rose-100 text-rose-700';
        case 'reviewed':
            return 'bg-blue-100 text-blue-700';
        default:
            return 'bg-amber-100 text-amber-700';
    }
};

const pdfUrl = computed(() => {
    if (!props.bill.pdf_path) {
        return null;
    }

    return `/storage/${props.bill.pdf_path}`;
});
</script>

<template>
    <Head :title="props.bill.title" />

    <PublicLayout :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-12 md:px-6">
            <div class="flex flex-col gap-4 rounded-3xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-400 p-8 text-white shadow-lg">
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-sm text-white/80">Bill {{ props.bill.bill_number }}</p>
                        <h1 class="text-3xl font-semibold md:text-4xl">{{ props.bill.title }}</h1>
                    </div>
                    <div class="flex flex-wrap items-center gap-3">
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide text-white uppercase">
                            {{ statusLabel(props.bill.status) }}
                        </span>
                        <span class="rounded-full bg-white/15 px-3 py-1 text-xs font-semibold tracking-wide text-white uppercase">
                            {{ statusLabel(props.bill.house) }}
                        </span>
                    </div>
                </header>

                <div class="grid gap-4 md:grid-cols-2">
                    <div class="space-y-3 text-sm text-white/90">
                        <p>{{ props.bill.description }}</p>
                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in props.bill.tags ?? []"
                                :key="tag"
                                class="rounded-full bg-white/15 px-3 py-1 text-xs tracking-wide text-white/90 uppercase"
                            >
                                {{ tag }}
                            </span>
                        </div>
                    </div>

                    <dl class="grid gap-3 text-sm text-white/90">
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Sponsor</dt>
                            <dd class="text-white">{{ props.bill.sponsor ?? 'Not specified' }}</dd>
                        </div>
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Committee</dt>
                            <dd class="text-white">{{ props.bill.committee ?? 'Not assigned' }}</dd>
                        </div>
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Gazetted on</dt>
                            <dd class="text-white">{{ props.bill.gazette_date ?? 'Pending' }}</dd>
                        </div>
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Participation window</dt>
                            <dd class="text-end text-white">
                                <span class="block">{{ props.bill.participation_start_date ?? '—' }}</span>
                                <span class="block">to {{ props.bill.participation_end_date ?? '—' }}</span>
                            </dd>
                        </div>
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Views</dt>
                            <dd class="text-white">{{ props.bill.views_count }}</dd>
                        </div>
                        <div class="flex justify-between rounded-lg bg-white/10 px-4 py-2">
                            <dt class="text-white/70">Total submissions</dt>
                            <dd class="text-white">{{ props.bill.submissions_count }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="flex flex-wrap gap-3">
                    <Link
                        v-if="pdfUrl"
                        :href="pdfUrl"
                        class="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 text-sm font-semibold text-white transition hover:border-white hover:bg-white/10"
                        target="_blank"
                        rel="noopener"
                    >
                        Download bill PDF
                    </Link>
                    <Link
                        :href="submissionRoutes.create.url({ query: { bill_id: props.bill.id } })"
                        class="inline-flex items-center gap-2 rounded-full bg-white px-5 py-2 text-sm font-semibold text-emerald-700 shadow-sm transition hover:bg-emerald-50"
                    >
                        Submit feedback
                    </Link>

                    <Button
                        v-if="props.canEdit"
                        type="button"
                        variant="secondary"
                        class="rounded-full border border-white/30 bg-white/10 text-white hover:bg-white/20"
                        @click="triggerSummary"
                    >
                        Regenerate summary
                    </Button>

                    <Link
                        v-if="props.canEdit"
                        :href="billRoutes.edit({ bill: props.bill.id }).url"
                        class="inline-flex items-center gap-2 rounded-full border border-white/40 px-4 py-2 text-sm font-semibold text-white transition hover:border-white hover:bg-white/10"
                    >
                        Edit bill
                    </Link>

                    <Button
                        v-if="props.canDelete"
                        type="button"
                        variant="destructive"
                        class="rounded-full bg-rose-600/90 text-white hover:bg-rose-700"
                        :disabled="destroyForm.processing"
                        @click="handleDelete"
                    >
                        Delete bill
                    </Button>
                </div>
            </div>

            <section v-if="props.bill.summary" class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <header class="mb-4 flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-emerald-900">Simplified summary</h2>
                    <span class="text-xs text-emerald-800/70">Generated {{ props.bill.summary.generated_at ?? 'recently' }}</span>
                </header>
                <p class="text-sm text-emerald-800/80">{{ props.bill.summary.simplified_summary_en }}</p>

                <div v-if="props.bill.summary.key_clauses.length" class="mt-4 space-y-2">
                    <h3 class="text-sm font-semibold text-emerald-900">Key clauses</h3>
                    <ul class="space-y-2 text-sm text-emerald-800/80">
                        <li v-for="clause in props.bill.summary.key_clauses" :key="clause" class="flex gap-2">
                            <span class="mt-1 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                            <span>{{ clause }}</span>
                        </li>
                    </ul>
                </div>
            </section>

            <section class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <header class="flex items-center justify-between">
                    <h2 class="text-xl font-semibold text-emerald-900">Recent submissions</h2>
                    <Link
                        :href="submissionRoutes.index().url"
                        class="text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline"
                    >
                        View all submissions
                    </Link>
                </header>

                <div v-if="props.bill.submissions.length" class="mt-4 space-y-4">
                    <article
                        v-for="submission in props.bill.submissions"
                        :key="submission.id"
                        class="rounded-xl border border-emerald-100/70 bg-emerald-50/70 p-4 text-sm"
                    >
                        <header class="flex flex-wrap items-center justify-between gap-3">
                            <div class="flex items-center gap-3">
                                <span class="rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold tracking-wide text-emerald-700 uppercase">
                                    {{ statusLabel(submission.submission_type) }}
                                </span>
                                <span class="rounded-full px-3 py-1 text-xs font-semibold" :class="submissionStatusBadge(submission.status)">
                                    {{ statusLabel(submission.status) }}
                                </span>
                            </div>
                            <span class="text-xs text-emerald-800/70">{{ submission.created_at }}</span>
                        </header>
                        <p class="mt-3 text-emerald-800/80">{{ submission.content }}</p>
                        <footer class="mt-3 flex items-center justify-between text-xs text-emerald-800/70">
                            <span>Tracking ID: {{ submission.tracking_id }}</span>
                            <span>{{ submission.user?.name ?? 'Citizen' }}</span>
                        </footer>
                    </article>
                </div>

                <p v-else class="mt-4 text-sm text-emerald-800/70">No submissions yet. Be the first to participate.</p>
            </section>

            <!-- Sentiment Analysis Section -->
            <section class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <SentimentVisualization :data="sentiment" :loading="false" :bill-title="bill.title" />
            </section>

            <!-- Clause Reader Section -->
            <section class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <header class="mb-4">
                    <h2 class="text-xl font-semibold text-emerald-900">Read and Comment on Clauses</h2>
                    <p class="text-sm text-emerald-800/80">Review each clause and share your thoughts</p>
                </header>

                <Suspense>
                    <ClauseReader :bill="bill" :clauses="clauses" :can-comment="true" />
                    <template #fallback>
                        <div class="flex items-center justify-center p-8">
                            <div class="h-8 w-8 animate-spin rounded-full border-b-2 border-emerald-600"></div>
                            <span class="ml-2 text-sm text-muted-foreground">Loading clauses...</span>
                        </div>
                    </template>
                </Suspense>
            </section>
        </div>
    </PublicLayout>
</template>
