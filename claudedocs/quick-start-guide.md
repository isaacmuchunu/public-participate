# Quick Start Guide - Clause System

## 🚀 Testing the Clause-by-Clause System

The clause system is now fully operational! Here's how to test it:

### 1. Create a Test Bill with PDF

```php
// Using Tinker
php artisan tinker

$bill = Bill::factory()->create([
    'title' => 'Public Participation Act 2025',
    'status' => 'open_for_participation',
    'participation_start_date' => now(),
    'participation_end_date' => now()->addDays(30),
]);

// Upload a PDF manually via the UI or set a path
// $bill->update(['pdf_path' => 'bills/test-bill.pdf']);
```

### 2. Parse Clauses from PDF (API)

```bash
# As Clerk/Admin
curl -X POST http://localhost:8000/api/v1/bills/1/clauses/parse \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json"

# Response:
{
  "message": "Bill clauses parsed successfully",
  "count": 8,
  "data": [
    {
      "id": 1,
      "bill_id": 1,
      "clause_number": "1",
      "clause_type": "section",
      "title": "Definitions",
      "content": "In this Act...",
      "display_order": 0
    }
  ]
}
```

### 3. Manually Add a Clause

```bash
curl -X POST http://localhost:8000/api/v1/bills/1/clauses \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{
    "clause_number": "10",
    "clause_type": "section",
    "title": "Penalties",
    "content": "Any person who contravenes the provisions of this Act..."
  }'
```

### 4. Submit Clause-Specific Feedback

```php
// Update SubmissionController or use API directly
Submission::create([
    'bill_id' => 1,
    'clause_id' => 3,  // Specific clause
    'submission_scope' => 'clause',
    'user_id' => auth()->id(),
    'content' => 'I disagree with the implementation timeline in this clause...',
    'submission_type' => 'concern',
    'channel' => 'web',
]);
```

### 5. Get Clause with Submissions

```bash
curl http://localhost:8000/api/v1/bills/1/clauses/3 \
  -H "Authorization: Bearer YOUR_TOKEN"

# Response includes:
{
  "data": {
    "id": 3,
    "clause_number": "3.2",
    "title": "Implementation Timeline",
    "submissions_count": 45,
    "submissions": [...],  // Latest 10 submissions
    "analytics": {
      "support_count": 12,
      "oppose_count": 28,
      "neutral_count": 5
    }
  }
}
```

## 🧪 Testing with Pest

```php
// tests/Feature/Api/ClauseTest.php
use function Pest\Laravel\{actingAs, postJson, getJson};

it('allows clerk to parse bill clauses from PDF', function () {
    $clerk = User::factory()->clerk()->create();
    $bill = Bill::factory()->create(['pdf_path' => 'bills/sample.pdf']);

    actingAs($clerk)
        ->postJson("/api/v1/bills/{$bill->id}/clauses/parse")
        ->assertSuccessful()
        ->assertJsonStructure(['message', 'count', 'data']);

    expect($bill->clauses()->count())->toBeGreaterThan(0);
});

it('allows citizen to submit clause-specific feedback', function () {
    $citizen = User::factory()->citizen()->create();
    $bill = Bill::factory()->create(['status' => 'open_for_participation']);
    $clause = BillClause::factory()->create(['bill_id' => $bill->id]);

    actingAs($citizen)
        ->postJson('/api/v1/submissions', [
            'bill_id' => $bill->id,
            'clause_id' => $clause->id,
            'submission_scope' => 'clause',
            'content' => 'My feedback on this specific clause',
            'submission_type' => 'suggestion',
        ])
        ->assertSuccessful();

    expect(Submission::where('clause_id', $clause->id)->count())->toBe(1);
});
```

## 📊 Database Queries Examples

```php
// Get all top-level clauses for a bill
$sections = Bill::find(1)->topLevelClauses;

// Get clause with all nested children
$clause = BillClause::with('children.children')->find(5);

// Get submissions for a specific clause
$submissions = BillClause::find(3)->submissions()->latest()->get();

// Get clause analytics
$analytics = BillClause::find(3)->analytics;
echo "Support: {$analytics->getSupportPercentage()}%";
echo "Dominant sentiment: {$analytics->getDominantSentiment()}";

// Get full clause hierarchy
$clause = BillClause::find(10);
$path = $clause->getClausePath(); // Array of parent clauses
$fullNumber = $clause->getFullClauseNumber(); // e.g., "5.2.1"
```

## 🎨 Frontend Integration Example

```vue
<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const clauses = ref([])
const selectedClause = ref(null)

const fetchClauses = async (billId) => {
  const { data } = await axios.get(`/api/v1/bills/${billId}/clauses`)
  clauses.value = data.data
}

const selectClause = (clause) => {
  selectedClause.value = clause
}

const submitFeedback = async (content) => {
  await axios.post('/api/v1/submissions', {
    bill_id: selectedClause.value.bill_id,
    clause_id: selectedClause.value.id,
    submission_scope: 'clause',
    content,
    submission_type: 'comment'
  })
}

onMounted(() => {
  const billId = route.params.id
  fetchClauses(billId)
})
</script>

<template>
  <div class="flex gap-6">
    <!-- Clause Tree Navigation -->
    <aside class="w-64">
      <h3>Bill Sections</h3>
      <ul>
        <li v-for="clause in clauses" :key="clause.id">
          <button @click="selectClause(clause)">
            {{ clause.clause_number }}. {{ clause.title }}
            <span class="badge">{{ clause.submissions_count }}</span>
          </button>
        </li>
      </ul>
    </aside>

    <!-- Clause Content -->
    <main class="flex-1">
      <div v-if="selectedClause">
        <h2>{{ selectedClause.title }}</h2>
        <div class="clause-content">
          {{ selectedClause.content }}
        </div>

        <!-- Feedback Form -->
        <form @submit.prevent="submitFeedback">
          <textarea v-model="feedback" placeholder="Your feedback on this clause..."></textarea>
          <button type="submit">Submit Feedback</button>
        </form>

        <!-- Existing Submissions -->
        <div class="submissions">
          <h3>Public Feedback ({{ selectedClause.submissions_count }})</h3>
          <!-- List submissions -->
        </div>
      </div>
    </main>
  </div>
</template>
```

## 🔧 Troubleshooting

### PDF Parsing Fails
**Issue:** `Unable to extract text from PDF`
**Solution:**
- Ensure PDF has text layer (not scanned image)
- Check file permissions on storage/app/public/bills
- Try manual clause addition as fallback

### Clause Relations Not Loading
**Issue:** Clauses show null children
**Solution:**
```php
// Eager load relationships
$clauses = BillClause::with(['children', 'parent', 'analytics'])->get();
```

### Foreign Key Constraint Errors
**Issue:** Cannot delete clause with submissions
**Solution:**
- Cascade deletes are configured in migrations
- Run: `php artisan migrate:fresh` if needed
- Or manually delete child records first

## 📖 Next Steps

1. **Install frontend dependencies** (if not already):
   ```bash
   npm install
   npm run dev
   ```

2. **Create factories** for testing:
   ```bash
   php artisan make:factory BillClauseFactory
   ```

3. **Write comprehensive tests**:
   ```bash
   php artisan test --filter ClauseTest
   ```

4. **Build Vue components** from examples above

5. **Setup queue worker** for async processing:
   ```bash
   php artisan queue:work
   ```

## 🎯 Available API Endpoints

| Method | Endpoint | Auth | Description |
|--------|----------|------|-------------|
| GET | `/api/v1/bills/{bill}/clauses` | Any | List all clauses |
| GET | `/api/v1/bills/{bill}/clauses/{clause}` | Any | Get single clause |
| POST | `/api/v1/bills/{bill}/clauses/parse` | Clerk/Admin | Parse PDF |
| POST | `/api/v1/bills/{bill}/clauses` | Clerk/Admin | Create clause |
| PATCH | `/api/v1/bills/{bill}/clauses/{clause}` | Clerk/Admin | Update clause |
| DELETE | `/api/v1/bills/{bill}/clauses/{clause}` | Clerk/Admin | Delete clause |

All endpoints require authentication via Bearer token.
