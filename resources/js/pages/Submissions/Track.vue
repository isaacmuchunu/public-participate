<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as submissionRoutes from '@/routes/submissions';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

interface SubmissionBill {
    id: number;
    title: string;
    bill_number: string;
}

interface SubmissionDetail {
    tracking_id: string;
    status: string;
    submission_type: string;
    created_at: string;
    review_notes: string | null;
    reviewed_at: string | null;
    bill: SubmissionBill;
}

const props = defineProps<{ submission: SubmissionDetail }>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Submissions', href: submissionRoutes.index().url },
    { title: 'Tracking', href: '/submissions/track' },
]);

const statusLabel = (value: string) => value.split('_').join(' ');

const badgeClass = (status: string) => {
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

const page = usePage<{ auth: { user: User | null } }>();
const layoutComponent = computed(() => (page.props.auth?.user ? AppLayout : PublicLayout));
</script>

<template>
    <Head title="Submission status" />

    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-16 md:px-6">
            <div class="w-full max-w-2xl rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-400 p-[1px] shadow-xl">
                <div class="rounded-3xl bg-white/95 p-10">
                    <header class="space-y-3 text-center">
                        <span class="inline-flex items-center rounded-full px-4 py-1.5 text-xs font-semibold uppercase tracking-wide" :class="badgeClass(props.submission.status)">
                            {{ statusLabel(props.submission.status) }}
                        </span>
                        <h1 class="text-3xl font-semibold text-emerald-900">Submission {{ props.submission.tracking_id }}</h1>
                        <p class="text-sm text-emerald-800/80">Submitted {{ props.submission.created_at }}</p>
                    </header>

                    <section class="mt-8 space-y-5 text-sm text-emerald-800/80">
                        <div class="rounded-2xl border border-emerald-100/70 bg-emerald-50/70 px-5 py-4">
                            <h2 class="text-sm font-semibold text-emerald-900">Bill</h2>
                            <p class="mt-1">
                                {{ props.submission.bill.title }}<br>
                                <span class="text-xs text-emerald-700/80">Bill {{ props.submission.bill.bill_number }}</span>
                            </p>
                            <Link
                                :href="billRoutes.show({ bill: props.submission.bill.id }).url"
                                class="mt-3 inline-flex text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline"
                            >
                                View bill details
                            </Link>
                        </div>

                        <div class="rounded-2xl border border-emerald-100/70 bg-white px-5 py-4 shadow-sm">
                            <h2 class="text-sm font-semibold text-emerald-900">Submission type</h2>
                            <p class="mt-1 capitalize text-emerald-800/90">{{ statusLabel(props.submission.submission_type) }}</p>
                        </div>

                        <div class="rounded-2xl border border-emerald-100/70 bg-white px-5 py-4 shadow-sm">
                            <h2 class="text-sm font-semibold text-emerald-900">Review notes</h2>
                            <p class="mt-1 whitespace-pre-line">
                                {{ props.submission.review_notes ?? 'Your submission is still under review. Check back later for updates.' }}
                            </p>
                            <p v-if="props.submission.reviewed_at" class="mt-2 text-xs text-emerald-700/80">
                                Updated on {{ props.submission.reviewed_at }}
                            </p>
                        </div>
                    </section>

                    <footer class="mt-8 text-center">
                        <Link :href="'/submissions/track'" class="text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline">
                            Track another submission
                        </Link>
                    </footer>
                </div>
            </div>
        </div>
    </component>
</template>
