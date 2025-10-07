<script setup lang="ts">
import { Skeleton } from '@/components/ui/skeleton'

interface Props {
  variant?: 'card' | 'list' | 'text'
  count?: number
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'card',
  count: 3,
})
</script>

<template>
  <div class="space-y-4" role="status" aria-live="polite" aria-label="Loading content">
    <!-- Card Skeleton -->
    <div
      v-if="variant === 'card'"
      v-for="n in count"
      :key="n"
      class="rounded-xl border p-6 shadow-sm"
    >
      <div class="space-y-4">
        <div class="flex items-center gap-2">
          <Skeleton class="h-6 w-20" />
          <Skeleton class="h-6 w-24" />
        </div>
        <Skeleton class="h-8 w-full" />
        <Skeleton class="h-4 w-32" />
        <Skeleton class="h-16 w-full" />
        <div class="flex gap-2">
          <Skeleton class="h-10 flex-1" />
        </div>
      </div>
    </div>

    <!-- List Skeleton -->
    <div
      v-if="variant === 'list'"
      v-for="n in count"
      :key="n"
      class="flex items-center gap-4 p-4"
    >
      <Skeleton class="h-12 w-12 rounded-full" />
      <div class="flex-1 space-y-2">
        <Skeleton class="h-4 w-full" />
        <Skeleton class="h-4 w-2/3" />
      </div>
    </div>

    <!-- Text Skeleton -->
    <div
      v-if="variant === 'text'"
      class="space-y-2"
    >
      <Skeleton v-for="n in count" :key="n" class="h-4 w-full" />
    </div>

    <span class="sr-only">Loading content, please wait...</span>
  </div>
</template>
