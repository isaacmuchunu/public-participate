<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import Button from '@/components/ui/button/Button.vue';
import { Input } from '@/components/ui/input';
import AppLayout from '@/layouts/AppLayout.vue';
import * as billRoutes from '@/routes/bills';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

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
    form.transform((data) => {
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
    }).post(billRoutes.store().url);
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
                <h1 class="text-foreground text-3xl font-semibold">Create a new bill</h1>
                <p class="text-muted-foreground mt-2 max-w-2xl text-sm">
                    Provide detailed information about the bill to open it for internal review and public participation.
                </p>
            </header>

            <form class="space-y-6" @submit.prevent="submit">
                <section class="border-sidebar-border/60 bg-card dark:border-sidebar-border rounded-xl border p-6 shadow-sm">
                    <h2 class="text-foreground text-lg font-semibold">Bill details</h2>
                    <div class="mt-4 grid gap-4 md:grid-cols-2">
                        <div class="space-y-2 md:col-span-2">
                            <label for="title" class="text-foreground text-sm font-medium">Title</label>
                            <Input id="title" v-model="form.title" type="text" placeholder="Enter bill title" class="h-11" />
                            <InputError :message="form.errors.title" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="description" class="text-foreground text-sm font-medium">Description</label>
                            <textarea
                                id="description"
                                v-model="form.description"
                                rows="6"
                                class="border-input text-foreground dark:bg-input/30 focus-visible:border-ring focus-visible:ring-ring/50 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none focus-visible:ring-[3px]"
                                placeholder="Describe the purpose, goals, and context of this bill"
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
                            <label for="sponsor" class="text-foreground text-sm font-medium">Sponsor</label>
                            <Input id="sponsor" v-model="form.sponsor" type="text" placeholder="Sponsoring member or ministry" />
                            <InputError :message="form.errors.sponsor" />
                        </div>

                        <div class="space-y-2">
                            <label for="committee" class="text-foreground text-sm font-medium">Committee</label>
                            <Input id="committee" v-model="form.committee" type="text" placeholder="Assigned committee" />
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
                            <Input id="tags" v-model="form.tags_input" type="text" placeholder="Comma-separated tags e.g. governance, climate" />
                            <InputError :message="form.errors.tags" />
                        </div>

                        <div class="space-y-2 md:col-span-2">
                            <label for="pdf_file" class="text-foreground text-sm font-medium">Bill PDF (optional)</label>
                            <input
                                id="pdf_file"
                                type="file"
                                accept="application/pdf"
                                class="border-input text-foreground dark:bg-input/30 file:bg-primary file:text-primary-foreground focus-visible:border-ring focus-visible:ring-ring/50 h-11 w-full rounded-md border bg-transparent px-3 py-2 text-sm outline-none file:mr-4 file:rounded-md file:border-0 file:px-3 file:py-2 file:text-sm file:font-medium focus-visible:ring-[3px]"
                                @change="handleFileChange"
                            />
                            <InputError :message="form.errors.pdf_file" />
                        </div>
                    </div>
                </section>

                <div class="flex items-center justify-end gap-3">
                    <Link
                        :href="billRoutes.index().url"
                        class="border-input text-foreground hover:border-primary inline-flex items-center gap-2 rounded-md border px-4 py-2 text-sm font-medium transition"
                    >
                        Cancel
                    </Link>
                    <Button type="submit" :disabled="form.processing">Create bill</Button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
