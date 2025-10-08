<script setup lang="ts">
/**
 * ClauseContent Component
 *
 * Displays individual clause content with interaction capabilities.
 * Features:
 * - Formatted clause text display
 * - Comment button with count
 * - Bookmark functionality
 * - User comment status indicator
 * - Text selection for highlighting (future enhancement)
 * - Accessible ARIA labels
 */

import Icon from '@/components/Icon.vue';
import Button from '@/components/ui/button/Button.vue';
import { Separator } from '@/components/ui/separator';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Clause {
    id: number;
    clause_number: string;
    title: string;
    content: string;
    submissions_count: number;
    user_has_commented: boolean;
}

interface Props {
    clause: Clause;
    isSelected: boolean;
    canComment?: boolean;
}

interface Emits {
    (e: 'openComment', clauseId: number): void;
}

const props = withDefaults(defineProps<Props>(), {
    canComment: true,
});

const emit = defineEmits<Emits>();

const handleCommentClick = () => {
    emit('openComment', props.clause.id);
};

const commentButtonLabel = computed(() => {
    if (props.clause.user_has_commented) {
        return 'Add another comment';
    }
    return props.clause.submissions_count > 0 ? 'Join the discussion' : 'Comment on this clause';
});
</script>

<template>
    <article
        :id="`clause-${clause.id}`"
        :aria-labelledby="`clause-title-${clause.id}`"
        :class="cn('scroll-mt-4 transition-colors', isSelected && 'rounded-lg ring-2 ring-emerald-500/20')"
        role="article"
    >
        <header class="mb-4">
            <div class="flex items-start justify-between gap-4">
                <div class="flex-1">
                    <h3 :id="`clause-title-${clause.id}`" class="text-xl font-semibold text-emerald-900">Clause {{ clause.clause_number }}</h3>
                    <p class="mt-1 text-base text-emerald-800/70">{{ clause.title }}</p>
                </div>

                <div
                    v-if="clause.user_has_commented"
                    class="flex items-center gap-1.5 rounded-full bg-emerald-100 px-3 py-1 text-xs font-medium text-emerald-700"
                    role="status"
                    aria-label="You have commented on this clause"
                >
                    <Icon name="check-circle" :size="14" />
                    <span>Commented</span>
                </div>
            </div>
        </header>

        <div class="prose prose-lg prose-emerald dark:prose-invert max-w-none">
            <p class="leading-relaxed text-emerald-900/90">{{ clause.content }}</p>
        </div>

        <footer class="mt-6 flex flex-wrap items-center gap-3">
            <Button v-if="canComment" type="button" size="default" class="bg-emerald-600 text-white hover:bg-emerald-700" @click="handleCommentClick">
                <Icon name="message-circle" :size="16" />
                {{ commentButtonLabel }}
            </Button>

            <Button
                type="button"
                variant="outline"
                size="sm"
                class="border-emerald-200 text-emerald-700 hover:border-emerald-400 hover:bg-emerald-50"
                aria-label="Bookmark this clause"
            >
                <Icon name="bookmark" :size="16" />
                Bookmark
            </Button>

            <div class="ml-auto flex items-center gap-2 text-sm text-emerald-800/70">
                <Icon name="message-circle" :size="16" />
                <span aria-label="`${clause.submissions_count} ${clause.submissions_count === 1 ? 'comment' : 'comments'} on this clause`">
                    {{ clause.submissions_count }} {{ clause.submissions_count === 1 ? 'comment' : 'comments' }}
                </span>
            </div>
        </footer>

        <Separator class="mt-12 bg-emerald-100/70" />
    </article>
</template>

<style scoped>
/* Custom prose styles for clause content */
.prose-emerald :deep(p) {
    @apply text-emerald-900/90;
}

.prose-emerald :deep(strong) {
    @apply font-semibold text-emerald-900;
}

.prose-emerald :deep(em) {
    @apply italic text-emerald-800;
}
</style>
