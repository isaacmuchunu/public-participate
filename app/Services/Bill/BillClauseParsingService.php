<?php

namespace App\Services\Bill;

use App\Models\Bill;
use App\Models\BillClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class BillClauseParsingService
{
    public function __construct(
        protected PdfProcessingService $pdfService
    ) {}

    /**
     * Parse bill PDF and extract clauses
     */
    public function parseBillClauses(Bill $bill): Collection
    {
        if (! $bill->pdf_path) {
            throw new \Exception('Bill has no PDF file attached');
        }

        // Extract text from PDF
        $pdfText = $this->pdfService->extractText($bill->pdf_path);
        $cleanText = $this->pdfService->cleanText($pdfText);

        // Parse clauses from text
        $clausesData = $this->identifyClauses($cleanText);

        // Save clauses to database
        return $this->saveClauses($bill, $clausesData);
    }

    /**
     * Save parsed clauses to database
     */
    protected function saveClauses(Bill $bill, array $clausesData): Collection
    {
        $savedClauses = collect();

        DB::transaction(function () use ($bill, $clausesData, &$savedClauses) {
            // Delete existing clauses for this bill
            $bill->clauses()->delete();

            // Create new clauses
            foreach ($clausesData as $clauseData) {
                $clause = BillClause::create([
                    'bill_id' => $bill->id,
                    'clause_number' => $clauseData['number'],
                    'clause_type' => $clauseData['type'],
                    'parent_clause_id' => $clauseData['parent_id'] ?? null,
                    'title' => $clauseData['title'] ?? null,
                    'content' => $clauseData['content'],
                    'metadata' => $clauseData['metadata'] ?? [],
                    'display_order' => $clauseData['order'],
                ]);

                $savedClauses->push($clause);
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

        // Split text into lines for processing
        $lines = explode("\n", $text);

        // Pattern for identifying sections
        // Matches: "Section 5.", "Section 5 -", "5.", "SECTION 5"
        $sectionPattern = '/^(?:Section\s+)?(\d+)\.?\s*-?\s*(.*)$/i';

        // Pattern for identifying subsections
        // Matches: "(1)", "(a)", "5.1", "5.1.1"
        $subsectionPattern = '/^\(([a-z0-9]+)\)\s+(.+)$/i';
        $numberedSubPattern = '/^(\d+\.\d+(?:\.\d+)?)\s+(.+)$/';

        $currentSection = null;
        $currentContent = '';

        foreach ($lines as $lineNum => $line) {
            $line = trim($line);

            if (empty($line)) {
                continue;
            }

            // Check for section header
            if (preg_match($sectionPattern, $line, $matches)) {
                // Save previous section if exists
                if ($currentSection) {
                    $clauses[] = [
                        'number' => $currentSection['number'],
                        'type' => 'section',
                        'title' => $currentSection['title'],
                        'content' => trim($currentContent),
                        'order' => $order++,
                        'metadata' => ['line_start' => $currentSection['line']],
                    ];
                }

                // Start new section
                $currentSection = [
                    'number' => $matches[1],
                    'title' => trim($matches[2]),
                    'line' => $lineNum,
                ];
                $currentContent = '';
            } else {
                // Accumulate content
                $currentContent .= $line."\n";
            }
        }

        // Save last section
        if ($currentSection) {
            $clauses[] = [
                'number' => $currentSection['number'],
                'type' => 'section',
                'title' => $currentSection['title'],
                'content' => trim($currentContent),
                'order' => $order++,
                'metadata' => ['line_start' => $currentSection['line']],
            ];
        }

        // If no clauses found with section pattern, create a single clause with all content
        if (empty($clauses)) {
            $clauses[] = [
                'number' => '1',
                'type' => 'section',
                'title' => 'Full Bill Text',
                'content' => $text,
                'order' => 0,
                'metadata' => ['auto_generated' => true],
            ];
        }

        return $clauses;
    }

    /**
     * Parse subsections within a section
     */
    protected function parseSubsections(string $content, int $sectionNumber): array
    {
        $subsections = [];
        $lines = explode("\n", $content);

        // Pattern for subsections: (1), (a), etc.
        $pattern = '/^\(([a-z0-9]+)\)\s+(.+)$/i';

        $currentSubsection = null;
        $currentContent = '';
        $order = 0;

        foreach ($lines as $line) {
            $line = trim($line);

            if (preg_match($pattern, $line, $matches)) {
                // Save previous subsection
                if ($currentSubsection) {
                    $subsections[] = [
                        'number' => $currentSubsection['number'],
                        'type' => 'subsection',
                        'content' => trim($currentContent),
                        'order' => $order++,
                    ];
                }

                // Start new subsection
                $currentSubsection = [
                    'number' => $matches[1],
                ];
                $currentContent = $matches[2];
            } else {
                $currentContent .= ' '.$line;
            }
        }

        // Save last subsection
        if ($currentSubsection) {
            $subsections[] = [
                'number' => $currentSubsection['number'],
                'type' => 'subsection',
                'content' => trim($currentContent),
                'order' => $order++,
            ];
        }

        return $subsections;
    }

    /**
     * Manually add a clause to a bill
     */
    public function addClause(Bill $bill, array $clauseData): BillClause
    {
        $order = $bill->clauses()->max('display_order') + 1;

        return BillClause::create([
            'bill_id' => $bill->id,
            'clause_number' => $clauseData['clause_number'],
            'clause_type' => $clauseData['clause_type'] ?? 'section',
            'parent_clause_id' => $clauseData['parent_clause_id'] ?? null,
            'title' => $clauseData['title'] ?? null,
            'content' => $clauseData['content'],
            'metadata' => $clauseData['metadata'] ?? [],
            'display_order' => $order,
        ]);
    }

    /**
     * Update clause content
     */
    public function updateClause(BillClause $clause, array $data): BillClause
    {
        $clause->update($data);

        return $clause->fresh();
    }

    /**
     * Delete a clause
     */
    public function deleteClause(BillClause $clause): void
    {
        $clause->delete();
    }
}
