<script setup lang="ts">
import { computed } from 'vue'
import { cva, type VariantProps } from 'class-variance-authority'

const badgeVariants = cva(
  'inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus-visible-ring',
  {
    variants: {
      variant: {
        default: 'border-transparent bg-primary text-primary-foreground hover:bg-primary/80',
        secondary: 'border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80',
        destructive: 'border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80',
        outline: 'text-foreground',
        success: 'border-transparent bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300',
        warning: 'border-transparent bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-300',
        info: 'border-transparent bg-blue-100 text-blue-700 dark:bg-blue-500/10 dark:text-blue-300',
      },
    },
    defaultVariants: {
      variant: 'default',
    },
  }
)

interface Props extends VariantProps<typeof badgeVariants> {
  class?: string
  ariaLabel?: string
}

const props = withDefaults(defineProps<Props>(), {
  variant: 'default',
})

const classes = computed(() => badgeVariants({ variant: props.variant, class: props.class }))
</script>

<template>
  <span
    :class="classes"
    role="status"
    :aria-label="ariaLabel"
  >
    <slot />
  </span>
</template>