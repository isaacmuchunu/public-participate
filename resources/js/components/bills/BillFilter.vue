<script setup lang="ts">
import { ref } from 'vue'
import { Button } from '@/components/ui/button'
import { Label } from '@/components/ui/label'
import { Checkbox } from '@/components/ui/checkbox'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'

interface Filters {
  status: string[]
  house: string[]
  dateFrom: string | null
  dateTo: string | null
}

interface Props {
  modelValue: Filters
}

const props = defineProps<Props>()
const emit = defineEmits<{
  'update:modelValue': [value: Filters]
}>()

const localFilters = ref<Filters>({ ...props.modelValue })

const statusOptions = [
  { value: 'open', label: 'Open for Participation' },
  { value: 'closed', label: 'Closed' },
  { value: 'draft', label: 'Draft' },
]

const houseOptions = [
  { value: 'senate', label: 'Senate' },
  { value: 'national_assembly', label: 'National Assembly' },
]

const toggleFilter = (category: 'status' | 'house', value: string) => {
  const current = localFilters.value[category]
  const index = current.indexOf(value)

  if (index > -1) {
    current.splice(index, 1)
  } else {
    current.push(value)
  }

  emit('update:modelValue', localFilters.value)
}

const clearAllFilters = () => {
  localFilters.value = {
    status: [],
    house: [],
    dateFrom: null,
    dateTo: null,
  }
  emit('update:modelValue', localFilters.value)
}

const getActiveFilterCount = () => {
  return (
    localFilters.value.status.length +
    localFilters.value.house.length +
    (localFilters.value.dateFrom ? 1 : 0) +
    (localFilters.value.dateTo ? 1 : 0)
  )
}
</script>

<template>
  <Card>
    <CardHeader>
      <div class="flex items-center justify-between">
        <CardTitle class="flex items-center gap-2">
          <Icon name="filter" class="h-5 w-5" />
          Filters
          <span
            v-if="getActiveFilterCount() > 0"
            class="flex h-5 w-5 items-center justify-center rounded-full bg-primary text-xs font-bold text-primary-foreground"
          >
            {{ getActiveFilterCount() }}
          </span>
        </CardTitle>
        <Button
          v-if="getActiveFilterCount() > 0"
          variant="ghost"
          size="sm"
          @click="clearAllFilters"
        >
          Clear All
        </Button>
      </div>
    </CardHeader>

    <CardContent class="space-y-6">
      <!-- Status Filter -->
      <div class="space-y-3">
        <Label class="text-base font-semibold">Status</Label>
        <div class="space-y-2">
          <div
            v-for="option in statusOptions"
            :key="option.value"
            class="flex items-center gap-2"
          >
            <Checkbox
              :id="`status-${option.value}`"
              :checked="localFilters.status.includes(option.value)"
              @update:checked="toggleFilter('status', option.value)"
            />
            <Label :for="`status-${option.value}`" class="cursor-pointer font-normal">
              {{ option.label }}
            </Label>
          </div>
        </div>
      </div>

      <!-- House Filter -->
      <div class="space-y-3">
        <Label class="text-base font-semibold">House</Label>
        <div class="space-y-2">
          <div
            v-for="option in houseOptions"
            :key="option.value"
            class="flex items-center gap-2"
          >
            <Checkbox
              :id="`house-${option.value}`"
              :checked="localFilters.house.includes(option.value)"
              @update:checked="toggleFilter('house', option.value)"
            />
            <Label :for="`house-${option.value}`" class="cursor-pointer font-normal">
              {{ option.label }}
            </Label>
          </div>
        </div>
      </div>

      <!-- Date Range Filter -->
      <div class="space-y-3">
        <Label class="text-base font-semibold">Participation Period</Label>
        <div class="space-y-2">
          <div>
            <Label for="dateFrom" class="text-sm">From</Label>
            <input
              id="dateFrom"
              v-model="localFilters.dateFrom"
              type="date"
              class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
              @change="emit('update:modelValue', localFilters)"
            />
          </div>
          <div>
            <Label for="dateTo" class="text-sm">To</Label>
            <input
              id="dateTo"
              v-model="localFilters.dateTo"
              type="date"
              class="mt-1 w-full rounded-lg border border-input bg-background px-3 py-2 text-sm"
              @change="emit('update:modelValue', localFilters)"
            />
          </div>
        </div>
      </div>
    </CardContent>
  </Card>
</template>
