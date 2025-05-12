<?php

namespace App\Http\Requests\Legislator;

use Illuminate\Foundation\Http\FormRequest;

class StoreHighlightRequest extends FormRequest
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

        return $user->isLegislator() || $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'clause_reference' => ['nullable', 'string', 'max:255'],
            'excerpt' => ['nullable', 'string'],
            'note' => ['nullable', 'string'],
            'submission_id' => ['nullable', 'integer', 'exists:submissions,id'],
            'metadata' => ['nullable', 'array'],
        ];
    }
}
