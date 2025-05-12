# Huduma Ya Raia - Architecture Specifications

**Document Date:** 2025-10-06
**Architect:** SuperClaude System Architecture Team
**Target:** Critical MVP Features
**Status:** Design Phase

---

## Table of Contents
1. [Clause-by-Clause Feedback System](#1-clause-by-clause-feedback-system)
2. [AI Bill Summarization Service](#2-ai-bill-summarization-service)
3. [Multi-Channel Submission Infrastructure](#3-multi-channel-submission-infrastructure)
4. [PDF Processing Pipeline](#4-pdf-processing-pipeline)
5. [Service Layer Architecture](#5-service-layer-architecture)
6. [Analytics & Reporting System](#6-analytics--reporting-system)

---

## 1. CLAUSE-BY-CLAUSE FEEDBACK SYSTEM

### 1.1 Overview
Enable citizens to provide targeted feedback on specific bill clauses rather than general commentary.

### 1.2 Database Schema

#### New Table: `bill_clauses`
```php
Schema::create('bill_clauses', function (Blueprint $table) {
    $table->id();
    $table->foreignId('bill_id')->constrained()->cascadeOnDelete();
    $table->string('clause_number'); // e.g., "2.1", "5.3.a"
    $table->string('clause_type'); // section, subsection, paragraph, subparagraph
    $table->foreignId('parent_clause_id')->nullable()->constrained('bill_clauses');
    $table->text('title')->nullable(); // e.g., "Definitions"
    $table->longText('content'); // The actual clause text
    $table->json('metadata')->nullable(); // page_number, etc.
    $table->integer('display_order')->default(0);
    $table->timestamps();

    $table->index(['bill_id', 'clause_number']);
    $table->index('display_order');
});
```

#### Modify Table: `submissions`
```php
Schema::table('submissions', function (Blueprint $table) {
    $table->foreignId('clause_id')->nullable()
          ->after('bill_id')
          ->constrained('bill_clauses')
          ->nullOnDelete();
    $table->enum('submission_scope', ['bill', 'clause'])
          ->default('bill')
          ->after('submission_type');
});
```

#### New Table: `clause_analytics`
```php
Schema::create('clause_analytics', function (Blueprint $table) {
    $table->id();
    $table->foreignId('clause_id')->constrained('bill_clauses')->cascadeOnDelete();
    $table->integer('submissions_count')->default(0);
    $table->integer('support_count')->default(0);
    $table->integer('oppose_count')->default(0);
    $table->integer('neutral_count')->default(0);
    $table->json('sentiment_scores')->nullable(); // detailed sentiment breakdown
    $table->json('top_keywords')->nullable(); // most common words in feedback
    $table->timestamp('last_analyzed_at')->nullable();
    $table->timestamps();

    $table->unique('clause_id');
});
```

### 1.3 Models

#### `BillClause.php`
```php
class BillClause extends Model
{
    protected $fillable = [
        'bill_id', 'clause_number', 'clause_type', 'parent_clause_id',
        'title', 'content', 'metadata', 'display_order'
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    // Relationships
    public function bill(): BelongsTo
    {
        return $this->belongsTo(Bill::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(BillClause::class, 'parent_clause_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(BillClause::class, 'parent_clause_id')
                    ->orderBy('display_order');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class, 'clause_id');
    }

    public function analytics(): HasOne
    {
        return $this->hasOne(ClauseAnalytics::class);
    }

    // Scopes
    public function scopeTopLevel(Builder $query): Builder
    {
        return $query->whereNull('parent_clause_id');
    }

    public function scopeOrdered(Builder $query): Builder
    {
        return $query->orderBy('display_order');
    }

    // Methods
    public function getFullClauseNumber(): string
    {
        if ($this->parent) {
            return $this->parent->getFullClauseNumber() . '.' . $this->clause_number;
        }
        return $this->clause_number;
    }
}
```

### 1.4 Service: `BillClauseParsingService`

```php
namespace App\Services;

class BillClauseParsingService
{
    /**
     * Parse bill PDF and extract clauses
     */
    public function parseBillClauses(Bill $bill): Collection
    {
        $pdfText = $this->extractTextFromPdf($bill->pdf_path);

        $clauses = $this->identifyClauses($pdfText);

        $savedClauses = collect();

        DB::transaction(function () use ($bill, $clauses, &$savedClauses) {
            foreach ($clauses as $clauseData) {
                $savedClauses->push(
                    BillClause::create([
                        'bill_id' => $bill->id,
                        'clause_number' => $clauseData['number'],
                        'clause_type' => $clauseData['type'],
                        'parent_clause_id' => $clauseData['parent_id'] ?? null,
                        'title' => $clauseData['title'] ?? null,
                        'content' => $clauseData['content'],
                        'metadata' => $clauseData['metadata'] ?? [],
                        'display_order' => $clauseData['order'],
                    ])
                );
            }
        });

        return $savedClauses;
    }

    /**
     * Identify clause structure using regex patterns
     */
    protected function identifyClauses(string $text): array
    {
        $clauses = [];
        $order = 0;

        // Pattern for section headers: "Section 5. Title"
        preg_match_all('/Section\s+(\d+)\.?\s+([^\n]+)/', $text, $sections, PREG_OFFSET_CAPTURE);

        // Pattern for subsections: "(1) Content" or "5.1 Content"
        preg_match_all('/\((\d+)\)\s+([^\n]+)/', $text, $subsections, PREG_OFFSET_CAPTURE);

        foreach ($sections[0] as $index => $section) {
            $clauses[] = [
                'number' => $sections[1][$index][0],
                'type' => 'section',
                'title' => trim($sections[2][$index][0]),
                'content' => $this->extractSectionContent($text, $section[1]),
                'order' => $order++,
            ];
        }

        return $clauses;
    }

    protected function extractTextFromPdf(string $pdfPath): string
    {
        // Implementation using spatie/pdf-to-text or smalot/pdfparser
        $parser = new \Smalot\PdfParser\Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $pdfPath));
        return $pdf->getText();
    }
}
```

### 1.5 API Endpoints

```php
// routes/api.php
Route::prefix('bills/{bill}/clauses')
    ->name('api.v1.bills.clauses.')
    ->group(function () {
        Route::get('/', [ClauseController::class, 'index'])->name('index');
        Route::get('{clause}', [ClauseController::class, 'show'])->name('show');

        Route::middleware('auth:sanctum', 'role:clerk,admin')->group(function () {
            Route::post('parse', [ClauseController::class, 'parse'])->name('parse');
            Route::post('/', [ClauseController::class, 'store'])->name('store');
            Route::patch('{clause}', [ClauseController::class, 'update'])->name('update');
            Route::delete('{clause}', [ClauseController::class, 'destroy'])->name('destroy');
        });
    });
```

### 1.6 Frontend Components (Vue)

```vue
<!-- BillClauseNavigator.vue -->
<template>
  <div class="clause-navigator">
    <div class="clause-sidebar">
      <ClauseTree :clauses="topLevelClauses" @select="selectClause" />
    </div>

    <div class="clause-content">
      <ClauseDetail
        :clause="selectedClause"
        :submissions="clauseSubmissions"
        @comment="openCommentDialog"
      />
    </div>

    <CommentDialog
      v-model="showCommentDialog"
      :clause="selectedClause"
      @submit="submitClauseComment"
    />
  </div>
</template>
```

---

## 2. AI BILL SUMMARIZATION SERVICE

### 2.1 Architecture Overview

**Pattern:** Service Layer + Job Queue
**AI Provider:** OpenAI GPT-4 (primary), Anthropic Claude (fallback)

### 2.2 Service: `BillSummarizationService`

```php
namespace App\Services\AI;

use OpenAI;

class BillSummarizationService
{
    protected OpenAI\Client $client;

    public function __construct()
    {
        $this->client = OpenAI::client(config('services.openai.key'));
    }

    /**
     * Generate comprehensive bill summary
     */
    public function generateSummary(Bill $bill): BillSummary
    {
        $billText = $this->extractBillText($bill);

        // Generate English summary
        $summaryEn = $this->generateSimplifiedSummary($billText, 'en');

        // Generate Kiswahili summary
        $summarySw = $this->generateSimplifiedSummary($billText, 'sw');

        // Extract key clauses
        $keyClauses = $this->extractKeyClauses($billText);

        // Generate audio
        $audioPathEn = $this->generateAudioSummary($summaryEn, 'en');
        $audioPathSw = $this->generateAudioSummary($summarySw, 'sw');

        return BillSummary::updateOrCreate(
            ['bill_id' => $bill->id],
            [
                'simplified_summary_en' => $summaryEn,
                'simplified_summary_sw' => $summarySw,
                'key_clauses' => $keyClauses,
                'audio_path_en' => $audioPathEn,
                'audio_path_sw' => $audioPathSw,
                'generation_method' => 'openai_gpt4',
                'generated_at' => now(),
            ]
        );
    }

    /**
     * Generate simplified summary using GPT-4
     */
    protected function generateSimplifiedSummary(string $billText, string $language): string
    {
        $prompt = $this->buildSummaryPrompt($billText, $language);

        $response = $this->client->chat()->create([
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                ['role' => 'system', 'content' => $this->getSystemPrompt($language)],
                ['role' => 'user', 'content' => $prompt],
            ],
            'max_tokens' => 1500,
            'temperature' => 0.3, // Lower temperature for factual accuracy
        ]);

        return $response->choices[0]->message->content;
    }

    protected function getSystemPrompt(string $language): string
    {
        if ($language === 'sw') {
            return <<<PROMPT
You are a Kenyan legal expert translator. Your task is to explain complex legal bills
in simple Kiswahili that any Kenyan citizen can understand. Use everyday language,
provide examples relevant to Kenyan life, and maintain cultural sensitivity.
Focus on how the bill affects ordinary citizens.
PROMPT;
        }

        return <<<PROMPT
You are a Kenyan legal expert. Your task is to explain complex legal bills in simple
English that any Kenyan citizen can understand (Explain Like I'm 5 style).
Avoid legal jargon, use everyday language, and provide examples relevant to Kenyan life.
Focus on how the bill affects ordinary citizens.
PROMPT;
    }

    protected function buildSummaryPrompt(string $billText, string $language): string
    {
        $instruction = $language === 'sw'
            ? 'Eleza mswada huu kwa lugha rahisi ya Kiswahili:'
            : 'Explain this bill in simple English:';

        return <<<PROMPT
{$instruction}

{$billText}

Please provide:
1. A 2-3 paragraph summary of what this bill does
2. Who will be affected and how
3. The main goals of this legislation
4. Any important deadlines or implementation dates

Keep the language simple and accessible.
PROMPT;
    }

    /**
     * Extract key clauses using AI
     */
    protected function extractKeyClauses(string $billText): array
    {
        $response = $this->client->chat()->create([
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                ['role' => 'system', 'content' => 'You are a legal analyst identifying the most important provisions in bills.'],
                ['role' => 'user', 'content' => <<<PROMPT
Identify the 5-7 most important clauses in this bill that citizens should know about.
For each clause, provide:
- Clause number and title
- Plain language explanation
- Why it matters to citizens

Bill text:
{$billText}

Return as JSON array with keys: clause_number, title, explanation, impact
PROMPT
                ],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        return json_decode($response->choices[0]->message->content, true)['key_clauses'];
    }

    /**
     * Generate audio using Google Cloud TTS
     */
    protected function generateAudioSummary(string $text, string $language): string
    {
        // Implementation using Google Cloud Text-to-Speech
        $client = new \Google\Cloud\TextToSpeech\V1\TextToSpeechClient([
            'credentials' => config('services.google.credentials_path'),
        ]);

        $voice = new \Google\Cloud\TextToSpeech\V1\VoiceSelectionParams([
            'language_code' => $language === 'sw' ? 'sw-KE' : 'en-KE',
            'name' => $language === 'sw' ? 'sw-KE-Standard-A' : 'en-KE-Standard-A',
        ]);

        $audioConfig = new \Google\Cloud\TextToSpeech\V1\AudioConfig([
            'audio_encoding' => \Google\Cloud\TextToSpeech\V1\AudioEncoding::MP3,
        ]);

        $response = $client->synthesizeSpeech($text, $voice, $audioConfig);

        $filename = 'summaries/' . Str::uuid() . '.mp3';
        Storage::disk('public')->put($filename, $response->getAudioContent());

        return $filename;
    }
}
```

### 2.3 Job: `GenerateBillSummaryJob`

```php
namespace App\Jobs;

class GenerateBillSummaryJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public Bill $bill
    ) {}

    public function handle(BillSummarizationService $service): void
    {
        try {
            $summary = $service->generateSummary($this->bill);

            // Notify clerks that summary is ready
            $this->bill->creator->notify(new BillSummaryGeneratedNotification($this->bill, $summary));

        } catch (\Exception $e) {
            Log::error('Bill summarization failed', [
                'bill_id' => $this->bill->id,
                'error' => $e->getMessage(),
            ]);

            $this->fail($e);
        }
    }

    public function retryUntil(): DateTime
    {
        return now()->addHours(2);
    }
}
```

### 2.4 Configuration

```php
// config/services.php
return [
    'openai' => [
        'key' => env('OPENAI_API_KEY'),
        'organization' => env('OPENAI_ORGANIZATION'),
    ],

    'google' => [
        'credentials_path' => env('GOOGLE_APPLICATION_CREDENTIALS'),
    ],
];
```

---

## 3. MULTI-CHANNEL SUBMISSION INFRASTRUCTURE

### 3.1 SMS Submission

#### Controller: `SmsWebhookController`

```php
namespace App\Http\Controllers\Api;

class SmsWebhookController extends Controller
{
    public function handle(Request $request, SmsSubmissionService $service)
    {
        $from = $request->input('From'); // Sender phone number
        $body = $request->input('Body'); // SMS content
        $messageId = $request->input('MessageSid');

        try {
            $result = $service->processIncomingSms($from, $body, $messageId);

            return response()->xml([
                'Response' => [
                    'Message' => $result['message'],
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('SMS processing failed', [
                'from' => $from,
                'body' => $body,
                'error' => $e->getMessage(),
            ]);

            return response()->xml([
                'Response' => [
                    'Message' => 'Samahani, kuna hitilafu. Tafadhali jaribu tena. / Sorry, there was an error. Please try again.',
                ],
            ]);
        }
    }
}
```

#### Service: `SmsSubmissionService`

```php
namespace App\Services;

class SmsSubmissionService
{
    /**
     * Process incoming SMS and create submission
     */
    public function processIncomingSms(string $phone, string $body, string $messageId): array
    {
        // Parse SMS format: "SUBMIT 123 This is my comment on the bill"
        if (! preg_match('/^SUBMIT\s+(\d+)\s+(.+)$/i', trim($body), $matches)) {
            return [
                'success' => false,
                'message' => 'Format: SUBMIT [BILL_NUMBER] [YOUR_COMMENT]',
            ];
        }

        $billNumber = $matches[1];
        $content = $matches[2];

        $bill = Bill::where('bill_number', 'LIKE', "%{$billNumber}%")
                    ->openForParticipation()
                    ->first();

        if (! $bill) {
            return [
                'success' => false,
                'message' => "Bill {$billNumber} not found or not open for comments.",
            ];
        }

        // Find or create user by phone
        $user = User::firstOrCreate(
            ['phone' => $this->normalizePhone($phone)],
            [
                'name' => 'SMS User ' . substr($phone, -4),
                'role' => UserRole::Citizen,
                'is_verified' => true,
            ]
        );

        // Create submission
        $submission = Submission::create([
            'bill_id' => $bill->id,
            'user_id' => $user->id,
            'submitter_phone' => $phone,
            'content' => $content,
            'channel' => 'sms',
            'language' => $this->detectLanguage($content),
            'status' => 'pending',
            'metadata' => [
                'message_id' => $messageId,
                'received_at' => now()->toIso8601String(),
            ],
        ]);

        return [
            'success' => true,
            'message' => "Thank you! Your tracking ID: {$submission->tracking_id}",
            'submission' => $submission,
        ];
    }

    protected function normalizePhone(string $phone): string
    {
        // Remove country code, spaces, and format
        return preg_replace('/[^0-9]/', '', $phone);
    }

    protected function detectLanguage(string $text): string
    {
        // Simple heuristic: check for Kiswahili words
        $swahiliWords = ['mswada', 'nataka', 'napinga', 'naomba', 'tafadhali'];

        foreach ($swahiliWords as $word) {
            if (stripos($text, $word) !== false) {
                return 'sw';
            }
        }

        return 'en';
    }
}
```

### 3.2 USSD Menu System

#### Controller: `UssdController`

```php
namespace App\Http\Controllers\Api;

class UssdController extends Controller
{
    public function handle(Request $request, UssdMenuService $service)
    {
        $sessionId = $request->input('sessionId');
        $phoneNumber = $request->input('phoneNumber');
        $text = $request->input('text'); // User input history

        $response = $service->processUssdRequest($sessionId, $phoneNumber, $text);

        return response($response['message'])
               ->header('Content-Type', 'text/plain');
    }
}
```

#### Service: `UssdMenuService`

```php
namespace App\Services;

class UssdMenuService
{
    /**
     * Process USSD session and return menu
     */
    public function processUssdRequest(string $sessionId, string $phone, string $text): array
    {
        $level = count(explode('*', $text));
        $userInput = explode('*', $text);

        // Main menu
        if ($text === '') {
            return $this->mainMenu();
        }

        // Bills list
        if ($level === 1 && $userInput[0] === '1') {
            return $this->billsList();
        }

        // Bill selection
        if ($level === 2 && $userInput[0] === '1') {
            $billIndex = (int) $userInput[1];
            return $this->billDetails($billIndex);
        }

        // Submit comment
        if ($level === 3 && $userInput[0] === '1' && $userInput[2] === '1') {
            return $this->submitCommentPrompt();
        }

        // Handle comment submission
        if ($level === 4 && $userInput[0] === '1') {
            $billIndex = (int) $userInput[1];
            $comment = $userInput[3];
            return $this->saveComment($phone, $billIndex, $comment);
        }

        return $this->invalidInput();
    }

    protected function mainMenu(): array
    {
        return [
            'continue' => true,
            'message' => "CON Welcome to Huduma Ya Raia\n1. View Open Bills\n2. Track Submission\n3. Help",
        ];
    }

    protected function billsList(): array
    {
        $bills = Bill::openForParticipation()
                     ->orderBy('participation_end_date')
                     ->limit(9)
                     ->get();

        $menu = "CON Select Bill:\n";
        foreach ($bills as $index => $bill) {
            $daysLeft = $bill->daysRemaining();
            $menu .= ($index + 1) . ". {$bill->title} ({$daysLeft} days left)\n";
        }
        $menu .= "0. Back";

        Cache::put("ussd_bills_{$sessionId}", $bills->pluck('id')->toArray(), now()->addMinutes(5));

        return [
            'continue' => true,
            'message' => $menu,
        ];
    }

    protected function saveComment(string $phone, int $billIndex, string $comment): array
    {
        $billIds = Cache::get("ussd_bills_{$sessionId}", []);
        $billId = $billIds[$billIndex - 1] ?? null;

        if (! $billId) {
            return ['continue' => false, 'message' => 'END Invalid bill selection.'];
        }

        $user = User::firstOrCreate(
            ['phone' => $this->normalizePhone($phone)],
            ['name' => 'USSD User ' . substr($phone, -4), 'role' => UserRole::Citizen]
        );

        $submission = Submission::create([
            'bill_id' => $billId,
            'user_id' => $user->id,
            'submitter_phone' => $phone,
            'content' => $comment,
            'channel' => 'ussd',
            'status' => 'pending',
        ]);

        return [
            'continue' => false,
            'message' => "END Thank you! Your tracking ID: {$submission->tracking_id}",
        ];
    }
}
```

---

## 4. PDF PROCESSING PIPELINE

### 4.1 Service: `PdfProcessingService`

```php
namespace App\Services;

use Smalot\PdfParser\Parser;
use Spatie\PdfToText\Pdf;

class PdfProcessingService
{
    /**
     * Extract text from PDF using multiple strategies
     */
    public function extractText(string $pdfPath): string
    {
        $fullPath = storage_path('app/public/' . $pdfPath);

        // Strategy 1: Try spatie/pdf-to-text (pdftotext wrapper)
        try {
            return Pdf::getText($fullPath);
        } catch (\Exception $e) {
            Log::warning('pdftotext failed, trying parser', ['error' => $e->getMessage()]);
        }

        // Strategy 2: Try smalot/pdfparser
        try {
            $parser = new Parser();
            $pdf = $parser->parseFile($fullPath);
            return $pdf->getText();
        } catch (\Exception $e) {
            Log::warning('PDF parser failed, trying OCR', ['error' => $e->getMessage()]);
        }

        // Strategy 3: OCR for scanned documents (Tesseract)
        return $this->performOcr($fullPath);
    }

    /**
     * Perform OCR using Tesseract
     */
    protected function performOcr(string $pdfPath): string
    {
        $imagePath = $this->convertPdfToImages($pdfPath);

        $tesseract = new \thiagoalessio\TesseractOCR\TesseractOCR($imagePath);
        $tesseract->lang('eng');

        return $tesseract->run();
    }

    /**
     * Extract metadata from PDF
     */
    public function extractMetadata(string $pdfPath): array
    {
        $parser = new Parser();
        $pdf = $parser->parseFile(storage_path('app/public/' . $pdfPath));

        $details = $pdf->getDetails();

        return [
            'title' => $details['Title'] ?? null,
            'author' => $details['Author'] ?? null,
            'subject' => $details['Subject'] ?? null,
            'keywords' => $details['Keywords'] ?? null,
            'creator' => $details['Creator'] ?? null,
            'producer' => $details['Producer'] ?? null,
            'creation_date' => $details['CreationDate'] ?? null,
            'page_count' => count($pdf->getPages()),
        ];
    }
}
```

---

## 5. SERVICE LAYER ARCHITECTURE

### 5.1 Service Organization

```
app/
├── Services/
│   ├── AI/
│   │   ├── BillSummarizationService.php
│   │   ├── SubmissionAnalysisService.php
│   │   └── SentimentAnalysisService.php
│   ├── Communication/
│   │   ├── SmsSubmissionService.php
│   │   ├── UssdMenuService.php
│   │   └── NotificationService.php
│   ├── Bill/
│   │   ├── BillClauseParsingService.php
│   │   ├── PdfProcessingService.php
│   │   └── BillPublishingService.php
│   └── Analytics/
│       ├── ClauseAnalyticsService.php
│       ├── SubmissionTrendService.php
│       └── GeographicAnalyticsService.php
```

### 5.2 Service Provider Registration

```php
// app/Providers/AppServiceProvider.php
public function register(): void
{
    $this->app->singleton(BillSummarizationService::class);
    $this->app->singleton(SmsSubmissionService::class);
    $this->app->singleton(UssdMenuService::class);
    $this->app->singleton(PdfProcessingService::class);
}
```

---

## 6. ANALYTICS & REPORTING SYSTEM

### 6.1 Service: `SubmissionAnalysisService`

```php
namespace App\Services\AI;

class SubmissionAnalysisService
{
    protected OpenAI\Client $client;

    /**
     * Analyze all submissions for a bill
     */
    public function analyzeBillSubmissions(Bill $bill): array
    {
        $submissions = $bill->submissions()
                            ->where('status', '!=', 'spam')
                            ->get();

        return [
            'topics' => $this->clusterByTopics($submissions),
            'sentiment' => $this->analyzeSentiment($submissions),
            'summary' => $this->generateSubmissionSummary($submissions),
            'key_arguments' => $this->extractKeyArguments($submissions),
        ];
    }

    /**
     * Cluster submissions by topic using AI
     */
    protected function clusterByTopics(Collection $submissions): array
    {
        $allContent = $submissions->pluck('content')->implode("\n\n---\n\n");

        $response = $this->client->chat()->create([
            'model' => 'gpt-4-turbo-preview',
            'messages' => [
                ['role' => 'system', 'content' => 'You are an expert at analyzing public feedback and identifying common themes.'],
                ['role' => 'user', 'content' => <<<PROMPT
Analyze these public submissions and identify 5-8 main topics/themes.
For each topic, provide:
- Topic name
- Brief description
- Number of submissions addressing this topic
- Representative quotes

Submissions:
{$allContent}

Return as JSON.
PROMPT
                ],
            ],
            'response_format' => ['type' => 'json_object'],
        ]);

        return json_decode($response->choices[0]->message->content, true)['topics'];
    }

    /**
     * Analyze sentiment using AI
     */
    protected function analyzeSentiment(Collection $submissions): array
    {
        // Batch process submissions for efficiency
        $sentiments = [];

        foreach ($submissions->chunk(10) as $chunk) {
            $contents = $chunk->pluck('content')->toArray();

            $response = $this->client->chat()->create([
                'model' => 'gpt-4-turbo-preview',
                'messages' => [
                    ['role' => 'system', 'content' => 'Analyze sentiment as: strongly_support, support, neutral, oppose, strongly_oppose'],
                    ['role' => 'user', 'content' => json_encode($contents)],
                ],
                'response_format' => ['type' => 'json_object'],
            ]);

            $batchSentiments = json_decode($response->choices[0]->message->content, true);
            $sentiments = array_merge($sentiments, $batchSentiments);
        }

        return $this->aggregateSentiments($sentiments);
    }
}
```

---

## 7. TESTING STRATEGY

### 7.1 Test Structure

```
tests/
├── Feature/
│   ├── Bill/
│   │   ├── ClauseSubmissionTest.php
│   │   ├── BillSummarizationTest.php
│   │   └── PdfProcessingTest.php
│   ├── Submission/
│   │   ├── SmsSubmissionTest.php
│   │   ├── UssdSubmissionTest.php
│   │   └── WebSubmissionTest.php
│   └── Analytics/
│       └── SubmissionAnalysisTest.php
├── Unit/
│   ├── Services/
│   │   ├── BillClauseParsingServiceTest.php
│   │   ├── SmsSubmissionServiceTest.php
│   │   └── UssdMenuServiceTest.php
│   └── Models/
│       └── BillClauseTest.php
└── Browser/
    ├── ClauseNavigationTest.php
    └── SubmissionWorkflowTest.php
```

### 7.2 Example Test: Clause Submission

```php
it('allows citizen to submit feedback on specific clause', function () {
    $bill = Bill::factory()->create(['status' => 'open_for_participation']);
    $clause = BillClause::factory()->create(['bill_id' => $bill->id]);
    $citizen = User::factory()->citizen()->create();

    $this->actingAs($citizen);

    $response = $this->postJson(route('api.v1.submissions.store'), [
        'bill_id' => $bill->id,
        'clause_id' => $clause->id,
        'submission_scope' => 'clause',
        'content' => 'This clause needs clarification on...',
        'submission_type' => 'suggestion',
    ]);

    $response->assertSuccessful();

    expect(Submission::count())->toBe(1);
    expect(Submission::first())
        ->clause_id->toBe($clause->id)
        ->submission_scope->toBe('clause');
});
```

---

## IMPLEMENTATION PRIORITY

### Week 1-2: Clause System
1. ✅ Create migrations for `bill_clauses`, `clause_analytics`
2. ✅ Implement `BillClause` model and relationships
3. ✅ Build `BillClauseParsingService`
4. ✅ Create API endpoints
5. ✅ Develop Vue components
6. ✅ Write feature tests

### Week 3: Multi-Channel
1. ✅ Implement `SmsSubmissionService`
2. ✅ Create SMS webhook endpoint
3. ✅ Build `UssdMenuService`
4. ✅ Test with Twilio sandbox
5. ✅ Write integration tests

### Week 4: AI Integration
1. ✅ Setup OpenAI API configuration
2. ✅ Implement `BillSummarizationService`
3. ✅ Create summarization job
4. ✅ Add TTS audio generation
5. ✅ Test with real bills

---

**Next Steps:**
1. Review and approve architecture
2. Setup development environment with AI API keys
3. Begin Week 1 implementation (Clause System)
4. Daily standups for progress tracking
