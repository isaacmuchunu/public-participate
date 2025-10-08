# Accessibility Quick Reference Guide

**Date**: October 8, 2025
**Purpose**: Quick reference for developers implementing accessibility features

---

## Component Usage Examples

### StatusBadge Component

**Use instead of inline status badges**:

```vue
<!-- BEFORE (Bills/Index.vue) -->
<span
  class="rounded-full px-3 py-1 text-xs font-medium capitalize"
  :class="statusBadgeClasses(bill.status)"
>
  {{ formatLabel(bill.status) }}
</span>

<!-- AFTER -->
<StatusBadge :status="bill.status" />
```

**Benefits**:
- Automatic ARIA labels with context
- Consistent styling
- Screen reader friendly
- Semantic role="status"

---

### Enhanced InputError

**Associate errors with inputs**:

```vue
<script setup>
const emailInputId = 'email-input';
</script>

<template>
  <div class="space-y-2">
    <Label :for="emailInputId">Email Address</Label>

    <Input
      :id="emailInputId"
      v-model="form.email"
      type="email"
      :aria-invalid="!!errors.email"
      :aria-describedby="errors.email ? `${emailInputId}-error` : undefined"
    />

    <InputError
      :message="errors.email"
      :id="`${emailInputId}-error`"
    />
  </div>
</template>
```

**Benefits**:
- Screen readers announce errors immediately
- Errors associated with specific fields
- `aria-invalid` state for assistive tech

---

### ScreenReaderAnnouncement

**Announce dynamic content changes**:

```vue
<script setup>
import ScreenReaderAnnouncement from '@/components/ScreenReaderAnnouncement.vue';

const filterResultMessage = computed(() => {
  if (!hasResults.value) {
    return 'No bills found matching your filters';
  }
  return `Showing ${bills.from} to ${bills.to} of ${bills.total} bills`;
});
</script>

<template>
  <!-- Your page content -->

  <ScreenReaderAnnouncement
    :message="filterResultMessage"
    priority="polite"
  />
</template>
```

**When to use**:
- Filter results updates
- Loading state changes
- Success/error messages (use priority="assertive")
- Dynamic content updates

---

### AccessibilitySettings

**Add to user settings page**:

```vue
<script setup>
import AccessibilitySettings from '@/components/AccessibilitySettings.vue';
import SettingsLayout from '@/layouts/SettingsLayout.vue';
</script>

<template>
  <SettingsLayout>
    <div class="space-y-8">
      <AccessibilitySettings />
      <!-- Other settings components -->
    </div>
  </SettingsLayout>
</template>
```

**Route suggestion**:
```php
// routes/web.php
Route::get('/settings/accessibility', function () {
    return Inertia::render('settings/Accessibility');
})->middleware('auth')->name('settings.accessibility');
```

---

## useAccessibility Composable

### Basic Usage

```typescript
import { useAccessibility } from '@/composables/useAccessibility';

const { announce, preferences } = useAccessibility();

// Announce to screen readers
announce('Form submitted successfully', 'polite');
announce('Critical error occurred', 'assertive');

// Check preferences
if (preferences.value.keyboardShortcuts) {
  // Enable keyboard shortcuts
}
```

### Focus Management

```vue
<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { useAccessibility } from '@/composables/useAccessibility';

const { trapFocus } = useAccessibility();
const modalRef = ref<HTMLElement | null>(null);

onMounted(() => {
  if (modalRef.value) {
    const cleanup = trapFocus(modalRef.value);
    onUnmounted(cleanup);
  }
});
</script>

<template>
  <Dialog>
    <DialogContent ref="modalRef">
      <!-- Modal content -->
      <!-- Focus will be trapped within this element -->
    </DialogContent>
  </Dialog>
</template>
```

---

## CSS Utility Classes

### Focus Indicators

```vue
<!-- Standard focus ring -->
<button class="focus-visible-ring">
  Click me
</button>

<!-- Keyboard-only focus -->
<a href="/bills" class="focus-keyboard">
  View bills
</a>

<!-- Strong focus for high contrast -->
<input type="text" class="focus-strong" />
```

### Screen Reader Only

```vue
<!-- Visible only to screen readers -->
<span class="sr-only">Required field</span>

<!-- Skip link (visible on focus) -->
<a href="#main-content" class="skip-link">
  Skip to main content
</a>
```

---

## Keyboard Navigation Patterns

### Interactive Cards

```vue
<article
  tabindex="0"
  role="article"
  :aria-labelledby="`card-title-${id}`"
  @keydown.enter="handleActivate"
  @keydown.space.prevent="handleActivate"
  class="focus-visible-ring"
>
  <h2 :id="`card-title-${id}`">{{ title }}</h2>
  <!-- Card content -->
</article>

<script setup>
const handleActivate = () => {
  // Navigate or perform action
  router.visit(`/bills/${id}`);
};
</script>
```

### Custom Dropdowns

```vue
<div
  role="combobox"
  :aria-expanded="isOpen"
  :aria-controls="listboxId"
  :aria-activedescendant="selectedId"
  @keydown.down.prevent="selectNext"
  @keydown.up.prevent="selectPrevious"
  @keydown.enter="confirmSelection"
  @keydown.escape="closeDropdown"
>
  <!-- Dropdown trigger -->
</div>
```

---

## Common Patterns

### Form Field with Error

```vue
<div class="space-y-2">
  <Label :for="fieldId">
    Field Name
    <span class="text-destructive" aria-label="required">*</span>
  </Label>

  <Input
    :id="fieldId"
    v-model="form.field"
    :aria-invalid="!!errors.field"
    :aria-describedby="errors.field ? `${fieldId}-error` : `${fieldId}-help`"
    aria-required="true"
  />

  <p :id="`${fieldId}-help`" class="text-xs text-muted-foreground">
    Help text for this field
  </p>

  <InputError
    :message="errors.field"
    :id="`${fieldId}-error`"
  />
</div>
```

### Loading State

```vue
<Button type="submit" :disabled="form.processing">
  <span v-if="form.processing" class="flex items-center gap-2">
    <Icon name="loader-2" class="animate-spin" aria-hidden="true" />
    <span aria-live="polite">Submitting...</span>
  </span>
  <span v-else>Submit</span>
</Button>
```

### Pagination with ARIA

```vue
<nav aria-label="Pagination navigation">
  <Link
    v-for="link in paginationLinks"
    :key="link.label"
    :href="link.url"
    :aria-current="link.active ? 'page' : undefined"
    :aria-label="getPaginationLabel(link)"
    :class="linkClasses(link)"
  >
    {{ link.label }}
  </Link>
</nav>

<script setup>
const getPaginationLabel = (link) => {
  if (link.label.includes('Previous')) return 'Go to previous page';
  if (link.label.includes('Next')) return 'Go to next page';
  if (link.active) return `Current page, page ${link.label}`;
  return `Go to page ${link.label}`;
};
</script>
```

### Empty State

```vue
<div
  v-if="!hasResults"
  role="status"
  aria-live="polite"
  class="flex min-h-[200px] items-center justify-center rounded-lg border-2 border-dashed p-10 text-center"
>
  <div>
    <Icon name="inbox" class="mx-auto h-12 w-12 text-muted-foreground" aria-hidden="true" />
    <h3 class="mt-4 text-lg font-semibold">No bills found</h3>
    <p class="mt-2 text-sm text-muted-foreground">
      Adjust your filters or check back later for newly published bills.
    </p>
    <Button class="mt-4" @click="resetFilters">
      Reset filters
    </Button>
  </div>
</div>
```

---

## Testing Checklist

### Manual Testing

```bash
# Keyboard Navigation
- [ ] Tab through all interactive elements
- [ ] Use Enter/Space to activate buttons
- [ ] Use Arrow keys in select/radio groups
- [ ] Press Escape to close modals
- [ ] Verify focus indicators visible

# Screen Reader (NVDA/JAWS/VoiceOver)
- [ ] Navigate by headings (H key)
- [ ] Navigate by landmarks (D key)
- [ ] Navigate by forms (F key)
- [ ] Listen to status announcements
- [ ] Verify ARIA labels read correctly

# Visual
- [ ] Test with 200% browser zoom
- [ ] Enable high contrast mode
- [ ] Enable reduced motion
- [ ] Verify color contrast (4.5:1)
- [ ] Check focus indicators (2px minimum)
```

### Automated Testing

```typescript
// Component test with accessibility
import { render } from '@testing-library/vue';
import { axe, toHaveNoViolations } from 'jest-axe';

expect.extend(toHaveNoViolations);

test('StatusBadge is accessible', async () => {
  const { container } = render(StatusBadge, {
    props: { status: 'open_for_participation' }
  });

  const results = await axe(container);
  expect(results).toHaveNoViolations();
});
```

---

## Quick Fixes for Common Issues

### Issue: Missing ARIA label on status badge
```vue
<!-- BAD -->
<span class="badge">{{ status }}</span>

<!-- GOOD -->
<StatusBadge :status="status" />
```

### Issue: Form errors not announced
```vue
<!-- BAD -->
<p v-if="error" class="text-red-600">{{ error }}</p>

<!-- GOOD -->
<InputError :message="error" :id="`${fieldId}-error`" />
```

### Issue: No keyboard navigation on interactive element
```vue
<!-- BAD -->
<div @click="handleClick">Click me</div>

<!-- GOOD -->
<button
  type="button"
  @click="handleClick"
  class="focus-visible-ring"
>
  Click me
</button>
```

### Issue: Icon without text alternative
```vue
<!-- BAD -->
<Icon name="trash" />

<!-- GOOD -->
<button type="button" aria-label="Delete item">
  <Icon name="trash" aria-hidden="true" />
</button>
```

### Issue: Loading state not announced
```vue
<!-- BAD -->
<Spinner v-if="loading" />

<!-- GOOD -->
<div v-if="loading" role="status" aria-live="polite">
  <Spinner aria-hidden="true" />
  <span class="sr-only">Loading...</span>
</div>
```

---

## Resources

### Browser Extensions
- [axe DevTools](https://www.deque.com/axe/devtools/) - Automated testing
- [WAVE](https://wave.webaim.org/extension/) - Visual feedback
- [Accessibility Insights](https://accessibilityinsights.io/) - Microsoft tool

### Screen Readers
- **Windows**: [NVDA](https://www.nvaccess.org/) (Free)
- **macOS**: VoiceOver (Built-in, Cmd+F5)
- **Mobile**: TalkBack (Android), VoiceOver (iOS)

### Contrast Checkers
- [WebAIM Contrast Checker](https://webaim.org/resources/contrastchecker/)
- [Colour Contrast Analyser](https://www.tpgi.com/color-contrast-checker/)

### Documentation
- [WCAG 2.1 Quick Reference](https://www.w3.org/WAI/WCAG21/quickref/)
- [ARIA Authoring Practices](https://www.w3.org/WAI/ARIA/apg/)
- [MDN Accessibility](https://developer.mozilla.org/en-US/docs/Web/Accessibility)

---

## Need Help?

**Questions or issues?**
- Check [accessibility-implementation.md](/claudedocs/accessibility-implementation.md)
- Review component examples in `/resources/js/components/`
- Test with screen readers before committing
- Run `npm run test:a11y` for automated checks

**Remember**: Accessibility is not optional. It's a legal requirement and moral obligation.

---

**Document Version**: 1.0
**Last Updated**: October 8, 2025
