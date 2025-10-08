<script setup lang="ts">
/**
 * ClauseNavigation Component
 *
 * Mobile-friendly dropdown navigation for jumping between clauses.
 * Features:
 * - Dropdown/select interface for mobile devices
 * - Quick jump to any clause
 * - Shows current clause in the selector
 * - Accessible label and descriptions
 */

import { cn } from '@/lib/utils';
import { computed } from 'vue';

interface Clause {
    id: number;
    clause_number: string;
    title: string;
}

interface Props {
    clauses: Clause[];
    currentClauseId: number | null;
    class?: string;
}

interface Emits {
    (e: 'selectClause', clauseId: number): void;
}

const props = defineProps<Props>();
const emit = defineEmits<Emits>();

const currentClause = computed(() => props.clauses.find((c) => c.id === props.currentClauseId));

const handleChange = (event: Event) => {
    const target = event.target as HTMLSelectElement;
    const clauseId = parseInt(target.value);
    if (!isNaN(clauseId)) {
        emit('selectClause', clauseId);
    }
};
</script>

<template>
    <div :class="cn('space-y-2', props.class)">
        <label for="clause-navigation" class="text-sm font-semibold text-emerald-900"> Jump to Clause </label>
        <select
            id="clause-navigation"
            :value="currentClauseId || ''"
            aria-label="Navigate to specific clause"
            class="w-full rounded-lg border border-emerald-200 bg-white px-3 py-2.5 text-sm text-emerald-900 outline-none transition focus:border-emerald-400 focus:ring-2 focus:ring-emerald-200"
            @change="handleChange"
        >
            <option value="" disabled>Select a clause...</option>
            <option v-for="clause in clauses" :key="clause.id" :value="clause.id">Clause {{ clause.clause_number }}: {{ clause.title }}</option>
        </select>

        <p v-if="currentClause" class="text-xs text-emerald-800/70">Currently viewing: Clause {{ currentClause.clause_number }}</p>
    </div>
</template>
