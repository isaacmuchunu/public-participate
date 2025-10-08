<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    form.transform((data) => {
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
    }).put(billRoutes.update({ bill: props.bill.id }).url);
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
                <h1 class="text-foreground text-3xl font-semibold">Edit bill</h1>
                <p class="text-muted-foreground mt-2 max-w-2xl text-sm">Update bill details, participation timelines, or supporting materials.</p>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="border-sidebar-border/60 bg-card dark:border-sidebar-border rounded-xl border p-6 shadow-sm">
                    <h2 class="text-foreground text-lg font-semibold">Bill details</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label for="title" class="text-foreground text-sm font-medium">Title</label>
                            <Input id="title" v-model="form.title" type="text" class="h-11" />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="description" class="text-foreground text-sm font-medium">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                class="border-input text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                            />
                            <InputError :message="form.errors.description" />
                        </div>

                        <div class="space-y-2">
                            <label for="type" class="text-foreground text-sm font-medium">Type</label>
                            <select
                                id="type"
                                v-model="form.type"
                                class="border-input text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                            >
                                <option value="public">Public bill</option>
                                <option value="private">Private members bill</option>
                                <option value="money">Money bill</option>
                            </select>
                            <InputError :message="form.errors.type" />
                        </div>

                        <div class="space-y-2">
                            <label for="house" class="text-foreground text-sm font-medium">House</label>
                            <select
                                id="house"
                                v-model="form.house"
                                class="border-input text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
                            >
                                <option value="national_assembly">National Assembly</option>
                                <option value="senate">Senate</option>
                                <option value="both">Both houses</option>
                            </select>
                            <InputError :message="form.errors.house" />
                        </div>

                        <div class="space-y-2">
                            <label for="status" class="text-foreground text-sm font-medium">Status</label>
                            <select
                                id="status"
                                v-model="form.status"
                                class="border-input text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-md border bg-transparent px-3 text-sm outline-none focus-visible:ring-[3px]"
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
                            <label for="sponsor" class="text-foreground text-sm font-medium">Sponsor</label>
                            <Input id="sponsor" v-model="form.sponsor" type="text" />
                            <InputError :message="form.errors.sponsor" />
                        </div>

                        <div class="space-y-2">
                            <label for="committee" class="text-foreground text-sm font-medium">Committee</label>
                            <Input id="committee" v-model="form.committee" type="text" />
                            <InputError :message="form.errors.committee" />
                        </div>

                        <div class="space-y-2">
                            <label for="gazette_date" class="text-foreground text-sm font-medium">Gazette date</label>
                            <Input id="gazette_date" v-model="form.gazette_date" type="date" />
                            <InputError :message="form.errors.gazette_date" />
                        </div>

                        <div class="space-y-2">
                            <label for="participation_start_date" class="text-foreground text-sm font-medium">Participation start</label>
                            <Input id="participation_start_date" v-model="form.participation_start_date" type="date" />
                            <InputError :message="form.errors.participation_start_date" />
                        </div>

                        <div class="space-y-2">
                            <label for="participation_end_date" class="text-foreground text-sm font-medium">Participation end</label>
                            <Input id="participation_end_date" v-model="form.participation_end_date" type="date" />
                            <InputError :message="form.errors.participation_end_date" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="tags" class="text-foreground text-sm font-medium">Tags</label>
                            <Input id="tags" v-model="form.tags_input" type="text" />
                            <InputError :message="form.errors.tags" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="pdf_file" class="text-foreground text-sm font-medium">Replace bill PDF</label>
                            <input
                                id="pdf_file"
                                type="file"
                                accept="application/pdf"
                                class="border-input text-foreground dark:bg-input/30 file:bg-primary file:text-primary-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none file:mr-4 file:rounded-md file:border-0 file:px-3 file:py-2 file:text-sm file:font-medium focus-visible:ring-[3px]"
                                @change="handleFileChange"
                            />
                            <InputError :message="form.errors.pdf_file" />
                            <p v-if="props.bill.pdf_path" class="text-muted-foreground text-xs">Current file: {{ props.bill.pdf_path }}</p>
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="billRoutes.show({ bill: props.bill.id }).url"
                        class="border-input text-foreground hover:border-primary inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" :disabled="form.processing">Save changes</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
