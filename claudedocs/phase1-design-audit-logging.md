# Audit Logging System - Technical Design

**Priority**: 🔴 Critical
**Complexity**: Medium
**Estimated Effort**: 3-5 days
**Dependencies**: None (foundational)

---

## 1. Architecture Overview

### Purpose
Create a comprehensive, immutable audit trail for all security-sensitive operations, administrative actions, and data modifications to meet government compliance requirements and enable forensic investigation.

### Key Requirements
- Log ALL data changes with before/after snapshots
- Log authentication events (login, logout, failed attempts)
- Log administrative actions (user suspend, role changes)
- Immutable records (no update/delete capability)
- 7-year retention for government compliance
- Fast write performance (<50ms per log)
- Efficient search and reporting

### Component Architecture
```
┌─────────────────────────────────────────────────────────────┐
│                    Application Layer                         │
├─────────────────────────────────────────────────────────────┤
│  Controllers  │  Middleware  │  Observers  │  Jobs/Events   │
└────────┬──────┴──────┬───────┴─────┬───────┴────────┬───────┘
         │             │             │                │
         └─────────────┴─────────────┴────────────────┘
                            │
                    ┌───────▼────────┐
                    │  AuditLogger   │ ← Service Layer
                    │    Service     │
                    └───────┬────────┘
                            │
                    ┌───────▼────────┐
                    │   AuditLog     │ ← Model
                    │     Model      │
                    └───────┬────────┘
                            │
                    ┌───────▼────────┐
                    │  audit_logs    │ ← Database Table
                    │     Table      │   (Partitioned by month)
                    └────────────────┘
```

---

## 2. Database Schema Design

### Migration: `2025_10_07_000000_create_audit_logs_table.php`

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->id();

            // Who performed the action
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->string('user_type')->nullable(); // Polymorphic support
            $table->string('user_name')->nullable(); // Denormalized for deleted users

            // What happened
            $table->string('event'); // created, updated, deleted, login, logout, etc.
            $table->string('auditable_type'); // Model class name
            $table->unsignedBigInteger('auditable_id')->nullable();

            // Change tracking
            $table->json('old_values')->nullable(); // Before state
            $table->json('new_values')->nullable(); // After state

            // Context
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent')->nullable();
            $table->string('url')->nullable();
            $table->json('tags')->nullable(); // For categorization (admin_action, security_event, etc.)

            $table->timestamp('created_at'); // No updated_at - immutable

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['auditable_type', 'auditable_id']);
            $table->index('event');
            $table->index('created_at'); // For partitioning queries
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audit_logs');
    }
};
```

### Table Partitioning Strategy (PostgreSQL Production)
```sql
-- Monthly partitioning for performance and archival
CREATE TABLE audit_logs_2025_10 PARTITION OF audit_logs
    FOR VALUES FROM ('2025-10-01') TO ('2025-11-01');

CREATE TABLE audit_logs_2025_11 PARTITION OF audit_logs
    FOR VALUES FROM ('2025-11-01') TO ('2025-12-01');

-- Automated partition creation via scheduled job
```

---

## 3. Model Design

### `app/Models/AuditLog.php`

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class AuditLog extends Model
{
    const UPDATED_AT = null; // Immutable - no updates allowed

    protected $fillable = [
        'user_id',
        'user_type',
        'user_name',
        'event',
        'auditable_type',
        'auditable_id',
        'old_values',
        'new_values',
        'ip_address',
        'user_agent',
        'url',
        'tags',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'tags' => 'array',
    ];

    // Relationships
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function auditable(): MorphTo
    {
        return $this->morphTo();
    }

    // Prevent updates and deletes
    public function update(array $attributes = [], array $options = []): bool
    {
        throw new \Exception('Audit logs are immutable and cannot be updated');
    }

    public function delete(): ?bool
    {
        throw new \Exception('Audit logs are immutable and cannot be deleted');
    }

    // Query scopes
    public function scopeForUser($query, User $user)
    {
        return $query->where('user_id', $user->id);
    }

    public function scopeForModel($query, string $modelType, ?int $modelId = null)
    {
        $query->where('auditable_type', $modelType);

        if ($modelId) {
            $query->where('auditable_id', $modelId);
        }

        return $query;
    }

    public function scopeByEvent($query, string $event)
    {
        return $query->where('event', $event);
    }

    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    public function scopeWithTag($query, string $tag)
    {
        return $query->whereJsonContains('tags', $tag);
    }
}
```

---

## 4. Service Layer Design

### `app/Services/AuditLogger.php`

```php
<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

class AuditLogger
{
    /**
     * Log a model event
     */
    public function log(
        string $event,
        Model $model,
        ?array $oldValues = null,
        ?array $newValues = null,
        array $tags = []
    ): AuditLog {
        return AuditLog::create([
            'user_id' => Auth::id(),
            'user_name' => Auth::user()?->name,
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'old_values' => $oldValues,
            'new_values' => $newValues,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'tags' => $tags,
        ]);
    }

    /**
     * Log authentication event
     */
    public function logAuth(string $event, ?int $userId = null, array $context = []): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'user_name' => $userId ? User::find($userId)?->name : null,
            'event' => $event,
            'auditable_type' => 'App\Models\User',
            'auditable_id' => $userId,
            'new_values' => $context,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'url' => Request::fullUrl(),
            'tags' => ['security_event', 'authentication'],
        ]);
    }

    /**
     * Log administrative action
     */
    public function logAdminAction(
        string $action,
        Model $target,
        array $details = []
    ): AuditLog {
        return $this->log(
            $action,
            $target,
            null,
            $details,
            ['admin_action']
        );
    }
}
```

---

## 5. Observer Pattern Implementation

### `app/Observers/AuditObserver.php`

```php
<?php

namespace App\Observers;

use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function created(Model $model): void
    {
        $this->auditLogger->log(
            'created',
            $model,
            null,
            $model->getAttributes()
        );
    }

    public function updated(Model $model): void
    {
        $this->auditLogger->log(
            'updated',
            $model,
            $model->getOriginal(),
            $model->getChanges()
        );
    }

    public function deleted(Model $model): void
    {
        $this->auditLogger->log(
            'deleted',
            $model,
            $model->getAttributes(),
            null
        );
    }

    public function restored(Model $model): void
    {
        $this->auditLogger->log(
            'restored',
            $model,
            null,
            $model->getAttributes(),
            ['restoration']
        );
    }
}
```

### Applying Observer to Models

Add to `app/Providers/EventServiceProvider.php`:

```php
use App\Observers\AuditObserver;

protected $observers = [
    User::class => [AuditObserver::class],
    Bill::class => [AuditObserver::class],
    Submission::class => [AuditObserver::class],
    // Add other critical models
];
```

---

## 6. Middleware for Request Auditing

### `app/Http/Middleware/AuditRequest.php`

```php
<?php

namespace App\Http\Middleware;

use App\Services\AuditLogger;
use Closure;
use Illuminate\Http\Request;

class AuditRequest
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function handle(Request $request, Closure $next)
    {
        // Only audit specific routes (admin actions, sensitive operations)
        if ($this->shouldAudit($request)) {
            $this->auditLogger->log(
                'http_request',
                new class extends \Illuminate\Database\Eloquent\Model {
                    protected $table = 'requests'; // Virtual
                },
                null,
                [
                    'method' => $request->method(),
                    'path' => $request->path(),
                    'query' => $request->query(),
                    'input' => $this->sanitizeInput($request->all()),
                ],
                ['http_request']
            );
        }

        return $next($request);
    }

    private function shouldAudit(Request $request): bool
    {
        // Audit admin routes, user management, bill status changes
        return $request->is('admin/*')
            || $request->is('api/admin/*')
            || $request->routeIs('*.destroy')
            || $request->routeIs('*.suspend')
            || $request->routeIs('*.restore');
    }

    private function sanitizeInput(array $input): array
    {
        // Remove sensitive fields from audit log
        return array_diff_key($input, array_flip([
            'password',
            'password_confirmation',
            'current_password',
            'token',
        ]));
    }
}
```

---

## 7. Authentication Event Listeners

### `app/Listeners/LogAuthenticationEvents.php`

```php
<?php

namespace App\Listeners;

use App\Services\AuditLogger;
use Illuminate\Auth\Events\Login;
use Illuminate\Auth\Events\Logout;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Lockout;

class LogAuthenticationEvents
{
    public function __construct(private AuditLogger $auditLogger)
    {
    }

    public function handleLogin(Login $event): void
    {
        $this->auditLogger->logAuth('login', $event->user->id, [
            'remember' => $event->remember,
        ]);
    }

    public function handleLogout(Logout $event): void
    {
        $this->auditLogger->logAuth('logout', $event->user?->id);
    }

    public function handleFailed(Failed $event): void
    {
        $this->auditLogger->logAuth('login_failed', null, [
            'credentials' => ['email' => $event->credentials['email'] ?? 'unknown'],
        ]);
    }

    public function handleLockout(Lockout $event): void
    {
        $this->auditLogger->logAuth('account_locked', null, [
            'email' => $event->request->input('email'),
        ]);
    }
}
```

Register in `app/Providers/EventServiceProvider.php`:

```php
protected $listen = [
    Login::class => [LogAuthenticationEvents::class . '@handleLogin'],
    Logout::class => [LogAuthenticationEvents::class . '@handleLogout'],
    Failed::class => [LogAuthenticationEvents::class . '@handleFailed'],
    Lockout::class => [LogAuthenticationEvents::class . '@handleLockout'],
];
```

---

## 8. Admin Interface - Audit Log Viewer

### Route: `routes/web.php`

```php
Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('/audit-logs', [AuditLogController::class, 'index'])->name('admin.audit.index');
    Route::get('/audit-logs/{auditLog}', [AuditLogController::class, 'show'])->name('admin.audit.show');
    Route::get('/audit-logs/export', [AuditLogController::class, 'export'])->name('admin.audit.export');
});
```

### Controller: `app/Http/Controllers/Admin/AuditLogController.php`

```php
<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        $query = AuditLog::query()
            ->with('user:id,name,email')
            ->latest();

        // Apply filters
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('auditable_type')) {
            $query->where('auditable_type', $request->auditable_type);
        }

        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        if ($request->filled('tag')) {
            $query->withTag($request->tag);
        }

        $logs = $query->paginate(50);

        return Inertia::render('Admin/AuditLogs/Index', [
            'logs' => $logs,
            'filters' => $request->only(['user_id', 'event', 'auditable_type', 'date_from', 'date_to', 'tag']),
        ]);
    }

    public function show(AuditLog $auditLog)
    {
        $auditLog->load('user:id,name,email', 'auditable');

        return Inertia::render('Admin/AuditLogs/Show', [
            'log' => $auditLog,
        ]);
    }

    public function export(Request $request)
    {
        // Export to CSV using Laravel Excel or similar
        // Apply same filters as index method
    }
}
```

---

## 9. Testing Strategy

### Feature Test: `tests/Feature/AuditLoggingTest.php`

```php
<?php

use App\Models\User;
use App\Models\Bill;
use App\Models\AuditLog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('bill creation is audited', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $bill = Bill::factory()->create(['title' => 'Test Bill']);

    $auditLog = AuditLog::where('auditable_type', Bill::class)
        ->where('auditable_id', $bill->id)
        ->where('event', 'created')
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->user_id)->toBe($user->id);
    expect($auditLog->new_values['title'])->toBe('Test Bill');
});

test('bill update captures old and new values', function () {
    $user = User::factory()->create();
    $bill = Bill::factory()->create(['title' => 'Original Title']);

    $this->actingAs($user);

    $bill->update(['title' => 'Updated Title']);

    $auditLog = AuditLog::where('auditable_type', Bill::class)
        ->where('auditable_id', $bill->id)
        ->where('event', 'updated')
        ->first();

    expect($auditLog->old_values['title'])->toBe('Original Title');
    expect($auditLog->new_values['title'])->toBe('Updated Title');
});

test('login event is audited', function () {
    $user = User::factory()->create();

    $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $auditLog = AuditLog::where('event', 'login')
        ->where('user_id', $user->id)
        ->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->tags)->toContain('security_event');
});

test('failed login is audited', function () {
    $this->post(route('login'), [
        'email' => 'wrong@example.com',
        'password' => 'wrong',
    ]);

    $auditLog = AuditLog::where('event', 'login_failed')->first();

    expect($auditLog)->not->toBeNull();
    expect($auditLog->new_values['credentials']['email'])->toBe('wrong@example.com');
});

test('audit logs are immutable', function () {
    $auditLog = AuditLog::factory()->create();

    expect(fn () => $auditLog->update(['event' => 'modified']))
        ->toThrow(\Exception::class, 'immutable');
});
```

---

## 10. Performance Considerations

### Async Logging (Optional Enhancement)
```php
// Dispatch to queue for high-traffic scenarios
dispatch(new LogAuditEvent($event, $model, $oldValues, $newValues));
```

### Database Partitioning
- Monthly partitions for PostgreSQL production
- Automated partition creation via scheduled job
- Archive old partitions to cold storage after 2 years

### Indexing Strategy
- Composite index on (user_id, created_at) for user activity queries
- Index on auditable_type + auditable_id for model history queries
- Index on created_at for date range queries and partitioning

---

## 11. Compliance & Retention

### Retention Policy
- **Active Storage**: 2 years in primary database (partitioned)
- **Archive Storage**: Years 3-7 in compressed cold storage (S3 or similar)
- **Legal Hold**: Ability to prevent deletion of specific records pending litigation

### GDPR Considerations
- Audit logs EXEMPT from "right to be forgotten" (legal requirement)
- User data in logs retained for compliance, not deleted with user account
- Clear privacy policy explaining audit log retention

---

## 12. Implementation Checklist

### Phase 1: Foundation (Week 1)
- [ ] Create migration for audit_logs table
- [ ] Create AuditLog model with immutability safeguards
- [ ] Create AuditLogger service
- [ ] Create AuditObserver and apply to critical models
- [ ] Write unit tests for AuditLogger service

### Phase 2: Integration (Week 1-2)
- [ ] Create authentication event listeners
- [ ] Create AuditRequest middleware for admin routes
- [ ] Register observers in EventServiceProvider
- [ ] Write feature tests for common audit scenarios

### Phase 3: Admin Interface (Week 2)
- [ ] Create AuditLogController with search/filter logic
- [ ] Create Admin/AuditLogs/Index.vue component
- [ ] Create Admin/AuditLogs/Show.vue component
- [ ] Add export functionality (CSV/PDF)

### Phase 4: Production Readiness (Week 3)
- [ ] Configure database partitioning for PostgreSQL
- [ ] Create scheduled job for partition management
- [ ] Add archival process for old logs
- [ ] Performance testing with 1M+ records
- [ ] Security review and penetration testing

---

## 13. Success Metrics

- **Coverage**: 100% of data modifications audited
- **Performance**: <50ms write latency for audit logs
- **Compliance**: 7-year retention policy enforced
- **Search**: Audit log queries return in <3 seconds
- **Reliability**: Zero audit log write failures

---

This design provides a complete, enterprise-grade audit logging system meeting government compliance requirements while maintaining excellent performance.
