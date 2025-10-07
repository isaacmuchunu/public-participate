<?php

namespace App\Http\Requests\Concerns;

use Mews\Purifier\Facades\Purifier;

trait SanitizesInput
{
    /**
     * Prepare the data for validation by sanitizing inputs
     */
    protected function prepareForValidation(): void
    {
        $sanitized = [];

        foreach ($this->getSanitizableFields() as $field) {
            if ($this->has($field)) {
                $value = $this->input($field);

                if (is_string($value)) {
                    $sanitized[$field] = $this->sanitizeField($field, $value);
                }
            }
        }

        if (! empty($sanitized)) {
            $this->merge($sanitized);
        }
    }

    /**
     * Sanitize a single field based on its type
     */
    protected function sanitizeField(string $field, string $value): string
    {
        // Get field configuration
        $config = $this->getFieldSanitizationConfig($field);

        // Apply appropriate sanitization
        if ($config['type'] === 'html') {
            return Purifier::clean($value, $config['rules'] ?? 'default');
        }

        if ($config['type'] === 'text') {
            return $this->sanitizeText($value);
        }

        if ($config['type'] === 'email') {
            return filter_var($value, FILTER_SANITIZE_EMAIL);
        }

        // Default: strip tags and trim
        return trim(strip_tags($value));
    }

    /**
     * Sanitize plain text (allows basic formatting but removes HTML)
     */
    protected function sanitizeText(string $value): string
    {
        // Remove HTML tags but preserve line breaks
        $value = strip_tags($value);

        // Normalize whitespace
        $value = preg_replace('/\s+/', ' ', $value);

        // Trim
        return trim($value);
    }

    /**
     * Get fields that should be sanitized
     * Override this method in your Form Request
     */
    protected function getSanitizableFields(): array
    {
        return [];
    }

    /**
     * Get sanitization configuration for a specific field
     * Override this method to customize per field
     */
    protected function getFieldSanitizationConfig(string $field): array
    {
        // Default configuration
        return [
            'type' => 'text', // text, html, email
            'rules' => null,  // Custom purifier rules if type is 'html'
        ];
    }

    /**
     * Helper method to configure HTML sanitization for rich content
     */
    protected function htmlFieldConfig(array $allowedTags = []): array
    {
        if (empty($allowedTags)) {
            // Default safe tags for user-generated content
            $allowedTags = ['p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li', 'a'];
        }

        return [
            'type' => 'html',
            'rules' => [
                'HTML.Allowed' => implode(',', $allowedTags),
                'HTML.SafeIframe' => false,
                'HTML.SafeObject' => false,
                'HTML.SafeEmbed' => false,
                'AutoFormat.RemoveEmpty' => true,
            ],
        ];
    }
}
