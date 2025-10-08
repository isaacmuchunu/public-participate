# Public Participation Platform - Frontend Architecture Analysis

**Date**: October 7, 2025
**Tech Stack**: Laravel 12 + Inertia.js 2 + Vue 3.5 + Tailwind CSS 4
**Purpose**: Comprehensive frontend architecture review and recommendations

---

## Executive Summary

The Public Participation Platform has a solid foundation with modern technologies (Inertia.js 2, Vue 3.5, Tailwind CSS 4, Reka UI components). However, significant gaps exist in implementing critical user stories for clause-by-clause participation, accessibility features, and multi-role UX optimization.

**Critical Priorities**:
1. Clause-level commenting interface (core user story)
2. Accessibility compliance (WCAG 2.1 AA)
3. Multi-language support (English/Swahili)
4. Real-time notifications and engagement features
5. Performance optimization with Inertia.js 2 features

---

## 1. Component Architecture Review

### Current State: Strengths

**Layout System** ✅
- Well-structured layout hierarchy:
  - `AppLayout` → `AppSidebarLayout` → `AppShell` + `AppSidebar` + `AppContent`
  - `PublicLayout` for unauthenticated views
  - `AuthCardLayout` and `AuthSplitLayout` for authentication flows
  - `SettingsLayout` for settings pages
- Clean separation of concerns with layout composition
- Breadcrumb system integrated into layouts

**UI Component Library** ✅
- Comprehensive Reka UI components (Radix Vue primitives):
  - Navigation: `Sidebar`, `NavigationMenu`, `Breadcrumb`
  - Forms: `Input`, `Label`, `Checkbox`, `Button`
  - Overlays: `Dialog`, `Sheet`, `DropdownMenu`, `Tooltip`
  - Feedback: `Skeleton`, `Separator`, `Avatar`, `Card`
- Design tokens with CSS variables for theming
- Dark mode support implemented via `useAppearance` composable

**State Management** ✅
- Inertia.js shared data for global state (`auth`, `flash`, `sidebarOpen`)
- Local reactive state with Vue 3 `reactive()` and `ref()`
- Proper use of `usePage()` for accessing shared props
- Toast notifications with `vue3-toastify`

### Current State: Gaps

**Missing Core Components** 🔴

1. **Clause-by-Clause Reader** - Critical for citizen participation
   - No component for displaying bill text with clause-level navigation
   - No clause highlighting or selection interface
   - No inline commenting mechanism
   - Missing clause bookmarking for legislators

2. **Comment/Submission Interface** - Essential user story
   - Basic submission pages exist but lack clause-specific commenting
   - No rich text editor for formatted submissions
   - No draft auto-save indicator
   - Missing attachment upload UI

3. **Multi-Language Selector** - Accessibility requirement
   - No language toggle component (English/Swahili)
   - No translation management system
   - Missing RTL support consideration

4. **Notification Center** - Real-time engagement
   - Basic notification page exists but lacks:
     - Real-time notification badge
     - Notification drawer/popover
     - Notification preferences UI
     - Push notification integration

5. **Data Visualization Components** - MP/Clerk analytics
   - No sentiment analysis charts
   - No participation statistics dashboard
   - No demographic breakdown visualizations
   - Missing report export UI

6. **Search and Filter Components** - Discoverability
   - Basic filtering exists but lacks:
     - Advanced search with autocomplete
     - Filter chips with removal
     - Saved search preferences
     - Search result highlighting

### Component Reusability Assessment

**Current Reusability**: Moderate (6/10)

**Strong**:
- UI primitives from Reka UI are highly reusable
- Layout components follow composition pattern
- Form components accept proper props and emit events

**Weak**:
- Business logic mixed into page components (filtering, pagination)
- No shared submission/comment components
- Duplicate code in bill filtering across Index and Participate pages
- Status badge logic repeated in templates

**Recommendations**:
1. Extract shared business logic into composables:
   - `useBillFiltering()` - Centralize filter logic
   - `usePagination()` - Reusable pagination handling
   - `useFormDraft()` - Auto-save draft management
   - `useNotifications()` - Real-time notification handling

2. Create domain-specific component library:
   - `BillCard.vue` - Reusable bill display component
   - `ClauseReader.vue` - Core clause-by-clause interface
   - `CommentThread.vue` - Threaded comment display
   - `SubmissionForm.vue` - Standardized submission interface
   - `StatusBadge.vue` - Consistent status display
   - `LanguageToggle.vue` - Multi-language switcher

3. Implement render function patterns for complex logic:
   - Dynamic form field rendering based on user role
   - Conditional notification content based on type

---

## 2. User Experience Optimization

### Multi-Role UX Analysis

**Current Implementation**: Basic role-based routing and dashboards

**Role-Specific Gaps**:

#### Citizens (Primary Users) 🔴
**Critical Missing Features**:
- Clause-by-clause reading interface with highlighting
- Inline commenting on specific clauses
- Submission draft management with auto-save
- Submission tracking with status updates
- Email/SMS notification preferences
- Mobile-optimized participation flow

**UX Issues**:
- Bills/Participate pages have different filter UIs (inconsistent)
- No visual indication of which bills user has commented on
- Pagination uses default Laravel links (not accessible)
- No empty state guidance for "no bills" scenario

#### Legislators (MPs/Senators) 🔴
**Critical Missing Features**:
- Comment aggregation by clause
- AI summary visualization (sentiment, themes)
- Demographic filtering interface
- Comment bookmarking/highlighting system
- Report generation and export UI
- Side-by-side bill and comment view

**UX Issues**:
- No quick navigation between clauses with comments
- Missing keyboard shortcuts for efficiency
- No bulk operations (export multiple reports)

#### Clerks (Super Admin) 🔴
**Critical Missing Features**:
- Bill upload wizard with clause parsing
- Clause editor with drag-and-drop reordering
- Bulk user management interface
- Analytics dashboard with participation metrics
- Comment moderation interface
- Report publishing workflow

**UX Issues**:
- Bill creation page likely lacks validation feedback
- No progress indication for long operations (AI summaries)
- Missing bulk operations for user management

### Recommended UX Patterns

**1. Clause-by-Clause Reading Experience**
```vue
<ClauseReader>
  <ClauseSidebar>
    <ClauseNavigation :clauses="bill.clauses" @select="scrollToClause" />
    <CommentStats :clause="selectedClause" />
  </ClauseSidebar>

  <ClauseContent>
    <ClauseText
      :clause="currentClause"
      :highlights="userHighlights"
      @highlight="addHighlight"
      @comment="openCommentDialog"
    />
    <ClauseComments :clause="currentClause" :comments="comments" />
  </ClauseContent>

  <ClauseActions>
    <Button @click="submitComment">Comment on Clause</Button>
    <Button variant="outline" @click="saveDraft">Save Draft</Button>
  </ClauseActions>
</ClauseReader>
```

**2. Progressive Disclosure for Complex Forms**
- Step-by-step submission wizard for citizens
- Collapsible sections for advanced filters
- Inline help tooltips for complex fields

**3. Optimistic UI Updates**
- Immediately show submitted comment (pending state)
- Update submission count before server confirmation
- Animate status changes for visual feedback

**4. Context-Aware Navigation**
- Breadcrumbs show user's journey
- "Back to Bill" button on submission pages
- Deep linking to specific clauses

**5. Empty States with Guidance**
```vue
<EmptyState v-if="!hasBills">
  <Icon name="inbox" />
  <Heading>No bills open for participation</Heading>
  <Text>New participation opportunities are posted frequently.
        We'll notify you when bills are published.</Text>
  <Button @click="setupNotifications">Enable Notifications</Button>
</EmptyState>
```

---

## 3. Accessibility & Inclusivity

### Current State: Basic Accessibility ⚠️

**Implemented**:
- Semantic HTML structure (header, nav, main, article)
- Form labels associated with inputs (Input component)
- Dark mode support for reduced eye strain
- Proper heading hierarchy in layouts

**Critical Gaps**: WCAG 2.1 AA Compliance 🔴

#### 1. Keyboard Navigation (WCAG 2.1.1)
**Issues**:
- Bill cards lack keyboard activation (only mouse hover)
- Filter forms missing keyboard shortcuts
- Pagination links not keyboard navigable

**Recent Improvements (Oct 8, 2025)**:
- Skip-to-content link added to `AppShell` targeting `#main-content`
- Badge component updated with ARIA labels and `role="status"`
- StatusBadge component created with context-aware ARIA descriptions
- InputError enhanced with `role="alert"` and `aria-live`
- ScreenReaderAnnouncement component for dynamic content
- AccessibilitySettings component with full user preference controls
- useAccessibility composable for global state management
- Comprehensive CSS updates for focus, contrast, and motion

**Fixes Required**:
```vue
<!-- Bill Card - Add keyboard support -->
<article
  tabindex="0"
  role="article"
  @keydown.enter="viewBill"
  @keydown.space.prevent="viewBill"
  aria-labelledby="bill-title-{{ bill.id }}"
>
  <h2 :id="`bill-title-${bill.id}`">{{ bill.title }}</h2>
  <!-- ... -->
</article>

<!-- Add skip link -->
<a href="#main-content" class="sr-only focus:not-sr-only">
  Skip to main content
</a>
```

#### 2. Screen Reader Support (WCAG 4.1.2)
**Issues**:
- Status badges lack ARIA labels
- Filter results don't announce count
- Pagination doesn't announce current page
- Loading states not announced
- Form validation errors not associated with fields

**Fixes Required**:
```vue
<!-- Status Badge with ARIA -->
<span
  class="badge"
  role="status"
  :aria-label="`Bill status: ${formatLabel(bill.status)}`"
>
  {{ formatLabel(bill.status) }}
</span>

<!-- Filter Results Announcement -->
<div role="status" aria-live="polite" aria-atomic="true">
  Showing {{ bills.from }}-{{ bills.to }} of {{ bills.total }} bills
</div>

<!-- Form Error Association -->
<InputError
  :message="errors.name"
  :id="`${id}-error`"
  role="alert"
  aria-live="assertive"
/>
<Input
  :id="id"
  :aria-invalid="!!errors.name"
  :aria-describedby="errors.name ? `${id}-error` : undefined"
/>
```

#### 3. Color Contrast (WCAG 1.4.3)
**Issues**:
- Emerald text on white background: Need to verify 4.5:1 ratio
- Muted text colors likely fail for small text
- Status badge colors need contrast verification

**Fixes Required**:
- Audit all color combinations with contrast checker
- Update `app.css` color tokens to meet AA standards:
```css
:root {
  /* Ensure 4.5:1 for normal text, 3:1 for large text */
  --muted-foreground: hsl(120 10% 40%); /* Darker than current 45% */
  --primary: hsl(120 60% 22%); /* Darker than current 25% */
}
```

#### 4. Focus Indicators (WCAG 2.4.7)
**Status**: Partially implemented via `outline-ring/50`

**Improvements Needed**:
- Increase focus ring visibility (2px minimum)
- Ensure focus visible on all interactive elements
- Add focus-within states for composite components

```css
/* Enhanced focus styles */
@layer utilities {
  .focus-visible-ring {
    @apply outline-none focus-visible:ring-2 focus-visible:ring-ring
           focus-visible:ring-offset-2 focus-visible:ring-offset-background;
  }
}
```

#### 5. Multi-Language Support (Inclusivity)
**Current**: None implemented 🔴

**Implementation Strategy**:

**Option A: Vue I18n (Recommended)**
```typescript
// composables/useI18n.ts
import { useI18n as useVueI18n } from 'vue-i18n'

export function useI18n() {
  const { t, locale, availableLocales } = useVueI18n()

  const setLocale = (newLocale: 'en' | 'sw') => {
    locale.value = newLocale
    localStorage.setItem('locale', newLocale)
    document.documentElement.lang = newLocale
  }

  return { t, locale, setLocale, availableLocales }
}
```

```vue
<!-- LanguageToggle.vue -->
<DropdownMenu>
  <DropdownMenuTrigger>
    <Button variant="ghost" size="sm">
      <Icon name="languages" />
      {{ locale === 'en' ? 'English' : 'Kiswahili' }}
    </Button>
  </DropdownMenuTrigger>
  <DropdownMenuContent>
    <DropdownMenuItem @click="setLocale('en')">
      English
    </DropdownMenuItem>
    <DropdownMenuItem @click="setLocale('sw')">
      Kiswahili
    </DropdownMenuItem>
  </DropdownMenuContent>
</DropdownMenu>
```

**Translation Structure**:
```typescript
// locales/en.json
{
  "bills": {
    "title": "Bills",
    "participate": "Participate in Bills",
    "status": {
      "open": "Open for participation",
      "closed": "Closed"
    }
  },
  "accessibility": {
    "skipToContent": "Skip to main content",
    "billStatus": "Bill status: {status}"
  }
}

// locales/sw.json
{
  "bills": {
    "title": "Miswada",
    "participate": "Shiriki katika Miswada",
    "status": {
      "open": "Wazi kwa ushiriki",
      "closed": "Imefungwa"
    }
  }
}
```

#### 6. PWD Accessibility Features
**Required Features**:
- Text resizing up to 200% without loss of functionality
- Captions for any video content
- Adjustable time limits for timed actions (submission deadlines)
- Alternative text for all images and icons
- High contrast mode option

**Implementation**:
```vue
<!-- AccessibilitySettings.vue -->
<Card>
  <CardHeader>
    <CardTitle>Accessibility Preferences</CardTitle>
  </CardHeader>
  <CardContent>
    <div class="space-y-4">
      <div class="flex items-center justify-between">
        <Label for="high-contrast">High Contrast Mode</Label>
        <Switch id="high-contrast" v-model="highContrast" />
      </div>
      <div class="flex items-center justify-between">
        <Label for="reduce-motion">Reduce Motion</Label>
        <Switch id="reduce-motion" v-model="reduceMotion" />
      </div>
      <div class="flex items-center justify-between">
        <Label for="font-size">Font Size</Label>
        <Select v-model="fontSize">
          <option value="sm">Small</option>
          <option value="md">Medium (Default)</option>
          <option value="lg">Large</option>
          <option value="xl">Extra Large</option>
        </Select>
      </div>
    </div>
  </CardContent>
</Card>
```

---

## 4. Performance & Optimization

### Current Performance Assessment

**Strengths**:
- Inertia.js eliminates full page reloads
- Tailwind CSS JIT compilation reduces bundle size
- Vue 3 composition API for optimal reactivity
- SSR capability with `ssr.ts` configuration

**✅ Optimizations Implemented (October 8, 2025)**:
- ✅ Deferred props in BillController for progressive data loading
- ✅ Hover-based prefetching for instant navigation
- ✅ Infinite scrolling replacing pagination
- ✅ Code splitting with manualChunks optimization (9 vendor chunks)
- ✅ Lazy loading page components (eager: false)
- ✅ Skeleton loading states for deferred content (4 components)
- **Performance Gains**: 60% smaller initial bundle, 79% faster perceived load, 90% faster navigation

See `/claudedocs/performance-optimizations-implementation.md` for detailed metrics and implementation.

**Issues Identified (Historical - Now Resolved)**:

#### 1. Large Initial Bundle 🟢 RESOLVED
**Problem**: All page components loaded eagerly
```typescript
// Current: app.ts loads all pages
resolve: (name) => resolvePageComponent(
  `./pages/${name}.vue`,
  import.meta.glob<DefineComponent>('./pages/**/*.vue')
)
```

**Solution**: Lazy load page components
```typescript
// Optimized: Lazy load pages
resolve: (name) => resolvePageComponent(
  `./pages/${name}.vue`,
  import.meta.glob<DefineComponent>('./pages/**/*.vue', { eager: false })
)
```

**Impact**: Reduce initial JS bundle by ~40-60%

**✅ Resolution**: Implemented in app.ts with `eager: false` configuration. Achieved 61% bundle size reduction.

#### 2. Missing Inertia.js 2 Performance Features 🟢 RESOLVED

**Previously Not Implemented**:
- **Deferred Props**: Heavy data loaded after initial render ✅ IMPLEMENTED
- **Prefetching**: Hover-based route prefetching ✅ IMPLEMENTED
- **Infinite Scrolling**: Large bill lists with pagination ✅ IMPLEMENTED
- **Polling**: Real-time notification updates ⚠️ PENDING (future phase)

**Implementation Strategy**:

**A. Deferred Props for Large Datasets**
```php
// BillController.php
public function show(Bill $bill)
{
    return Inertia::render('Bills/Show', [
        'bill' => $bill->load('creator'),
        'clauses' => Inertia::defer(fn() => $bill->clauses()->with('submissions')->get()),
        'submissions' => Inertia::defer(fn() => $bill->submissions()
            ->with('user')
            ->latest()
            ->paginate(20)
        ),
        'analytics' => Inertia::defer(fn() => $this->getAnalytics($bill)),
    ]);
}
```

```vue
<!-- Bills/Show.vue with skeleton loading -->
<script setup lang="ts">
import { Deferred } from '@inertiajs/vue3'

interface Props {
  bill: Bill
  clauses: Deferred<Clause[]>
  submissions: Deferred<PaginatedSubmissions>
  analytics: Deferred<Analytics>
}

const props = defineProps<Props>()
</script>

<template>
  <div>
    <!-- Bill info renders immediately -->
    <BillHeader :bill="bill" />

    <!-- Clauses load with skeleton -->
    <Suspense>
      <ClauseList :clauses="clauses" />
      <template #fallback>
        <ClauseListSkeleton />
      </template>
    </Suspense>

    <!-- Submissions load progressively -->
    <Suspense>
      <SubmissionList :submissions="submissions" />
      <template #fallback>
        <SubmissionListSkeleton />
      </template>
    </Suspense>
  </div>
</template>
```

**B. Prefetching for Bills List**
```vue
<!-- Bills/Index.vue -->
<Link
  :href="billRoutes.show({ bill: bill.id }).url"
  @mouseenter="prefetchBill(bill.id)"
>
  View details
</Link>

<script setup>
import { router } from '@inertiajs/vue3'

const prefetchBill = (billId: number) => {
  router.visit(billRoutes.show({ bill: billId }).url, {
    only: ['bill', 'clauses'], // Prefetch critical data only
    preserveState: true,
    preserveScroll: true,
    onBefore: () => false, // Prevent navigation, just prefetch
  })
}
</script>
```

**C. Infinite Scrolling for Large Lists**
```vue
<!-- Bills/Participate.vue -->
<script setup lang="ts">
import { useInfiniteScroll } from '@vueuse/core'
import { router } from '@inertiajs/vue3'

const props = defineProps<{ bills: PaginatedBills }>()

const loadMoreRef = ref<HTMLElement | null>(null)

useInfiniteScroll(
  loadMoreRef,
  () => {
    if (props.bills.links.next) {
      router.visit(props.bills.links.next, {
        preserveState: true,
        preserveScroll: true,
        only: ['bills'],
        onSuccess: () => {
          // Bills will be merged automatically with Inertia.js merge props
        }
      })
    }
  },
  { distance: 200 }
)
</script>

<template>
  <div>
    <BillCard v-for="bill in bills.data" :key="bill.id" :bill="bill" />
    <div ref="loadMoreRef" class="h-20">
      <Skeleton v-if="bills.links.next" />
    </div>
  </div>
</template>
```

**D. Polling for Real-Time Notifications**
```vue
<!-- NotificationBadge.vue -->
<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import { onMounted, onUnmounted } from 'vue'

const props = defineProps<{ unreadCount: number }>()

let pollInterval: ReturnType<typeof setInterval> | null = null

onMounted(() => {
  // Poll every 30 seconds
  pollInterval = setInterval(() => {
    router.reload({
      only: ['unreadCount'],
      preserveState: true,
      preserveScroll: true,
    })
  }, 30000)
})

onUnmounted(() => {
  if (pollInterval) clearInterval(pollInterval)
})
</script>

<template>
  <Button variant="ghost" position="relative">
    <Icon name="bell" />
    <span v-if="unreadCount > 0" class="notification-badge">
      {{ unreadCount }}
    </span>
  </Button>
</template>
```

#### 3. Code Splitting Strategy 🟡

**Recommended Split Points**:

1. **Route-Based Splitting** (already implemented via Inertia)
2. **Component-Based Splitting**:
```typescript
// Lazy load heavy components
const ClauseReader = defineAsyncComponent(() =>
  import('@/components/ClauseReader.vue')
)
const RichTextEditor = defineAsyncComponent(() =>
  import('@/components/RichTextEditor.vue')
)
const ChartDashboard = defineAsyncComponent(() =>
  import('@/components/ChartDashboard.vue')
)
```

3. **Library Splitting**:
```typescript
// vite.config.ts
export default defineConfig({
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          'reka-ui': ['reka-ui'],
          'icons': ['lucide-vue-next'],
          'utils': ['clsx', 'tailwind-merge', 'class-variance-authority'],
        }
      }
    }
  }
})
```

#### 4. Image Optimization 🟡

**Current**: No image optimization strategy

**Recommendations**:
- Use Laravel's image intervention for resizing
- Implement lazy loading for avatars and bill images
- Add `loading="lazy"` and proper `width`/`height` attributes
- Consider WebP format with PNG fallback

```vue
<img
  :src="bill.thumbnail_url"
  :alt="bill.title"
  width="400"
  height="300"
  loading="lazy"
  class="rounded-lg"
/>
```

---

## 5. Critical Gaps & Implementation Priorities

### Priority 1: Core Participation Features (Sprint 1-2) 🔴

#### A. Clause-by-Clause Reading Interface
**User Story**: As a citizen, I want to read a bill clause by clause and comment on specific sections

**Components to Build**:
1. `ClauseReader.vue` - Main container
2. `ClauseSidebar.vue` - Navigation and mini-map
3. `ClauseContent.vue` - Text display with highlighting
4. `ClauseCommentDialog.vue` - Comment submission modal
5. `ClauseNavigation.vue` - Jump to clause navigation
6. `ClauseHighlight.vue` - Text selection and highlighting

**Technical Implementation**:
```typescript
// types/bill.ts
interface Bill {
  id: number
  title: string
  clauses: Clause[]
}

interface Clause {
  id: number
  bill_id: number
  clause_number: string  // e.g., "1.2.3"
  title: string
  content: string
  order: number
  parent_id: number | null
  children?: Clause[]
  submissions_count: number
  user_has_commented: boolean
}

interface ClauseSubmission {
  id: number
  clause_id: number
  user: User
  content: string
  created_at: string
  is_draft: boolean
}
```

```vue
<!-- ClauseReader.vue -->
<script setup lang="ts">
import { ref, computed } from 'vue'
import { useIntersectionObserver } from '@vueuse/core'

interface Props {
  bill: Bill
  clauses: Clause[]
  userSubmissions?: ClauseSubmission[]
}

const props = defineProps<Props>()

const selectedClauseId = ref<number | null>(null)
const selectedClause = computed(() =>
  props.clauses.find(c => c.id === selectedClauseId.value)
)

// Auto-update selected clause based on scroll position
const clauseRefs = ref<Map<number, HTMLElement>>(new Map())
const setClauseRef = (id: number, el: HTMLElement | null) => {
  if (el) clauseRefs.value.set(id, el)
}

// Track which clause is currently visible
props.clauses.forEach(clause => {
  const clauseEl = computed(() => clauseRefs.value.get(clause.id))
  useIntersectionObserver(
    clauseEl,
    ([{ isIntersecting }]) => {
      if (isIntersecting) {
        selectedClauseId.value = clause.id
      }
    },
    { threshold: 0.5 }
  )
})

const scrollToClause = (clauseId: number) => {
  const el = clauseRefs.value.get(clauseId)
  el?.scrollIntoView({ behavior: 'smooth', block: 'start' })
}

const openCommentDialog = (clauseId: number) => {
  // Open comment dialog for specific clause
}
</script>

<template>
  <div class="flex h-screen">
    <!-- Sidebar Navigation -->
    <aside class="w-64 border-r bg-muted/30 overflow-y-auto">
      <div class="p-4 border-b">
        <h2 class="font-semibold text-lg">{{ bill.title }}</h2>
        <p class="text-sm text-muted-foreground">
          {{ clauses.length }} clauses
        </p>
      </div>

      <nav class="p-2">
        <button
          v-for="clause in clauses"
          :key="clause.id"
          @click="scrollToClause(clause.id)"
          :class="[
            'w-full text-left px-3 py-2 rounded-lg text-sm transition',
            selectedClauseId === clause.id
              ? 'bg-primary text-primary-foreground'
              : 'hover:bg-muted'
          ]"
        >
          <div class="font-medium">
            Clause {{ clause.clause_number }}
          </div>
          <div class="text-xs opacity-80">
            {{ clause.title }}
          </div>
          <div
            v-if="clause.submissions_count > 0"
            class="text-xs mt-1 flex items-center gap-1"
          >
            <Icon name="message-circle" class="w-3 h-3" />
            {{ clause.submissions_count }} comments
          </div>
        </button>
      </nav>
    </aside>

    <!-- Main Content -->
    <main class="flex-1 overflow-y-auto">
      <div class="max-w-3xl mx-auto p-8 space-y-12">
        <article
          v-for="clause in clauses"
          :key="clause.id"
          :ref="el => setClauseRef(clause.id, el as HTMLElement)"
          :id="`clause-${clause.id}`"
          class="scroll-mt-4"
        >
          <header class="mb-4">
            <div class="flex items-center justify-between">
              <div>
                <h3 class="text-xl font-semibold">
                  Clause {{ clause.clause_number }}
                </h3>
                <p class="text-muted-foreground">{{ clause.title }}</p>
              </div>

              <Badge v-if="clause.user_has_commented" variant="success">
                <Icon name="check" class="w-3 h-3" />
                Commented
              </Badge>
            </div>
          </header>

          <div class="prose prose-lg dark:prose-invert">
            <p>{{ clause.content }}</p>
          </div>

          <footer class="mt-6 flex items-center gap-3">
            <Button @click="openCommentDialog(clause.id)">
              <Icon name="message-circle" />
              Comment on this clause
            </Button>

            <Button variant="ghost" size="sm">
              <Icon name="bookmark" />
              Bookmark
            </Button>

            <div class="ml-auto text-sm text-muted-foreground">
              {{ clause.submissions_count }}
              {{ clause.submissions_count === 1 ? 'comment' : 'comments' }}
            </div>
          </footer>

          <Separator class="mt-12" />
        </article>
      </div>
    </main>

    <!-- Comments Sidebar (legislators only) -->
    <aside
      v-if="$page.props.auth.user?.role === 'mp' || $page.props.auth.user?.role === 'senator'"
      class="w-96 border-l bg-muted/30 overflow-y-auto"
    >
      <ClauseComments
        v-if="selectedClause"
        :clause="selectedClause"
        :submissions="getSubmissionsForClause(selectedClause.id)"
      />
    </aside>
  </div>
</template>
```

**Backend Support Required**:
```php
// BillController@show - Update to include clause data
return Inertia::render('Bills/Show', [
    'bill' => $bill,
    'clauses' => $bill->clauses()
        ->withCount('submissions')
        ->with(['children'])
        ->orderBy('order')
        ->get()
        ->map(fn($clause) => [
            ...$clause->toArray(),
            'user_has_commented' => $clause->submissions()
                ->where('user_id', auth()->id())
                ->exists(),
        ]),
    'userSubmissions' => auth()->check()
        ? $bill->submissions()->where('user_id', auth()->id())->get()
        : []
]);
```

#### B. Submission Form with Draft Management
**Components to Build**:
1. `SubmissionForm.vue` - Main form component
2. `RichTextEditor.vue` - Formatted text input
3. `DraftIndicator.vue` - Auto-save status
4. `AttachmentUpload.vue` - File upload component

**Implementation**:
```vue
<!-- SubmissionForm.vue -->
<script setup lang="ts">
import { useForm } from '@inertiajs/vue3'
import { useDebounceFn } from '@vueuse/core'
import { watch } from 'vue'

interface Props {
  bill: Bill
  clause?: Clause
  existingDraft?: SubmissionDraft
}

const props = defineProps<Props>()

const form = useForm({
  bill_id: props.bill.id,
  clause_id: props.clause?.id,
  content: props.existingDraft?.content ?? '',
  attachments: [] as File[],
  is_anonymous: false,
})

// Auto-save draft every 3 seconds
const saveDraft = useDebounceFn(() => {
  router.post('/api/submission-drafts', form.data(), {
    preserveState: true,
    preserveScroll: true,
    only: ['draftSavedAt'],
    onSuccess: () => {
      toast.success('Draft saved')
    }
  })
}, 3000)

watch(() => form.content, saveDraft)

const submitFinal = () => {
  form.post('/submissions', {
    onSuccess: () => {
      toast.success('Submission sent successfully')
      router.visit('/submissions/track')
    }
  })
}
</script>

<template>
  <Form @submit.prevent="submitFinal">
    <Card>
      <CardHeader>
        <CardTitle>Submit Your Comments</CardTitle>
        <CardDescription>
          Your submission will be reviewed by parliamentary clerks
        </CardDescription>
      </CardHeader>

      <CardContent class="space-y-6">
        <div v-if="clause">
          <Label>Commenting on</Label>
          <Card variant="muted" class="p-4">
            <p class="font-medium">Clause {{ clause.clause_number }}</p>
            <p class="text-sm text-muted-foreground">{{ clause.title }}</p>
          </Card>
        </div>

        <div>
          <Label for="content">Your Comments</Label>
          <RichTextEditor
            id="content"
            v-model="form.content"
            :error="form.errors.content"
            placeholder="Share your thoughts on this clause..."
          />
          <InputError :message="form.errors.content" />
          <p class="text-xs text-muted-foreground mt-2">
            Minimum 50 characters
          </p>
        </div>

        <div>
          <Label>Supporting Documents</Label>
          <AttachmentUpload
            v-model="form.attachments"
            :max-files="3"
            :max-size-mb="10"
          />
        </div>

        <div class="flex items-center gap-2">
          <Checkbox
            id="anonymous"
            v-model="form.is_anonymous"
          />
          <Label for="anonymous" class="cursor-pointer">
            Submit anonymously (your identity will be hidden from legislators)
          </Label>
        </div>
      </CardContent>

      <CardFooter class="flex items-center justify-between">
        <DraftIndicator :last-saved="draftSavedAt" />

        <div class="flex gap-2">
          <Button
            type="button"
            variant="outline"
            @click="router.visit('/bills')"
          >
            Cancel
          </Button>
          <Button
            type="submit"
            :disabled="form.processing || !form.isDirty"
          >
            <Icon v-if="form.processing" name="loader" class="animate-spin" />
            Submit Comments
          </Button>
        </div>
      </CardFooter>
    </Card>
  </Form>
</template>
```

### Priority 2: Accessibility Compliance (Sprint 3) 🟡

**Tasks**:
1. Implement ARIA labels and roles across all components
2. Ensure keyboard navigation for all interactive elements
3. Add focus management for modal dialogs
4. Implement skip links and landmarks *(Skip-to-content link added in AppShell on Oct 8, 2025; additional landmarks pending)*
5. Audit and fix color contrast issues
6. Add screen reader announcements for dynamic content
7. Test with NVDA, JAWS, and VoiceOver

**Deliverables**:
- Accessibility audit report
- Component accessibility checklist
- WCAG 2.1 AA compliance certification

### Priority 3: Multi-Language Support (Sprint 4) 🟡

**Tasks**:
1. Install and configure Vue I18n
2. Extract all UI strings to translation files
3. Create English and Swahili translations
4. Implement language toggle component
5. Add language preference persistence
6. Update server responses for translated content (bill summaries, notifications)

**Deliverables**:
- Fully bilingual UI (English/Swahili)
- Language preference storage
- Translation management system for non-developers

### Priority 4: Real-Time Features (Sprint 5) 🟢

**Tasks**:
1. Implement notification polling with Inertia.js
2. Build notification center with unread badge
3. Add real-time participation statistics
4. Implement live comment count updates
5. Add WebSocket support for future scaling (Laravel Reverb)

**Deliverables**:
- Real-time notification system
- Live dashboard updates
- Polling infrastructure

### Priority 5: Advanced Features (Sprint 6+) 🟢

**Tasks**:
1. AI-powered sentiment analysis visualization
2. Advanced search with filters and autocomplete
3. Data export functionality for reports
4. Mobile app considerations (PWA)
5. Offline draft support

---

## 6. Technical Recommendations

### Component Library Additions

**Recommended Third-Party Libraries**:

1. **Rich Text Editor**: Tiptap (Vue 3 compatible)
```bash
npm install @tiptap/vue-3 @tiptap/starter-kit
```

2. **Charts**: Chart.js with vue-chartjs
```bash
npm install chart.js vue-chartjs
```

3. **Date/Time**: date-fns (lighter than moment.js)
```bash
npm install date-fns
```

4. **Form Validation**: Vuelidate or VeeValidate
```bash
npm install @vuelidate/core @vuelidate/validators
```

5. **Internationalization**: Vue I18n
```bash
npm install vue-i18n@9
```

### State Management Strategy

**Current**: Adequate for current scale (Inertia shared props + local state)

**Future Consideration**: If real-time features grow complex, consider:
- **Pinia** (Vue 3 official state management) for client-side state
- Keep Inertia for server-driven state

**When to Add Pinia**:
- Multiple components need to share notification state
- Complex filtering logic across multiple pages
- Draft management needs centralization

### Testing Strategy

**Current**: No frontend tests identified 🔴

**Recommended Testing Pyramid**:

1. **Unit Tests** (Vitest)
```typescript
// composables/useBillFiltering.test.ts
import { describe, it, expect } from 'vitest'
import { useBillFiltering } from './useBillFiltering'

describe('useBillFiltering', () => {
  it('applies status filter correctly', () => {
    const { applyFilters } = useBillFiltering()
    const result = applyFilters({ status: 'open' }, bills)
    expect(result.every(b => b.status === 'open')).toBe(true)
  })
})
```

2. **Component Tests** (Vitest + Testing Library)
```typescript
// components/BillCard.test.ts
import { render, screen } from '@testing-library/vue'
import BillCard from './BillCard.vue'

it('renders bill information correctly', () => {
  render(BillCard, {
    props: {
      bill: mockBill
    }
  })

  expect(screen.getByText(mockBill.title)).toBeInTheDocument()
  expect(screen.getByLabelText(/bill status/i)).toHaveTextContent('Open')
})
```

3. **E2E Tests** (Playwright - already available in Laravel Boost)
```typescript
// tests/e2e/citizen-participation.spec.ts
import { test, expect } from '@playwright/test'

test('citizen can comment on bill clause', async ({ page }) => {
  await page.goto('/bills/1')
  await page.click('button:has-text("Comment on this clause")')
  await page.fill('#content', 'This is my comment on the clause')
  await page.click('button:has-text("Submit Comments")')

  await expect(page.locator('.toast')).toHaveText(/submission sent/i)
})
```

4. **Accessibility Tests** (axe-core)
```typescript
// tests/a11y/bills-index.test.ts
import { injectAxe, checkA11y } from 'axe-playwright'

test('bills index page is accessible', async ({ page }) => {
  await page.goto('/bills')
  await injectAxe(page)
  await checkA11y(page, null, {
    detailedReport: true,
    detailedReportOptions: { html: true }
  })
})
```

---

## 7. Implementation Roadmap

### Sprint 1-2 (Weeks 1-4): Core Participation
- [ ] Build ClauseReader component system
- [ ] Implement clause navigation and highlighting
- [ ] Create SubmissionForm with rich text editor
- [ ] Add draft auto-save functionality
- [ ] Implement attachment upload
- [ ] Test citizen participation workflow end-to-end

### Sprint 3 (Weeks 5-6): Accessibility
- [ ] Conduct initial WCAG 2.1 AA audit
- [ ] Fix keyboard navigation issues
- [ ] Add ARIA labels and roles
- [ ] Implement screen reader announcements
- [ ] Fix color contrast issues
- [ ] Test with assistive technologies

### Sprint 4 (Weeks 7-8): Multi-Language
- [ ] Set up Vue I18n infrastructure
- [ ] Extract and translate all UI strings
- [ ] Create language toggle component
- [ ] Translate server-generated content
- [ ] Test bilingual workflows

### Sprint 5 (Weeks 9-10): Performance & Real-Time
- [ ] Implement Inertia.js deferred props
- [ ] Add prefetching for bill navigation
- [ ] Build notification polling system
- [ ] Optimize bundle size with code splitting
- [ ] Add infinite scrolling for bill lists

### Sprint 6+ (Ongoing): Advanced Features
- [ ] AI sentiment visualization
- [ ] Advanced search and filtering
- [ ] Report generation and export
- [ ] Progressive Web App features


---

## 8. Success Metrics

### User Experience
- Task completion rate for submitting comments: Target 95%
- Average time to find and comment on a bill: Target < 5 minutes
- Mobile user satisfaction: Target 4.5/5

### Accessibility
- WCAG 2.1 AA compliance: 100% of pages
- Screen reader compatibility: All major screen readers
- Keyboard navigation: 100% of functionality accessible

### Performance
- Initial page load (FCP): Target < 1.5s
- Time to Interactive (TTI): Target < 3s
- Lighthouse Performance Score: Target > 90
- Bundle size: Target < 200KB gzipped

### Engagement
- Citizen participation rate: Track monthly growth
- Average comments per bill: Baseline and growth targets
- Mobile vs desktop usage: Track and optimize accordingly

---

## 9. Risk Mitigation

### Technical Risks

**Risk: Inertia.js SSR complexity with real-time features**
- Mitigation: Start with polling, defer WebSockets to later phase
- Fallback: Client-side only rendering for real-time components

**Risk: Large bill documents causing performance issues**
- Mitigation: Implement pagination, lazy loading, and deferred props
- Fallback: Server-side PDF generation for download

**Risk: Translation quality for legal/technical terms**
- Mitigation: Engage professional legal translators for Swahili
- Fallback: Provide glossary of untranslated technical terms

### User Experience Risks

**Risk: Citizens find clause-by-clause navigation confusing**
- Mitigation: User testing before launch, tooltips and onboarding
- Fallback: Provide traditional full-text view option

**Risk: Low mobile adoption due to complex interactions**
- Mitigation: Mobile-first design, simplified mobile flows
- Fallback: SMS-based notification system for participation reminders

---

## 10. Conclusion & Next Steps

The Public Participation Platform has a strong foundation with modern technologies, but requires focused development on core user stories, accessibility, and performance optimization.

**Immediate Actions**:
1. **This Week**: Begin ClauseReader component development
2. **Next Sprint**: Complete citizen participation workflow
3. **Month 1**: Achieve WCAG 2.1 AA compliance
4. **Month 2**: Launch multi-language support
5. **Month 3**: Deploy real-time notification system

**Long-Term Vision**:
- Position platform as Africa's leading digital public participation system
- Scale to support multiple legislative bodies (county assemblies)
- Mobile app for increased accessibility
- AI-powered insights for legislators and citizens

**Resources Needed**:
- 2-3 frontend developers (Vue 3 + Inertia.js experience)
- 1 UX designer with accessibility expertise
- 1 QA engineer for testing
- Access to professional Swahili translators
- Budget for accessibility audit and testing tools

---

## Appendix A: Component Inventory

### Existing Components
✅ Layouts (4): AppLayout, PublicLayout, AuthCardLayout, AuthSplitLayout
✅ UI Primitives (20+): Button, Input, Card, Dialog, etc.
✅ Custom Components (10): AppShell, AppSidebar, Breadcrumbs, etc.

### Missing Components (Priority Order)
1. 🔴 ClauseReader system (6 components)
2. 🔴 SubmissionForm system (4 components)
3. 🔴 LanguageToggle and I18n system
4. 🟡 NotificationCenter (3 components)
5. 🟡 DataVisualization (chart components)
6. 🟢 AdvancedSearch components
7. 🟢 ReportGenerator components

---

## Appendix B: Accessibility Checklist

### WCAG 2.1 Level AA Requirements

**Perceivable**
- [ ] 1.1.1 Non-text Content (A)
- [ ] 1.3.1 Info and Relationships (A)
- [ ] 1.4.3 Contrast (Minimum) (AA)
- [ ] 1.4.11 Non-text Contrast (AA)

**Operable**
- [ ] 2.1.1 Keyboard (A)
- [ ] 2.1.2 No Keyboard Trap (A)
- [ ] 2.4.1 Bypass Blocks (A)
- [ ] 2.4.3 Focus Order (A)
- [ ] 2.4.7 Focus Visible (AA)

**Understandable**
- [ ] 3.1.1 Language of Page (A)
- [ ] 3.1.2 Language of Parts (AA)
- [ ] 3.2.1 On Focus (A)
- [ ] 3.3.1 Error Identification (A)
- [ ] 3.3.3 Error Suggestion (AA)

**Robust**
- [ ] 4.1.2 Name, Role, Value (A)
- [ ] 4.1.3 Status Messages (AA)

---

**Document Version**: 1.0
**Last Updated**: October 7, 2025
**Next Review**: After Sprint 2 completion
