<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import BillCard from '@/components/bills/BillCard.vue'
import BillFilter from '@/components/bills/BillFilter.vue'
import BillSearch from '@/components/bills/BillSearch.vue'
import EmptyState from '@/components/ui/custom/EmptyState.vue'
import LoadingSkeleton from '@/components/ui/custom/LoadingSkeleton.vue'
import { Button } from '@/components/ui/button'
import Icon from '@/components/Icon.vue'

interface Bill {
  id: number
  title: string
  bill_number: string
  status: string
  house: string
  participation_start_date: string
  participation_end_date: string
  submissions_count: number
  summary?: string
}

interface PaginatedBills {
  data: Bill[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
  links: {
    first: string
    last: string
    prev: string | null
    next: string | null
  }
}

interface Props {
  bills: PaginatedBills
}

defineProps<Props>()

const searchQuery = ref('')
const filters = ref({
  status: [],
  house: [],
  dateFrom: null,
  dateTo: null,
})
const viewMode = ref<'grid' | 'list'>('grid')
const isLoading = ref(false)

const handleSearch = (query: string) => {
  searchQuery.value = query
  applyFiltersAndSearch()
}

const handleFilterChange = () => {
  applyFiltersAndSearch()
}

const applyFiltersAndSearch = () => {
  isLoading.value = true

  router.get(
    '/bills',
    {
      search: searchQuery.value,
      status: filters.value.status,
      house: filters.value.house,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
    },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['bills'],
      onFinish: () => {
        isLoading.value = false
      },
    }
  )
}

const goToPage = (page: number) => {
  router.get(
    '/bills',
    {
      page,
      search: searchQuery.value,
      status: filters.value.status,
      house: filters.value.house,
      date_from: filters.value.dateFrom,
      date_to: filters.value.dateTo,
    },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['bills'],
    }
  )
}
</script>

<template>
  <AppLayout>
    <Head title="Bills" />

    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold">Bills</h1>
        <p class="text-muted-foreground">
          Browse and participate in legislative bills open for public comment
        </p>
      </div>

      <div class="grid gap-6 lg:grid-cols-[300px_1fr]">
        <!-- Sidebar: Filters -->
        <aside class="space-y-6">
          <BillFilter
            v-model="filters"
            @update:model-value="handleFilterChange"
          />
        </aside>

        <!-- Main Content -->
        <main class="space-y-6">
          <!-- Search and View Toggle -->
          <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
              <BillSearch
                v-model="searchQuery"
                @search="handleSearch"
              />
            </div>

            <div class="flex items-center gap-2">
              <Button
                variant="outline"
                size="sm"
                @click="viewMode = 'grid'"
                :class="{ 'bg-muted': viewMode === 'grid' }"
                aria-label="Grid view"
              >
                <Icon name="grid-2x2" class="h-4 w-4" />
              </Button>
              <Button
                variant="outline"
                size="sm"
                @click="viewMode = 'list'"
                :class="{ 'bg-muted': viewMode === 'list' }"
                aria-label="List view"
              >
                <Icon name="list" class="h-4 w-4" />
              </Button>
            </div>
          </div>

          <!-- Results Count -->
          <div
            v-if="bills.data.length > 0"
            class="text-muted-foreground text-sm"
            role="status"
            aria-live="polite"
          >
            Showing {{ bills.from }} to {{ bills.to }} of {{ bills.total }} bills
          </div>

          <!-- Loading State -->
          <LoadingSkeleton v-if="isLoading" :count="3" variant="card" />

          <!-- Bills Grid/List -->
          <div
            v-else-if="bills.data.length > 0"
            :class="[
              viewMode === 'grid'
                ? 'grid gap-6 sm:grid-cols-2 xl:grid-cols-3'
                : 'space-y-4'
            ]"
          >
            <BillCard
              v-for="bill in bills.data"
              :key="bill.id"
              :bill="bill"
            />
          </div>

          <!-- Empty State -->
          <EmptyState
            v-else
            title="No bills found"
            description="There are currently no bills matching your search criteria. Try adjusting your filters or check back later for new bills."
            icon="inbox"
          />

          <!-- Pagination -->
          <div
            v-if="bills.last_page > 1"
            class="flex items-center justify-center gap-2"
          >
            <Button
              variant="outline"
              size="sm"
              :disabled="!bills.links.prev"
              @click="goToPage(bills.current_page - 1)"
              aria-label="Previous page"
            >
              <Icon name="chevron-left" class="h-4 w-4" />
              Previous
            </Button>

            <span class="text-muted-foreground text-sm">
              Page {{ bills.current_page }} of {{ bills.last_page }}
            </span>

            <Button
              variant="outline"
              size="sm"
              :disabled="!bills.links.next"
              @click="goToPage(bills.current_page + 1)"
              aria-label="Next page"
            >
              Next
              <Icon name="chevron-right" class="h-4 w-4" />
            </Button>
          </div>
        </main>
      </div>
    </div>
  </AppLayout>
</template>
