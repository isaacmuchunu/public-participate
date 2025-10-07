<script setup lang="ts">
import { SelectTrigger as RekaSelectTrigger } from 'reka-ui'
</script>

<template>
  <RekaSelectTrigger as-child>
    <slot />
  </RekaSelectTrigger>
</template>