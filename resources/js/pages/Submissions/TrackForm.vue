<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import PublicLayout from '@/layouts/PublicLayout.vue';
import * as submissionRoutes from '@/routes/submissions';
import type { BreadcrumbItem, User } from '@/types';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import { Input } from '@/components/ui/input';
import Button from '@/components/ui/button/Button.vue';
import InputError from '@/components/InputError.vue';

const breadcrumbs = computed<BreadcrumbItem[]>(() => [
    { title: 'Submissions', href: submissionRoutes.index().url },
    { title: 'Track submission', href: '/submissions/track' },
]);

const form = useForm({
    tracking_id: '',
});

const submit = () => {
    form.post(submissionRoutes.track().url);
};

const page = usePage<{ auth: { user: User | null } }>();
const layoutComponent = computed(() => (page.props.auth?.user ? AppLayout : PublicLayout));
</script>

<template>
    <Head title="Track submission" />

    <component :is="layoutComponent" :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col items-center justify-center px-4 py-16 md:px-6">
            <div class="w-full max-w-xl rounded-3xl bg-gradient-to-br from-emerald-600 via-emerald-500 to-emerald-400 p-[1px] shadow-xl">
                <div class="rounded-3xl bg-white/95 p-10">
                    <h1 class="text-3xl font-semibold text-emerald-900">Track your submission</h1>
                    <p class="mt-3 text-sm text-emerald-800/80">
                        Enter the 12-character tracking ID from your confirmation message to check the review status of your submission in real time.
                    </p>

                    <form class="mt-8 space-y-5" @submit.prevent="submit">
                        <div class="space-y-2">
                            <label for="tracking_id" class="text-sm font-semibold text-emerald-900">Tracking ID</label>
                            <Input
                                id="tracking_id"
                                v-model="form.tracking_id"
                                type="text"
                                maxlength="12"
                                minlength="12"
                                class="h-12 rounded-xl border border-emerald-200/80 bg-white/80 text-center text-lg font-mono uppercase tracking-[0.3em] text-emerald-900"
                                placeholder="ABC123DEF456"
                            />
                            <InputError :message="form.errors.tracking_id" />
                        </div>

                        <Button
                            type="submit"
                            class="w-full h-12 rounded-full bg-emerald-600 text-sm font-semibold text-white shadow-md transition hover:bg-emerald-700"
                            :disabled="form.processing"
                        >
                            Check status
                        </Button>
                    </form>
                </div>
            </div>
        </div>
    </component>
</template>
