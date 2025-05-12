<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';

interface BillSummary {
    simplified_summary_en: string | null;
}

interface BillDetail {
    id: number;
    title: string;
    description: string;
    type: string;
    house: string;
    status: string;
    sponsor: string | null;
    committee: string | null;
    gazette_date: string | null;
    participation_start_date: string | null;
    participation_end_date: string | null;
    tags: string[] | null;
    pdf_path: string | null;
    summary?: BillSummary | null;
}

const props = defineProps<{ bill: BillDetail }>();

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Bills', href: billRoutes.index().url },
    { title: props.bill.title, href: billRoutes.show({ bill: props.bill.id }).url },
    { title: 'Edit', href: billRoutes.edit({ bill: props.bill.id }).url },
]);

const form = useForm({
    title: props.bill.title,
    description: props.bill.description,
    type: props.bill.type,
    house: props.bill.house,
    status: props.bill.status,
    sponsor: props.bill.sponsor ?? '',
    committee: props.bill.committee ?? '',
    gazette_date: props.bill.gazette_date ?? '',
    participation_start_date: props.bill.participation_start_date ?? '',
    participation_end_date: props.bill.participation_end_date ?? '',
    tags_input: props.bill.tags?.join(', ') ?? '',
    pdf_file: null as File | null,
});

const submit = () => {
    form
        .transform((data) => {
            const { tags_input, pdf_file, ...rest } = data;

            const payload: Record<string, unknown> = {
                ...rest,
                tags: tags_input
                    ? tags_input
                          .split(',')
                          .map((tag) => tag.trim())
                          .filter(Boolean)
                    : null,
            };

            if (pdf_file) {
                payload.pdf_file = pdf_file;
            }

            return payload;
        })
        .put(billRoutes.update({ bill: props.bill.id }).url);
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.pdf_file = target.files?.[0] ?? null;
};
</script>

<template>
    <Head :title="`Edit ${props.bill.title}`" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header>
                <h1 class="text-3xl font-semibold text-foreground">Edit bill</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Update bill details, participation timelines, or supporting materials.
                </p>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                    <h2 class="text-lg font-semibold text-foreground">Bill details</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label for="title" class="text-sm font-medium text-foreground">Title</label>
                            <Input id="title" v-model="form.title" type="text" class="h-11" />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="description" class="text-sm font-medium text-foreground">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="space-y-2">
                            <label for="type" class="text-sm font-medium text-foreground">Type</label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="public">Public bill</option>
                                <option value="private">Private members bill</option>
                                <option value="money">Money bill</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="space-y-2">
                            <label for="house" class="text-sm font-medium text-foreground">House</label>
                            <select
                                id="house"
                                v-model="form.house"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="national_assembly">National Assembly</option>
                                <option value="senate">Senate</option>
                                <option value="both">Both houses</option>
                            </select>
                            <InputError :message="form.errors.house" />
                        </div>

                        <div class="space-y-2">
                            <label for="status" class="text-sm font-medium text-foreground">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="draft">Draft</option>
                                <option value="gazetted">Gazetted</option>
                                <option value="open_for_participation">Open for participation</option>
                                <option value="closed">Closed</option>
                                <option value="committee_review">Committee review</option>
                                <option value="passed">Passed</option>
                                <option value="rejected">Rejected</option>
                            </select>
                            <InputError :message="form.errors.status" />
                        </div>

                        <div class="space-y-2">
                            <label for="sponsor" class="text-sm font-medium text-foreground">Sponsor</label>
                            <Input id="sponsor" v-model="form.sponsor" type="text" />
                            <InputError :message="form.errors.sponsor" />
                        </div>

                        <div class="space-y-2">
                            <label for="committee" class="text-sm font-medium text-foreground">Committee</label>
                            <Input id="committee" v-model="form.committee" type="text" />
                            <InputError :message="form.errors.committee" />
                        </div>

                        <div class="space-y-2">
                            <label for="gazette_date" class="text-sm font-medium text-foreground">Gazette date</label>
                            <Input id="gazette_date" v-model="form.gazette_date" type="date" />
                            <InputError :message="form.errors.gazette_date" />
                        </div>

                        <div class="space-y-2">
                            <label for="participation_start_date" class="text-sm font-medium text-foreground">Participation start</label>
                            <Input id="participation_start_date" v-model="form.participation_start_date" type="date" />
                            <InputError :message="form.errors.participation_start_date" />
                        </div>

                        <div class="space-y-2">
                            <label for="participation_end_date" class="text-sm font-medium text-foreground">Participation end</label>
                            <Input id="participation_end_date" v-model="form.participation_end_date" type="date" />
                            <InputError :message="form.errors.participation_end_date" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="tags" class="text-sm font-medium text-foreground">Tags</label>
                            <Input id="tags" v-model="form.tags_input" type="text" />
                            <InputError :message="form.errors.tags" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="pdf_file" class="text-sm font-medium text-foreground">Replace bill PDF</label>
                            <input
                                id="pdf_file"
                                type="file"
                                accept="application/pdf"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                @change="handleFileChange"
                            />
                            <InputError :message="form.errors.pdf_file" />
                            <p v-if="props.bill.pdf_path" class="text-xs text-muted-foreground">
                                Current file: {{ props.bill.pdf_path }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="billRoutes.show({ bill: props.bill.id }).url"
                        class="inline-flex items-center gap-2 rounded-md border border-input px-4 py-2 text-sm font-medium text-foreground transition hover:border-primary"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" :disabled="form.processing">Save changes</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
