<script setup lang="ts">
import { computed } from 'vue'
import { Head, Link, usePage } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import ClauseReader from '@/components/bills/ClauseReader.vue'
import StatusBadge from '@/components/ui/custom/StatusBadge.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import Icon from '@/components/Icon.vue'

interface Clause {
  id: number
  bill_id: number
  clause_number: string
  title: string
  content: string
  order: number
  parent_id: number | null
  children?: Clause[]
  submissions_count: number
  user_has_commented: boolean
}

interface Bill {
  id: number
  title: string
  bill_number: string
  status: string
  house: string
  sponsor: string
  participation_start_date: string
  participation_end_date: string
  submissions_count: number
  summary?: string
  description?: string
  created_at: string
}

interface Props {
  bill: Bill
  clauses: Clause[]
}

const props = defineProps<Props>()
const page = usePage()

const canComment = computed(() => {
  return page.props.auth?.user && props.bill.status === 'open'
})

const getDaysRemaining = () => {
  const endDate = new Date(props.bill.participation_end_date)
  const today = new Date()
  const diffTime = endDate.getTime() - today.getTime()
  const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24))
  return diffDays
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
  })
}

const daysRemaining = getDaysRemaining()
</script>

<template>
  <AppLayout>
    <Head :title="bill.title" />

    <div class="container mx-auto px-4 py-8">
      <!-- Bill Header -->
      <div class="mb-8">
        <div class="mb-4 flex items-center gap-2">
          <Link
            href="/bills"
            class="text-muted-foreground hover:text-foreground flex items-center gap-1 text-sm"
          >
            <Icon name="chevron-left" class="h-4 w-4" />
            Back to Bills
          </Link>
        </div>

        <div class="mb-4 flex flex-wrap items-center gap-2">
          <StatusBadge :status="bill.status" />
          <span class="text-muted-foreground text-sm">{{ bill.house }}</span>
          <span class="text-muted-foreground text-sm">•</span>
          <span class="text-muted-foreground text-sm">{{ bill.bill_number }}</span>
        </div>

        <h1 class="mb-4 text-3xl font-bold">{{ bill.title }}</h1>

        <div class="grid gap-6 md:grid-cols-3">
          <!-- Bill Info Card -->
          <Card class="md:col-span-2">
            <CardHeader>
              <CardTitle>Bill Information</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div v-if="bill.summary" class="space-y-2">
                <h3 class="font-semibold">Summary</h3>
                <p class="text-muted-foreground text-sm">{{ bill.summary }}</p>
              </div>

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <p class="text-muted-foreground text-sm">Sponsor</p>
                  <p class="font-medium">{{ bill.sponsor }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground text-sm">House</p>
                  <p class="font-medium">{{ bill.house }}</p>
                </div>
              </div>

              <Separator />

              <div class="grid gap-4 sm:grid-cols-2">
                <div>
                  <p class="text-muted-foreground text-sm">Participation Opens</p>
                  <p class="font-medium">{{ formatDate(bill.participation_start_date) }}</p>
                </div>
                <div>
                  <p class="text-muted-foreground text-sm">Participation Closes</p>
                  <p class="font-medium">{{ formatDate(bill.participation_end_date) }}</p>
                </div>
              </div>

              <div
                v-if="bill.status === 'open'"
                class="flex items-center gap-2 rounded-lg bg-muted p-4"
              >
                <Icon
                  :name="daysRemaining < 7 ? 'alert-circle' : 'clock'"
                  :class="[
                    'h-5 w-5',
                    daysRemaining < 7 ? 'text-red-600' : 'text-green-600'
                  ]"
                />
                <div>
                  <p class="font-semibold">{{ daysRemaining }} days remaining</p>
                  <p class="text-muted-foreground text-sm">
                    to submit your comments on this bill
                  </p>
                </div>
              </div>
            </CardContent>
          </Card>

          <!-- Stats Card -->
          <Card>
            <CardHeader>
              <CardTitle>Participation Stats</CardTitle>
            </CardHeader>
            <CardContent class="space-y-4">
              <div class="flex items-center gap-3">
                <div class="bg-primary/10 text-primary rounded-full p-3">
                  <Icon name="message-circle" class="h-6 w-6" />
                </div>
                <div>
                  <p class="text-2xl font-bold">{{ bill.submissions_count }}</p>
                  <p class="text-muted-foreground text-sm">Total Comments</p>
                </div>
              </div>

              <div class="flex items-center gap-3">
                <div class="bg-primary/10 text-primary rounded-full p-3">
                  <Icon name="file-text" class="h-6 w-6" />
                </div>
                <div>
                  <p class="text-2xl font-bold">{{ clauses.length }}</p>
                  <p class="text-muted-foreground text-sm">Clauses</p>
                </div>
              </div>

              <Separator />

              <div v-if="canComment" class="space-y-2">
                <Button class="w-full" as-child>
                  <a href="#clause-1">
                    <Icon name="message-square-plus" class="mr-2 h-4 w-4" />
                    Start Commenting
                  </a>
                </Button>
              </div>
              <div v-else-if="!page.props.auth?.user" class="space-y-2">
                <Button class="w-full" as-child>
                  <Link href="/login">
                    Sign in to Comment
                  </Link>
                </Button>
              </div>
            </CardContent>
          </Card>
        </div>
      </div>

      <!-- Clause Reader -->
      <ClauseReader
        :bill="bill"
        :clauses="clauses"
        :can-comment="canComment"
      />
    </div>
  </AppLayout>
</template>
