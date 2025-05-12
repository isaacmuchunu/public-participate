<?php

namespace App\Http\Requests\Clerk;

use Illuminate\Foundation\Http\FormRequest;

class StoreLegislatorRequest extends FormRequest
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
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email:rfc,dns', 'max:255', 'unique:users,email'],
            'phone' => ['nullable', 'string', 'max:32', 'unique:users,phone'],
            'legislative_house' => ['required', 'in:national_assembly,senate'],
            'county' => ['nullable', 'string', 'max:255'],
            'constituency' => ['nullable', 'string', 'max:255'],
            'invitation_message' => ['nullable', 'string', 'max:1000'],
            'expires_in_days' => ['nullable', 'integer', 'min:1', 'max:30'],
        ];
    }
}
