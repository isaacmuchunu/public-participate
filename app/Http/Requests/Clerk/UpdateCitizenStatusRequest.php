<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCitizenStatusRequest extends FormRequest
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
        return [
            'action' => ['required', 'in:verify,unverify,suspend,restore'],
            'reason' => ['nullable', 'string', 'max:500'],
        ];
    }
}
