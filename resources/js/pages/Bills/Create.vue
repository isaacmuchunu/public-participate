<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Bills', href: billRoutes.index().url },
    { title: 'Create', href: billRoutes.create().url },
]);

const form = useForm({
    title: '',
    description: '',
    type: 'public',
    house: 'national_assembly',
    sponsor: '',
    committee: '',
    gazette_date: '',
    participation_start_date: '',
    participation_end_date: '',
    tags_input: '',
    pdf_file: null as File | null,
});

const submit = () => {
    form
        .transform((data) => {
        const { tags_input, ...rest } = data;

        return {
            ...rest,
            tags: tags_input
                ? tags_input
                      .split(',')
                      .map((tag) => tag.trim())
                      .filter(Boolean)
                : null,
        };
    })
        .post(billRoutes.store().url);
};

const handleFileChange = (event: Event) => {
    const target = event.target as HTMLInputElement;
    form.pdf_file = target.files?.[0] ?? null;
};
</script>

<template>
    <Head title="Create Bill" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header>
                <h1 class="text-3xl font-semibold text-foreground">Create a new bill</h1>
                <p class="mt-2 max-w-2xl text-sm text-muted-foreground">
                    Provide detailed information about the bill to open it for internal review and public participation.
                </p>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="rounded-xl border border-sidebar-border/60 bg-card p-6 shadow-sm dark:border-sidebar-border">
                    <h2 class="text-lg font-semibold text-foreground">Bill details</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label for="title" class="text-sm font-medium text-foreground">Title</label>
                            <Input id="title" v-model="form.title" type="text" placeholder="Enter bill title" class="h-11" />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="description" class="text-sm font-medium text-foreground">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                class="border-input text-foreground dark:bg-input/30 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Describe the purpose, goals, and context of this bill"
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
                            <label for="sponsor" class="text-sm font-medium text-foreground">Sponsor</label>
                            <Input id="sponsor" v-model="form.sponsor" type="text" placeholder="Sponsoring member or ministry" />
                            <InputError :message="form.errors.sponsor" />
                        </div>

                        <div class="space-y-2">
                            <label for="committee" class="text-sm font-medium text-foreground">Committee</label>
                            <Input id="committee" v-model="form.committee" type="text" placeholder="Assigned committee" />
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
                            <Input
                                id="tags"
                                v-model="form.tags_input"
                                type="text"
                                placeholder="Comma-separated tags e.g. governance, climate"
                            />
                            <InputError :message="form.errors.tags" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="pdf_file" class="text-sm font-medium text-foreground">Bill PDF (optional)</label>
                            <input
                                id="pdf_file"
                                type="file"
                                accept="application/pdf"
                                class="border-input text-foreground dark:bg-input/30 h-11 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none file:mr-4 file:rounded-md file:border-0 file:bg-primary file:px-3 file:py-2 file:text-sm file:font-medium file:text-primary-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                @change="handleFileChange"
                            />
                            <InputError :message="form.errors.pdf_file" />
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="billRoutes.index().url"
                        class="inline-flex items-center gap-2 rounded-md border border-input px-4 py-2 text-sm font-medium text-foreground transition hover:border-primary"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" :disabled="form.processing">Create bill</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
