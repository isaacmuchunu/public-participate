<script setup lang="ts">
import Badge from '@/components/ui/badge/Badge.vue';
import { computed } from 'vue';

interface Props {
    status: string;
    label?: string;
}

const props = defineProps<Props>();

const badgeVariant = computed(() => {
    switch (props.status) {
        case 'open_for_participation':
            return 'success';
        case 'closed':
        case 'rejected':
            return 'destructive';
        case 'passed':
            return 'info';
        case 'draft':
            return 'secondary';
        case 'gazetted':
        case 'committee_review':
            return 'warning';
        default:
            return 'default';
    }
});

const formatLabel = (value: string) => value.split('_').join(' ');

const displayLabel = computed(() => props.label ?? formatLabel(props.status));

const ariaLabel = computed(() => {
    const statusText = formatLabel(props.status);
    switch (props.status) {
        case 'open_for_participation':
            return `Status: ${statusText}. Citizens can submit comments.`;
        case 'closed':
            return `Status: ${statusText}. Participation period has ended.`;
        case 'passed':
            return `Status: ${statusText}. Bill has been enacted into law.`;
        case 'rejected':
            return `Status: ${statusText}. Bill was not approved.`;
        case 'draft':
            return `Status: ${statusText}. Bill is being prepared.`;
        case 'gazetted':
            return `Status: ${statusText}. Bill has been officially published.`;
        case 'committee_review':
            return `Status: ${statusText}. Bill is under committee review.`;
        default:
            return `Status: ${statusText}`;
    }
});
</script>

<template>
    <Badge :variant="badgeVariant" :aria-label="ariaLabel" class="capitalize">
        {{ displayLabel }}
    </Badge>
</template>
