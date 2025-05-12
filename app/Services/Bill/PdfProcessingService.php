<?php

namespace App\Services\Bill;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class PdfProcessingService
{
    /**
     * Extract text from PDF using multiple strategies
     */
    public function extractText(string $pdfPath): string
    {
        $fullPath = Storage::disk('public')->path($pdfPath);

        if (! file_exists($fullPath)) {
            throw new \Exception("PDF file not found at path: {$fullPath}");
        }

        // Strategy 1: Try smalot/pdfparser (pure PHP solution)
        try {
            return $this->extractWithParser($fullPath);
        } catch (\Exception $e) {
            Log::warning('PDF parser failed, trying alternative method', [
                'error' => $e->getMessage(),
                'path' => $pdfPath,
            ]);
        }

        // Strategy 2: Could add pdftotext or Tesseract OCR here
        // For now, throw exception if parser fails
        throw new \Exception('Unable to extract text from PDF. File may be corrupted or encrypted.');
    }

    /**
     * Extract text using Smalot PDF Parser
     */
    protected function extractWithParser(string $fullPath): string
    {
        $parser = new Parser;
        $pdf = $parser->parseFile($fullPath);

        $text = $pdf->getText();

        if (empty(trim($text))) {
            throw new \Exception('PDF contains no extractable text. May be scanned or image-based.');
        }

        return $text;
    }

    /**
     * Extract metadata from PDF
     */
    public function extractMetadata(string $pdfPath): array
    {
        $fullPath = Storage::disk('public')->path($pdfPath);

        $parser = new Parser;
        $pdf = $parser->parseFile($fullPath);

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

    /**
     * Clean extracted text (remove excessive whitespace, normalize line breaks)
     */
    public function cleanText(string $text): string
    {
        // Normalize line breaks
        $text = str_replace(["\r\n", "\r"], "\n", $text);

        // Remove excessive whitespace
        $text = preg_replace('/[ \t]+/', ' ', $text);

        // Remove excessive line breaks (more than 2)
        $text = preg_replace('/\n{3,}/', "\n\n", $text);

        return trim($text);
    }

    /**
     * Validate PDF file
     */
    public function validatePdf(string $pdfPath): bool
    {
        $fullPath = Storage::disk('public')->path($pdfPath);

        if (! file_exists($fullPath)) {
            return false;
        }

        // Check file extension
        if (! str_ends_with(strtolower($pdfPath), '.pdf')) {
            return false;
        }

        // Check file signature (PDF magic bytes)
        $handle = fopen($fullPath, 'r');
        $header = fread($handle, 5);
        fclose($handle);

        return $header === '%PDF-';
    }
}
