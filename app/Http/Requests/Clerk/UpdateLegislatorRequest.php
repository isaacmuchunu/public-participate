<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateLegislatorRequest extends FormRequest
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

        return $user->isClerk() || $user->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $legislator = $this->route('legislator');

        return [
            'name' => ['sometimes', 'string', 'max:255'],
            'email' => [
                'sometimes',
                'email:rfc,dns',
                'max:255',
                Rule::unique('users', 'email')->ignore($legislator?->id),
            ],
            'phone' => [
                'nullable',
                'string',
                'max:32',
                Rule::unique('users', 'phone')->ignore($legislator?->id),
            ],
            'legislative_house' => ['sometimes', 'in:national_assembly,senate'],
            'county' => ['nullable', 'string', 'max:255'],
            'constituency' => ['nullable', 'string', 'max:255'],
            'suspended' => ['sometimes', 'boolean'],
            'reset_invitation' => ['sometimes', 'boolean'],
            'invitation_message' => ['nullable', 'string', 'max:1000'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
