/**
 * ClauseReader Component System Exports
 *
 * This file provides a centralized export point for all ClauseReader
 * components and related utilities.
 *
 * Usage:
 * ```typescript
 * import { ClauseReader, ClauseContent } from '@/components/clause-reader';
 * ```
 */

// Main Components
export { default as ClauseCommentDialog } from './ClauseCommentDialog.vue';
export { default as ClauseContent } from './ClauseContent.vue';
export { default as ClauseHighlight } from './ClauseHighlight.vue';
export { default as ClauseNavigation } from './ClauseNavigation.vue';
export { default as ClauseReader } from './ClauseReader.vue';
export { default as ClauseSidebar } from './ClauseSidebar.vue';

// Type Exports
export type {
    Bill,
    Clause,
    ClauseAnalytics,
    ClauseCommentDialogProps,
    ClauseCommentFormData,
    ClauseContentProps,
    ClauseHighlightProps,
    ClauseHighlight as ClauseHighlightType,
    ClauseNavigationProps,
    ClauseNavigationState,
    ClauseReaderProps,
    ClauseSidebarProps,
    ClauseSubmission,
    HighlightColor,
    SubmissionStatus,
    SubmissionType,
} from './types';

export { HIGHLIGHT_COLORS, SUBMISSION_STATUS, SUBMISSION_TYPE } from './types';
