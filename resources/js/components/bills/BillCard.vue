<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardFooter, CardHeader, CardTitle } from '@/components/ui/card';
import StatusBadge from '@/components/ui/custom/StatusBadge.vue';
import { Link } from '@inertiajs/vue3';

interface Bill {
    id: number;
    title: string;
    bill_number: string;
    status: string;
    house: string;
    participation_start_date: string;
    participation_end_date: string;
    submissions_count: number;
    summary?: string;
}

interface Props {
    bill: Bill;
}

const props = defineProps<Props>();

const getDaysRemaining = () => {
    const endDate = new Date(props.bill.participation_end_date);
    const today = new Date();
    const diffTime = endDate.getTime() - today.getTime();
    const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
    return diffDays;
};

const getProgressPercentage = () => {
    const startDate = new Date(props.bill.participation_start_date);
    const endDate = new Date(props.bill.participation_end_date);
    const today = new Date();

    const total = endDate.getTime() - startDate.getTime();
    const elapsed = today.getTime() - startDate.getTime();
    const percentage = Math.min(100, Math.max(0, (elapsed / total) * 100));

    return Math.round(percentage);
};

const getHouseBadgeColor = (house: string) => {
    return house.toLowerCase() === 'senate'
        ? 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-100'
        : 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-100';
};

const daysRemaining = getDaysRemaining();
const progressPercentage = getProgressPercentage();

const navigateToBill = () => {
    router.visit(`/bills/${props.bill.id}`);
};
</script>

<template>
    <Card
        class="focus-visible-ring transition hover:shadow-lg"
        tabindex="0"
        role="article"
        :aria-labelledby="`bill-title-${bill.id}`"
        @keydown.enter="navigateToBill"
        @keydown.space.prevent="navigateToBill"
        @click="navigateToBill"
    >
        <CardHeader>
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <div class="mb-2 flex flex-wrap items-center gap-2">
                        <StatusBadge :status="bill.status" />
                        <span
                            :class="['rounded-full px-2 py-1 text-xs font-medium', getHouseBadgeColor(bill.house)]"
                            role="status"
                            :aria-label="`Bill house: ${bill.house}`"
                        >
                            {{ bill.house }}
                        </span>
                    </div>
                    <CardTitle class="text-lg">
                        <span :id="`bill-title-${bill.id}`" class="block">
                            {{ bill.title }}
                        </span>
                    </CardTitle>
                    <p class="mt-1 text-sm text-muted-foreground">{{ bill.bill_number }}</p>
                </div>
            </div>
        </CardHeader>

        <CardContent class="space-y-4">
            <!-- Summary -->
            <p v-if="bill.summary" class="line-clamp-2 text-sm text-muted-foreground">
                {{ bill.summary }}
            </p>

            <!-- Progress Bar -->
            <div v-if="bill.status === 'open'" class="space-y-2">
                <div class="flex justify-between text-xs">
                    <span class="text-muted-foreground">Participation Progress</span>
                    <span :class="['font-medium', daysRemaining < 7 ? 'text-red-600' : 'text-green-600']"> {{ daysRemaining }} days remaining </span>
                </div>
                <div class="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        class="h-full bg-primary transition-all"
                        :style="{ width: `${progressPercentage}%` }"
                        role="progressbar"
                        :aria-valuenow="progressPercentage"
                        aria-valuemin="0"
                        aria-valuemax="100"
                        :aria-label="`${progressPercentage}% of participation period elapsed`"
                    />
                </div>
            </div>

            <!-- Submission Count -->
            <div class="flex items-center gap-2 text-sm">
                <Icon name="message-circle" class="h-4 w-4 text-muted-foreground" />
                <span class="text-muted-foreground">
                    {{ bill.submissions_count }}
                    {{ bill.submissions_count === 1 ? 'submission' : 'submissions' }}
                </span>
            </div>
        </CardContent>

        <CardFooter>
            <Button as-child variant="default" class="w-full">
                <Link :href="`/bills/${bill.id}`"> View Bill Details </Link>
            </Button>
        </CardFooter>
    </Card>
</template>
