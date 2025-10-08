# ClauseReader Component System

A comprehensive, accessible, and mobile-responsive system for clause-by-clause bill reading and citizen participation.

## Overview

The ClauseReader component system enables citizens to:

- Navigate bills clause by clause
- Track which clauses they've read and commented on
- Submit comments on specific clauses
- Use keyboard shortcuts for efficient navigation
- Deep link to specific clauses

## Components

### 1. ClauseReader (Main Container)

**File**: `ClauseReader.vue`

The main orchestrator component that manages the overall clause reading experience.

**Features**:

- Intersection observer for auto-tracking visible clauses
- Keyboard shortcuts (j/k for navigation, c for comment)
- Deep linking support via URL hash (#clause-123)
- Mobile-responsive layout with sidebar on desktop, dropdown on mobile
- State management for selected clause and comment dialog

**Props**:

```typescript
interface Props {
    bill: Bill; // Bill metadata
    clauses: Clause[]; // Array of clauses to display
    canComment?: boolean; // Whether user can comment (default: true)
}
```

**Keyboard Shortcuts**:

- `j` - Navigate to next clause
- `k` - Navigate to previous clause
- `c` - Open comment dialog for current clause

**Usage**:

```vue
<ClauseReader :bill="bill" :clauses="clauses" :can-comment="true" />
```

---

### 2. ClauseSidebar (Navigation Panel)

**File**: `ClauseSidebar.vue`

Sidebar navigation showing list of all clauses with metadata and active state.

**Features**:

- Hierarchical clause list
- Active clause highlighting
- Comment count indicators
- User comment status badges (checkmark for commented clauses)
- Scrollable with sticky header
- Desktop-only display (hidden on mobile)

**Props**:

```typescript
interface Props {
    clauses: Clause[];
    selectedClauseId: number | null;
    billTitle: string;
    class?: string;
}
```

**Events**:

```typescript
emit('selectClause', clauseId: number)
```

**Accessibility**:

- Proper ARIA labels for navigation
- `aria-current="location"` for active clause
- Keyboard navigable buttons

---

### 3. ClauseContent (Content Display)

**File**: `ClauseContent.vue`

Displays individual clause content with interaction options.

**Features**:

- Formatted clause text with prose styling
- Comment button with dynamic label
- Bookmark functionality
- Comment count display
- User comment status indicator
- Visual feedback for selected clause

**Props**:

```typescript
interface Props {
    clause: Clause;
    isSelected: boolean;
    canComment?: boolean;
}
```

**Events**:

```typescript
emit('openComment', clauseId: number)
```

**Accessibility**:

- Article role with proper labeling
- ARIA labels for all interactive elements
- Status indicators with proper roles

---

### 4. ClauseCommentDialog (Comment Submission)

**File**: `ClauseCommentDialog.vue`

Modal dialog for submitting comments on specific clauses.

**Features**:

- Rich textarea with character counter
- Minimum/maximum character validation (50-5000 chars)
- Anonymous submission option
- Form validation with error handling
- Context display showing which clause is being commented on
- Automatic form reset on close
- Toast notifications for success/error

**Props**:

```typescript
interface Props {
    open: boolean;
    clause: Clause;
    bill: Bill;
}
```

**Events**:

```typescript
emit('close'); // Dialog closed
emit('submit'); // Comment successfully submitted
```

**Form Validation**:

- Minimum 50 characters
- Maximum 5000 characters
- Real-time character count with color coding
- Submit button disabled until valid

**Accessibility**:

- Focus management on open/close
- ARIA labels and descriptions
- Error messages associated with fields
- Keyboard navigation support

---

### 5. ClauseNavigation (Mobile Dropdown)

**File**: `ClauseNavigation.vue`

Mobile-friendly dropdown for quick clause navigation.

**Features**:

- Select dropdown with all clauses
- Shows current clause selection
- Mobile-only display (hidden on desktop)
- Accessible labels and descriptions

**Props**:

```typescript
interface Props {
    clauses: Clause[];
    currentClauseId: number | null;
    class?: string;
}
```

**Events**:

```typescript
emit('selectClause', clauseId: number)
```

---

### 6. ClauseHighlight (Text Highlighting)

**File**: `ClauseHighlight.vue`

Future enhancement component for text selection and highlighting within clauses.

**Features** (Planned):

- Text selection detection
- Multiple color highlights
- Persistent highlight storage
- Comment attachment to highlights
- Hover menu for highlight options

**Props**:

```typescript
interface Props {
    clauseId: number;
    content: string;
    highlights?: Highlight[];
    enabled?: boolean;
}
```

**Events**:

```typescript
emit('highlight', highlight: Omit<Highlight, 'id' | 'createdAt'>)
emit('removeHighlight', highlightId: string)
```

**Status**: Placeholder implementation for future enhancement

---

## TypeScript Types

**File**: `types.ts`

Complete type definitions for the entire ClauseReader system.

**Main Interfaces**:

```typescript
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
    house: string;
    type: string;
}

interface ClauseSubmission {
    id: number;
    clause_id: number;
    user_id: number;
    submission_type: 'comment' | 'suggestion' | 'objection' | 'support';
    content: string;
    status: 'pending' | 'reviewed' | 'included' | 'rejected';
    is_anonymous: boolean;
    created_at: string;
}
```

---

## Installation & Setup

### 1. Import the Component

```vue
<script setup lang="ts">
import ClauseReader from '@/components/clause-reader/ClauseReader.vue';
// Or use named imports:
import { ClauseReader, type Clause, type Bill } from '@/components/clause-reader';
</script>
```

### 2. Backend Requirements

**Controller** (`BillController.php`):

```php
use Inertia\Inertia;

public function show(Bill $bill)
{
    return Inertia::render('Bills/Show', [
        'bill' => $bill->only(['id', 'title', 'bill_number', 'status', 'house', 'type']),
        'clauses' => Inertia::defer(fn() => $bill->clauses()
            ->withCount('submissions')
            ->orderBy('order')
            ->get()
            ->map(fn($clause) => [
                ...$clause->toArray(),
                'user_has_commented' => $clause->submissions()
                    ->where('user_id', auth()->id())
                    ->exists(),
            ])
        ),
    ]);
}
```

**Routes** (`routes/web.php`):

```php
Route::middleware('auth')->group(function () {
    Route::get('/bills/{bill}', [BillController::class, 'show'])->name('bills.show');
    Route::post('/submissions', [SubmissionController::class, 'store'])->name('submissions.store');
});
```

### 3. Database Schema

**Clauses Table**:

```sql
CREATE TABLE clauses (
    id BIGINT PRIMARY KEY,
    bill_id BIGINT NOT NULL,
    clause_number VARCHAR(50) NOT NULL,
    title VARCHAR(255) NOT NULL,
    content TEXT NOT NULL,
    `order` INT NOT NULL,
    parent_id BIGINT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES clauses(id) ON DELETE CASCADE
);
```

**Submissions Table**:

```sql
CREATE TABLE submissions (
    id BIGINT PRIMARY KEY,
    bill_id BIGINT NOT NULL,
    clause_id BIGINT NULL,
    user_id BIGINT NOT NULL,
    submission_type ENUM('comment', 'suggestion', 'objection', 'support'),
    content TEXT NOT NULL,
    status ENUM('pending', 'reviewed', 'included', 'rejected') DEFAULT 'pending',
    is_anonymous BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    FOREIGN KEY (bill_id) REFERENCES bills(id) ON DELETE CASCADE,
    FOREIGN KEY (clause_id) REFERENCES clauses(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);
```

---

## Usage Example

**Complete Page Implementation** (`Bills/Show.vue`):

```vue
<script setup lang="ts">
import { Suspense } from 'vue';
import ClauseReader from '@/components/clause-reader/ClauseReader.vue';
import type { Bill, Clause } from '@/components/clause-reader';

interface Props {
    bill: Bill;
    clauses: Clause[]; // Deferred prop from backend
}

const props = defineProps<Props>();
</script>

<template>
    <div>
        <h1>{{ bill.title }}</h1>

        <section>
            <h2>Read and Comment on Clauses</h2>

            <Suspense>
                <ClauseReader :bill="bill" :clauses="clauses" :can-comment="true" />

                <template #fallback>
                    <div>Loading clauses...</div>
                </template>
            </Suspense>
        </section>
    </div>
</template>
```

---

## Accessibility Features

### WCAG 2.1 AA Compliance

**Keyboard Navigation**:

- All interactive elements keyboard accessible
- Logical tab order throughout component
- Keyboard shortcuts for power users (j/k/c)
- Focus visible on all focusable elements

**Screen Reader Support**:

- Proper ARIA labels on all components
- `role="navigation"` on sidebar
- `role="article"` on clause content
- `aria-current="location"` for active clause
- `aria-labelledby` for heading associations
- Status indicators with `role="status"`

**Color Contrast**:

- All text meets 4.5:1 minimum contrast ratio
- Interactive elements meet 3:1 contrast
- Status badges use both color and text

**Focus Management**:

- Dialog traps focus when open
- Focus returns to trigger on close
- Visible focus indicators (2px ring)

**Semantic HTML**:

- Proper heading hierarchy (h2, h3)
- `<article>` for clause content
- `<nav>` for navigation elements
- `<button>` for interactive elements

---

## Mobile Responsiveness

### Breakpoints

**Desktop (lg: 1024px+)**:

- Sidebar visible on left
- Three-column layout (sidebar | content | optional metadata)
- Full keyboard shortcuts enabled

**Tablet (md: 768px - 1023px)**:

- Sidebar hidden
- Dropdown navigation at top
- Two-column layout where needed

**Mobile (< 768px)**:

- Single column layout
- Dropdown navigation
- Touch-optimized buttons (min 44px touch targets)
- Simplified UI with essential actions

### Touch Optimization

- Minimum 44px touch targets
- Swipe gestures for clause navigation (future)
- Pull-to-refresh for updating data (future)

---

## Performance Optimization

### Lazy Loading

- Clauses loaded via Inertia deferred props
- Component code-split via dynamic imports
- Images lazy loaded with `loading="lazy"`

### Intersection Observer

- Efficient scroll tracking
- Only observes visible clauses
- Automatic cleanup on unmount

### Debouncing

- Form auto-save debounced at 3 seconds
- Search input debounced at 300ms

### Bundle Size

- Individual component imports
- Tree-shaking friendly exports
- Minimal external dependencies

---

## Testing

### Unit Tests

**ClauseReader.test.ts**:

```typescript
import { mount } from '@vue/test-utils';
import ClauseReader from './ClauseReader.vue';

describe('ClauseReader', () => {
    it('renders clauses correctly', () => {
        const wrapper = mount(ClauseReader, {
            props: {
                bill: mockBill,
                clauses: mockClauses,
            },
        });

        expect(wrapper.findAll('article').length).toBe(mockClauses.length);
    });

    it('navigates to next clause on j key', async () => {
        const wrapper = mount(ClauseReader, {
            props: { bill: mockBill, clauses: mockClauses },
        });

        await wrapper.trigger('keydown', { key: 'j' });
        // Assert selected clause changed
    });
});
```

### E2E Tests

**clause-reader.spec.ts** (Playwright):

```typescript
import { test, expect } from '@playwright/test';

test('citizen can comment on clause', async ({ page }) => {
    await page.goto('/bills/1');

    // Click on first clause comment button
    await page.click('button:has-text("Comment on this clause")');

    // Fill in comment
    await page.fill('#comment-content', 'This is my comment on the clause');

    // Submit
    await page.click('button:has-text("Submit Comment")');

    // Verify success
    await expect(page.locator('.toast')).toHaveText(/submitted successfully/i);
});

test('keyboard navigation works', async ({ page }) => {
    await page.goto('/bills/1');

    // Press j to go to next clause
    await page.keyboard.press('j');

    // Verify scroll happened
    // ...
});
```

---

## Future Enhancements

### Phase 2 Features

1. **Text Highlighting**:
    - Multi-color highlights
    - Persistent storage
    - Comment attachment to highlights

2. **Real-time Updates**:
    - WebSocket integration for live comment counts
    - Real-time notification when new comments added

3. **Advanced Navigation**:
    - Clause search/filter
    - Jump to clauses with most comments
    - "Hot clauses" indicator

4. **Collaboration**:
    - See which clauses others are viewing
    - Comment threads with replies
    - @mentions for legislators

5. **Analytics**:
    - Reading time tracking
    - Engagement heatmap
    - User journey visualization

---

## Browser Support

- Chrome/Edge: Latest 2 versions
- Firefox: Latest 2 versions
- Safari: Latest 2 versions
- Mobile Safari: iOS 14+
- Chrome Mobile: Latest version

---

## Dependencies

**Core**:

- Vue 3.5+
- Inertia.js 2.0+
- TypeScript 5.0+

**UI Components**:

- Reka UI (Radix Vue primitives)
- Tailwind CSS 4.0+
- Lucide Vue Next (icons)

**Utilities**:

- @vueuse/core (composables)
- vue3-toastify (notifications)
- clsx & tailwind-merge (class management)

---

## Troubleshooting

### Common Issues

**1. Clauses not loading**:

- Check backend returns clauses in deferred prop
- Verify Suspense wrapper is present
- Check browser console for errors

**2. Keyboard shortcuts not working**:

- Ensure no input is focused
- Check @vueuse/core is installed
- Verify useMagicKeys import

**3. Comment submission failing**:

- Check authentication status
- Verify CSRF token present
- Check network tab for API errors

**4. Intersection observer not tracking**:

- Verify clause refs are set correctly
- Check scroll container has proper height
- Ensure clauses have unique IDs

---

## Contributing

When contributing to the ClauseReader system:

1. Follow existing TypeScript patterns
2. Add proper ARIA labels for accessibility
3. Test on mobile devices
4. Update this documentation
5. Write unit tests for new features
6. Follow the project's code style (run `npm run lint`)

---

## License

Part of the Public Participation Platform.
Licensed under MIT.

---

## Support

For issues or questions:

- Check this documentation first
- Review component source code
- Consult Laravel Boost documentation
- Open an issue on the project repository
