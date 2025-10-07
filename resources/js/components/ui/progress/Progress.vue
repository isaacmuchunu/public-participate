<script setup lang="ts">
import { computed } from 'vue'

interface Props {
  value?: number
  max?: number
  class?: string
}

const props = withDefaults(defineProps<Props>(), {
  value: 0,
  max: 100,
  class: '',
})

const percentage = computed(() => {
  return Math.min(100, Math.max(0, (props.value / props.max) * 100))
})
</script>

<template>
  <div class="relative h-2 w-full overflow-hidden rounded-full bg-muted">
    <div
      class="h-full w-full flex-1 bg-primary transition-all"
      :style="{ transform: `translateX(-${100 - percentage}%)` }"
    />
  </div>
</template>