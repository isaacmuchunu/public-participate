# i18n Usage Examples - Public Participation Platform

Quick reference guide for using internationalization features in components.

---

## Basic Usage

### Import and Setup
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t, locale, setLocale } = useI18n();
</script>
```

---

## Common Translation Patterns

### 1. Simple Text Translation
```vue
<template>
  <!-- Bill title -->
  <h1>{{ t('bills.title') }}</h1>
  <!-- Output (en): "Bills" -->
  <!-- Output (sw): "Miswada" -->

  <!-- Navigation items -->
  <nav>
    <a href="/bills">{{ t('navigation.bills') }}</a>
    <a href="/submissions">{{ t('navigation.submissions') }}</a>
    <a href="/notifications">{{ t('navigation.notifications') }}</a>
  </nav>

  <!-- Common buttons -->
  <button>{{ t('common.save') }}</button>
  <button>{{ t('common.cancel') }}</button>
  <button>{{ t('common.delete') }}</button>
</template>
```

### 2. Nested Translations
```vue
<template>
  <!-- Bill status -->
  <span>{{ t('bills.status.open') }}</span>
  <!-- Output (en): "Open for participation" -->
  <!-- Output (sw): "Wazi kwa ushiriki" -->

  <!-- House labels -->
  <span>{{ t('bills.house.national_assembly') }}</span>
  <!-- Output (en): "National Assembly" -->
  <!-- Output (sw): "Bunge la Kitaifa" -->

  <!-- Submission status -->
  <span>{{ t('submissions.draft') }}</span>
  <span>{{ t('submissions.submitted') }}</span>
  <span>{{ t('submissions.approved') }}</span>
</template>
```

### 3. Translations with Variables
```vue
<script setup>
const { t } = useI18n();
const billTitle = 'Finance Bill 2025';
const daysLeft = 5;
</script>

<template>
  <!-- Bill closing notification -->
  <p>{{ t('notifications.messages.bill_closing_soon', { title: billTitle, days: daysLeft }) }}</p>
  <!-- Output (en): "Bill "Finance Bill 2025" closes in 5 days" -->
  <!-- Output (sw): "Mswada "Finance Bill 2025" utafungwa katika siku 5" -->

  <!-- Current language -->
  <p>{{ t('accessibility.currentLanguage', { language: 'English' }) }}</p>
  <!-- Output (en): "Current language: English" -->
</template>
```

### 4. Pluralization
```vue
<script setup>
const { t } = useI18n();
const submissionCount = 5;
</script>

<template>
  <!-- Automatic pluralization based on count -->
  <p>{{ t('bills.days_remaining', { count: 1 }) }}</p>
  <!-- Output (en): "1 day remaining" -->

  <p>{{ t('bills.days_remaining', { count: 5 }) }}</p>
  <!-- Output (en): "5 days remaining" -->
  <!-- Output (sw): "siku 5 zimesalia" -->

  <!-- Relative time -->
  <span>{{ t('dates.hours_ago', { count: 3 }) }}</span>
  <!-- Output (en): "3 hours ago" -->
  <!-- Output (sw): "masaa 3 yaliyopita" -->
</template>
```

---

## Component-Specific Examples

### Bill Cards
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

interface Props {
  bill: {
    title: string;
    status: 'open' | 'closed' | 'draft';
    house: 'national_assembly' | 'senate';
    submissions_count: number;
  };
}

const props = defineProps<Props>();
</script>

<template>
  <div class="bill-card">
    <h3>{{ bill.title }}</h3>

    <!-- Status badge -->
    <span :aria-label="t('accessibility.billStatus', { status: t(`bills.status.${bill.status}`) })">
      {{ t(`bills.status.${bill.status}`) }}
    </span>

    <!-- House label -->
    <span>{{ t(`bills.house.${bill.house}`) }}</span>

    <!-- Submission count -->
    <div>
      {{ t('accessibility.submissionCount', { count: bill.submissions_count }) }}
    </div>

    <!-- Actions -->
    <button>{{ t('bills.view_details') }}</button>
    <button>{{ t('bills.submit_feedback') }}</button>
  </div>
</template>
```

### Submission Form
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();
</script>

<template>
  <form>
    <!-- Form title -->
    <h2>{{ t('submissions.create') }}</h2>

    <!-- Content field -->
    <label>{{ t('submissions.form.content') }}</label>
    <textarea
      :placeholder="t('submissions.form.content_placeholder')"
      :aria-label="t('submissions.form.content')"
    />
    <p class="hint">{{ t('submissions.form.content_hint') }}</p>

    <!-- Attachments -->
    <label>{{ t('submissions.form.attachments') }}</label>
    <input type="file" multiple />
    <p class="hint">{{ t('submissions.form.attachments_hint') }}</p>

    <!-- Anonymous checkbox -->
    <label>
      <input type="checkbox" />
      {{ t('submissions.form.is_anonymous') }}
    </label>
    <p class="hint">{{ t('submissions.form.is_anonymous_hint') }}</p>

    <!-- Actions -->
    <button type="submit">{{ t('submissions.form.submit') }}</button>
    <button type="button">{{ t('submissions.form.save_draft') }}</button>
    <button type="button">{{ t('submissions.form.cancel') }}</button>
  </form>
</template>
```

### Validation Errors
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

const errors = {
  content: 'content_min_length',
  attachment: 'attachment_too_large'
};
</script>

<template>
  <form>
    <div>
      <textarea v-model="content" />
      <span v-if="errors.content" class="error">
        {{ t(`submissions.validation.${errors.content}`) }}
      </span>
      <!-- Output (en): "Comments must be at least 50 characters" -->
      <!-- Output (sw): "Maoni lazima yawe angalau herufi 50" -->
    </div>

    <div>
      <input type="file" />
      <span v-if="errors.attachment" class="error">
        {{ t(`submissions.validation.${errors.attachment}`) }}
      </span>
      <!-- Output (en): "File size must not exceed 10MB" -->
      <!-- Output (sw): "Ukubwa wa faili usizidi MB 10" -->
    </div>
  </form>
</template>
```

### Notifications
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { t } = useI18n();

interface Notification {
  type: 'bill_published' | 'bill_closing_soon' | 'submission_received';
  data: {
    title: string;
    days?: number;
  };
}

const notifications: Notification[] = [
  {
    type: 'bill_published',
    data: { title: 'Finance Bill 2025' }
  },
  {
    type: 'bill_closing_soon',
    data: { title: 'Education Bill 2025', days: 3 }
  }
];
</script>

<template>
  <div v-for="notification in notifications" :key="notification.type">
    <!-- Notification type label -->
    <h4>{{ t(`notifications.types.${notification.type}`) }}</h4>

    <!-- Notification message with dynamic data -->
    <p>{{ t(`notifications.messages.${notification.type}`, notification.data) }}</p>
    <!-- Example output (en): "A new bill "Finance Bill 2025" has been published" -->
    <!-- Example output (sw): "Mswada mpya "Finance Bill 2025" umechapishwa" -->
  </div>
</template>
```

---

## Accessibility Examples

### Screen Reader Announcements
```vue
<template>
  <!-- Skip to content link -->
  <a href="#main-content">
    {{ t('accessibility.skipToContent') }}
  </a>

  <!-- Loading state -->
  <div role="status" aria-live="polite">
    {{ t('accessibility.loading') }}
  </div>

  <!-- Error alert -->
  <div role="alert" aria-live="assertive">
    {{ t('accessibility.error') }}
  </div>

  <!-- Success message -->
  <div role="status" aria-live="polite">
    {{ t('accessibility.success') }}
  </div>

  <!-- Language selector -->
  <button :aria-label="t('accessibility.languageSelector')">
    <Globe />
  </button>
</template>
```

### ARIA Labels
```vue
<script setup>
const { t } = useI18n();
const clauseNumber = '1.2.3';
const currentPage = 2;
const totalPages = 10;
</script>

<template>
  <!-- Clause navigation -->
  <button :aria-label="t('accessibility.clauseNavigation', { number: clauseNumber })">
    {{ clauseNumber }}
  </button>

  <!-- Page navigation -->
  <nav :aria-label="t('accessibility.pageNavigation', { current: currentPage, total: totalPages })">
    <!-- pagination controls -->
  </nav>

  <!-- Toggle sidebar -->
  <button :aria-label="t('accessibility.toggleSidebar')">
    <Menu />
  </button>
</template>
```

---

## Date Formatting Examples

### Relative Time
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';
import { formatDistanceToNow } from 'date-fns';
import { enUS, sw as swLocale } from 'date-fns/locale';

const { t, locale } = useI18n();

const getDateLocale = () => locale.value === 'sw' ? swLocale : enUS;

const submittedAt = new Date('2025-10-05');
</script>

<template>
  <!-- Relative time with i18n -->
  <time>
    {{ formatDistanceToNow(submittedAt, { locale: getDateLocale(), addSuffix: true }) }}
  </time>

  <!-- Using built-in translations -->
  <span>{{ t('dates.today') }}</span>
  <span>{{ t('dates.yesterday') }}</span>
  <span>{{ t('dates.just_now') }}</span>
</template>
```

---

## Programmatic Locale Management

### Change Language Programmatically
```vue
<script setup lang="ts">
import { useI18n } from '@/composables/useI18n';

const { locale, setLocale } = useI18n();

// Check current locale
console.log(locale.value); // 'en' or 'sw'

// Change to Swahili
const switchToSwahili = () => {
  setLocale('sw');
  // Updates:
  // - locale.value
  // - localStorage.locale
  // - document.documentElement.lang
};

// Change to English
const switchToEnglish = () => {
  setLocale('en');
};

// Reactive UI based on locale
const greeting = computed(() => {
  return locale.value === 'en' ? 'Welcome' : 'Karibu';
});
</script>
```

### Conditional Rendering
```vue
<template>
  <!-- Show different content based on language -->
  <div v-if="locale === 'en'">
    English-specific content
  </div>
  <div v-else-if="locale === 'sw'">
    Swahili-specific content
  </div>

  <!-- Or use computed -->
  <p>{{ greeting }}</p>
</template>
```

---

## Testing Examples

### Component Testing
```typescript
import { mount } from '@vue/test-utils';
import { createI18n } from 'vue-i18n';
import BillCard from '@/components/bills/BillCard.vue';

const i18n = createI18n({
  legacy: false,
  locale: 'en',
  messages: {
    en: {
      bills: {
        status: {
          open: 'Open for participation'
        }
      }
    }
  }
});

it('displays translated status', () => {
  const wrapper = mount(BillCard, {
    global: {
      plugins: [i18n]
    },
    props: {
      bill: {
        status: 'open'
      }
    }
  });

  expect(wrapper.text()).toContain('Open for participation');
});
```

---

## Common Patterns

### Loading States
```vue
<template>
  <div v-if="loading">
    {{ t('common.loading') }}
  </div>
  <div v-else-if="error">
    {{ t('common.error') }}
  </div>
  <div v-else>
    <!-- Content -->
  </div>
</template>
```

### Empty States
```vue
<template>
  <div v-if="!bills.length" class="empty-state">
    <h3>{{ t('bills.no_bills') }}</h3>
    <p>{{ t('bills.no_bills_description') }}</p>
  </div>
</template>
```

### Confirmation Dialogs
```vue
<template>
  <dialog>
    <h2>{{ t('common.confirm') }}</h2>
    <p>Are you sure you want to delete this?</p>
    <button>{{ t('common.yes') }}</button>
    <button>{{ t('common.no') }}</button>
  </dialog>
</template>
```

---

## Best Practices

1. **Always use translation keys**: Never hardcode English strings
2. **Provide context**: Use nested keys for better organization
3. **Variable names**: Use descriptive variable names in interpolation
4. **Accessibility**: Always include translated ARIA labels
5. **Consistency**: Use the same translation for the same concept
6. **Testing**: Test components with both languages

---

## Quick Reference

### Most Used Keys
- `common.save`, `common.cancel`, `common.delete`
- `common.loading`, `common.error`, `common.success`
- `navigation.home`, `navigation.bills`, `navigation.submissions`
- `accessibility.skipToContent`, `accessibility.loading`

### Translation File Location
- English: `/resources/js/locales/en.json`
- Swahili: `/resources/js/locales/sw.json`

### Composable
- Location: `/resources/js/composables/useI18n.ts`
- Import: `import { useI18n } from '@/composables/useI18n'`

---

**Last Updated**: October 8, 2025
