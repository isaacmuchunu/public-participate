<script setup lang="ts">
import { ref, computed, watch } from 'vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Badge } from '@/components/ui/badge'
import { Popover, PopoverContent, PopoverTrigger } from '@/components/ui/popover/index'
import Icon from '@/components/Icon.vue'
import { Search, Filter, X, Calendar, Hash } from 'lucide-vue-next'

interface SearchFilters {
  query: string
  status: string[]
  house: string[]
  dateFrom: string | null
  dateTo: string | null
  tags: string[]
}

interface Props {
  modelValue: SearchFilters
  suggestions?: string[]
}

interface Emits {
  (e: 'update:modelValue', value: SearchFilters): void
  (e: 'search', filters: SearchFilters): void
}

const props = withDefaults(defineProps<Props>(), {
  suggestions: () => [],
})

const emit = defineEmits<Emits>()

const filters = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value),
})

const searchQuery = ref('')
const showAdvanced = ref(false)

const statusOptions = [
  { value: 'draft', label: 'Draft' },
  { value: 'gazetted', label: 'Gazetted' },
  { value: 'open_for_participation', label: 'Open for Participation' },
  { value: 'closed', label: 'Closed' },
  { value: 'committee_review', label: 'Committee Review' },
  { value: 'passed', label: 'Passed' },
  { value: 'rejected', label: 'Rejected' },
]

const houseOptions = [
  { value: 'national_assembly', label: 'National Assembly' },
  { value: 'senate', label: 'Senate' },
  { value: 'both', label: 'Joint Sittings' },
]

const tagOptions = [
  { value: 'governance', label: 'Governance' },
  { value: 'health', label: 'Health' },
  { value: 'education', label: 'Education' },
  { value: 'agriculture', label: 'Agriculture' },
  { value: 'economy', label: 'Economy' },
]

const addFilter = (type: keyof SearchFilters, value: string) => {
  if (Array.isArray(filters.value[type])) {
    if (!(filters.value[type] as string[]).includes(value)) {
      (filters.value[type] as string[]).push(value)
    }
  }
}

const removeFilter = (type: keyof SearchFilters, value: string) => {
  if (Array.isArray(filters.value[type])) {
    const index = (filters.value[type] as string[]).indexOf(value)
    if (index > -1) {
      (filters.value[type] as string[]).splice(index, 1)
    }
  }
}

const clearAllFilters = () => {
  filters.value = {
    query: '',
    status: [],
    house: [],
    dateFrom: null,
    dateTo: null,
    tags: [],
  }
}

const performSearch = () => {
  filters.value.query = searchQuery.value
  emit('search', filters.value)
}

const toggleAdvanced = () => {
  showAdvanced.value = !showAdvanced.value
}

const activeFilterCount = computed(() => {
  return filters.value.status.length +
         filters.value.house.length +
         filters.value.tags.length +
         (filters.value.dateFrom ? 1 : 0) +
         (filters.value.dateTo ? 1 : 0)
})

// Watch for external filter changes
watch(() => props.modelValue, (newFilters) => {
  filters.value = newFilters
}, { deep: true })
</script>

<template>
  <div class="space-y-4">
    <!-- Main Search Bar -->
    <div class="flex gap-2">
      <div class="relative flex-1">
        <Search class="absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
        <Input
          v-model="searchQuery"
          placeholder="Search bills by title, number, or content..."
          class="pl-10 pr-4"
          @keyup.enter="performSearch"
        />
      </div>

      <Button @click="toggleAdvanced" variant="outline" size="icon">
        <Filter class="h-4 w-4" />
      </Button>

      <Button @click="performSearch">
        Search
      </Button>
    </div>

    <!-- Advanced Filters -->
    <Card v-if="showAdvanced" class="border-l-4 border-l-primary">
      <CardHeader class="pb-3">
        <div class="flex items-center justify-between">
          <CardTitle class="text-lg">Advanced Filters</CardTitle>
          <Button
            v-if="activeFilterCount > 0"
            @click="clearAllFilters"
            variant="ghost"
            size="sm"
          >
            Clear All
          </Button>
        </div>
      </CardHeader>

      <CardContent class="space-y-4">
        <!-- Status Filters -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">Status</Label>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="status in statusOptions"
              :key="status.value"
              variant="outline"
              class="cursor-pointer hover:bg-muted"
              @click="filters.status.includes(status.value) ? removeFilter('status', status.value) : addFilter('status', status.value)"
              :class="{ 'bg-primary text-primary-foreground': filters.status.includes(status.value) }"
            >
              {{ status.label }}
            </Badge>
          </div>
        </div>

        <!-- House Filters -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">House</Label>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="house in houseOptions"
              :key="house.value"
              variant="outline"
              class="cursor-pointer hover:bg-muted"
              @click="filters.house.includes(house.value) ? removeFilter('house', house.value) : addFilter('house', house.value)"
              :class="{ 'bg-primary text-primary-foreground': filters.house.includes(house.value) }"
            >
              {{ house.label }}
            </Badge>
          </div>
        </div>

        <!-- Tag Filters -->
        <div class="space-y-2">
          <Label class="text-sm font-medium">Tags</Label>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="tag in tagOptions"
              :key="tag.value"
              variant="outline"
              class="cursor-pointer hover:bg-muted"
              @click="filters.tags.includes(tag.value) ? removeFilter('tags', tag.value) : addFilter('tags', tag.value)"
              :class="{ 'bg-primary text-primary-foreground': filters.tags.includes(tag.value) }"
            >
              {{ tag.label }}
            </Badge>
          </div>
        </div>

        <!-- Date Range -->
        <div class="grid gap-4 md:grid-cols-2">
          <div class="space-y-2">
            <Label for="dateFrom" class="text-sm font-medium">From Date</Label>
            <Input
              id="dateFrom"
              v-model="filters.dateFrom"
              type="date"
              class="w-full"
            />
          </div>
          <div class="space-y-2">
            <Label for="dateTo" class="text-sm font-medium">To Date</Label>
            <Input
              id="dateTo"
              v-model="filters.dateTo"
              type="date"
              class="w-full"
            />
          </div>
        </div>

        <!-- Active Filters Display -->
        <div v-if="activeFilterCount > 0" class="space-y-2">
          <Label class="text-sm font-medium">Active Filters</Label>
          <div class="flex flex-wrap gap-2">
            <Badge
              v-for="status in filters.status"
              :key="`status-${status}`"
              variant="secondary"
              class="flex items-center gap-1"
            >
              Status: {{ statusOptions.find(s => s.value === status)?.label }}
              <Button
                @click="removeFilter('status', status)"
                variant="ghost"
                size="sm"
                class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
              >
                <X class="h-3 w-3" />
              </Button>
            </Badge>

            <Badge
              v-for="house in filters.house"
              :key="`house-${house}`"
              variant="secondary"
              class="flex items-center gap-1"
            >
              House: {{ houseOptions.find(h => h.value === house)?.label }}
              <Button
                @click="removeFilter('house', house)"
                variant="ghost"
                size="sm"
                class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
              >
                <X class="h-3 w-3" />
              </Button>
            </Badge>

            <Badge
              v-for="tag in filters.tags"
              :key="`tag-${tag}`"
              variant="secondary"
              class="flex items-center gap-1"
            >
              Tag: {{ tagOptions.find(t => t.value === tag)?.label }}
              <Button
                @click="removeFilter('tags', tag)"
                variant="ghost"
                size="sm"
                class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
              >
                <X class="h-3 w-3" />
              </Button>
            </Badge>

            <Badge
              v-if="filters.dateFrom || filters.dateTo"
              variant="secondary"
              class="flex items-center gap-1"
            >
              Date: {{ filters.dateFrom || '...' }} - {{ filters.dateTo || '...' }}
              <Button
                @click="filters.dateFrom = null; filters.dateTo = null"
                variant="ghost"
                size="sm"
                class="h-4 w-4 p-0 hover:bg-destructive hover:text-destructive-foreground"
              >
                <X class="h-3 w-3" />
              </Button>
            </Badge>
          </div>
        </div>
      </CardContent>
    </div>

    <!-- Search Suggestions (if provided) -->
    <div v-if="suggestions.length > 0 && searchQuery" class="space-y-2">
      <Label class="text-sm font-medium">Suggestions</Label>
      <div class="flex flex-wrap gap-2">
        <Button
          v-for="suggestion in suggestions.slice(0, 5)"
          :key="suggestion"
          @click="searchQuery = suggestion; performSearch()"
          variant="outline"
          size="sm"
        >
          {{ suggestion }}
        </Button>
      </div>
    </div>
  </div>
</template>