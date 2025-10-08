/**
 * TypeScript Type Definitions for ClauseReader System
 *
 * This file contains all TypeScript interfaces and types used across
 * the ClauseReader component system for type safety and IntelliSense.
 */

/**
 * Represents a single clause within a bill
 */
export interface Clause {
    id: number;
    bill_id: number;
    clause_number: string; // e.g., "1.2.3", "4", "5.1"
    title: string;
    content: string;
    order: number;
    parent_id: number | null;
    children?: Clause[];
    submissions_count: number;
    user_has_commented: boolean;
    created_at?: string;
    updated_at?: string;
}

/**
 * Represents basic bill information needed for clause reader
 */
export interface Bill {
    id: number;
    title: string;
    bill_number: string;
    status: string;
    house: string;
    type: string;
}

/**
 * Represents a submission/comment on a clause
 */
export interface ClauseSubmission {
    id: number;
    clause_id: number;
    bill_id: number;
    user_id: number;
    user?: {
        id: number;
        name: string;
        avatar_url?: string;
    };
    submission_type: 'comment' | 'suggestion' | 'objection' | 'support';
    content: string;
    status: 'pending' | 'reviewed' | 'included' | 'rejected';
    is_anonymous: boolean;
    created_at: string;
    updated_at: string;
}

/**
 * Represents a text highlight within a clause
 */
export interface ClauseHighlight {
    id: string;
    clause_id: number;
    user_id: number;
    start_offset: number;
    end_offset: number;
    text: string;
    color: 'yellow' | 'green' | 'blue' | 'pink';
    note?: string;
    created_at: string;
}

/**
 * Props for ClauseReader component
 */
export interface ClauseReaderProps {
    bill: Bill;
    clauses: Clause[];
    canComment?: boolean;
}

/**
 * Props for ClauseSidebar component
 */
export interface ClauseSidebarProps {
    clauses: Clause[];
    selectedClauseId: number | null;
    billTitle: string;
    class?: string;
}

/**
 * Props for ClauseContent component
 */
export interface ClauseContentProps {
    clause: Clause;
    isSelected: boolean;
    canComment?: boolean;
}

/**
 * Props for ClauseCommentDialog component
 */
export interface ClauseCommentDialogProps {
    open: boolean;
    clause: Clause;
    bill: Bill;
}

/**
 * Props for ClauseNavigation component
 */
export interface ClauseNavigationProps {
    clauses: Clause[];
    currentClauseId: number | null;
    class?: string;
}

/**
 * Props for ClauseHighlight component
 */
export interface ClauseHighlightProps {
    clauseId: number;
    content: string;
    highlights?: ClauseHighlight[];
    enabled?: boolean;
}

/**
 * Form data for submitting a clause comment
 */
export interface ClauseCommentFormData {
    bill_id: number;
    clause_id: number;
    submission_type: 'comment';
    content: string;
    is_anonymous: boolean;
}

/**
 * Navigation state for clause reader
 */
export interface ClauseNavigationState {
    currentClauseId: number | null;
    previousClauseId: number | null;
    historyStack: number[];
}

/**
 * Analytics data for clause engagement
 */
export interface ClauseAnalytics {
    clause_id: number;
    views_count: number;
    comments_count: number;
    average_reading_time: number;
    sentiment_score?: number;
}

/**
 * Color options for text highlighting
 */
export const HIGHLIGHT_COLORS = {
    YELLOW: 'yellow',
    GREEN: 'green',
    BLUE: 'blue',
    PINK: 'pink',
} as const;

export type HighlightColor = (typeof HIGHLIGHT_COLORS)[keyof typeof HIGHLIGHT_COLORS];

/**
 * Status options for clause submissions
 */
export const SUBMISSION_STATUS = {
    PENDING: 'pending',
    REVIEWED: 'reviewed',
    INCLUDED: 'included',
    REJECTED: 'rejected',
} as const;

export type SubmissionStatus = (typeof SUBMISSION_STATUS)[keyof typeof SUBMISSION_STATUS];

/**
 * Submission type options
 */
export const SUBMISSION_TYPE = {
    COMMENT: 'comment',
    SUGGESTION: 'suggestion',
    OBJECTION: 'objection',
    SUPPORT: 'support',
} as const;

export type SubmissionType = (typeof SUBMISSION_TYPE)[keyof typeof SUBMISSION_TYPE];
