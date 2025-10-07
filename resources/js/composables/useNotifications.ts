import { ref, onMounted, onUnmounted } from 'vue'
import { router } from '@inertiajs/vue3'

interface Notification {
  id: string
  type: string
  title: string
  message: string
  read_at: string | null
  created_at: string
  data?: any
}

export function useNotifications(pollingInterval = 30000) {
  const notifications = ref<Notification[]>([])
  const unreadCount = ref(0)
  const isLoading = ref(false)
  let pollInterval: ReturnType<typeof setInterval> | null = null

  // Fetch notifications from server
  const fetchNotifications = async () => {
    isLoading.value = true
    try {
      router.reload({
        only: ['notifications', 'unreadCount'],
        preserveState: true,
        preserveScroll: true,
        onSuccess: (page) => {
          if (page.props.notifications) {
            notifications.value = page.props.notifications as Notification[]
          }
          if (page.props.unreadCount !== undefined) {
            unreadCount.value = page.props.unreadCount as number
          }
        },
      })
    } catch (error) {
      console.error('Failed to fetch notifications:', error)
    } finally {
      isLoading.value = false
    }
  }

  // Mark single notification as read
  const markAsRead = async (notificationId: string) => {
    try {
      await router.post(
        `/notifications/${notificationId}/mark-as-read`,
        {},
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            const notification = notifications.value.find((n) => n.id === notificationId)
            if (notification) {
              notification.read_at = new Date().toISOString()
              unreadCount.value = Math.max(0, unreadCount.value - 1)
            }
          },
        }
      )
    } catch (error) {
      console.error('Failed to mark notification as read:', error)
    }
  }

  // Mark all notifications as read
  const markAllAsRead = async () => {
    try {
      await router.post(
        '/notifications/mark-all-as-read',
        {},
        {
          preserveState: true,
          preserveScroll: true,
          onSuccess: () => {
            notifications.value.forEach((notification) => {
              notification.read_at = new Date().toISOString()
            })
            unreadCount.value = 0
          },
        }
      )
    } catch (error) {
      console.error('Failed to mark all notifications as read:', error)
    }
  }

  // Start polling for new notifications
  const startPolling = () => {
    if (pollInterval) {
      clearInterval(pollInterval)
    }

    pollInterval = setInterval(() => {
      fetchNotifications()
    }, pollingInterval)
  }

  // Stop polling
  const stopPolling = () => {
    if (pollInterval) {
      clearInterval(pollInterval)
      pollInterval = null
    }
  }

  // Lifecycle hooks
  onMounted(() => {
    startPolling()
  })

  onUnmounted(() => {
    stopPolling()
  })

  return {
    notifications,
    unreadCount,
    isLoading,
    fetchNotifications,
    markAsRead,
    markAllAsRead,
    startPolling,
    stopPolling,
  }
}
