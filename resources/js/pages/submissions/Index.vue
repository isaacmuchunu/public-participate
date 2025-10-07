<script setup lang="ts">
import { ref } from 'vue'
import { Head, Link, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/ui/custom/StatusBadge.vue'
import EmptyState from '@/components/ui/custom/EmptyState.vue'
import { Button } from '@/components/ui/button'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import Icon from '@/components/Icon.vue'

interface Submission {
  id: string
  tracking_id: string
  bill_id: number
  clause_id: number | null
  content: string
  submission_type: string
  status: string
  is_draft: boolean
  created_at: string
  updated_at: string
  bill: {
    title: string
    bill_number: string
  }
  clause?: {
    clause_number: string
    title: string
  }
}

interface PaginatedSubmissions {
  data: Submission[]
  current_page: number
  last_page: number
  per_page: number
  total: number
  from: number
  to: number
}

interface Props {
  submissions: PaginatedSubmissions
}

defineProps<Props>()

const statusFilter = ref<string>('all')

const filterSubmissions = (status: string) => {
  statusFilter.value = status
  router.get(
    '/submissions',
    { status: status === 'all' ? undefined : status },
    {
      preserveState: true,
      preserveScroll: true,
      only: ['submissions'],
    }
  )
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  })
}

const deleteSubmission = (submissionId: string) => {
  if (confirm('Are you sure you want to delete this draft?')) {
    router.delete(`/submissions/${submissionId}`, {
      preserveScroll: true,
    })
  }
}
</script>

<template>
  <AppLayout>
    <Head title="My Submissions" />

    <div class="container mx-auto px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold">My Submissions</h1>
        <p class="text-muted-foreground">
          View and manage your bill comments and submissions
        </p>
      </div>

      <!-- Filter Tabs -->
      <div class="mb-6 flex flex-wrap gap-2">
        <Button
          v-for="status in ['all', 'draft', 'pending', 'under_review', 'approved', 'rejected']"
          :key="status"
          :variant="statusFilter === status ? 'default' : 'outline'"
          size="sm"
          @click="filterSubmissions(status)"
          class="capitalize"
        >
          {{ status.replace('_', ' ') }}
        </Button>
      </div>

      <!-- Results Count -->
      <div
        v-if="submissions.data.length > 0"
        class="text-muted-foreground mb-4 text-sm"
        role="status"
        aria-live="polite"
      >
        Showing {{ submissions.from }} to {{ submissions.to }} of {{ submissions.total }} submissions
      </div>

      <!-- Submissions List -->
      <div v-if="submissions.data.length > 0" class="space-y-4">
        <Card
          v-for="submission in submissions.data"
          :key="submission.id"
          class="transition hover:shadow-lg"
        >
          <CardHeader>
            <div class="flex items-start justify-between gap-4">
              <div class="flex-1">
                <div class="mb-2 flex flex-wrap items-center gap-2">
                  <StatusBadge :status="submission.status" />
                  <span
                    v-if="submission.is_draft"
                    class="rounded-full bg-gray-100 px-2 py-1 text-xs font-medium text-gray-800 dark:bg-gray-900 dark:text-gray-100"
                  >
                    Draft
                  </span>
                </div>

                <CardTitle class="text-lg">
                  <Link
                    :href="`/bills/${submission.bill_id}`"
                    class="hover:underline"
                  >
                    {{ submission.bill.title }}
                  </Link>
                </CardTitle>

                <p class="text-muted-foreground mt-1 text-sm">
                  {{ submission.bill.bill_number }}
                  <span v-if="submission.clause">
                    · Clause {{ submission.clause.clause_number }}
                  </span>
                </p>
              </div>

              <div class="text-right">
                <p class="text-muted-foreground text-sm">
                  {{ formatDate(submission.created_at) }}
                </p>
                <p class="text-muted-foreground mt-1 text-xs">
                  ID: {{ submission.tracking_id }}
                </p>
              </div>
            </div>
          </CardHeader>

          <CardContent>
            <p class="text-muted-foreground mb-4 line-clamp-2 text-sm">
              {{ submission.content }}
            </p>

            <div class="flex flex-wrap gap-2">
              <Button
                v-if="submission.is_draft"
                as-child
                size="sm"
                variant="outline"
              >
                <Link :href="`/submissions/${submission.id}/edit`">
                  <Icon name="edit" class="mr-2 h-4 w-4" />
                  Edit Draft
                </Link>
              </Button>

              <Button
                as-child
                size="sm"
                variant="outline"
              >
                <Link :href="`/submissions/${submission.tracking_id}/track`">
                  <Icon name="eye" class="mr-2 h-4 w-4" />
                  Track Status
                </Link>
              </Button>

              <Button
                v-if="submission.is_draft"
                size="sm"
                variant="outline"
                @click="deleteSubmission(submission.id)"
              >
                <Icon name="trash-2" class="mr-2 h-4 w-4" />
                Delete
              </Button>
            </div>
          </CardContent>
        </Card>
      </div>

      <!-- Empty State -->
      <EmptyState
        v-else
        title="No submissions yet"
        description="You haven't submitted any comments on bills yet. Browse open bills and share your thoughts!"
        icon="inbox"
        action-text="Browse Bills"
        action-href="/bills"
      />

      <!-- Pagination -->
      <div
        v-if="submissions.last_page > 1"
        class="mt-6 flex items-center justify-center gap-2"
      >
        <Button
          variant="outline"
          size="sm"
          :disabled="submissions.current_page === 1"
          @click="filterSubmissions(statusFilter)"
          aria-label="Previous page"
        >
          <Icon name="chevron-left" class="h-4 w-4" />
          Previous
        </Button>

        <span class="text-muted-foreground text-sm">
          Page {{ submissions.current_page }} of {{ submissions.last_page }}
        </span>

        <Button
          variant="outline"
          size="sm"
          :disabled="submissions.current_page === submissions.last_page"
          @click="filterSubmissions(statusFilter)"
          aria-label="Next page"
        >
          Next
          <Icon name="chevron-right" class="h-4 w-4" />
        </Button>
      </div>
    </div>
  </AppLayout>
</template>
