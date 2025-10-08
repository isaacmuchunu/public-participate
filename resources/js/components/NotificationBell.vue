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
import { useNotifications } from '@/composables/useNotifications';
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
import { computed, watch } from 'vue';

interface SharedNotifications {
    unread_count: number;
    latest: PortalNotification[];
}

const page = usePage<{ notifications: SharedNotifications }>();

const {
    notifications,
    unreadCount,
    markAsRead,
    markAllAsRead: markAllNotificationsAsRead,
} = useNotifications({
    pollingInterval: 30000,
    markAsReadRoute: (notificationId) => notificationRoutes.read({ notification: notificationId }).url,
    markAllAsReadRoute: () => notificationRoutes.readAll().url,
});

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

const latestNotifications = computed(() => notifications.value);

watch(
    () => page.props.notifications as SharedNotifications | undefined,
    (value) => {
        if (!value) {
            notifications.value = [];
            unreadCount.value = 0;
            return;
        }

        notifications.value = value.latest ?? [];
        unreadCount.value = value.unread_count ?? 0;
    },
    { immediate: true, deep: true },
);

const markNotificationAsRead = (notification: PortalNotification) => {
    if (notification.read_at) {
        return;
    }

    markAsRead(notification.id);
};

const handleMarkAllAsRead = () => {
    if (!hasUnread.value) {
        return;
    }

    markAllNotificationsAsRead();
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
                class="bg-muted/60 text-muted-foreground hover:bg-muted focus-visible:ring-primary/60 relative inline-flex h-10 w-10 items-center justify-center rounded-full border border-transparent transition-colors focus:outline-none focus-visible:ring-2"
                aria-label="Open notifications"
            >
                <BellRing class="h-5 w-5" />
                <span
                    v-if="unreadBadge"
                    class="bg-primary text-primary-foreground absolute -right-1 -top-1 inline-flex min-h-[1.25rem] min-w-[1.25rem] items-center justify-center rounded-full px-1 text-xs font-semibold"
                >
                    {{ unreadBadge }}
                </span>
            </button>
        </DropdownMenuTrigger>
        <DropdownMenuContent align="end" class="w-80 p-0">
            <DropdownMenuLabel class="flex items-center justify-between px-3 py-2 text-sm font-medium">
                <span>Notifications</span>
                <span v-if="hasUnread" class="text-primary text-xs font-semibold">{{ unreadCount }} new</span>
            </DropdownMenuLabel>
            <DropdownMenuSeparator />
            <div v-if="latestNotifications.length" class="max-h-80 space-y-1 overflow-y-auto px-1 py-1">
                <DropdownMenuItem
                    v-for="notification in latestNotifications"
                    :key="notification.id"
                    class="focus:bg-muted/60 flex w-full flex-col gap-2 rounded-lg px-2 py-2"
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
                            <p class="text-foreground text-sm font-semibold leading-tight">
                                {{ titleForNotification(notification) }}
                            </p>
                            <p class="text-muted-foreground text-xs leading-snug">
                                {{ bodyForNotification(notification) }}
                            </p>
                            <div class="text-muted-foreground flex flex-wrap items-center gap-3 text-[11px]">
                                <span>{{ relativeTimeFromNow(notification.created_at) }}</span>
                                <span
                                    v-if="!notification.read_at"
                                    class="bg-primary/10 text-primary inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-medium"
                                >
                                    New
                                </span>
                            </div>
                        </div>
                    </div>
                    <div class="text-muted-foreground flex items-center justify-end gap-2 text-xs">
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
                            class="text-primary text-xs font-medium underline-offset-4 hover:underline"
                            @click.stop
                        >
                            Open
                        </Link>
                    </div>
                </DropdownMenuItem>
            </div>
            <div v-else class="text-muted-foreground flex flex-col items-center gap-2 px-4 py-8 text-center text-sm">
                <span class="bg-muted/80 flex h-12 w-12 items-center justify-center rounded-full">
                    <Inbox class="h-5 w-5" />
                </span>
                <div>
                    <p class="text-foreground font-medium">No alerts right now</p>
                    <p class="text-muted-foreground text-xs">You will see new bill milestones and submission updates here.</p>
                </div>
            </div>
            <DropdownMenuSeparator />
            <div class="flex items-center justify-between px-3 py-2">
                <Button variant="ghost" size="sm" class="h-8 px-3" :disabled="!hasUnread" @click="handleMarkAllAsRead"> Mark all as read </Button>
                <Link :href="notificationRoutes.index().url" class="text-primary text-xs font-medium underline-offset-4 hover:underline">
                    View inbox
                </Link>
            </div>
        </DropdownMenuContent>
    </DropdownMenu>
</template>
