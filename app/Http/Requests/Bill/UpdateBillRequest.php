<?php

namespace App\Http\Requests\Bill;

use App\Models\Bill;
use Illuminate\Foundation\Http\FormRequest;

class UpdateBillRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $bill = $this->route('bill');

        return $bill instanceof Bill
            ? $this->user()?->can('update', $bill) ?? false
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
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'type' => ['required', 'in:public,private,money'],
            'house' => ['required', 'in:national_assembly,senate,both'],
            'status' => ['required', 'in:draft,gazetted,open_for_participation,closed,committee_review,passed,rejected'],
            'sponsor' => ['nullable', 'string', 'max:255'],
            'committee' => ['nullable', 'string', 'max:255'],
            'gazette_date' => ['nullable', 'date'],
            'participation_start_date' => ['nullable', 'date'],
            'participation_end_date' => ['nullable', 'date', 'after:participation_start_date'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['string', 'max:50'],
            'pdf_file' => ['nullable', 'file', 'mimes:pdf', 'max:10240'],
        ];
    }
}
