# Public Participation Platform: System Architecture Blueprint & Infrastructure Roadmap

**Document Version:** 1.0
**Date:** October 7, 2025
**System:** Huduma Ya Raia - National Public Participation Platform
**Scale Target:** National-level (47 counties, 50M+ citizens)

---

## Executive Summary

This document provides a comprehensive system architecture analysis and infrastructure recommendations for the Kenyan Public Participation Platform. The analysis covers current state assessment, scalability requirements, infrastructure design, integration architecture, security architecture, and a phased implementation roadmap.

**Key Findings:**
- Current monolithic Laravel architecture is well-structured but requires strategic enhancements for national scale
- Database migration from SQLite to managed PostgreSQL is critical path item
- Hybrid multi-channel architecture (web, SMS, USSD, IVR) requires distributed processing
- Expected peak load: 10,000+ concurrent users during active bill periods
- Recommended deployment: Kubernetes-based with multi-region failover capability

---

## 1. Current Architecture Analysis

### 1.1 Technology Stack Assessment

**Backend Framework:**
- Laravel 12.28.1 (PHP 8.4.1)
- Modern Laravel 12 structure with streamlined organization
- Inertia.js v2 for SSR + Vue 3.5.18 frontend

**Current Database:**
- SQLite (development/prototyping)
- **Critical Gap:** Not suitable for production at national scale
- No connection pooling, limited concurrency, single-writer bottleneck

**Queue System:**
- Database-backed queue (shared with primary database)
- **Concern:** Queue and primary DB share resources
- Notifications queue for async processing

**Current Strengths:**
1. Clean separation of concerns (Controllers, Models, Jobs, Policies)
2. Role-based access control (Citizen, Clerk, Legislator, Admin)
3. Multi-channel submission handling (web, SMS, USSD, IVR)
4. Observer pattern for side effects (BillObserver, SubmissionObserver)
5. Queued notification system for scalability

**Current Limitations:**
1. SQLite cannot handle concurrent writes at scale
2. Database-backed queue shares resources with primary DB
3. No caching layer for frequently accessed data
4. Single-server deployment assumption
5. No geographic distribution strategy
6. Limited monitoring and observability

### 1.2 System Architecture Pattern

**Current Pattern:** Modular Monolith with Service Layer

```
┌─────────────────────────────────────────────────┐
│           Frontend (Inertia + Vue 3)            │
│  ┌──────────┬──────────┬──────────┬──────────┐ │
│  │  Citizen │  Clerk   │Legislator│  Admin   │ │
│  │  Portal  │Dashboard │ Dashboard│Dashboard │ │
│  └──────────┴──────────┴──────────┴──────────┘ │
└─────────────────┬───────────────────────────────┘
                  │ Inertia Protocol (SSR)
┌─────────────────▼───────────────────────────────┐
│             Laravel Application                  │
│  ┌──────────────────────────────────────────┐  │
│  │         HTTP/API Controllers              │  │
│  ├──────────────────────────────────────────┤  │
│  │   Service Layer (PDF, AI, Parsing)       │  │
│  ├──────────────────────────────────────────┤  │
│  │      Domain Models & Business Logic      │  │
│  ├──────────────────────────────────────────┤  │
│  │   Queue Jobs (Notifications, Reports)    │  │
│  ├──────────────────────────────────────────┤  │
│  │        Observers (Bill, Submission)      │  │
│  └──────────────────────────────────────────┘  │
└─────────────────┬───────────────────────────────┘
                  │
┌─────────────────▼───────────────────────────────┐
│          Data & Integration Layer               │
│  ┌──────────┬──────────┬──────────┬──────────┐ │
│  │ SQLite   │  Queue   │  Files   │  Ext API │ │
│  │   DB     │  Table   │  (PDFs)  │ (Twilio) │ │
│  └──────────┴──────────┴──────────┴──────────┘ │
└─────────────────────────────────────────────────┘
```

**Assessment:** Good foundation for MVP, but requires evolution for production scale.

---

## 2. Scalability & Performance Architecture

### 2.1 Expected Load Profile

**User Base Projections:**
- Year 1: 100,000 verified users
- Year 3: 500,000 verified users
- Year 5: 2,000,000+ verified users

**Traffic Patterns:**
- **Normal Load:** 100-500 concurrent users
- **Active Bill Period:** 5,000-10,000 concurrent users
- **Peak Events:** 15,000+ concurrent users (national significance bills)
- **Submission Spikes:** 1,000+ submissions/minute during deadline hours

**Geographic Distribution:**
- 47 counties across Kenya
- Urban concentration: Nairobi (40%), Mombasa (15%), Kisumu (10%)
- Rural access: 35% via USSD/SMS/IVR

### 2.2 Database Scaling Strategy

**Phase 1: Managed PostgreSQL (Immediate - Month 1-2)**

```yaml
Database: PostgreSQL 16+
Deployment: AWS RDS / Azure Database / DO Managed Postgres
Configuration:
  Instance: db.r6g.xlarge (4 vCPU, 32GB RAM) - initial
  Storage: 500GB SSD with auto-scaling to 2TB
  Backups:
    - Automated daily snapshots (30-day retention)
    - Point-in-time recovery (5-minute granularity)
    - Cross-region backup replication
  High Availability:
    - Multi-AZ deployment
    - Automatic failover (30-60 second RTO)
    - Read replicas: 2x for reporting and analytics
  Connection Pooling:
    - PgBouncer layer (500 connections → 100 backend)
    - Laravel config: pool_size=20 per app instance
```

**Migration Strategy:**
```bash
# Step 1: Schema Migration
php artisan migrate --env=production --database=pgsql

# Step 2: Data Migration (with batching)
php artisan migrate:sqlite-to-postgres --chunk=1000

# Step 3: Verification
php artisan db:verify-migration

# Step 4: Cutover (zero-downtime)
# - Run both DBs in parallel (1 hour)
# - Switch reads to PostgreSQL
# - Monitor for 24 hours
# - Switch writes to PostgreSQL
# - Deprecate SQLite
```

**Phase 2: Read Scaling (Month 3-6)**

```
┌──────────────────────────────────────────────┐
│         Application Servers (10x)            │
└────┬─────────┬─────────┬─────────┬──────────┘
     │ Write   │  Read   │  Read   │  Read
     │         │         │         │
┌────▼─────────┴─────────┴─────────┴──────────┐
│           PgBouncer Pool                     │
└────┬─────────┬─────────┬─────────┬──────────┘
     │         │         │         │
┌────▼──┐  ┌───▼──┐  ┌───▼──┐  ┌───▼──┐
│Primary│  │Read  │  │Read  │  │Read  │
│ (RW)  │  │Rep #1│  │Rep #2│  │Rep #3│
│ AZ-A  │  │ AZ-B │  │ AZ-A │  │ AZ-C │
└───────┘  └──────┘  └──────┘  └──────┘
```

**Read/Write Separation in Laravel:**
```php
// config/database.php
'pgsql' => [
    'read' => [
        'host' => [
            env('DB_READ_HOST_1'),
            env('DB_READ_HOST_2'),
            env('DB_READ_HOST_3'),
        ],
    ],
    'write' => [
        'host' => env('DB_WRITE_HOST'),
    ],
    'sticky' => true, // Ensure read-your-writes
],
```

**Phase 3: Sharding Strategy (Year 2+)**

**Vertical Sharding (Service-based):**
```
┌────────────────┐  ┌────────────────┐  ┌────────────────┐
│   Users DB     │  │    Bills DB    │  │ Submissions DB │
│   (Primary)    │  │   (Secondary)  │  │   (Secondary)  │
│                │  │                │  │                │
│ - users        │  │ - bills        │  │ - submissions  │
│ - sessions     │  │ - clauses      │  │ - drafts       │
│ - counties     │  │ - summaries    │  │ - engagements  │
│ - wards        │  │ - highlights   │  │ - analytics    │
└────────────────┘  └────────────────┘  └────────────────┘
```

**Horizontal Sharding (if needed):**
```
Submissions Sharding by Bill ID:
- Shard 0: bill_id % 4 == 0
- Shard 1: bill_id % 4 == 1
- Shard 2: bill_id % 4 == 2
- Shard 3: bill_id % 4 == 3

Benefits:
- Distributes high-volume submission processing
- Parallel analytics processing per bill
- Isolated failure domains
```

### 2.3 Caching Layer Architecture

**Multi-Tier Caching Strategy:**

```
┌─────────────────────────────────────────────┐
│          Application Servers                │
│  ┌────────────────────────────────────────┐ │
│  │    OPcache (Opcodes & Strings)         │ │
│  │    Laravel Config Cache                │ │
│  └────────────────────────────────────────┘ │
└───────────────────┬─────────────────────────┘
                    │
┌───────────────────▼─────────────────────────┐
│           Redis Cluster (Cache)             │
│  ┌──────────────────────────────────────┐  │
│  │  Session Store (separate DB)         │  │
│  │  Cache Store (TTL-based)             │  │
│  │  Rate Limiting (atomic counters)     │  │
│  └──────────────────────────────────────┘  │
│  Config: 3 masters + 3 replicas (HA)       │
└───────────────────┬─────────────────────────┘
                    │
┌───────────────────▼─────────────────────────┐
│            PostgreSQL (Source)              │
└─────────────────────────────────────────────┘
```

**Caching Policies:**

```php
// Bill Detail Cache (15 minutes TTL)
Cache::remember("bill:{$billId}", 900, function() use ($billId) {
    return Bill::with(['summary', 'clauses', 'creator'])
        ->findOrFail($billId);
});

// Active Bills List (5 minutes TTL)
Cache::tags(['bills', 'active'])->remember('bills:active', 300, function() {
    return Bill::openForParticipation()
        ->with(['summary'])
        ->get();
});

// User Session (1 hour TTL, renewed on activity)
Cache::put("user:session:{$userId}", $sessionData, 3600);

// Submission Counts (1 minute TTL, high churn)
Cache::remember("bill:{$billId}:submission_count", 60, function() use ($billId) {
    return Submission::where('bill_id', $billId)->count();
});
```

**Cache Invalidation Strategy:**
```php
// Using Model Observers
class BillObserver {
    public function updated(Bill $bill): void {
        Cache::forget("bill:{$bill->id}");
        Cache::tags(['bills', 'active'])->flush();
    }
}

class SubmissionObserver {
    public function created(Submission $submission): void {
        Cache::forget("bill:{$submission->bill_id}:submission_count");
        // Async job to update aggregates
        dispatch(new UpdateBillSubmissionCount($submission->bill_id));
    }
}
```

**Redis Cluster Configuration:**
```yaml
Redis Deployment:
  Mode: Cluster (3 masters, 3 replicas)
  Instance: cache.r6g.large (2 vCPU, 13GB RAM)
  Persistence: RDB snapshots + AOF (appendonly)
  Eviction: allkeys-lru (Least Recently Used)
  Max Memory: 10GB per node
  Network: VPC Peering with app servers
```

### 2.4 CDN & Static Asset Optimization

**CDN Strategy:**
```
CloudFlare CDN (or AWS CloudFront)
├─ Static Assets (JS, CSS, Images)
│  ├─ Edge Locations: Nairobi, Lagos, Cape Town
│  ├─ Cache TTL: 7 days (versioned assets)
│  └─ Compression: Brotli + Gzip
├─ Bill PDFs (Large Files)
│  ├─ Origin: S3/Object Storage
│  ├─ Cache TTL: 30 days (immutable)
│  └─ Signed URLs (security)
└─ Dynamic Content (HTML)
   ├─ Cache TTL: 5 minutes (authenticated users: bypass)
   └─ Vary: Cookie, Accept-Language
```

**Laravel Asset Pipeline:**
```javascript
// vite.config.ts
export default {
  build: {
    rollupOptions: {
      output: {
        manualChunks: {
          vendor: ['vue', 'axios'],
          inertia: ['@inertiajs/vue3'],
          ui: ['@headlessui/vue', '@heroicons/vue'],
        },
      },
    },
  },
};
```

### 2.5 Queue Architecture

**Phase 1: Redis-backed Queue (Month 1-2)**

```yaml
Queue Backend: Redis (separate from cache)
Deployment: AWS ElastiCache / Redis Cloud
Configuration:
  Mode: Master-Replica (2 nodes)
  Instance: cache.r6g.large
  Persistence: AOF + RDB
  Queues:
    - default (general processing)
    - notifications (SMS, email, push)
    - analytics (AI processing, reporting)
    - high-priority (OTP, critical alerts)
  Workers:
    - default: 5 workers
    - notifications: 10 workers (parallel SMS)
    - analytics: 3 workers (CPU intensive)
    - high-priority: 5 workers (low latency)
```

**Worker Deployment:**
```bash
# Supervisor configuration for queue workers
[program:laravel-worker-default]
process_name=%(program_name)s_%(process_num)02d
command=php /app/artisan queue:work redis --queue=default --tries=3 --timeout=180
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=5

[program:laravel-worker-notifications]
command=php /app/artisan queue:work redis --queue=notifications --tries=3 --timeout=60
numprocs=10

[program:laravel-worker-high-priority]
command=php /app/artisan queue:work redis --queue=high-priority --tries=5 --timeout=30
numprocs=5
```

**Phase 2: SQS for Multi-Region (Year 2)**

```
┌────────────────────────────────────────────┐
│         Application Servers                │
└──────────┬─────────────────────┬───────────┘
           │                     │
┌──────────▼──────┐   ┌──────────▼──────────┐
│  SQS Standard   │   │  SQS FIFO (OTP)     │
│  (Notifications)│   │  (Order Critical)   │
└──────────┬──────┘   └──────────┬──────────┘
           │                     │
┌──────────▼──────────────────────▼──────────┐
│       Worker Fleets (Auto-Scaling)         │
│  ┌─────────┬─────────┬─────────┬────────┐ │
│  │Worker-1 │Worker-2 │Worker-3 │...N    │ │
│  └─────────┴─────────┴─────────┴────────┘ │
└────────────────────────────────────────────┘
```

---

## 3. Infrastructure & DevOps Architecture

### 3.1 Deployment Architecture

**Recommended Deployment: Kubernetes on Managed Service**

**Why Kubernetes:**
1. Auto-scaling for traffic spikes
2. Zero-downtime deployments
3. Self-healing and fault tolerance
4. Multi-region failover capability
5. Container-based consistency
6. GitOps workflow integration

**Target Platform Options:**
1. **AWS EKS** (Elastic Kubernetes Service) - Recommended
2. **Azure AKS** (Azure Kubernetes Service)
3. **Google GKE** (Google Kubernetes Engine)
4. **Digital Ocean Kubernetes** (Cost-effective alternative)

**Cluster Architecture:**

```
Production Kubernetes Cluster
├─ Control Plane (Managed by Cloud Provider)
├─ Node Pool 1: Application Servers
│  ├─ Instance: t3.xlarge (4 vCPU, 16GB RAM)
│  ├─ Auto-scaling: 3-15 nodes
│  ├─ Workloads:
│  │  ├─ Laravel App (5-20 pods)
│  │  └─ Queue Workers (5-15 pods)
│  └─ Affinity: CPU-optimized
├─ Node Pool 2: Background Jobs
│  ├─ Instance: c6i.2xlarge (8 vCPU, 16GB RAM)
│  ├─ Auto-scaling: 2-8 nodes
│  ├─ Workloads:
│  │  ├─ AI/ML Processing (2-5 pods)
│  │  └─ Report Generation (2-5 pods)
│  └─ Affinity: CPU-intensive
└─ Node Pool 3: Monitoring & Logging
   ├─ Instance: t3.medium (2 vCPU, 4GB RAM)
   ├─ Static: 2 nodes (HA)
   └─ Workloads:
      ├─ Prometheus (metrics)
      ├─ Grafana (dashboards)
      └─ Loki (logs)
```

**Kubernetes Deployment Manifests:**

```yaml
# Laravel Application Deployment
apiVersion: apps/v1
kind: Deployment
metadata:
  name: laravel-app
  namespace: production
spec:
  replicas: 5
  strategy:
    type: RollingUpdate
    rollingUpdate:
      maxSurge: 2
      maxUnavailable: 1
  selector:
    matchLabels:
      app: laravel-app
  template:
    metadata:
      labels:
        app: laravel-app
    spec:
      containers:
      - name: laravel
        image: registry.example.com/public-participate:latest
        ports:
        - containerPort: 8000
        env:
        - name: APP_ENV
          value: "production"
        - name: DB_HOST
          valueFrom:
            secretKeyRef:
              name: database-credentials
              key: host
        resources:
          requests:
            memory: "512Mi"
            cpu: "500m"
          limits:
            memory: "2Gi"
            cpu: "2000m"
        livenessProbe:
          httpGet:
            path: /up
            port: 8000
          initialDelaySeconds: 30
          periodSeconds: 10
        readinessProbe:
          httpGet:
            path: /up
            port: 8000
          initialDelaySeconds: 10
          periodSeconds: 5
---
# Horizontal Pod Autoscaler
apiVersion: autoscaling/v2
kind: HorizontalPodAutoscaler
metadata:
  name: laravel-app-hpa
spec:
  scaleTargetRef:
    apiVersion: apps/v1
    kind: Deployment
    name: laravel-app
  minReplicas: 5
  maxReplicas: 20
  metrics:
  - type: Resource
    resource:
      name: cpu
      target:
        type: Utilization
        averageUtilization: 70
  - type: Resource
    resource:
      name: memory
      target:
        type: Utilization
        averageUtilization: 80
```

### 3.2 Load Balancing & Ingress

```
Internet (Users)
       │
┌──────▼──────────────────────────────────┐
│  CloudFlare (DDoS Protection, WAF)     │
│  - Rate Limiting: 1000 req/min/IP      │
│  - Bot Protection                       │
└──────┬──────────────────────────────────┘
       │
┌──────▼──────────────────────────────────┐
│  AWS Application Load Balancer (ALB)   │
│  - HTTPS Termination (TLS 1.3)         │
│  - SSL Certificate: ACM/Let's Encrypt  │
│  - Health Checks: /up endpoint         │
└──────┬──────────────────────────────────┘
       │
┌──────▼──────────────────────────────────┐
│  Kubernetes Ingress Controller         │
│  (NGINX Ingress or AWS ALB Controller) │
│  - Path-based routing                  │
│  - Request buffering                   │
└──────┬──────────────────────────────────┘
       │
   ┌───┴────┬──────────┬──────────┐
   │        │          │          │
┌──▼──┐ ┌──▼──┐ ┌────▼──┐ ┌────▼──┐
│Pod-1│ │Pod-2│ │Pod-3  │ │...N   │
└─────┘ └─────┘ └───────┘ └───────┘
```

**Ingress Configuration:**
```yaml
apiVersion: networking.k8s.io/v1
kind: Ingress
metadata:
  name: laravel-ingress
  annotations:
    cert-manager.io/cluster-issuer: "letsencrypt-prod"
    nginx.ingress.kubernetes.io/ssl-redirect: "true"
    nginx.ingress.kubernetes.io/rate-limit: "100"
spec:
  ingressClassName: nginx
  tls:
  - hosts:
    - participate.parliament.go.ke
    secretName: participate-tls
  rules:
  - host: participate.parliament.go.ke
    http:
      paths:
      - path: /
        pathType: Prefix
        backend:
          service:
            name: laravel-app
            port:
              number: 80
```

### 3.3 Monitoring & Observability

**Three Pillars of Observability:**

**1. Metrics (Prometheus + Grafana)**

```yaml
Prometheus Stack:
  Deployment: Kubernetes Operator
  Data Retention: 30 days
  Storage: 100GB SSD
  Scrape Interval: 15 seconds

  Metrics Collected:
    - Application Metrics (Laravel Telescope/Horizon)
      - Request rate, latency, error rate
      - Queue job processing times
      - Database query performance
    - Infrastructure Metrics
      - CPU, Memory, Disk, Network per pod
      - Kubernetes cluster health
      - Node resource utilization
    - Business Metrics
      - Bills published per day
      - Submissions per bill
      - User registrations per day
      - Channel usage (web, SMS, USSD)

Grafana Dashboards:
  - Real-time Traffic Dashboard
  - Application Performance Dashboard
  - Database Performance Dashboard
  - Business Metrics Dashboard
  - Alert Status Dashboard
```

**2. Logging (Loki + Promtail)**

```yaml
Loki Configuration:
  Deployment: StatefulSet (2 replicas)
  Storage: S3 (long-term) + SSD (recent)
  Retention: 90 days
  Compression: Gzip

  Log Sources:
    - Laravel Application Logs (daily channel)
      - Request logs (access, errors)
      - Queue job logs
      - Authentication logs
      - Security events
    - Infrastructure Logs
      - Kubernetes events
      - Ingress access logs
      - Container stdout/stderr
    - Database Logs
      - Slow query logs (>500ms)
      - Connection errors
      - Replication lag

Log Aggregation:
  - Promtail agents on each node
  - Structured JSON logging
  - Log levels: DEBUG, INFO, WARN, ERROR
  - Correlation IDs for request tracing
```

**3. Tracing (Jaeger or Zipkin)**

```yaml
Distributed Tracing:
  Backend: Jaeger (or AWS X-Ray)
  Sampling: 1% of requests (10% for errors)

  Trace Spans:
    - HTTP Request (Inertia)
    - Database Queries (Eloquent)
    - Cache Operations (Redis)
    - Queue Jobs (Dispatch → Process)
    - External API Calls (Twilio, eCitizen)

  Use Cases:
    - Identify slow API endpoints
    - Debug multi-service call chains
    - Analyze queue job performance
    - Troubleshoot integration issues
```

**Alerting Strategy (PagerDuty or Opsgenie):**

```yaml
Critical Alerts (Immediate Response):
  - Application error rate > 5%
  - Response time P95 > 3 seconds
  - Database connection pool exhaustion
  - Queue worker failures > 10%
  - Disk usage > 90%
  - SSL certificate expiry < 7 days

Warning Alerts (24-hour Response):
  - Application error rate > 1%
  - Response time P95 > 1.5 seconds
  - Database query time P95 > 500ms
  - Cache hit rate < 80%
  - Queue depth > 10,000 jobs
  - Memory usage > 85%

Notification Channels:
  - Slack: #alerts-production
  - Email: ops@parliament.go.ke
  - SMS: On-call engineer (critical only)
  - PagerDuty: Escalation policy
```

### 3.4 Backup & Disaster Recovery

**Database Backup Strategy:**

```yaml
Automated Backups (RDS/Managed):
  Full Backup:
    - Daily at 02:00 EAT (low traffic)
    - Retention: 30 days
    - Cross-region replication to DR site

  Incremental Backup:
    - Continuous (transaction log shipping)
    - Point-in-time recovery (5-minute RPO)

  Snapshot Testing:
    - Weekly restore test to staging
    - Automated restore verification
    - Performance benchmark

Manual Backups:
  - Before major migrations
  - Before schema changes
  - Before data cleanup operations
  - Retention: 90 days
```

**Application State Backup:**

```yaml
File Storage Backup (S3/Object Storage):
  Content:
    - Bill PDFs
    - User uploads
    - Generated reports

  Strategy:
    - Versioning enabled (S3)
    - Cross-region replication
    - Lifecycle policy: Archive to Glacier after 1 year

  Retention:
    - Active files: Indefinite (legal requirement)
    - Deleted files: 90-day soft delete

Redis Backup:
  Strategy: RDB snapshots + AOF
  Frequency: Hourly snapshots
  Retention: 7 days
  Use Case: Session recovery, cache warm-up
```

**Disaster Recovery Plan:**

```yaml
RTO (Recovery Time Objective): 1 hour
RPO (Recovery Point Objective): 5 minutes

Disaster Scenarios:
  1. Primary Region Failure (e.g., AWS us-east-1)
     Action:
       - Automatic DNS failover to DR region (Route53)
       - Promote read replica to primary (2 minutes)
       - Scale up DR application pods
       - Restore Redis from snapshot
     Expected Downtime: 15-30 minutes

  2. Database Corruption
     Action:
       - Point-in-time restore from automated backup
       - Restore to parallel instance
       - Verify data integrity
       - Switch application connection
     Expected Downtime: 30-60 minutes

  3. Application-Level Incident (Bad Deployment)
     Action:
       - Kubernetes rollback to previous version (instant)
       - No data loss
     Expected Downtime: 2-5 minutes

DR Testing:
  - Quarterly full DR drill
  - Monthly partial failover test
  - Post-incident review and improvement
```

### 3.5 CI/CD Pipeline

**GitOps Workflow:**

```
Developer → Git Push → GitHub/GitLab
                ↓
         ┌──────────────────┐
         │   CI Pipeline    │
         │  (GitHub Actions)│
         ├──────────────────┤
         │ 1. Lint (Pint)   │
         │ 2. Tests (Pest)  │
         │ 3. Security Scan │
         │ 4. Build Image   │
         │ 5. Push Registry │
         └──────┬───────────┘
                ↓
         ┌──────────────────┐
         │  ArgoCD / Flux   │
         │  (GitOps Sync)   │
         ├──────────────────┤
         │ 1. Detect Change │
         │ 2. Apply Manifest│
         │ 3. Health Check  │
         └──────┬───────────┘
                ↓
         ┌──────────────────┐
         │  Kubernetes      │
         │  Rolling Update  │
         └──────────────────┘
```

**GitHub Actions Pipeline:**

```yaml
name: CI/CD Pipeline

on:
  push:
    branches: [main, staging, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: 8.4

      - name: Install Dependencies
        run: composer install --no-interaction --prefer-dist

      - name: Run Laravel Pint
        run: vendor/bin/pint --test

      - name: Run Pest Tests
        run: vendor/bin/pest --coverage --min=80

      - name: Security Audit
        run: composer audit

  build:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - name: Build Docker Image
        run: |
          docker build -t participate:${{ github.sha }} .
          docker tag participate:${{ github.sha }} registry.example.com/participate:latest

      - name: Push to Registry
        run: docker push registry.example.com/participate:latest

      - name: Update Kubernetes Manifest
        run: |
          sed -i 's|IMAGE_TAG|${{ github.sha }}|g' k8s/deployment.yaml
          git commit -am "Update image to ${{ github.sha }}"
          git push
```

**Deployment Environments:**

```yaml
Environments:
  1. Development (dev)
     - Auto-deploy from develop branch
     - Low-resource cluster (2 nodes)
     - SQLite database (local dev)

  2. Staging (staging)
     - Auto-deploy from staging branch
     - Production-like cluster (3 nodes)
     - PostgreSQL replica (de-identified data)
     - Full integration testing

  3. Production (prod)
     - Manual approval for deploy
     - Full HA cluster (5+ nodes)
     - Production database
     - Blue-green deployment strategy
```

---

## 4. Integration Architecture

### 4.1 External Service Integrations

**Integration Point Matrix:**

| Service | Purpose | Protocol | Criticality | Fallback |
|---------|---------|----------|-------------|----------|
| **eCitizen ID** | National ID Verification | REST API | High | Manual Verification |
| **Twilio** | SMS (OTP, Notifications) | REST API | High | Alternative SMS Gateway |
| **SendGrid/SES** | Email Delivery | SMTP/API | Medium | Direct SMTP |
| **Africa's Talking** | USSD Gateway | REST API | High | Downtime Notice |
| **OpenAI/Local LLM** | AI Summaries | REST API | Medium | Queue for Later |
| **Object Storage** | PDF Storage | S3 API | Critical | Local Filesystem (temp) |

### 4.2 eCitizen ID Verification Integration

**Architecture:**

```
┌──────────────────────────────────────────┐
│      User Registration Flow              │
└──────────────────┬───────────────────────┘
                   │
           1. Submit National ID
                   │
┌──────────────────▼───────────────────────┐
│     Laravel Application                  │
│  ┌────────────────────────────────────┐ │
│  │  1. Validate ID Format (Kenyan)   │ │
│  │  2. Check Local Cache (Redis)     │ │
│  │  3. Call eCitizen API (async)     │ │
│  └────────────┬───────────────────────┘ │
└───────────────┼──────────────────────────┘
                │
┌───────────────▼──────────────────────────┐
│     eCitizen Verification Service        │
│  ┌────────────────────────────────────┐ │
│  │  API Endpoint: /verify-id          │ │
│  │  Method: POST                      │ │
│  │  Payload: { national_id, dob }    │ │
│  │  Response: { verified, name, ... }│ │
│  └────────────────────────────────────┘ │
└───────────────┬──────────────────────────┘
                │
         ┌──────┴────────┐
    Success         Failure
         │               │
┌────────▼──┐    ┌───────▼────────┐
│ Cache     │    │ Retry (3x)     │
│ Result    │    │ Then: Manual   │
│ (7 days)  │    │ Review         │
└───────────┘    └────────────────┘
```

**Implementation:**

```php
// app/Services/Verification/ECitizenVerificationService.php
namespace App\Services\Verification;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class ECitizenVerificationService
{
    public function verifyNationalId(string $nationalId, string $dateOfBirth): array
    {
        // Check cache first (7-day TTL)
        $cacheKey = "ecitizen:verify:{$nationalId}";

        return Cache::remember($cacheKey, 604800, function () use ($nationalId, $dateOfBirth) {
            $response = Http::timeout(10)
                ->retry(3, 1000)
                ->withToken(config('services.ecitizen.api_key'))
                ->post(config('services.ecitizen.base_url') . '/verify-id', [
                    'national_id' => $nationalId,
                    'date_of_birth' => $dateOfBirth,
                ]);

            if ($response->failed()) {
                // Log failure for manual review
                logger()->error('eCitizen verification failed', [
                    'national_id' => $nationalId,
                    'status' => $response->status(),
                ]);

                return [
                    'verified' => false,
                    'requires_manual_review' => true,
                ];
            }

            return [
                'verified' => $response->json('verified'),
                'full_name' => $response->json('full_name'),
                'county' => $response->json('county'),
            ];
        });
    }
}
```

**Configuration:**

```php
// config/services.php
'ecitizen' => [
    'base_url' => env('ECITIZEN_BASE_URL', 'https://api.ecitizen.go.ke'),
    'api_key' => env('ECITIZEN_API_KEY'),
    'timeout' => 10, // seconds
    'retry_attempts' => 3,
    'cache_ttl' => 604800, // 7 days
],
```

### 4.3 SMS Gateway Integration (Twilio/Africa's Talking)

**Multi-Provider Strategy:**

```php
// app/Notifications/Channels/SmsChannel.php
namespace App\Notifications\Channels;

use App\Services\Sms\SmsProviderManager;
use Illuminate\Notifications\Notification;

class SmsChannel
{
    public function __construct(private SmsProviderManager $manager) {}

    public function send(object $notifiable, Notification $notification): void
    {
        $phoneNumber = $notifiable->routeNotificationForSms();
        $message = $notification->toSms($notifiable);

        // Try primary provider (Twilio), fallback to secondary (Africa's Talking)
        try {
            $this->manager->provider('twilio')->send($phoneNumber, $message);
        } catch (\Exception $e) {
            logger()->warning('Primary SMS provider failed, using fallback', [
                'provider' => 'twilio',
                'error' => $e->getMessage(),
            ]);

            $this->manager->provider('africastalking')->send($phoneNumber, $message);
        }
    }
}
```

**Provider Manager:**

```php
// app/Services/Sms/SmsProviderManager.php
namespace App\Services\Sms;

class SmsProviderManager
{
    private array $providers = [];

    public function __construct()
    {
        $this->providers['twilio'] = new TwilioProvider(
            config('services.twilio.sid'),
            config('services.twilio.token'),
            config('services.twilio.from')
        );

        $this->providers['africastalking'] = new AfricasTalkingProvider(
            config('services.africastalking.username'),
            config('services.africastalking.api_key'),
            config('services.africastalking.from')
        );
    }

    public function provider(string $name): SmsProviderInterface
    {
        return $this->providers[$name] ?? throw new \InvalidArgumentException("Unknown SMS provider: {$name}");
    }
}
```

### 4.4 AI/ML Service Integration

**Microservice Architecture for AI Processing:**

```
┌────────────────────────────────────────┐
│    Laravel Application (Main)          │
│  ┌──────────────────────────────────┐ │
│  │  1. Bill PDF Upload              │ │
│  │  2. Queue AI Processing Job      │ │
│  └──────────┬───────────────────────┘ │
└─────────────┼──────────────────────────┘
              │ Job Queue (Redis/SQS)
┌─────────────▼──────────────────────────┐
│    AI Microservice (Python FastAPI)   │
│  ┌──────────────────────────────────┐ │
│  │  Tasks:                          │ │
│  │  - PDF Text Extraction           │ │
│  │  - Summary Generation (LLM)     │ │
│  │  - Clause Parsing (NLP)         │ │
│  │  - Sentiment Analysis           │ │
│  │  - Topic Clustering (BERTopic)  │ │
│  └──────────────────────────────────┘ │
│  Models:                               │
│  - BART/T5 (Summarization)            │
│  - BERT (Sentiment)                   │
│  - BERTopic (Clustering)              │
└─────────────┬──────────────────────────┘
              │ Results (Webhook/Callback)
┌─────────────▼──────────────────────────┐
│    Laravel Application (Store)         │
│  ┌──────────────────────────────────┐ │
│  │  Save Results:                   │ │
│  │  - bill_summaries table          │ │
│  │  - bill_clauses table            │ │
│  │  - submission metadata (JSON)    │ │
│  └──────────────────────────────────┘ │
└────────────────────────────────────────┘
```

**API Contract:**

```python
# AI Microservice (FastAPI)
from fastapi import FastAPI, File, UploadFile
from pydantic import BaseModel

app = FastAPI()

class BillSummaryRequest(BaseModel):
    bill_id: int
    pdf_url: str
    language: str = "en"  # en, sw

class BillSummaryResponse(BaseModel):
    summary: str
    key_clauses: list[dict]
    estimated_impact_areas: list[str]

@app.post("/api/v1/summarize-bill")
async def summarize_bill(request: BillSummaryRequest) -> BillSummaryResponse:
    # 1. Download PDF from URL
    # 2. Extract text using PyPDF2/pdfplumber
    # 3. Generate summary using BART/T5
    # 4. Identify key clauses using NLP
    # 5. Classify impact areas
    return BillSummaryResponse(...)

class SubmissionAnalysisRequest(BaseModel):
    bill_id: int
    submissions: list[dict]

class SubmissionAnalysisResponse(BaseModel):
    topics: list[dict]  # [{topic: str, count: int, sentiment: float}]
    sentiment_summary: dict  # {support: 45%, oppose: 30%, neutral: 25%}
    key_arguments: list[str]

@app.post("/api/v1/analyze-submissions")
async def analyze_submissions(request: SubmissionAnalysisRequest) -> SubmissionAnalysisResponse:
    # 1. Topic clustering (BERTopic)
    # 2. Sentiment analysis per topic
    # 3. Extract key arguments (extractive summarization)
    return SubmissionAnalysisResponse(...)
```

**Laravel Integration:**

```php
// app/Services/Ai/AiProcessingService.php
namespace App\Services\Ai;

use Illuminate\Support\Facades\Http;

class AiProcessingService
{
    private string $baseUrl;

    public function __construct()
    {
        $this->baseUrl = config('services.ai.base_url');
    }

    public function summarizeBill(int $billId, string $pdfUrl, string $language = 'en'): array
    {
        $response = Http::timeout(120) // AI processing takes time
            ->retry(2, 5000)
            ->post("{$this->baseUrl}/api/v1/summarize-bill", [
                'bill_id' => $billId,
                'pdf_url' => $pdfUrl,
                'language' => $language,
            ]);

        $response->throw();

        return $response->json();
    }

    public function analyzeSubmissions(int $billId, array $submissions): array
    {
        $response = Http::timeout(60)
            ->post("{$this->baseUrl}/api/v1/analyze-submissions", [
                'bill_id' => $billId,
                'submissions' => $submissions,
            ]);

        $response->throw();

        return $response->json();
    }
}
```

**Queue Job:**

```php
// app/Jobs/ProcessBillSummary.php
namespace App\Jobs;

use App\Models\Bill;
use App\Models\BillSummary;
use App\Services\Ai\AiProcessingService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;

class ProcessBillSummary implements ShouldQueue
{
    use Queueable;

    public int $timeout = 300; // 5 minutes
    public int $tries = 2;

    public function __construct(private readonly Bill $bill) {
        $this->onQueue('analytics');
    }

    public function handle(AiProcessingService $aiService): void
    {
        $pdfUrl = Storage::url($this->bill->pdf_path);

        $result = $aiService->summarizeBill(
            $this->bill->id,
            $pdfUrl,
            'en' // TODO: Support Kiswahili
        );

        // Save summary
        BillSummary::updateOrCreate(
            ['bill_id' => $this->bill->id],
            [
                'summary' => $result['summary'],
                'key_clauses' => $result['key_clauses'],
                'impact_areas' => $result['estimated_impact_areas'],
            ]
        );
    }
}
```

### 4.5 File Storage Architecture

**Object Storage Strategy (S3-Compatible):**

```yaml
Storage Buckets:
  bills-pdfs:
    Purpose: Original bill PDFs
    Access: Public read (authenticated), private write
    Lifecycle:
      - Retain indefinitely (legal requirement)
      - Archive to Glacier after 2 years (reduced access)
    Versioning: Enabled (track amendments)
    Replication: Cross-region

  user-uploads:
    Purpose: Citizen attachments (future feature)
    Access: Private
    Lifecycle:
      - Retain 90 days after bill closes
      - Soft delete (30-day recovery)
    Encryption: Server-side (AES-256)

  generated-reports:
    Purpose: PDF reports for committees
    Access: Private (role-based)
    Lifecycle:
      - Retain 5 years
      - Archive to Glacier after 1 year
    Encryption: Server-side (AES-256)
```

**Laravel Filesystem Configuration:**

```php
// config/filesystems.php
'disks' => [
    's3-bills' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BILLS_BUCKET'),
        'url' => env('AWS_BILLS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'visibility' => 'public',
    ],

    's3-reports' => [
        'driver' => 's3',
        'bucket' => env('AWS_REPORTS_BUCKET'),
        'visibility' => 'private',
    ],
],
```

**CDN Integration for Bill PDFs:**

```php
// Generate signed URL for secure PDF access
$url = Storage::disk('s3-bills')->temporaryUrl(
    'bills/2025/bill-123.pdf',
    now()->addHours(1) // URL expires in 1 hour
);
```

---

## 5. Security Architecture

### 5.1 Authentication & Authorization

**Authentication Flow:**

```
1. Citizen Registration:
   ┌──────────────────────────────────────┐
   │  1. Submit National ID + Phone       │
   │  2. eCitizen Verification (API)      │
   │  3. Generate OTP (6-digit)           │
   │  4. Send SMS (Twilio)                │
   │  5. Verify OTP (5-minute expiry)     │
   │  6. Create User Account              │
   │  7. Issue Session Token              │
   └──────────────────────────────────────┘

2. Legislator/Clerk Invitation:
   ┌──────────────────────────────────────┐
   │  1. Clerk sends invitation (email)   │
   │  2. Invitation token (UUID, 7-day)   │
   │  3. Legislator accepts invitation    │
   │  4. Set password (bcrypt, rounds=12) │
   │  5. 2FA setup (optional, TOTP)       │
   │  6. Issue Session Token              │
   └──────────────────────────────────────┘

3. Session Management:
   - Session Driver: Redis (distributed sessions)
   - Session Lifetime: 2 hours (configurable)
   - Idle Timeout: 30 minutes
   - Concurrent Sessions: Allowed (track devices)
   - Session Hijacking Protection: IP + User-Agent validation
```

**Authorization Model:**

```php
// Role-Based Access Control (RBAC)
enum UserRole: string {
    case Citizen = 'citizen';
    case Clerk = 'clerk';
    case Legislator = 'mp'; // Member of Parliament
    case Senator = 'senator';
    case Admin = 'admin';
}

// Policy-Based Authorization
class BillPolicy {
    public function create(User $user): bool {
        return $user->isClerk() || $user->isAdmin();
    }

    public function update(User $user, Bill $bill): bool {
        return $user->isClerk() || $user->isAdmin();
    }

    public function delete(User $user, Bill $bill): bool {
        return $user->isAdmin();
    }
}

class SubmissionPolicy {
    public function create(User $user): bool {
        return $user->isCitizen() && $user->is_verified;
    }

    public function view(User $user, Submission $submission): bool {
        // Own submissions or authorized roles
        return $submission->user_id === $user->id
            || $user->isClerk()
            || $user->isLegislator()
            || $user->isAdmin();
    }

    public function review(User $user, Submission $submission): bool {
        return $user->isClerk() || $user->isAdmin();
    }
}
```

### 5.2 Data Encryption

**Encryption Strategy:**

```yaml
Data at Rest:
  Database:
    - AWS RDS Encryption: AES-256
    - Tablespace Encryption: Enabled
    - Backup Encryption: Enabled
    - Sensitive Columns (additional):
      - national_id: Laravel encrypted cast
      - phone: Laravel encrypted cast (for analytics: hashed)

  Object Storage (S3):
    - Server-Side Encryption: AES-256
    - Client-Side Encryption: For sensitive reports
    - KMS Integration: AWS KMS or HashiCorp Vault

  Application Secrets:
    - Kubernetes Secrets (base64 + RBAC)
    - Sealed Secrets (for GitOps, encrypted at rest)
    - External Secrets Operator (AWS Secrets Manager)

Data in Transit:
  - HTTPS/TLS 1.3 (mandatory)
  - Database Connections: SSL/TLS enforced
  - Internal Services: mTLS (mutual TLS)
  - API Integrations: TLS + API Key/OAuth
```

**Laravel Encrypted Casts:**

```php
// app/Models/User.php
protected function casts(): array
{
    return [
        'national_id' => 'encrypted', // Laravel 10+ encrypted cast
        'phone' => 'encrypted',
        // Other casts...
    ];
}

// For analytics (need hashed, not encrypted):
public function getPhoneHashAttribute(): string
{
    return hash('sha256', $this->phone . config('app.key'));
}
```

### 5.3 Rate Limiting & DDoS Protection

**Multi-Layer Rate Limiting:**

```
Layer 1: CloudFlare (Edge)
├─ Global Rate Limit: 10,000 req/sec
├─ Per-IP Rate Limit: 100 req/min
├─ Bot Protection: Challenge on suspicious patterns
└─ DDoS Mitigation: Automatic with "I'm Under Attack" mode

Layer 2: Application Load Balancer
├─ Connection Rate Limit: 5,000 connections/min per IP
├─ Request Rate Limit: 200 req/min per IP
└─ Geo-Blocking: Block known malicious regions (configurable)

Layer 3: Laravel Application
├─ Global Throttle: 1,000 req/min per IP
├─ API Throttle: 60 req/min per user (authenticated)
├─ Sensitive Endpoints:
│  ├─ Registration: 5 attempts/hour per IP
│  ├─ OTP Send: 3 attempts/hour per phone
│  ├─ OTP Verify: 5 attempts/5min per phone
│  ├─ Login: 10 attempts/hour per IP
│  └─ Submission: 10 submissions/hour per user
└─ Burst Protection: Redis-backed rate limiter
```

**Laravel Rate Limiter Configuration:**

```php
// bootstrap/app.php
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;

RateLimiter::for('api', function (Request $request) {
    return $request->user()
        ? Limit::perMinute(60)->by($request->user()->id)
        : Limit::perMinute(20)->by($request->ip());
});

RateLimiter::for('otp-send', function (Request $request) {
    return Limit::perHour(3)->by($request->input('phone'));
});

RateLimiter::for('otp-verify', function (Request $request) {
    return Limit::perMinutes(5, 5)->by($request->input('phone'));
});

RateLimiter::for('submissions', function (Request $request) {
    return $request->user()
        ? Limit::perHour(10)->by($request->user()->id)
        : Limit::none(); // Must be authenticated
});
```

### 5.4 GDPR & Data Privacy Compliance

**Compliance Framework:**

```yaml
Legal Basis:
  - Kenya Data Protection Act (2019)
  - GDPR (for EU citizens, if applicable)
  - Constitutional Right to Privacy

Data Collection Principles:
  1. Lawfulness: Explicit consent for data processing
  2. Purpose Limitation: Only for public participation
  3. Data Minimization: Collect only necessary data
  4. Accuracy: eCitizen verification ensures accuracy
  5. Storage Limitation: Retention policy (5 years)
  6. Integrity & Confidentiality: Encryption + Access Control

User Rights:
  - Right to Access: Download all personal data (JSON export)
  - Right to Rectification: Update profile information
  - Right to Erasure: Delete account (with legal constraints)
  - Right to Data Portability: Export submissions
  - Right to Object: Opt-out of non-essential processing
  - Right to Automated Decision-Making: Human review available
```

**Implementation:**

```php
// Data Export (GDPR Article 15)
Route::post('/user/export-data', function (Request $request) {
    $user = $request->user();

    $data = [
        'profile' => $user->only(['name', 'email', 'phone', 'county']),
        'submissions' => $user->submissions()->with('bill')->get(),
        'engagements' => $user->engagements()->get(),
        'notifications' => $user->notifications()->get(),
    ];

    // Queue job to generate PDF + JSON
    dispatch(new ExportUserData($user, $data));

    return response()->json(['message' => 'Export will be emailed within 24 hours']);
});

// Data Deletion (GDPR Article 17)
Route::delete('/user/delete-account', function (Request $request) {
    $user = $request->user();

    // Legal constraint: Cannot delete if submissions are under review
    if ($user->submissions()->where('status', 'pending')->exists()) {
        return response()->json([
            'error' => 'Cannot delete account while submissions are under review'
        ], 400);
    }

    // Anonymize submissions (retain for legal/historical record)
    $user->submissions()->update([
        'user_id' => null,
        'submitter_name' => 'Anonymous',
        'submitter_email' => null,
        'submitter_phone' => null,
    ]);

    // Soft delete user (90-day recovery period)
    $user->delete();

    return response()->json(['message' => 'Account deleted. Data will be permanently removed in 90 days.']);
});
```

**Data Retention Policy:**

```yaml
Retention Periods:
  User Accounts:
    - Active: Indefinite (while participating)
    - Inactive (no login): 2 years
    - Deleted: 90-day soft delete, then purge

  Submissions:
    - Active Bills: Indefinite
    - Closed Bills: 5 years (legal requirement)
    - Anonymized: 10 years (research/historical)

  Logs:
    - Application Logs: 90 days
    - Audit Logs: 5 years
    - Security Logs: 7 years

  Backups:
    - Database Snapshots: 30 days
    - Archived Backups: 1 year
```

### 5.5 Security Auditing & Compliance

**Security Audit Schedule:**

```yaml
Continuous Monitoring:
  - Automated Security Scanning:
    - Dependency Vulnerability Scan (Snyk, daily)
    - Container Image Scan (Trivy, on build)
    - SAST (Static Application Security Testing, on PR)
    - DAST (Dynamic Application Security Testing, weekly)

  - Intrusion Detection:
    - AWS GuardDuty (or equivalent)
    - Kubernetes Network Policies (anomaly detection)
    - File Integrity Monitoring (AIDE, OSSEC)

Periodic Audits:
  - Penetration Testing: Quarterly (external firm)
  - Code Security Review: Every major release
  - Compliance Audit: Annual (Data Protection Commissioner)
  - Access Control Review: Quarterly

Incident Response:
  - Security Incident Response Plan (SIRP): Documented
  - Incident Response Team: On-call rotation
  - Post-Incident Review: Within 7 days
  - Public Disclosure: As required by law (72 hours for breaches)
```

**Security Best Practices Checklist:**

```yaml
Application Security:
  ✓ Input Validation: Laravel Form Requests
  ✓ Output Encoding: Blade templating (auto-escape)
  ✓ SQL Injection Prevention: Eloquent ORM (parameterized queries)
  ✓ XSS Prevention: Content Security Policy (CSP) headers
  ✓ CSRF Protection: Laravel middleware (enabled by default)
  ✓ Authentication: Secure password hashing (bcrypt, rounds=12)
  ✓ Session Security: HttpOnly, Secure, SameSite cookies
  ✓ API Security: Rate limiting, JWT tokens (if API)

Infrastructure Security:
  ✓ Network Segmentation: Private subnets for DB, public for web
  ✓ Firewall Rules: Security Groups (least privilege)
  ✓ Bastion Host: SSH access only via jump server
  ✓ Secret Management: Kubernetes Secrets + External Secrets
  ✓ TLS Everywhere: HTTPS, mTLS for internal services
  ✓ Regular Updates: Automated patching (Kubernetes node pools)

Operational Security:
  ✓ Access Control: RBAC for Kubernetes, IAM for cloud
  ✓ Audit Logging: All privileged actions logged
  ✓ MFA Enforcement: For admin and clerk accounts
  ✓ Code Review: Mandatory for all PRs
  ✓ Secrets Rotation: Quarterly for API keys, monthly for passwords
```

---

## 6. Reliability & Availability

### 6.1 High Availability Architecture

**Target SLA: 99.9% Uptime (43 minutes downtime/month)**

**HA Strategy:**

```
Geographic Distribution:
┌───────────────────────────────────────────┐
│         Primary Region (us-east-1)        │
│  ┌────────────────────────────────────┐  │
│  │  Availability Zone A               │  │
│  │  - App Pods: 3                     │  │
│  │  - Database: Primary               │  │
│  └────────────────────────────────────┘  │
│  ┌────────────────────────────────────┐  │
│  │  Availability Zone B               │  │
│  │  - App Pods: 2                     │  │
│  │  - Database: Standby (sync)        │  │
│  │  - Redis: Replica                  │  │
│  └────────────────────────────────────┘  │
│  ┌────────────────────────────────────┐  │
│  │  Availability Zone C               │  │
│  │  - App Pods: 2 (optional)          │  │
│  │  - Database: Read Replica          │  │
│  └────────────────────────────────────┘  │
└───────────────────────────────────────────┘

┌───────────────────────────────────────────┐
│      Disaster Recovery Region (eu-west-1) │
│  - Standby Database (async replication)   │
│  - Minimal App Pods (1-2, scaled on DR)   │
│  - S3 Cross-Region Replication            │
└───────────────────────────────────────────┘

DNS Failover (Route53):
- Health Checks: /up endpoint every 30 seconds
- Automatic Failover: 90 seconds
- TTL: 60 seconds (fast propagation)
```

### 6.2 Failover & Redundancy

**Database Failover:**

```yaml
RDS Multi-AZ Deployment:
  Primary: AZ-A (write)
  Standby: AZ-B (synchronous replication)
  Failover:
    - Automatic: 30-60 seconds
    - Trigger: Primary failure, network partition
    - DNS Update: Automatic (RDS endpoint)
    - Application Impact: Brief connection errors (retry logic)

Read Replica Failover:
  Primary Read: AZ-C
  Secondary Reads: AZ-A, AZ-B
  Failover: Application-level (connection pool)
  Promotion: Manual (disaster recovery)
```

**Application Failover:**

```yaml
Kubernetes Pod Anti-Affinity:
  - Spread pods across AZs
  - No single point of failure
  - Automatic pod rescheduling on node failure

Pod Disruption Budget:
  minAvailable: 50% (at least 3 pods running)
  Purpose: Prevent simultaneous pod evictions during:
    - Node maintenance
    - Cluster upgrades
    - Auto-scaling events

Liveness & Readiness Probes:
  Liveness: /up (restart if unhealthy)
  Readiness: /up (remove from load balancer if not ready)
  Frequency: Every 10 seconds
  Failure Threshold: 3 consecutive failures
```

### 6.3 Session Management

**Distributed Session Strategy:**

```php
// config/session.php
'driver' => env('SESSION_DRIVER', 'redis'),
'connection' => 'session', // Separate Redis DB

// Session Configuration
'lifetime' => 120, // 2 hours
'expire_on_close' => false,
'encrypt' => true,
'cookie' => 'participate_session',
'domain' => env('SESSION_DOMAIN'),
'same_site' => 'lax',
'secure' => true, // HTTPS only
'http_only' => true, // No JS access

// Sticky Sessions: Enabled at ALB level
// - Ensures user hits same pod during session
// - Session state in Redis (shared across pods)
// - No session loss on pod restart
```

**Session Tracking:**

```php
// app/Models/UserSession.php
class UserSession extends Model {
    protected $fillable = [
        'user_id',
        'session_id',
        'ip_address',
        'user_agent',
        'last_activity',
        'expires_at',
    ];
}

// Track active sessions (for security)
// Middleware: TrackUserSession
class TrackUserSession {
    public function handle(Request $request, Closure $next) {
        if ($user = $request->user()) {
            UserSession::updateOrCreate(
                [
                    'user_id' => $user->id,
                    'session_id' => session()->getId(),
                ],
                [
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'last_activity' => now(),
                    'expires_at' => now()->addMinutes(config('session.lifetime')),
                ]
            );
        }

        return $next($request);
    }
}
```

---

## 7. Implementation Roadmap

### 7.1 Phase-Based Rollout

**Phase 1: Infrastructure Foundation (Month 1-2)**

**Objectives:**
- Migrate from SQLite to managed PostgreSQL
- Implement Redis caching layer
- Set up CI/CD pipeline
- Deploy to Kubernetes (staging environment)

**Deliverables:**
```
✓ PostgreSQL Migration:
  - Schema migration (Laravel migrations)
  - Data migration script (SQLite → PostgreSQL)
  - Connection pooling (PgBouncer)
  - Backup configuration

✓ Redis Setup:
  - Cache store configuration
  - Session store configuration
  - Queue backend (Redis-backed)

✓ Kubernetes Deployment:
  - Staging cluster (3 nodes)
  - Application deployment manifest
  - Ingress controller (NGINX)
  - Secrets management

✓ CI/CD Pipeline:
  - GitHub Actions workflow
  - Automated testing (Pest)
  - Docker image build
  - Automated deployment to staging

✓ Monitoring Baseline:
  - Prometheus + Grafana
  - Basic dashboards (traffic, errors, latency)
  - Alerting (Slack integration)
```

**Success Criteria:**
- Staging environment fully operational
- All tests passing in CI/CD
- Staging performance benchmarks established

---

**Phase 2: Production Deployment & HA (Month 3-4)**

**Objectives:**
- Deploy to production Kubernetes cluster
- Implement high availability
- Set up production monitoring
- Configure CDN and DNS

**Deliverables:**
```
✓ Production Kubernetes:
  - Production cluster (5 nodes, multi-AZ)
  - Production database (RDS Multi-AZ)
  - Redis cluster (3 masters, 3 replicas)
  - S3 buckets for file storage

✓ High Availability:
  - Multi-AZ pod distribution
  - Pod Disruption Budget
  - Database read replicas (2x)
  - Load balancer health checks

✓ CDN Configuration:
  - CloudFlare or AWS CloudFront
  - Static asset caching
  - PDF caching (S3 origin)

✓ DNS & SSL:
  - Route53 (or equivalent)
  - SSL certificates (ACM/Let's Encrypt)
  - Health-check based failover

✓ Production Monitoring:
  - Full Prometheus + Grafana setup
  - Loki for log aggregation
  - PagerDuty for alerting
  - Status page (statuspage.io)

✓ Backup & DR:
  - Automated database backups
  - Cross-region backup replication
  - Disaster recovery runbook
```

**Success Criteria:**
- Production environment stable for 72 hours
- 99.9% uptime achieved in first month
- Zero data loss during DR test

---

**Phase 3: Integration & Optimization (Month 5-6)**

**Objectives:**
- Integrate eCitizen ID verification
- Integrate SMS gateway (Twilio)
- Optimize database queries
- Implement advanced caching

**Deliverables:**
```
✓ eCitizen Integration:
  - API integration (REST)
  - Caching strategy (7-day TTL)
  - Fallback to manual verification
  - Error handling and retry logic

✓ SMS Gateway Integration:
  - Twilio integration (primary)
  - Africa's Talking (fallback)
  - OTP delivery (<10 seconds)
  - SMS notifications (bills, submissions)

✓ Performance Optimization:
  - Database query optimization (N+1 fixes)
  - Eager loading strategies
  - Advanced caching policies
  - API response time <500ms (P95)

✓ Multi-Channel Support:
  - USSD gateway integration (Africa's Talking)
  - IVR system integration (Twilio)
  - SMS submission parsing

✓ Security Hardening:
  - Rate limiting (multi-layer)
  - DDoS protection (CloudFlare)
  - Security audit (external firm)
  - Penetration testing
```

**Success Criteria:**
- eCitizen verification success rate >95%
- SMS delivery success rate >98%
- API response time P95 <500ms
- Security audit: No critical vulnerabilities

---

**Phase 4: AI/ML Integration (Month 7-9)**

**Objectives:**
- Deploy AI microservice
- Implement bill summarization
- Implement submission analysis
- Generate legislator reports

**Deliverables:**
```
✓ AI Microservice Deployment:
  - FastAPI service (Python)
  - Kubernetes deployment (separate namespace)
  - Model deployment (BART, BERT, BERTopic)
  - GPU nodes (optional, for faster inference)

✓ Bill Summarization:
  - PDF text extraction
  - Summary generation (English & Kiswahili)
  - Key clause identification
  - Impact area classification

✓ Submission Analysis:
  - Topic clustering (BERTopic)
  - Sentiment analysis per topic
  - Key argument extraction
  - Geographic sentiment mapping

✓ Legislator Reports:
  - Automated constituency reports
  - Sentiment dashboards
  - PDF report generation
  - Email delivery

✓ AI Quality Assurance:
  - Human-in-the-loop review
  - Model accuracy monitoring
  - Bias detection and mitigation
```

**Success Criteria:**
- AI summarization accuracy >80% (human validation)
- Submission analysis processing <5 minutes for 10,000 submissions
- Legislator reports generated within 24 hours of bill closure

---

**Phase 5: Scale & Optimize (Month 10-12)**

**Objectives:**
- Scale to national launch
- Optimize for 10,000+ concurrent users
- Implement advanced analytics
- Continuous improvement

**Deliverables:**
```
✓ National Scale Readiness:
  - Load testing (10,000 concurrent users)
  - Auto-scaling policies (HPA, VPA, Cluster Autoscaler)
  - Database sharding (if needed)
  - Multi-region deployment (DR region active)

✓ Advanced Analytics:
  - Real-time participation dashboards
  - Geographic participation heatmaps
  - Bill engagement trends
  - Predictive analytics (participation forecasting)

✓ Continuous Optimization:
  - Performance monitoring and tuning
  - Cost optimization (reserved instances, spot instances)
  - Feature flags (LaunchDarkly or custom)
  - A/B testing framework

✓ Compliance & Governance:
  - Data Protection Impact Assessment (DPIA)
  - Annual compliance audit
  - Privacy policy updates
  - Terms of service updates

✓ User Education:
  - User guides (video tutorials)
  - FAQ section
  - Helpdesk integration (Zendesk)
  - Community forums
```

**Success Criteria:**
- Handle 10,000 concurrent users with <1 second response time
- 99.95% uptime achieved
- Cost per user <$0.50/month
- User satisfaction score >80%

---

### 7.2 Risk Mitigation

**Critical Risks & Mitigation Strategies:**

| Risk | Impact | Probability | Mitigation |
|------|--------|-------------|------------|
| **eCitizen API Downtime** | High | Medium | Fallback to manual verification; cache results for 7 days |
| **SMS Gateway Failure** | High | Low | Multi-provider strategy (Twilio + Africa's Talking) |
| **Database Failure** | Critical | Low | Multi-AZ deployment; automated failover; cross-region backup |
| **DDoS Attack** | High | Medium | CloudFlare protection; rate limiting; auto-scaling |
| **Data Breach** | Critical | Low | Encryption (at rest & in transit); audit logging; penetration testing |
| **AI Model Bias** | Medium | Medium | Human-in-the-loop review; regular bias audits; diverse training data |
| **Peak Load Failure** | High | Medium | Load testing; auto-scaling; queue-based processing |
| **Vendor Lock-In** | Medium | High | Kubernetes (portable); S3-compatible API; open-source tools |

---

### 7.3 Cost Estimation

**Monthly Infrastructure Costs (Year 1):**

```yaml
Compute (Kubernetes):
  - Application Nodes (5x t3.xlarge): $750/month
  - Background Job Nodes (2x c6i.2xlarge): $500/month
  - Monitoring Nodes (2x t3.medium): $100/month
  - Total: $1,350/month

Database:
  - PostgreSQL RDS (db.r6g.xlarge, Multi-AZ): $600/month
  - Read Replicas (2x db.r6g.large): $400/month
  - Backup Storage (500GB): $50/month
  - Total: $1,050/month

Cache & Queue:
  - Redis Cluster (3x cache.r6g.large): $450/month
  - Redis Session (1x cache.r6g.medium): $100/month
  - Total: $550/month

Storage:
  - S3 Storage (1TB): $25/month
  - S3 Requests (1M GET, 100K PUT): $10/month
  - S3 Data Transfer (500GB): $45/month
  - Total: $80/month

CDN & Networking:
  - CloudFlare Pro Plan: $20/month
  - AWS Data Transfer (1TB): $90/month
  - Load Balancer (ALB): $25/month
  - Total: $135/month

Third-Party Services:
  - Twilio (10,000 SMS/month): $100/month
  - SendGrid (100,000 emails/month): $20/month
  - PagerDuty Team Plan: $50/month
  - Monitoring (Grafana Cloud): $50/month
  - Total: $220/month

AI/ML (Optional Phase 4+):
  - GPU Nodes (2x g4dn.xlarge): $600/month
  - OpenAI API (if used): $200/month
  - Total: $800/month (only when active)

Grand Total (Phase 1-3): $3,385/month
Grand Total (Phase 4+): $4,185/month

Annual Cost: ~$41,000 - $50,000/year
```

**Scaling Projections:**

```
Year 1 (100K users): $50,000/year
Year 3 (500K users): $120,000/year (2.4x increase)
Year 5 (2M users): $300,000/year (6x increase)

Cost per User:
- Year 1: $0.50/user/year
- Year 3: $0.24/user/year (economies of scale)
- Year 5: $0.15/user/year
```

---

## 8. Conclusion & Recommendations

### 8.1 Executive Summary

The Public Participation Platform has a solid foundation with Laravel 12 and modern web technologies. To achieve national scale and meet the 99.9% uptime SLA, the following critical path items are recommended:

**Immediate Priorities (Months 1-2):**
1. Migrate from SQLite to managed PostgreSQL (AWS RDS Multi-AZ)
2. Implement Redis caching layer (session + cache + queue)
3. Deploy to Kubernetes (staging first, then production)
4. Set up CI/CD pipeline (GitHub Actions + ArgoCD)

**High Impact Enhancements (Months 3-6):**
1. Integrate eCitizen ID verification with fallback strategies
2. Integrate SMS gateway (Twilio primary, Africa's Talking secondary)
3. Implement multi-layer rate limiting and DDoS protection
4. Set up comprehensive monitoring (Prometheus + Grafana + Loki)

**Long-Term Strategic (Months 7-12):**
1. Deploy AI microservice for bill summarization and submission analysis
2. Implement multi-region deployment for disaster recovery
3. Optimize for 10,000+ concurrent users with auto-scaling
4. Conduct security audits and penetration testing

### 8.2 Technology Recommendations

**Core Infrastructure:**
- **Cloud Provider:** AWS (recommended), Azure, or Google Cloud
- **Kubernetes:** Managed service (EKS, AKS, GKE)
- **Database:** PostgreSQL 16+ (RDS Multi-AZ or equivalent)
- **Cache/Queue:** Redis Cluster (ElastiCache or Redis Cloud)
- **Object Storage:** S3 (or compatible)
- **CDN:** CloudFlare (DDoS protection) or AWS CloudFront

**Monitoring & Observability:**
- **Metrics:** Prometheus + Grafana
- **Logging:** Loki + Promtail (or ELK stack)
- **Tracing:** Jaeger (or AWS X-Ray)
- **Alerting:** PagerDuty or Opsgenie
- **Status Page:** statuspage.io

**Third-Party Services:**
- **SMS:** Twilio (primary), Africa's Talking (fallback)
- **Email:** SendGrid or AWS SES
- **ID Verification:** eCitizen API
- **AI/ML:** OpenAI API (for prototyping), Self-hosted models (production)

### 8.3 Success Metrics

**Technical Metrics:**
- Uptime: 99.9% (43 minutes downtime/month)
- Response Time: P95 <500ms, P99 <1s
- Database Query Time: P95 <200ms
- Cache Hit Rate: >85%
- Queue Processing: <5 seconds for notifications
- Error Rate: <0.5%

**Business Metrics:**
- User Registrations: 100,000+ in Year 1
- Submissions per Bill: Average 5,000+
- Geographic Coverage: All 47 counties participating
- Multi-Channel Usage: 40% non-web submissions
- Legislator Engagement: 80%+ active legislators

**Operational Metrics:**
- Deployment Frequency: Daily (staging), Weekly (production)
- Mean Time to Recovery (MTTR): <1 hour
- Change Failure Rate: <5%
- Security Incidents: Zero critical breaches

### 8.4 Final Recommendations

1. **Prioritize Database Migration:** SQLite is a blocker for scale. Migrate to PostgreSQL in Month 1.

2. **Invest in Monitoring Early:** Observability is critical for diagnosing issues at scale. Set up Prometheus, Grafana, and Loki in parallel with infrastructure deployment.

3. **Build for Failure:** Assume services will fail. Implement retries, circuit breakers, and graceful degradation throughout the system.

4. **Security First:** Implement rate limiting, DDoS protection, and encryption from day one. Conduct regular security audits.

5. **Test at Scale:** Load test the system to 2-3x expected peak load before national launch. Identify bottlenecks early.

6. **Human-in-the-Loop:** AI-powered features should have human review mechanisms, especially for critical analysis that informs legislation.

7. **Phased Rollout:** Launch with a pilot group (3-5 committees) before national rollout. Collect feedback and iterate.

8. **Community Engagement:** The platform's success depends on user adoption. Invest in user education, helpdesk support, and community building.

---

**Document End**

---

**Appendices:**
- Appendix A: Kubernetes Deployment Manifests (Full)
- Appendix B: Database Schema Diagrams
- Appendix C: API Documentation (Integration Contracts)
- Appendix D: Security Audit Checklist
- Appendix E: Disaster Recovery Runbook
- Appendix F: Cost Optimization Strategies

**Document Control:**
- Version: 1.0
- Last Updated: October 7, 2025
- Next Review: January 7, 2026
- Approved By: [System Architect, CTO, Head of DevOps]
