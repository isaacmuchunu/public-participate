<?php

namespace App\Http\Requests\Submission;

use App\Http\Middleware\ThrottleSubmissions;
use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreSubmissionRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
            'submission_type' => ['required', 'in:support,oppose,amend,neutral'],
            'content' => ['required', 'string', 'min:10', 'max:10000'],
            'language' => ['required', 'in:en,sw,other'],
            'submitter_name' => ['nullable', 'string', 'max:255'],
            'submitter_phone' => ['nullable', 'string', 'max:20'],
            'submitter_email' => ['nullable', 'email', 'max:255'],
            'submitter_county' => ['nullable', 'string', 'max:255'],
            'draft_id' => ['nullable', 'integer', 'exists:submission_drafts,id'],
        ];
    }

    /**
     * Get fields that should be sanitized
     */
    protected function getSanitizableFields(): array
    {
        return ['content', 'submitter_name', 'submitter_county'];
    }

    /**
     * Get sanitization configuration for a specific field
     */
    protected function getFieldSanitizationConfig(string $field): array
    {
        if ($field === 'content') {
            return $this->htmlFieldConfig(['p', 'br', 'strong', 'em', 'u', 'ul', 'ol', 'li']);
        }

        return parent::getFieldSanitizationConfig($field);
    }

    /**
     * Configure the validator instance
     */
    public function withValidator(Validator $validator): void
    {
        $validator->after(function ($validator) {
            // Check if bill is open for participation
            if ($this->has('bill_id')) {
                $bill = Bill::find($this->bill_id);

                if ($bill && ! $bill->isOpenForParticipation()) {
                    $validator->errors()->add('bill_id', 'This bill is not currently open for public participation.');
                }
            }

            // Check daily submission limit
            if ($this->user() && ! ThrottleSubmissions::canSubmit($this->user()->id)) {
                $remaining = ThrottleSubmissions::getRemainingSubmissions($this->user()->id);
                $validator->errors()->add('rate_limit', "Daily submission limit reached. You have {$remaining} submissions remaining today.");
            }

            // Check for duplicate submissions (same user, same bill, same content within 1 hour)
            if ($this->user() && $this->has('bill_id') && $this->has('content')) {
                $recentSubmission = $this->user()
                    ->submissions()
                    ->where('bill_id', $this->bill_id)
                    ->where('content', $this->content)
                    ->where('created_at', '>=', now()->subHour())
                    ->exists();

                if ($recentSubmission) {
                    $validator->errors()->add('content', 'You have already submitted this exact content recently. Please wait before submitting again.');
                }
            }
        });
    }

    /**
     * Get custom messages for validation errors
     */
    public function messages(): array
    {
        return [
            'bill_id.required' => 'Please select a bill to submit feedback for.',
            'bill_id.exists' => 'The selected bill does not exist.',
            'submission_type.required' => 'Please indicate your position on this bill.',
            'content.required' => 'Please provide your submission content.',
            'content.min' => 'Your submission must be at least 10 characters.',
            'content.max' => 'Your submission cannot exceed 10,000 characters.',
        ];
    }
}
