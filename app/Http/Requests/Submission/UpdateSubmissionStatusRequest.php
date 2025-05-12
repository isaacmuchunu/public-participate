<?php

namespace App\Http\Requests\Submission;

use App\Models\Submission;
use Illuminate\Foundation\Http\FormRequest;

class UpdateSubmissionStatusRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $submission = $this->route('submission');

        return $submission instanceof Submission
            ? $this->user()?->can('update', $submission) ?? false
            : false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'in:pending,reviewed,included,aggregated,rejected'],
            'review_notes' => ['nullable', 'string'],
        ];
    }
}
