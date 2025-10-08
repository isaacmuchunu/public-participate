<script setup lang="ts">
/**
 * ClauseReader Component
 *
 * Main container for the clause-by-clause reading interface.
 * Features:
 * - Sidebar navigation with clause list
 * - Main content area with scrollable clauses
 * - Intersection observer for auto-tracking visible clauses
 * - Keyboard shortcuts (j/k for navigation, c for comment)
 * - Deep linking support to specific clauses
 * - Mobile responsive design
 */

import { router } from '@inertiajs/vue3';
import { useIntersectionObserver, useMagicKeys } from '@vueuse/core';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import ClauseCommentDialog from './ClauseCommentDialog.vue';
import ClauseContent from './ClauseContent.vue';
import ClauseNavigation from './ClauseNavigation.vue';
import ClauseSidebar from './ClauseSidebar.vue';

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
const commentDialogOpen = ref(false);
const commentingClauseId = ref<number | null>(null);
const clauseRefs = ref<Map<number, HTMLElement>>(new Map());

const commentingClause = computed(() => props.clauses.find((c) => c.id === commentingClauseId.value));

// Setup intersection observers for auto-tracking visible clauses
const setupIntersectionObservers = () => {
    props.clauses.forEach((clause) => {
        const clauseEl = computed(() => clauseRefs.value.get(clause.id));

        useIntersectionObserver(
            clauseEl,
            ([{ isIntersecting, intersectionRatio }]) => {
                // Update selected clause when it's the most visible
                if (isIntersecting && intersectionRatio > 0.5) {
                    selectedClauseId.value = clause.id;

                    // Update URL hash without scrolling
                    if (window.location.hash !== `#clause-${clause.id}`) {
                        history.replaceState(null, '', `#clause-${clause.id}`);
                    }
                }
            },
            { threshold: [0.5] },
        );
    });
};

// Set clause ref for intersection observer
const setClauseRef = (id: number, el: HTMLElement | null) => {
    if (el) {
        clauseRefs.value.set(id, el);
    } else {
        clauseRefs.value.delete(id);
    }
};

// Scroll to specific clause
const scrollToClause = (clauseId: number) => {
    const el = clauseRefs.value.get(clauseId);
    if (el) {
        el.scrollIntoView({ behavior: 'smooth', block: 'start' });
        selectedClauseId.value = clauseId;
    }
};

// Open comment dialog for specific clause
const openCommentDialog = (clauseId: number) => {
    commentingClauseId.value = clauseId;
    commentDialogOpen.value = true;
};

// Close comment dialog
const closeCommentDialog = () => {
    commentDialogOpen.value = false;
    commentingClauseId.value = null;
};

// Handle comment submission
const handleCommentSubmit = () => {
    closeCommentDialog();
    // Refresh clause data to update comment counts
    router.reload({ only: ['clauses'] });
};

// Navigate to next clause
const goToNextClause = () => {
    const currentIndex = props.clauses.findIndex((c) => c.id === selectedClauseId.value);
    if (currentIndex < props.clauses.length - 1) {
        scrollToClause(props.clauses[currentIndex + 1].id);
    }
};

// Navigate to previous clause
const goToPreviousClause = () => {
    const currentIndex = props.clauses.findIndex((c) => c.id === selectedClauseId.value);
    if (currentIndex > 0) {
        scrollToClause(props.clauses[currentIndex - 1].id);
    }
};

// Keyboard shortcuts
const { j, k, c } = useMagicKeys();

watch(j, (pressed) => {
    if (pressed) goToNextClause();
});

watch(k, (pressed) => {
    if (pressed) goToPreviousClause();
});

watch(c, (pressed) => {
    if (pressed && selectedClauseId.value && props.canComment) {
        openCommentDialog(selectedClauseId.value);
    }
});

// Initialize on mount
onMounted(() => {
    // Check if there's a hash in the URL to jump to specific clause
    const hash = window.location.hash;
    if (hash.startsWith('#clause-')) {
        const clauseId = parseInt(hash.replace('#clause-', ''));
        const clause = props.clauses.find((c) => c.id === clauseId);
        if (clause) {
            setTimeout(() => scrollToClause(clauseId), 100);
        }
    } else if (props.clauses.length > 0) {
        // Select first clause by default
        selectedClauseId.value = props.clauses[0].id;
    }

    // Setup intersection observers after DOM is ready
    setTimeout(setupIntersectionObservers, 100);
});

// Cleanup on unmount
onUnmounted(() => {
    clauseRefs.value.clear();
});
</script>

<template>
    <div class="flex h-auto min-h-[600px] flex-col gap-4 lg:flex-row">
        <!-- Sidebar Navigation (Desktop) -->
        <ClauseSidebar
            :clauses="clauses"
            :selected-clause-id="selectedClauseId"
            :bill-title="bill.title"
            class="hidden lg:block"
            @select-clause="scrollToClause"
        />

        <!-- Mobile Navigation Dropdown -->
        <div class="lg:hidden">
            <ClauseNavigation :clauses="clauses" :current-clause-id="selectedClauseId" @select-clause="scrollToClause" />
        </div>

        <!-- Main Content Area -->
        <div class="flex-1 overflow-y-auto rounded-xl border border-emerald-100/70 bg-white/95 p-6 lg:max-h-[800px]">
            <div class="mx-auto max-w-3xl space-y-12">
                <!-- Keyboard Shortcuts Help -->
                <div
                    class="mb-4 rounded-lg border border-emerald-100/70 bg-emerald-50/50 p-3 text-sm text-emerald-800"
                    role="status"
                    aria-label="Keyboard shortcuts help"
                >
                    <p class="font-medium">Keyboard shortcuts:</p>
                    <ul class="mt-2 space-y-1 text-xs">
                        <li><kbd class="rounded bg-white px-1.5 py-0.5 font-mono shadow">j</kbd> Next clause</li>
                        <li><kbd class="rounded bg-white px-1.5 py-0.5 font-mono shadow">k</kbd> Previous clause</li>
                        <li v-if="canComment"><kbd class="rounded bg-white px-1.5 py-0.5 font-mono shadow">c</kbd> Comment on current clause</li>
                    </ul>
                </div>

                <!-- Clause Content -->
                <ClauseContent
                    v-for="clause in clauses"
                    :key="clause.id"
                    :ref="(el) => setClauseRef(clause.id, el as HTMLElement)"
                    :clause="clause"
                    :is-selected="selectedClauseId === clause.id"
                    :can-comment="canComment"
                    @open-comment="openCommentDialog"
                />

                <!-- End of Content Message -->
                <div class="rounded-lg border border-dashed border-emerald-200 p-6 text-center text-sm text-emerald-800/70">
                    <p class="font-medium">You have reached the end of the bill.</p>
                    <p class="mt-2">{{ clauses.length }} {{ clauses.length === 1 ? 'clause' : 'clauses' }} reviewed</p>
                </div>
            </div>
        </div>

        <!-- Comment Dialog -->
        <ClauseCommentDialog
            v-if="commentingClause"
            :open="commentDialogOpen"
            :clause="commentingClause"
            :bill="bill"
            @close="closeCommentDialog"
            @submit="handleCommentSubmit"
        />
    </div>
</template>
