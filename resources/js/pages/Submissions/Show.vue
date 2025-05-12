<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import { store as storeEngagement } from '@/routes/submissions/engagements';
import type { AppPageProps, BreadcrumbItem } from '@/types';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';

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

interface SubmissionDetail {
    id: number;
    tracking_id: string;
    status: string;
    submission_type: string;
    content: string;
    language: string;
    channel: string;
    metadata?: Record<string, unknown> | null;
    created_at: string;
    reviewed_at: string | null;
    review_notes: string | null;
    submitter_name: string | null;
    submitter_phone: string | null;
    submitter_email: string | null;
    submitter_county: string | null;
    user?: SubmissionUser | null;
    reviewer?: SubmissionUser | null;
    bill: SubmissionBill;
}

interface EngagementDetail {
    id: number;
    subject: string;
    message: string;
    sent_at: string;
    sender: {
        id: number;
        name: string;
    };
}

const props = defineProps<{ submission: SubmissionDetail; engagements: EngagementDetail[]; canFollowUp: boolean }>();

const page = usePage<AppPageProps>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Bills', href: billRoutes.index().url },
    { title: 'Submissions', href: submissionRoutes.index().url },
    { title: props.submission.tracking_id, href: submissionRoutes.show({ submission: props.submission.id }).url },
]);

const canReview = computed(() => page.props.auth.user.role !== 'citizen');
const hasEngagements = computed(() => props.engagements.length > 0);

const reviewForm = useForm({
    status: props.submission.status,
    review_notes: props.submission.review_notes ?? '',
});

const updateStatus = () => {
    reviewForm
        .transform((data) => ({
            status: data.status,
            review_notes: data.review_notes || null,
        }))
        .patch(submissionRoutes.update({ submission: props.submission.id }).url);
};

const followUpForm = useForm({
    subject: '',
    message: '',
});

const sendFollowUp = () => {
    followUpForm.post(storeEngagement({ submission: props.submission.id }).url, {
        onSuccess: () => {
            followUpForm.reset();
        },
    });
};

const statusLabel = (value: string) => value.split('_').join(' ');

const badgeClass = (status: string) => {
    switch (status) {
        case 'included':
            return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300';
        case 'aggregated':
            return 'bg-emerald-600/15 text-emerald-600 dark:bg-emerald-400/10 dark:text-emerald-300';
        case 'rejected':
            return 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300';
        case 'reviewed':
            return 'bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300';
        default:
            return 'bg-muted text-muted-foreground';
    }
};

const metadataEntries = computed(() => {
    if (!props.submission.metadata) {
        return [];
    }

    return Object.entries(props.submission.metadata);
});
</script>

<template>
    <Head :title="`Submission ${props.submission.tracking_id}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-2">
                <div class="flex flex-wrap items-center gap-3">
                    <span class="rounded-full px-3 py-1 text-xs font-medium" :class="badgeClass(props.submission.status)">
                        {{ statusLabel(props.submission.status) }}
                    </span>
                    <span class="rounded-full bg-muted px-3 py-1 text-xs font-medium text-muted-foreground">
                        {{ statusLabel(props.submission.submission_type) }} • {{ props.submission.language.toUpperCase() }}
                    </span>
                </div>
                <h1 class="text-3xl font-semibold text-foreground">Tracking ID {{ props.submission.tracking_id }}</h1>
                <p class="text-sm text-muted-foreground">Submitted {{ props.submission.created_at }} via {{ props.submission.channel ?? 'web' }}</p>
            </header>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <p class="text-xs text-muted-foreground">Bill {{ props.submission.bill.bill_number }}</p>
                        <h2 class="text-xl font-semibold text-foreground">{{ props.submission.bill.title }}</h2>
                    </div>
                    <Link
                        :href="billRoutes.show({ bill: props.submission.bill.id }).url"
                        class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                    >
                        View bill
                    </Link>
                </header>
                <article class="mt-4 space-y-4 text-sm text-muted-foreground">
                    <p class="whitespace-pre-line">{{ props.submission.content }}</p>
                </article>
            </section>

            <section class="grid gap-6 md:grid-cols-2">
                <div class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                    <h3 class="text-lg font-semibold text-foreground">Submitter information</h3>
                    <dl class="mt-4 space-y-3 text-sm text-muted-foreground">
                        <div class="flex items-center justify-between">
                            <dt>Name</dt>
                            <dd class="text-foreground">{{ props.submission.submitter_name ?? props.submission.user?.name ?? 'Anonymous' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt>Email</dt>
                            <dd class="text-foreground">{{ props.submission.submitter_email ?? props.submission.user?.email ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt>Phone</dt>
                            <dd class="text-foreground">{{ props.submission.submitter_phone ?? '—' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt>County</dt>
                            <dd class="text-foreground">{{ props.submission.submitter_county ?? '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                    <h3 class="text-lg font-semibold text-foreground">Review timeline</h3>
                    <dl class="mt-4 space-y-3 text-sm text-muted-foreground">
                        <div class="flex items-center justify-between">
                            <dt>Reviewed</dt>
                            <dd class="text-foreground">{{ props.submission.reviewed_at ?? 'Pending' }}</dd>
                        </div>
                        <div class="flex items-center justify-between">
                            <dt>Reviewer</dt>
                            <dd class="text-foreground">{{ props.submission.reviewer?.name ?? '—' }}</dd>
                        </div>
                        <div class="space-y-1">
                            <dt class="text-sm font-medium text-foreground">Review notes</dt>
                            <dd class="rounded-lg border border-input/60 bg-muted/40 px-3 py-2 text-sm text-muted-foreground">
                                {{ props.submission.review_notes ?? 'No notes yet' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>

            <section v-if="metadataEntries.length" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <h3 class="text-lg font-semibold text-foreground">Submission insights</h3>
                <dl class="mt-4 grid gap-3 md:grid-cols-2">
                    <div v-for="[key, value] in metadataEntries" :key="key" class="rounded-lg border border-input/40 bg-muted/40 px-4 py-3">
                        <dt class="text-xs uppercase text-muted-foreground">{{ key }}</dt>
                        <dd class="mt-1 text-sm text-foreground">{{ value }}</dd>
                    </div>
                </dl>
            </section>

            <section v-if="canReview" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <header class="mb-4 flex items-center justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">Update status</h3>
                        <p class="text-sm text-muted-foreground">Document the review outcome for this submission.</p>
                    </div>
                </header>

                <form class="grid gap-4 md:grid-cols-2" @submit.prevent="updateStatus">
                    <div class="space-y-2">
                        <label for="status" class="text-sm font-medium text-foreground">Status</label>
                        <select
                            id="status"
                            v-model="reviewForm.status"
                            class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="pending">Pending</option>
                            <option value="reviewed">Reviewed</option>
                            <option value="included">Included in report</option>
                            <option value="aggregated">Aggregated into report</option>
                            <option value="rejected">Rejected</option>
                        </select>
                        <InputError :message="reviewForm.errors.status" />
                    </div>

                    <div class="space-y-2 md:col-span-2">
                        <label for="review_notes" class="text-sm font-medium text-foreground">Review notes</label>
                        <textarea
                            id="review_notes"
                            v-model="reviewForm.review_notes"
                            rows="4"
                            class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Summarize key points and decisions from the review"
                        />
                        <InputError :message="reviewForm.errors.review_notes" />
                    </div>

                    <div class="flex items-center justify-end gap-3 md:col-span-2">
                        <Button type="submit" :disabled="reviewForm.processing">Save review</Button>
                    </div>
                </form>
            </section>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                <header class="mb-6 flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">Engagement log</h3>
                        <p class="text-sm text-muted-foreground">Track legislator follow-ups and citizen responses.</p>
                    </div>
                </header>

                <div class="space-y-4">
                    <div v-if="hasEngagements" class="space-y-4">
                        <article
                            v-for="engagement in props.engagements"
                            :key="engagement.id"
                            class="rounded-lg border border-input/50 bg-muted/30 p-4"
                        >
                            <header class="flex flex-wrap items-center justify-between gap-2">
                                <p class="text-sm font-medium text-foreground">{{ engagement.subject }}</p>
                                <span class="text-xs uppercase text-muted-foreground">{{ engagement.sent_at }}</span>
                            </header>
                            <p class="mt-2 text-sm text-muted-foreground whitespace-pre-line">{{ engagement.message }}</p>
                            <footer class="mt-3 text-xs text-muted-foreground">From {{ engagement.sender.name }}</footer>
                        </article>
                    </div>
                    <p v-else class="text-sm text-muted-foreground">No follow-up requests have been logged yet.</p>

                    <form v-if="props.canFollowUp" class="grid gap-4 md:grid-cols-2" @submit.prevent="sendFollowUp">
                        <div class="space-y-2 md:col-span-2">
                            <label for="follow_subject" class="text-sm font-medium text-foreground">Subject</label>
                            <input
                                id="follow_subject"
                                v-model="followUpForm.subject"
                                type="text"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Request to confirm policy detail"
                            />
                            <InputError :message="followUpForm.errors.subject" />
                        </div>
                        <div class="space-y-2 md:col-span-2">
                            <label for="follow_message" class="text-sm font-medium text-foreground">Message</label>
                            <textarea
                                id="follow_message"
                                v-model="followUpForm.message"
                                rows="4"
                                class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Please expand on how this proposal impacts county-level programs..."
                            />
                            <InputError :message="followUpForm.errors.message" />
                        </div>
                        <div class="flex items-center justify-end gap-3 md:col-span-2">
                            <Button type="submit" :disabled="followUpForm.processing">Send follow-up</Button>
                        </div>
                    </form>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
