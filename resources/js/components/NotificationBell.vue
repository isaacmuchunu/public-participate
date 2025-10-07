<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuLabel,
    DropdownMenuSeparator,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import * as notificationRoutes from '@/routes/notifications';
import {
    bodyForNotification,
    iconForNotification,
    linkForNotification,
    relativeTimeFromNow,
    titleForNotification,
    type PortalNotification,
} from '@/utils/notifications';
import { Link, router, usePage } from '@inertiajs/vue3';
import { BellRing, Inbox } from 'lucide-vue-next';
import { computed } from 'vue';

interface SharedNotifications {
    unread_count: number;
    latest: PortalNotification[];
}

const page = usePage<{ notifications: SharedNotifications }>();

const notifications = computed<SharedNotifications>(() => {
    if (!page.props.notifications) {
        return { unread_count: 0, latest: [] };
    }

    return page.props.notifications;
});

const unreadCount = computed(() => notifications.value.unread_count);
const hasUnread = computed(() => unreadCount.value > 0);
const unreadBadge = computed(() => {
    if (!hasUnread.value) {
        return null;
    }

    if (unreadCount.value > 99) {
        return '99+';
    }

    return String(unreadCount.value);
});

const latestNotifications = computed(() => notifications.value.latest ?? []);

let pollInterval: ReturnType<typeof setInterval> | null = null;

onMounted(() => {
    // Poll for notifications every 30 seconds
    pollInterval = setInterval(() => {
        router.reload({
            only: ['notifications'],
            preserveState: true,
            preserveScroll: true,
        });
    }, 30000);
});

onUnmounted(() => {
    if (pollInterval) {
        clearInterval(pollInterval);
    }
});

const markNotificationAsRead = (notification: PortalNotification) => {
    if (notification.read_at) {
        return;
    }

    router.post(
        notificationRoutes.read({ notification: notification.id }).url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const markAllAsRead = () => {
    if (!hasUnread.value) {
        return;
    }

    router.post(
        notificationRoutes.readAll().url,
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const openNotification = (notification: PortalNotification) => {
    const link = linkForNotification(notification);

    if (!link) {
        markNotificationAsRead(notification);

        return;
    }

    router.visit(link);
};
</script>

<template>
    <DropdownMenu>
        <DropdownMenuTrigger as-child>
            <button
                type="button"
                class="relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-transparent bg-muted/60 text-muted-foreground transition-colors hover:bg-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-primary/60"
                aria-label="Open notifications"
            >
                <BellRing class="h-5 w-5" />
                <span
                    v-if="unreadBadge"
                    class="absolute -top-1 -right-1 inline-flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full bg-primary px-1 text-xs font-semibold text-primary-foreground"
                >
                    {{ unreadBadge }}
                </span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <DropdownMenuLabel class="flex items-center justify-between px-3 py-2 text-sm font-medium">
                <span>Notifications</span>
                <span v-if="hasUnread" class="text-xs font-semibold text-primary">{{ unreadCount }} new</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div v-if="latestNotifications.length" class="max-h-80 space-y-1 overflow-y-auto px-1 py-1">
                <DropdownMenuItem
                    v-for="notification in latestNotifications"
                    :key="notification.id"
                    class="flex w-full flex-col gap-2 rounded-lg px-2 py-2 focus:bg-muted/60"
                    @click="openNotification(notification)"
                >
                    <div class="flex items-start gap-3">
                        <span
                            class="mt-1 flex h-9 w-9 shrink-0 items-center justify-center rounded-full"
                            :class="notification.read_at ? 'bg-muted text-muted-foreground' : 'bg-primary/10 text-primary'"
                        >
                            <Icon :name="iconForNotification(notification.type)" class="h-5 w-5" />
                        </span>
                        <div class="flex flex-1 flex-col gap-1">
                            <p class="text-sm leading-tight font-semibold text-foreground">
                                {{ titleForNotification(notification) }}
                            </p>
                            <p class="text-xs leading-snug text-muted-foreground">
                                {{ bodyForNotification(notification) }}
                            </p>
                            <div class="flex flex-wrap items-center gap-3 text-[11px] text-muted-foreground">
                                <span>{{ relativeTimeFromNow(notification.created_at) }}</span>
                                <span
                                    v-if="!notification.read_at"
                                    class="inline-flex items-center gap-1 rounded-full bg-primary/10 px-2 py-0.5 text-[11px] font-medium text-primary"
                                >
                                    New
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="flex items-center justify-end gap-2 text-xs text-muted-foreground">
                        <Button
                            v-if="!notification.read_at"
                            variant="ghost"
                            size="sm"
                            class="h-7 px-2 text-xs"
                            @click.stop="markNotificationAsRead(notification)"
                        >
                            Mark read
                        </Button>
                        <Link
                            v-if="linkForNotification(notification)"
                            :href="linkForNotification(notification) as string"
                            class="text-xs font-medium text-primary underline-offset-4 hover:underline"
                            @click.stop
                        >
                            Open
                        </Link>
                    </div>
                </DropdownMenuItem>
            </div>
            <div v-else class="flex flex-col items-center gap-2 px-4 py-8 text-center text-sm text-muted-foreground">
                <span class="flex h-12 w-12 items-center justify-center rounded-full bg-muted/80">
                    <Inbox class="h-5 w-5" />
                </span>
                <div>
                    <p class="font-medium text-foreground">No alerts right now</p>
                    <p class="text-xs text-muted-foreground">You will see new bill milestones and submission updates here.</p>
                </div>
            </div>
            <DropdownMenuSeparator />
            <div class="flex items-center justify-between px-3 py-2">
                <Button variant="ghost" size="sm" class="h-8 px-3" :disabled="!hasUnread" @click="markAllAsRead"> Mark all as read </Button>
                <Link :href="notificationRoutes.index().url" class="text-xs font-medium text-primary underline-offset-4 hover:underline">
                    View inbox
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
