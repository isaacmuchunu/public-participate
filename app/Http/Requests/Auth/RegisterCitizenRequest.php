<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;

class RegisterCitizenRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
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
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:users,email'],
            'phone' => ['required', 'string', 'regex:/^07\d{8}$/', 'unique:users,phone'],
            'national_id' => ['required', 'string', 'min:6', 'max:12', 'unique:users,national_id'],
            'county_id' => ['required', 'integer', 'exists:counties,id'],
            'constituency_id' => [
                'required',
                'integer',
                Rule::exists('constituencies', 'id')->where(fn ($query) => $query->where('county_id', $this->integer('county_id'))),
            ],
            'ward_id' => [
                'required',
                'integer',
                Rule::exists('wards', 'id')->where(fn ($query) => $query->where('constituency_id', $this->integer('constituency_id'))),
            ],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'phone' => $this->phone ? preg_replace('/\D+/', '', $this->phone) : null,
            'national_id' => $this->national_id ? preg_replace('/\s+/', '', $this->national_id) : null,
        ]);
    }
}
