<?php

namespace App\Http\Requests\Engagement;

use App\Http\Requests\Concerns\SanitizesInput;
use App\Models\Bill;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class StoreEngagementRequest extends FormRequest
{
    use SanitizesInput;

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->isCitizen();
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'recipient_id' => ['required', 'integer', 'exists:users,id'],
            'bill_id' => ['required', 'integer', 'exists:bills,id'],
            'submission_id' => ['nullable', 'integer', 'exists:submissions,id'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
        ];
    }

    /**
     * Get fields that should be sanitized
     */
    protected function getSanitizableFields(): array
    {
        return ['subject', 'message'];
    }

    /**
     * Get sanitization configuration for a specific field
     */
    protected function getFieldSanitizationConfig(string $field): array
    {
        if ($field === 'message') {
            return $this->htmlFieldConfig(['p', 'br', 'strong', 'em', 'u']);
        }

        return parent::getFieldSanitizationConfig($field);
    }

    /**
     * Get the recipient user
     */
    public function getRecipient(): User
    {
        return User::findOrFail($this->validated('recipient_id'));
    }

    /**
     * Get the bill
     */
    public function getBill(): Bill
    {
        return Bill::findOrFail($this->validated('bill_id'));
    }

    /**
     * Get the submission (if provided)
     */
    public function getSubmission(): ?Submission
    {
        $submissionId = $this->validated('submission_id');

        return $submissionId ? Submission::findOrFail($submissionId) : null;
    }

    /**
     * Get custom messages for validation errors
     */
    public function messages(): array
    {
        return [
            'recipient_id.required' => 'Please select a legislator to contact.',
            'recipient_id.exists' => 'The selected legislator does not exist.',
            'bill_id.required' => 'Please select a bill for this engagement.',
            'bill_id.exists' => 'The selected bill does not exist.',
            'subject.required' => 'Please provide a subject for your message.',
            'message.required' => 'Please provide a message.',
            'message.min' => 'Your message must be at least 10 characters.',
            'message.max' => 'Your message cannot exceed 5,000 characters.',
        ];
    }
}
