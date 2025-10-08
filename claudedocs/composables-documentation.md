# Public Participation Platform - Composables Documentation

**Created**: October 8, 2025
**Tech Stack**: Vue 3.5 Composition API + TypeScript + Inertia.js 2
**Purpose**: Reusable composables for common functionality across the application

---

## Overview

This document describes the four core composables created to reduce code duplication and improve maintainability across the Public Participation Platform.

---

## 1. useBillFiltering

**File**: `resources/js/composables/useBillFiltering.ts`
**Purpose**: Centralized filter logic for bills (status, house, tag, search)

### Features

- Reactive filter state management
- Query string building for Inertia navigation
- Active filter detection
- Filter reset functionality
- Seamless integration with Inertia.js

### Usage

```typescript
import { useBillFiltering } from '@/composables/useBillFiltering'

const { filters, hasActiveFilters, applyFilters, resetFilters, setFilter } = useBillFiltering({
    status: 'open_for_participation',
    house: 'all',
    tag: 'all',
    search: '',
})

// Apply filters with navigation
applyFilters('bills.participate')

// Check if filters are active
if (hasActiveFilters.value) {
    // Show "Clear filters" button
}
```

### API

**Composable Signature**:
```typescript
function useBillFiltering(initialFilters: BillFilters = {}): {
    filters: BillFilters
    hasActiveFilters: ComputedRef<boolean>
    applyFilters: (routeName?: string) => void
    resetFilters: () => void
    setFilter: (key: keyof BillFilters, value: string) => void
}
```

**Types**:
```typescript
interface BillFilters {
    status?: string
    house?: string
    tag?: string
    search?: string
}
```

### Example: Bills Index Page

```vue
<script setup lang="ts">
import { useBillFiltering } from '@/composables/useBillFiltering'

interface Props {
    filters: {
        status?: string
        house?: string
        tag?: string
        search?: string
    }
}

const props = defineProps<Props>()

const { filters, hasActiveFilters, applyFilters, resetFilters } = useBillFiltering(props.filters)
</script>

<template>
    <form @submit.prevent="applyFilters('bills.index')">
        <input v-model="filters.search" placeholder="Search bills..." />
        <select v-model="filters.status">
            <option value="all">All statuses</option>
            <option value="open_for_participation">Open for participation</option>
        </select>
        <button type="submit">Apply</button>
        <button v-if="hasActiveFilters" type="button" @click="resetFilters">Reset</button>
    </form>
</template>
```

---

## 2. usePagination

**File**: `resources/js/composables/usePagination.ts`
**Purpose**: Reusable pagination handling with Inertia.js

### Features

- Full Laravel pagination integration
- Page navigation (next, previous, specific page)
- Pagination metadata (current page, total, per page)
- Loading state management
- Configurable Inertia.js options (preserve state, scroll, partial reloads)
- Pagination summary formatting
- Visible page calculation for UI
- Label formatting (removes HTML entities)

### Usage

```typescript
import { usePagination } from '@/composables/usePagination'

const {
    goToPage,
    nextPage,
    previousPage,
    isLoading,
    hasNextPage,
    currentPage,
    paginationSummary
} = usePagination(
    () => props.bills,
    {
        preserveScroll: true,
        only: ['bills']
    }
)
```

### API

**Composable Signature**:
```typescript
function usePagination<T = any>(
    dataRef: Ref<PaginatedData<T>> | (() => PaginatedData<T>),
    options?: UsePaginationOptions
): UsePaginationReturn
```

**Types**:
```typescript
interface PaginatedData<T = any> {
    data: T[]
    links: PaginationLink[]
    total: number
    from: number | null
    to: number | null
    current_page?: number
    last_page?: number
    per_page?: number
}

interface PaginationLink {
    url: string | null
    label: string
    active: boolean
}

interface UsePaginationOptions {
    preserveState?: boolean      // Default: true
    preserveScroll?: boolean     // Default: false
    only?: string[]              // Partial reload properties
    replace?: boolean            // Default: true
}
```

**Return Properties**:
```typescript
{
    // State
    isLoading: Ref<boolean>

    // Metadata
    currentPage: ComputedRef<number>
    lastPage: ComputedRef<number>
    total: ComputedRef<number>
    from: ComputedRef<number | null>
    to: ComputedRef<number | null>
    perPage: ComputedRef<number>
    paginationSummary: ComputedRef<string>

    // Navigation state
    hasNextPage: ComputedRef<boolean>
    hasPreviousPage: ComputedRef<boolean>
    nextPageUrl: ComputedRef<string | null>
    previousPageUrl: ComputedRef<string | null>

    // Methods
    nextPage: () => void
    previousPage: () => void
    goToPage: (pageNumber: number) => void
    navigateToUrl: (url: string | null) => void
    formatLabel: (label: string) => string
    visiblePages: (maxVisible?: number) => number[]

    // Raw data
    links: ComputedRef<PaginationLink[]>
    data: ComputedRef<T[]>
}
```

### Example: Paginated Bills List

```vue
<script setup lang="ts">
import { usePagination } from '@/composables/usePagination'

interface Props {
    bills: PaginatedData<BillItem>
}

const props = defineProps<Props>()

const {
    nextPage,
    previousPage,
    goToPage,
    hasNextPage,
    hasPreviousPage,
    currentPage,
    visiblePages,
    paginationSummary,
    isLoading
} = usePagination(
    () => props.bills,
    { preserveScroll: true }
)
</script>

<template>
    <div>
        <!-- Bill list -->
        <article v-for="bill in props.bills.data" :key="bill.id">
            {{ bill.title }}
        </article>

        <!-- Pagination summary -->
        <div class="text-sm text-muted-foreground">
            {{ paginationSummary }}
        </div>

        <!-- Pagination controls -->
        <nav class="flex items-center gap-2">
            <button
                @click="previousPage"
                :disabled="!hasPreviousPage || isLoading"
            >
                Previous
            </button>

            <button
                v-for="page in visiblePages(7)"
                :key="page"
                @click="goToPage(page)"
                :class="{ active: page === currentPage }"
            >
                {{ page }}
            </button>

            <button
                @click="nextPage"
                :disabled="!hasNextPage || isLoading"
            >
                Next
            </button>
        </nav>
    </div>
</template>
```

---

## 3. useFormDraft

**File**: `resources/js/composables/useFormDraft.ts`
**Purpose**: Auto-save draft management with debouncing

### Features

- Automatic draft saving to localStorage
- Configurable debounce interval
- Draft loading on initialization
- Dirty state tracking
- Draft clearing
- Draft existence checking

### Usage

```typescript
import { useFormDraft } from '@/composables/useFormDraft'

const {
    formData,
    lastSaved,
    isDirty,
    loadDraft,
    saveDraft,
    clearDraft
} = useFormDraft(
    'submission-draft-123',
    {
        content: '',
        is_anonymous: false
    },
    3000 // Auto-save every 3 seconds
)
```

### API

**Composable Signature**:
```typescript
function useFormDraft<T extends FormData>(
    formKey: string,
    initialData: T,
    autosaveInterval?: number  // Default: 3000ms
): UseFormDraftReturn<T>
```

**Types**:
```typescript
interface FormData {
    [key: string]: any
}

interface UseFormDraftReturn<T> {
    formData: Ref<T>
    lastSaved: Ref<Date | null>
    isDirty: Ref<boolean>
    loadDraft: () => boolean
    saveDraft: () => void
    clearDraft: () => void
    hasDraft: () => boolean
}
```

### Example: Submission Form

```vue
<script setup lang="ts">
import { useFormDraft } from '@/composables/useFormDraft'
import { onMounted } from 'vue'

interface SubmissionFormData {
    content: string
    is_anonymous: boolean
}

const {
    formData,
    lastSaved,
    isDirty,
    loadDraft,
    clearDraft
} = useFormDraft<SubmissionFormData>(
    'submission-draft-bill-123',
    {
        content: '',
        is_anonymous: false
    },
    3000
)

onMounted(() => {
    const draftLoaded = loadDraft()
    if (draftLoaded) {
        toast.info('Draft restored from previous session')
    }
})

const submitForm = () => {
    // Submit form data
    form.post('/submissions', formData.value, {
        onSuccess: () => {
            clearDraft()
            toast.success('Submission sent successfully')
        }
    })
}
</script>

<template>
    <form @submit.prevent="submitForm">
        <textarea v-model="formData.content" placeholder="Your comments..." />
        <label>
            <input type="checkbox" v-model="formData.is_anonymous" />
            Submit anonymously
        </label>

        <div v-if="lastSaved" class="text-xs text-muted-foreground">
            Draft saved {{ formatDistanceToNow(lastSaved) }} ago
        </div>

        <button type="submit" :disabled="!isDirty">Submit</button>
    </form>
</template>
```

---

## 4. useNotifications

**File**: `resources/js/composables/useNotifications.ts`
**Purpose**: Real-time notification handling with polling

### Features

- Automatic notification polling
- Configurable polling interval
- Mark single notification as read
- Mark all notifications as read
- Unread count tracking
- Loading state management
- Automatic lifecycle management (start/stop on mount/unmount)

### Usage

```typescript
import { useNotifications } from '@/composables/useNotifications'

const {
    notifications,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead
} = useNotifications(30000) // Poll every 30 seconds
```

### API

**Composable Signature**:
```typescript
function useNotifications(pollingInterval?: number): UseNotificationsReturn
```

**Types**:
```typescript
interface Notification {
    id: string
    type: string
    title: string
    message: string
    read_at: string | null
    created_at: string
    data?: any
}

interface UseNotificationsReturn {
    notifications: Ref<Notification[]>
    unreadCount: Ref<number>
    isLoading: Ref<boolean>
    fetchNotifications: () => Promise<void>
    markAsRead: (notificationId: string) => Promise<void>
    markAllAsRead: () => Promise<void>
    startPolling: () => void
    stopPolling: () => void
}
```

### Example: Notification Center

```vue
<script setup lang="ts">
import { useNotifications } from '@/composables/useNotifications'

const {
    notifications,
    unreadCount,
    isLoading,
    markAsRead,
    markAllAsRead
} = useNotifications(30000)
</script>

<template>
    <div>
        <!-- Notification badge -->
        <button class="relative">
            <Icon name="bell" />
            <span v-if="unreadCount > 0" class="notification-badge">
                {{ unreadCount }}
            </span>
        </button>

        <!-- Notification list -->
        <div class="notification-panel">
            <div class="flex items-center justify-between p-4">
                <h3 class="font-semibold">Notifications</h3>
                <button
                    v-if="unreadCount > 0"
                    @click="markAllAsRead"
                    class="text-sm"
                >
                    Mark all as read
                </button>
            </div>

            <div v-if="isLoading" class="p-4">
                <Skeleton class="h-12 w-full" />
            </div>

            <div v-else-if="notifications.length === 0" class="p-8 text-center">
                <p class="text-muted-foreground">No notifications</p>
            </div>

            <div v-else>
                <article
                    v-for="notification in notifications"
                    :key="notification.id"
                    :class="{ 'opacity-60': notification.read_at }"
                    class="border-b p-4 hover:bg-muted/50"
                    @click="markAsRead(notification.id)"
                >
                    <h4 class="font-medium">{{ notification.title }}</h4>
                    <p class="text-sm text-muted-foreground">
                        {{ notification.message }}
                    </p>
                    <time class="text-xs text-muted-foreground">
                        {{ formatDistanceToNow(notification.created_at) }} ago
                    </time>
                </article>
            </div>
        </div>
    </div>
</template>
```

---

## Integration Patterns

### Combined Usage Example

Here's how multiple composables can work together in a complex page:

```vue
<script setup lang="ts">
import { useBillFiltering } from '@/composables/useBillFiltering'
import { usePagination } from '@/composables/usePagination'
import { useNotifications } from '@/composables/useNotifications'
import { useFormDraft } from '@/composables/useFormDraft'

interface Props {
    bills: PaginatedData<BillItem>
    filters: BillFilters
}

const props = defineProps<Props>()

// Bill filtering
const {
    filters,
    hasActiveFilters,
    applyFilters,
    resetFilters
} = useBillFiltering(props.filters)

// Pagination
const {
    nextPage,
    previousPage,
    hasNextPage,
    hasPreviousPage,
    paginationSummary
} = usePagination(
    () => props.bills,
    {
        preserveScroll: true,
        only: ['bills']
    }
)

// Real-time notifications
const {
    unreadCount,
    notifications
} = useNotifications(30000)

// Submission draft (if creating submission)
const {
    formData,
    lastSaved,
    clearDraft
} = useFormDraft(
    'submission-draft',
    { content: '', is_anonymous: false },
    3000
)
</script>

<template>
    <!-- Page with all features integrated -->
</template>
```

---

## Benefits

### Code Reusability
- Eliminates duplication across `Bills/Index.vue` and `Bills/Participate.vue`
- Standardized patterns for filtering, pagination, and notifications
- Consistent behavior across the application

### Maintainability
- Single source of truth for business logic
- Easier to test and debug
- Type-safe with TypeScript

### Developer Experience
- Clear, documented APIs
- Intuitive composable names
- Comprehensive JSDoc comments

### Performance
- Efficient state management with Vue 3 reactivity
- Debounced operations (draft saving)
- Optimized Inertia.js integration (partial reloads, state preservation)

---

## Best Practices

### When to Use Composables

**DO use composables for**:
- Business logic shared across multiple components
- Stateful logic that needs lifecycle management
- Reusable patterns (filtering, pagination, notifications)

**DON'T use composables for**:
- Simple utility functions (use `lib/utils.ts`)
- Component-specific logic that won't be reused
- Presentation logic (keep in components)

### Naming Conventions

- Always prefix with `use` (Vue convention)
- Use descriptive names: `useBillFiltering` not `useFilters`
- Return object with clear property names

### TypeScript

- Always define interfaces for props and return values
- Export types alongside composables
- Use generics when appropriate (`usePagination<T>`)

### Testing

Composables should be unit tested with Vitest:

```typescript
// useBillFiltering.test.ts
import { describe, it, expect } from 'vitest'
import { useBillFiltering } from './useBillFiltering'

describe('useBillFiltering', () => {
    it('initializes with default filters', () => {
        const { filters } = useBillFiltering()
        expect(filters.status).toBe('all')
    })

    it('detects active filters correctly', () => {
        const { filters, hasActiveFilters } = useBillFiltering({ status: 'open' })
        expect(hasActiveFilters.value).toBe(true)
    })
})
```

---

## Future Enhancements

### Potential Composables

1. **useClauseNavigation** - Clause-by-clause reading interface
2. **useSubmissionTracking** - Track submission status
3. **useAnalyticsDashboard** - MP/Clerk analytics data
4. **useAccessibilityPreferences** - WCAG compliance settings
5. **useMultiLanguage** - Extended i18n features (already have basic `useI18n`)

### Advanced Features

- **Offline support**: Cache drafts and sync when online
- **WebSocket integration**: Replace polling with real-time push
- **Error recovery**: Automatic retry with exponential backoff
- **Analytics**: Track composable usage and performance

---

## Related Documentation

- [Frontend Architecture Analysis](/claudedocs/frontend-architecture-analysis.md)
- [Component Library Documentation](/resources/js/components/ui/README.md)
- [Inertia.js v2 Features](https://inertiajs.com/releases)
- [Vue 3 Composition API](https://vuejs.org/guide/extras/composition-api-faq.html)

---

**Last Updated**: October 8, 2025
**Maintainer**: Development Team
**Version**: 1.0
