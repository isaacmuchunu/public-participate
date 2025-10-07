<script setup lang="ts">
import { computed } from 'vue'
import Icon from '@/components/Icon.vue'

interface Props {
  status: string
  showIcon?: boolean
}

const props = withDefaults(defineProps<Props>(), {
  showIcon: true,
})

const badgeConfig = computed(() => {
  const configs: Record<string, { label: string; color: string; icon: string }> = {
    open: {
      label: 'Open',
      color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
      icon: 'circle-check',
    },
    closed: {
      label: 'Closed',
      color: 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-100',
      icon: 'circle-x',
    },
    draft: {
      label: 'Draft',
      color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
      icon: 'file-edit',
    },
    pending: {
      label: 'Pending',
      color: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-100',
      icon: 'clock',
    },
    approved: {
      label: 'Approved',
      color: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-100',
      icon: 'check-circle',
    },
    rejected: {
      label: 'Rejected',
      color: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-100',
      icon: 'x-circle',
    },
    under_review: {
      label: 'Under Review',
      color: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100',
      icon: 'search',
    },
  }

  return configs[props.status] || configs.draft
})
</script>

<template>
  <span
    :class="[
      'inline-flex items-center gap-1 rounded-full px-2 py-1 text-xs font-medium',
      badgeConfig.color
    ]"
    role="status"
    :aria-label="`Status: ${badgeConfig.label}`"
  >
    <Icon
      v-if="showIcon"
      :name="badgeConfig.icon"
      class="h-3 w-3"
      aria-hidden="true"
    />
    {{ badgeConfig.label }}
  </span>
</template>
