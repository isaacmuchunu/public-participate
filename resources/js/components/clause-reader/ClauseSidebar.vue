<script setup lang="ts">
/**
 * ClauseSidebar Component
 *
 * Sidebar navigation showing list of clauses with metadata.
 * Features:
 * - Hierarchical clause display
 * - Comment count indicators
 * - Active clause highlighting
 * - User comment status badges
 * - Scrollable clause list
 */

import Icon from '@/components/Icon.vue';
import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Clause {
    id: number;
    clause_number: string;
    title: string;
    submissions_count: number;
    user_has_commented: boolean;
    parent_id: number | null;
}

interface Props {
    clauses: Clause[];
    selectedClauseId: number | null;
    billTitle: string;
    class?: string;
}

interface Emits {
    (e: 'selectClause', clauseId: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const totalComments = computed(() => props.clauses.reduce((sum, clause) => sum + clause.submissions_count, 0));

const userCommentedCount = computed(() => props.clauses.filter((clause) => clause.user_has_commented).length);

const handleSelect = (clauseId: number) => {
    emit('selectClause', clauseId);
};
</script>

<template>
    <aside
        :class="cn('w-80 flex-shrink-0 overflow-y-auto rounded-xl border border-emerald-100/70 bg-white/95 shadow-sm', props.class)"
        role="navigation"
        aria-label="Clause navigation"
    >
        <div class="sticky top-0 z-10 border-b border-emerald-100/70 bg-emerald-50/80 p-4 backdrop-blur">
            <h2 class="text-lg font-semibold text-emerald-900">{{ billTitle }}</h2>
            <p class="mt-1 text-sm text-emerald-800/70">{{ clauses.length }} {{ clauses.length === 1 ? 'clause' : 'clauses' }}</p>

            <div class="mt-3 flex items-center gap-4 text-xs text-emerald-800/70">
                <div class="flex items-center gap-1">
                    <Icon name="message-circle" :size="14" />
                    <span>{{ totalComments }} {{ totalComments === 1 ? 'comment' : 'comments' }}</span>
                </div>
                <div v-if="userCommentedCount > 0" class="flex items-center gap-1">
                    <Icon name="check-circle" :size="14" />
                    <span>{{ userCommentedCount }} commented</span>
                </div>
            </div>
        </div>

        <nav class="p-2">
            <button
                v-for="clause in clauses"
                :key="clause.id"
                type="button"
                :aria-label="`Jump to clause ${clause.clause_number}: ${clause.title}`"
                :aria-current="selectedClauseId === clause.id ? 'location' : undefined"
                :class="[
                    'w-full rounded-lg px-3 py-2.5 text-left text-sm transition',
                    'focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2',
                    selectedClauseId === clause.id ? 'bg-emerald-600 text-white shadow-sm' : 'text-emerald-800 hover:bg-emerald-50',
                ]"
                @click="handleSelect(clause.id)"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0 flex-1">
                        <div class="font-semibold">Clause {{ clause.clause_number }}</div>
                        <div :class="['mt-0.5 line-clamp-2 text-xs', selectedClauseId === clause.id ? 'text-white/90' : 'text-emerald-800/70']">
                            {{ clause.title }}
                        </div>
                    </div>

                    <div class="flex flex-col items-end gap-1">
                        <Icon
                            v-if="clause.user_has_commented"
                            name="check-circle"
                            :size="16"
                            :class="[selectedClauseId === clause.id ? 'text-white' : 'text-emerald-600']"
                            aria-label="You have commented on this clause"
                        />
                        <div
                            v-if="clause.submissions_count > 0"
                            :class="['flex items-center gap-1 text-xs', selectedClauseId === clause.id ? 'text-white/90' : 'text-emerald-700']"
                        >
                            <Icon name="message-circle" :size="12" />
                            <span>{{ clause.submissions_count }}</span>
                        </div>
                    </div>
                </div>
            </button>
        </nav>
    </aside>
</template>
