<script setup lang="ts">
import { Button } from '@/components/ui/button'
import Icon from '@/components/Icon.vue'

interface Props {
  title: string
  description: string
  icon?: string
  actionText?: string
  actionHref?: string
}

const props = withDefaults(defineProps<Props>(), {
  icon: 'inbox',
})

const emit = defineEmits<{
  action: []
}>()
</script>

<template>
  <div class="flex flex-col items-center justify-center py-12 text-center">
    <div class="bg-muted text-muted-foreground mb-4 rounded-full p-6">
      <Icon :name="icon" class="h-12 w-12" aria-hidden="true" />
    </div>

    <h3 class="mb-2 text-xl font-semibold">{{ title }}</h3>
    <p class="text-muted-foreground mb-6 max-w-md text-sm">
      {{ description }}
    </p>

    <Button
      v-if="actionText"
      :as="actionHref ? 'a' : 'button'"
      :href="actionHref"
      @click="!actionHref && emit('action')"
    >
      {{ actionText }}
    </Button>
  </div>
</template>
