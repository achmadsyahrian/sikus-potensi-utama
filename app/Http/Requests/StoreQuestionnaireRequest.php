<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreQuestionnaireRequest extends FormRequest
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
            'academic_period_id' => ['required', 'integer', 'exists:academic_periods,id'],
            'start_date' => ['required', 'date'],
            'description' => ['nullable'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'targets' => ['required', 'array'],
            'targets.*.target_type' => ['required', 'string', Rule::in(['role', 'university', 'faculty', 'program_study'])],
            'targets.*.target_value' => ['required', 'max:255'],
        ];
    }
}
