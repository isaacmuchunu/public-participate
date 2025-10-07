<script setup lang="ts">
import { ref } from 'vue'
import { Head, router } from '@inertiajs/vue3'
import AppLayout from '@/layouts/AppLayout.vue'
import StatusBadge from '@/components/ui/custom/StatusBadge.vue'
import { Button } from '@/components/ui/button'
import { Input } from '@/components/ui/input'
import { Label } from '@/components/ui/label'
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card'
import { Separator } from '@/components/ui/separator'
import Icon from '@/components/Icon.vue'

interface Submission {
  id: string
  tracking_id: string
  bill: {
    title: string
    bill_number: string
  }
  clause?: {
    clause_number: string
    title: string
  }
  content: string
  submission_type: string
  status: string
  created_at: string
  updated_at: string
  review_notes?: string
  timeline: Array<{
    status: string
    timestamp: string
    notes?: string
  }>
}

interface Props {
  submission?: Submission
  tracking_id?: string
}

const props = defineProps<Props>()

const trackingInput = ref(props.tracking_id || '')
const isTracking = ref(false)

const trackSubmission = () => {
  if (!trackingInput.value) return

  isTracking.value = true
  router.get(
    `/submissions/${trackingInput.value}/track`,
    {},
    {
      preserveState: true,
      onFinish: () => {
        isTracking.value = false
      },
    }
  )
}

const formatDate = (dateString: string) => {
  return new Date(dateString).toLocaleDateString('en-US', {
    year: 'numeric',
    month: 'long',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  })
}

const getStatusIcon = (status: string) => {
  const icons: Record<string, string> = {
    pending: 'clock',
    under_review: 'search',
    approved: 'check-circle',
    rejected: 'x-circle',
  }
  return icons[status] || 'circle'
}
</script>

<template>
  <AppLayout>
    <Head title="Track Submission" />

    <div class="container mx-auto max-w-3xl px-4 py-8">
      <!-- Header -->
      <div class="mb-8">
        <h1 class="mb-2 text-3xl font-bold">Track Submission</h1>
        <p class="text-muted-foreground">
          Enter your tracking ID to check the status of your submission
        </p>
      </div>

      <!-- Tracking Form -->
      <Card class="mb-8">
        <CardContent class="pt-6">
          <form @submit.prevent="trackSubmission" class="space-y-4">
            <div class="space-y-2">
              <Label for="tracking_id">Tracking ID</Label>
              <div class="flex gap-2">
                <Input
                  id="tracking_id"
                  v-model="trackingInput"
                  type="text"
                  placeholder="Enter your tracking ID (e.g., SUB-2024-001234)"
                  class="flex-1"
                  required
                />
                <Button
                  type="submit"
                  :disabled="isTracking || !trackingInput"
                >
                  <Icon
                    v-if="isTracking"
                    name="loader-2"
                    class="mr-2 h-4 w-4 animate-spin"
                  />
                  Track
                </Button>
              </div>
            </div>
          </form>
        </CardContent>
      </Card>

      <!-- Submission Details -->
      <div v-if="submission" class="space-y-6">
        <!-- Status Card -->
        <Card>
          <CardHeader>
            <div class="flex items-center justify-between">
              <CardTitle>Submission Status</CardTitle>
              <StatusBadge :status="submission.status" />
            </div>
          </CardHeader>
          <CardContent class="space-y-4">
            <div class="grid gap-4 sm:grid-cols-2">
              <div>
                <p class="text-muted-foreground text-sm">Tracking ID</p>
                <p class="font-mono font-medium">{{ submission.tracking_id }}</p>
              </div>
              <div>
                <p class="text-muted-foreground text-sm">Submitted</p>
                <p class="font-medium">{{ formatDate(submission.created_at) }}</p>
              </div>
            </div>

            <Separator />

            <div>
              <p class="text-muted-foreground text-sm">Bill</p>
              <p class="font-semibold">{{ submission.bill.title }}</p>
              <p class="text-muted-foreground text-sm">
                {{ submission.bill.bill_number }}
                <span v-if="submission.clause">
                  · Clause {{ submission.clause.clause_number }}
                </span>
              </p>
            </div>

            <div v-if="submission.review_notes">
              <p class="text-muted-foreground mb-2 text-sm">Review Notes</p>
              <div class="rounded-lg bg-muted p-4">
                <p class="text-sm">{{ submission.review_notes }}</p>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Timeline -->
        <Card>
          <CardHeader>
            <CardTitle>Status Timeline</CardTitle>
          </CardHeader>
          <CardContent>
            <div class="space-y-4">
              <div
                v-for="(event, index) in submission.timeline"
                :key="index"
                class="relative flex gap-4"
              >
                <!-- Timeline Line -->
                <div class="flex flex-col items-center">
                  <div
                    :class="[
                      'flex h-10 w-10 items-center justify-center rounded-full',
                      event.status === submission.status
                        ? 'bg-primary text-primary-foreground'
                        : 'bg-muted text-muted-foreground'
                    ]"
                  >
                    <Icon :name="getStatusIcon(event.status)" class="h-5 w-5" />
                  </div>
                  <div
                    v-if="index < submission.timeline.length - 1"
                    class="bg-muted h-full w-0.5"
                  />
                </div>

                <!-- Event Content -->
                <div class="flex-1 pb-8">
                  <p class="font-semibold capitalize">
                    {{ event.status.replace('_', ' ') }}
                  </p>
                  <p class="text-muted-foreground text-sm">
                    {{ formatDate(event.timestamp) }}
                  </p>
                  <p v-if="event.notes" class="text-muted-foreground mt-1 text-sm">
                    {{ event.notes }}
                  </p>
                </div>
              </div>
            </div>
          </CardContent>
        </Card>

        <!-- Content Card -->
        <Card>
          <CardHeader>
            <CardTitle>Your Submission</CardTitle>
          </CardHeader>
          <CardContent>
            <p class="whitespace-pre-wrap text-sm">{{ submission.content }}</p>
          </CardContent>
        </Card>

        <!-- Actions -->
        <div class="flex justify-center">
          <Button variant="outline" @click="() => window.print()">
            <Icon name="printer" class="mr-2 h-4 w-4" />
            Print
          </Button>
        </div>
      </div>
    </div>
  </AppLayout>
</template>
