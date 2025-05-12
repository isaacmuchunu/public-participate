<?php

namespace App\Http\Requests\Submission;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionRequest extends FormRequest
{
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
            'content' => ['required', 'string', 'min:10'],
            'language' => ['required', 'in:en,sw,other'],
            'submitter_name' => ['nullable', 'string', 'max:255'],
            'submitter_phone' => ['nullable', 'string', 'max:20'],
            'submitter_email' => ['nullable', 'email', 'max:255'],
            'submitter_county' => ['nullable', 'string', 'max:255'],
            'draft_id' => ['nullable', 'integer', 'exists:submission_drafts,id'],
        ];
    }
}
