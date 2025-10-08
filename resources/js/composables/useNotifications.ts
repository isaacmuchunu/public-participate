import { router } from '@inertiajs/vue3';
import { onMounted, onUnmounted, ref } from 'vue';

interface Notification {
    id: string;
    type: string;
    title: string;
    message: string;
    read_at: string | null;
    created_at: string;
    data?: any;
}

interface SharedNotifications {
    unread_count: number;
    latest: Notification[];
}

interface UseNotificationsOptions {
    pollingInterval?: number;
    markAsReadRoute?: (notificationId: string) => string;
    markAllAsReadRoute?: () => string;
}

export function useNotifications(options: UseNotificationsOptions = {}) {
    const notifications = ref<Notification[]>([]);
    const unreadCount = ref(0);
    const isLoading = ref(false);
    let pollInterval: ReturnType<typeof setInterval> | null = null;

    const pollingInterval = options.pollingInterval ?? 30000;

    // Fetch notifications from server
    const fetchNotifications = async () => {
        isLoading.value = true;
        try {
            router.reload({
                only: ['notifications', 'unreadCount'],
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    const shared = page.props.notifications as SharedNotifications | undefined;

                    if (shared) {
                        notifications.value = shared.latest ?? [];
                        unreadCount.value = shared.unread_count ?? unreadCount.value;
                    }

                    if (page.props.unreadCount !== undefined && typeof page.props.unreadCount === 'number') {
                        unreadCount.value = page.props.unreadCount;
                    }
                },
            });
        } catch (error) {
            console.error('Failed to fetch notifications:', error);
        } finally {
            isLoading.value = false;
        }
    };

    // Mark single notification as read
    const markAsRead = async (notificationId: string) => {
        try {
            const url = options.markAsReadRoute ? options.markAsReadRoute(notificationId) : `/notifications/${notificationId}/mark-as-read`;

            await router.post(
                url,
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        const notification = notifications.value.find((n) => n.id === notificationId);
                        if (notification) {
                            notification.read_at = new Date().toISOString();
                            unreadCount.value = Math.max(0, unreadCount.value - 1);
                        }
                    },
                },
            );
        } catch (error) {
            console.error('Failed to mark notification as read:', error);
        }
    };

    // Mark all notifications as read
    const markAllAsRead = async () => {
        try {
            const url = options.markAllAsReadRoute ? options.markAllAsReadRoute() : '/notifications/mark-all-as-read';
            await router.post(
                url,
                {},
                {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        notifications.value.forEach((notification) => {
                            notification.read_at = new Date().toISOString();
                        });
                        unreadCount.value = 0;
                    },
                },
            );
        } catch (error) {
            console.error('Failed to mark all notifications as read:', error);
        }
    };

    // Start polling for new notifications
    const startPolling = () => {
        if (pollInterval) {
            clearInterval(pollInterval);
        }

        pollInterval = setInterval(() => {
            fetchNotifications();
        }, pollingInterval);
    };

    // Stop polling
    const stopPolling = () => {
        if (pollInterval) {
            clearInterval(pollInterval);
            pollInterval = null;
        }
    };

    // Lifecycle hooks
    onMounted(() => {
        startPolling();
    });

    onUnmounted(() => {
        stopPolling();
    });

    return {
        notifications,
        unreadCount,
        isLoading,
        fetchNotifications,
        markAsRead,
        markAllAsRead,
        startPolling,
        stopPolling,
    };
}
