<?php

namespace App\Http\Requests\Submission;

use Illuminate\Foundation\Http\FormRequest;

class StoreSubmissionDraftRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $user = $this->user();

        if (! $user) {
            return false;
        }

        return $user->isCitizen() || $user->isAdmin();
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
            'submission_type' => ['nullable', 'in:support,oppose,amend,neutral'],
            'language' => ['nullable', 'in:en,sw,other'],
            'content' => ['nullable', 'string'],
            'contact_information' => ['nullable', 'array'],
            'contact_information.name' => ['nullable', 'string', 'max:255'],
            'contact_information.email' => ['nullable', 'email', 'max:255'],
            'contact_information.phone' => ['nullable', 'string', 'max:20'],
            'contact_information.county' => ['nullable', 'string', 'max:255'],
            'attachments' => ['nullable', 'array'],
            'attachments.*' => ['array'],
        ];
    }
}
