<script setup lang="ts">
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';
import { Link } from '@inertiajs/vue3';

defineProps<{
    title?: string;
    description?: string;
}>();
</script>

<template>
    <div class="bg-background grid min-h-svh lg:grid-cols-[1.1fr,0.9fr]">
        <div class="relative hidden overflow-hidden bg-gradient-to-br from-emerald-600 via-emerald-500 to-sky-500 text-white lg:flex">
            <div class="pointer-events-none absolute inset-0">
                <span class="absolute -left-24 top-24 h-64 w-64 rounded-full bg-white/25 blur-3xl" />
                <span class="absolute bottom-20 right-10 h-72 w-72 rounded-full bg-white/10 blur-3xl" />
                <span class="absolute inset-x-0 bottom-0 h-40 bg-gradient-to-t from-emerald-950/30 to-transparent" />
            </div>
            <div class="relative mx-auto flex w-full max-w-2xl flex-col justify-between gap-12 p-12">
                <div class="flex items-center gap-3 text-sm font-medium uppercase tracking-[0.35em]">
                    <Link :href="home()" class="flex items-center gap-3">
                        <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-white/10 backdrop-blur">
                            <AppLogoIcon class="size-9 text-white" />
                        </span>
                        <span class="text-xs font-semibold text-white/80">Public Participation Service</span>
                    </Link>
                </div>

                <div class="space-y-6">
                    <p class="text-sm font-medium uppercase text-white/70">Welcome to the civic engagement workspace</p>
                    <h2 class="text-4xl font-semibold leading-tight">Build trust with transparent consultation and evidence-led decision making.</h2>
                    <p class="text-base text-white/70">
                        Manage national participation cycles, capture public insights, and align legislative action with citizen priorities in one
                        collaborative platform.
                    </p>
                </div>

                <dl class="grid gap-8 sm:grid-cols-3">
                    <div>
                        <dd class="text-3xl font-semibold">142k+</dd>
                        <dt class="text-sm text-white/70">Verified citizens</dt>
                    </div>
                    <div>
                        <dd class="text-3xl font-semibold">480+</dd>
                        <dt class="text-sm text-white/70">Legislator briefings</dt>
                    </div>
                    <div>
                        <dd class="text-3xl font-semibold">92%</dd>
                        <dt class="text-sm text-white/70">Submissions resolved on time</dt>
                    </div>
                </dl>
            </div>
        </div>

        <div class="bg-background relative flex items-center justify-center px-6 py-16 sm:px-10 lg:px-16">
            <div class="w-full max-w-md">
                <div class="border-border/60 bg-card/95 rounded-3xl border p-10 shadow-xl shadow-emerald-900/5 backdrop-blur">
                    <div class="space-y-2">
                        <p class="text-primary/70 text-xs font-medium uppercase tracking-[0.3em]">Secure access</p>
                        <h1 class="text-foreground text-2xl font-semibold leading-tight">{{ title }}</h1>
                        <p class="text-muted-foreground text-sm">{{ description }}</p>
                    </div>
                    <div class="mt-8 space-y-6">
                        <slot />
                    </div>
                </div>
                <p class="text-muted-foreground mt-6 text-center text-xs">
                    Need assistance? <a href="mailto:support@pps.ke" class="text-primary font-medium hover:underline">support@pps.ke</a>
                </p>
            </div>
        </div>
    </div>
</template>
