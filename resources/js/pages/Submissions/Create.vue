<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import Input from '@/components/ui/input/Input.vue';
import Label from '@/components/ui/label/Label.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import * as submissionDraftRoutes from '@/routes/submissions/drafts';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';
import {
    ArrowLeft,
    ArrowRight,
    CheckCircle2,
    Circle,
    FileText,
    History,
    Loader2,
    Save,
    Sparkles,
    Trash2,
} from 'lucide-vue-next';

interface BillSummary {
    simplified_summary_en?: string | null;
    key_clauses?: string[] | null;
}

interface BillDetail {
    id: number;
    title: string;
    bill_number: string;
    status: string;
    participation_end_date: string | null;
    summary?: BillSummary | null;
}

interface DraftContactInformation {
    name?: string | null;
    email?: string | null;
    phone?: string | null;
    county?: string | null;
}

interface DraftBillSummary {
    id: number;
    title: string;
    bill_number: string;
    status: string;
    participation_end_date: string | null;
}

interface DraftResource {
    id: number;
    bill_id: number;
    submission_type?: string | null;
    language?: string | null;
    content?: string | null;
    contact_information?: DraftContactInformation | null;
    attachments?: Record<string, unknown>[] | null;
    updated_at?: string | null;
    bill?: DraftBillSummary | null;
}

interface RecentSubmission {
    id: number;
    tracking_id: string;
    status: string;
    created_at: string | null;
    bill: {
        id: number;
        title: string;
        bill_number: string;
    } | null;
}

interface Props {
    bill: BillDetail | null;
    drafts: DraftResource[];
    activeDraft: DraftResource | null;
    recentSubmissions: RecentSubmission[];
}

const props = defineProps<Props>();

const page = usePage();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Bills', href: billRoutes.index().url },
    { title: 'Submissions', href: submissionRoutes.index().url },
    {
        title: 'Submit feedback',
        href: submissionRoutes.create.url({
            query: {
                ...(props.bill ? { bill_id: props.bill.id } : {}),
                ...(props.activeDraft ? { draft_id: props.activeDraft.id } : {}),
            },
        }),
    },
]);

const draftId = ref<number | null>(props.activeDraft?.id ?? null);

const form = useForm({
    bill_id: props.activeDraft?.bill_id?.toString() ?? props.bill?.id?.toString() ?? '',
    submission_type: props.activeDraft?.submission_type ?? 'support',
    language: props.activeDraft?.language ?? 'en',
    content: props.activeDraft?.content ?? '',
    submitter_name: props.activeDraft?.contact_information?.name ?? '',
    submitter_phone: props.activeDraft?.contact_information?.phone ?? '',
    submitter_email: props.activeDraft?.contact_information?.email ?? '',
    submitter_county: props.activeDraft?.contact_information?.county ?? '',
    draft_id: props.activeDraft?.id ?? null,
});

const steps = [
    {
        key: 'bill',
        title: 'Bill selection',
        description: 'Confirm the bill you are responding to and review its context.',
    },
    {
        key: 'feedback',
        title: 'Compose feedback',
        description: 'Capture your position, supporting details, and recommendations.',
    },
    {
        key: 'contact',
        title: 'Contact details',
        description: 'Share how the clerks can reach you for clarifications.',
    },
    {
        key: 'review',
        title: 'Review & submit',
        description: 'Verify information before sending it for parliamentary review.',
    },
];

const currentStep = ref(props.activeDraft ? 2 : 1);
const isSavingDraft = ref(false);
const isDeletingDraft = ref(false);

const dateFormatter = computed(() =>
    new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }),
);

const statusLabels: Record<string, string> = {
    pending: 'Pending review',
    reviewed: 'Reviewed',
    included: 'Included in report',
    aggregated: 'Aggregated',
    rejected: 'Rejected',
};

const formattedRecentSubmissions = computed(() =>
    props.recentSubmissions.map((submission) => ({
        ...submission,
        created_at: submission.created_at ? dateFormatter.value.format(new Date(submission.created_at)) : '—',
        status_label: statusLabels[submission.status] ?? submission.status.replace(/_/g, ' '),
    })),
);

const selectedBill = computed(() => {
    if (props.bill) {
        return props.bill;
    }

    if (props.activeDraft?.bill) {
        return props.activeDraft.bill;
    }

    const fromDrafts = props.drafts.find((draft) => draft.bill && draft.bill.id === Number(form.bill_id));

    return fromDrafts?.bill ?? null;
});

const hasSelectedBill = computed(() => Boolean(form.bill_id));
const isOnReviewStep = computed(() => currentStep.value === steps.length);

const canAdvance = computed(() => {
    if (currentStep.value === 1) {
        return hasSelectedBill.value;
    }

    if (currentStep.value === 2) {
        return form.content.trim().length >= 10;
    }

    return true;
});

function applyDraft(draft: DraftResource | null) {
    if (draft) {
        form.bill_id = draft.bill_id?.toString() ?? form.bill_id;
        form.submission_type = draft.submission_type ?? form.submission_type;
        form.language = draft.language ?? form.language;
        form.content = draft.content ?? form.content;
        form.submitter_name = draft.contact_information?.name ?? form.submitter_name;
        form.submitter_email = draft.contact_information?.email ?? form.submitter_email;
        form.submitter_phone = draft.contact_information?.phone ?? form.submitter_phone;
        form.submitter_county = draft.contact_information?.county ?? form.submitter_county;
        draftId.value = draft.id;
        form.draft_id = draft.id;
    }
}

watch(
    () => props.activeDraft,
    (draft) => {
        if (draft) {
            applyDraft(draft);
        } else if (!form.bill_id && props.bill?.id) {
            form.bill_id = props.bill.id.toString();
        }
    },
    { immediate: true }
);

function goToStep(step: number) {
    if (step < 1 || step > steps.length) {
        return;
    }

    if (step > currentStep.value && !canAdvance.value) {
        return;
    }

    currentStep.value = step;
}

function nextStep() {
    if (currentStep.value < steps.length && canAdvance.value) {
        currentStep.value += 1;
    }
}

function previousStep() {
    if (currentStep.value > 1) {
        currentStep.value -= 1;
    }
}

function draftPayload() {
    return {
        bill_id: Number(form.bill_id),
        submission_type: form.submission_type,
        language: form.language,
        content: form.content,
        contact_information: {
            name: form.submitter_name || null,
            email: form.submitter_email || null,
            phone: form.submitter_phone || null,
            county: form.submitter_county || null,
        },
    };
}

function saveDraft() {
    if (!hasSelectedBill.value) {
        form.setError('bill_id', 'Select the bill you are responding to before saving.');
        currentStep.value = 1;
        return;
    }

    const payload = draftPayload();

    const options = {
        preserveScroll: true,
        preserveState: true,
        replace: true,
        onStart: () => {
            isSavingDraft.value = true;
        },
        onFinish: () => {
            isSavingDraft.value = false;
        },
    } as const;

    if (draftId.value) {
        router.patch(submissionDraftRoutes.update({ submissionDraft: draftId.value }).url, payload, options);
    } else {
        router.post(submissionDraftRoutes.store().url, payload, options);
    }
}

function discardDraft() {
    if (!draftId.value) {
        return;
    }

    if (!window.confirm('Discard this draft? This cannot be undone.')) {
        return;
    }

    isDeletingDraft.value = true;

    router.delete(submissionDraftRoutes.destroy({ submissionDraft: draftId.value }).url, {
        preserveScroll: true,
        replace: true,
        onFinish: () => {
            isDeletingDraft.value = false;
            draftId.value = null;
            form.reset();
            if (props.bill?.id) {
                form.bill_id = props.bill.id.toString();
            }
            currentStep.value = 1;
        },
    });
}

function submit() {
    form
        .transform((data) => ({
            ...data,
            bill_id: data.bill_id ? Number(data.bill_id) : data.bill_id,
            draft_id: draftId.value,
        }))
        .post(submissionRoutes.store().url, {
            onSuccess: () => {
                draftId.value = null;
            },
        });
}

const reviewDetails = computed(() => [
    {
        label: 'Bill',
        value: selectedBill.value ? `${selectedBill.value.title} (${selectedBill.value.bill_number})` : form.bill_id,
    },
    {
        label: 'Submission type',
        value: form.submission_type.replace(/_/g, ' '),
    },
    {
        label: 'Language',
        value: form.language.toUpperCase(),
    },
]);

const contactDetails = computed(() => [
    { label: 'Name', value: form.submitter_name || '—' },
    { label: 'Email', value: form.submitter_email || '—' },
    { label: 'Phone', value: form.submitter_phone || '—' },
    { label: 'County', value: form.submitter_county || '—' },
]);

const flashSuccess = computed(() => page.props.flash?.success ?? null);
</script>

<template>
    <Head title="Submit feedback" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6 xl:flex-row">
            <div class="flex-1 space-y-6">
                <header class="space-y-2">
                    <h1 class="text-3xl font-semibold text-foreground">Submit feedback</h1>
                    <p class="max-w-2xl text-sm text-muted-foreground">
                        Share your perspective on a bill currently open for public participation. Save drafts as you go and return to them whenever you are ready.
                    </p>
                </header>

                <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                    <ol class="flex flex-col gap-4 md:flex-row md:items-start md:gap-6">
                        <li
                            v-for="(step, index) in steps"
                            :key="step.key"
                            class="flex flex-1 items-start gap-3"
                        >
                            <button
                                type="button"
                                class="flex h-8 w-8 items-center justify-center rounded-full border border-input/60"
                                :class="[
                                    currentStep === index + 1
                                        ? 'border-primary bg-primary text-primary-foreground'
                                        : currentStep > index + 1
                                          ? 'border-emerald-500/60 bg-emerald-500/15 text-emerald-500'
                                          : 'bg-muted text-muted-foreground',
                                ]"
                                @click="goToStep(index + 1)"
                            >
                                <CheckCircle2 v-if="currentStep > index + 1" class="h-4 w-4" />
                                <span v-else>{{ index + 1 }}</span>
                            </button>
                            <div class="space-y-1">
                                <p class="text-sm font-semibold" :class="currentStep === index + 1 ? 'text-foreground' : 'text-muted-foreground'">
                                    {{ step.title }}
                                </p>
                                <p class="text-xs text-muted-foreground">{{ step.description }}</p>
                            </div>
                        </li>
                    </ol>
                </section>

                <form class="space-y-6" @submit.prevent="submit">
                    <section v-show="currentStep === 1" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                        <header class="mb-4 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Confirm bill</h2>
                                <p class="text-sm text-muted-foreground">Pick the bill you want to respond to. You can start from the bills list or enter the ID manually.</p>
                            </div>
                            <Link :href="billRoutes.participate().url" class="inline-flex items-center gap-2 text-xs font-medium text-primary">
                                <Sparkles class="h-4 w-4" />
                                View open participation space
                            </Link>
                        </header>

                        <div class="space-y-4">
                            <div class="grid gap-2">
                                <Label for="bill-id">Bill identifier</Label>
                                <Input
                                    id="bill-id"
                                    v-model="form.bill_id"
                                    type="number"
                                    min="1"
                                    placeholder="Enter the bill ID"
                                />
                                <InputError :message="form.errors.bill_id" />
                                <p class="text-xs text-muted-foreground">
                                    The ID is located on the bill workspace page. Saving a draft will automatically link to this bill.
                                </p>
                            </div>

                            <div
                                v-if="selectedBill"
                                class="rounded-lg border border-input/60 bg-muted/30 p-4 text-sm text-muted-foreground"
                            >
                                <p class="text-xs uppercase text-muted-foreground">Bill summary</p>
                                <p class="mt-1 font-medium text-foreground">{{ selectedBill.title }}</p>
                                <div class="mt-2 flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                    <span>Number: {{ selectedBill.bill_number }}</span>
                                    <span>Status: {{ selectedBill.status.replace(/_/g, ' ') }}</span>
                                    <span>Deadline: {{ selectedBill.participation_end_date ?? 'TBA' }}</span>
                                </div>
                                <p v-if="selectedBill.summary?.simplified_summary_en" class="mt-2 text-xs">
                                    {{ selectedBill.summary.simplified_summary_en }}
                                </p>
                            </div>
                        </div>

                        <footer class="mt-6 flex items-center justify-between">
                            <Button type="button" variant="outline" @click="saveDraft" :disabled="isSavingDraft">
                                <Loader2 v-if="isSavingDraft" class="mr-2 h-4 w-4 animate-spin" />
                                <Save v-else class="mr-2 h-4 w-4" />
                                Save draft
                            </Button>
                            <Button type="button" :disabled="!canAdvance" @click="nextStep">
                                Continue
                                <ArrowRight class="ml-2 h-4 w-4" />
                            </Button>
                        </footer>
                    </section>

                    <section v-show="currentStep === 2" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                        <header class="mb-4 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Compose feedback</h2>
                                <p class="text-sm text-muted-foreground">Capture the strongest points explaining your position. Share stories, data, and any sections needing changes.</p>
                            </div>
                        </header>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="submission-type">Submission type</Label>
                                <select
                                    id="submission-type"
                                    v-model="form.submission_type"
                                    class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="support">Support</option>
                                    <option value="oppose">Oppose</option>
                                    <option value="amend">Suggest amendment</option>
                                    <option value="neutral">General comment</option>
                                </select>
                                <InputError :message="form.errors.submission_type" />
                            </div>

                            <div class="grid gap-2">
                                <Label for="submission-language">Language</Label>
                                <select
                                    id="submission-language"
                                    v-model="form.language"
                                    class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="en">English</option>
                                    <option value="sw">Swahili</option>
                                    <option value="other">Other</option>
                                </select>
                                <InputError :message="form.errors.language" />
                            </div>

                            <div class="md:col-span-2 grid gap-2">
                                <Label for="submission-content">Your feedback</Label>
                                <textarea
                                    id="submission-content"
                                    v-model="form.content"
                                    rows="7"
                                    class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-3 text-sm leading-relaxed outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Explain your position, suggested amendments, and supporting context."
                                ></textarea>
                                <InputError :message="form.errors.content" />
                                <p class="text-xs text-muted-foreground">Aim for at least 10 characters to help reviewers understand your position.</p>
                            </div>
                        </div>

                        <footer class="mt-6 flex items-center justify-between">
                            <Button type="button" variant="ghost" @click="previousStep">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back
                            </Button>
                            <div class="flex items-center gap-3">
                                <Button type="button" variant="outline" @click="saveDraft" :disabled="isSavingDraft">
                                    <Loader2 v-if="isSavingDraft" class="mr-2 h-4 w-4 animate-spin" />
                                    <Save v-else class="mr-2 h-4 w-4" />
                                    Save draft
                                </Button>
                                <Button type="button" :disabled="!canAdvance" @click="nextStep">
                                    Continue
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </div>
                        </footer>
                    </section>

                    <section v-show="currentStep === 3" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                        <header class="mb-4 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Contact details (optional)</h2>
                                <p class="text-sm text-muted-foreground">Share how proceeding officers can reach you if clarification or testimonies are required.</p>
                            </div>
                        </header>

                        <div class="grid gap-4 md:grid-cols-2">
                            <div class="grid gap-2">
                                <Label for="contact-name">Full name</Label>
                                <Input id="contact-name" v-model="form.submitter_name" type="text" placeholder="Jane Doe" />
                                <InputError :message="form.errors.submitter_name" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact-email">Email</Label>
                                <Input id="contact-email" v-model="form.submitter_email" type="email" placeholder="jane@example.com" />
                                <InputError :message="form.errors.submitter_email" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact-phone">Phone</Label>
                                <Input id="contact-phone" v-model="form.submitter_phone" type="tel" placeholder="07xx xxx xxx" />
                                <InputError :message="form.errors.submitter_phone" />
                            </div>
                            <div class="grid gap-2">
                                <Label for="contact-county">County</Label>
                                <Input id="contact-county" v-model="form.submitter_county" type="text" placeholder="Nairobi" />
                                <InputError :message="form.errors.submitter_county" />
                            </div>
                        </div>

                        <footer class="mt-6 flex items-center justify-between">
                            <Button type="button" variant="ghost" @click="previousStep">
                                <ArrowLeft class="mr-2 h-4 w-4" />
                                Back
                            </Button>
                            <div class="flex items-center gap-3">
                                <Button type="button" variant="outline" @click="saveDraft" :disabled="isSavingDraft">
                                    <Loader2 v-if="isSavingDraft" class="mr-2 h-4 w-4 animate-spin" />
                                    <Save v-else class="mr-2 h-4 w-4" />
                                    Save draft
                                </Button>
                                <Button type="button" @click="nextStep">
                                    Review submission
                                    <ArrowRight class="ml-2 h-4 w-4" />
                                </Button>
                            </div>
                        </footer>
                    </section>

                    <section v-show="isOnReviewStep" class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                        <header class="mb-4 flex items-start justify-between">
                            <div>
                                <h2 class="text-lg font-semibold text-foreground">Review & submit</h2>
                                <p class="text-sm text-muted-foreground">Verify everything before submission. A clerk will email your tracking ID once processed.</p>
                            </div>
                        </header>

                        <div class="grid gap-6 md:grid-cols-2">
                            <div class="space-y-4">
                                <h3 class="text-sm font-semibold text-foreground">Submission summary</h3>
                                <dl class="space-y-3 text-sm text-muted-foreground">
                                    <div v-for="item in reviewDetails" :key="item.label" class="flex items-start justify-between gap-4">
                                        <dt class="min-w-[120px] text-xs uppercase tracking-wide text-muted-foreground">{{ item.label }}</dt>
                                        <dd class="flex-1 text-foreground">{{ item.value }}</dd>
                                    </div>
                                </dl>
                                <div class="rounded-lg border border-input/60 bg-muted/30 p-4">
                                    <p class="text-xs uppercase text-muted-foreground">Feedback</p>
                                    <p class="mt-2 whitespace-pre-wrap text-sm text-muted-foreground">{{ form.content }}</p>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h3 class="text-sm font-semibold text-foreground">Contact information</h3>
                                <dl class="space-y-3 text-sm text-muted-foreground">
                                    <div v-for="item in contactDetails" :key="item.label" class="flex items-start justify-between gap-4">
                                        <dt class="min-w-[120px] text-xs uppercase tracking-wide text-muted-foreground">{{ item.label }}</dt>
                                        <dd class="flex-1 text-foreground">{{ item.value }}</dd>
                                    </div>
                                </dl>
                                <div class="rounded-lg border border-amber-500/40 bg-amber-500/5 p-4 text-xs text-amber-600 dark:text-amber-300">
                                    <p>Submitting this feedback shares your contact details with authenticated clerks only. They will reach out if clarification is required.</p>
                                </div>
                            </div>
                        </div>

                        <footer class="mt-6 flex flex-wrap items-center justify-between gap-3">
                            <div class="flex gap-2">
                                <Button type="button" variant="ghost" @click="previousStep">
                                    <ArrowLeft class="mr-2 h-4 w-4" />
                                    Back
                                </Button>
                                <Button type="button" variant="outline" @click="saveDraft" :disabled="isSavingDraft">
                                    <Loader2 v-if="isSavingDraft" class="mr-2 h-4 w-4 animate-spin" />
                                    <Save v-else class="mr-2 h-4 w-4" />
                                    Save draft
                                </Button>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button
                                    v-if="draftId"
                                    type="button"
                                    variant="outline"
                                    class="border-destructive/40 text-destructive"
                                    :disabled="isDeletingDraft"
                                    @click="discardDraft"
                                >
                                    <Loader2 v-if="isDeletingDraft" class="mr-2 h-4 w-4 animate-spin" />
                                    <Trash2 v-else class="mr-2 h-4 w-4" />
                                    Discard draft
                                </Button>
                                <Button type="submit" :disabled="form.processing">
                                    <FileText class="mr-2 h-4 w-4" />
                                    Submit feedback
                                </Button>
                            </div>
                        </footer>
                    </section>
                </form>

                <section v-if="flashSuccess" class="rounded-xl border border-emerald-500/30 bg-emerald-500/10 p-4 text-sm text-emerald-700 dark:text-emerald-200">
                    {{ flashSuccess }}
                </section>
            </div>

            <aside class="w-full max-w-[340px] space-y-6">
                <section id="drafts-panel" class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <header class="mb-4 flex items-center justify-between">
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">Drafts</p>
                            <h3 class="text-sm font-semibold text-foreground">Saved in-progress submissions</h3>
                        </div>
                        <Circle class="h-4 w-4 text-muted-foreground" />
                    </header>

                    <div v-if="props.drafts.length" class="space-y-3">
                        <article
                            v-for="draft in props.drafts"
                            :key="draft.id"
                            class="rounded-lg border border-input/60 bg-muted/30 p-4 text-sm text-muted-foreground"
                        >
                            <div class="flex items-center justify-between">
                                <p class="text-sm font-medium text-foreground">{{ draft.bill?.title ?? 'Draft without bill' }}</p>
                                <span class="text-xs uppercase text-muted-foreground">{{ draft.updated_at ? dateFormatter.format(new Date(draft.updated_at)) : '—' }}</span>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">{{ (draft.content ?? '').slice(0, 80) || 'No content yet saved.' }}</p>
                            <div class="mt-3 flex items-center justify-between">
                                <span class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground">{{ (draft.submission_type ?? 'draft').replace(/_/g, ' ') }}</span>
                                <Link
                                    :href="submissionRoutes.create.url({ query: { draft_id: draft.id, bill_id: draft.bill_id } })"
                                    class="text-xs font-medium text-primary"
                                >
                                    Resume
                                </Link>
                            </div>
                        </article>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">Saving a draft keeps your progress synced across devices. Nothing is saved yet.</p>
                </section>

                <section class="rounded-xl border border-sidebar-border/60 bg-card p-5 shadow-sm dark:border-sidebar-border">
                    <header class="mb-4 flex items-center gap-2">
                        <History class="h-4 w-4 text-primary" />
                        <div>
                            <p class="text-xs uppercase text-muted-foreground">Recent activity</p>
                            <h3 class="text-sm font-semibold text-foreground">Latest submissions</h3>
                        </div>
                    </header>
                    <div v-if="formattedRecentSubmissions.length" class="space-y-3">
                        <article
                            v-for="submission in formattedRecentSubmissions"
                            :key="submission.id"
                            class="rounded-lg border border-input/60 bg-muted/20 p-4 text-xs text-muted-foreground"
                        >
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-foreground">{{ submission.status_label }}</span>
                                <span>{{ submission.created_at }}</span>
                            </div>
                            <p class="mt-2 text-muted-foreground">Tracking ID {{ submission.tracking_id }}</p>
                            <p v-if="submission.bill" class="mt-1 text-foreground">{{ submission.bill.title }}</p>
                            <Link
                                :href="submissionRoutes.show({ submission: submission.id }).url"
                                class="mt-2 inline-flex items-center gap-2 text-[11px] font-medium text-primary"
                            >
                                View submission
                                <ArrowRight class="h-3 w-3" />
                            </Link>
                        </article>
                    </div>
                    <p v-else class="text-xs text-muted-foreground">No submissions yet. Once you submit, they will appear here for quick tracking.</p>
                </section>
            </aside>
        </div>
    </AppLayout>
</template>
