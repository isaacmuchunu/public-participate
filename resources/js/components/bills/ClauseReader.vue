<script setup lang="ts">
import Icon from '@/components/Icon.vue';
import { Button } from '@/components/ui/button';
import { Separator } from '@/components/ui/separator';
import { useIntersectionObserver } from '@vueuse/core';
import { onMounted, ref } from 'vue';
import CommentForm from './CommentForm.vue';

interface Clause {
    id: number;
    bill_id: number;
    clause_number: string;
    title: string;
    content: string;
    order: number;
    parent_id: number | null;
    children?: Clause[];
    submissions_count: number;
    user_has_commented: boolean;
}

interface Bill {
    id: number;
    title: string;
    bill_number: string;
    status: string;
}

interface Props {
    bill: Bill;
    clauses: Clause[];
    canComment?: boolean;
}

const props = withDefaults(defineProps<Props>(), {
    canComment: true,
});

const selectedClauseId = ref<number | null>(null);
const activeCommentClause = ref<number | null>(null);

// Track clause refs for intersection observer
const clauseRefs = new Map<number, HTMLElement>();
const setClauseRef = (id: number, el: any) => {
    if (el) {
        clauseRefs.set(id, el);

        // Setup intersection observer for this clause
        useIntersectionObserver(
            el,
            ([{ isIntersecting }]) => {
                if (isIntersecting && selectedClauseId.value !== id) {
                    selectedClauseId.value = id;
                }
            },
            { threshold: 0.5 },
        );
    }
};

const scrollToClause = (clauseId: number) => {
    const el = clauseRefs.get(clauseId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        selectedClauseId.value = clauseId;
    }
};

const toggleCommentForm = (clauseId: number) => {
    activeCommentClause.value = activeCommentClause.value === clauseId ? null : clauseId;
};

const handleCommentSubmitted = () => {
    activeCommentClause.value = null;
    // Trigger a reload or update to refresh submission counts
};

// Set initial selected clause
onMounted(() => {
    if (props.clauses.length > 0 && !selectedClauseId.value) {
        selectedClauseId.value = props.clauses[0].id;
    }
});
</script>

<template>
    <div class="flex h-screen flex-col md:flex-row">
        <!-- Left Sidebar: Clause Navigation -->
        <aside class="bg-muted/30 w-full overflow-y-auto border-b md:w-64 md:border-b-0 md:border-r">
            <div class="border-b p-4">
                <h2 class="text-lg font-semibold">{{ bill.title }}</h2>
                <p class="text-muted-foreground text-sm">{{ clauses.length }} {{ clauses.length === 1 ? 'clause' : 'clauses' }}</p>
            </div>

            <nav class="p-2">
                <button
                    v-for="clause in clauses"
                    :key="clause.id"
                    type="button"
                    @click="scrollToClause(clause.id)"
                    :class="[
                        'w-full rounded-lg px-3 py-2 text-left text-sm transition',
                        selectedClauseId === clause.id ? 'bg-primary text-primary-foreground' : 'hover:bg-muted',
                    ]"
                    :aria-label="`Navigate to clause ${clause.clause_number}`"
                    :aria-current="selectedClauseId === clause.id ? 'location' : undefined"
                >
                    <div class="font-medium">Clause {{ clause.clause_number }}</div>
                    <div class="text-xs opacity-80">
                        {{ clause.title }}
                    </div>
                    <div v-if="clause.submissions_count > 0" class="mt-1 flex items-center gap-1 text-xs">
                        <Icon name="message-circle" class="h-3 w-3" />
                        {{ clause.submissions_count }}
                    </div>
                    <div v-if="clause.user_has_commented" class="mt-1 flex items-center gap-1 text-xs text-green-600">
                        <Icon name="check-circle" class="h-3 w-3" />
                        Commented
                    </div>
                </button>
            </nav>
        </aside>

        <!-- Main Content: Clause Display -->
        <main class="touch-scroll flex-1 overflow-y-auto">
            <div class="mx-auto max-w-3xl space-y-12 p-4 md:p-8">
                <article
                    v-for="clause in clauses"
                    :key="clause.id"
                    :ref="(el: any) => setClauseRef(clause.id, el)"
                    :id="`clause-${clause.id}`"
                    class="scroll-mt-4"
                >
                    <!-- Clause Header -->
                    <header class="mb-4">
                        <div class="flex items-center justify-between">
                            <div>
                                <h3 class="text-xl font-semibold">Clause {{ clause.clause_number }}</h3>
                                <p class="text-muted-foreground">{{ clause.title }}</p>
                            </div>

                            <span
                                v-if="clause.user_has_commented"
                                class="inline-flex items-center gap-1 rounded-full bg-green-100 px-3 py-1 text-sm font-medium text-green-800 dark:bg-green-900 dark:text-green-100"
                                role="status"
                                aria-label="You have commented on this clause"
                            >
                                <Icon name="check" class="h-3 w-3" />
                                Commented
                            </span>
                        </div>
                    </header>

                    <!-- Clause Content -->
                    <div class="prose prose-lg dark:prose-invert max-w-none">
                        <p>{{ clause.content }}</p>
                    </div>

                    <!-- Comment Section -->
                    <div v-if="canComment" class="mt-6">
                        <Button
                            @click="toggleCommentForm(clause.id)"
                            :variant="activeCommentClause === clause.id ? 'secondary' : 'default'"
                            :aria-expanded="activeCommentClause === clause.id"
                            :aria-controls="`comment-form-${clause.id}`"
                        >
                            <Icon :name="activeCommentClause === clause.id ? 'x' : 'message-circle'" class="h-4 w-4" />
                            {{ activeCommentClause === clause.id ? 'Cancel' : 'Add Comment' }}
                        </Button>

                        <!-- Inline Comment Form -->
                        <div v-if="activeCommentClause === clause.id" :id="`comment-form-${clause.id}`" class="mt-4">
                            <CommentForm :clause="clause" :bill="bill" @submitted="handleCommentSubmitted" @cancelled="activeCommentClause = null" />
                        </div>
                    </div>

                    <!-- Existing Comments Count -->
                    <footer class="text-muted-foreground mt-4 text-sm">
                        {{ clause.submissions_count }}
                        {{ clause.submissions_count === 1 ? 'comment' : 'comments' }} on this clause
                    </footer>

                    <Separator class="mt-12" />
                </article>
            </div>
        </main>
    </div>
</template>
