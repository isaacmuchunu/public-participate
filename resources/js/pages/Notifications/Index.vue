<script setup lang="ts">
import AppLayout from '@/layouts/AppLayout.vue';
import { dashboard } from '@/routes';
import * as notificationRoutes from '@/routes/notifications';
import type { BreadcrumbItem } from '@/types';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import Button from '@/components/ui/button/Button.vue';
import Icon from '@/components/Icon.vue';
import {
    bodyForNotification,
    formatNotificationDate,
    iconForNotification,
    linkForNotification,
    titleForNotification,
    type PortalNotification,
} from '@/utils/notifications';

interface PaginationLink {
    url: string | null;
    label: string;
    active: boolean;
}

const props = defineProps<{
    notifications: {
        data: PortalNotification[];
        links: PaginationLink[];
        from: number | null;
        to: number | null;
        total: number;
    };
}>();

const breadcrumbs: BreadcrumbItem[] = [
    { title: 'Notifications', href: notificationRoutes.index().url },
];

const hasNotifications = computed(() => props.notifications.data.length > 0);

const markAsRead = (notification: PortalNotification) => {
    if (notification.read_at) {
        return;
    }

    router.post(notificationRoutes.read({ notification: notification.id }).url, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const markAllAsRead = () => {
    router.post(notificationRoutes.readAll().url, {}, {
        preserveScroll: true,
        preserveState: true,
    });
};

const paginationLabel = (label: string) =>
    label.replaceAll('&laquo;', '«').replaceAll('&raquo;', '»');
</script>

<template>
    <Head title="Notifications" />

    <AppLayout :breadcrumbs="breadcrumbs">
        <div class="flex flex-1 flex-col gap-6 p-6">
            <header class="flex flex-col gap-2 md:flex-row md:items-center md:justify-between">
                <div>
                    <h1 class="text-3xl font-semibold text-foreground">Notifications</h1>
                    <p class="text-sm text-muted-foreground">Stay on top of bill milestones, submission updates, and direct legislator engagement.</p>
                </div>
                <Button variant="outline" class="h-10" :disabled="!props.notifications.data.some((item) => !item.read_at)" @click="markAllAsRead">
                    Mark all as read
                </Button>
            </header>

            <section class="rounded-xl border border-sidebar-border/60 bg-card p-4 shadow-sm dark:border-sidebar-border">
                <div v-if="hasNotifications" class="space-y-4">
                    <article
                        v-for="notification in props.notifications.data"
                        :key="notification.id"
                        class="rounded-lg border p-4 transition-colors"
                        :class="notification.read_at ? 'border-input/50 bg-muted/20' : 'border-primary/60 bg-primary/5'
                        "
                    >
                        <div class="flex flex-col gap-3 md:flex-row md:items-start md:justify-between">
                            <div class="flex gap-3">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-primary/10">
                                    <Icon :name="iconForNotification(notification.type)" class="h-5 w-5 text-primary" />
                                </span>
                                <div class="space-y-1">
                                    <p class="text-sm font-semibold text-foreground">{{ titleForNotification(notification) }}</p>
                                    <p class="text-sm text-muted-foreground">{{ bodyForNotification(notification) }}</p>
                                    <div class="flex flex-wrap items-center gap-3 text-xs text-muted-foreground">
                                        <span>{{ formatNotificationDate(notification.created_at) }}</span>
                                        <span v-if="!notification.read_at" class="rounded-full bg-primary/10 px-2 py-0.5 text-primary">Unread</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button variant="ghost" size="sm" @click="markAsRead(notification)">
                                    Mark read
                                </Button>
                                <Link
                                    v-if="linkForNotification(notification)"
                                    :href="linkForNotification(notification) as string"
                                    class="text-sm font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    View details
                                </Link>
                            </div>
                        </div>
                    </article>

                    <div class="flex flex-col items-center justify-between gap-3 border-t border-sidebar-border/60 pt-4 text-xs text-muted-foreground md:flex-row">
                        <p>
                            Showing
                            <span class="font-medium text-foreground">{{ props.notifications.from ?? 0 }}</span>
                            -
                            <span class="font-medium text-foreground">{{ props.notifications.to ?? 0 }}</span>
                            of
                            <span class="font-medium text-foreground">{{ props.notifications.total }}</span>
                            updates
                        </p>
                        <nav class="flex flex-wrap gap-2">
                            <Link
                                v-for="link in props.notifications.links"
                                :key="link.label"
                                :href="link.url || '#'"
                                class="rounded-md px-3 py-1 text-sm"
                                :class="[
                                    link.active ? 'bg-primary text-primary-foreground' : 'bg-muted text-muted-foreground hover:bg-muted/80',
                                    !link.url && !link.active ? 'pointer-events-none opacity-50' : '',
                                ]"
                            >
                                {{ paginationLabel(link.label) }}
                            </Link>
                        </nav>
                    </div>
                </div>
                <div v-else class="flex flex-col items-center gap-4 py-12 text-center">
                    <div class="flex h-16 w-16 items-center justify-center rounded-full bg-primary/10">
                        <Icon name="bell" class="h-7 w-7 text-primary" />
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold text-foreground">You are all caught up</h3>
                        <p class="text-sm text-muted-foreground">
                            We will alert you here when new bills launch, commentary windows open, or legislators need more detail from you.
                        </p>
                    </div>
                    <Link :href="dashboard.url()" class="text-sm font-medium text-primary underline-offset-4 hover:underline">Return to dashboard</Link>
                </div>
            </section>
        </div>
    </AppLayout>
</template>
