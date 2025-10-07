<?php

namespace App\Services;

use App\Models\Bill;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\View;

class BillPdfService
{
    /**
     * Generate PDF for a bill with all clauses
     */
    public function generateBillPdf(Bill $bill): string
    {
        // Load bill with clauses
        $bill->load(['clauses' => function ($query) {
            $query->orderBy('order');
        }]);

        // Generate PDF content
        $pdfContent = $this->renderBillPdf($bill);

        // Create PDF
        $pdf = Pdf::loadHTML($pdfContent)
            ->setPaper('a4', 'portrait')
            ->setOptions([
                'defaultFont' => 'sans-serif',
                'isHtml5ParserEnabled' => true,
                'isPhpEnabled' => true,
            ]);

        // Generate unique filename
        $filename = 'bill_' . $bill->id . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';

        // Store PDF in storage/app/private/bills
        $path = 'bills/' . $filename;
        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Generate PDF for a specific clause
     */
    public function generateClausePdf(Bill $bill, $clauseId): string
    {
        $clause = $bill->clauses()->find($clauseId);

        if (!$clause) {
            throw new \InvalidArgumentException('Clause not found');
        }

        $pdfContent = $this->renderClausePdf($bill, $clause);

        $pdf = Pdf::loadHTML($pdfContent)
            ->setPaper('a4', 'portrait');

        $filename = 'clause_' . $clause->id . '_' . now()->format('Y_m_d_H_i_s') . '.pdf';
        $path = 'clauses/' . $filename;
        Storage::disk('private')->put($path, $pdf->output());

        return $path;
    }

    /**
     * Render bill PDF content
     */
    private function renderBillPdf(Bill $bill): string
    {
        return View::make('pdfs.bill', [
            'bill' => $bill,
            'clauses' => $bill->clauses,
        ])->render();
    }

    /**
     * Render clause PDF content
     */
    private function renderClausePdf(Bill $bill, $clause): string
    {
        return View::make('pdfs.clause', [
            'bill' => $bill,
            'clause' => $clause,
        ])->render();
    }

    /**
     * Get PDF download URL
     */
    public function getPdfUrl(string $path): string
    {
        return route('bills.pdf.download', ['path' => encrypt($path)]);
    }

    /**
     * Check if PDF exists and is accessible
     */
    public function pdfExists(string $path): bool
    {
        return Storage::disk('private')->exists($path);
    }

    /**
     * Delete old PDFs (cleanup)
     */
    public function cleanupOldPdfs(int $daysOld = 7): int
    {
        $cutoffDate = now()->subDays($daysOld);

        $files = Storage::disk('private')->files('bills');
        $clauseFiles = Storage::disk('private')->files('clauses');

        $allFiles = array_merge($files, $clauseFiles);
        $deletedCount = 0;

        foreach ($allFiles as $file) {
            $lastModified = Storage::disk('private')->lastModified($file);
            if ($lastModified < $cutoffDate->timestamp) {
                Storage::disk('private')->delete($file);
                $deletedCount++;
            }
        }

        return $deletedCount;
    }
}
