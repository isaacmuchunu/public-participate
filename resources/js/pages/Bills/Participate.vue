<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as billRoutes from '@/routes/bills';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItem, User } from '@/types';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed, reactive } from 'vue';
import { Input } from '@/components/ui/input';
import Button from '@/components/ui/button/Button.vue';

interface BillSummary {
    simplified_summary_en: string | null;
    key_clauses: string[];
}

interface BillItem {
    id: number;
    title: string;
    bill_number: string;
    description: string;
    participation_end_date: string | null;
    submissions_count: number;
    tags: string[] | null;
    summary?: BillSummary | null;
}

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

interface Props {
    bills: {
        data: BillItem[];
        links: PaginationLink[];
        total: number;
        from: number | null;
        to: number | null;
    };
    filters: {
        tag?: string;
        search?: string;
    };
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Bills', href: billRoutes.index().url },
    { title: 'Participate', href: billRoutes.participate().url },
];

const filterForm = reactive({
    tag: props.filters?.tag ?? 'all',
    search: props.filters?.search ?? '',
});

const hasResults = computed(() => props.bills.data.length > 0);

const submitFilters = () => {
    const query: Record<string, string> = {};

    if (filterForm.tag && filterForm.tag !== 'all') {
        query.tag = filterForm.tag;
    }

    if (filterForm.search) {
        query.search = filterForm.search;
    }

    router.get(
        billRoutes.participate.url({ query }),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        },
    );
};

const resetFilters = () => {
    filterForm.tag = 'all';
    filterForm.search = '';
    submitFilters();
};

const paginationLabel = (label: string) =>
    label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');

const page = usePage<{ auth: { user: User | null } }>();
const layoutComponent = computed(() => (page.props.auth?.user ? AppLayout : PublicLayout));
</script>

<template>
    <Head title="Participate in Bills" />

    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <div class="mx-auto flex w-full max-w-6xl flex-1 flex-col gap-8 px-4 py-12 md:px-6">
            <header class="rounded-3xl bg-gradient-to-r from-emerald-600 via-emerald-500 to-emerald-400 p-8 text-white shadow-lg">
                <h1 class="text-3xl font-semibold">Participate in public bills</h1>
                <p class="mt-3 max-w-3xl text-base text-white/90">
                    Share your expertise or lived experience on live legislation. Every submission is reviewed by the parliamentary clerks before committee reports are drafted.
                </p>
            </header>

            <section class="rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm backdrop-blur">
                <form class="grid gap-4 md:grid-cols-[2fr_1fr_auto]" @submit.prevent="submitFilters">
                    <div class="space-y-2">
                        <label for="search" class="text-sm font-semibold text-emerald-900">Search bills</label>
                        <Input
                            id="search"
                            v-model="filterForm.search"
                            type="search"
                            placeholder="Search by title or theme"
                            class="h-11 rounded-lg border border-emerald-200/80 bg-white/80 text-emerald-900"
                        />
                    </div>

                    <div class="space-y-2">
                        <label for="tag" class="text-sm font-semibold text-emerald-900">Filter by tag</label>
                        <select
                            id="tag"
                            v-model="filterForm.tag"
                            class="h-11 w-full rounded-lg border border-emerald-200/80 bg-white/80 px-3 text-sm text-emerald-900 outline-none transition focus-visible:border-emerald-400 focus-visible:ring-[3px] focus-visible:ring-emerald-200"
                        >
                            <option value="all">All tags</option>
                            <option value="governance">Governance</option>
                            <option value="health">Health</option>
                            <option value="education">Education</option>
                            <option value="agriculture">Agriculture</option>
                            <option value="economy">Economy</option>
                        </select>
                    </div>

                    <div class="flex items-end gap-2">
                        <Button type="submit" class="h-11 rounded-full bg-emerald-600 text-sm font-semibold text-white shadow-sm hover:bg-emerald-700">
                            Search
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            class="h-11 rounded-full border-emerald-200 text-sm font-medium text-emerald-700 hover:border-emerald-400 hover:text-emerald-800"
                            @click="resetFilters"
                        >
                            Reset
                        </Button>
                    </div>
                </form>
            </section>

            <section class="flex-1">
                <div v-if="hasResults" class="grid gap-5 md:grid-cols-2">
                    <article
                        v-for="bill in props.bills.data"
                        :key="bill.id"
                        class="flex h-full flex-col gap-4 rounded-2xl border border-emerald-100/70 bg-white/95 p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-lg"
                    >
                        <header class="space-y-1">
                            <h2 class="text-xl font-semibold text-emerald-900">{{ bill.title }}</h2>
                            <p class="text-sm text-emerald-800/70">Bill {{ bill.bill_number }}</p>
                        </header>

                        <p class="text-sm text-emerald-800/80">
                            {{ bill.summary?.simplified_summary_en ?? bill.description }}
                        </p>

                        <ul v-if="bill.summary?.key_clauses?.length" class="space-y-2 text-sm text-emerald-800/80">
                            <li v-for="clause in bill.summary.key_clauses" :key="clause" class="flex gap-2">
                                <span class="mt-2 h-1.5 w-1.5 shrink-0 rounded-full bg-emerald-500"></span>
                                <span>{{ clause }}</span>
                            </li>
                        </ul>

                        <div class="flex flex-wrap gap-2">
                            <span
                                v-for="tag in bill.tags ?? []"
                                :key="tag"
                                class="rounded-full bg-emerald-50 px-3 py-1 text-xs text-emerald-700"
                            >
                                {{ tag }}
                            </span>
                        </div>

                        <footer class="mt-auto flex flex-col gap-3 rounded-xl bg-emerald-50/70 p-4 text-sm text-emerald-800/80">
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-emerald-900">Submissions received</span>
                                <span>{{ bill.submissions_count }}</span>
                            </div>
                            <div class="flex items-center justify-between">
                                <span class="font-medium text-emerald-900">Closes on</span>
                                <span>{{ bill.participation_end_date ?? 'To be confirmed' }}</span>
                            </div>
                            <div class="flex items-center justify-end gap-2">
                                <Link
                                    :href="billRoutes.show({ bill: bill.id }).url"
                                    class="text-sm font-semibold text-emerald-700 underline-offset-4 hover:text-emerald-900 hover:underline"
                                >
                                    View details
                                </Link>
                                <Link
                                    :href="submissionRoutes.create.url({ query: { bill_id: bill.id } })"
                                    class="inline-flex rounded-full bg-emerald-600 px-4 py-2 text-sm font-semibold text-white transition hover:bg-emerald-700"
                                >
                                    Submit feedback
                                </Link>
                            </div>
                        </footer>
                    </article>
                </div>

                <div v-else class="flex min-h-[200px] flex-col items-center justify-center rounded-2xl border border-dashed border-emerald-200 bg-white/95 p-10 text-center text-emerald-800/70">
                    <p class="font-medium">No open bills at the moment</p>
                    <p class="mt-2 text-sm">New participation opportunities are posted frequently. Check again soon.</p>
                </div>
            </section>

            <nav v-if="hasResults && props.bills.links.length > 1" class="flex items-center justify-center gap-2">
                <Link
                    v-for="link in props.bills.links"
                    :key="link.label"
                    :href="link.url ?? '#'"
                    :class="[
                        'rounded-full px-4 py-2 text-sm transition',
                        link.active
                            ? 'bg-emerald-600 text-white shadow-sm'
                            : 'text-emerald-700 hover:bg-emerald-50',
                        !link.url && 'pointer-events-none opacity-50',
                    ]"
                >
                    {{ paginationLabel(link.label) }}
                </Link>
            </nav>
        </div>
    </component>
</template>
