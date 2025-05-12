<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import type { BreadcrumbItem } from '@/types';
import { Button } from '@/components/ui/button';
import { Head, router } from '@inertiajs/vue3';

interface SessionResource {
    id: number;
    session_id: string;
    device: string | null;
    ip_address: string | null;
    location: string | null;
    user_agent: string | null;
    login_at: string;
    last_activity_at: string;
    is_current: boolean;
}

interface Props {
    sessions: SessionResource[];
}

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Active sessions',
        href: '/settings/sessions',
    },
];

const revoke = (id: number) => {
    router.delete(`/settings/sessions/${id}`, {
        preserveScroll: true,
    });
};

const formatDateTime = (value: string) => {
    const date = new Date(value);

    if (Number.isNaN(date.getTime())) {
        return '—';
    }

    return new Intl.DateTimeFormat('en-KE', {
        dateStyle: 'medium',
        timeStyle: 'short',
    }).format(date);
};
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Active sessions" />

        <SettingsLayout>
            <div class="space-y-6">
                <div>
                    <h2 class="text-2xl font-semibold text-foreground">Manage active sessions</h2>
                    <p class="mt-1 text-sm text-muted-foreground">
                        Review devices that are signed in to your account. Revoke any sessions you do not recognise.
                    </p>
                </div>

                <div class="overflow-hidden rounded-xl border border-border bg-card">
                    <div class="grid grid-cols-[1.2fr_1fr_1fr_auto] gap-4 border-b border-border bg-muted/40 px-6 py-3 text-xs font-semibold uppercase tracking-wide text-muted-foreground">
                        <span>Device</span>
                        <span>Sign-in</span>
                        <span>Last active</span>
                        <span class="text-right">&nbsp;</span>
                    </div>
                    <ul class="divide-y divide-border">
                        <li v-for="session in props.sessions" :key="session.id" class="grid grid-cols-[1.2fr_1fr_1fr_auto] gap-4 px-6 py-4 text-sm">
                            <div class="space-y-1">
                                <p class="font-medium text-foreground">
                                    {{ session.device ?? 'Unknown device' }}
                                    <span v-if="session.is_current" class="ml-2 rounded-full bg-green-100 px-2 py-0.5 text-xs font-semibold text-green-700">Current</span>
                                </p>
                                <p class="text-muted-foreground">
                                    {{ session.ip_address ?? 'No IP captured' }} · {{ session.location ?? 'N/A' }}
                                </p>
                                <p class="text-xs text-muted-foreground/80 line-clamp-1">
                                    {{ session.user_agent ?? 'No user agent recorded' }}
                                </p>
                            </div>
                            <p class="text-muted-foreground">{{ formatDateTime(session.login_at) }}</p>
                            <p class="text-muted-foreground">{{ formatDateTime(session.last_activity_at) }}</p>
                            <div class="flex justify-end">
                                <Button variant="outline" size="sm" :disabled="session.is_current" @click="revoke(session.id)">
                                    Revoke
                                </Button>
                            </div>
                        </li>
                        <li v-if="!props.sessions.length" class="px-6 py-12 text-center text-sm text-muted-foreground">
                            You have no saved sessions yet.
                        </li>
                    </ul>
                </div>
            </div>
        </SettingsLayout>
    </AppLayout>
</template>
